<?php

namespace App\Filament\Resources\StockBalances\Tables;

use App\Models\Category;
use App\Support\QuantityFormatter;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class StockBalancesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->poll('5s')
            ->defaultSort('updated_at', 'desc')
            ->columns([
                TextColumn::make('item.code')
                    ->label('كود الصنف')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('item.name')
                    ->label('اسم الصنف')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('warehouse.name')
                    ->label('المخزن')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('quantity')
                    ->label('الكمية')
                    ->formatStateUsing(fn (mixed $state): string => QuantityFormatter::formatForDisplay($state))
                    ->extraAttributes(QuantityFormatter::displayAttributes())
                    ->alignCenter()
                    ->sortable(),

                TextColumn::make('average_cost')
                    ->label('متوسط التكلفة')
                    ->money('EGP')
                    ->alignCenter()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('warehouse_id')
                    ->label('المخزن')
                    ->relationship('warehouse', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('item_category')
                    ->label('فئة الصنف')
                    ->options(fn (): array => Category::query()
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all())
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['value'] ?? null,
                            fn (Builder $query, int $categoryId): Builder => $query->whereHas(
                                'item',
                                fn (Builder $query): Builder => $query->where('category_id', $categoryId),
                            ),
                        );
                    }),
            ]);
    }
}
