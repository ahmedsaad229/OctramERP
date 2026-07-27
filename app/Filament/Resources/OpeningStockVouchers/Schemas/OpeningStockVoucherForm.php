<?php

namespace App\Filament\Resources\OpeningStockVouchers\Schemas;

use App\Support\DocumentFieldPresentation;
use App\Support\QuantityFormatter;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class OpeningStockVoucherForm
{
    public static function configure(
        Schema $schema,
        array $headerComponents = [],
        string $dateField = 'voucher_date',
    ): Schema {
        return $schema->components([
            Grid::make(2)->schema([
                Placeholder::make('code')
                    ->label('رقم المستند')
                    ->content(fn ($record) => $record?->code ?? 'سيتم إنشاؤه تلقائيًا')
                    ->extraAttributes(DocumentFieldPresentation::itemCode())
                    ->extraEntryWrapperAttributes(DocumentFieldPresentation::wrapper())
                    ->alignCenter(),
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
                    Grid::make(4)->schema([
                        Select::make('item_id')
                            ->label('الصنف')
                            ->relationship('item', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        TextInput::make('quantity')
                            ->label('الكمية')
                            ->type('text')
                            ->formatStateUsing(fn (mixed $state): ?string => QuantityFormatter::normalizeForInput($state))
                            ->mutateStateForValidationUsing(fn (mixed $state): mixed => QuantityFormatter::normalizeForInput($state) ?? $state)
                            ->dehydrateStateUsing(fn (mixed $state): mixed => QuantityFormatter::normalizeForInput($state) ?? $state)
                            ->inputMode('decimal')
                            ->rules(['numeric', 'gt:0'])
                            ->afterStateUpdated(fn (TextInput $component, mixed $state) => $component->state(
                                QuantityFormatter::normalizeForInput($state) ?? $state,
                            ))
                            ->extraInputAttributes(QuantityFormatter::inputAttributes())
                            ->default(1)
                            ->required()
                            ->live(),
                        TextInput::make('unit_cost')
                            ->label('تكلفة الوحدة')
                            ->type('text')
                            ->formatStateUsing(fn (mixed $state): ?string => QuantityFormatter::normalizeForInput($state))
                            ->mutateStateForValidationUsing(fn (mixed $state): mixed => QuantityFormatter::normalizeForInput($state) ?? $state)
                            ->dehydrateStateUsing(fn (mixed $state): mixed => QuantityFormatter::normalizeForInput($state) ?? $state)
                            ->inputMode('decimal')
                            ->rules(['numeric', 'gte:0'])
                            ->afterStateUpdated(fn (TextInput $component, mixed $state) => $component->state(
                                QuantityFormatter::normalizeForInput($state) ?? $state,
                            ))
                            ->extraInputAttributes(QuantityFormatter::inputAttributes())
                            ->default(0)
                            ->required()
                            ->live(),
                        Placeholder::make('total')
                            ->label('الإجمالي')
                            ->content(fn ($get): string => number_format(
                                (float) $get('quantity') * (float) $get('unit_cost'),
                                2,
                            ).' ج.م')
                            ->extraAttributes(DocumentFieldPresentation::money())
                            ->extraEntryWrapperAttributes(DocumentFieldPresentation::wrapper())
                            ->alignCenter(),
                    ]),
                    Textarea::make('notes')->label('ملاحظات'),
                ])
                ->defaultItems(1)
                ->addActionLabel('إضافة صنف')
                ->collapsible()
                ->cloneable(),
        ]);
    }
}
