<?php

namespace App\Filament\Resources\SalesQuotations\Pages;

use App\Filament\Resources\CustomerPurchaseOrders\CustomerPurchaseOrderResource;
use App\Filament\Resources\SalesQuotations\SalesQuotationResource;
use App\Models\CustomerPurchaseOrder;
use App\Models\SalesQuotation;
use App\Services\SalesQuotationToCustomerPurchaseOrderService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\Support\Htmlable;
use Throwable;

class ViewSalesQuotation extends ViewRecord
{
    protected static string $resource = SalesQuotationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('تعديل'),

            Action::make('convert_to_purchase_order')
                ->label('تحويل إلى أمر توريد')
                ->icon('heroicon-o-arrow-right-circle')
                ->color('success')
                ->visible(fn (): bool => ! $this->hasPurchaseOrder())
                ->requiresConfirmation()
                ->modalHeading('تحويل عرض السعر إلى أمر توريد')
                ->modalDescription(
                    'سيتم إنشاء أمر توريد جديد ونقل العميل والبنود من عرض السعر.'
                )
                ->modalSubmitActionLabel('تنفيذ التحويل')
                ->action(function () {
                    try {
                        /** @var SalesQuotation $quotation */
                        $quotation = $this->getRecord();

                        $purchaseOrder = app(
                            SalesQuotationToCustomerPurchaseOrderService::class
                        )->convert($quotation);

                        Notification::make()
                            ->title('تم إنشاء أمر التوريد بنجاح')
                            ->body(
                                'تم إنشاء أمر التوريد رقم '
                                .$purchaseOrder->document_number
                            )
                            ->success()
                            ->send();

                        return redirect()->to(
                            CustomerPurchaseOrderResource::getUrl('edit', [
                                'record' => $purchaseOrder,
                            ])
                        );
                    } catch (Throwable $exception) {
                        report($exception);

                        Notification::make()
                            ->title('تعذر إنشاء أمر التوريد')
                            ->body($exception->getMessage())
                            ->danger()
                            ->persistent()
                            ->send();

                        return null;
                    }
                }),

            Action::make('open_purchase_order')
                ->label('فتح أمر التوريد')
                ->icon('heroicon-o-document-text')
                ->color('primary')
                ->visible(fn (): bool => $this->hasPurchaseOrder())
                ->url(function (): ?string {
                    $purchaseOrder = $this->getPurchaseOrder();

                    if (! $purchaseOrder) {
                        return null;
                    }

                    return CustomerPurchaseOrderResource::getUrl('edit', [
                        'record' => $purchaseOrder,
                    ]);
                }),

            Action::make('print')
                ->label('طباعة عرض السعر')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->url(
                    fn (): string => route(
                        'sales-quotations.print',
                        $this->getRecord()
                    )
                )
                ->openUrlInNewTab(),
        ];
    }

    public function getTitle(): string
    {
        return 'عرض السعر '.$this->getRecord()->quotation_number;
    }

    public function getHeading(): string|Htmlable|null
    {
        return view('filament.resources.sales-quotations.view-heading', [
            'record' => $this->getRecord(),
        ]);
    }

    public function getSubheading(): ?string
    {
        /** @var SalesQuotation $record */
        $record = $this->getRecord();

        if ($this->hasPurchaseOrder()) {
            return 'تم التحويل إلى أمر توريد';
        }

        return $record->expiryLabel();
    }

    private function hasPurchaseOrder(): bool
    {
        return CustomerPurchaseOrder::query()
            ->where(
                'sales_quotation_id',
                $this->getRecord()->getKey()
            )
            ->exists();
    }

    private function getPurchaseOrder(): ?CustomerPurchaseOrder
    {
        return CustomerPurchaseOrder::query()
            ->where(
                'sales_quotation_id',
                $this->getRecord()->getKey()
            )
            ->first();
    }
}