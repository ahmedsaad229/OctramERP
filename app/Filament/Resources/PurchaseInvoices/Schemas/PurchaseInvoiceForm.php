<?php

namespace App\Filament\Resources\PurchaseInvoices\Schemas;

use App\Enums\PaymentType;
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

class PurchaseInvoiceForm
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
                        TextInput::make('code')
                            ->label('رقم المستند')
                            ->placeholder('سيتم إنشاؤه تلقائياً')
                            ->disabled()
                            ->dehydrated(false),

                        DatePicker::make('invoice_date')
                            ->label('التاريخ')
                            ->required()
                            ->default(now()),

                        TextInput::make('invoice_number')
                            ->label('رقم الفاتورة')
                            ->required()
                            ->maxLength(255),

                        Select::make('payment_type')
                            ->label('نوع التعامل')
                            ->options(PaymentType::options())
                            ->default(PaymentType::Cash->value)
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (Set $set, mixed $state): void {
                                if ($state === PaymentType::Cash->value) {
                                    $set('due_date', null);
                                }
                            }),

                        DatePicker::make('due_date')
                            ->label('تاريخ الاستحقاق')
                            ->visible(fn (Get $get): bool => $get('payment_type') === PaymentType::Credit->value)
                            ->required(fn (Get $get): bool => $get('payment_type') === PaymentType::Credit->value)
                            ->minDate(fn (Get $get): mixed => $get('invoice_date')),

                        Select::make('supplier_id')
                            ->label('المورد')
                            ->relationship('supplier', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Select::make('warehouse_id')
                            ->label('المخزن')
                            ->relationship('warehouse', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),

                    ])->columnSpanFull(),
                ])
                ->columns(1)
                ->columnSpanFull(),

            Section::make('أصناف الفاتورة')
                ->schema([
                    Repeater::make('items')
                        ->relationship()
                        ->label('أصناف الفاتورة')
                        ->schema([
                            Grid::make([
                                'default' => 1,
                                'md' => 6,
                                'xl' => 13,
                            ])->schema([
                                Select::make('item_id')
                                    ->label('الصنف')
                                    ->relationship('item', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->columnSpan([
                                        'default' => 1,
                                        'md' => 6,
                                        'xl' => 5,
                                    ]),

                                TextInput::make('quantity')
                                    ->label('الكمية')
                                    ->numeric()
                                    ->default(1)
                                    ->required()
                                    ->live()
                                    ->columnSpan([
                                        'default' => 1,
                                        'md' => 3,
                                        'xl' => 3,
                                    ]),

                                TextInput::make('unit_cost')
                                    ->label('تكلفة الوحدة')
                                    ->numeric()
                                    ->default(0)
                                    ->required()
                                    ->live()
                                    ->columnSpan([
                                        'default' => 1,
                                        'md' => 3,
                                        'xl' => 3,
                                    ]),

                                Placeholder::make('total')
                                    ->label('الإجمالي')
                                    ->content(fn (Get $get): string => number_format(
                                        (float) $get('quantity') * (float) $get('unit_cost'),
                                        2,
                                    ))
                                    ->columnSpan([
                                        'default' => 1,
                                        'md' => 3,
                                        'xl' => 2,
                                    ]),
                            ])->columnSpanFull(),

                            Textarea::make('notes')
                                ->label('ملاحظات')
                                ->columnSpanFull(),
                        ])
                        ->defaultItems(1)
                        ->addActionLabel('إضافة صنف')
                        ->collapsible()
                        ->cloneable()
                        ->columnSpanFull(),

                    Placeholder::make('invoice_total')
                        ->label('إجمالي الفاتورة')
                        ->content(fn (Get $get): string => number_format(
                            collect($get('items') ?? [])->sum(
                                fn (array $item): float => (float) ($item['quantity'] ?? 0)
                                    * (float) ($item['unit_cost'] ?? 0),
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
