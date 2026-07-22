<?php

namespace App\Filament\Resources\Suppliers\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class SupplierForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)
                    ->schema([

                        TextInput::make('code')
                            ->label('كود المورد')
                            ->readOnly()
                            ->dehydrated(),

                        TextInput::make('name')
                            ->label('اسم المورد')
                            ->required(),

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
            ]);
    }
}