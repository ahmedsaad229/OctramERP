<?php

namespace App\Services;

use App\Filament\Resources\CustomerPurchaseOrders\CustomerPurchaseOrderResource;
use App\Models\CustomerPurchaseOrder;
use App\Models\CustomerPurchaseOrderExecution;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

class CustomerPurchaseOrderMonitoringService
{
    /** @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    public function report(array $filters = [], ?string $view = null): array
    {
        $filters = $this->normalize($filters);
        $base = $this->query($filters, $view);
        $today = today()->toDateString();
        $week = today()->addDays(7)->toDateString();

        $summaryBase = $this->query($filters);
        $summary = [
            'total' => (clone $summaryBase)->count(),
            'new' => (clone $summaryBase)->where('status', CustomerPurchaseOrder::STATUS_NEW)->count(),
            'inProgress' => (clone $summaryBase)->where('status', CustomerPurchaseOrder::STATUS_IN_PROGRESS)->count(),
            'partial' => (clone $summaryBase)->where('status', CustomerPurchaseOrder::STATUS_PARTIAL)->count(),
            'completed' => (clone $summaryBase)->where('status', CustomerPurchaseOrder::STATUS_COMPLETED)->count(),
            'delayed' => (clone $summaryBase)->whereDate('required_delivery_date', '<', $today)
                ->whereNotIn('status', [CustomerPurchaseOrder::STATUS_COMPLETED, CustomerPurchaseOrder::STATUS_CANCELLED])->count(),
            'dueSoon' => (clone $summaryBase)->whereBetween('required_delivery_date', [$today, $week])
                ->whereNotIn('status', [CustomerPurchaseOrder::STATUS_COMPLETED, CustomerPurchaseOrder::STATUS_CANCELLED])->count(),
            'suspended' => (clone $summaryBase)->where('status', CustomerPurchaseOrder::STATUS_SUSPENDED)->count(),
            'cancelled' => (clone $summaryBase)->where('status', CustomerPurchaseOrder::STATUS_CANCELLED)->count(),
        ];

        $orders = $base
            ->with('customer:id,name')
            ->withCount(['items', 'attachments'])
            ->withCount(['items as completed_items_count' => fn (Builder $query) => $query->where('remaining_quantity', '<=', 0)])
            ->withCount(['items as remaining_items_count' => fn (Builder $query) => $query->where('remaining_quantity', '>', 0)])
            ->withSum('items as remaining_quantity_sum', 'remaining_quantity')
            ->addSelect(['sales_invoices_count' => CustomerPurchaseOrderExecution::query()
                ->selectRaw('COUNT(DISTINCT source_id)')
                ->whereColumn('customer_purchase_order_id', 'customer_purchase_orders.id')])
            ->orderByDesc('order_date')->orderByDesc('id')->get();

        return ['summary' => $summary, 'rows' => $orders->map(fn (CustomerPurchaseOrder $order): array => $this->row($order))];
    }

    /** @return array<string, mixed> */
    public function customerSummary(int $customerId): array
    {
        $report = $this->report(['customer_id' => $customerId]);
        $rows = $report['rows'];

        return [
            'total' => $rows->count(),
            'open' => $rows->whereNotIn('statusLabel', ['منفذ بالكامل', 'ملغي'])->count(),
            'delayed' => $rows->where('delayed', true)->count(),
            'completed' => $rows->where('statusLabel', 'منفذ بالكامل')->count(),
            'rows' => $rows,
        ];
    }

