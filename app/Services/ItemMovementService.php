<?php

namespace App\Services;

use App\Filament\Resources\GoodsIssueVouchers\GoodsIssueVoucherResource;
use App\Filament\Resources\GoodsReceiptVouchers\GoodsReceiptVoucherResource;
use App\Filament\Resources\OpeningStockVouchers\OpeningStockVoucherResource;
use App\Filament\Resources\PurchaseInvoices\PurchaseInvoiceResource;
use App\Filament\Resources\SalesInvoices\SalesInvoiceResource;
use App\Models\GoodsIssueVoucher;
use App\Models\GoodsReceiptVoucher;
use App\Models\Item;
use App\Models\OpeningStockVoucher;
use App\Models\PurchaseInvoice;
use App\Models\SalesInvoice;
use App\Models\StockTransaction;
use App\Models\Warehouse;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

class ItemMovementService
{
    /** @return array<string, string> */
    public function transactionTypeOptions(): array
    {
        return [
            StockTransaction::TYPE_OPENING => 'رصيد أول المدة',
            StockTransaction::TYPE_PURCHASE => 'حركة واردة',
            StockTransaction::TYPE_SALE => 'حركة منصرفة',
            StockTransaction::TYPE_TRANSFER_IN => 'تحويل وارد',
            StockTransaction::TYPE_TRANSFER_OUT => 'تحويل صادر',
            StockTransaction::TYPE_ADJUSTMENT => 'تسوية مخزنية',
        ];
    }

    /** @return array<string, mixed> */
    public function report(int $itemId, ?int $warehouseId = null, ?string $fromDate = null, ?string $toDate = null, ?string $type = null): array
    {
        $item = Item::query()->with('unit')->findOrFail($itemId);
        $warehouse = $warehouseId ? Warehouse::query()->findOrFail($warehouseId) : null;
        $from = filled($fromDate) ? CarbonImmutable::parse($fromDate) : null;
        $to = filled($toDate) ? CarbonImmutable::parse($toDate) : null;

        if ($from && $to && $to->lt($from)) {
            throw ValidationException::withMessages(['data.to_date' => 'يجب ألا يسبق تاريخ النهاية تاريخ البداية.']);
        }
        if (filled($type) && ! array_key_exists($type, $this->transactionTypeOptions())) {
            throw ValidationException::withMessages(['data.transaction_type' => 'نوع الحركة غير صحيح.']);
        }

        $transactions = StockTransaction::query()
            ->where('item_id', $itemId)
            ->when($warehouseId, fn (Builder $query) => $query->where('warehouse_id', $warehouseId))
            ->when($to, fn (Builder $query) => $query->whereDate('transaction_date', '<=', $to->toDateString()))
            ->with('warehouse')
            ->orderBy('transaction_date')->orderBy('created_at')->orderBy('id')->get();

        $states = [];
        $sourceUrls = $this->sourceUrls($transactions->pluck('reference_no')->filter()->unique()->all());
        $openingQuantity = $openingValue = 0.0;
        $openingCaptured = ! $from;
        $allRows = collect();

        foreach ($transactions as $transaction) {
            if (! $openingCaptured && ! $transaction->transaction_date->lt($from)) {
                [$openingQuantity, $openingValue] = $this->totals($states);
                $openingCaptured = true;
            }

            $movement = $this->apply($states, $transaction);
            if (! $from || $transaction->transaction_date->gte($from)) {
                [$runningQuantity, $runningValue] = $this->totals($states);
                $allRows->push([
                    'id' => $transaction->getKey(),
                    'date' => $transaction->transaction_date->format('d/m/Y'),
                    'type' => $transaction->transaction_type,
                    'typeLabel' => $this->documentTypeLabel($transaction),
                    'reference' => $transaction->reference_no ?: '—',
                    'warehouse' => $transaction->warehouse?->name ?: '—',
                    'description' => $transaction->notes ?: $this->documentTypeLabel($transaction),
                    'inbound' => $movement['inbound'],
                    'outbound' => $movement['outbound'],
                    'runningQuantity' => $runningQuantity,
                    'unitCost' => (float) $transaction->unit_cost,
                    'movementValue' => $movement['value'],
                    'runningValue' => $runningValue,
                    'runningAverage' => $runningQuantity != 0.0 ? $runningValue / $runningQuantity : 0.0,
                    'url' => $sourceUrls[$transaction->reference_no] ?? null,
                ]);
            }
        }

        if (! $openingCaptured) {
            [$openingQuantity, $openingValue] = $this->totals($states);
        }

        [$closingQuantity, $closingValue] = $this->totals($states);
        $rows = filled($type) ? $allRows->where('type', $type)->values() : $allRows;

        return [
            'item' => $item,
            'warehouse' => $warehouse,
            'fromDate' => $from,
            'toDate' => $to,
            'openingQuantity' => $openingQuantity,
            'openingValue' => $openingValue,
            'totalInbound' => (float) $rows->sum('inbound'),
            'totalOutbound' => (float) $rows->sum('outbound'),
            'closingQuantity' => $closingQuantity,
            'closingValue' => $closingValue,
            'closingAverage' => $closingQuantity != 0.0 ? $closingValue / $closingQuantity : 0.0,
            'transactionCount' => $rows->count(),
            'rows' => $rows,
        ];
    }

