<?php

namespace App\Filament\Actions;

use App\Exceptions\DocumentDeletionBlockedException;
use App\Services\Documents\DocumentDeletionGuard;
use App\Services\Documents\DocumentDeletionService;
use Filament\Actions\DeleteBulkAction;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Collection;

class ProtectedDeleteBulkAction extends DeleteBulkAction
{
    protected function setUp(): void
    {
        parent::setUp();

        /*
         * Keep Filament's normal delete-any authorization active.
         */
        $this->authorize('deleteAny');

        $this->action(function (Collection $records): void {
            /*
             * Defense in depth:
             * authorization is checked again when executing.
             */
            $this->authorize('deleteAny');

            try {
                $guard = app(DocumentDeletionGuard::class);

                foreach ($records as $record) {
                    $guard->assertCanDelete($record);
                }

                foreach ($records as $record) {
                    app(DocumentDeletionService::class)->delete($record);
                }
            } catch (DocumentDeletionBlockedException $exception) {
                Notification::make()
                    ->danger()
                    ->title($exception->title())
                    ->body($exception->getMessage())
                    ->persistent()
                    ->send();

                $this->halt();
            }
        });
    }
}
