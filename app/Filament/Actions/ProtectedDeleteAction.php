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

        $this->action(function (): void {
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
