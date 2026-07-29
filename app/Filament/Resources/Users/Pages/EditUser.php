<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
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
        ];
    }
}
