<?php

namespace App\Filament\Resources\CashAdvances\Schemas;

use App\Models\CashAdvance;
use App\Models\CashAdvanceSettlement;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CashAdvanceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([

                Section::make('بيانات العهدة')
                    ->schema([

                        Grid::make([
                            'default' => 1,
                            'md' => 2,
                            'xl' => 4,
                        ])
                        ->schema([

                            TextInput::make('document_number')
                                ->label('رقم العهدة')
                                ->placeholder('تلقائي')
                                ->disabled()
                                ->dehydrated(false),

                            DatePicker::make('advance_date')
                                ->label('تاريخ العهدة')
                                ->default(now())
                                ->required()
                                ->native(false),

                            TextInput::make('recipient_name')
                                ->label('مستلم العهدة')
                                ->required(),

                            TextInput::make('amount')
                                ->label('مبلغ العهدة')
                                ->numeric()
                                ->minValue(0.01)
                                ->required(),

                            TextInput::make('purpose')
                                ->label('الغرض من العهدة')
                                ->columnSpan(2),

                            DatePicker::make('due_date')
                                ->label('تاريخ التسوية المتوقع')
                                ->native(false),

                            TextInput::make('status')
                                ->label('الحالة')
                                ->formatStateUsing(
                                    fn ($state): string =>
                                        CashAdvance::statusOptions()[$state]
                                        ?? 'مفتوحة'
                                )
                                ->disabled()
                                ->dehydrated(false)
                                ->visible(fn ($record): bool => filled($record)),
                        ]),

                        Textarea::make('notes')
                            ->label('ملاحظات')
                            ->rows(2),
                    ]),

                Section::make('حركات التسوية')
                    ->description('أدخل المصروفات أو المبالغ المرتجعة بعد صرف العهدة. ويمكن التسوية على أكثر من مرة.')
                    ->visible(fn ($record): bool => filled($record))
                    ->schema([

                        Repeater::make('settlements')
                            ->relationship()
                            ->label('')
                            ->addActionLabel('إضافة حركة تسوية')
                            ->reorderable(false)
                            ->schema([

                                Grid::make([
                                    'default' => 1,
                                    'md' => 12,
                                    'xl' => 12,
                                ])
                                ->schema([

                                    DatePicker::make('settlement_date')
                                        ->label('التاريخ')
                                        ->default(now())
                                        ->required()
                                        ->native(false)
                                        ->columnSpan(2),


                                    Select::make('type')
                                        ->label('نوع الحركة')
                                        ->options(
                                            CashAdvanceSettlement::typeOptions()
                                        )
                                        ->required()
                                        ->native(false)
                                        ->columnSpan(2),

                                    TextInput::make('description')
                                        ->label('البيان')
                                        ->columnSpan(4),

                                    TextInput::make('document_number')
                                        ->label('رقم المستند / الفاتورة')
                                        ->columnSpan(2),

                                    TextInput::make('amount')
                                        ->label('المبلغ')
                                        ->numeric()
                                        ->minValue(0.01)
                                        ->required()
                                        ->columnSpan(2),
                                ]),

                                Textarea::make('notes')
                                    ->label('ملاحظات الحركة')
                                    ->rows(1),
                            ]),
                    ]),

                Section::make('ملخص العهدة')
                    ->visible(fn ($record): bool => filled($record))
                    ->schema([

                        Grid::make([
                            'default' => 1,
                            'md' => 2,
                            'xl' => 4,
                        ])
                        ->schema([

                            TextInput::make('amount')
                                ->label('مبلغ العهدة')
                                ->disabled()
                                ->dehydrated(false),

                            TextInput::make('total_spent')
                                ->label('إجمالي المصروف')
                                ->disabled()
                                ->dehydrated(false),

                            TextInput::make('total_returned')
                                ->label('إجمالي المرتجع')
                                ->disabled()
                                ->dehydrated(false),

                            TextInput::make('remaining_amount')
                                ->label('المتبقي')
                                ->disabled()
                                ->dehydrated(false),
                        ]),
                    ]),
            ]);
    }
}
