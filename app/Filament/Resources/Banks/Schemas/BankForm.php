<?php
namespace App\Filament\Resources\Banks\Schemas;
use Filament\Forms\Components\TextInput; use Filament\Forms\Components\Textarea; use Filament\Forms\Components\Toggle; use Filament\Schemas\Components\Grid; use Filament\Schemas\Schema;
class BankForm { public static function configure(Schema $schema): Schema { return $schema->components([
    Grid::make(2)->schema([
        TextInput::make('code')->label('كود البنك')->readOnly()->dehydrated(),
        TextInput::make('name')->label('اسم البنك')->required()->maxLength(255),
        TextInput::make('name_en')->label('الاسم بالإنجليزية')->maxLength(255),
        TextInput::make('swift_code')->label('SWIFT / BIC')->maxLength(20),
        TextInput::make('website')->label('الموقع الإلكتروني')->url()->maxLength(255),
        Toggle::make('is_active')->label('نشط')->default(true),
    ]), Textarea::make('notes')->label('ملاحظات')->rows(3)->columnSpanFull(),
]); } }
