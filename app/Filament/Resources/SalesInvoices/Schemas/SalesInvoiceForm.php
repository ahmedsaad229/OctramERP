<?php

namespace App\Filament\Resources\SalesInvoices\Schemas;

use App\Models\Item;
use App\Models\SalesInvoice;
use App\Services\Inventory\InventoryService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class SalesInvoiceForm
{
    public static function configure(Schema $schema): Schema
    {
        $schema->columns(1);

        return $schema->components([
            Section::make('بيانات الفاتورة')
                ->schema([
                    Grid::make([
                        'default' => 1,
                        'md' => 2,
                        'xl' => 3,
                    ])->schema([
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

                        TextInput::make('electronic_invoice_number')
                            ->label('رقم الفاتورة الإلكترونية')
                            ->required()
                            ->numeric()
                            ->integer()
                            ->minValue(1)
                            ->validationMessages([
                                'required' => 'رقم الفاتورة الإلكترونية مطلوب.',
                                'integer' => 'رقم الفاتورة الإلكترونية يجب أن يكون رقماً صحيحاً.',
                                'min' => 'رقم الفاتورة الإلكترونية يجب أن يكون أكبر من صفر.',
                            ]),

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
                            ->required()
                            ->live(),

                    ])->columnSpanFull(),
                ])
                ->columns(1)
                ->columnSpanFull(),

            Section::make('أصناف الفاتورة')
                ->schema([
                    Repeater::make('items')
                        ->label('أصناف الفاتورة')
                        ->schema([
                            Grid::make([
                                'default' => 1,
                                'md' => 6,
                                'xl' => 13,
                            ])->schema([
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
                                    })
                                    ->columnSpan([
                                        'default' => 1,
                                        'md' => 6,
                                        'xl' => 4,
                                    ]),

                                TextInput::make('quantity')
                                    ->label('الكمية')
                                    ->numeric()
                                    ->minValue(0.01)
                                    ->default(1)
                                    ->required()
                                    ->live()
                                    ->columnSpan([
                                        'default' => 1,
                                        'md' => 3,
                                        'xl' => 3,
                                    ]),

                                Placeholder::make('warehouse_stock_balance')
                                    ->label('الرصيد بالمخزن')
                                    ->content(fn (Get $get, ?SalesInvoice $record): string => number_format(
                                        app(InventoryService::class)->availableForSalesInvoice(
                                            (int) $get('../../warehouse_id'),
                                            (int) $get('item_id'),
                                            $record?->getKey(),
                                        ),
                                        2,
                                    ))
                                    ->columnSpan([
                                        'default' => 1,
                                        'md' => 3,
                                        'xl' => 2,
                                    ]),

                                TextInput::make('unit_price')
                                    ->label('سعر البيع')
                                    ->numeric()
                                    ->minValue(0)
                                    ->default(0)
                                    ->required()
                                    ->live()
                                    ->columnSpan([
                                        'default' => 1,
                                        'md' => 3,
                                        'xl' => 3,
                                    ]),

                                Placeholder::make('line_total_display')
                                    ->label('الإجمالي')
                                    ->content(fn (Get $get): string => number_format(
                                        (float) $get('quantity') * (float) $get('unit_price'),
                                        2,
                                    ))
                                    ->columnSpan([
                                        'default' => 1,
                                        'md' => 3,
                                        'xl' => 1,
                                    ]),
                            ])->columnSpanFull(),
                        ])
                        ->defaultItems(1)
                        ->minItems(1)
                        ->addActionLabel('إضافة صنف')
                        ->reorderable(false)
                        ->columnSpanFull(),

                    Placeholder::make('invoice_total')
                        ->label('إجمالي الفاتورة')
                        ->content(fn (Get $get): string => number_format(
                            collect($get('items') ?? [])->sum(
                                fn (array $item): float => (float) ($item['quantity'] ?? 0)
                                    * (float) ($item['unit_price'] ?? 0),
                            ),
                            2,
                        ))
                        ->columnSpanFull(),
                ])
                ->columns(1)
                ->columnSpanFull(),

            Textarea::make('notes')
                ->label('ملاحظات')
                ->rows(3)
                ->columnSpanFull(),
        ]);
    }
}
