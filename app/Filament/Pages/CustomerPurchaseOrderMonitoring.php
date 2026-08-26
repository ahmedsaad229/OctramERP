<?php

namespace App\Filament\Pages;

use App\Models\Customer;
use App\Models\CustomerPurchaseOrder;
use App\Services\CustomerPurchaseOrderMonitoringService;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CustomerPurchaseOrderMonitoring extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationLabel = 'متابعة أوامر التوريد';

    protected static ?string $title = 'متابعة أوامر توريد العملاء';

    protected static string|\UnitEnum|null $navigationGroup = 'المبيعات';

    protected static ?int $navigationSort = 35;

    protected string $view = 'filament.pages.customer-purchase-order-monitoring';

    public array $data = [];

    public ?array $report = null;

    public string $activeView = 'all';

    public function mount(): void
    {
        $this->form->fill();
        $this->runReport();
    }

    public function form(Schema $schema): Schema
    {
        return $schema->statePath('data')->components([
            Section::make('عوامل المتابعة')->compact()->schema([
                Grid::make(['default' => 1, 'md' => 2, 'xl' => 5])->schema([
                    Select::make('customer_id')->label('العميل')->options(Customer::query()->where('active', true)->pluck('name', 'id'))->searchable(),
                    Select::make('status')->label('الحالة')->options(CustomerPurchaseOrder::statusOptions()),
                    DatePicker::make('order_from')->label('من تاريخ الأمر')->native(false),
                    DatePicker::make('order_to')->label('إلى تاريخ الأمر')->native(false)->afterOrEqual('order_from'),
                    DatePicker::make('delivery_from')->label('من تاريخ التسليم')->native(false),
                    DatePicker::make('delivery_to')->label('إلى تاريخ التسليم')->native(false)->afterOrEqual('delivery_from'),
                    TextInput::make('project')->label('المشروع'),
                    Toggle::make('delayed_only')->label('متأخر فقط'),
                    Toggle::make('remaining_only')->label('به كمية متبقية'),
                    Toggle::make('attachments_only')->label('به مرفقات'),
                    Toggle::make('due_soon')->label('التسليم خلال 7 أيام'),
                ]),
            ]),
        ]);
    }

    public function runReport(): void
    {
        $this->report = app(CustomerPurchaseOrderMonitoringService::class)->report($this->form->getState(), $this->activeView);
    }

    public function setView(string $view): void
    {
        $this->activeView = $view;
        $this->runReport();
    }

    public function resetReport(): void
    {
        $this->form->fill();
        $this->activeView = 'all';
        $this->runReport();
    }

    public function excelUrl(): string
    {
        return route('customer-purchase-order-monitoring.excel', array_filter([
            ...$this->data,
            'view' => $this->activeView,
        ], fn ($value): bool => filled($value)));
    }

    public function printUrl(): string
    {
        return route('customer-purchase-order-monitoring.print', array_filter([
            ...$this->data,
            'view' => $this->activeView,
        ], fn ($value): bool => filled($value)));
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->hasPermission('customer_purchase_order_monitoring.view') === true;
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasPermission('customer_purchase_order_monitoring.view') === true;
    }
}
