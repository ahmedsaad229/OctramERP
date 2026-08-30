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
         * Do not call authorize('delete') here.
         *
         * Filament interprets that as a direct Laravel Gate check against
         * the record, which bypasses our Resource::canDelete() permission
         * system.
         *
         * Leaving the action authorization unset allows Filament to use
         * the Resource's default action authorization.
         */
        $this->action(function (): void {
            /*
             * Authorization is also checked by Filament before the action
             * can be executed. This explicit check provides defense in depth
             * while still using the Resource authorization response.
             */
            if (! $this->isAuthorized()) {
                $this->failure();

                return;
            }

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
