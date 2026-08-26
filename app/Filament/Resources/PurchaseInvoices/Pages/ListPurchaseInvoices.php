<?php

namespace App\Filament\Resources\PurchaseInvoices\Pages;

use App\Filament\Resources\PurchaseInvoices\PurchaseInvoiceResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPurchaseInvoices extends ListRecords
{
    protected static string $resource = PurchaseInvoiceResource::class;

    private function reportUrl(string $routeName): string
    {
        $ids = $this->getFilteredTableQuery()
            ?->pluck('purchase_invoices.id')
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
                    'purchase-invoices.export-excel'
                ))
                ->openUrlInNewTab(),

            Action::make('export_pdf')
                ->label('PDF')
                ->icon('heroicon-o-document-text')
                ->color('danger')
                ->url(fn (): string => $this->reportUrl(
                    'purchase-invoices.export-pdf'
                ))
                ->openUrlInNewTab(),

            Action::make('detailed_report')
                ->label('تقرير تفصيلي')
                ->icon('heroicon-o-rectangle-stack')
                ->color('info')
                ->url(fn (): string => $this->reportUrl(
                    'purchase-invoices.detailed-report'
                ))
                ->openUrlInNewTab(),

            CreateAction::make()
                ->label('إضافة فاتورة شراء'),
        ];
    }
}
