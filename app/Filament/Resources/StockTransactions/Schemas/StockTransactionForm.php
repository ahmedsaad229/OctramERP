<?php

namespace App\Filament\Resources\StockTransactions\Schemas;

use App\Models\Item;
use App\Models\StockTransaction;
use App\Models\Warehouse;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class StockTransactionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                Grid::make(2)
                    ->schema([

                        Select::make('warehouse_id')
                            ->label('المخزن')
                            ->relationship('warehouse', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Select::make('item_id')
                            ->label('الصنف')
                            ->relationship('item', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Select::make('transaction_type')
                            ->label('نوع الحركة')
                            ->options(StockTransaction::types())
                            ->default(StockTransaction::TYPE_OPENING)
                            ->required(),

                        TextInput::make('quantity')
                            ->label('الكمية')
                            ->numeric()
                            ->required()
                            ->default(1)
                            ->minValue(0.01),

                        TextInput::make('unit_cost')
                            ->label('تكلفة الوحدة')
                            ->numeric()
                            ->required()
                            ->default(0)
                            ->minValue(0)
                            ->suffix('ج.م'),

                        DatePicker::make('transaction_date')
                            ->label('التاريخ')
                            ->required()
                            ->default(now()),

                        TextInput::make('reference_no')
                            ->label('رقم المرجع')
                            ->maxLength(255),

                    ]),

                Textarea::make('notes')
                    ->label('ملاحظات')
                    ->rows(4)
                    ->columnSpanFull(),

            ]);
    }
}