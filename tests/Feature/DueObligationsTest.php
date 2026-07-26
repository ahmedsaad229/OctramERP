<?php

namespace Tests\Feature;

use App\Filament\Resources\DueObligations\Pages\ListDueObligations;
use App\Filament\Resources\DueObligations\Widgets\DueObligationStats;
use App\Models\DueObligation;
use App\Models\User;
use App\Support\ArabicInvoiceCount;
use App\Support\ArabicMoney;
use App\Support\DueObligationSummary;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class DueObligationsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-07-26 12:00:00');
        $this->createSchema();
        $this->seedInvoices();
        $this->actingAs(User::factory()->create());
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_due_obligations_screen(): void
    {
        $this->assertSummaryTotals();
        $this->assertDueStatusFilters();
        $this->assertTabs();
        $this->assertTabsCombineWithFilters();
        $this->assertArabicPresentation();
        $this->assertDefaultSorting();
        $this->assertViewOnlyActions();
        $this->assertNoOverdueBanner();
    }

    private function assertSummaryTotals(): void
    {
        $this->assertSame([
            'customer_due' => 450.0,
            'customer_due_count' => 3,
            'supplier_due' => 700.0,
            'supplier_due_count' => 3,
            'due_today' => 350.0,
            'due_today_count' => 2,
            'overdue' => 300.0,
            'overdue_count' => 2,
        ], DueObligationSummary::totals());

        $this->assertSame('0.00 ج.م', ArabicMoney::format(0));
        $this->assertSame('30,000.00 ج.م', ArabicMoney::format(30000));

        Livewire::test(DueObligationStats::class)
            ->assertSee('3 فواتير آجلة')
            ->assertSee('فاتورتان مستحقتان اليوم')
            ->assertSee('فاتورتان متأخرتان')
            ->assertSee('يوجد فاتورتان متأخرتان بإجمالي 300.00 ج.م')
            ->assertSee('450.00')
            ->assertSee('700.00')
            ->assertSee('ج.م')
            ->assertDontSee('EGP');

        $this->assertSame('لا توجد فواتير آجلة', ArabicInvoiceCount::credit(0));
        $this->assertSame('فاتورة آجلة واحدة', ArabicInvoiceCount::credit(1));
        $this->assertSame('فاتورتان آجلتان', ArabicInvoiceCount::credit(2));
        $this->assertSame('8 فواتير آجلة', ArabicInvoiceCount::credit(8));
        $this->assertSame('11 فاتورة آجلة', ArabicInvoiceCount::credit(11));
        $this->assertSame('لا توجد فواتير مستحقة اليوم', ArabicInvoiceCount::dueToday(0));
        $this->assertSame('فاتورة واحدة مستحقة اليوم', ArabicInvoiceCount::dueToday(1));
        $this->assertSame('فاتورتان مستحقتان اليوم', ArabicInvoiceCount::dueToday(2));
        $this->assertSame('5 فواتير مستحقة اليوم', ArabicInvoiceCount::dueToday(5));
        $this->assertSame('12 فاتورة مستحقة اليوم', ArabicInvoiceCount::dueToday(12));
        $this->assertSame('لا توجد فواتير متأخرة', ArabicInvoiceCount::overdue(0));
        $this->assertSame('فاتورة متأخرة واحدة', ArabicInvoiceCount::overdue(1));
        $this->assertSame('فاتورتان متأخرتان', ArabicInvoiceCount::overdue(2));
        $this->assertSame('7 فواتير متأخرة', ArabicInvoiceCount::overdue(7));
        $this->assertSame('15 فاتورة متأخرة', ArabicInvoiceCount::overdue(15));
    }

    private function assertDueStatusFilters(): void
    {
        $records = $this->records();

        Livewire::test(ListDueObligations::class)
            ->filterTable('due_status', DueObligation::STATUS_OVERDUE)
            ->assertCanSeeTableRecords([$records['sale:1'], $records['purchase:1']])
            ->assertCanNotSeeTableRecords([$records['sale:2'], $records['purchase:2'], $records['sale:3']])
            ->filterTable('due_status', DueObligation::STATUS_TODAY)
            ->assertCanSeeTableRecords([$records['sale:2'], $records['purchase:2']])
            ->assertCanNotSeeTableRecords([$records['sale:1'], $records['purchase:1'], $records['sale:3']])
            ->filterTable('due_status', DueObligation::STATUS_FUTURE)
            ->assertCanSeeTableRecords([$records['sale:3'], $records['purchase:3']])
            ->assertCanNotSeeTableRecords([$records['sale:1'], $records['sale:2'], $records['purchase:2']]);
    }

    private function assertTabs(): void
    {
        $records = $this->records();

        Livewire::test(ListDueObligations::class)
            ->assertSet('activeTab', 'all')
            ->assertCanSeeTableRecords(array_values($records))
            ->set('activeTab', 'customers')
            ->assertCanSeeTableRecords([
                $records['sale:1'],
                $records['sale:2'],
                $records['sale:3'],
                $records['sale:4'],
            ])
            ->assertCanNotSeeTableRecords([
                $records['purchase:1'],
                $records['purchase:2'],
                $records['purchase:3'],
            ])
            ->set('activeTab', 'suppliers')
            ->assertCanSeeTableRecords([
                $records['purchase:1'],
                $records['purchase:2'],
                $records['purchase:3'],
            ])
            ->assertCanNotSeeTableRecords([
                $records['sale:1'],
                $records['sale:2'],
                $records['sale:3'],
                $records['sale:4'],
            ]);
    }

    private function assertTabsCombineWithFilters(): void
    {
        $records = $this->records();

        Livewire::test(ListDueObligations::class)
            ->set('activeTab', 'customers')
            ->filterTable('due_status', DueObligation::STATUS_OVERDUE)
            ->assertCanSeeTableRecords([$records['sale:1']])
            ->assertCanNotSeeTableRecords([
                $records['purchase:1'],
                $records['sale:2'],
                $records['sale:3'],
                $records['sale:4'],
            ])
            ->assertSet('activeTab', 'customers');
    }

    private function assertArabicPresentation(): void
    {
        $records = $this->records();

        Livewire::test(ListDueObligations::class)
            ->assertTableColumnFormattedStateSet('total_amount', '100.00 ج.م', $records['sale:1'])
            ->assertTableColumnFormattedStateSet('payment_type', 'كاش', $records['sale:4'])
            ->assertTableColumnStateSet('due_date', null, $records['sale:4'])
            ->assertTableColumnFormattedStateSet('days', '—', $records['sale:4'])
            ->assertTableColumnFormattedStateSet('due_status', 'كاش', $records['sale:4'])
            ->assertTableColumnFormattedStateSet('days', 'متبقي 15 يوم', $records['sale:3'])
            ->assertTableColumnFormattedStateSet('days', 'متبقي يومان', $records['purchase:3'])
            ->assertTableColumnFormattedStateSet('days', 'اليوم', $records['sale:2'])
            ->assertTableColumnFormattedStateSet('days', 'متأخر 6 أيام', $records['sale:1'])
            ->assertTableColumnFormattedStateSet('due_status', 'مستحق لاحقاً', $records['sale:3'])
            ->assertTableColumnFormattedStateSet('due_status', 'مستحق اليوم', $records['sale:2'])
            ->assertTableColumnFormattedStateSet('due_status', 'متأخر', $records['sale:1'])
            ->assertDontSee('EGP');
    }

    private function assertDefaultSorting(): void
    {
        $records = $this->records();

        Livewire::test(ListDueObligations::class)
            ->assertCanSeeTableRecords([
                $records['sale:1'],
                $records['purchase:1'],
                $records['sale:2'],
                $records['purchase:2'],
                $records['purchase:3'],
                $records['sale:3'],
                $records['sale:4'],
            ], inOrder: true);
    }

    private function assertViewOnlyActions(): void
    {
        $records = $this->records();

        Livewire::test(ListDueObligations::class)
            ->assertTableActionVisible('view_invoice', $records['sale:1'])
            ->assertTableActionVisible('view_invoice', $records['purchase:1'])
            ->assertTableActionDoesNotExist('edit', null, $records['sale:1'])
            ->assertTableActionDoesNotExist('delete', null, $records['sale:1']);
    }

    private function assertNoOverdueBanner(): void
    {
        DB::table('sales_invoices')->where('id', 1)->update(['due_date' => '2026-08-20']);
        DB::table('purchase_invoices')->where('id', 1)->update(['due_date' => '2026-08-21']);

        Livewire::test(DueObligationStats::class)
            ->assertSee('لا توجد استحقاقات متأخرة.')
            ->assertSee('لا توجد فواتير متأخرة');
    }

    /**
     * @return array<string, DueObligation>
     */
    private function records(): array
    {
        return DueObligation::queryUnified()->get()
            ->mapWithKeys(fn (DueObligation $record): array => ["{$record->source_type}:{$record->source_id}" => $record])
            ->all();
    }

    private function seedInvoices(): void
    {
        DB::table('customers')->insert([
            ['id' => 1, 'name' => 'عميل ألف'],
            ['id' => 2, 'name' => 'عميل باء'],
        ]);
        DB::table('suppliers')->insert([
            ['id' => 1, 'name' => 'مورد ألف'],
            ['id' => 2, 'name' => 'مورد باء'],
        ]);
        DB::table('warehouses')->insert(['id' => 1, 'name' => 'المخزن الرئيسي']);

        DB::table('sales_invoices')->insert([
            ['id' => 1, 'document_number' => 'SAL-OVERDUE', 'invoice_date' => '2026-07-01', 'customer_id' => 1, 'warehouse_id' => 1, 'payment_type' => 'credit', 'due_date' => '2026-07-20'],
            ['id' => 2, 'document_number' => 'SAL-TODAY', 'invoice_date' => '2026-07-03', 'customer_id' => 1, 'warehouse_id' => 1, 'payment_type' => 'credit', 'due_date' => '2026-07-26'],
            ['id' => 3, 'document_number' => 'SAL-FUTURE', 'invoice_date' => '2026-07-04', 'customer_id' => 2, 'warehouse_id' => 1, 'payment_type' => 'credit', 'due_date' => '2026-08-10'],
            ['id' => 4, 'document_number' => 'SAL-CASH', 'invoice_date' => '2026-07-05', 'customer_id' => 2, 'warehouse_id' => 1, 'payment_type' => 'cash', 'due_date' => null],
        ]);
        DB::table('purchase_invoices')->insert([
            ['id' => 1, 'invoice_number' => 'PUR-OVERDUE', 'invoice_date' => '2026-07-02', 'supplier_id' => 1, 'warehouse_id' => 1, 'payment_type' => 'credit', 'due_date' => '2026-07-21'],
            ['id' => 2, 'invoice_number' => 'PUR-TODAY', 'invoice_date' => '2026-07-04', 'supplier_id' => 1, 'warehouse_id' => 1, 'payment_type' => 'credit', 'due_date' => '2026-07-26'],
            ['id' => 3, 'invoice_number' => 'PUR-FUTURE', 'invoice_date' => '2026-07-10', 'supplier_id' => 2, 'warehouse_id' => 1, 'payment_type' => 'credit', 'due_date' => '2026-07-28'],
        ]);

        foreach ([1 => 100, 2 => 150, 3 => 200, 4 => 900] as $invoiceId => $total) {
            DB::table('sales_invoice_items')->insert([
                'sales_invoice_id' => $invoiceId,
                'line_total' => $total,
            ]);
        }
        foreach ([1 => 200, 2 => 200, 3 => 300] as $invoiceId => $total) {
            DB::table('purchase_invoice_items')->insert([
                'purchase_invoice_id' => $invoiceId,
                'quantity' => 2,
                'unit_cost' => $total / 2,
            ]);
        }
    }

    private function createSchema(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });
        foreach (['customers', 'suppliers', 'warehouses'] as $tableName) {
            Schema::create($tableName, function (Blueprint $table): void {
                $table->id();
                $table->string('name');
            });
        }
        Schema::create('sales_invoices', function (Blueprint $table): void {
            $table->id();
            $table->string('document_number');
            $table->date('invoice_date');
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('warehouse_id');
            $table->string('payment_type');
            $table->date('due_date')->nullable();
        });
        Schema::create('purchase_invoices', function (Blueprint $table): void {
            $table->id();
            $table->string('invoice_number');
            $table->date('invoice_date');
            $table->unsignedBigInteger('supplier_id');
            $table->unsignedBigInteger('warehouse_id');
            $table->string('payment_type');
            $table->date('due_date')->nullable();
        });
        Schema::create('sales_invoice_items', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('sales_invoice_id');
            $table->decimal('line_total', 15, 2);
        });
        Schema::create('purchase_invoice_items', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('purchase_invoice_id');
            $table->decimal('quantity', 15, 2);
            $table->decimal('unit_cost', 15, 2);
        });
    }
}
