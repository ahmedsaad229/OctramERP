<?php

namespace App\Services;

use App\Filament\Resources\CustomerPurchaseOrders\CustomerPurchaseOrderResource;
use App\Filament\Resources\GoodsIssueVouchers\GoodsIssueVoucherResource;
use App\Filament\Resources\GoodsReceiptVouchers\GoodsReceiptVoucherResource;
use App\Filament\Resources\OpeningStockVouchers\OpeningStockVoucherResource;
use App\Filament\Resources\PurchaseInvoices\PurchaseInvoiceResource;
use App\Filament\Resources\PurchaseRequests\PurchaseRequestResource;
use App\Filament\Resources\SalesInvoices\SalesInvoiceResource;
use App\Filament\Resources\SalesQuotations\SalesQuotationResource;
use App\Filament\Resources\SupplierPurchaseOrders\SupplierPurchaseOrderResource;
use App\Models\CustomerPurchaseOrder;
use App\Models\CustomerPurchaseOrderItem;
use App\Models\GoodsIssueItem;
use App\Models\GoodsReceiptItem;
use App\Models\OpeningStockItem;
use App\Models\PurchaseInvoiceItem;
use App\Models\PurchaseRequestItem;
use App\Models\SalesInvoiceItem;
use App\Models\SalesQuotation;
use App\Models\SalesQuotationItem;
use App\Models\SupplierPurchaseOrderItem;
use Illuminate\Support\Collection;

class ItemTraceService
{
    public const ALL = 'all';
    public const PURCHASE_REQUESTS = 'purchase_requests';
    public const SUPPLIER_PURCHASE_ORDERS = 'supplier_purchase_orders';
    public const PURCHASE_INVOICES = 'purchase_invoices';
    public const SALES_QUOTATIONS = 'sales_quotations';
    public const CUSTOMER_PURCHASE_ORDERS = 'customer_purchase_orders';
    public const SALES_INVOICES = 'sales_invoices';
    public const GOODS_RECEIPTS = 'goods_receipts';
    public const GOODS_ISSUES = 'goods_issues';
    public const OPENING_STOCK = 'opening_stock';

    /** @return array<string, string> */
    public function documentTypeOptions(): array
    {
        return [
            self::ALL => 'الكل',
            self::PURCHASE_REQUESTS => 'طلبات الشراء',
            self::SUPPLIER_PURCHASE_ORDERS => 'أوامر توريد الموردين',
            self::PURCHASE_INVOICES => 'فواتير الشراء',
            self::SALES_QUOTATIONS => 'عروض الأسعار',
            self::CUSTOMER_PURCHASE_ORDERS => 'أوامر توريد العملاء',
            self::SALES_INVOICES => 'فواتير البيع',
            self::GOODS_RECEIPTS => 'أذون الإضافة',
            self::GOODS_ISSUES => 'أذون الصرف',
            self::OPENING_STOCK => 'أرصدة أول المدة',
        ];
    }

    /** @return array{rows: Collection<int, array<string, mixed>>, count: int, total_quantity: float} */
    public function search(int $itemId, string $documentType = self::ALL, ?string $fromDate = null, ?string $toDate = null): array
    {
        $rows = collect();

        $append = function (string $type, callable $loader) use (&$rows, $documentType): void {
            if ($documentType === self::ALL || $documentType === $type) {
                $rows = $rows->concat($loader());
            }
        };

        $append(self::PURCHASE_REQUESTS, fn () => $this->purchaseRequests($itemId, $fromDate, $toDate));
        $append(self::SUPPLIER_PURCHASE_ORDERS, fn () => $this->supplierPurchaseOrders($itemId, $fromDate, $toDate));
        $append(self::PURCHASE_INVOICES, fn () => $this->purchaseInvoices($itemId, $fromDate, $toDate));
        $append(self::SALES_QUOTATIONS, fn () => $this->salesQuotations($itemId, $fromDate, $toDate));
        $append(self::CUSTOMER_PURCHASE_ORDERS, fn () => $this->customerPurchaseOrders($itemId, $fromDate, $toDate));
        $append(self::SALES_INVOICES, fn () => $this->salesInvoices($itemId, $fromDate, $toDate));
        $append(self::GOODS_RECEIPTS, fn () => $this->goodsReceipts($itemId, $fromDate, $toDate));
        $append(self::GOODS_ISSUES, fn () => $this->goodsIssues($itemId, $fromDate, $toDate));
        $append(self::OPENING_STOCK, fn () => $this->openingStock($itemId, $fromDate, $toDate));

        $rows = $rows
            ->sortByDesc(fn (array $row): string => ($row['date_sort'] ?? '').sprintf('%012d', (int) ($row['id'] ?? 0)))
            ->values();

        return [
            'rows' => $rows,
            'count' => $rows->count(),
            'total_quantity' => (float) $rows->sum('quantity'),
        ];
    }

