<?php

namespace App\Filament\Resources\PurchaseInvoices\Pages;


use Filament\Actions\Action;
use App\Filament\Actions\ProtectedDeleteAction;
use App\Filament\Resources\PurchaseInvoices\PurchaseInvoiceResource;
use App\Models\PurchaseInvoice;
use App\Services\Inventory\PurchaseInvoiceService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class EditPurchaseInvoice extends EditRecord
{
    protected static string $resource = PurchaseInvoiceResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        if (! $this->getRecord()->supplier_purchase_order_id) {
            $data['items'] = $this->getRecord()->items()
                ->get(['item_id', 'quantity', 'unit_cost', 'notes'])
                ->toArray();

            return $data;
        }

        $remaining = collect(app(PurchaseInvoiceService::class)->importRemainingOrderItems(
            $this->getRecord()->supplier_purchase_order_id,
            $this->getRecord(),
        ))->keyBy('supplier_purchase_order_item_id');

        $data['items'] = $this->getRecord()->items->map(function ($invoiceItem) use ($remaining): array {
            return [
                ...$remaining->get($invoiceItem->supplier_purchase_order_item_id, []),
                'supplier_purchase_order_item_id' => $invoiceItem->supplier_purchase_order_item_id,
                'item_id' => $invoiceItem->item_id,
                'quantity' => (float) $invoiceItem->quantity,
                'unit_cost' => (float) $invoiceItem->unit_cost,
                'notes' => $invoiceItem->notes,
            ];
        })->all();

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        try {
            return app(PurchaseInvoiceService::class)->update($record, $data);
        } catch (ValidationException $exception) {
            $this->notifyValidationFailure();

            throw $exception;
        }
    }

    protected function onValidationError(ValidationException $exception): void
    {
        $this->notifyValidationFailure();
    }

    protected function getHeaderActions(): array
    {
        return [
                        Action::make('print')
                ->label('طباعة / حفظ PDF')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->url(
                    fn (PurchaseInvoice $record): string => route(
                        'purchase-invoices.print',
                        $record
                    )
                )
                ->openUrlInNewTab(),
ProtectedDeleteAction::make()
                ->using(fn (PurchaseInvoice $record): bool => app(PurchaseInvoiceService::class)->delete($record)),
        ];
    }

    private function notifyValidationFailure(): void
    {
        Notification::make()
            ->danger()
            ->title('تعذر حفظ فاتورة الشراء')
            ->body('راجع بيانات أمر التوريد والكميات المطلوبة.')
            ->send();
    }
}
