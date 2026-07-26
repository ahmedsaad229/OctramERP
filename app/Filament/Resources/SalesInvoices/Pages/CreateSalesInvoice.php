<?php

namespace App\Filament\Resources\SalesInvoices\Pages;

use App\Filament\Resources\SalesInvoices\SalesInvoiceResource;
use App\Services\Inventory\SalesInvoiceService;
use App\Services\SalesQuotationConversionService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class CreateSalesInvoice extends CreateRecord
{
    private const INSUFFICIENT_STOCK_MESSAGE = 'الكمية المطلوبة غير متوفرة في المخزن.';

    protected static string $resource = SalesInvoiceResource::class;

    protected static bool $canCreateAnother = false;

    protected function afterFill(): void
    {
        $quotationId = request()->integer('sales_quotation');
        if (! $quotationId) {
            return;
        }
        $this->data = [
            ...$this->data,
            'sales_quotation_id' => $quotationId,
            ...app(SalesQuotationConversionService::class)->payload($quotationId),
        ];
    }

    protected function handleRecordCreation(array $data): Model
    {
        try {
            return app(SalesInvoiceService::class)->create($data);
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

    protected function getCreateFormAction(): Action
    {
        return parent::getCreateFormAction()
            ->label('حفظ الفاتورة');
    }
}
