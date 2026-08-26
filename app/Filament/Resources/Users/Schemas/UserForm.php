<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\Role;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('الاسم')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('email')
                            ->label('البريد الإلكتروني')
                            ->required()
                            ->email()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),

                        TextInput::make('mobile')
                            ->label('رقم الموبايل')
                            ->tel()
                            ->maxLength(50)
                            ->placeholder('01xxxxxxxxx'),

                        TextInput::make('job_title')
                            ->label('المسمى الوظيفي')
                            ->maxLength(150)
                            ->placeholder('مثال: مسؤول مبيعات'),

                        TextInput::make('password')
                            ->label('كلمة المرور')
                            ->password()
                            ->revealable()
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->minLength(8)
                            ->same('password_confirmation')
                            ->dehydrated(fn (?string $state): bool => filled($state)),

                        TextInput::make('password_confirmation')
                            ->label('تأكيد كلمة المرور')
                            ->password()
                            ->revealable()
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->minLength(8)
                            ->dehydrated(false),

                        Select::make('role_id')
                            ->label('الدور الوظيفي')
                            ->options(fn (): array => Role::query()->orderBy('name')->pluck('name', 'id')->all())
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->helperText('مدير النظام لا يحتاج إلى دور؛ لديه كل الصلاحيات تلقائيًا.'),

                        Toggle::make('is_active')
                            ->label('نشط')
                            ->default(true),

                        Toggle::make('is_admin')
                            ->label('مدير نظام')
                            ->default(false),
                    ]),
            ]);
    }
}
