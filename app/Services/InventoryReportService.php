<?php

namespace App\Services;

use App\Models\StockBalance;
use App\Models\StockTransaction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class InventoryReportService
{
    public const STATUS_OUT = 'out';

    public const STATUS_LOW = 'low';

    public const STATUS_NORMAL = 'normal';

    /** @return array<string, string> */
    public function statusOptions(): array
    {
        return [
            self::STATUS_OUT => 'نفد',
            self::STATUS_LOW => 'منخفض',
            self::STATUS_NORMAL => 'طبيعي',
        ];
    }

    /** @return array<string, mixed> */
    public function balances(array $filters = []): array
    {
        $query = StockBalance::query()
            ->when($filters['warehouse_id'] ?? null, fn (Builder $query, $id) => $query->where('warehouse_id', $id))
            ->when($filters['item_id'] ?? null, fn (Builder $query, $id) => $query->where('item_id', $id))
            ->when($filters['category_id'] ?? null, fn (Builder $query, $id) => $query->whereHas(
                'item',
                fn (Builder $query) => $query->where('category_id', $id),
            ))
            ->when($filters['has_balance'] ?? false, fn (Builder $query) => $query->where('quantity', '!=', 0));

        $totals = (clone $query)
            ->selectRaw('COALESCE(SUM(quantity), 0) AS total_quantity')
            ->selectRaw('COALESCE(SUM(quantity * average_cost), 0) AS total_value')
            ->first();

        $rows = $query
            ->select(['id', 'warehouse_id', 'item_id', 'quantity', 'average_cost'])
            ->with([
                'warehouse:id,name',
                'item:id,code,name,category_id,unit_id',
                'item.category:id,name',
                'item.unit:id,name',
            ])
            ->orderBy('warehouse_id')
            ->orderBy('item_id')
            ->get()
            ->map(fn (StockBalance $balance): array => [
                'item_code' => $balance->item?->code ?? '—',
                'item_name' => $balance->item?->name ?? '—',
                'category' => $balance->item?->category?->name ?? '—',
                'warehouse' => $balance->warehouse?->name ?? '—',
                'unit' => $balance->item?->unit?->name ?? '—',
                'quantity' => (float) $balance->quantity,
                'average_cost' => (float) $balance->average_cost,
                'inventory_value' => (float) $balance->quantity * (float) $balance->average_cost,
            ]);

        return [
            'rows' => $rows,
            'total_quantity' => (float) $totals->total_quantity,
            'total_value' => (float) $totals->total_value,
        ];
    }

    /** @return array<string, mixed> */
    public function movements(array $filters = []): array
    {
        $toDate = $filters['to_date'] ?? null;
        $fromDate = $filters['from_date'] ?? null;
        $visibleType = $filters['transaction_type'] ?? null;
        $reference = trim((string) ($filters['reference_no'] ?? ''));

        $transactions = StockTransaction::query()
            ->select([
                'id', 'warehouse_id', 'item_id', 'transaction_type', 'reference_no',
                'quantity', 'unit_cost', 'transaction_date', 'created_at',
            ])
            ->with(['item:id,code,name', 'warehouse:id,name'])
            ->when($filters['warehouse_id'] ?? null, fn (Builder $query, $id) => $query->where('warehouse_id', $id))
            ->when($filters['item_id'] ?? null, fn (Builder $query, $id) => $query->where('item_id', $id))
            ->when($toDate, fn (Builder $query, $date) => $query->whereDate('transaction_date', '<=', $date))
            ->orderBy('transaction_date')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        $states = [];
        $rows = new Collection;

        foreach ($transactions as $transaction) {
            $key = "{$transaction->warehouse_id}:{$transaction->item_id}";
            $state = $states[$key] ?? 0.0;
            $quantity = (float) $transaction->quantity;

            $inbound = 0.0;
            $outbound = 0.0;

            if (in_array($transaction->transaction_type, [
                StockTransaction::TYPE_OPENING,
                StockTransaction::TYPE_PURCHASE,
                StockTransaction::TYPE_TRANSFER_IN,
            ], true)) {
                $inbound = $quantity;
                $state += $quantity;
            } elseif (in_array($transaction->transaction_type, [
                StockTransaction::TYPE_SALE,
                StockTransaction::TYPE_TRANSFER_OUT,
            ], true)) {
                $outbound = $quantity;
                $state -= $quantity;
            } else {
                $difference = $quantity - $state;
                $inbound = max(0, $difference);
                $outbound = max(0, -$difference);
                $state = $quantity;
            }

            $states[$key] = $state;

            if ($fromDate && $transaction->transaction_date->toDateString() < $fromDate) {
                continue;
            }
            if ($visibleType && $transaction->transaction_type !== $visibleType) {
                continue;
            }
            if ($reference !== '' && ! str_contains(mb_strtolower((string) $transaction->reference_no), mb_strtolower($reference))) {
                continue;
            }

            $rows->push([
                'date' => $transaction->transaction_date->format('d/m/Y'),
                'sort_date' => $transaction->transaction_date->toDateString(),
                'id' => $transaction->getKey(),
                'type' => $transaction->transaction_type,
                'type_label' => StockTransaction::types()[$transaction->transaction_type] ?? $transaction->transaction_type,
                'reference' => $transaction->reference_no ?: '—',
                'item' => $transaction->item?->name ?? '—',
                'item_code' => $transaction->item?->code ?? '—',
                'warehouse' => $transaction->warehouse?->name ?? '—',
                'inbound' => $inbound,
                'outbound' => $outbound,
                'running_balance' => $state,
                'unit_cost' => (float) $transaction->unit_cost,
            ]);
        }

        return [
            'rows' => $rows->sortByDesc(fn (array $row): string => "{$row['sort_date']}-".str_pad((string) $row['id'], 20, '0', STR_PAD_LEFT))->values(),
        ];
    }

    /** @return array<string, mixed> */
    public function lowStock(array $filters = []): array
    {
        $rows = StockBalance::query()
            ->select(['id', 'warehouse_id', 'item_id', 'quantity'])
            ->with([
                'warehouse:id,name',
                'item:id,code,name,category_id,reorder_level',
                'item.category:id,name',
            ])
            ->whereHas('item', fn (Builder $query) => $query->whereNotNull('reorder_level'))
            ->when($filters['warehouse_id'] ?? null, fn (Builder $query, $id) => $query->where('warehouse_id', $id))
            ->when($filters['category_id'] ?? null, fn (Builder $query, $id) => $query->whereHas(
                'item',
                fn (Builder $query) => $query->where('category_id', $id),
            ))
            ->orderBy('quantity')
            ->get()
            ->map(function (StockBalance $balance): array {
                $quantity = (float) $balance->quantity;
                $level = (float) $balance->item->reorder_level;
                $status = match (true) {
                    $quantity == 0.0 => self::STATUS_OUT,
                    $quantity > 0 && $quantity <= $level => self::STATUS_LOW,
                    default => self::STATUS_NORMAL,
                };

                return [
                    'item_code' => $balance->item->code,
                    'item_name' => $balance->item->name,
                    'category' => $balance->item->category?->name ?? '—',
                    'warehouse' => $balance->warehouse?->name ?? '—',
                    'quantity' => $quantity,
                    'reorder_level' => $level,
                    'difference' => $quantity - $level,
                    'status' => $status,
                    'status_label' => $this->statusOptions()[$status],
                ];
            })
            ->when(
                $filters['status'] ?? null,
                fn (Collection $rows, string $status): Collection => $rows->where('status', $status),
            )
            ->values();

        return ['rows' => $rows];
    }
}
