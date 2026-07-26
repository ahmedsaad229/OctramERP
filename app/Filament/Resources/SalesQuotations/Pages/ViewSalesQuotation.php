<?php

namespace App\Filament\Resources\SalesQuotations\Pages;

use App\Filament\Resources\SalesInvoices\Pages\CreateSalesInvoice;
use App\Filament\Resources\SalesQuotations\SalesQuotationResource;
use App\Models\SalesQuotation;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\Support\Htmlable;

class ViewSalesQuotation extends ViewRecord
{
    protected static string $resource = SalesQuotationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()->label('تعديل'),
            Action::make('convert')
                ->label('تحويل إلى فاتورة بيع')
                ->icon('heroicon-o-arrow-path')
                ->color('success')
                ->visible(fn (): bool => ! $this->getRecord()->isFullyConverted())
                ->url(fn (): string => CreateSalesInvoice::getUrl([
                    'sales_quotation' => $this->getRecord()->getKey(),
                ])),
            Action::make('print')
                ->label('طباعة عرض السعر')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->url(fn (): string => route('sales-quotations.print', $this->getRecord()))
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

        return $record->conversionStatus() === SalesQuotation::STATUS_FULLY_CONVERTED
            ? 'محول بالكامل'
            : $record->expiryLabel();
    }
}
