<?php

namespace App\Filament\Resources\PurchaseRequests\Schemas;

use App\Models\Item;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestItem;
use App\Services\Inventory\InventoryService;
use App\Support\DocumentFieldPresentation;
use App\Support\QuantityFormatter;
use Filament\Forms\Components\DatePicker;
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
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class PurchaseRequestForm
{
    public const OTHER_DEPARTMENT = 'أخرى';

    /**
     * @return array<string, string>
     */
    public static function departmentOptions(): array
    {
        return collect([
            'الإدارة العليا',
            'إدارة المشتريات',
            'إدارة المخازن',
            'إدارة المشروعات',
            'الإدارة المالية والحسابات',
            'إدارة الموارد البشرية',
            'إدارة المبيعات',
            'الإدارة الفنية',
            'الموقع',
            self::OTHER_DEPARTMENT,
        ])->mapWithKeys(fn (string $department): array => [$department => $department])->all();
    }

    public static function configureDatePicker(DatePicker $datePicker): DatePicker
    {
        return $datePicker
            ->native(false)
            ->locale('ar')
            ->displayFormat('d/m/Y')
            ->format('Y-m-d')
            ->closeOnDateSelection()
            ->firstDayOfWeek(6)
            ->placeholder('يوم / شهر / سنة');
    }

    /**
     * @return array{unit_id: ?int, unit_name: ?string}
     */
    public static function itemDefaults(mixed $itemId): array
    {
        $item = Item::query()->with('unit:id,name')->find($itemId);

        return [
            'unit_id' => $item?->unit_id,
            'unit_name' => $item?->unit?->name,
        ];
    }

    /**
     * @return array{department_choice: ?string, department_custom: ?string}
     */
    public static function departmentFormState(?string $department): array
    {
        $isPredefined = $department !== null && array_key_exists($department, self::departmentOptions());

        return [
            'department_choice' => $department && ! $isPredefined ? self::OTHER_DEPARTMENT : $department,
            'department_custom' => $isPredefined ? null : $department,
        ];
    }

    public static function configure(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Section::make('بيانات طلب الشراء')
                ->schema([
                    Grid::make(['default' => 1, 'md' => 2, 'xl' => 4])->schema([
                        TextInput::make('code')
                            ->label('رقم طلب الشراء')
                            ->placeholder('سيتم إنشاؤه تلقائيًا')
                            ->disabled()
                            ->dehydrated(false),
                        self::configureDatePicker(
                            DatePicker::make('request_date')->label('تاريخ الطلب'),
                        )
                            ->default(now())
                            ->required(),
                        self::configureDatePicker(
                            DatePicker::make('required_date')->label('تاريخ الاحتياج'),
                        )
                            ->minDate(fn (Get $get) => $get('request_date')),
                        Select::make('warehouse_id')
                            ->label('المخزن')
                            ->relationship('warehouse', 'name')
                            ->searchable()
                            ->preload()
                            ->live(),
                        Hidden::make('requested_by')
                            ->default(fn (): ?int => auth()->id())
                            ->dehydrated(),
                        TextInput::make('requester_name')
                            ->label('طالب الشراء')
                            ->default(fn (): ?string => auth()->user()?->name)
                            ->disabled()
                            ->dehydrated(false),
                        Hidden::make('department'),
                        Select::make('department_choice')
                            ->label('الإدارة / القسم')
                            ->options(self::departmentOptions())
                            ->searchable()
                            ->native(false)
                            ->preload()
                            ->live()
                            ->dehydrated(false)
                            ->afterStateUpdated(function (Set $set, Get $get, mixed $state): void {
                                $set(
                                    'department',
                                    $state === self::OTHER_DEPARTMENT
                                        ? ($get('department_custom') ?: self::OTHER_DEPARTMENT)
                                        : $state,
                                );
                            }),
                        TextInput::make('department_custom')
                            ->label('اسم الإدارة / القسم')
                            ->maxLength(255)
                            ->visible(fn (Get $get): bool => $get('department_choice') === self::OTHER_DEPARTMENT)
                            ->live()
                            ->dehydrated(false)
                            ->afterStateUpdated(fn (Set $set, mixed $state) => $set(
                                'department',
                                filled($state) ? $state : self::OTHER_DEPARTMENT,
                            )),
                        Textarea::make('purpose')
                            ->label('الغرض من الطلب')
                            ->columnSpan(['md' => 2]),
                    ])->columnSpanFull(),
                ]),
            Section::make('الأصناف المطلوبة')
                ->schema([
                    Repeater::make('items')
                        ->label('الأصناف المطلوبة')
                        ->schema([
                            Grid::make(['default' => 1, 'md' => 2, 'xl' => 12])->schema([
                                Hidden::make('id')
                                    ->dehydrated(false),
                                Select::make('item_id')
                                    ->label('الصنف')
                                    ->options(fn (): array => Item::query()->where('active', true)->orderBy('name')->pluck('name', 'id')->all())
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->validationMessages([
                                        'required' => 'يجب اختيار الصنف.',
                                    ])
                                    ->distinct()
                                    ->live()
                                    ->afterStateUpdated(function (Set $set, mixed $state): void {
                                        $defaults = self::itemDefaults($state);
                                        $set('unit_id', $defaults['unit_id']);
                                        $set('unit_name', $defaults['unit_name']);
                                    })
                                    ->columnSpan(['md' => 2, 'xl' => 4]),
                                Placeholder::make('item_code')
                                    ->label('كود الصنف')
                                    ->content(fn (Get $get): string => Item::query()->find($get('item_id'))?->code ?? '—')
                                    ->extraAttributes(DocumentFieldPresentation::itemCode())
                                    ->extraEntryWrapperAttributes(DocumentFieldPresentation::wrapper())
                                    ->alignCenter()
                                    ->columnSpan(['xl' => 1]),
                                Hidden::make('unit_id')
                                    ->dehydrated()
                                    ->required()
                                    ->validationMessages([
                                        'required' => 'يجب تحديد وحدة الصنف.',
                                    ]),
                                Hidden::make('unit_name')->dehydrated(false),
                                Placeholder::make('unit_name_display')
                                    ->label('الوحدة')
                                    ->content(fn (Get $get): string => $get('unit_name') ?: '—')
                                    ->extraAttributes(DocumentFieldPresentation::unit())
                                    ->extraEntryWrapperAttributes(DocumentFieldPresentation::wrapper())
                                    ->alignCenter()
                                    ->columnSpan(['xl' => 1]),
                                Placeholder::make('unit_validation')
                                    ->label('')
                                    ->content(new HtmlString('<span class="text-sm text-danger-600 dark:text-danger-400">الصنف المختار لا توجد له وحدة افتراضية. يرجى تسجيل الوحدة في بطاقة الصنف أولًا.</span>'))
                                    ->visible(fn (Get $get): bool => filled($get('item_id')) && blank($get('unit_id')))
                                    ->columnSpan(['md' => 2, 'xl' => 12]),
                                TextInput::make('requested_quantity')
                                    ->label('الكمية المطلوبة')
                                    ->type('text')
                                    ->formatStateUsing(fn (mixed $state): ?string => QuantityFormatter::normalizeForInput($state))
                                    ->mutateStateForValidationUsing(fn (mixed $state): mixed => QuantityFormatter::normalizeForInput($state) ?? $state)
                                    ->dehydrateStateUsing(fn (mixed $state): mixed => QuantityFormatter::normalizeForInput($state) ?? $state)
                                    ->inputMode('decimal')
                                    ->extraInputAttributes(QuantityFormatter::inputAttributes())
                                    ->rules(['numeric', 'gt:0'])
                                    ->afterStateUpdated(fn (TextInput $component, mixed $state) => $component->state(
                                        QuantityFormatter::normalizeForInput($state) ?? $state,
                                    ))
                                    ->required()
                                    ->validationMessages([
                                        'required' => 'يجب إدخال الكمية المطلوبة.',
                                        'gt' => 'يجب أن تكون الكمية المطلوبة أكبر من صفر.',
                                    ])
                                    ->live()
                                    ->columnSpan(['xl' => 2]),
                                Placeholder::make('warehouse_balance')
                                    ->label('الرصيد بالمخزن')
                                    ->content(fn (Get $get): string => QuantityFormatter::formatForDisplay(app(InventoryService::class)->warehouseBalance(
                                        $get('../../warehouse_id'),
                                        $get('item_id'),
                                    )))
                                    ->extraAttributes(DocumentFieldPresentation::stock())
                                    ->extraEntryWrapperAttributes(DocumentFieldPresentation::wrapper())
                                    ->alignCenter()
                                    ->columnSpan(['xl' => 2]),
                                Placeholder::make('total_balance')
                                    ->label('إجمالي الرصيد بكل المخازن')
                                    ->content(fn (Get $get): string => QuantityFormatter::formatForDisplay(
                                        app(InventoryService::class)->totalBalance($get('item_id')),
                                    ))
                                    ->extraAttributes(DocumentFieldPresentation::stock())
                                    ->extraEntryWrapperAttributes(DocumentFieldPresentation::wrapper())
                                    ->alignCenter()
                                    ->columnSpan(['xl' => 2]),
                                Placeholder::make('stock_warning')
                                    ->label('تنبيه الرصيد')
                                    ->content(new HtmlString('<span class="text-sm text-warning-600 dark:text-warning-400">الكمية المطلوبة أكبر من الرصيد المتاح بالمخزن.</span>'))
                                    ->visible(fn (Get $get): bool => filled($get('item_id'))
                                        && filled($get('../../warehouse_id'))
                                        && (float) $get('requested_quantity') > app(InventoryService::class)->warehouseBalance(
                                            $get('../../warehouse_id'),
                                            $get('item_id'),
                                        ))
                                    ->extraAttributes(['style' => 'overflow-wrap: anywhere;'])
                                    ->columnSpan(['md' => 2, 'xl' => 3]),
                                Hidden::make('purchase_followup_html')
                                    ->dehydrated(false),

                                Placeholder::make('purchase_followup')
                                    ->label('متابعة الشراء')
                                    ->content(
                                        fn (Get $get): HtmlString =>
                                            new HtmlString(
                                                (string) ($get('purchase_followup_html') ?: '—')
                                            )
                                    )
                                    ->visible(
                                        fn (Get $get): bool =>
                                            filled($get('purchase_followup_html'))
                                    )
                                    ->columnSpan([
                                        'md' => 2,
                                        'xl' => 12,
                                    ]),
                                TextInput::make('notes')
                                    ->label('ملاحظات')
                                    ->columnSpan(fn (Get $get): array => [
                                        'md' => 2,
                                        'xl' => filled($get('item_id'))
                                            && filled($get('../../warehouse_id'))
                                            && (float) $get('requested_quantity') > app(InventoryService::class)->warehouseBalance(
                                                $get('../../warehouse_id'),
                                                $get('item_id'),
                                            )
                                                ? 9
                                                : 12,
                                    ]),
                            ])->columnSpanFull(),
                        ])
                        ->defaultItems(1)
                        ->minItems(1)
                        ->validationMessages([
                            'min' => 'يجب إضافة صنف واحد على الأقل.',
                        ])
                        ->addActionLabel('إضافة صنف')
                        ->reorderable()
                        ->columnSpanFull(),
                ]),
            Section::make('البيان والملاحظات')
                ->schema([
                    Textarea::make('notes')->label('ملاحظات')->rows(3),
                ]),
        ]);
    }
}
