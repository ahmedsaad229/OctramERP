<?php

namespace App\Services;

use App\Filament\Resources\PurchaseInvoices\PurchaseInvoiceResource;
use App\Filament\Resources\SupplierPaymentVouchers\SupplierPaymentVoucherResource;
use App\Models\PartyTransaction;
use App\Models\PurchaseInvoice;
use App\Models\Supplier;
use App\Models\SupplierPaymentVoucher;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class SupplierStatementService
{
    /** @return array<string, string> */
    public function transactionTypeOptions(): array
    {
        return [
            PartyTransaction::TYPE_PURCHASE_INVOICE => 'فاتورة شراء',
            PartyTransaction::TYPE_SUPPLIER_PAYMENT => 'سند صرف مورد',
        ];
    }

    /** @return array<string, mixed> */
    public function report(
        int $supplierId,
        ?string $fromDate = null,
        ?string $toDate = null,
        ?string $transactionType = null,
    ): array {
        $supplier = Supplier::query()->findOrFail($supplierId);
        $from = filled($fromDate) ? CarbonImmutable::parse($fromDate)->startOfDay() : null;
        $to = filled($toDate) ? CarbonImmutable::parse($toDate)->startOfDay() : null;

        $openingBalance = $from
            ? (float) $this->supplierTransactions($supplier)
                ->whereDate('transaction_date', '<', $from->toDateString())
                ->selectRaw('COALESCE(SUM(credit - debit), 0) AS balance')
                ->value('balance')
            : 0.0;

        $periodQuery = $this->supplierTransactions($supplier)
            ->when($from, fn (Builder $query) => $query->whereDate('transaction_date', '>=', $from->toDateString()))
            ->when($to, fn (Builder $query) => $query->whereDate('transaction_date', '<=', $to->toDateString()));

        $closingMovement = (float) (clone $periodQuery)
            ->selectRaw('COALESCE(SUM(credit - debit), 0) AS movement')
            ->value('movement');

        $runningBalance = $openingBalance;
        $rows = (clone $periodQuery)
            ->with('source')
            ->orderBy('transaction_date')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get()
            ->map(function (PartyTransaction $transaction) use (&$runningBalance): array {
                $purchases = (float) $transaction->credit;
                $paid = (float) $transaction->debit;
                $runningBalance += $purchases - $paid;

                return [
                    'id' => $transaction->getKey(),
                    'date' => $transaction->transaction_date->format('d/m/Y'),
                    'type' => $transaction->transaction_type,
                    'typeLabel' => $this->transactionTypeLabel($transaction->transaction_type),
                    'reference' => $transaction->reference_no ?: '—',
                    'description' => $this->description($transaction),
                    'purchases' => $purchases,
                    'paid' => $paid,
                    'runningBalance' => $runningBalance,
                    'url' => $this->sourceUrl($transaction),
                ];
            })
            ->when(
                filled($transactionType),
                fn (Collection $rows) => $rows->where('type', $transactionType)->values(),
            );

        $aggregates = (clone $periodQuery)
            ->when(
                filled($transactionType),
                fn (Builder $query) => $query->where('transaction_type', $transactionType),
            )
            ->selectRaw('COALESCE(SUM(credit), 0) AS total_purchases')
            ->selectRaw('COALESCE(SUM(debit), 0) AS total_paid')
            ->selectRaw('COUNT(*) AS transaction_count')
            ->first();

        $closingBalance = $openingBalance + $closingMovement;
        [$statusLabel, $statusColor] = $this->balanceStatus($closingBalance);

        return [
            'supplier' => $supplier,
            'fromDate' => $from,
            'toDate' => $to,
            'openingBalance' => $openingBalance,
            'totalPurchases' => (float) $aggregates->total_purchases,
            'totalPaid' => (float) $aggregates->total_paid,
            'closingBalance' => $closingBalance,
            'transactionCount' => (int) $aggregates->transaction_count,
            'rows' => $rows,
            'statusLabel' => $statusLabel,
            'statusColor' => $statusColor,
        ];
    }

    public function transactionTypeLabel(string $type): string
    {
        return $this->transactionTypeOptions()[$type] ?? 'حركة مورد';
    }

    public function money(float $amount): string
    {
        return number_format($amount, 2).' ج.م';
    }

    /** @return array{string, string} */
    private function balanceStatus(float $balance): array
    {
        if ($balance > 0.00001) {
            return ['مستحق للمورد', 'danger'];
        }

        if ($balance < -0.00001) {
            return ['رصيد دائن للشركة', 'info'];
        }

        return ['الحساب مسدد', 'success'];
    }

    private function supplierTransactions(Supplier $supplier): Builder
    {
        return PartyTransaction::query()
            ->where('party_type', $supplier->getMorphClass())
            ->where('party_id', $supplier->getKey())
            ->whereIn('transaction_type', array_keys($this->transactionTypeOptions()));
    }

    private function description(PartyTransaction $transaction): string
    {
        if (filled($transaction->notes)) {
            return $transaction->notes;
        }

        return match ($transaction->transaction_type) {
            PartyTransaction::TYPE_PURCHASE_INVOICE => 'فاتورة شراء من المورد',
            PartyTransaction::TYPE_SUPPLIER_PAYMENT => 'سداد للمورد',
            default => 'حركة حساب مورد',
        };
    }

    private function sourceUrl(PartyTransaction $transaction): ?string
    {
        $source = $transaction->source;

        return match (true) {
            $source instanceof PurchaseInvoice => PurchaseInvoiceResource::canView($source)
                ? PurchaseInvoiceResource::getUrl('view', ['record' => $source])
                : null,
            $source instanceof SupplierPaymentVoucher => SupplierPaymentVoucherResource::canEdit($source)
                ? SupplierPaymentVoucherResource::getUrl('edit', ['record' => $source])
                : null,
            default => null,
        };
    }
}
