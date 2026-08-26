<?php

namespace App\Filament\Resources\SalesInvoices\Pages;

use App\Filament\Resources\SalesInvoices\SalesInvoiceResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\Support\Htmlable;

class ViewSalesInvoice extends ViewRecord
{
    protected static string $resource = SalesInvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('تعديل الفاتورة')
                ->icon('heroicon-o-pencil-square')
                ->color('primary'),

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
        ];
    }

    public function getTitle(): string
    {
        return 'فاتورة مبيعات';
    }

    public function getHeading(): string|Htmlable|null
    {
        return 'فاتورة مبيعات رقم '.$this->getRecord()->document_number;
    }

    public function getSubheading(): ?string
    {
        $record = $this->getRecord();

        return 'العميل: '.($record->customer?->name ?? '—')
            .' — التاريخ: '
            .$record->invoice_date?->format('d/m/Y');
    }
}