    private function purchaseRequests(int $itemId, ?string $fromDate, ?string $toDate): Collection
    {
        return PurchaseRequestItem::query()
            ->where('item_id', $itemId)
            ->whereHas('purchaseRequest', fn ($q) => $this->dateScope($q, 'request_date', $fromDate, $toDate))
            ->with(['purchaseRequest.warehouse'])
            ->get()
            ->map(function (PurchaseRequestItem $line): array {
                $doc = $line->purchaseRequest;
                return $this->row(
                    self::PURCHASE_REQUESTS, 'طلب شراء', $doc?->id,
                    $doc?->request_date?->format('Y-m-d'), $doc?->code ?? (string) $doc?->id,
                    $doc?->warehouse?->name ?? '—', (float) $line->requested_quantity,
                    null, null, $doc?->procurementStatusLabel() ?? '—',
                    $this->url(PurchaseRequestResource::class, $doc?->id)
                );
            });
    }

    private function supplierPurchaseOrders(int $itemId, ?string $fromDate, ?string $toDate): Collection
    {
        return SupplierPurchaseOrderItem::query()
            ->where('item_id', $itemId)
            ->whereHas('supplierPurchaseOrder', fn ($q) => $this->dateScope($q, 'order_date', $fromDate, $toDate))
            ->with(['supplierPurchaseOrder.supplier'])
            ->get()
            ->map(function (SupplierPurchaseOrderItem $line): array {
                $doc = $line->supplierPurchaseOrder;
                return $this->row(
                    self::SUPPLIER_PURCHASE_ORDERS, 'أمر توريد مورد', $doc?->id,
                    $doc?->order_date?->format('Y-m-d'), $doc?->code ?? (string) $doc?->id,
                    $doc?->supplier?->name ?? '—', (float) $line->ordered_quantity,
                    (float) $line->unit_price, (float) $line->line_total,
                    $doc?->invoiceConversionStatusLabel() ?? '—',
                    $this->url(SupplierPurchaseOrderResource::class, $doc?->id)
                );
            });
    }

    private function purchaseInvoices(int $itemId, ?string $fromDate, ?string $toDate): Collection
    {
        return PurchaseInvoiceItem::query()
            ->where('item_id', $itemId)
            ->whereHas('invoice', fn ($q) => $this->dateScope($q, 'invoice_date', $fromDate, $toDate))
            ->with(['invoice.supplier'])
            ->get()
            ->map(function (PurchaseInvoiceItem $line): array {
                $doc = $line->invoice;
                return $this->row(
                    self::PURCHASE_INVOICES, 'فاتورة شراء', $doc?->id,
                    $doc?->invoice_date?->format('Y-m-d'), $doc?->code ?? $doc?->invoice_number ?? (string) $doc?->id,
                    $doc?->supplier?->name ?? '—', (float) $line->quantity,
                    (float) $line->unit_cost, (float) $line->total_cost,
                    ($doc?->posted ?? false) ? 'مرحلة' : 'غير مرحلة',
                    $this->url(PurchaseInvoiceResource::class, $doc?->id, true)
                );
            });
    }

    private function salesQuotations(int $itemId, ?string $fromDate, ?string $toDate): Collection
    {
        return SalesQuotationItem::query()
            ->where('item_id', $itemId)
            ->whereHas('quotation', fn ($q) => $this->dateScope($q, 'quotation_date', $fromDate, $toDate))
            ->with(['quotation.customer'])
            ->get()
            ->map(function (SalesQuotationItem $line): array {
                $doc = $line->quotation;
                $status = match ($doc?->conversionStatus()) {
                    SalesQuotation::STATUS_NOT_CONVERTED => 'غير محول',
                    SalesQuotation::STATUS_PARTIALLY_CONVERTED => 'محول جزئيًا',
                    SalesQuotation::STATUS_FULLY_CONVERTED => 'محول بالكامل',
                    default => '—',
                };
                return $this->row(
                    self::SALES_QUOTATIONS, 'عرض سعر', $doc?->id,
                    $doc?->quotation_date?->format('Y-m-d'), $doc?->quotation_number ?? (string) $doc?->id,
                    $doc?->customer?->name ?? '—', (float) $line->quantity,
                    (float) $line->unit_price, (float) $line->line_total,
                    $status, $this->url(SalesQuotationResource::class, $doc?->id, true)
                );
            });
    }

    private function customerPurchaseOrders(int $itemId, ?string $fromDate, ?string $toDate): Collection
    {
        return CustomerPurchaseOrderItem::query()
            ->where('item_id', $itemId)
            ->whereHas('order', fn ($q) => $this->dateScope($q, 'order_date', $fromDate, $toDate))
            ->with(['order.customer'])
            ->get()
            ->map(function (CustomerPurchaseOrderItem $line): array {
                $doc = $line->order;
                return $this->row(
                    self::CUSTOMER_PURCHASE_ORDERS, 'أمر توريد عميل', $doc?->id,
                    $doc?->order_date?->format('Y-m-d'), $doc?->document_number ?? $doc?->customer_order_number ?? (string) $doc?->id,
                    $doc?->customer?->name ?? '—', (float) $line->ordered_quantity,
                    (float) $line->unit_price, (float) $line->line_total,
                    CustomerPurchaseOrder::statusOptions()[$doc?->status] ?? ($doc?->status ?? '—'),
                    $this->url(CustomerPurchaseOrderResource::class, $doc?->id)
                );
            });
    }

