<?php

namespace Tests\Feature;

use App\Filament\Pages\SupplierStatement;
use App\Models\CompanySetting;
use App\Models\PartyTransaction;
use App\Models\PurchaseInvoice;
use App\Models\Supplier;
use App\Models\SupplierPaymentVoucher;
use App\Models\Treasury;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\PartyTransactionService;
use App\Services\SupplierStatementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SupplierStatementTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Supplier $supplier;

    private Warehouse $warehouse;

    private Treasury $treasury;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->supplier = Supplier::create([
            'code' => 'SUP-STMT', 'name' => 'مورد كشف الحساب',
            'mobile' => '01000000000', 'active' => true,
        ]);
        $this->warehouse = Warehouse::create(['code' => 'WH-SUP-STMT', 'name' => 'مخزن المورد', 'active' => true]);
        $this->treasury = Treasury::create([
            'code' => 'TR-SUP-STMT', 'name' => 'خزينة المورد',
            'type' => 'cash', 'opening_balance' => 0, 'active' => true,
        ]);
    }

    public function test_page_access_navigation_labels_and_validation(): void
    {
        $this->get(SupplierStatement::getUrl())->assertRedirect();
        $this->assertSame('المشتريات', SupplierStatement::getNavigationGroup());
        $this->assertSame('كشف حساب الموردين', SupplierStatement::getNavigationLabel());

        $this->actingAs($this->user);
        Livewire::test(SupplierStatement::class)
            ->assertOk()
            ->assertSee('كشف حساب مورد')
            ->call('runReport')
            ->assertHasFormErrors(['supplier_id' => 'required'])
            ->fillForm([
                'supplier_id' => $this->supplier->id,
                'from_date' => '2026-07-10',
                'to_date' => '2026-07-01',
            ])
            ->call('runReport')
            ->assertHasFormErrors(['to_date']);
    }

    public function test_statement_calculates_opening_totals_running_and_stable_order(): void
    {
        $this->invoice('PUR-BEFORE', '2026-06-30', 5000);
        $first = $this->invoice('PUR-001', '2026-07-01', 15000);
        $second = $this->payment('SPV-001', '2026-07-01', 5000);
        $this->invoice('PUR-002', '2026-07-10', 20000);

        PartyTransaction::query()->whereMorphedTo('source', $first)->update(['created_at' => '2026-07-01 08:00:00']);
        PartyTransaction::query()->whereMorphedTo('source', $second)->update(['created_at' => '2026-07-01 09:00:00']);

        $report = app(SupplierStatementService::class)->report($this->supplier->id, '2026-07-01', '2026-07-10');

        $this->assertSame(5000.0, $report['openingBalance']);
        $this->assertSame(35000.0, $report['totalPurchases']);
        $this->assertSame(5000.0, $report['totalPaid']);
        $this->assertSame(35000.0, $report['closingBalance']);
        $this->assertSame(3, $report['transactionCount']);
        $this->assertSame([20000.0, 15000.0, 35000.0], $report['rows']->pluck('runningBalance')->all());
        $this->assertSame(['PUR-001', 'SPV-001', 'PUR-002'], $report['rows']->pluck('reference')->all());
        $this->assertSame(['فاتورة شراء', 'سند صرف مورد', 'فاتورة شراء'], $report['rows']->pluck('typeLabel')->all());
        $this->assertSame('مستحق للمورد', $report['statusLabel']);
        $this->assertSame('35,000.00 ج.م', app(SupplierStatementService::class)->money(35000));
        $this->assertNotNull($report['rows']->first()['url']);
    }

    public function test_filter_true_balance_empty_period_and_statuses(): void
    {
        $this->invoice('PUR-FILTER', '2026-06-01', 1000);
        $this->payment('SPV-FILTER', '2026-07-02', 400);

        $filtered = app(SupplierStatementService::class)->report(
            $this->supplier->id, '2026-07-01', '2026-07-31', PartyTransaction::TYPE_SUPPLIER_PAYMENT,
        );
        $this->assertCount(1, $filtered['rows']);
        $this->assertSame(1000.0, $filtered['openingBalance']);
        $this->assertSame(0.0, $filtered['totalPurchases']);
        $this->assertSame(400.0, $filtered['totalPaid']);
        $this->assertSame(600.0, $filtered['closingBalance']);
        $this->assertSame(600.0, $filtered['rows']->first()['runningBalance']);

        $empty = app(SupplierStatementService::class)->report($this->supplier->id, '2026-08-01', '2026-08-31');
        $this->assertTrue($empty['rows']->isEmpty());
        $this->assertSame(600.0, $empty['openingBalance']);
        $this->assertSame(600.0, $empty['closingBalance']);

        $this->payment('SPV-ZERO', '2026-07-03', 600);
        $this->assertSame('الحساب مسدد', app(SupplierStatementService::class)
            ->report($this->supplier->id, null, '2026-07-31')['statusLabel']);
        $this->payment('SPV-CREDIT', '2026-07-04', 50);
        $this->assertSame('رصيد دائن للشركة', app(SupplierStatementService::class)
            ->report($this->supplier->id, null, '2026-07-31')['statusLabel']);
    }

    public function test_missing_source_page_rendering_and_new_tab_print_link_are_safe(): void
    {
        PartyTransaction::create([
            'party_type' => $this->supplier->getMorphClass(), 'party_id' => $this->supplier->id,
            'transaction_type' => PartyTransaction::TYPE_PURCHASE_INVOICE,
            'source_type' => PurchaseInvoice::class, 'source_id' => 999999,
            'reference_no' => 'MISSING-SUP-001', 'transaction_date' => '2026-07-01',
            'debit' => 0, 'credit' => 250,
        ]);

        $report = app(SupplierStatementService::class)->report($this->supplier->id);
        $this->assertNull($report['rows']->first()['url']);
        $this->assertSame('حركة مورد', app(SupplierStatementService::class)->transactionTypeLabel('raw_internal'));

        $this->actingAs($this->user);
        Livewire::test(SupplierStatement::class)
            ->fillForm(['supplier_id' => $this->supplier->id])
            ->call('runReport')
            ->assertHasNoFormErrors()
            ->assertSee('مورد كشف الحساب')
            ->assertSee('MISSING-SUP-001')
            ->assertSee('250.00 ج.م')
            ->assertSeeHtml('target="_blank"')
            ->assertSeeHtml('rel="noopener noreferrer"')
            ->assertDontSee('App\Models');
    }

    public function test_print_route_is_authenticated_and_renders_clean_a4_report(): void
    {
        CompanySetting::current()->update(['company_name' => 'شركة أوكترام']);
        $this->invoice('PUR-PRINT', '2026-07-01', 1250);
        $url = route('supplier-statement.print', [
            'supplier' => $this->supplier->id,
            'from_date' => '2026-07-01',
            'to_date' => '2026-07-31',
        ]);

        $this->get($url)->assertRedirect();
        $this->actingAs($this->user);
        $this->get($url)
            ->assertOk()
            ->assertSee('company-document-header', escape: false)
            ->assertSee('كشف حساب مورد')
            ->assertSee('مورد كشف الحساب')
            ->assertSee('PUR-PRINT')
            ->assertSee('1,250.00 ج.م')
            ->assertSee('@page', escape: false)
            ->assertDontSee('fi-sidebar', escape: false)
            ->assertDontSee('Laravel');
    }

    public function test_excel_export_is_authenticated_filtered_and_contains_statement_totals(): void
    {
        $this->invoice('PUR-EXPORT-BEFORE', '2026-06-30', 5000);
        $this->invoice('PUR-EXPORT', '2026-07-02', 1500);
        $this->payment('SPV-EXPORT', '2026-07-03', 500);

        $otherSupplier = Supplier::create(['name' => 'مورد آخر', 'active' => true]);
        PartyTransaction::create([
            'party_type' => $otherSupplier->getMorphClass(),
            'party_id' => $otherSupplier->id,
            'transaction_type' => PartyTransaction::TYPE_PURCHASE_INVOICE,
            'reference_no' => 'OTHER-SUPPLIER',
            'transaction_date' => '2026-07-02',
            'debit' => 0,
            'credit' => 9999,
        ]);

        $url = route('supplier-statement.excel', [
            'supplier' => $this->supplier->id,
            'from_date' => '2026-07-01',
            'to_date' => '2026-07-31',
        ]);

        $this->get($url)->assertRedirect();
        $this->actingAs($this->user);
        $response = $this->get($url);
        $response->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $content = $response->streamedContent();

        $this->assertStringStartsWith("\xEF\xBB\xBF", $content);
        $this->assertStringContainsString('اسم المورد', $content);
        $this->assertStringContainsString('مورد كشف الحساب', $content);
        $this->assertStringContainsString('PUR-EXPORT', $content);
        $this->assertStringContainsString('SPV-EXPORT', $content);
        $this->assertStringNotContainsString('OTHER-SUPPLIER', $content);
        $this->assertStringContainsString('"رصيد أول المدة",5000', $content);
        $this->assertStringContainsString('"إجمالي المدين",500', $content);
        $this->assertStringContainsString('"إجمالي الدائن",1500', $content);
        $this->assertStringContainsString('"الرصيد الختامي",6000', $content);

        $empty = $this->get(route('supplier-statement.excel', [
            'supplier' => $this->supplier->id,
            'from_date' => '2027-01-01',
            'to_date' => '2027-01-31',
        ]));
        $empty->assertOk();
        $this->assertStringContainsString('الرصيد الختامي', $empty->streamedContent());
    }

    public function test_statement_uses_shared_report_wrapper_and_one_filtered_excel_action(): void
    {
        $this->invoice('PUR-WRAPPER', '2026-07-05', 100);
        $this->actingAs($this->user);
        $component = Livewire::test(SupplierStatement::class)
            ->fillForm([
                'supplier_id' => $this->supplier->id,
                'from_date' => '2026-07-01',
                'to_date' => '2026-07-31',
            ])
            ->call('runReport')
            ->assertSeeHtml('class="octram-report')
            ->assertSeeHtml('octram-report-scroll')
            ->assertSeeHtml(e(route('supplier-statement.excel', [
                'supplier' => $this->supplier->id,
                'from_date' => '2026-07-01',
                'to_date' => '2026-07-31',
            ])));

        $this->assertSame(1, substr_count($component->html(), 'تصدير Excel'));
    }

    private function invoice(string $number, string $date, float $amount): PurchaseInvoice
    {
        $invoice = PurchaseInvoice::create([
            'code' => $number, 'invoice_number' => $number, 'invoice_date' => $date,
            'supplier_id' => $this->supplier->id, 'warehouse_id' => $this->warehouse->id,
            'payment_type' => 'cash', 'tax_type' => 'none', 'discount_amount' => 0, 'tax_amount' => 0,
        ]);
        app(PartyTransactionService::class)->replaceDocumentTransaction(
            $this->supplier, PartyTransaction::TYPE_PURCHASE_INVOICE, $invoice, $date,
            0, $amount, $number,
        );

        return $invoice;
    }

    private function payment(string $number, string $date, float $amount): SupplierPaymentVoucher
    {
        $voucher = SupplierPaymentVoucher::create([
            'document_number' => $number, 'voucher_date' => $date,
            'supplier_id' => $this->supplier->id, 'treasury_id' => $this->treasury->id,
            'amount' => $amount, 'payment_method' => 'cash', 'created_by' => $this->user->id,
        ]);
        app(PartyTransactionService::class)->replaceDocumentTransaction(
            $this->supplier, PartyTransaction::TYPE_SUPPLIER_PAYMENT, $voucher, $date,
            $amount, 0, $number,
        );

        return $voucher;
    }
}
