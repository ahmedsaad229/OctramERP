<?php

namespace App\Services;

use App\Filament\Resources\SalesInvoices\SalesInvoiceResource;
use App\Models\CustomerPurchaseOrder;
use App\Models\CustomerPurchaseOrderExecution;
use App\Models\CustomerPurchaseOrderItem;
use App\Models\SalesInvoice;
use App\Models\SalesQuotation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class CustomerPurchaseOrderService
{
    public function create(array $data): CustomerPurchaseOrder
    {
        return DB::transaction(function () use ($data): CustomerPurchaseOrder {
            $items = $this->normalizeItems($data['items'] ?? []);
            $followUps = $data['followUps'] ?? [];
            $attachments = $data['attachments'] ?? [];
            unset($data['items'], $data['followUps'], $data['attachments'], $data['document_number'], $data['execution_percentage']);
            $this->validateHeader($data);
            $data['document_number'] = app(DocumentNumberService::class)->generate(DocumentNumberService::CUSTOMER_PURCHASE_ORDER);
            $data['status'] = in_array($data['status'] ?? null, [CustomerPurchaseOrder::STATUS_SUSPENDED, CustomerPurchaseOrder::STATUS_CANCELLED], true)
                ? $data['status'] : CustomerPurchaseOrder::STATUS_NEW;
            $data['execution_percentage'] = 0;
            $order = CustomerPurchaseOrder::create($data);
            $order->items()->createMany($items);
            $order->followUps()->createMany(array_map(fn (array $row): array => [...$row, 'created_by' => auth()->id()], $followUps));
            $this->persistAttachments($order, $attachments);

            return $order->fresh(['customer', 'items.item', 'items.unit', 'followUps']);
        });
    }

    public function update(CustomerPurchaseOrder $order, array $data): CustomerPurchaseOrder
    {
        return DB::transaction(function () use ($order, $data): CustomerPurchaseOrder {
            $order = CustomerPurchaseOrder::query()->lockForUpdate()->findOrFail($order->getKey());
            $items = $this->normalizeItems($data['items'] ?? [], $order);
            $followUps = $data['followUps'] ?? [];
            $attachments = $data['attachments'] ?? [];
            unset($data['items'], $data['followUps'], $data['attachments'], $data['document_number'], $data['execution_percentage']);
            $this->validateHeader($data);
            $order->update($data);

            if ($order->executions()->exists()) {
                $existingIds = $order->items()->pluck('id')->sort()->values()->all();
                $submittedIds = collect($items)->pluck('id')->filter()->sort()->values()->all();
                if ($existingIds !== $submittedIds) {
                    throw ValidationException::withMessages(['items' => 'لا يمكن حذف أصناف مرتبطة بمستندات تنفيذ.']);
                }
            }
            foreach ($items as $row) {
                $id = $row['id'] ?? null;
                unset($row['id']);
                $id ? $order->items()->whereKey($id)->update($row) : $order->items()->create($row);
            }
            $order->followUps()->delete();
            $order->followUps()->createMany(array_map(fn (array $row): array => [...$row, 'created_by' => auth()->id()], $followUps));
            $retainedPaths = collect($attachments)->pluck('file_path')->filter()->all();
            $removedPaths = $order->attachments()->whereNotIn('file_path', $retainedPaths ?: [''])->pluck('file_path')->all();
            $order->attachments()->whereNotIn('file_path', $retainedPaths ?: [''])->delete();
            $this->persistAttachments($order, $attachments);
            $this->recalculate($order);
            DB::afterCommit(fn () => collect($removedPaths)->each(fn (string $path) => Storage::disk('public')->delete($path)));

            return $order->fresh(['customer', 'items.item', 'items.unit', 'followUps']);
        });
    }

    public function delete(CustomerPurchaseOrder $order): bool
    {
        if ($order->executions()->exists()) {
            throw ValidationException::withMessages(['record' => 'لا يمكن حذف أمر التوريد لارتباطه بمستندات تنفيذ.']);
        }

        $paths = $order->attachments()->pluck('file_path')->all();
        $deleted = DB::transaction(fn (): bool => (bool) $order->delete());
        if ($deleted) {
            collect($paths)->each(fn (string $path) => Storage::disk('public')->delete($path));
        }

        return $deleted;
    }

    public function replaceSalesInvoiceExecutions(SalesInvoice $invoice): void
    {
        if (! Schema::hasTable('customer_purchase_order_executions')) {
            return;
        }
        $oldOrderIds = CustomerPurchaseOrderExecution::query()
            ->where('source_type', $invoice->getMorphClass())->where('source_id', $invoice->getKey())
            ->pluck('customer_purchase_order_id')->unique();
        CustomerPurchaseOrderExecution::query()
            ->where('source_type', $invoice->getMorphClass())->where('source_id', $invoice->getKey())->delete();
        CustomerPurchaseOrder::query()->whereKey($oldOrderIds)->get()->each(fn ($oldOrder) => $this->recalculate($oldOrder));

        if (! $invoice->customer_purchase_order_id) {
            return;
        }

        $order = CustomerPurchaseOrder::query()->with('items.executions')->lockForUpdate()->findOrFail($invoice->customer_purchase_order_id);
        CustomerPurchaseOrderItem::query()->where('customer_purchase_order_id', $order->getKey())
            ->orderBy('id')->lockForUpdate()->get();
        if ((int) $order->customer_id !== (int) $invoice->customer_id || in_array($order->status, [CustomerPurchaseOrder::STATUS_CANCELLED, CustomerPurchaseOrder::STATUS_COMPLETED], true)) {
            throw ValidationException::withMessages(['customer_purchase_order_id' => 'أمر التوريد غير متاح لهذا العميل.']);
        }

        foreach ($invoice->items as $invoiceItem) {
            if (! $invoiceItem->customer_purchase_order_item_id) {
                continue;
            }
            $orderItem = $order->items->firstWhere('id', $invoiceItem->customer_purchase_order_item_id);
            $alreadyExecuted = (float) $orderItem?->executions()->lockForUpdate()->sum('executed_quantity');
            if (! $orderItem || (float) $invoiceItem->quantity > (float) $orderItem->ordered_quantity - $alreadyExecuted) {
                throw ValidationException::withMessages(['items' => 'كمية التنفيذ تتجاوز الكمية المتبقية بأمر التوريد.']);
            }
            $order->executions()->create([
                'customer_purchase_order_item_id' => $orderItem->getKey(),
                'source_type' => $invoice->getMorphClass(), 'source_id' => $invoice->getKey(),
                'source_item_id' => $invoiceItem->getKey(), 'executed_quantity' => $invoiceItem->quantity,
                'execution_date' => $invoice->invoice_date,
            ]);
        }
        $this->recalculate($order);
    }

    public function removeSalesInvoiceExecutions(SalesInvoice $invoice): void
    {
        if (! Schema::hasTable('customer_purchase_order_executions')) {
            return;
        }
        $orderIds = CustomerPurchaseOrderExecution::query()->where('source_type', $invoice->getMorphClass())
            ->where('source_id', $invoice->getKey())->pluck('customer_purchase_order_id')->unique();
        CustomerPurchaseOrderExecution::query()->where('source_type', $invoice->getMorphClass())->where('source_id', $invoice->getKey())->delete();
        CustomerPurchaseOrder::query()->whereKey($orderIds)->get()->each(fn ($order) => $this->recalculate($order));
    }

    public function recalculate(CustomerPurchaseOrder $order): void
    {
        $order->load('items.executions');
        foreach ($order->items as $item) {
            $executed = min((float) $item->ordered_quantity, (float) $item->executions->sum('executed_quantity'));
            $item->update(['executed_quantity' => $executed, 'remaining_quantity' => (float) $item->ordered_quantity - $executed]);
        }
        $percentage = $order->items->isEmpty() ? 0 : $order->items->avg(fn ($item) => (float) $item->ordered_quantity > 0 ? ((float) $item->executed_quantity / (float) $item->ordered_quantity) * 100 : 0);
        $status = $order->status;
        if (! in_array($status, [CustomerPurchaseOrder::STATUS_SUSPENDED, CustomerPurchaseOrder::STATUS_CANCELLED], true)) {
            $status = $percentage >= 100 ? CustomerPurchaseOrder::STATUS_COMPLETED : ($percentage > 0 ? CustomerPurchaseOrder::STATUS_PARTIAL : CustomerPurchaseOrder::STATUS_NEW);
        }
        $order->update(['execution_percentage' => round($percentage, 2), 'status' => $status, 'actual_completion_date' => $status === CustomerPurchaseOrder::STATUS_COMPLETED ? now()->toDateString() : null]);
    }

    /** @return array<int, array<string, mixed>> */
    public function linkedDocuments(CustomerPurchaseOrder $order): array
    {
        $executions = $order->executions()
            ->with('orderItem.item')
            ->orderBy('execution_date')->orderBy('created_at')->orderBy('id')->get();
        $invoices = SalesInvoice::query()->with(['customer', 'items'])
            ->whereKey($executions->pluck('source_id')->unique())->get()->keyBy('id');
        $running = [];
        $details = [];

        foreach ($executions as $execution) {
            $itemId = (int) $execution->customer_purchase_order_item_id;
            $previous = $running[$itemId] ?? 0.0;
            $executed = (float) $execution->executed_quantity;
            $running[$itemId] = $previous + $executed;
            $details[$execution->source_id][] = [
                'item' => $execution->orderItem?->item?->name ?: '—',
                'ordered' => (float) ($execution->orderItem?->ordered_quantity ?? 0),
                'executed' => $executed,
                'previous' => $previous,
                'remainingAfter' => max(0, (float) ($execution->orderItem?->ordered_quantity ?? 0) - $running[$itemId]),
            ];
        }

        return $executions->groupBy('source_id')->map(function ($rows, $sourceId) use ($details, $invoices): array {
            $first = $rows->first();
            $invoice = $invoices->get($sourceId);

            return [
                'reference' => $invoice instanceof SalesInvoice ? $invoice->document_number : "فاتورة #{$sourceId}",
                'date' => $invoice instanceof SalesInvoice ? $invoice->invoice_date->format('d/m/Y') : $first->execution_date->format('d/m/Y'),
                'customer' => $invoice instanceof SalesInvoice ? $invoice->customer->name : '—',
                'total' => $invoice instanceof SalesInvoice ? $invoice->totalAmount() : 0.0,
                'quantity' => (float) $rows->sum('executed_quantity'),
                'lines' => $rows->count(),
                'linkedAt' => $rows->min('created_at')?->format('d/m/Y H:i'),
                'url' => $invoice instanceof SalesInvoice && SalesInvoiceResource::canView($invoice)
                    ? SalesInvoiceResource::getUrl('view', ['record' => $invoice]) : null,
                'details' => $details[$sourceId] ?? [],
            ];
        })->values()->all();
    }

    private function normalizeItems(array $items, ?CustomerPurchaseOrder $order = null): array
    {
        if ($items === []) {
            throw ValidationException::withMessages(['items' => 'يجب إضافة صنف واحد على الأقل.']);
        }
        $seen = [];

        return array_map(function (array $row, int $index) use (&$seen, $order): array {
            $itemId = (int) ($row['item_id'] ?? 0);
            $quantity = (float) ($row['ordered_quantity'] ?? 0);
            if ($itemId <= 0 || $quantity <= 0 || isset($seen[$itemId])) {
                throw ValidationException::withMessages(["items.{$index}" => 'بيانات الصنف غير صحيحة أو مكررة.']);
            }
            $seen[$itemId] = true;
            $id = $row['id'] ?? null;
            $executed = $id && $order ? (float) $order->items()->whereKey($id)->value('executed_quantity') : 0;
            if ($quantity < $executed) {
                throw ValidationException::withMessages(["items.{$index}.ordered_quantity" => 'لا يمكن أن تقل الكمية المطلوبة عن الكمية المنفذة.']);
            }

            $lineSubtotal = round($quantity * (float) ($row['unit_price'] ?? 0), 2);
            $discount = min($lineSubtotal, max(0, round((float) ($row['discount_amount'] ?? 0), 2)));
            $taxExempt = (bool) ($row['tax_exempt'] ?? false);
            $taxRate = $taxExempt ? 0.0 : max(0, (float) ($row['tax_rate'] ?? 0));
            $lineTax = $taxExempt
                ? 0.0
                : round(max(0, $lineSubtotal - $discount) * ($taxRate / 100), 2);

            return [
                ...$row,
                'id' => $id,
                'executed_quantity' => $executed,
                'remaining_quantity' => $quantity - $executed,
                'discount_amount' => $discount,
                'tax_exempt' => $taxExempt,
                'tax_rate' => $taxRate,
                'line_subtotal' => $lineSubtotal,
                'line_tax' => $lineTax,
                'line_total' => round($lineSubtotal - $discount + $lineTax, 2),
                'sort_order' => $index,
            ];
        }, $items, array_keys($items));
    }

    private function validateHeader(array $data): void
{
    if (blank($data['customer_id'] ?? null)) {
        throw ValidationException::withMessages([
            'customer_id' => 'يجب اختيار العميل.',
        ]);
    }

    if (blank($data['order_date'] ?? null)) {
        throw ValidationException::withMessages([
            'order_date' => 'تاريخ الأمر مطلوب.',
        ]);
    }

    if (
        filled($data['required_delivery_date'] ?? null)
        && $data['required_delivery_date'] < $data['order_date']
    ) {
        throw ValidationException::withMessages([
            'required_delivery_date' => 'تاريخ التسليم لا يجوز أن يسبق تاريخ الأمر.',
        ]);
    }

    if (filled($data['sales_quotation_id'] ?? null)) {
        $quotationExists = SalesQuotation::query()
            ->whereKey($data['sales_quotation_id'])
            ->where('customer_id', $data['customer_id'])
            ->exists();

        if (! $quotationExists) {
            throw ValidationException::withMessages([
                'sales_quotation_id' => 'عرض السعر المحدد لا يتبع العميل المختار.',
            ]);
        }
    }
}

    private function persistAttachments(CustomerPurchaseOrder $order, array $attachments): void
    {
        foreach ($attachments as $attachment) {
            $path = (string) ($attachment['file_path'] ?? '');
            if (blank($path) || $order->attachments()->where('file_path', $path)->exists()) {
                continue;
            }
            $disk = Storage::disk('public');
            if (! $disk->exists($path)) {
                throw ValidationException::withMessages(['attachments' => 'تعذر العثور على ملف المرفق.']);
            }
            $mime = (string) $disk->mimeType($path);
            if (! in_array($mime, ['application/pdf', 'image/jpeg', 'image/png'], true) || $disk->size($path) > 5 * 1024 * 1024) {
                throw ValidationException::withMessages(['attachments' => 'نوع المرفق أو حجمه غير مسموح.']);
            }
            $order->attachments()->create([
                'original_name' => basename((string) ($attachment['original_name'] ?? $path)),
                'stored_name' => basename($path), 'file_path' => $path,
                'mime_type' => $mime, 'file_size' => $disk->size($path), 'uploaded_by' => auth()->id(),
            ]);
        }
    }
}
