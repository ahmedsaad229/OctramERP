<?php

namespace App\Filament\Pages;

use App\Models\PurchaseRequest;
use App\Models\Warehouse;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class PurchaseRequestMonitoring extends Page
{
    protected static string|BackedEnum|null $navigationIcon =
        Heroicon::OutlinedChartBarSquare;

    protected static ?string $navigationLabel =
        'متابعة طلبات الشراء';

    protected static ?string $title =
        'متابعة تنفيذ طلبات الشراء';

    protected static string|\UnitEnum|null $navigationGroup =
        'المشتريات';

    protected static ?int $navigationSort = 30;

    /*
     * مهمة جدًا في Filament 4:
     * الخاصية غير static.
     */
    protected string $view =
        'filament.pages.purchase-request-monitoring';

    public ?string $status = null;

    public ?string $warehouseId = null;

    public ?string $dateFrom = null;

    public ?string $dateUntil = null;

    public function resetFilters(): void
    {
        $this->status = null;
        $this->warehouseId = null;
        $this->dateFrom = null;
        $this->dateUntil = null;
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $rows = $this->reportRows();

        return [
            'rows' => $rows,

            'warehouses' => Warehouse::query()
                ->orderBy('name')
                ->pluck('name', 'id')
                ->all(),

            'summary' => [
                'requests_count' => $rows->count(),

                'not_ordered_count' => $rows
                    ->where(
                        'status',
                        PurchaseRequest::STATUS_NOT_ORDERED
                    )
                    ->count(),

                'partially_ordered_count' => $rows
                    ->where(
                        'status',
                        PurchaseRequest::STATUS_PARTIALLY_ORDERED
                    )
                    ->count(),

                'fully_ordered_count' => $rows
                    ->where(
                        'status',
                        PurchaseRequest::STATUS_FULLY_ORDERED
                    )
                    ->count(),

                'remaining_items_count' => $rows
                    ->sum('remaining_items_count'),
            ],
        ];
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function reportRows(): Collection
    {
        $requests = PurchaseRequest::query()
            ->with([
                'warehouse:id,name',
                'requestedBy:id,name',
                'items.item:id,code,name',
                'items.unit:id,name',
                'items.purchaseOrderItems.supplierPurchaseOrder.supplier:id,name',
            ])
            ->when(
                filled($this->warehouseId),
                fn (Builder $query): Builder => $query->where(
                    'warehouse_id',
                    (int) $this->warehouseId
                )
            )
            ->when(
                filled($this->dateFrom),
                fn (Builder $query): Builder => $query->whereDate(
                    'request_date',
                    '>=',
                    $this->dateFrom
                )
            )
            ->when(
                filled($this->dateUntil),
                fn (Builder $query): Builder => $query->whereDate(
                    'request_date',
                    '<=',
                    $this->dateUntil
                )
            )
            ->orderByDesc('request_date')
            ->orderByDesc('id')
            ->get();

        $rows = $requests->map(
            function (PurchaseRequest $request): array {
                $items = $request->items
                    ->map(function ($requestItem): array {
                        $requested = round(
                            (float) $requestItem->requested_quantity,
                            2
                        );

                        $ordered = round(
                            (float) $requestItem
                                ->purchaseOrderItems
                                ->sum('ordered_quantity'),
                            2
                        );

                        $remaining = round(
                            max(0, $requested - $ordered),
                            2
                        );

                        $status = match (true) {
                            $ordered <= 0 =>
                                PurchaseRequest::STATUS_NOT_ORDERED,

                            $remaining > 0 =>
                                PurchaseRequest::STATUS_PARTIALLY_ORDERED,

                            default =>
                                PurchaseRequest::STATUS_FULLY_ORDERED,
                        };

                        $orderNumbers = $requestItem
                            ->purchaseOrderItems
                            ->map(
                                fn ($orderItem): ?string =>
                                    $orderItem
                                        ->supplierPurchaseOrder
                                        ?->code
                            )
                            ->filter()
                            ->unique()
                            ->values()
                            ->implode('، ');

                        $suppliers = $requestItem
                            ->purchaseOrderItems
                            ->map(
                                fn ($orderItem): ?string =>
                                    $orderItem
                                        ->supplierPurchaseOrder
                                        ?->supplier
                                        ?->name
                            )
                            ->filter()
                            ->unique()
                            ->values()
                            ->implode('، ');

                        return [
                            'item_code' =>
                                $requestItem->item?->code ?? '—',

                            'item_name' =>
                                $requestItem->item?->name ?? '—',

                            'unit_name' =>
                                $requestItem->unit?->name ?? '—',

                            'requested_quantity' => $requested,
                            'ordered_quantity' => $ordered,
                            'remaining_quantity' => $remaining,

                            'purchase_orders' =>
                                filled($orderNumbers)
                                    ? $orderNumbers
                                    : '—',

                            'suppliers' =>
                                filled($suppliers)
                                    ? $suppliers
                                    : '—',

                            'status' => $status,

                            'status_label' =>
                                $this->statusLabel($status),
                        ];
                    })
                    ->values();

                $hasAnyOrderedItem = $items->contains(
                    fn (array $item): bool =>
                        $item['ordered_quantity'] > 0
                );

                $allItemsCompleted = $items->isNotEmpty()
                    && $items->every(
                        fn (array $item): bool =>
                            $item['remaining_quantity'] <= 0
                    );

                $status = match (true) {
                    $allItemsCompleted =>
                        PurchaseRequest::STATUS_FULLY_ORDERED,

                    $hasAnyOrderedItem =>
                        PurchaseRequest::STATUS_PARTIALLY_ORDERED,

                    default =>
                        PurchaseRequest::STATUS_NOT_ORDERED,
                };

                return [
                    'code' => $request->code,

                    'request_date' =>
                        $request->request_date?->format('d/m/Y')
                        ?? '—',

                    'required_date' =>
                        $request->required_date?->format('d/m/Y')
                        ?? '—',

                    'warehouse' =>
                        $request->warehouse?->name ?? '—',

                    'requested_by' =>
                        $request->requestedBy?->name ?? '—',

                    'department' =>
                        $request->department ?: '—',

                    'purpose' =>
                        $request->purpose ?: '—',

                    'status' => $status,

                    'status_label' =>
                        $this->statusLabel($status),

                    'items_count' => $items->count(),

                    'not_ordered_items_count' => $items
                        ->where(
                            'status',
                            PurchaseRequest::STATUS_NOT_ORDERED
                        )
                        ->count(),

                    'partially_ordered_items_count' => $items
                        ->where(
                            'status',
                            PurchaseRequest::STATUS_PARTIALLY_ORDERED
                        )
                        ->count(),

                    'fully_ordered_items_count' => $items
                        ->where(
                            'status',
                            PurchaseRequest::STATUS_FULLY_ORDERED
                        )
                        ->count(),

                    'remaining_items_count' => $items
                        ->filter(
                            fn (array $item): bool =>
                                $item['remaining_quantity'] > 0
                        )
                        ->count(),

                    'items' => $items,
                ];
            }
        );

        if (filled($this->status)) {
            $rows = $rows
                ->where('status', $this->status)
                ->values();
        }

        return $rows;
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            PurchaseRequest::STATUS_PARTIALLY_ORDERED =>
                'تم إصدار أوامر جزئيًا',

            PurchaseRequest::STATUS_FULLY_ORDERED =>
                'تم إصدار أوامر بالكامل',

            default =>
                'لم يتم إصدار أمر توريد',
        };
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->hasPermission('purchase_request_monitoring.view') === true;
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasPermission('purchase_request_monitoring.view') === true;
    }
}
