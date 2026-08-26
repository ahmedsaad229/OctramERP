<?php

namespace App\Filament\Resources\JournalEntries\Pages;

use App\Filament\Resources\JournalEntries\JournalEntryResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;

class EditJournalEntry extends EditRecord
{
    protected static string $resource = JournalEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()->label('حذف القيد'),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->validateBalance();

        $data['updated_by'] = auth()->id();

        return $data;
    }

    private function validateBalance(): void
    {
        $state = $this->form->getRawState();
        $lines = collect($state['lines'] ?? []);

        $debit = round(
            (float) $lines->sum(fn ($line) => (float) ($line['debit'] ?? 0)),
            2
        );

        $credit = round(
            (float) $lines->sum(fn ($line) => (float) ($line['credit'] ?? 0)),
            2
        );

        if ($debit <= 0 || abs($debit - $credit) > 0.009) {
            throw ValidationException::withMessages([
                'data.lines' => 'القيد غير متزن. يجب أن يتساوى إجمالي المدين مع إجمالي الدائن.',
            ]);
        }

        foreach ($lines as $index => $line) {
            $lineDebit = round((float) ($line['debit'] ?? 0), 2);
            $lineCredit = round((float) ($line['credit'] ?? 0), 2);

            if (($lineDebit > 0 && $lineCredit > 0) || ($lineDebit <= 0 && $lineCredit <= 0)) {
                throw ValidationException::withMessages([
                    "data.lines.{$index}.debit" => 'كل طرف يجب أن يحتوي على مبلغ مدين أو دائن فقط.',
                ]);
            }
        }
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'تم تعديل القيد بنجاح.';
    }
}
