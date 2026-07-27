<?php

namespace App\Services;

use App\Filament\Resources\ReceiptVouchers\ReceiptVoucherResource;
use App\Filament\Resources\SalesInvoices\SalesInvoiceResource;
use App\Models\Customer;
use App\Models\PartyTransaction;
use App\Models\ReceiptVoucher;
use App\Models\SalesInvoice;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class CustomerStatementService
{
    /**
     * @return array<string, string>
     */
    public function transactionTypeOptions(): array
    {
        return [
            PartyTransaction::TYPE_CUSTOMER_DEBIT => 'فاتورة بيع',
            PartyTransaction::TYPE_CUSTOMER_CREDIT => 'سند قبض',
        ];
    }

    /**
     * @return array{
     *     customer: Customer,
     *     fromDate: ?CarbonImmutable,
     *     toDate: ?CarbonImmutable,
     *     openingBalance: float,
     *     totalDebt: float,
     *     totalPaid: float,
     *     closingBalance: float,
     *     transactionCount: int,
     *     rows: Collection<int, array<string, mixed>>,
     *     statusLabel: string,
     *     statusColor: string
     * }
     */
    public function report(
        int $customerId,
        ?string $fromDate = null,
        ?string $toDate = null,
        ?string $transactionType = null,
    ): array {
        $customer = Customer::query()->findOrFail($customerId);
        $from = filled($fromDate) ? CarbonImmutable::parse($fromDate)->startOfDay() : null;
        $to = filled($toDate) ? CarbonImmutable::parse($toDate)->startOfDay() : null;

        $openingBalance = $from
            ? (float) $this->customerTransactions($customer)
                ->whereDate('transaction_date', '<', $from->toDateString())
                ->selectRaw('COALESCE(SUM(debit - credit), 0) AS balance')
                ->value('balance')
            : 0.0;

        $periodQuery = $this->customerTransactions($customer)
            ->when($from, fn (Builder $query) => $query->whereDate('transaction_date', '>=', $from->toDateString()))
            ->when($to, fn (Builder $query) => $query->whereDate('transaction_date', '<=', $to->toDateString()));

        $closingMovement = (float) (clone $periodQuery)
            ->selectRaw('COALESCE(SUM(debit - credit), 0) AS movement')
            ->value('movement');

        $allRows = (clone $periodQuery)
            ->with('source')
            ->orderBy('transaction_date')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        $runningBalance = $openingBalance;
        $rows = $allRows->map(function (PartyTransaction $transaction) use (&$runningBalance): array {
            $debit = (float) $transaction->debit;
            $credit = (float) $transaction->credit;
            $runningBalance += $debit - $credit;

            return [
                'id' => $transaction->getKey(),
                'date' => $transaction->transaction_date->format('d/m/Y'),
                'type' => $transaction->transaction_type,
                'typeLabel' => $this->transactionTypeLabel($transaction->transaction_type),
                'reference' => $transaction->reference_no ?: '—',
                'description' => $this->description($transaction),
                'debit' => $debit,
                'credit' => $credit,
                'runningBalance' => $runningBalance,
                'url' => $this->sourceUrl($transaction),
            ];
        })->when(
            filled($transactionType),
            fn (Collection $rows) => $rows->where('type', $transactionType)->values(),
        );

        $filteredPeriodQuery = (clone $periodQuery)
            ->when(
                filled($transactionType),
                fn (Builder $query) => $query->where('transaction_type', $transactionType),
            );

        $aggregates = $filteredPeriodQuery
            ->selectRaw('COALESCE(SUM(debit), 0) AS total_debt')
            ->selectRaw('COALESCE(SUM(credit), 0) AS total_paid')
            ->selectRaw('COUNT(*) AS transaction_count')
            ->first();

        $closingBalance = $openingBalance + $closingMovement;
        [$statusLabel, $statusColor] = $this->balanceStatus($closingBalance);

        return [
            'customer' => $customer,
            'fromDate' => $from,
            'toDate' => $to,
            'openingBalance' => $openingBalance,
            'totalDebt' => (float) $aggregates->total_debt,
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
        return $this->transactionTypeOptions()[$type] ?? 'حركة عميل';
    }

    public function money(float $amount): string
    {
        return number_format($amount, 2).' ج.م';
    }

    /**
     * @return array{string, string}
     */
    private function balanceStatus(float $balance): array
    {
        if ($balance > 0.00001) {
            return ['على العميل', 'danger'];
        }

        if ($balance < -0.00001) {
            return ['رصيد دائن', 'info'];
        }

        return ['مسدد', 'success'];
    }

    private function customerTransactions(Customer $customer): Builder
    {
        return PartyTransaction::query()
            ->where('party_type', $customer->getMorphClass())
            ->where('party_id', $customer->getKey())
            ->whereIn('transaction_type', array_keys($this->transactionTypeOptions()));
    }

    private function description(PartyTransaction $transaction): string
    {
        if (filled($transaction->notes)) {
            return $transaction->notes;
        }

        return match ($transaction->transaction_type) {
            PartyTransaction::TYPE_CUSTOMER_DEBIT => 'فاتورة بيع للعميل',
            PartyTransaction::TYPE_CUSTOMER_CREDIT => 'سداد من العميل',
            default => 'حركة حساب عميل',
        };
    }

    private function sourceUrl(PartyTransaction $transaction): ?string
    {
        $source = $transaction->source;

        return match (true) {
            $source instanceof SalesInvoice => SalesInvoiceResource::canView($source)
                ? SalesInvoiceResource::getUrl('view', ['record' => $source])
                : null,
            $source instanceof ReceiptVoucher => ReceiptVoucherResource::canEdit($source)
                ? ReceiptVoucherResource::getUrl('edit', ['record' => $source])
                : null,
            default => null,
        };
    }
}
