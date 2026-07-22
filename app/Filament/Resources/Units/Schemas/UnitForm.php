<?php

namespace App\Filament\Resources\Units\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class UnitForm
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
                            ->label('اسم الوحدة')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('short_name')
                            ->label('الاختصار')
                            ->required()
                            ->maxLength(20),

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