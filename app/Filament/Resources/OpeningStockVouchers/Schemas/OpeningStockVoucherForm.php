<?php

namespace App\Filament\Resources\OpeningStockVouchers\Schemas;

use App\Models\Warehouse;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class OpeningStockVoucherForm
{
    public static function configure(
        Schema $schema,
        array $headerComponents = [],
        string $dateField = 'voucher_date',
    ): Schema
    {
        return $schema
            ->components([

                Grid::make(2)
                    ->schema([

                        Placeholder::make('code')
                            ->label('رقم المستند')
                            ->content(fn ($record) => $record?->code ?? 'سيتم إنشاؤه تلقائياً'),

                        DatePicker::make($dateField)
                            ->label('التاريخ')
                            ->required()
                            ->default(now()),

                        Select::make('warehouse_id')
                            ->label('المخزن')
                            ->relationship('warehouse', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),

                        ...$headerComponents,

                    ]),

                Textarea::make('notes')
                    ->label('ملاحظات')
                    ->rows(3),

                Repeater::make('items')
                    ->relationship()
                    ->label('أصناف المستند')
                    ->schema([

                        Grid::make(4)
                            ->schema([

                                Select::make('item_id')
                                    ->label('الصنف')
                                    ->relationship('item', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required(),

                                \Filament\Forms\Components\TextInput::make('quantity')
                                    ->label('الكمية')
                                    ->numeric()
                                    ->default(1)
                                    ->required()
                                    ->live(),

                                \Filament\Forms\Components\TextInput::make('unit_cost')
                                    ->label('تكلفة الوحدة')
                                    ->numeric()
                                    ->default(0)
                                    ->required()
                                    ->live(),

                                Placeholder::make('total')
                                    ->label('الإجمالي')
                                    ->content(function ($get) {

                                        return number_format(
                                            (float) $get('quantity')
                                            *
                                            (float) $get('unit_cost'),
                                            2
                                        );

                                    }),

                            ]),

                        Textarea::make('notes')
                            ->label('ملاحظات'),

                    ])
                    ->defaultItems(1)
                    ->addActionLabel('إضافة صنف')
                    ->collapsible()
                    ->cloneable(),

            ]);
    }
}
