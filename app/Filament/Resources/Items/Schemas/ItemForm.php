<?php

namespace App\Filament\Resources\Items\Schemas;

use App\Models\Item;
use App\Support\ItemNameNormalizer;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
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
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->rules([
                                function (?Item $record) {
                                    return function (string $attribute, mixed $value, \Closure $fail) use ($record): void {
                                        $normalized = ItemNameNormalizer::normalize($value);

                                        if ($normalized === '') {
                                            return;
                                        }

                                        $duplicate = Item::query()
                                            ->where('name_normalized', $normalized)
                                            ->when(
                                                $record,
                                                fn ($query) => $query->whereKeyNot($record->getKey()),
                                            )
                                            ->first(['id', 'code', 'name']);

                                        if ($duplicate) {
                                            $fail("هذا الصنف مسجل بالفعل بالكود {$duplicate->code}: {$duplicate->name}");
                                        }
                                    };
                                },
                            ])
                            ->afterStateUpdated(function (mixed $state, ?Item $record): void {
                                $normalized = ItemNameNormalizer::normalize($state);

                                if ($normalized === '') {
                                    return;
                                }

                                $duplicate = Item::query()
                                    ->where('name_normalized', $normalized)
                                    ->when(
                                        $record,
                                        fn ($query) => $query->whereKeyNot($record->getKey()),
                                    )
                                    ->first(['id', 'code', 'name']);

                                if ($duplicate) {
                                    Notification::make()
                                        ->title('الصنف مسجل بالفعل')
                                        ->body("الكود: {$duplicate->code} — {$duplicate->name}")
                                        ->warning()
                                        ->send();
                                }
                            }),

                        TextInput::make('name_en')
                            ->label('الاسم بالإنجليزية'),

                        Toggle::make('is_stock_item')
                            ->label('يؤثر على المخزون')
                            ->helperText('أوقف هذا الخيار للبنود التي لا تحتاج إلى حركة أو رصيد مخزني، مثل الصيانة والتركيب والأعمال.')
                            ->default(true)
                            ->live(),

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
                            ->preload()
                            ->required(fn (Get $get): bool => (bool) $get('is_stock_item')),

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
