<?php

namespace App\Filament\Resources\Customers\Schemas;

use App\Models\Customer;
use App\Services\CustomerPurchaseOrderMonitoringService;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;

class CustomerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)
                    ->schema([

                        TextInput::make('code')
                            ->label('كود العميل')
                            ->readOnly()
                            ->dehydrated(),

                        TextInput::make('name')
                            ->label('اسم العميل')
                            ->required(),

                        TextInput::make('contact_person')
                            ->label('اسم المسؤول لدى العميل')
                            ->maxLength(255),

                        TextInput::make('contact_job_title')
                            ->label('المسمى الوظيفي للمسؤول')
                            ->maxLength(255),

                        TextInput::make('contact_mobile')
                            ->label('موبايل المسؤول')
                            ->tel()
                            ->maxLength(50),

                        TextInput::make('contact_email')
                            ->label('البريد الإلكتروني للمسؤول')
                            ->email()
                            ->maxLength(255),

                        TextInput::make('mobile')
                            ->label('الموبايل')
                            ->tel(),

                        TextInput::make('phone')
                            ->label('الهاتف')
                            ->tel(),

                        TextInput::make('email')
                            ->label('البريد الإلكتروني')
                            ->email(),

                        TextInput::make('tax_number')
                            ->label('الرقم الضريبي'),

                        TextInput::make('commercial_register')
                            ->label('السجل التجاري'),

                        TextInput::make('country')
                            ->label('الدولة')
                            ->default('Egypt'),

                        TextInput::make('governorate')
                            ->label('المحافظة'),

                        TextInput::make('city')
                            ->label('المدينة'),

                        TextInput::make('opening_balance')
                            ->label('الرصيد الافتتاحي')
                            ->numeric()
                            ->default(0),

                        TextInput::make('credit_limit')
                            ->label('حد الائتمان')
                            ->numeric()
                            ->default(0),

                        Toggle::make('active')
                            ->label('نشط')
                            ->default(true),
                    ]),

                Textarea::make('address')
                    ->label('العنوان')
                    ->rows(3)
                    ->columnSpanFull(),

                Textarea::make('notes')
                    ->label('ملاحظات')
                    ->rows(3)
                    ->columnSpanFull(),
                Section::make('أوامر التوريد')
                    ->description('اضغط لعرض أوامر التوريد وحركات العميل')
                    ->schema([
                        View::make('filament.resources.customers.purchase-orders')
                            ->viewData(fn (?Customer $record): array => [
                                'summary' => $record
                                    ? app(CustomerPurchaseOrderMonitoringService::class)->customerSummary($record->getKey())
                                    : null,
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed()
                    ->visible(fn (?Customer $record): bool => filled($record))
                    ->columnSpanFull(),
            ]);
    }
}
