<?php

namespace Tests\Feature;

use App\Filament\Pages\CustomerStatement;
use App\Models\CompanySetting;
use App\Models\Customer;
use App\Models\PartyTransaction;
use App\Models\ReceiptVoucher;
use App\Models\SalesInvoice;
use App\Models\Treasury;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\CustomerStatementService;
use App\Services\PartyTransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CustomerStatementTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Customer $customer;

    private Warehouse $warehouse;

    private Treasury $treasury;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->customer = Customer::create([
            'code' => 'CUS-STMT',
            'name' => 'عميل كشف الحساب',
            'mobile' => '01000000000',
            'active' => true,
        ]);
        $this->warehouse = Warehouse::create(['code' => 'WH-STMT', 'name' => 'مخزن الكشف', 'active' => true]);
        $this->treasury = Treasury::create([
            'code' => 'TR-STMT',
            'name' => 'خزينة الكشف',
            'type' => 'cash',
            'opening_balance' => 0,
            'active' => true,
        ]);
    }

    public function test_page_access_labels_and_validation(): void
    {
        $this->get(CustomerStatement::getUrl())->assertRedirect();

        $this->actingAs($this->user);
        Livewire::test(CustomerStatement::class)
            ->assertOk()
            ->assertSee('كشف حساب عميل')
            ->assertSee('من تاريخ')
            ->assertSee('إلى تاريخ');

        Livewire::test(CustomerStatement::class)
            ->call('runReport')
            ->assertHasFormErrors(['customer_id' => 'required'])
            ->fillForm([
                'customer_id' => $this->customer->id,
                'from_date' => '2026-07-10',
                'to_date' => '2026-07-01',
            ])
            ->call('runReport')
            ->assertHasFormErrors(['to_date']);
    }

    public function test_statement_calculates_opening_totals_running_and_stable_order(): void
    {
        $this->invoice('SI-BEFORE', '2026-06-30', 5000);
        $first = $this->invoice('SI-001', '2026-07-01', 15000);
        $second = $this->receipt('RV-001', '2026-07-01', 5000);
        $this->invoice('SI-002', '2026-07-10', 20000);

        PartyTransaction::query()->where('source_id', $first->id)->where('source_type', $first->getMorphClass())
            ->update(['created_at' => '2026-07-01 08:00:00']);
        PartyTransaction::query()->where('source_id', $second->id)->where('source_type', $second->getMorphClass())
            ->update(['created_at' => '2026-07-01 09:00:00']);

        $report = app(CustomerStatementService::class)->report(
            $this->customer->id,
            '2026-07-01',
            '2026-07-10',
        );

        $this->assertSame(5000.0, $report['openingBalance']);
        $this->assertSame(35000.0, $report['totalDebt']);
        $this->assertSame(5000.0, $report['totalPaid']);
        $this->assertSame(35000.0, $report['closingBalance']);
        $this->assertSame(3, $report['transactionCount']);
        $this->assertSame([20000.0, 15000.0, 35000.0], $report['rows']->pluck('runningBalance')->all());
        $this->assertSame(['SI-001', 'RV-001', 'SI-002'], $report['rows']->pluck('reference')->all());
        $this->assertSame(['فاتورة بيع', 'سند قبض', 'فاتورة بيع'], $report['rows']->pluck('typeLabel')->all());
        $this->assertSame('على العميل', $report['statusLabel']);
        $this->assertSame('35,000.00 ج.م', app(CustomerStatementService::class)->money($report['closingBalance']));
        $this->assertNotNull($report['rows']->first()['url']);
    }

    public function test_type_filter_changes_rows_and_totals_without_corrupting_true_balance(): void
    {
        $this->invoice('SI-FILTER', '2026-07-01', 1000);
        $this->receipt('RV-FILTER', '2026-07-02', 400);

        $report = app(CustomerStatementService::class)->report(
            $this->customer->id,
            '2026-07-01',
            '2026-07-31',
            PartyTransaction::TYPE_CUSTOMER_CREDIT,
        );

        $this->assertCount(1, $report['rows']);
        $this->assertSame(0.0, $report['totalDebt']);
        $this->assertSame(400.0, $report['totalPaid']);
        $this->assertSame(600.0, $report['closingBalance']);
        $this->assertSame(600.0, $report['rows']->first()['runningBalance']);
    }

    public function test_empty_period_prior_balance_and_balance_statuses(): void
    {
        $this->invoice('SI-PRIOR', '2026-06-01', 100);

        $report = app(CustomerStatementService::class)->report($this->customer->id, '2026-07-01', '2026-07-31');
        $this->assertTrue($report['rows']->isEmpty());
        $this->assertSame(100.0, $report['openingBalance']);
        $this->assertSame(100.0, $report['closingBalance']);

        $this->receipt('RV-ZERO', '2026-07-01', 100);
        $this->assertSame('مسدد', app(CustomerStatementService::class)
            ->report($this->customer->id, null, '2026-07-31')['statusLabel']);

        $this->receipt('RV-CREDIT', '2026-07-02', 50);
        $this->assertSame('رصيد دائن', app(CustomerStatementService::class)
            ->report($this->customer->id, null, '2026-07-31')['statusLabel']);
    }

    public function test_missing_source_falls_back_safely_and_page_renders_report(): void
    {
        PartyTransaction::create([
            'party_type' => $this->customer->getMorphClass(),
            'party_id' => $this->customer->id,
            'transaction_type' => PartyTransaction::TYPE_CUSTOMER_DEBIT,
            'source_type' => SalesInvoice::class,
            'source_id' => 999999,
            'reference_no' => 'MISSING-001',
            'transaction_date' => '2026-07-01',
            'debit' => 250,
            'credit' => 0,
        ]);

        $report = app(CustomerStatementService::class)->report($this->customer->id);
        $this->assertSame('MISSING-001', $report['rows']->first()['reference']);
        $this->assertNull($report['rows']->first()['url']);
        $this->assertSame('حركة عميل', app(CustomerStatementService::class)->transactionTypeLabel('internal_unknown'));

        $this->actingAs($this->user);
        Livewire::test(CustomerStatement::class)
            ->fillForm(['customer_id' => $this->customer->id])
            ->call('runReport')
            ->assertHasNoFormErrors()
            ->assertSee('عميل كشف الحساب')
            ->assertSee('MISSING-001')
            ->assertSee('250.00 ج.م')
            ->assertSee('على العميل')
            ->assertSeeHtml('target="_blank"')
            ->assertSeeHtml('rel="noopener noreferrer"')
            ->assertSeeHtml(route('customer-statement.print', ['customer' => $this->customer->id]));
    }

    public function test_print_route_is_authenticated_and_contains_clean_a4_statement(): void
    {
        CompanySetting::current()->update(['company_name' => 'شركة أوكترام']);
        $this->invoice('SI-PRINT', '2026-07-01', 1250);
        $url = route('customer-statement.print', [
            'customer' => $this->customer->id,
            'from_date' => '2026-07-01',
            'to_date' => '2026-07-31',
        ]);

        $this->get($url)->assertRedirect();

        $this->actingAs($this->user);
        $this->get($url)
            ->assertOk()
            ->assertSee('company-document-header', escape: false)
            ->assertSee('كشف حساب عميل')
            ->assertSee('عميل كشف الحساب')
            ->assertSee('01/07/2026')
            ->assertSee('SI-PRINT')
            ->assertSee('1,250.00 ج.م')
            ->assertSee('@page', escape: false)
            ->assertDontSee('fi-sidebar', escape: false)
            ->assertDontSee('Laravel');
    }

    private function invoice(string $number, string $date, float $amount): SalesInvoice
    {
        $invoice = SalesInvoice::create([
            'document_number' => $number,
            'invoice_date' => $date,
            'customer_id' => $this->customer->id,
            'warehouse_id' => $this->warehouse->id,
            'payment_type' => 'cash',
            'discount_amount' => 0,
            'tax_type' => 'none',
            'tax_amount' => 0,
        ]);

        app(PartyTransactionService::class)->replaceDocumentTransaction(
            $this->customer,
            PartyTransaction::TYPE_CUSTOMER_DEBIT,
            $invoice,
            $date,
            $amount,
            0,
            $number,
        );

        return $invoice;
    }

    private function receipt(string $number, string $date, float $amount): ReceiptVoucher
    {
        $voucher = ReceiptVoucher::create([
            'document_number' => $number,
            'treasury_id' => $this->treasury->id,
            'customer_id' => $this->customer->id,
            'date' => $date,
            'amount' => $amount,
            'payment_method' => 'cash',
            'created_by' => $this->user->id,
        ]);

        app(PartyTransactionService::class)->replaceDocumentTransaction(
            $this->customer,
            PartyTransaction::TYPE_CUSTOMER_CREDIT,
            $voucher,
            $date,
            0,
            $amount,
            $number,
        );

        return $voucher;
    }
}
