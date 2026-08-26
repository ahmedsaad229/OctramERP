<?php

namespace App\Filament\Resources\SalesInvoices\Pages;

use App\Filament\Resources\SalesInvoices\SalesInvoiceResource;
use App\Models\CustomerPurchaseOrder;
use App\Services\CustomerPurchaseOrderConversionService;
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
        $customerPurchaseOrderId = request()->integer(
            'customer_purchase_order'
        );

        if ($customerPurchaseOrderId) {
            $this->fillFromCustomerPurchaseOrder(
                $customerPurchaseOrderId
            );

            return;
        }

        $salesQuotationId = request()->integer('sales_quotation');

        if ($salesQuotationId) {
            $this->fillFromSalesQuotation($salesQuotationId);
        }
    }

    private function fillFromCustomerPurchaseOrder(
        int $customerPurchaseOrderId
    ): void {
        $customerPurchaseOrder = CustomerPurchaseOrder::query()
            ->findOrFail($customerPurchaseOrderId);

        $conversionService = app(
            CustomerPurchaseOrderConversionService::class
        );

        $orderLines = collect(
            $conversionService->lines($customerPurchaseOrderId)
        )
            ->map(function (array $line): array {
                return [
                    ...$line,
                    'selected' => true,
                    'import_quantity' => $line['remaining_quantity'] ?? 0,
                ];
            })
            ->values()
            ->all();

        $invoiceItems = $conversionService->invoiceItems($orderLines);

        $this->data = [
            ...$this->data,

            /*
             * نلغي الربط المباشر بعرض السعر عند التحويل من أمر التوريد،
             * لأن المستند المصدر هنا هو أمر التوريد.
             */
            'sales_quotation_id' => null,

            'customer_purchase_order_id' => $customerPurchaseOrder->getKey(),
            'customer_id' => $customerPurchaseOrder->customer_id,

            /*
             * تظهر البنود في قسم الاستيراد، ويتم تحديد جميع البنود
             * المتبقية بصورة تلقائية.
             */
            'order_import_lines' => $orderLines,

            /*
             * تُضاف الكميات المتبقية مباشرة إلى أصناف الفاتورة،
             * ويمكن للمستخدم تعديل الكمية قبل الحفظ لتنفيذ فاتورة جزئية.
             */
            'items' => $invoiceItems,
        ];
    }

    private function fillFromSalesQuotation(int $salesQuotationId): void
    {
        $this->data = [
            ...$this->data,
            'sales_quotation_id' => $salesQuotationId,
            ...app(SalesQuotationConversionService::class)->payload(
                $salesQuotationId
            ),
        ];
    }

    protected function handleRecordCreation(array $data): Model
    {
        try {
            return app(SalesInvoiceService::class)->create($data);
        } catch (ValidationException $exception) {
            if (
                ! collect($exception->errors())
                    ->flatten()
                    ->contains(self::INSUFFICIENT_STOCK_MESSAGE)
            ) {
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