<?php

namespace App\Services;

use App\Models\Account;
use App\Models\FiscalYear;
use App\Models\JournalEntry;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FiscalYearClosingService
{
    private const PNL_TYPES = [
        Account::TYPE_REVENUE,
        Account::TYPE_COST,
        Account::TYPE_EXPENSE,
        Account::TYPE_OTHER_REVENUE,
        Account::TYPE_OTHER_EXPENSE,
    ];

    public function preview(FiscalYear $year, int $retainedEarningsAccountId): array
    {
        $retained = Account::query()->posting()->findOrFail($retainedEarningsAccountId);

        if ($retained->account_type !== Account::TYPE_EQUITY) {
            throw ValidationException::withMessages([
                'retained_earnings_account_id' => 'حساب ترحيل نتيجة العام يجب أن يكون حساب حقوق ملكية قابلًا للترحيل.',
            ]);
        }

        $unbalanced = DB::table('journal_entries as je')
            ->join('journal_entry_lines as jl', 'jl.journal_entry_id', '=', 'je.id')
            ->whereBetween('je.entry_date', [$year->start_date->toDateString(), $year->end_date->toDateString()])
            ->groupBy('je.id', 'je.document_number')
            ->havingRaw('ABS(SUM(jl.debit) - SUM(jl.credit)) > 0.009')
            ->select('je.id', 'je.document_number')
            ->get();

        $rows = DB::table('journal_entry_lines as jl')
            ->join('journal_entries as je', 'je.id', '=', 'jl.journal_entry_id')
            ->join('accounts as a', 'a.id', '=', 'jl.account_id')
            ->whereBetween('je.entry_date', [$year->start_date->toDateString(), $year->end_date->toDateString()])
            ->whereIn('a.account_type', self::PNL_TYPES)
            ->groupBy('a.id', 'a.code', 'a.name', 'a.account_type')
            ->selectRaw('a.id, a.code, a.name, a.account_type, SUM(jl.debit) AS debit, SUM(jl.credit) AS credit')
            ->orderBy('a.code')
            ->get();

        $lines = [];
        $closingDebit = 0.0;
        $closingCredit = 0.0;

        foreach ($rows as $row) {
            $balance = round((float) $row->debit - (float) $row->credit, 2);

            if (abs($balance) < 0.005) {
                continue;
            }

            // لعكس رصيد الحساب: الرصيد المدين يُقفل بدائن، والدائن يُقفل بمدين.
            $debit = $balance < 0 ? abs($balance) : 0.0;
            $credit = $balance > 0 ? $balance : 0.0;

            $closingDebit += $debit;
            $closingCredit += $credit;

            $lines[] = [
                'account_id' => (int) $row->id,
                'code' => $row->code,
                'name' => $row->name,
                'debit' => round($debit, 2),
                'credit' => round($credit, 2),
            ];
        }

        // الفرق في قيد الإقفال هو نتيجة العام التي تُرحل لحقوق الملكية.
        $resultCredit = round($closingDebit - $closingCredit, 2);
        $retainedDebit = $resultCredit < 0 ? abs($resultCredit) : 0.0;
        $retainedCredit = $resultCredit > 0 ? $resultCredit : 0.0;

        if (abs($resultCredit) >= 0.005) {
            $lines[] = [
                'account_id' => $retained->getKey(),
                'code' => $retained->code,
                'name' => $retained->name.' (ترحيل نتيجة العام)',
                'debit' => round($retainedDebit, 2),
                'credit' => round($retainedCredit, 2),
            ];
        }

        $totalDebit = round(collect($lines)->sum('debit'), 2);
        $totalCredit = round(collect($lines)->sum('credit'), 2);

        return [
            'year' => $year,
            'retained_account' => $retained,
            'unbalanced_entries' => $unbalanced,
            'lines' => $lines,
            'total_debit' => $totalDebit,
            'total_credit' => $totalCredit,
            // موجب = ربح، سالب = خسارة.
            'net_result' => round($resultCredit, 2),
            'can_close' => $unbalanced->isEmpty()
                && abs($totalDebit - $totalCredit) < 0.01
                && ! $year->isClosed(),
        ];
    }

    public function close(FiscalYear $year, int $retainedEarningsAccountId): FiscalYear
    {
        return DB::transaction(function () use ($year, $retainedEarningsAccountId): FiscalYear {
            $year = FiscalYear::query()->lockForUpdate()->findOrFail($year->getKey());

            if ($year->isClosed()) {
                throw ValidationException::withMessages(['fiscal_year' => 'السنة المالية مقفلة بالفعل.']);
            }

            $preview = $this->preview($year, $retainedEarningsAccountId);

            if ($preview['unbalanced_entries']->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'fiscal_year' => 'لا يمكن الإقفال: توجد قيود غير متزنة داخل الفترة.',
                ]);
            }

            if (! $preview['can_close']) {
                throw ValidationException::withMessages([
                    'fiscal_year' => 'تعذر الإقفال لأن قيد الإقفال غير متزن.',
                ]);
            }

            $entry = JournalEntry::query()->create([
                'entry_date' => $year->end_date->toDateString(),
                'entry_type' => JournalEntry::TYPE_AUTOMATIC,
                'source_type' => FiscalYear::class,
                'source_id' => $year->getKey(),
                'document_number' => 'CLOSE-'.$year->end_date->format('Y'),
                'description' => 'قيد إقفال السنة المالية '.$year->name,
                'created_by' => null,
            ]);

            foreach ($preview['lines'] as $line) {
                $entry->lines()->create([
                    'account_id' => $line['account_id'],
                    'debit' => $line['debit'],
                    'credit' => $line['credit'],
                    'memo' => 'إقفال '.$year->name,
                ]);
            }

            $year->update([
                'status' => FiscalYear::STATUS_CLOSED,
                'retained_earnings_account_id' => $retainedEarningsAccountId,
                'closing_journal_entry_id' => $entry->getKey(),
                'closed_at' => now(),
                'closed_by' => auth()->id(),
            ]);

            return $year->fresh(['retainedEarningsAccount', 'closingJournalEntry', 'closer']);
        });
    }

    public function reopen(FiscalYear $year): FiscalYear
    {
        if (! (bool) (auth()->user()?->is_admin ?? false)) {
            throw ValidationException::withMessages([
                'fiscal_year' => 'إلغاء إقفال السنة متاح لمدير النظام فقط.',
            ]);
        }

        return DB::transaction(function () use ($year): FiscalYear {
            $year = FiscalYear::query()->lockForUpdate()->findOrFail($year->getKey());

            if (! $year->isClosed()) {
                return $year;
            }

            if ($year->closing_journal_entry_id) {
                // Query Builder لتجاوز منع حذف القيود الأوتوماتيكية مباشرة.
                DB::table('journal_entry_lines')
                    ->where('journal_entry_id', $year->closing_journal_entry_id)
                    ->delete();

                DB::table('journal_entries')
                    ->where('id', $year->closing_journal_entry_id)
                    ->where('source_type', FiscalYear::class)
                    ->where('source_id', $year->getKey())
                    ->delete();
            }

            $year->update([
                'status' => FiscalYear::STATUS_OPEN,
                'closing_journal_entry_id' => null,
                'closed_at' => null,
                'closed_by' => null,
            ]);

            return $year->fresh();
        });
    }
}
