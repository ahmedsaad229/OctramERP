<?php

namespace App\Filament\Resources\SalesInvoices\Schemas;

use App\Models\Item;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class SalesInvoiceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(2)->schema([
                TextInput::make('document_number')
                    ->label('رقم المستند')
                    ->placeholder('سيتم إنشاؤه تلقائياً')
                    ->disabled()
                    ->dehydrated(false),

                DatePicker::make('invoice_date')
                    ->label('تاريخ الفاتورة')
                    ->default(now())
                    ->native(false)
                    ->required(),

                Select::make('customer_id')
                    ->label('العميل')
                    ->relationship(
                        'customer',
                        'name',
                        modifyQueryUsing: fn (Builder $query): Builder => $query->where('active', true),
                    )
                    ->searchable()
                    ->preload()
                    ->required(),

                Select::make('warehouse_id')
                    ->label('المخزن')
                    ->relationship(
                        'warehouse',
                        'name',
                        modifyQueryUsing: fn (Builder $query): Builder => $query->where('active', true),
                    )
                    ->searchable()
                    ->preload()
                    ->required(),
            ]),

            Textarea::make('notes')
                ->label('ملاحظات')
                ->rows(3),

            Repeater::make('items')
                ->label('أصناف الفاتورة')
                ->schema([
                    Grid::make(4)->schema([
                        Select::make('item_id')
                            ->label('الصنف')
                            ->options(fn (): array => Item::query()
                                ->where('active', true)
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->all())
                            ->searchable()
                            ->preload()
                            ->required()
                            ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                            ->live()
                            ->afterStateUpdated(function (Set $set, mixed $state): void {
                                $set(
                                    'unit_price',
                                    Item::query()->whereKey($state)->value('sale_price') ?? 0,
                                );
                            }),

                        TextInput::make('quantity')
                            ->label('الكمية')
                            ->numeric()
                            ->minValue(0.01)
                            ->default(1)
                            ->required()
                            ->live(),

                        TextInput::make('unit_price')
                            ->label('سعر البيع')
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->required()
                            ->live(),

                        Placeholder::make('line_total_display')
                            ->label('الإجمالي')
                            ->content(fn (Get $get): string => number_format(
                                (float) $get('quantity') * (float) $get('unit_price'),
                                2,
                            )),
                    ]),
                ])
                ->defaultItems(1)
                ->minItems(1)
                ->addActionLabel('إضافة صنف')
                ->reorderable(false),

            Placeholder::make('invoice_total')
                ->label('إجمالي الفاتورة')
                ->content(fn (Get $get): string => number_format(
                    collect($get('items') ?? [])->sum(
                        fn (array $item): float => (float) ($item['quantity'] ?? 0)
                            * (float) ($item['unit_price'] ?? 0),
                    ),
                    2,
                )),
        ]);
    }
}
