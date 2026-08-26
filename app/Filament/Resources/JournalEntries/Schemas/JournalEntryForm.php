<?php

namespace App\Filament\Resources\JournalEntries\Schemas;

use App\Models\Account;
use App\Models\JournalEntry;
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
use Filament\Schemas\Schema;

class JournalEntryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('بيانات القيد')
                ->schema([
                    Grid::make([
                        'default' => 1,
                        'md' => 2,
                        'xl' => 4,
                    ])->schema([
                        TextInput::make('document_number')
                            ->label('رقم القيد')
                            ->placeholder('يُنشأ تلقائيًا')
                            ->readOnly()
                            ->dehydrated(),

                        DatePicker::make('entry_date')
                            ->label('تاريخ القيد')
                            ->default(now())
                            ->required()
                            ->native(false),

                        Select::make('entry_type')
                            ->label('نوع القيد')
                            ->options(JournalEntry::typeOptions())
                            ->default(JournalEntry::TYPE_MANUAL)
                            ->disabled()
                            ->dehydrated(),

                        Placeholder::make('source_display')
                            ->label('المصدر')
                            ->content(fn (?JournalEntry $record): string => $record?->isAutomatic()
                                ? ($record->source_type ?: 'مستند أوتوماتيكي')
                                : 'قيد يدوي'),
                    ]),

                    Textarea::make('description')
                        ->label('البيان العام')
                        ->required()
                        ->rows(2)
                        ->columnSpanFull(),
                ]),

            Section::make('أطراف القيد')
                ->description('يجب أن يتساوى إجمالي المدين مع إجمالي الدائن.')
                ->schema([
                    Repeater::make('lines')
                        ->label('سطور القيد')
                        ->relationship('lines')
                        ->minItems(2)
                        ->defaultItems(2)
                        ->reorderable(false)
                        ->addActionLabel('إضافة طرف')
                        ->schema([
                            Grid::make([
                                'default' => 1,
                                'md' => 12,
                            ])->schema([
                                Hidden::make('id'),

                                Select::make('account_id')
                                    ->label('الحساب')
                                    ->options(fn (): array => Account::query()
                                        ->posting()
                                        ->orderBy('code')
                                        ->get()
                                        ->mapWithKeys(fn (Account $account): array => [
                                            $account->getKey() => $account->displayName(),
                                        ])
                                        ->all())
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->columnSpan(['md' => 5]),

                                TextInput::make('debit')
                                    ->label('مدين')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0)
                                    ->live(onBlur: true)
                                    ->columnSpan(['md' => 2]),

                                TextInput::make('credit')
                                    ->label('دائن')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0)
                                    ->live(onBlur: true)
                                    ->columnSpan(['md' => 2]),

                                TextInput::make('memo')
                                    ->label('البيان')
                                    ->maxLength(255)
                                    ->columnSpan(['md' => 3]),
                            ]),
                        ])
                        ->columnSpanFull(),

                    Grid::make(3)->schema([
                        Placeholder::make('total_debit_display')
                            ->label('إجمالي المدين')
                            ->content(fn (Get $get): string => number_format(
                                collect($get('lines') ?? [])->sum(fn ($line) => (float) ($line['debit'] ?? 0)),
                                2
                            ).' ج.م'),

                        Placeholder::make('total_credit_display')
                            ->label('إجمالي الدائن')
                            ->content(fn (Get $get): string => number_format(
                                collect($get('lines') ?? [])->sum(fn ($line) => (float) ($line['credit'] ?? 0)),
                                2
                            ).' ج.م'),

                        Placeholder::make('balance_status_display')
                            ->label('حالة القيد')
                            ->content(function (Get $get): string {
                                $lines = collect($get('lines') ?? []);
                                $debit = (float) $lines->sum(fn ($line) => (float) ($line['debit'] ?? 0));
                                $credit = (float) $lines->sum(fn ($line) => (float) ($line['credit'] ?? 0));

                                return abs($debit - $credit) < 0.01 && $debit > 0
                                    ? 'متزن'
                                    : 'غير متزن';
                            }),
                    ]),
                ]),
        ]);
    }
}
