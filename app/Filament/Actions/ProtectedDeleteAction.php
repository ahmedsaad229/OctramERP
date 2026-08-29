<?php

namespace App\Filament\Actions;

use App\Exceptions\DocumentDeletionBlockedException;
use App\Services\Documents\DocumentDeletionService;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;

class ProtectedDeleteAction extends DeleteAction
{
    protected function setUp(): void
    {
        parent::setUp();

        /*
         * Keep Filament's normal resource authorization active.
         * The action must not be visible or executable when the
         * current resource denies deletion for the record.
         */
        $this->authorize('delete');

        $this->action(function (): void {
            /*
             * Defense in depth:
             * re-run authorization when the action is actually executed.
             */
            $this->authorize('delete');

            try {
                $result = $this->process(
                    fn (Model $record): bool => app(DocumentDeletionService::class)->delete($record),
                );
            } catch (DocumentDeletionBlockedException $exception) {
                Notification::make()
                    ->danger()
                    ->title($exception->title())
                    ->body($exception->getMessage())
                    ->persistent()
                    ->send();

                $this->halt();
            }

            if (! $result) {
                $this->failure();

                return;
            }

            $this->success();
        });
    }
}
