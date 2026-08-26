<?php

namespace App\Console\Commands;

use App\Models\JournalEntry;
use App\Models\PurchaseInvoice;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RepairPurchaseInvoiceJournals extends Command
{
    protected $signature = 'octram:repair-purchase-invoice-journals
                            {--apply : تنفيذ التصحيح فعلياً. بدون هذا الخيار يعمل الأمر كمعاينة فقط}';

    protected $description = 'معاينة/تصحيح قيود فواتير الشراء القديمة التي سُجلت كقيود يدوية أو فقدت ربطها بالفاتورة';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');

        $candidates = JournalEntry::query()
            ->where(function ($query): void {
                $query
                    ->where('entry_type', '!=', JournalEntry::TYPE_AUTOMATIC)
                    ->orWhereNull('entry_type')
                    ->orWhereNull('source_id')
                    ->orWhereNull('source_type')
                    ->orWhere('source_type', 'manual');
            })
            ->whereNotNull('document_number')
            ->orderBy('id')
            ->get();

        $rows = [];
        $repairs = [];

        foreach ($candidates as $entry) {
            $invoice = PurchaseInvoice::query()
                ->where('code', $entry->document_number)
                ->first();

            if (! $invoice) {
                continue;
            }

            $expectedDescription = "فاتورة شراء {$invoice->code}";

            // لا نصحح إلا التطابق المؤكد تماماً حتى لا نلمس قيداً يدوياً حقيقياً.
            if (trim((string) $entry->description) !== $expectedDescription) {
                continue;
            }

            $expectedSourceType = $invoice->getMorphClass();

            $needsRepair =
                $entry->entry_type !== JournalEntry::TYPE_AUTOMATIC
                || (string) $entry->source_type !== (string) $expectedSourceType
                || (int) ($entry->source_id ?? 0) !== (int) $invoice->getKey();

            if (! $needsRepair) {
                continue;
            }

            $rows[] = [
                $entry->id,
                $entry->document_number,
                $entry->entry_type ?: 'NULL',
                $entry->source_type ?: 'NULL',
                $entry->source_id ?: 'NULL',
                $invoice->getKey(),
            ];

            $repairs[] = [$entry, $invoice, $expectedSourceType];
        }

        if ($rows === []) {
            $this->info('لا توجد قيود فواتير شراء قديمة تحتاج إلى تصحيح.');
            return self::SUCCESS;
        }

        $this->table(
            ['ID القيد', 'رقم المستند', 'النوع الحالي', 'المصدر الحالي', 'Source ID الحالي', 'Invoice ID الصحيح'],
            $rows,
        );

        $this->newLine();
        $this->line('عدد القيود المطابقة المؤكدة: '.count($repairs));

        if (! $apply) {
            $this->warn('هذه معاينة فقط. لم يتم تعديل أي بيانات.');
            $this->line('للتنفيذ الفعلي استخدم:');
            $this->line('php artisan octram:repair-purchase-invoice-journals --apply');
            return self::SUCCESS;
        }

        DB::transaction(function () use ($repairs): void {
            foreach ($repairs as [$entry, $invoice, $sourceType]) {
                JournalEntry::query()
                    ->whereKey($entry->getKey())
                    ->update([
                        'entry_type' => JournalEntry::TYPE_AUTOMATIC,
                        'source_type' => $sourceType,
                        'source_id' => $invoice->getKey(),
                        // القيد مولد بواسطة النظام؛ إظهار "النظام" في شاشة القيود.
                        'created_by' => null,
                    ]);
            }
        });

        $this->newLine();
        $this->info('تم تصحيح '.count($repairs).' قيد/قيود بنجاح.');
        $this->info('أصبحت القيود أوتوماتيكية ومربوطة بفواتير الشراء الصحيحة.');

        return self::SUCCESS;
    }
}
