<?php

namespace App\Services;

use App\Models\Account;
use App\Models\BankAccount;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use Illuminate\Support\Collection;

class CashFlowStatementService
{
    public const SECTION_OPERATING = 'operating';
    public const SECTION_INVESTING = 'investing';
    public const SECTION_FINANCING = 'financing';

    /** @return array<string, mixed> */
    public function report(?string $fromDate, ?string $toDate, bool $details = true): array
    {
        $cashAccountIds = $this->cashAccountIds();

        if ($cashAccountIds->isEmpty()) {
            return $this->emptyReport($fromDate, $toDate, $details);
        }

        $openingBalance = $this->cashBalanceBefore($cashAccountIds, $fromDate);

        $entries = JournalEntry::query()
            ->with(['lines.account'])
            ->whereHas('lines', fn ($query) => $query->whereIn('account_id', $cashAccountIds))
            ->when($fromDate, fn ($query) => $query->whereDate('entry_date', '>=', $fromDate))
            ->when($toDate, fn ($query) => $query->whereDate('entry_date', '<=', $toDate))
            ->orderBy('entry_date')
            ->orderBy('id')
            ->get();

        $movements = $entries
            ->map(fn (JournalEntry $entry): ?array => $this->movementFromEntry($entry, $cashAccountIds))
            ->filter()
            ->values();

        $sections = collect([
            self::SECTION_OPERATING => $this->section(
                'التدفقات النقدية من الأنشطة التشغيلية',
                self::SECTION_OPERATING,
                $movements,
                $details,
            ),
            self::SECTION_INVESTING => $this->section(
                'التدفقات النقدية من الأنشطة الاستثمارية',
                self::SECTION_INVESTING,
                $movements,
                $details,
            ),
            self::SECTION_FINANCING => $this->section(
                'التدفقات النقدية من الأنشطة التمويلية',
                self::SECTION_FINANCING,
                $movements,
                $details,
            ),
        ]);

        $operating = (float) $sections[self::SECTION_OPERATING]['net'];
        $investing = (float) $sections[self::SECTION_INVESTING]['net'];
        $financing = (float) $sections[self::SECTION_FINANCING]['net'];
        $netChange = round($operating + $investing + $financing, 2);
        $closingBalance = round($openingBalance + $netChange, 2);
        $ledgerClosingBalance = $this->cashBalanceTo($cashAccountIds, $toDate);
        $difference = round($closingBalance - $ledgerClosingBalance, 2);

        return [
            'sections' => $sections,
            'totals' => [
                'opening_balance' => round($openingBalance, 2),
                'inflows' => round((float) $movements->where('amount', '>', 0)->sum('amount'), 2),
                'outflows' => round(abs((float) $movements->where('amount', '<', 0)->sum('amount')), 2),
                'operating' => round($operating, 2),
                'investing' => round($investing, 2),
                'financing' => round($financing, 2),
                'net_change' => $netChange,
                'closing_balance' => $closingBalance,
                'ledger_closing_balance' => round($ledgerClosingBalance, 2),
                'difference' => $difference,
                'balanced' => abs($difference) < 0.01,
                'movement_count' => $movements->count(),
            ],
            'cashAccounts' => Account::query()
                ->whereIn('id', $cashAccountIds)
                ->orderBy('code')
                ->get(['id', 'code', 'name']),
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'details' => $details,
        ];
    }

    /** @param Collection<int, int> $cashAccountIds */
    private function movementFromEntry(JournalEntry $entry, Collection $cashAccountIds): ?array
    {
        $cashLines = $entry->lines->whereIn('account_id', $cashAccountIds);
        $amount = round((float) $cashLines->sum('debit') - (float) $cashLines->sum('credit'), 2);

        // Internal cash and bank transfers have no cash-flow effect. Only fees remain as an outflow.
        if (abs($amount) < 0.005) {
            return null;
        }

        $counterpartLines = $entry->lines->whereNotIn('account_id', $cashAccountIds);
        $classification = $this->classify($entry, $counterpartLines, $amount);

        return [
            'id' => $entry->getKey(),
            'date' => $entry->entry_date?->format('Y-m-d'),
            'document_number' => $entry->document_number,
            'description' => $entry->description ?: 'حركة نقدية',
            'source_type' => class_basename((string) $entry->source_type),
            'section' => $classification['section'],
            'category' => $classification['category'],
            'category_label' => $classification['label'],
            'amount' => $amount,
            'direction' => $amount >= 0 ? 'inflow' : 'outflow',
        ];
    }

