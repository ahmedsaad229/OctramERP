<?php

namespace App\Console\Commands;

use App\Models\PurchaseInvoice;
use App\Models\ReceiptVoucher;
use App\Models\SalesInvoice;
use App\Models\SupplierPaymentVoucher;
use App\Services\JournalEntryService;
use Illuminate\Console\Command;

class RebuildAccountingEntries extends Command
{
    protected $signature = 'accounting:rebuild {--fresh : حذف القيود الموجودة أولاً}';
    protected $description = 'إعادة بناء القيود المحاسبية من الفواتير والسندات الحالية';

    public function handle(JournalEntryService $service): int
    {
        if ($this->option('fresh')) {
            \App\Models\JournalEntry::query()->delete();
        }

        SalesInvoice::query()->with('items')->orderBy('id')->each(fn ($m) => $service->postSalesInvoice($m));
        PurchaseInvoice::query()->with('items')->orderBy('id')->each(fn ($m) => $service->postPurchaseInvoice($m));
        ReceiptVoucher::query()->orderBy('id')->each(fn ($m) => $service->postReceiptVoucher($m));
        SupplierPaymentVoucher::query()->orderBy('id')->each(fn ($m) => $service->postSupplierPaymentVoucher($m));

        $this->info('تمت إعادة بناء القيود المحاسبية بنجاح.');
        return self::SUCCESS;
    }
}
