<?php

namespace App\Filament\Resources\CustomerPurchaseOrders\Schemas;

use App\Models\CustomerPurchaseOrder;
use App\Models\CustomerPurchaseOrderFollowUp;
use App\Models\Item;
use App\Models\SalesQuotation;
use App\Services\CustomerPurchaseOrderService;
use App\Support\QuantityFormatter;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;

class CustomerPurchaseOrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('بيانات أمر التوريد')
                    ->schema([
                        Grid::make([
                            'default' => 1,
                            'md' => 2,
                            'xl' => 4,
                        ])
                            ->schema([
                                TextInput::make('document_number')
                                    ->label('رقم المستند')
                                    ->readOnly()
                                    ->dehydrated(false)
                                    ->placeholder('سيتم إنشاؤه تلقائيًا'),

                                TextInput::make('customer_order_number')
                                    ->label('رقم أمر التوريد لدى العميل')
                                    ->maxLength(255),

                                Select::make('customer_id')
                                    ->label('العميل')
                                    ->relationship('customer', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function (Set $set): void {
                                        $set('sales_quotation_id', null);

                                        $set('items', [
                                            [
                                                'item_id' => null,
                                                'unit_id' => null,
                                                'unit_name' => null,
                                                'ordered_quantity' => null,
                                                'executed_quantity' => 0,
                                                'remaining_quantity' => 0,
                                                'unit_price' => null,
                                                'description' => null,
                                                'notes' => null,
                                            ],
                                        ]);
                                    }),

                                Select::make('sales_quotation_id')
                                    ->label('عرض السعر')
                                    ->placeholder('اختر عرض السعر')
                                    ->searchable()
                                    ->live()
                                    ->disabled(
                                        fn (Get $get): bool => blank($get('customer_id'))
                                    )
                                    ->options(function (Get $get): array {
                                        $customerId = $get('customer_id');

                                        if (blank($customerId)) {
                                            return [];
                                        }

                                        return SalesQuotation::query()
                                            ->where('customer_id', $customerId)
                                            ->orderByDesc('quotation_date')
                                            ->orderByDesc('id')
                                            ->get()
                                            ->mapWithKeys(function (SalesQuotation $quotation): array {
                                                $date = $quotation->quotation_date
                                                    ? $quotation->quotation_date->format('d/m/Y')
                                                    : 'بدون تاريخ';

                                                $total = number_format(
                                                    (float) $quotation->total_amount,
                                                    2
                                                );

                                                return [
                                                    $quotation->getKey() =>
                                                        "{$quotation->quotation_number} - {$date} - {$total} ج.م",
                                                ];
                                            })
                                            ->all();
                                    })
                                    ->afterStateUpdated(function (
                                        Get $get,
                                        Set $set,
                                        $state
                                    ): void {
                                        if (blank($state)) {
                                            return;
                                        }

                                        $customerId = $get('customer_id');

                                        $quotation = SalesQuotation::query()
                                            ->with([
                                                'items.item',
                                                'items.unit',
                                            ])
                                            ->whereKey($state)
                                            ->where('customer_id', $customerId)
                                            ->first();

                                        if (! $quotation) {
                                            $set('sales_quotation_id', null);

                                            return;
                                        }

                                        $items = $quotation->items
                                            ->values()
                                            ->map(function ($quotationItem, int $index): array {
                                                $quantity = (float) $quotationItem->quantity;

                                                return [
                                                    'item_id' => $quotationItem->item_id,
                                                    'unit_id' => $quotationItem->unit_id,
                                                    'unit_name' => $quotationItem->unit?->name,
                                                    'ordered_quantity' => $quantity,
                                                    'executed_quantity' => 0,
                                                    'remaining_quantity' => $quantity,
                                                    'unit_price' => (float) $quotationItem->unit_price,
                                                    'discount_amount' => (float) $quotationItem->discount_amount,
                                                    'tax_exempt' => (bool) $quotationItem->tax_exempt,
                                                    'tax_rate' => (bool) $quotationItem->tax_exempt
                                                        ? 0
                                                        : (
                                                            ((float) $quotationItem->quantity * (float) $quotationItem->unit_price - (float) $quotationItem->discount_amount) > 0
                                                                ? round(
                                                                    ((float) $quotationItem->tax_amount
                                                                        / ((float) $quotationItem->quantity * (float) $quotationItem->unit_price - (float) $quotationItem->discount_amount)
                                                                    ) * 100,
                                                                    4
                                                                )
                                                                : 0
                                                        ),
                                                    'description' => null,
                                                    'notes' => $quotationItem->notes,
                                                    'sort_order' => $index,
                                                ];
                                            })
                                            ->all();

                                        $set('items', $items);
                                    }),

                                DatePicker::make('order_date')
                                    ->label('تاريخ الأمر')
                                    ->default(now())
                                    ->native(false)
                                    ->required(),

                                DatePicker::make('received_date')
                                    ->label('تاريخ الاستلام')
                                    ->native(false),

                                DatePicker::make('required_delivery_date')
                                    ->label('تاريخ التسليم المطلوب')
                                    ->native(false)
                                    ->afterOrEqual('order_date'),

                                TextInput::make('project_name')
                                    ->label('المشروع')
                                    ->maxLength(255),

                                TextInput::make('delivery_location')
                                    ->label('مكان التسليم')
                                    ->maxLength(255),

                                TextInput::make('contact_person')
                                    ->label('مسؤول التواصل')
                                    ->maxLength(255),

                                Select::make('status')
                                    ->label('الحالة')
                                    ->options(CustomerPurchaseOrder::statusOptions())
                                    ->default(CustomerPurchaseOrder::STATUS_NEW)
                                    ->required(),

                                Placeholder::make('execution_percentage_display')
                                    ->label('نسبة التنفيذ')
                                    ->content(
                                        fn (Get $get): string => number_format(
                                            (float) $get('execution_percentage'),
                                            2
                                        ).'%'
                                    ),

                                Hidden::make('execution_percentage')
                                    ->default(0),
                            ]),
                    ]),

                Section::make('أصناف أمر التوريد')
                    ->schema([
                        Repeater::make('items')
                            ->schema([
                                Hidden::make('id'),
                                Hidden::make('discount_amount')->default(0),
                                Hidden::make('tax_exempt')->default(false),
                                Hidden::make('tax_rate')->default(0),

                                Grid::make([
                                    'default' => 1,
                                    'md' => 6,
                                    'xl' => 12,
                                ])
                                    ->schema([
                                        Select::make('item_id')
                                            ->label('الصنف')
                                            ->options(
                                                Item::query()
                                                    ->where('active', true)
                                                    ->pluck('name', 'id')
                                            )
                                            ->searchable()
                                            ->required()
                                            ->live()
                                            ->afterStateUpdated(function (
                                                Set $set,
                                                $state
                                            ): void {
                                                $item = Item::query()
                                                    ->with('unit')
                                                    ->find($state);

                                                $set('unit_id', $item?->unit_id);
                                                $set('unit_name', $item?->unit?->name);
                                            })
                                            ->columnSpan([
                                                'xl' => 3,
                                            ]),

                                        Hidden::make('unit_id'),

                                        TextInput::make('unit_name')
                                            ->label('الوحدة')
                                            ->readOnly()
                                            ->dehydrated(false)
                                            ->columnSpan([
                                                'xl' => 1,
                                            ]),

                                        TextInput::make('ordered_quantity')
                                            ->label('الكمية المطلوبة')
                                            ->type('text')
                                            ->inputMode('decimal')
                                            ->extraInputAttributes(
                                                QuantityFormatter::inputAttributes()
                                            )
                                            ->rules([
                                                'numeric',
                                                'gt:0',
                                            ])
                                            ->required()
                                            ->columnSpan([
                                                'xl' => 2,
                                            ]),

                                        TextInput::make('executed_quantity')
                                            ->label('الكمية المنفذة')
                                            ->readOnly()
                                            ->dehydrated(false)
                                            ->columnSpan([
                                                'xl' => 2,
                                            ]),

                                        TextInput::make('remaining_quantity')
                                            ->label('الكمية المتبقية')
                                            ->readOnly()
                                            ->dehydrated(false)
                                            ->columnSpan([
                                                'xl' => 2,
                                            ]),

                                        TextInput::make('unit_price')
                                            ->label('سعر الوحدة')
                                            ->type('text')
                                            ->inputMode('decimal')
                                            ->extraInputAttributes(
                                                QuantityFormatter::inputAttributes()
                                            )
                                            ->rules([
                                                'nullable',
                                                'numeric',
                                                'gte:0',
                                            ])
                                            ->columnSpan([
                                                'xl' => 2,
                                            ]),

                                        Textarea::make('description')
                                            ->label('الوصف')
                                            ->columnSpan([
                                                'xl' => 6,
                                            ]),

                                        Textarea::make('notes')
                                            ->label('ملاحظات')
                                            ->columnSpan([
                                                'xl' => 6,
                                            ]),
                                    ]),
                            ])
                            ->defaultItems(1)
                            ->minItems(1)
                            ->reorderable(false)
                            ->columnSpanFull(),
                    ]),

                Section::make('سجل المتابعة')
                    ->schema([
                        Repeater::make('followUps')
                            ->schema([
                                DatePicker::make('follow_up_date')
                                    ->label('التاريخ')
                                    ->default(now())
                                    ->required()
                                    ->native(false),

                                Select::make('event_type')
                                    ->label('نوع المتابعة')
                                    ->options(
                                        CustomerPurchaseOrderFollowUp::eventOptions()
                                    )
                                    ->required(),

                                Textarea::make('note')
                                    ->label('الملاحظة')
                                    ->required(),
                            ])
                            ->columns(3)
                            ->defaultItems(0),
                    ]),

                Section::make('المستندات المرتبطة')
                    ->schema([
                        View::make(
                            'filament.resources.customer-purchase-orders.linked-documents'
                        )
                            ->viewData(
                                fn (?CustomerPurchaseOrder $record): array => [
                                    'documents' => $record
                                        ? app(CustomerPurchaseOrderService::class)
                                            ->linkedDocuments($record)
                                        : [],
                                ]
                            ),
                    ])
                    ->visible(
                        fn (?CustomerPurchaseOrder $record): bool => filled($record)
                    ),

                Section::make('المرفقات')
                    ->schema([
                        Repeater::make('attachments')
                            ->schema([
                                FileUpload::make('file_path')
                                    ->label('الملف')
                                    ->disk('public')
                                    ->directory('customer-purchase-orders')
                                    ->acceptedFileTypes([
                                        'application/pdf',
                                        'image/jpeg',
                                        'image/png',
                                    ])
                                    ->maxSize(5120)
                                    ->storeFileNamesIn('original_name')
                                    ->downloadable()
                                    ->openable()
                                    ->required(),

                                Hidden::make('original_name'),
                            ])
                            ->defaultItems(0)
                            ->addActionLabel('إضافة مرفق'),
                    ]),

                Textarea::make('notes')
                    ->label('الملاحظات')
                    ->rows(3),
            ]);
    }
}