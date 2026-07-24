<?php

namespace App\Filament\Resources\PurchaseInvoices\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PurchaseInvoicesTable
{
    public static function configure(Table $table): Table
    {
        return $table->defaultSort('id', 'desc')->columns([
            TextColumn::make('code')->label('رقم المستند')->searchable()->sortable(),
            TextColumn::make('invoice_number')->label('رقم الفاتورة')->searchable()->sortable(),
            TextColumn::make('supplier.name')->label('المورد')->searchable()->sortable(),
            TextColumn::make('invoice_date')->label('التاريخ')->date()->sortable(),
            TextColumn::make('warehouse.name')->label('المخزن')->searchable()->sortable(),
            TextColumn::make('items_count')->counts('items')->label('عدد الأصناف')->badge(),
            IconColumn::make('posted')->label('مرحل')->boolean(),
        ])->recordActions([EditAction::make()]);
    }
}
