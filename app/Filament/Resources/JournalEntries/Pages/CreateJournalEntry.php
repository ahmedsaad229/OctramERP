<?php

namespace App\Filament\Resources\JournalEntries\Pages;

use App\Filament\Resources\JournalEntries\JournalEntryResource;
use App\Models\JournalEntry;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateJournalEntry extends CreateRecord
{
    protected static string $resource = JournalEntryResource::class;

    protected static bool $canCreateAnother = false;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->validateBalance();

        $data['entry_type'] = JournalEntry::TYPE_MANUAL;
        $data['source_type'] = 'manual';
        $data['source_id'] = null;
        $data['created_by'] = auth()->id();
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
                'data.lines' => 'القيد غير متزن. يجب أن يتساوى إجمالي المدين مع إجمالي الدائن وأن تكون القيمة أكبر من صفر.',
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

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'تم إنشاء القيد اليدوي بنجاح.';
    }
}
