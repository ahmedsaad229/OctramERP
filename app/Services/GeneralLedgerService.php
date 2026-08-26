<?php

namespace App\Services;

use App\Models\Account;
use App\Models\JournalEntryLine;
use Illuminate\Support\Collection;

class GeneralLedgerService
{
    /** @return array<string, mixed> */
    public function report(int $accountId, ?string $fromDate, ?string $toDate): array
    {
        $account = Account::query()->posting()->findOrFail($accountId);

        $base = JournalEntryLine::query()
            ->select([
                'journal_entry_lines.id',
                'journal_entry_lines.debit',
                'journal_entry_lines.credit',
                'journal_entry_lines.memo',
                'journal_entries.entry_date',
                'journal_entries.document_number',
                'journal_entries.description',
                'journal_entries.source_type',
                'journal_entries.source_id',
            ])
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
            ->where('journal_entry_lines.account_id', $account->getKey());

        $openingDebit = 0.0;
        $openingCredit = 0.0;

        if ($fromDate) {
            $openingDebit = (float) (clone $base)
                ->whereDate('journal_entries.entry_date', '<', $fromDate)
                ->sum('journal_entry_lines.debit');

            $openingCredit = (float) (clone $base)
                ->whereDate('journal_entries.entry_date', '<', $fromDate)
                ->sum('journal_entry_lines.credit');
        }

        $query = clone $base;
        if ($fromDate) {
            $query->whereDate('journal_entries.entry_date', '>=', $fromDate);
        }
        if ($toDate) {
            $query->whereDate('journal_entries.entry_date', '<=', $toDate);
        }

        $openingNet = round($openingDebit - $openingCredit, 2);
        $runningNet = $openingNet;

        $rows = $query
            ->orderBy('journal_entries.entry_date')
            ->orderBy('journal_entries.id')
            ->orderBy('journal_entry_lines.id')
            ->get()
            ->map(function ($line) use (&$runningNet): array {
                $debit = round((float) $line->debit, 2);
                $credit = round((float) $line->credit, 2);
                $runningNet = round($runningNet + $debit - $credit, 2);

                return [
                    'id' => $line->id,
                    'date' => $line->entry_date,
                    'document_number' => $line->document_number ?: '—',
                    'description' => $line->memo ?: $line->description ?: '—',
                    'source_type' => $line->source_type,
                    'source_label' => $this->sourceLabel((string) $line->source_type),
                    'debit' => $debit,
                    'credit' => $credit,
                    'running_debit' => max(0, $runningNet),
                    'running_credit' => max(0, -$runningNet),
                    'running_balance' => abs($runningNet),
                    'running_side' => $runningNet > 0.009 ? 'مدين' : ($runningNet < -0.009 ? 'دائن' : 'متزن'),
                ];
            });

        $periodDebit = round((float) $rows->sum('debit'), 2);
        $periodCredit = round((float) $rows->sum('credit'), 2);
        $closingNet = round($openingNet + $periodDebit - $periodCredit, 2);

        return [
            'account' => $account,
            'rows' => $rows,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'totals' => [
                'opening_debit' => max(0, $openingNet),
                'opening_credit' => max(0, -$openingNet),
                'period_debit' => $periodDebit,
                'period_credit' => $periodCredit,
                'closing_debit' => max(0, $closingNet),
                'closing_credit' => max(0, -$closingNet),
                'closing_balance' => abs($closingNet),
                'closing_side' => $closingNet > 0.009 ? 'مدين' : ($closingNet < -0.009 ? 'دائن' : 'متزن'),
                'transaction_count' => $rows->count(),
            ],
        ];
    }

    private function sourceLabel(string $sourceType): string
    {
        return match ($sourceType) {
            'sales_invoice' => 'فاتورة بيع',
            'purchase_invoice' => 'فاتورة شراء',
            'receipt_voucher' => 'سند قبض عميل',
            'supplier_payment_voucher' => 'سند صرف مورد',
            'cash_receipt_voucher' => 'سند قبض نقدي',
            'cash_payment_voucher' => 'سند صرف نقدي',
            'bank_transfer' => 'تحويل بنكي',
            'bank_check' => 'شيك بنكي',
            default => 'قيد محاسبي',
        };
    }
}