    private function salesInvoices(int $itemId, ?string $fromDate, ?string $toDate): Collection
    {
        return SalesInvoiceItem::query()
            ->where('item_id', $itemId)
            ->whereHas('invoice', fn ($q) => $this->dateScope($q, 'invoice_date', $fromDate, $toDate))
            ->with(['invoice.customer'])
            ->get()
            ->map(function (SalesInvoiceItem $line): array {
                $doc = $line->invoice;
                return $this->row(
                    self::SALES_INVOICES, 'فاتورة بيع', $doc?->id,
                    $doc?->invoice_date?->format('Y-m-d'), $doc?->document_number ?? (string) $doc?->id,
                    $doc?->customer?->name ?? '—', (float) $line->quantity,
                    (float) $line->unit_price, (float) $line->line_total,
                    '—', $this->url(SalesInvoiceResource::class, $doc?->id, true)
                );
            });
    }

    private function goodsReceipts(int $itemId, ?string $fromDate, ?string $toDate): Collection
    {
        return GoodsReceiptItem::query()
            ->where('item_id', $itemId)
            ->whereHas('voucher', fn ($q) => $this->dateScope($q, 'voucher_date', $fromDate, $toDate))
            ->with(['voucher.warehouse'])
            ->get()
            ->map(function (GoodsReceiptItem $line): array {
                $doc = $line->voucher;
                return $this->row(
                    self::GOODS_RECEIPTS, 'إذن إضافة', $doc?->id,
                    $doc?->voucher_date?->format('Y-m-d'), $doc?->code ?? (string) $doc?->id,
                    $doc?->warehouse?->name ?? '—', (float) $line->quantity,
                    (float) $line->unit_cost, (float) $line->total_cost,
                    '—', $this->url(GoodsReceiptVoucherResource::class, $doc?->id)
                );
            });
    }

    private function goodsIssues(int $itemId, ?string $fromDate, ?string $toDate): Collection
    {
        return GoodsIssueItem::query()
            ->where('item_id', $itemId)
            ->whereHas('voucher', fn ($q) => $this->dateScope($q, 'voucher_date', $fromDate, $toDate))
            ->with(['voucher.warehouse'])
            ->get()
            ->map(function (GoodsIssueItem $line): array {
                $doc = $line->voucher;
                return $this->row(
                    self::GOODS_ISSUES, 'إذن صرف', $doc?->id,
                    $doc?->voucher_date?->format('Y-m-d'), $doc?->code ?? (string) $doc?->id,
                    $doc?->warehouse?->name ?? '—', (float) $line->quantity,
                    (float) $line->unit_cost, (float) $line->total_cost,
                    '—', $this->url(GoodsIssueVoucherResource::class, $doc?->id)
                );
            });
    }

    private function openingStock(int $itemId, ?string $fromDate, ?string $toDate): Collection
    {
        return OpeningStockItem::query()
            ->where('item_id', $itemId)
            ->whereHas('voucher', fn ($q) => $this->dateScope($q, 'voucher_date', $fromDate, $toDate))
            ->with(['voucher.warehouse'])
            ->get()
            ->map(function (OpeningStockItem $line): array {
                $doc = $line->voucher;
                return $this->row(
                    self::OPENING_STOCK, 'رصيد أول مدة', $doc?->id,
                    $doc?->voucher_date?->format('Y-m-d'), $doc?->code ?? (string) $doc?->id,
                    $doc?->warehouse?->name ?? '—', (float) $line->quantity,
                    (float) $line->unit_cost, (float) $line->total_cost,
                    '—', $this->url(OpeningStockVoucherResource::class, $doc?->id)
                );
            });
    }

    private function dateScope($query, string $column, ?string $fromDate, ?string $toDate): void
    {
        $query
            ->when(filled($fromDate), fn ($q) => $q->whereDate($column, '>=', $fromDate))
            ->when(filled($toDate), fn ($q) => $q->whereDate($column, '<=', $toDate));
    }

    /** @return array<string, mixed> */
    private function row(string $type, string $typeLabel, ?int $id, ?string $date, string $number, string $party, float $quantity, ?float $price, ?float $total, string $status, ?string $url): array
    {
        return [
            'type' => $type,
            'type_label' => $typeLabel,
            'id' => $id,
            'date' => filled($date) ? date('d/m/Y', strtotime($date)) : '—',
            'date_sort' => $date ?? '0000-00-00',
            'number' => $number,
            'party' => $party,
            'quantity' => $quantity,
            'unit_price' => $price,
            'line_total' => $total,
            'status' => $status,
            'url' => $url,
        ];
    }

    private function url(string $resourceClass, ?int $recordId, bool $preferView = false): ?string
    {
        if (! $recordId) {
            return null;
        }

        try {
            if ($preferView) {
                return $resourceClass::getUrl('view', ['record' => $recordId]);
            }

            return $resourceClass::getUrl('edit', ['record' => $recordId]);
        } catch (\Throwable) {
            try {
                return $resourceClass::getUrl('index');
            } catch (\Throwable) {
                return null;
            }
        }
    }
}
