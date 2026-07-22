<?php

namespace App\Filament\Resources\Warehouses\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class WarehouseForm
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
                            ->label('اسم المخزن')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('manager')
                            ->label('مدير المخزن')
                            ->maxLength(255),

                        TextInput::make('phone')
                            ->label('رقم الهاتف')
                            ->tel()
                            ->maxLength(50),

                        TextInput::make('address')
                            ->label('العنوان')
                            ->columnSpanFull(),

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