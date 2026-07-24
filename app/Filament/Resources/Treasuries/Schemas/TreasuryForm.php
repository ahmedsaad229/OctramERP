<?php

namespace App\Filament\Resources\Treasuries\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class TreasuryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(2)->schema([
                TextInput::make('code')->label('الكود')->readOnly()->dehydrated(),
                TextInput::make('name')->label('اسم الخزينة')->required()->maxLength(255),
                TextInput::make('opening_balance')->label('الرصيد الافتتاحي')->numeric()->default(0)->required(),
                Toggle::make('is_default')
                    ->label('الخزينة الافتراضية')
                    ->dehydrateStateUsing(fn ($state): ?bool => $state ? true : null),
                Toggle::make('is_active')->label('نشطة')->default(true),
            ]),
            Textarea::make('notes')->label('ملاحظات')->rows(3)->columnSpanFull(),
        ]);
    }
}
