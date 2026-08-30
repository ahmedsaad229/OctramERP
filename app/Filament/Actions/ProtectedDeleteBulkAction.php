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
         * Do not call authorize('deleteAny') directly.
         * Leaving authorization unset allows Filament to use the
         * Resource's canDeleteAny() authorization.
         */
        $this->action(function (Collection $records): void {
            if (! $this->isAuthorized()) {
                $this->failure();

                return;
            }

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
