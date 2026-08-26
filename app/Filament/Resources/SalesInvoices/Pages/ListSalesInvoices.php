<?php

namespace App\Filament\Resources\SalesInvoices\Pages;

use App\Filament\Resources\SalesInvoices\SalesInvoiceResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSalesInvoices extends ListRecords
{
    protected static string $resource = SalesInvoiceResource::class;

    private function reportUrl(string $routeName): string
    {
        $ids = $this->getFilteredTableQuery()
            ?->pluck('sales_invoices.id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all() ?? [];

        return route($routeName, [
            'ids' => implode(',', $ids),
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export_excel')
                ->label('Excel')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->url(fn (): string => $this->reportUrl(
                    'sales-invoices.export-excel'
                ))
                ->openUrlInNewTab(),

            Action::make('export_pdf')
                ->label('PDF')
                ->icon('heroicon-o-document-text')
                ->color('danger')
                ->url(fn (): string => $this->reportUrl(
                    'sales-invoices.export-pdf'
                ))
                ->openUrlInNewTab(),

            Action::make('detailed_report')
                ->label('تقرير تفصيلي')
                ->icon('heroicon-o-rectangle-stack')
                ->color('info')
                ->url(fn (): string => $this->reportUrl(
                    'sales-invoices.detailed-report'
                ))
                ->openUrlInNewTab(),

            CreateAction::make()
                ->label('إضافة فاتورة بيع'),
        ];
    }
}
