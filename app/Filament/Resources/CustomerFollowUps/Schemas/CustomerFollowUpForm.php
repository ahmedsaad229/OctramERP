<?php

namespace App\Filament\Resources\CustomerFollowUps\Schemas;

use App\Models\Customer;
use App\Models\CustomerFollowUp;
use App\Models\User;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class CustomerFollowUpForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('بيانات المتابعة')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->schema([
                        Grid::make([
                            'default' => 1,
                            'md' => 2,
                            'xl' => 4,
                        ])->schema([
                            TextInput::make('follow_up_number')
                                ->label('رقم المتابعة')
                                ->readOnly()
                                ->dehydrated(false)
                                ->placeholder('سيتم إنشاؤه تلقائيًا'),

                            Select::make('customer_id')
                                ->label('العميل')
                                ->relationship('customer', 'name')
                                ->searchable()
                                ->preload()
                                ->required()
                                ->live()
                                ->afterStateUpdated(function (Set $set, mixed $state): void {
                                    $customer = Customer::find($state);

                                    $set(
                                        'contact_person',
                                        $customer?->contact_person
                                    );
                                }),

                            Select::make('sales_responsible_id')
                                ->label('مسؤول المبيعات')
                                ->options(fn (): array => User::query()
                                    ->where('is_active', true)
                                    ->orderBy('name')
                                    ->pluck('name', 'id')
                                    ->all())
                                ->default(fn (): ?int => auth()->id())
                                ->searchable()
                                ->preload()
                                ->required(),

                            Select::make('type')
                                ->label('نوع المتابعة')
                                ->options(CustomerFollowUp::typeOptions())
                                ->native(false)
                                ->required()
                                ->live(),

                            DateTimePicker::make('scheduled_at')
                                ->label('تاريخ ووقت المتابعة')
                                ->seconds(false)
                                ->native(false)
                                ->default(now())
                                ->required(),

                            Select::make('status')
                                ->label('الحالة')
                                ->options(CustomerFollowUp::statusOptions())
                                ->default(CustomerFollowUp::STATUS_SCHEDULED)
                                ->native(false)
                                ->required()
                                ->live(),

                            Select::make('priority')
                                ->label('الأولوية')
                                ->options(CustomerFollowUp::priorityOptions())
                                ->default('normal')
                                ->native(false)
                                ->required(),

                            TextInput::make('contact_person')
                                ->label('الشخص المسؤول لدى العميل')
                                ->maxLength(255),
                        ])->columnSpanFull(),
                    ]),

                Section::make('تفاصيل التواصل والفيدباك')
                    ->icon('heroicon-o-pencil-square')
                    ->schema([
                        TextInput::make('subject')
                            ->label('موضوع المتابعة')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Textarea::make('discussion')
                            ->label('ما تم مناقشته')
                            ->rows(4)
                            ->columnSpanFull(),

                        Textarea::make('customer_feedback')
                            ->label('رد العميل / الفيدباك')
                            ->rows(4)
                            ->required(
                                fn (Get $get): bool =>
                                    $get('status') === CustomerFollowUp::STATUS_COMPLETED
                            )
                            ->columnSpanFull(),

                        Select::make('result')
                            ->label('نتيجة المتابعة')
                            ->options(CustomerFollowUp::resultOptions())
                            ->native(false)
                            ->searchable(),

                        Textarea::make('notes')
                            ->label('ملاحظات داخلية')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make('بيانات الزيارة')
                    ->icon('heroicon-o-map-pin')
                    ->visible(
                        fn (Get $get): bool =>
                            $get('type') === CustomerFollowUp::TYPE_VISIT
                    )
                    ->schema([
                        TextInput::make('visit_location')
                            ->label('مكان أو عنوان الزيارة')
                            ->maxLength(255)
                            ->columnSpanFull(),
                    ]),

                Section::make('الإجراء والمتابعة القادمة')
                    ->icon('heroicon-o-calendar-days')
                    ->schema([
                        Textarea::make('next_action')
                            ->label('الإجراء المطلوب بعد المتابعة')
                            ->rows(3),

                        DateTimePicker::make('next_follow_up_at')
                            ->label('موعد المتابعة القادمة')
                            ->seconds(false)
                            ->native(false),

                        Placeholder::make('next_follow_up_hint')
                            ->label('')
                            ->content(
                                'حدد موعدًا جديدًا حتى لا يتم نسيان متابعة العميل.'
                            )
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }
}
