<?php

namespace App\Filament\Resources\DueObligations\Widgets;

use App\Filament\Resources\PurchaseInvoices\PurchaseInvoiceResource;
use App\Filament\Resources\SalesInvoices\SalesInvoiceResource;
use App\Models\DueObligation;
use App\Services\DueObligationService;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;

class DueObligationStats extends Widget
{
    protected string $view = 'filament.resources.due-obligations.widgets.stats';

    protected int|string|array $columnSpan = 'full';

    /**
     * @var array<string, mixed>|null
     */
    private ?array $dashboardData = null;

    /**
     * @return array<string, mixed>
     */
    public function getDashboardData(): array
    {
        if ($this->dashboardData !== null) {
            return $this->dashboardData;
        }

        /** @var DueObligationService $service */
        $service = app(DueObligationService::class);
        $records = $service->records();

        $customers = $records
            ->where('source_type', DueObligation::TYPE_SALE)
            ->values();

        $suppliers = $records
            ->where('source_type', DueObligation::TYPE_PURCHASE)
            ->values();

        $customerTotals = $this->totals($customers);
        $supplierTotals = $this->totals($suppliers);

        $overdueCustomers = $this->overdueRecords($customers);
        $overdueSuppliers = $this->overdueRecords($suppliers);

        $customerOverdueAmount = round((float) $overdueCustomers->sum('remaining_amount'), 2);
        $supplierOverdueAmount = round((float) $overdueSuppliers->sum('remaining_amount'), 2);
        $overdueAmount = round($customerOverdueAmount + $supplierOverdueAmount, 2);

        $collectionRate = $customerTotals['total'] > 0
            ? round(($customerTotals['paid'] / $customerTotals['total']) * 100, 2)
            : 0.0;

        return $this->dashboardData = [
            'customers' => $customerTotals,
            'suppliers' => $supplierTotals,
            'overdue' => [
                'amount' => $overdueAmount,
                'count' => $overdueCustomers->count() + $overdueSuppliers->count(),
                'customers_amount' => $customerOverdueAmount,
                'customers_count' => $overdueCustomers->count(),
                'suppliers_amount' => $supplierOverdueAmount,
                'suppliers_count' => $overdueSuppliers->count(),
                'customer_records' => $this->formatOverdueRecords($overdueCustomers),
                'supplier_records' => $this->formatOverdueRecords($overdueSuppliers),
            ],
            'position' => [
                'customer_remaining' => $customerTotals['remaining'],
                'supplier_remaining' => $supplierTotals['remaining'],
                'net' => round($customerTotals['remaining'] - $supplierTotals['remaining'], 2),
                'collection_rate' => $collectionRate,
            ],
        ];
    }

    /**
     * @param Collection<int, DueObligation> $records
     * @return array{total: float, paid: float, remaining: float, count: int}
     */
    private function totals(Collection $records): array
    {
        return [
            'total' => round((float) $records->sum('total_amount'), 2),
            'paid' => round((float) $records->sum('paid_amount'), 2),
            'remaining' => round((float) $records->sum('remaining_amount'), 2),
            'count' => $records->count(),
        ];
    }

    /**
     * @param Collection<int, DueObligation> $records
     * @return Collection<int, DueObligation>
     */
    private function overdueRecords(Collection $records): Collection
    {
        return $records
            ->filter(fn (DueObligation $record): bool =>
                $record->payment_type === 'credit'
                && $record->due_date !== null
                && $record->due_date->copy()->startOfDay()->isBefore(now()->startOfDay())
                && (float) $record->remaining_amount > 0.009
            )
            ->sortBy('due_date')
            ->values();
    }

    /**
     * @param Collection<int, DueObligation> $records
     * @return array<int, array<string, mixed>>
     */
    private function formatOverdueRecords(Collection $records): array
    {
        return $records
            ->map(function (DueObligation $record): array {
                $url = $record->source_type === DueObligation::TYPE_SALE
                    ? SalesInvoiceResource::getUrl('view', ['record' => $record->source_id])
                    : PurchaseInvoiceResource::getUrl('view', ['record' => $record->source_id]);

                return [
                    'document_number' => $record->document_number,
                    'party_name' => $record->party_name,
                    'invoice_date' => $record->invoice_date?->format('Y/m/d') ?? '—',
                    'due_date' => $record->due_date?->format('Y/m/d') ?? '—',
                    'remaining_amount' => round((float) $record->remaining_amount, 2),
                    'url' => $url,
                ];
            })
            ->all();
    }
}