    /** @param array<int, array{quantity: float, average: float}> $states */
    private function apply(array &$states, StockTransaction $transaction): array
    {
        $key = (int) $transaction->warehouse_id;
        $state = $states[$key] ?? ['quantity' => 0.0, 'average' => 0.0];
        $quantity = (float) $transaction->quantity;
        $cost = (float) $transaction->unit_cost;
        $inbound = $outbound = $value = 0.0;

        if (in_array($transaction->transaction_type, [StockTransaction::TYPE_OPENING, StockTransaction::TYPE_PURCHASE, StockTransaction::TYPE_TRANSFER_IN], true)) {
            $inbound = $quantity;
            $value = $quantity * $cost;
            $newQuantity = $state['quantity'] + $quantity;
            if ($newQuantity > 0) {
                $state['average'] = (($state['quantity'] * $state['average']) + $value) / $newQuantity;
            }
            $state['quantity'] = $newQuantity;
        } elseif (in_array($transaction->transaction_type, [StockTransaction::TYPE_SALE, StockTransaction::TYPE_TRANSFER_OUT], true)) {
            $outbound = $quantity;
            $value = $quantity * $state['average'];
            $state['quantity'] -= $quantity;
        } else {
            $difference = $quantity - $state['quantity'];
            $inbound = max(0, $difference);
            $outbound = max(0, -$difference);
            if ($cost > 0) {
                $state['average'] = $cost;
            }
            $value = abs($difference) * $state['average'];
            $state['quantity'] = $quantity;
        }

        $states[$key] = $state;

        return compact('inbound', 'outbound', 'value');
    }

    /** @param array<int, array{quantity: float, average: float}> $states */
    private function totals(array $states): array
    {
        $quantity = array_sum(array_column($states, 'quantity'));
        $value = array_sum(array_map(fn (array $state): float => $state['quantity'] * $state['average'], $states));

        return [$quantity, $value];
    }

    private function documentTypeLabel(StockTransaction $transaction): string
    {
        $reference = (string) $transaction->reference_no;

        return match (true) {
            str_starts_with($reference, 'OSV-') => 'رصيد أول المدة',
            str_starts_with($reference, 'GRV-') => 'إذن استلام',
            str_starts_with($reference, 'GIV-') => 'إذن صرف',
            str_starts_with($reference, 'PUR-') => 'فاتورة شراء',
            str_starts_with($reference, 'SAL-') => 'فاتورة بيع',
            default => $this->transactionTypeOptions()[$transaction->transaction_type] ?? 'حركة مخزنية',
        };
    }

    /** @param array<int, string> $references
     * @return array<string, string>
     */
    private function sourceUrls(array $references): array
    {
        $sources = [
            [OpeningStockVoucher::class, OpeningStockVoucherResource::class],
            [GoodsReceiptVoucher::class, GoodsReceiptVoucherResource::class],
            [GoodsIssueVoucher::class, GoodsIssueVoucherResource::class],
            [PurchaseInvoice::class, PurchaseInvoiceResource::class],
            [SalesInvoice::class, SalesInvoiceResource::class],
        ];
        $urls = [];

        foreach ($sources as [$model, $resource]) {
            $column = in_array($model, [
                OpeningStockVoucher::class,
                GoodsReceiptVoucher::class,
                GoodsIssueVoucher::class,
                PurchaseInvoice::class,
            ], true) ? 'code' : 'document_number';
            foreach ($model::query()->whereIn($column, $references)->get() as $record) {
                if ($resource::canEdit($record)) {
                    $urls[$record->{$column}] = $resource::getUrl('edit', ['record' => $record]);
                }
            }
        }

        return $urls;
    }
}