    /** @param array<string, mixed> $filters */
    public function query(array $filters = [], ?string $view = null): Builder
    {
        $today = today()->toDateString();
        $week = today()->addDays(7)->toDateString();

        return CustomerPurchaseOrder::query()
            ->when($filters['customer_id'] ?? null, fn (Builder $q, $id) => $q->where('customer_id', $id))
            ->when($filters['status'] ?? null, fn (Builder $q, $status) => $q->where('status', $status))
            ->when($filters['order_from'] ?? null, fn (Builder $q, $date) => $q->whereDate('order_date', '>=', $date))
            ->when($filters['order_to'] ?? null, fn (Builder $q, $date) => $q->whereDate('order_date', '<=', $date))
            ->when($filters['delivery_from'] ?? null, fn (Builder $q, $date) => $q->whereDate('required_delivery_date', '>=', $date))
            ->when($filters['delivery_to'] ?? null, fn (Builder $q, $date) => $q->whereDate('required_delivery_date', '<=', $date))
            ->when($filters['project'] ?? null, fn (Builder $q, $project) => $q->where('project_name', 'like', "%{$project}%"))
            ->when($filters['remaining_only'] ?? false, fn (Builder $q) => $q->whereHas('items', fn (Builder $i) => $i->where('remaining_quantity', '>', 0)))
            ->when($filters['attachments_only'] ?? false, fn (Builder $q) => $q->has('attachments'))
            ->when(($filters['delayed_only'] ?? false) || $view === 'delayed', fn (Builder $q) => $q
                ->whereDate('required_delivery_date', '<', $today)
                ->whereNotIn('status', [CustomerPurchaseOrder::STATUS_COMPLETED, CustomerPurchaseOrder::STATUS_CANCELLED]))
            ->when(($filters['due_soon'] ?? false) || $view === 'due_soon', fn (Builder $q) => $q
                ->whereBetween('required_delivery_date', [$today, $week])
                ->whereNotIn('status', [CustomerPurchaseOrder::STATUS_COMPLETED, CustomerPurchaseOrder::STATUS_CANCELLED]))
            ->when($view === 'partial', fn (Builder $q) => $q->where('status', CustomerPurchaseOrder::STATUS_PARTIAL))
            ->when($view === 'new', fn (Builder $q) => $q->where('status', CustomerPurchaseOrder::STATUS_NEW))
            ->when($view === 'completed', fn (Builder $q) => $q->where('status', CustomerPurchaseOrder::STATUS_COMPLETED))
            ->when($view === 'suspended', fn (Builder $q) => $q->where('status', CustomerPurchaseOrder::STATUS_SUSPENDED));
    }

    /** @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    private function normalize(array $filters): array
    {
        foreach ([['order_from', 'order_to'], ['delivery_from', 'delivery_to']] as [$from, $to]) {
            if (filled($filters[$from] ?? null) && filled($filters[$to] ?? null)
                && CarbonImmutable::parse($filters[$to])->lt(CarbonImmutable::parse($filters[$from]))) {
                throw ValidationException::withMessages(["data.{$to}" => 'يجب ألا يسبق تاريخ النهاية تاريخ البداية.']);
            }
        }

        return $filters;
    }

    /** @return array<string, mixed> */
    private function row(CustomerPurchaseOrder $order): array
    {
        $days = $order->required_delivery_date ? today()->diffInDays($order->required_delivery_date, false) : null;

        return [
            'id' => $order->getKey(), 'documentNumber' => $order->document_number,
            'customerOrderNumber' => $order->customer_order_number ?: '—', 'customer' => $order->customer->name,
            'project' => $order->project_name ?: '—', 'orderDate' => $order->order_date->format('d/m/Y'),
            'deliveryDate' => $order->required_delivery_date?->format('d/m/Y') ?: '—',
            'statusLabel' => CustomerPurchaseOrder::statusOptions()[$order->status] ?? '—',
            'percentage' => (float) $order->execution_percentage, 'itemsCount' => $order->items_count,
            'completedItems' => $order->completed_items_count, 'remainingItems' => $order->remaining_items_count,
            'remainingQuantity' => (float) ($order->remaining_quantity_sum ?? 0),
            'invoiceCount' => $order->sales_invoices_count, 'attachmentCount' => $order->attachments_count,
            'delayed' => $order->isDelayed(), 'days' => $days,
            'url' => CustomerPurchaseOrderResource::getUrl('edit', ['record' => $order]),
        ];
    }
}