    /** @param Collection<int, JournalEntryLine> $counterpartLines
     *  @return array{section:string,category:string,label:string}
     */
    private function classify(JournalEntry $entry, Collection $counterpartLines, float $amount): array
    {
        $source = class_basename((string) $entry->source_type);

        if (in_array($source, ['ReceiptVoucher', 'SalesInvoice'], true)) {
            return [
                'section' => self::SECTION_OPERATING,
                'category' => 'customer_receipts',
                'label' => 'تحصيلات ومبيعات نقدية',
            ];
        }

        if (in_array($source, ['SupplierPaymentVoucher', 'PurchaseInvoice'], true)) {
            return [
                'section' => self::SECTION_OPERATING,
                'category' => 'supplier_payments',
                'label' => 'مدفوعات الموردين والمشتريات النقدية',
            ];
        }

        if ($source === 'BankTransfer') {
            return [
                'section' => self::SECTION_OPERATING,
                'category' => 'bank_fees',
                'label' => 'مصروفات وعمولات بنكية',
            ];
        }

        $accounts = $counterpartLines
            ->pluck('account')
            ->filter();

        $codes = $accounts->pluck('code')->filter()->all();

        if (array_intersect($codes, [AccountingAccountResolver::CUSTOMERS, AccountingAccountResolver::SUPPLIERS])) {
            return [
                'section' => self::SECTION_OPERATING,
                'category' => $amount >= 0 ? 'customer_receipts' : 'supplier_payments',
                'label' => $amount >= 0 ? 'تحصيلات العملاء' : 'مدفوعات الموردين',
            ];
        }

        $types = $accounts->pluck('account_type')->filter()->unique();

        if ($types->contains(Account::TYPE_EQUITY) || $types->contains(Account::TYPE_LIABILITY)) {
            return [
                'section' => self::SECTION_FINANCING,
                'category' => $amount >= 0 ? 'financing_inflows' : 'financing_outflows',
                'label' => $amount >= 0 ? 'تمويلات وقروض وزيادات رأس المال' : 'سداد تمويلات وقروض ومسحوبات',
            ];
        }

        if ($types->contains(Account::TYPE_ASSET)) {
            return [
                'section' => self::SECTION_INVESTING,
                'category' => $amount >= 0 ? 'asset_disposals' : 'asset_purchases',
                'label' => $amount >= 0 ? 'متحصلات بيع أصول واستثمارات' : 'شراء أصول واستثمارات',
            ];
        }

        return [
            'section' => self::SECTION_OPERATING,
            'category' => $amount >= 0 ? 'other_operating_inflows' : 'operating_expenses',
            'label' => $amount >= 0 ? 'متحصلات تشغيلية أخرى' : 'مصروفات ومدفوعات تشغيلية',
        ];
    }

    /** @param Collection<int, array<string, mixed>> $movements */
    private function section(string $label, string $section, Collection $movements, bool $details): array
    {
        $sectionRows = $movements->where('section', $section)->values();

        $categories = $sectionRows
            ->groupBy('category')
            ->map(function (Collection $rows): array {
                return [
                    'key' => (string) $rows->first()['category'],
                    'label' => (string) $rows->first()['category_label'],
                    'amount' => round((float) $rows->sum('amount'), 2),
                    'rows' => $rows->values(),
                ];
            })
            ->values();

        return [
            'key' => $section,
            'label' => $label,
            'categories' => $categories,
            'rows' => $details ? $sectionRows : collect(),
            'inflows' => round((float) $sectionRows->where('amount', '>', 0)->sum('amount'), 2),
            'outflows' => round(abs((float) $sectionRows->where('amount', '<', 0)->sum('amount')), 2),
            'net' => round((float) $sectionRows->sum('amount'), 2),
        ];
    }

    /** @return Collection<int, int> */
    private function cashAccountIds(): Collection
    {
        $ids = Account::query()
            ->where('code', AccountingAccountResolver::CASH)
            ->pluck('id');

        if (class_exists(BankAccount::class)) {
            $ids = $ids->merge(BankAccount::query()->pluck('account_id'));
        }

        return $ids
            ->filter(fn ($id): bool => (int) $id > 0)
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values();
    }

    /** @param Collection<int, int> $cashAccountIds */
    private function cashBalanceBefore(Collection $cashAccountIds, ?string $fromDate): float
    {
        if (! $fromDate) {
            return 0.0;
        }

        return $this->cashBalanceQuery($cashAccountIds)
            ->whereDate('journal_entries.entry_date', '<', $fromDate)
            ->selectRaw('COALESCE(SUM(journal_entry_lines.debit - journal_entry_lines.credit), 0) AS balance')
            ->value('balance') ?: 0.0;
    }

    /** @param Collection<int, int> $cashAccountIds */
    private function cashBalanceTo(Collection $cashAccountIds, ?string $toDate): float
    {
        $query = $this->cashBalanceQuery($cashAccountIds);

        if ($toDate) {
            $query->whereDate('journal_entries.entry_date', '<=', $toDate);
        }

        return (float) ($query
            ->selectRaw('COALESCE(SUM(journal_entry_lines.debit - journal_entry_lines.credit), 0) AS balance')
            ->value('balance') ?: 0.0);
    }

    /** @param Collection<int, int> $cashAccountIds */
    private function cashBalanceQuery(Collection $cashAccountIds)
    {
        return JournalEntryLine::query()
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
            ->whereIn('journal_entry_lines.account_id', $cashAccountIds);
    }

    /** @return array<string, mixed> */
    private function emptyReport(?string $fromDate, ?string $toDate, bool $details): array
    {
        $emptySection = fn (string $key, string $label): array => [
            'key' => $key,
            'label' => $label,
            'categories' => collect(),
            'rows' => collect(),
            'inflows' => 0.0,
            'outflows' => 0.0,
            'net' => 0.0,
        ];

        return [
            'sections' => collect([
                self::SECTION_OPERATING => $emptySection(self::SECTION_OPERATING, 'التدفقات النقدية من الأنشطة التشغيلية'),
                self::SECTION_INVESTING => $emptySection(self::SECTION_INVESTING, 'التدفقات النقدية من الأنشطة الاستثمارية'),
                self::SECTION_FINANCING => $emptySection(self::SECTION_FINANCING, 'التدفقات النقدية من الأنشطة التمويلية'),
            ]),
            'totals' => [
                'opening_balance' => 0.0,
                'inflows' => 0.0,
                'outflows' => 0.0,
                'operating' => 0.0,
                'investing' => 0.0,
                'financing' => 0.0,
                'net_change' => 0.0,
                'closing_balance' => 0.0,
                'ledger_closing_balance' => 0.0,
                'difference' => 0.0,
                'balanced' => true,
                'movement_count' => 0,
            ],
            'cashAccounts' => collect(),
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'details' => $details,
        ];
    }
}
