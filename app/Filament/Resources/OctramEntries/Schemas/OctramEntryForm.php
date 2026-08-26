<?php

namespace App\Filament\Resources\OctramEntries\Schemas;

use App\Models\Item;
use App\Models\Supplier;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class OctramEntryForm
{
    private static function calculateTotal(Set $set, Get $get): void
    {
        $quantity = max(
            0,
            (float) ($get('purchase_quantity') ?? 0)
        );

        $price = max(
            0,
            (float) ($get('purchase_price') ?? 0)
        );

        $base = round($quantity * $price, 2);

        $taxEnabled = (bool) (
            $get('purchase_tax_enabled') ?? false
        );

        $tax = $taxEnabled
            ? round($base * 0.14, 2)
            : 0.0;

        $set('purchase_tax', $tax);

        $set(
            'purchase_price_including_tax',
            round($base + $tax, 2)
        );
    }

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([

                Section::make('المشتريات')
                    ->description('بيانات سجل أوكترام')
                    ->schema([

                        Grid::make([
                            'default' => 1,
                            'md' => 2,
                            'xl' => 4,
                        ])
                            ->schema([

                                DatePicker::make('purchase_date')
                                    ->label('التاريخ')
                                    ->default(now())
                                    ->native(false)
                                    ->displayFormat('d/m/Y')
                                    ->format('Y-m-d'),

                                Select::make('purchase_item_id')
                                    ->label('الصنف')
                                    ->options(
                                        fn (): array => Item::query()
                                            ->where('active', true)
                                            ->orderBy('name')
                                            ->pluck('name', 'id')
                                            ->all()
                                    )
                                    ->searchable()
                                    ->preload(),

                                Select::make('supplier_id')
                                    ->label('المورد')
                                    ->options(
                                        fn (): array => Supplier::query()
                                            ->where('active', true)
                                            ->orderBy('name')
                                            ->pluck('name', 'id')
                                            ->all()
                                    )
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->afterStateUpdated(
                                        function ($state, Set $set): void {
                                            $supplier = Supplier::find($state);

                                            $set(
                                                'supplier_address',
                                                $supplier?->address
                                            );

                                            $set(
                                                'supplier_phone',
                                                $supplier?->mobile
                                                    ?: $supplier?->phone
                                            );
                                        }
                                    ),

                                TextInput::make('supplier_address')
                                    ->label('العنوان')
                                    ->maxLength(255),

                                TextInput::make('supplier_phone')
                                    ->label('رقم التليفون')
                                    ->maxLength(100),

                                TextInput::make('purchase_quantity')
                                    ->label('الكمية')
                                    ->numeric()
                                    ->minValue(0)
                                    ->default(1)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(
                                        fn ($state, Set $set, Get $get) =>
                                            self::calculateTotal($set, $get)
                                    ),

                                TextInput::make('purchase_price')
                                    ->label('سعر الوحدة')
                                    ->numeric()
                                    ->minValue(0)
                                    ->default(0)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(
                                        fn ($state, Set $set, Get $get) =>
                                            self::calculateTotal($set, $get)
                                    ),

                                Checkbox::make('purchase_tax_enabled')
                                    ->label('تطبيق ضريبة 14%')
                                    ->default(false)
                                    ->live()
                                    ->afterStateUpdated(
                                        fn ($state, Set $set, Get $get) =>
                                            self::calculateTotal($set, $get)
                                    ),

                                TextInput::make('purchase_tax')
                                    ->label('الضريبة 14%')
                                    ->numeric()
                                    ->default(0)
                                    ->readOnly(),

                                TextInput::make('purchase_price_including_tax')
                                    ->label('السعر شامل')
                                    ->numeric()
                                    ->default(0)
                                    ->readOnly(),

                            ]),
                    ]),

                Section::make('ملاحظات')
                    ->schema([
                        Textarea::make('notes')
                            ->label('ملاحظات')
                            ->rows(2),
                    ]),
            ]);
    }
}
