<?php

namespace App\Services;

use App\Filament\Resources\BankChecks\BankCheckResource;
use App\Filament\Resources\Customers\CustomerResource;
use App\Filament\Resources\DueObligations\DueObligationResource;
use App\Filament\Resources\Items\ItemResource;
use App\Filament\Resources\JournalEntries\JournalEntryResource;
use App\Filament\Resources\PurchaseInvoices\PurchaseInvoiceResource;
use App\Filament\Resources\PurchaseRequests\PurchaseRequestResource;
use App\Filament\Resources\ReceiptVouchers\ReceiptVoucherResource;
use App\Filament\Resources\SalesInvoices\SalesInvoiceResource;
use App\Filament\Resources\SupplierPaymentVouchers\SupplierPaymentVoucherResource;
use App\Filament\Resources\SupplierPurchaseOrders\SupplierPurchaseOrderResource;
use App\Filament\Resources\Suppliers\SupplierResource;
use App\Models\BankAccount;
use App\Models\BankCheck;
use App\Models\DueObligation;
use App\Models\FiscalYear;
use App\Models\Item;
use App\Models\JournalEntry;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseRequest;
use App\Models\ReceiptVoucher;
use App\Models\SalesInvoice;
use App\Models\StockBalance;
use App\Models\SupplierPaymentVoucher;
use App\Models\SupplierPurchaseOrder;
use App\Models\Treasury;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    /** @return array<string, mixed> */
    public function data(
        string $period = 'month',
        ?string $fromDate = null,
        ?string $toDate = null,
    ): array {
        [$from, $to, $periodLabel] = $this->resolvePeriod($period, $fromDate, $toDate);

        $salesInvoices = SalesInvoice::query()
            ->with(['customer', 'items.item', 'receiptAllocations'])
            ->whereBetween('invoice_date', [$from->toDateString(), $to->toDateString()])
            ->get();

        $purchaseInvoices = PurchaseInvoice::query()
            ->with(['supplier', 'items.item', 'supplierPaymentAllocations'])
            ->whereBetween('invoice_date', [$from->toDateString(), $to->toDateString()])
            ->get();

        $receipts = ReceiptVoucher::query()
            ->where('receipt_type', ReceiptVoucher::TYPE_CUSTOMER)
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->get();

        $payments = SupplierPaymentVoucher::query()
            ->where('payment_type', SupplierPaymentVoucher::TYPE_SUPPLIER)
            ->whereBetween('voucher_date', [$from->toDateString(), $to->toDateString()])
            ->get();

        // الحركة النقدية الفعلية تشمل الفواتير الكاش نفسها + السندات.
        $cashSalesInvoicesAmount = round(
            (float) $salesInvoices
                ->filter(fn (SalesInvoice $invoice): bool => $this->isCashPaymentType($invoice->payment_type))
                ->sum(fn (SalesInvoice $invoice): float => $invoice->totalAmount()),
            2
        );

        $cashPurchaseInvoicesAmount = round(
            (float) $purchaseInvoices
                ->filter(fn (PurchaseInvoice $invoice): bool => $this->isCashPaymentType($invoice->payment_type))
                ->sum(fn (PurchaseInvoice $invoice): float => $invoice->totalAmount()),
            2
        );

        $customerReceiptVouchersAmount = round((float) $receipts->sum('amount'), 2);
        $supplierPaymentVouchersAmount = round((float) $payments->sum('amount'), 2);

        $customerCashIn = round($cashSalesInvoicesAmount + $customerReceiptVouchersAmount, 2);
        $supplierCashOut = round($cashPurchaseInvoicesAmount + $supplierPaymentVouchersAmount, 2);

        /*
         * ملخص تحصيل/سداد الفواتير نفسها:
         * - الفاتورة النقدية تعتبر مسددة بالكامل.
         * - الفاتورة الآجلة تعتمد على توزيعات سندات القبض/الصرف.
         * وبذلك لا نخلط بين "قيمة الفواتير" و"حركة السندات خلال الفترة".
         */
        $salesInvoiceSummary = $this->salesInvoiceSummary($salesInvoices);
        $purchaseInvoiceSummary = $this->purchaseInvoiceSummary($purchaseInvoices);

        $dueRecords = app(DueObligationService::class)->records();

        $customerOverdue = $dueRecords
            ->where('source_type', DueObligation::TYPE_SALE)
            ->filter(fn (DueObligation $record): bool => $this->isOverdue($record))
            ->values();

        $supplierOverdue = $dueRecords
            ->where('source_type', DueObligation::TYPE_PURCHASE)
            ->filter(fn (DueObligation $record): bool => $this->isOverdue($record))
            ->values();

        $cashAndBanks = $this->cashAndBanksBalance();

        $income = app(IncomeStatementService::class)->report(
            $from->toDateString(),
            $to->toDateString(),
            false,
        );

        $lowStockItems = $this->lowStockItems();
        $dueChecks = $this->checksDueSoon();

        return [
            'period' => [
                'key' => $period,
                'label' => $periodLabel,
                'from' => $from->format('Y/m/d'),
                'to' => $to->format('Y/m/d'),
            ],

            'cards' => [
                [
                    'key' => 'sales',
                    'label' => 'مبيعات الفترة',
                    'value' => $salesInvoiceSummary['total'],
                    'sub_lines' => [
                        ['label' => 'المحصل', 'value' => $salesInvoiceSummary['paid'], 'tone' => 'positive'],
                        ['label' => 'المتبقي', 'value' => $salesInvoiceSummary['remaining'], 'tone' => 'negative'],
                    ],
                    'icon' => '🛒',
                    'tone' => 'green',
                    'url' => $this->safeResourceUrl(SalesInvoiceResource::class),
                ],
                [
                    'key' => 'purchases',
                    'label' => 'مشتريات الفترة',
                    'value' => $purchaseInvoiceSummary['total'],
                    'sub_lines' => [
                        ['label' => 'المسدد', 'value' => $purchaseInvoiceSummary['paid'], 'tone' => 'positive'],
                        ['label' => 'المتبقي', 'value' => $purchaseInvoiceSummary['remaining'], 'tone' => 'negative'],
                    ],
                    'icon' => '🛍️',
                    'tone' => 'blue',
                    'url' => $this->safeResourceUrl(PurchaseInvoiceResource::class),
                ],
                [
                    'key' => 'receipts',
                    'label' => 'المقبوض من العملاء',
                    'value' => $customerCashIn,
                    'sub_lines' => [
                        ['label' => 'فواتير بيع كاش', 'value' => $cashSalesInvoicesAmount, 'tone' => 'positive'],
                        ['label' => 'سندات قبض', 'value' => $customerReceiptVouchersAmount, 'tone' => 'positive'],
                    ],
                    'icon' => '↙',
                    'tone' => 'emerald',
                    'url' => $this->safeResourceUrl(ReceiptVoucherResource::class),
                ],
                [
                    'key' => 'payments',
                    'label' => 'المدفوع للموردين',
                    'value' => $supplierCashOut,
                    'sub_lines' => [
                        ['label' => 'فواتير شراء كاش', 'value' => $cashPurchaseInvoicesAmount, 'tone' => 'negative'],
                        ['label' => 'سندات صرف', 'value' => $supplierPaymentVouchersAmount, 'tone' => 'negative'],
                    ],
                    'icon' => '↗',
                    'tone' => 'orange',
                    'url' => $this->safeResourceUrl(SupplierPaymentVoucherResource::class),
                ],
                [
                    'key' => 'cash',
                    'label' => 'أرصدة الخزائن والبنوك',
                    'value' => $cashAndBanks,
                    'icon' => '🏦',
                    'tone' => 'violet',
                    'url' => null,
                ],
                [
                    'key' => 'profit',
                    'label' => ((float) $income['totals']['net_profit'] >= 0) ? 'صافي الربح' : 'صافي الخسارة',
                    'value' => abs((float) $income['totals']['net_profit']),
                    'signed_value' => (float) $income['totals']['net_profit'],
                    'sub_lines' => [
                        ['label' => 'الإيرادات', 'value' => (float) $income['totals']['revenue'], 'tone' => 'positive'],
                        ['label' => 'تكلفة الإيرادات', 'value' => (float) $income['totals']['cost'], 'tone' => 'negative'],
                        ['label' => 'المصروفات', 'value' => (float) $income['totals']['expenses'], 'tone' => 'negative'],
                    ],
                    'caption' => 'محسوب من القيود اليومية',
                    'icon' => '📈',
                    'tone' => ((float) $income['totals']['net_profit'] >= 0) ? 'green' : 'red',
                    'url' => null,
                ],
            ],

            'alerts' => [
                [
                    'label' => 'مبالغ العملاء المتأخرة',
                    'count' => $customerOverdue->count(),
                    'amount' => round((float) $customerOverdue->sum('remaining_amount'), 2),
                    'unit' => 'فاتورة',
                    'tone' => 'red',
                    'icon' => '👥',
                    'url' => $this->safeResourceUrl(DueObligationResource::class),
                ],
                [
                    'label' => 'مستحقات الموردين المتأخرة',
                    'count' => $supplierOverdue->count(),
                    'amount' => round((float) $supplierOverdue->sum('remaining_amount'), 2),
                    'unit' => 'فاتورة',
                    'tone' => 'orange',
                    'icon' => '👤',
                    'url' => $this->safeResourceUrl(DueObligationResource::class),
                ],
                [
                    'label' => 'أصناف تحت حد إعادة الطلب',
                    'count' => count($lowStockItems),
                    'amount' => null,
                    'unit' => 'صنف',
                    'tone' => 'amber',
                    'icon' => '📦',
                    'url' => $this->safeResourceUrl(ItemResource::class),
                    'items' => array_slice($lowStockItems, 0, 5),
                ],
                [
                    'label' => 'شيكات مستحقة خلال 7 أيام',
                    'count' => $dueChecks['count'],
                    'amount' => $dueChecks['amount'],
                    'unit' => 'شيك',
                    'tone' => 'blue',
                    'icon' => '💳',
                    'url' => $this->safeResourceUrl(BankCheckResource::class),
                ],
                [
                    'label' => 'أوامر توريد مفتوحة',
                    'count' => $this->openSupplierPurchaseOrdersCount(),
                    'amount' => null,
                    'unit' => 'أمر توريد',
                    'tone' => 'green',
                    'icon' => '🚚',
                    'url' => $this->safeResourceUrl(SupplierPurchaseOrderResource::class),
                ],
            ],

            'recent_financial' => $this->recentFinancialMovements(),
            'recent_documents' => $this->recentOperationalDocuments(),
            'chart' => $this->salesPurchasesChart(),

            // المرحلة الثانية: التحليلات.
            'analytics' => [
                'top_customers' => $this->topCustomers($salesInvoices),
                'top_suppliers' => $this->topSuppliers($purchaseInvoices),
                'top_sales_items' => $this->topSalesItems($salesInvoices),
                'top_purchase_items' => $this->topPurchaseItems($purchaseInvoices),

                'collection' => $this->ratioSummary(
                    $salesInvoiceSummary['paid'],
                    $salesInvoiceSummary['total'],
                    'نسبة تحصيل فواتير العملاء'
                ),

                'payment' => $this->ratioSummary(
                    $purchaseInvoiceSummary['paid'],
                    $purchaseInvoiceSummary['total'],
                    'نسبة سداد فواتير الموردين'
                ),

                'ratio_notes' => [
                    'collection' => 'الفاتورة الآجلة لا تعتبر محصلة إلا بقيمة سندات القبض المرتبطة بها.',
                    'payment' => 'الفاتورة الكاش تعتبر مسددة مباشرة، والآجلة تعتبر مسددة بقيمة سندات الصرف المرتبطة بها.',
                ],

                'customer_aging' => $this->agingBuckets(
                    $dueRecords->where('source_type', DueObligation::TYPE_SALE)
                ),

                'supplier_aging' => $this->agingBuckets(
                    $dueRecords->where('source_type', DueObligation::TYPE_PURCHASE)
                ),
            ],

            'management' => $this->managementAnalytics(
                $from,
                $to,
                $income,
                $dueRecords,
                $salesInvoices,
                $purchaseInvoices,
            ),
        ];
    }

    /** @return array{0: Carbon, 1: Carbon, 2: string} */
    private function resolvePeriod(
        string $period,
        ?string $fromDate = null,
        ?string $toDate = null,
    ): array {
        $today = now();

        if ($period === 'custom') {
            try {
                $from = $fromDate
                    ? Carbon::parse($fromDate)->startOfDay()
                    : $today->copy()->startOfMonth();

                $to = $toDate
                    ? Carbon::parse($toDate)->endOfDay()
                    : $today->copy()->endOfDay();

                if ($from->gt($to)) {
                    [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
                }

                return [$from, $to, 'فترة مخصصة'];
            } catch (\Throwable) {
                return [$today->copy()->startOfMonth(), $today->copy()->endOfDay(), 'هذا الشهر'];
            }
        }

        if ($period === 'today') {
            return [$today->copy()->startOfDay(), $today->copy()->endOfDay(), 'اليوم'];
        }

        if ($period === 'year') {
            $fiscalYear = FiscalYear::query()
                ->where('status', FiscalYear::STATUS_OPEN)
                ->whereDate('start_date', '<=', $today)
                ->whereDate('end_date', '>=', $today)
                ->orderByDesc('start_date')
                ->first();

            if ($fiscalYear) {
                return [
                    $fiscalYear->start_date->copy()->startOfDay(),
                    min($fiscalYear->end_date->copy()->endOfDay(), $today->copy()->endOfDay()),
                    'السنة المالية '.$fiscalYear->name,
                ];
            }

            return [$today->copy()->startOfYear(), $today->copy()->endOfDay(), 'هذه السنة'];
        }

        return [$today->copy()->startOfMonth(), $today->copy()->endOfDay(), 'هذا الشهر'];
    }

    /**
     * @param Collection<int, SalesInvoice> $invoices
     * @return array{total: float, paid: float, remaining: float}
     */
    private function salesInvoiceSummary(Collection $invoices): array
    {
        $total = 0.0;
        $paid = 0.0;

        foreach ($invoices as $invoice) {
            $invoiceTotal = (float) $invoice->totalAmount();

            $invoicePaid = $this->isCashPaymentType($invoice->payment_type)
                ? $invoiceTotal
                : (float) $invoice->receiptAllocations->sum('amount');

            $total += $invoiceTotal;
            $paid += min($invoiceTotal, max(0, $invoicePaid));
        }

        return [
            'total' => round($total, 2),
            'paid' => round($paid, 2),
            'remaining' => round(max(0, $total - $paid), 2),
        ];
    }

    /**
     * @param Collection<int, PurchaseInvoice> $invoices
     * @return array{total: float, paid: float, remaining: float}
     */
    private function purchaseInvoiceSummary(Collection $invoices): array
    {
        $total = 0.0;
        $paid = 0.0;

        foreach ($invoices as $invoice) {
            $invoiceTotal = (float) $invoice->totalAmount();

            $invoicePaid = $this->isCashPaymentType($invoice->payment_type)
                ? $invoiceTotal
                : (float) $invoice->supplierPaymentAllocations->sum('amount');

            $total += $invoiceTotal;
            $paid += min($invoiceTotal, max(0, $invoicePaid));
        }

        return [
            'total' => round($total, 2),
            'paid' => round($paid, 2),
            'remaining' => round(max(0, $total - $paid), 2),
        ];
    }

    private function isCashPaymentType(mixed $paymentType): bool
    {
        $value = $paymentType instanceof \BackedEnum
            ? $paymentType->value
            : (string) $paymentType;

        return $value === 'cash';
    }

    private function cashAndBanksBalance(): float
    {
        $treasuryService = app(TreasuryTransactionService::class);
        $bankService = app(BankTransactionService::class);

        $treasuryBalance = Treasury::query()
            ->where('is_active', true)
            ->get()
            ->sum(fn (Treasury $treasury): float => $treasuryService->getBalance($treasury));

        $bankBalance = BankAccount::query()
            ->where('is_active', true)
            ->get()
            ->sum(fn (BankAccount $account): float => $bankService->balance($account));

        return round((float) $treasuryBalance + (float) $bankBalance, 2);
    }

    private function isOverdue(DueObligation $record): bool
    {
        return $record->payment_type === 'credit'
            && $record->due_date !== null
            && $record->due_date->copy()->startOfDay()->isBefore(now()->startOfDay())
            && (float) $record->remaining_amount > 0.009;
    }

    /** @return array<int, array<string, mixed>> */
    private function lowStockItems(): array
    {
        $balances = StockBalance::query()
            ->select('item_id', DB::raw('COALESCE(SUM(quantity), 0) as total_quantity'))
            ->groupBy('item_id');

        return Item::query()
            ->where('items.active', true)
            ->where('items.is_stock_item', true)
            ->leftJoinSub(
                $balances,
                'stock_totals',
                fn ($join) => $join->on('stock_totals.item_id', '=', 'items.id')
            )
            ->whereNotNull('items.reorder_level')
            ->where('items.reorder_level', '>', 0)
            ->whereColumn(
                DB::raw('COALESCE(stock_totals.total_quantity, 0)'),
                '<=',
                'items.reorder_level'
            )
            ->orderBy('items.name')
            ->get([
                'items.id',
                'items.code',
                'items.name',
                'items.reorder_level',
                DB::raw('COALESCE(stock_totals.total_quantity, 0) as total_quantity'),
            ])
            ->map(fn (Item $item): array => [
                'id' => (int) $item->getKey(),
                'code' => (string) $item->code,
                'name' => (string) $item->name,
                'quantity' => (float) $item->total_quantity,
                'reorder_level' => (float) $item->reorder_level,
                'url' => $this->safeRecordUrl(ItemResource::class, $item),
            ])
            ->values()
            ->all();
    }

    /** @return array{count: int, amount: float} */
    private function checksDueSoon(): array
    {
        $activeStatuses = [
            BankCheck::STATUS_IN_HAND,
            BankCheck::STATUS_DEPOSITED,
            BankCheck::STATUS_PENDING_DELIVERY,
            BankCheck::STATUS_DELIVERED,
        ];

        $checks = BankCheck::query()
            ->whereIn('status', $activeStatuses)
            ->whereBetween('due_date', [today()->toDateString(), today()->copy()->addDays(7)->toDateString()])
            ->get();

        return [
            'count' => $checks->count(),
            'amount' => round((float) $checks->sum('amount'), 2),
        ];
    }

    private function openSupplierPurchaseOrdersCount(): int
    {
        return SupplierPurchaseOrder::query()
            ->with(['items.purchaseInvoiceItems'])
            ->get()
            ->filter(function (SupplierPurchaseOrder $order): bool {
                $ordered = (float) $order->items->sum('ordered_quantity');
                $invoiced = (float) $order->items->sum(
                    fn ($item): float => (float) $item->purchaseInvoiceItems->sum('quantity')
                );

                return $ordered > 0.009 && ($ordered - $invoiced) > 0.009;
            })
            ->count();
    }

    /** @return array<int, array<string, mixed>> */
    private function recentFinancialMovements(): array
    {
        $receipts = ReceiptVoucher::query()
            ->with('customer')
            ->latest('date')
            ->limit(8)
            ->get()
            ->map(fn (ReceiptVoucher $voucher): array => [
                'date_sort' => $voucher->date?->format('Y-m-d') ?? '',
                'date' => $voucher->date?->format('Y/m/d') ?? '—',
                'type' => 'سند قبض',
                'description' => $voucher->customer?->name
                    ? 'تحصيل من '.$voucher->customer->name
                    : ($voucher->receivedFromName() ?: 'سند قبض'),
                'debit' => (float) $voucher->amount,
                'credit' => 0.0,
                'url' => $this->safeRecordUrl(ReceiptVoucherResource::class, $voucher),
            ]);

        $payments = SupplierPaymentVoucher::query()
            ->with('supplier')
            ->latest('voucher_date')
            ->limit(8)
            ->get()
            ->map(fn (SupplierPaymentVoucher $voucher): array => [
                'date_sort' => $voucher->voucher_date?->format('Y-m-d') ?? '',
                'date' => $voucher->voucher_date?->format('Y/m/d') ?? '—',
                'type' => 'سند صرف',
                'description' => $voucher->supplier?->name
                    ? 'سداد إلى '.$voucher->supplier->name
                    : ($voucher->paidToName() ?: 'سند صرف'),
                'debit' => 0.0,
                'credit' => (float) $voucher->amount,
                'url' => $this->safeRecordUrl(SupplierPaymentVoucherResource::class, $voucher),
            ]);

        return $receipts
            ->concat($payments)
            ->sortByDesc('date_sort')
            ->take(5)
            ->values()
            ->all();
    }

    /** @return array<int, array<string, mixed>> */
    private function recentOperationalDocuments(): array
    {
        $sales = SalesInvoice::query()
            ->with(['customer', 'items'])
            ->latest('invoice_date')
            ->limit(5)
            ->get()
            ->map(fn (SalesInvoice $invoice): array => [
                'date_sort' => $invoice->invoice_date?->format('Y-m-d') ?? '',
                'date' => $invoice->invoice_date?->format('Y/m/d') ?? '—',
                'type' => 'فاتورة بيع',
                'number' => $invoice->document_number,
                'party' => $invoice->customer?->name ?? '—',
                'total' => $invoice->totalAmount(),
                'url' => $this->safeRecordUrl(SalesInvoiceResource::class, $invoice, 'view'),
            ]);

        $purchases = PurchaseInvoice::query()
            ->with(['supplier', 'items'])
            ->latest('invoice_date')
            ->limit(5)
            ->get()
            ->map(fn (PurchaseInvoice $invoice): array => [
                'date_sort' => $invoice->invoice_date?->format('Y-m-d') ?? '',
                'date' => $invoice->invoice_date?->format('Y/m/d') ?? '—',
                'type' => 'فاتورة شراء',
                'number' => $invoice->code,
                'party' => $invoice->supplier?->name ?? '—',
                'total' => $invoice->totalAmount(),
                'url' => $this->safeRecordUrl(PurchaseInvoiceResource::class, $invoice, 'view'),
            ]);

        $orders = SupplierPurchaseOrder::query()
            ->with('supplier')
            ->latest('order_date')
            ->limit(5)
            ->get()
            ->map(fn (SupplierPurchaseOrder $order): array => [
                'date_sort' => $order->order_date?->format('Y-m-d') ?? '',
                'date' => $order->order_date?->format('Y/m/d') ?? '—',
                'type' => 'أمر توريد',
                'number' => $order->code,
                'party' => $order->supplier?->name ?? '—',
                'total' => (float) $order->total,
                'url' => $this->safeRecordUrl(SupplierPurchaseOrderResource::class, $order),
            ]);

        return $sales
            ->concat($purchases)
            ->concat($orders)
            ->sortByDesc('date_sort')
            ->take(5)
            ->values()
            ->all();
    }

    /** @return array<int, array<string, mixed>> */
    private function salesPurchasesChart(): array
    {
        $months = collect(range(5, 0))
            ->map(fn (int $offset): Carbon => now()->copy()->subMonths($offset)->startOfMonth());

        return $months->map(function (Carbon $month): array {
            $start = $month->copy()->startOfMonth();
            $end = $month->copy()->endOfMonth();

            $sales = SalesInvoice::query()
                ->with('items')
                ->whereBetween('invoice_date', [$start->toDateString(), $end->toDateString()])
                ->get()
                ->sum(fn (SalesInvoice $invoice): float => $invoice->totalAmount());

            $purchases = PurchaseInvoice::query()
                ->with('items')
                ->whereBetween('invoice_date', [$start->toDateString(), $end->toDateString()])
                ->get()
                ->sum(fn (PurchaseInvoice $invoice): float => $invoice->totalAmount());

            $income = app(IncomeStatementService::class)->report(
                $start->toDateString(),
                $end->toDateString(),
                false,
            );

            return [
                'label' => $this->arabicMonthName((int) $month->month),
                'sales' => round((float) $sales, 2),
                'purchases' => round((float) $purchases, 2),
                'profit' => round((float) $income['totals']['net_profit'], 2),
            ];
        })->all();
    }

    /**
     * @param Collection<int, DueObligation> $dueRecords
     * @param Collection<int, SalesInvoice> $salesInvoices
     * @param Collection<int, PurchaseInvoice> $purchaseInvoices
     * @return array<string, mixed>
     */
    private function managementAnalytics(
        Carbon $from,
        Carbon $to,
        array $income,
        Collection $dueRecords,
        Collection $salesInvoices,
        Collection $purchaseInvoices,
    ): array {
        $customerReceivables = round(
            (float) $dueRecords
                ->where('source_type', DueObligation::TYPE_SALE)
                ->sum('remaining_amount'),
            2
        );

        $supplierPayables = round(
            (float) $dueRecords
                ->where('source_type', DueObligation::TYPE_PURCHASE)
                ->sum('remaining_amount'),
            2
        );

        $inventoryValue = round(
            (float) StockBalance::query()
                ->selectRaw('COALESCE(SUM(quantity * average_cost), 0) AS inventory_value')
                ->value('inventory_value'),
            2
        );

        $revenue = (float) $income['totals']['revenue'];
        $grossProfit = (float) $income['totals']['gross_profit'];
        $netProfit = (float) $income['totals']['net_profit'];

        $grossMargin = abs($revenue) > 0.009
            ? round(($grossProfit / $revenue) * 100, 1)
            : 0.0;

        $netMargin = abs($revenue) > 0.009
            ? round(($netProfit / $revenue) * 100, 1)
            : 0.0;

        $comparison = $this->periodComparison(
            $from,
            $to,
            $salesInvoices,
            $purchaseInvoices,
            $income,
        );

        return [
            'cards' => [
                [
                    'label' => 'السيولة الحالية',
                    'value' => $this->cashAndBanksBalance(),
                    'icon' => '💧',
                    'tone' => 'blue',
                    'note' => 'الخزائن + الحسابات البنكية',
                ],
                [
                    'label' => 'أرصدة العملاء',
                    'value' => $customerReceivables,
                    'icon' => '👥',
                    'tone' => 'green',
                    'note' => 'المتبقي على فواتير العملاء',
                ],
                [
                    'label' => 'أرصدة الموردين',
                    'value' => $supplierPayables,
                    'icon' => '🤝',
                    'tone' => 'orange',
                    'note' => 'المتبقي للموردين',
                ],
                [
                    'label' => 'قيمة المخزون',
                    'value' => $inventoryValue,
                    'icon' => '📦',
                    'tone' => 'violet',
                    'note' => 'الرصيد × متوسط التكلفة',
                ],
            ],
            'margins' => [
                'gross_profit' => round($grossProfit, 2),
                'gross_margin' => $grossMargin,
                'net_profit' => round($netProfit, 2),
                'net_margin' => $netMargin,
                'revenue' => round($revenue, 2),
            ],
            'comparison' => $comparison,
            'top_inventory_value' => $this->topInventoryByValue(),
        ];
    }

    /**
     * @param Collection<int, SalesInvoice> $currentSales
     * @param Collection<int, PurchaseInvoice> $currentPurchases
     * @return array<string, mixed>
     */
    private function periodComparison(
        Carbon $from,
        Carbon $to,
        Collection $currentSales,
        Collection $currentPurchases,
        array $currentIncome,
    ): array {
        $days = max(1, $from->copy()->startOfDay()->diffInDays($to->copy()->startOfDay()) + 1);

        $previousTo = $from->copy()->subDay()->endOfDay();
        $previousFrom = $previousTo->copy()->subDays($days - 1)->startOfDay();

        $previousSales = SalesInvoice::query()
            ->with('items')
            ->whereBetween('invoice_date', [
                $previousFrom->toDateString(),
                $previousTo->toDateString(),
            ])
            ->get()
            ->sum(fn (SalesInvoice $invoice): float => $invoice->totalAmount());

        $previousPurchases = PurchaseInvoice::query()
            ->with('items')
            ->whereBetween('invoice_date', [
                $previousFrom->toDateString(),
                $previousTo->toDateString(),
            ])
            ->get()
            ->sum(fn (PurchaseInvoice $invoice): float => $invoice->totalAmount());

        $previousIncome = app(IncomeStatementService::class)->report(
            $previousFrom->toDateString(),
            $previousTo->toDateString(),
            false,
        );

        $currentSalesTotal = (float) $currentSales
            ->sum(fn (SalesInvoice $invoice): float => $invoice->totalAmount());

        $currentPurchasesTotal = (float) $currentPurchases
            ->sum(fn (PurchaseInvoice $invoice): float => $invoice->totalAmount());

        $currentNet = (float) $currentIncome['totals']['net_profit'];
        $previousNet = (float) $previousIncome['totals']['net_profit'];

        return [
            'current_label' => $from->format('Y/m/d').' - '.$to->format('Y/m/d'),
            'previous_label' => $previousFrom->format('Y/m/d').' - '.$previousTo->format('Y/m/d'),
            'rows' => [
                $this->comparisonRow('المبيعات', $currentSalesTotal, (float) $previousSales),
                $this->comparisonRow('المشتريات', $currentPurchasesTotal, (float) $previousPurchases),
                $this->comparisonRow('صافي النتيجة', $currentNet, $previousNet),
            ],
        ];
    }

    /** @return array{label: string, current: float, previous: float, change: float, direction: string} */
    private function comparisonRow(string $label, float $current, float $previous): array
    {
        $change = abs($previous) > 0.009
            ? (($current - $previous) / abs($previous)) * 100
            : (abs($current) > 0.009 ? 100.0 : 0.0);

        return [
            'label' => $label,
            'current' => round($current, 2),
            'previous' => round($previous, 2),
            'change' => round($change, 1),
            'direction' => $change > 0.05 ? 'up' : ($change < -0.05 ? 'down' : 'same'),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function topInventoryByValue(): array
    {
        return StockBalance::query()
            ->join('items', 'items.id', '=', 'stock_balances.item_id')
            ->where('items.active', true)
            ->where('items.is_stock_item', true)
            ->groupBy('items.id', 'items.code', 'items.name')
            ->selectRaw(
                'items.id, items.code, items.name, '.
                'COALESCE(SUM(stock_balances.quantity), 0) AS quantity, '.
                'COALESCE(SUM(stock_balances.quantity * stock_balances.average_cost), 0) AS stock_value'
            )
            ->orderByDesc('stock_value')
            ->limit(5)
            ->get()
            ->map(fn ($row): array => [
                'id' => (int) $row->id,
                'code' => (string) $row->code,
                'name' => (string) $row->name,
                'quantity' => round((float) $row->quantity, 2),
                'value' => round((float) $row->stock_value, 2),
                'url' => $this->safeRecordUrl(
                    ItemResource::class,
                    Item::query()->find($row->id)
                ),
            ])
            ->values()
            ->all();
    }

    /**
     * @param Collection<int, SalesInvoice> $invoices
     * @return array<int, array<string, mixed>>
     */
    private function topCustomers(Collection $invoices): array
    {
        return $invoices
            ->groupBy('customer_id')
            ->map(function (Collection $rows): array {
                /** @var SalesInvoice $first */
                $first = $rows->first();

                return [
                    'id' => (int) $first->customer_id,
                    'name' => $first->customer?->name ?? '—',
                    'count' => $rows->count(),
                    'amount' => round(
                        (float) $rows->sum(fn (SalesInvoice $invoice): float => $invoice->totalAmount()),
                        2
                    ),
                    'url' => $first->customer
                        ? $this->safeRecordUrl(CustomerResource::class, $first->customer)
                        : null,
                ];
            })
            ->sortByDesc('amount')
            ->take(5)
            ->values()
            ->all();
    }

    /**
     * @param Collection<int, PurchaseInvoice> $invoices
     * @return array<int, array<string, mixed>>
     */
    private function topSuppliers(Collection $invoices): array
    {
        return $invoices
            ->groupBy('supplier_id')
            ->map(function (Collection $rows): array {
                /** @var PurchaseInvoice $first */
                $first = $rows->first();

                return [
                    'id' => (int) $first->supplier_id,
                    'name' => $first->supplier?->name ?? '—',
                    'count' => $rows->count(),
                    'amount' => round(
                        (float) $rows->sum(fn (PurchaseInvoice $invoice): float => $invoice->totalAmount()),
                        2
                    ),
                    'url' => $first->supplier
                        ? $this->safeRecordUrl(SupplierResource::class, $first->supplier)
                        : null,
                ];
            })
            ->sortByDesc('amount')
            ->take(5)
            ->values()
            ->all();
    }

    /**
     * @param Collection<int, SalesInvoice> $invoices
     * @return array<int, array<string, mixed>>
     */
    private function topSalesItems(Collection $invoices): array
    {
        return $invoices
            ->flatMap(fn (SalesInvoice $invoice): Collection => $invoice->items)
            ->groupBy('item_id')
            ->map(function (Collection $rows): array {
                $first = $rows->first();

                return [
                    'id' => (int) $first->item_id,
                    'code' => $first->item?->code ?? '—',
                    'name' => $first->item?->name ?? '—',
                    'quantity' => round((float) $rows->sum('quantity'), 2),
                    'amount' => round((float) $rows->sum('line_total'), 2),
                    'url' => $first->item
                        ? $this->safeRecordUrl(ItemResource::class, $first->item)
                        : null,
                ];
            })
            ->sortByDesc('quantity')
            ->take(5)
            ->values()
            ->all();
    }

    /**
     * @param Collection<int, PurchaseInvoice> $invoices
     * @return array<int, array<string, mixed>>
     */
    private function topPurchaseItems(Collection $invoices): array
    {
        return $invoices
            ->flatMap(fn (PurchaseInvoice $invoice): Collection => $invoice->items)
            ->groupBy('item_id')
            ->map(function (Collection $rows): array {
                $first = $rows->first();

                return [
                    'id' => (int) $first->item_id,
                    'code' => $first->item?->code ?? '—',
                    'name' => $first->item?->name ?? '—',
                    'quantity' => round((float) $rows->sum('quantity'), 2),
                    'amount' => round((float) $rows->sum('total_cost'), 2),
                    'url' => $first->item
                        ? $this->safeRecordUrl(ItemResource::class, $first->item)
                        : null,
                ];
            })
            ->sortByDesc('quantity')
            ->take(5)
            ->values()
            ->all();
    }

    /** @return array{label: string, paid: float, total: float, remaining: float, percentage: float} */
    private function ratioSummary(float $paid, float $total, string $label): array
    {
        $total = max(0, $total);
        $paid = min($total, max(0, $paid));

        return [
            'label' => $label,
            'paid' => round($paid, 2),
            'total' => round($total, 2),
            'remaining' => round(max(0, $total - $paid), 2),
            'percentage' => $total > 0
                ? round(min(100, ($paid / $total) * 100), 1)
                : 0.0,
        ];
    }

    /**
     * @param Collection<int, DueObligation> $records
     * @return array<string, array{label: string, count: int, amount: float}>
     */
    private function agingBuckets(Collection $records): array
    {
        $today = today();

        $buckets = [
            'not_due' => ['label' => 'غير مستحق', 'count' => 0, 'amount' => 0.0],
            '1_30' => ['label' => '1 - 30 يوم', 'count' => 0, 'amount' => 0.0],
            '31_60' => ['label' => '31 - 60 يوم', 'count' => 0, 'amount' => 0.0],
            'over_60' => ['label' => 'أكثر من 60 يوم', 'count' => 0, 'amount' => 0.0],
        ];

        foreach ($records as $record) {
            $remaining = (float) ($record->remaining_amount ?? 0);

            if ($remaining <= 0.009) {
                continue;
            }

            if (! $record->due_date || $record->due_date->copy()->startOfDay()->gte($today)) {
                $key = 'not_due';
            } else {
                $days = $record->due_date->copy()->startOfDay()->diffInDays($today);

                $key = match (true) {
                    $days <= 30 => '1_30',
                    $days <= 60 => '31_60',
                    default => 'over_60',
                };
            }

            $buckets[$key]['count']++;
            $buckets[$key]['amount'] += $remaining;
        }

        foreach ($buckets as &$bucket) {
            $bucket['amount'] = round((float) $bucket['amount'], 2);
        }
        unset($bucket);

        return $buckets;
    }

    private function arabicMonthName(int $month): string
    {
        return [
            1 => 'يناير', 2 => 'فبراير', 3 => 'مارس', 4 => 'أبريل',
            5 => 'مايو', 6 => 'يونيو', 7 => 'يوليو', 8 => 'أغسطس',
            9 => 'سبتمبر', 10 => 'أكتوبر', 11 => 'نوفمبر', 12 => 'ديسمبر',
        ][$month] ?? (string) $month;
    }

    private function safeResourceUrl(string $resource): ?string
    {
        try {
            return $resource::canViewAny() ? $resource::getUrl('index') : null;
        } catch (\Throwable) {
            return null;
        }
    }

    private function safeRecordUrl(string $resource, mixed $record, string $preferredPage = 'edit'): ?string
    {
        try {
            if ($preferredPage === 'view' && $resource::canView($record)) {
                return $resource::getUrl('view', ['record' => $record]);
            }

            if ($resource::canEdit($record)) {
                return $resource::getUrl('edit', ['record' => $record]);
            }

            if ($resource::canView($record)) {
                return $resource::getUrl('view', ['record' => $record]);
            }
        } catch (\Throwable) {
            return null;
        }

        return null;
    }
}
