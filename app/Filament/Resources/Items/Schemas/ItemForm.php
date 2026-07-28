<?php

namespace App\Filament\Resources\Items\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class ItemForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                Grid::make(2)
                    ->schema([

                        TextInput::make('code')
                            ->label('الكود')
                            ->readOnly()
                            ->dehydrated(),

                        TextInput::make('name')
                            ->label('اسم الصنف')
                            ->required(),

                        TextInput::make('name_en')
                            ->label('الاسم بالإنجليزية'),

                        TextInput::make('sku')
                            ->label('SKU'),

                        TextInput::make('barcode')
                            ->label('الباركود'),

                        Select::make('category_id')
                            ->label('الفئة')
                            ->relationship('category', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Select::make('unit_id')
                            ->label('وحدة القياس')
                            ->relationship('unit', 'name')
                            ->searchable()
                            ->preload(),

                        TextInput::make('purchase_price')
                            ->label('سعر الشراء')
                            ->numeric()
                            ->default(0),

                        TextInput::make('sale_price')
                            ->label('سعر البيع')
                            ->numeric()
                            ->default(0),

                        TextInput::make('minimum_stock')
                            ->label('الحد الأدنى')
                            ->numeric()
                            ->default(0),

                        TextInput::make('reorder_level')
                            ->label('حد إعادة الطلب')
                            ->numeric()
                            ->minValue(0)
                            ->nullable(),

                        Toggle::make('allow_negative_stock')
                            ->label('السماح بمخزون سالب')
                            ->default(false),

                        Toggle::make('active')
                            ->label('نشط')
                            ->default(true),
                    ]),

                Textarea::make('description')
                    ->label('الوصف')
                    ->rows(4)
                    ->columnSpanFull(),

            ]);
    }
}
