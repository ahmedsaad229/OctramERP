<?php

namespace App\Filament\Resources\Items\Tables;

use App\Filament\Actions\ProtectedDeleteBulkAction;
use App\Filament\Resources\PurchaseRequests\PurchaseRequestResource;
use App\Filament\Resources\SalesQuotations\SalesQuotationResource;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class ItemsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([

                TextColumn::make('code')
                    ->label('الكود')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('name')
                    ->label('اسم الصنف')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('category.name')
                    ->label('الفئة')
                    ->searchable()
                    ->sortable(),

                IconColumn::make('is_stock_item')
                    ->label('يؤثر على المخزون')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('unit.name')
                    ->label('الوحدة')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('purchase_price')
                    ->label('سعر الشراء')
                    ->money('EGP')
                    ->sortable(),

                TextColumn::make('sale_price')
                    ->label('سعر البيع')
                    ->money('EGP')
                    ->sortable(),

                IconColumn::make('active')
                    ->label('نشط')
                    ->boolean(),

                TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->date('Y-m-d')
                    ->sortable(),

            ])
            ->filters([
                SelectFilter::make("category_id")
                    ->label("الفئة")
                    ->relationship("category", "name")
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('create_purchase_request')
                        ->label('إنشاء طلب شراء')
                        ->icon('heroicon-o-shopping-cart')
                        ->color('primary')
                        ->visible(fn (): bool => PurchaseRequestResource::canCreate())
                        ->deselectRecordsAfterCompletion()
                        ->action(function (Collection $records) {
                            $ids = $records->pluck('id')->map(fn ($id): int => (int) $id)->values()->all();

                            return redirect()->to(
                                PurchaseRequestResource::getUrl('create', [
                                    'item_ids' => implode(',', $ids),
                                ])
                            );
                        }),

                    BulkAction::make('create_sales_quotation')
                        ->label('إنشاء عرض سعر')
                        ->icon('heroicon-o-document-text')
                        ->color('success')
                        ->visible(fn (): bool => SalesQuotationResource::canCreate())
                        ->deselectRecordsAfterCompletion()
                        ->action(function (Collection $records) {
                            $ids = $records->pluck('id')->map(fn ($id): int => (int) $id)->values()->all();

                            return redirect()->to(
                                SalesQuotationResource::getUrl('create', [
                                    'item_ids' => implode(',', $ids),
                                ])
                            );
                        }),

                    ProtectedDeleteBulkAction::make(),
                ]),
            ]);
    }
}
