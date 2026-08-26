<?php

namespace App\Filament\Pages;

use App\Enums\PaymentType;
use App\Models\Customer;
use App\Models\Warehouse;
use App\Services\Reports\SalesReportService;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use UnitEnum;

class SalesReports extends Page
{
    protected static string|BackedEnum|null $navigationIcon =
        Heroicon::OutlinedChartBarSquare;

    protected static ?string $navigationLabel = 'تقارير المبيعات';

    protected static ?string $title = 'مركز تقارير المبيعات';

    protected static string|UnitEnum|null $navigationGroup = 'المبيعات';

    protected static ?int $navigationSort = 90;

    protected string $view = 'filament.pages.sales-reports';

    public ?string $date_from = null;

    public ?string $date_until = null;

    public ?int $customer_id = null;

    public ?int $warehouse_id = null;

    public ?string $payment_type = null;

    public ?string $document_number = null;

    public bool $reportLoaded = true;

    public function mount(): void
    {
        $this->date_from = now()->startOfMonth()->toDateString();
        $this->date_until = now()->toDateString();
    }

    /**
     * @return array<string, mixed>
     */
    public function filters(): array
    {
        return [
            'date_from' => $this->date_from,
            'date_until' => $this->date_until,
            'customer_id' => $this->customer_id,
            'warehouse_id' => $this->warehouse_id,
            'payment_type' => $this->payment_type,
            'document_number' => $this->document_number,
        ];
    }

    public function showReport(): void
    {
        $this->validate([
            'date_from' => ['nullable', 'date'],
            'date_until' => ['nullable', 'date', 'after_or_equal:date_from'],
            'customer_id' => ['nullable', 'integer'],
            'warehouse_id' => ['nullable', 'integer'],
            'payment_type' => ['nullable', 'string'],
            'document_number' => ['nullable', 'string', 'max:100'],
        ]);

        $this->reportLoaded = true;
    }

    public function resetFilters(): void
    {
        $this->reset([
            'customer_id',
            'warehouse_id',
            'payment_type',
            'document_number',
        ]);

        $this->date_from = now()->startOfMonth()->toDateString();
        $this->date_until = now()->toDateString();
        $this->reportLoaded = true;
    }

    /**
     * @return Collection<int, \App\Models\SalesInvoice>
     */
    public function getRecordsProperty(): Collection
    {
        if (! $this->reportLoaded) {
            return collect();
        }

        return app(SalesReportService::class)->records($this->filters());
    }

    /**
     * @return array<string, int|float>
     */
    public function getTotalsProperty(): array
    {
        return app(SalesReportService::class)->totals($this->records);
    }

    /**
     * @return array<int|string, string>
     */
    public function getCustomerOptionsProperty(): array
    {
        return Customer::query()
            ->where('active', true)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    /**
     * @return array<int|string, string>
     */
    public function getWarehouseOptionsProperty(): array
    {
        return Warehouse::query()
            ->where('active', true)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    /**
     * @return array<string, string>
     */
    public function getPaymentTypeOptionsProperty(): array
    {
        return PaymentType::options();
    }

    public function printUrl(): string
    {
        return route(
            'sales-reports.print',
            array_filter(
                $this->filters(),
                fn (mixed $value): bool => filled($value)
            )
        );
    }

    public function excelUrl(): string
    {
        return route(
            'sales-reports.excel',
            array_filter(
                $this->filters(),
                fn (mixed $value): bool => filled($value)
            )
        );
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->hasPermission('sales_reports.view') === true;
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasPermission('sales_reports.view') === true;
    }
}
