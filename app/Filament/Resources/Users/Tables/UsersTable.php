<?php

namespace App\Filament\Resources\Users\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Validation\ValidationException;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('الاسم')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('email')
                    ->label('البريد الإلكتروني')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('mobile')
                    ->label('الموبايل')
                    ->searchable()
                    ->placeholder('—')
                    ->copyable(),

                TextColumn::make('job_title')
                    ->label('المسمى الوظيفي')
                    ->searchable()
                    ->sortable()
                    ->placeholder('—'),

                TextColumn::make('role.name')
                    ->label('الدور')
                    ->badge()
                    ->placeholder('بدون دور'),

                IconColumn::make('is_active')
                    ->label('نشط')
                    ->boolean()
                    ->sortable(),

                IconColumn::make('is_admin')
                    ->label('مدير نظام')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->filters([])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->action(function (DeleteAction $action): void {
                        try {
                            $action->getRecord()->delete();
                        } catch (ValidationException $exception) {
                            Notification::make()
                                ->danger()
                                ->title('تعذر حذف المستخدم')
                                ->body(collect($exception->errors())->flatten()->first())
                                ->persistent()
                                ->send();

                            $action->halt();
                        }
                    }),
            ]);
    }
}
