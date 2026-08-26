<?php

namespace App\Services;

use App\Models\Account;
use App\Models\BankAccount;
use App\Models\BankTransaction;
use App\Models\BankTransfer;
use App\Models\JournalEntry;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BankTransferService
{
    public function __construct(private readonly BankTransactionService $transactions) {}

    public function create(array $data): BankTransfer
    {
        return DB::transaction(function () use ($data): BankTransfer {
            $data = $this->validate($data);
            $data['document_number'] = $this->nextNumber();
            $data['created_by'] = auth()->id();
            $transfer = BankTransfer::query()->create($data);
            $this->post($transfer);
            return $transfer->fresh(['fromAccount.bank', 'toAccount.bank']);
        });
    }

    public function update(BankTransfer $transfer, array $data): BankTransfer
    {
        return DB::transaction(function () use ($transfer, $data): BankTransfer {
            $data = $this->validate($data);
            $transfer->update($data);
            $this->post($transfer);
            return $transfer->fresh(['fromAccount.bank', 'toAccount.bank']);
        });
    }

    public function delete(BankTransfer $transfer): bool
    {
        return DB::transaction(function () use ($transfer): bool {
            $this->transactions->deleteForSource($transfer);
            JournalEntry::query()->where('source_type', $transfer->getMorphClass())
                ->where('source_id', $transfer->getKey())->delete();
            return (bool) $transfer->delete();
        });
    }

    private function post(BankTransfer $transfer): void
    {
        $transfer->loadMissing(['fromAccount.ledgerAccount', 'toAccount.ledgerAccount']);
        $amount = round((float) $transfer->amount, 2);
        $fees = round((float) $transfer->fees, 2);

        $this->transactions->deleteForSource($transfer);
        $this->transactions->replaceForSource(
            $transfer->fromAccount, $transfer, $transfer->transfer_date,
            BankTransaction::TYPE_TRANSFER_OUT, $amount + $fees, BankTransaction::DIRECTION_CREDIT,
            $transfer->document_number, $transfer->reference_number, 'تحويل بنكي صادر',
        );
        $this->transactions->replaceForSource(
            $transfer->toAccount, $transfer, $transfer->transfer_date,
            BankTransaction::TYPE_TRANSFER_IN, $amount, BankTransaction::DIRECTION_DEBIT,
            $transfer->document_number, $transfer->reference_number, 'تحويل بنكي وارد',
        );

        $this->postJournal($transfer, $amount, $fees);
    }

    private function postJournal(BankTransfer $transfer, float $amount, float $fees): void
    {
        if (! class_exists(JournalEntry::class)) return;

        $fromId = (int) $transfer->fromAccount->account_id;
        $toId = (int) $transfer->toAccount->account_id;
        $expense = Account::query()->firstOrCreate(['code' => '6120'], [
            'name' => 'مصروفات وعمولات بنكية', 'account_type' => Account::TYPE_EXPENSE,
            'normal_balance' => Account::BALANCE_DEBIT, 'is_group' => false,
            'allow_posting' => true, 'active' => true, 'level' => 1, 'sort_order' => 6120,
        ]);

        JournalEntry::query()->where('source_type', $transfer->getMorphClass())
            ->where('source_id', $transfer->getKey())->delete();
        $entry = JournalEntry::query()->create([
            'entry_date' => $transfer->transfer_date,
            'source_type' => $transfer->getMorphClass(),
            'source_id' => $transfer->getKey(),
            'document_number' => $transfer->document_number,
            'description' => "تحويل بنكي {$transfer->document_number}",
            'created_by' => auth()->id(),
        ]);
        $lines = [
            ['account_id' => $toId, 'debit' => $amount, 'credit' => 0, 'memo' => 'الحساب البنكي المستلم'],
            ['account_id' => $fromId, 'debit' => 0, 'credit' => $amount + $fees, 'memo' => 'الحساب البنكي المحول منه'],
        ];
        if ($fees > 0) $lines[] = ['account_id' => $expense->getKey(), 'debit' => $fees, 'credit' => 0, 'memo' => 'عمولة التحويل'];
        $entry->lines()->createMany($lines);
    }

    private function validate(array $data): array
    {
        $from = (int) ($data['from_bank_account_id'] ?? 0);
        $to = (int) ($data['to_bank_account_id'] ?? 0);
        if ($from <= 0 || $to <= 0 || $from === $to) {
            throw ValidationException::withMessages(['data.to_bank_account_id' => 'يجب اختيار حسابين بنكيين مختلفين.']);
        }
        if (! BankAccount::query()->whereKey($from)->where('is_active', true)->exists()
            || ! BankAccount::query()->whereKey($to)->where('is_active', true)->exists()) {
            throw ValidationException::withMessages(['data.from_bank_account_id' => 'أحد الحسابات البنكية غير موجود أو غير نشط.']);
        }
        if ((float) ($data['amount'] ?? 0) <= 0) {
            throw ValidationException::withMessages(['data.amount' => 'قيمة التحويل يجب أن تكون أكبر من صفر.']);
        }
        $data['fees'] = max(0, (float) ($data['fees'] ?? 0));
        unset($data['document_number'], $data['created_by']);
        return $data;
    }

    private function nextNumber(): string
    {
        $year = now()->format('Y');
        $last = BankTransfer::query()->where('document_number', 'like', "BT-{$year}-%")
            ->orderByDesc('id')->value('document_number');
        $sequence = $last ? ((int) str($last)->afterLast('-')->toString()) + 1 : 1;
        return "BT-{$year}-".str_pad((string) $sequence, 5, '0', STR_PAD_LEFT);
    }
}
