<?php

namespace App\Filament\Resources\SalesInvoices\Pages;

use App\Filament\Actions\ProtectedDeleteAction;
use App\Filament\Resources\SalesInvoices\SalesInvoiceResource;
use App\Models\SalesInvoice;
use App\Services\CustomerPurchaseOrderConversionService;
use App\Services\Inventory\SalesInvoiceService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class EditSalesInvoice extends EditRecord
{
    private const INSUFFICIENT_STOCK_MESSAGE = 'الكمية المطلوبة غير متوفرة في المخزن.';

    protected static string $resource = SalesInvoiceResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['items'] = $this->getRecord()
            ->items()
            ->with('item')
            ->get(['item_id', 'sales_quotation_item_id', 'customer_purchase_order_item_id', 'unit_id', 'quantity', 'unit_price', 'discount_amount', 'tax_amount', 'notes'])
            ->map(fn ($item): array => [
                'item_id' => $item->item_id,
                'is_stock_item_state' => $item->item?->is_stock_item,
                'sales_quotation_item_id' => $item->sales_quotation_item_id,
                'customer_purchase_order_item_id' => $item->customer_purchase_order_item_id,
                'unit_id' => $item->unit_id,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'discount_amount' => $item->discount_amount,
                'tax_amount' => $item->tax_amount,
                'notes' => $item->notes,
            ])
            ->all();
        $data['order_import_lines'] = $this->getRecord()->customer_purchase_order_id
            ? app(CustomerPurchaseOrderConversionService::class)->lines(
                $this->getRecord()->customer_purchase_order_id,
                $this->getRecord()->getKey(),
            ) : [];

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        try {
            return app(SalesInvoiceService::class)->update($record, $data);
        } catch (ValidationException $exception) {
            if (! collect($exception->errors())->flatten()->contains(self::INSUFFICIENT_STOCK_MESSAGE)) {
                throw $exception;
            }

            Notification::make()
                ->danger()
                ->title(self::INSUFFICIENT_STOCK_MESSAGE)
                ->persistent()
                ->send();

            foreach (array_keys($data['items'] ?? []) as $index) {
                $this->addError(
                    "data.items.{$index}.quantity",
                    self::INSUFFICIENT_STOCK_MESSAGE,
                );
            }

            throw (new Halt)->rollBackDatabaseTransaction();
        }
    }

    protected function getSaveFormAction(): Action
    {
        return parent::getSaveFormAction()
            ->label('حفظ التعديلات');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('print')
                ->label('طباعة الفاتورة')
                ->icon('heroicon-o-printer')
                ->color('success')
                ->url(
                    fn (): string => route(
                        'sales-invoices.print',
                        $this->getRecord()
                    )
                )
                ->openUrlInNewTab(),

            ProtectedDeleteAction::make()
                ->using(fn (SalesInvoice $record): bool => app(SalesInvoiceService::class)->delete($record)),
        ];
    }
}
