<?php

namespace Tests\Feature;

use App\Filament\Resources\CashReceiptVouchers\CashReceiptVoucherResource;
use App\Filament\Resources\CashReceiptVouchers\Pages\CreateCashReceiptVoucher;
use App\Filament\Resources\CashReceiptVouchers\Pages\EditCashReceiptVoucher;
use App\Filament\Resources\CashReceiptVouchers\Pages\ListCashReceiptVouchers;
use App\Filament\Resources\ReceiptVouchers\Pages\ListReceiptVouchers;
use App\Models\Customer;
use App\Models\PartyTransaction;
use App\Models\ReceiptVoucher;
use App\Models\Treasury;
use App\Models\TreasuryTransaction;
use App\Models\User;
use App\Services\CustomerStatementService;
use App\Services\ReceiptVoucherService;
use App\Services\TreasuryTransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class CashReceiptVoucherTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Customer $customer;

    private Treasury $treasury;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
        $this->customer = Customer::create(['code' => 'CUS-CASH', 'name' => 'عميل النقدية', 'active' => true]);
        $this->treasury = Treasury::create([
            'code' => 'TR-CASH', 'name' => 'الخزينة الرئيسية', 'type' => 'cash',
            'opening_balance' => 0, 'active' => true,
        ]);
    }

    public function test_navigation_access_form_validation_and_legacy_default(): void
    {
        $this->assertSame('الخزينة', CashReceiptVoucherResource::getNavigationGroup());
        $this->assertSame('سندات استلام النقدية', CashReceiptVoucherResource::getNavigationLabel());
        Livewire::test(ListCashReceiptVouchers::class)->assertOk();
        Livewire::test(CreateCashReceiptVoucher::class)
            ->fillForm([
                'receipt_type' => ReceiptVoucher::TYPE_CUSTOMER,
                'date' => '2026-07-27',
                'amount' => 0,
            ])
            ->call('create')
            ->assertHasFormErrors(['treasury_id', 'customer_id', 'amount']);

        $legacy = ReceiptVoucher::create([
            'document_number' => 'REC-LEGACY', 'treasury_id' => $this->treasury->id,
            'customer_id' => $this->customer->id, 'date' => '2026-07-27', 'amount' => 10,
            'payment_method' => 'cash', 'created_by' => $this->user->id,
        ]);
        $this->assertTrue($legacy->fresh()->isCustomerReceipt());
        $this->assertSame('استلام من عميل', $legacy->receipt_type_label);

        $this->expectException(ValidationException::class);
        app(ReceiptVoucherService::class)->create([
            'receipt_type' => 'unsupported', 'treasury_id' => $this->treasury->id,
            'date' => '2026-07-27', 'amount' => 100, 'payment_method' => 'cash',
        ]);
    }

    public function test_customer_and_general_receipts_post_exactly_once_and_appear_in_correct_places(): void
    {
        $service = app(ReceiptVoucherService::class);
        $customerVoucher = $service->create($this->payload());

        $this->assertSame(1, TreasuryTransaction::query()->whereMorphedTo('source', $customerVoucher)->count());
        $this->assertSame(1, PartyTransaction::query()->whereMorphedTo('source', $customerVoucher)->count());
        $this->assertSame(500.0, app(TreasuryTransactionService::class)->getBalance($this->treasury));
        $this->assertSame(-500.0, app(CustomerStatementService::class)
            ->report($this->customer->id)['closingBalance']);

        $generalVoucher = $service->create([
            ...$this->payload(),
            'receipt_type' => ReceiptVoucher::TYPE_GENERAL,
            'customer_id' => $this->customer->id,
            'amount' => 300,
            'payer_name' => 'جهة عامة',
            'receipt_reason' => ReceiptVoucher::REASON_CAPITAL,
        ]);

        $this->assertNull($generalVoucher->customer_id);
        $this->assertSame(1, TreasuryTransaction::query()->whereMorphedTo('source', $generalVoucher)->count());
        $this->assertSame(0, PartyTransaction::query()->whereMorphedTo('source', $generalVoucher)->count());
        $this->assertSame(800.0, app(TreasuryTransactionService::class)->getBalance($this->treasury));
        $this->assertSame(-500.0, app(CustomerStatementService::class)
            ->report($this->customer->id)['closingBalance']);

        Livewire::test(ListCashReceiptVouchers::class)
            ->assertCanSeeTableRecords([$customerVoucher, $generalVoucher]);
        Livewire::test(ListReceiptVouchers::class)
            ->assertCanSeeTableRecords([$customerVoucher])
            ->assertCanNotSeeTableRecords([$generalVoucher]);
    }

    public function test_edit_type_customer_and_treasury_replaces_effects_without_duplicates_and_delete_cleans_up(): void
    {
        $service = app(ReceiptVoucherService::class);
        $voucher = $service->create($this->payload());
        $otherCustomer = Customer::create(['code' => 'CUS-OTHER', 'name' => 'عميل آخر', 'active' => true]);
        $otherTreasury = Treasury::create([
            'code' => 'TR-OTHER', 'name' => 'خزينة أخرى', 'type' => 'cash',
            'opening_balance' => 0, 'active' => true,
        ]);

        $number = $voucher->document_number;
        $voucher = $service->update($voucher, [
            ...$this->payload(),
            'customer_id' => $otherCustomer->id,
            'treasury_id' => $otherTreasury->id,
            'amount' => 700,
        ]);
        $this->assertSame($number, $voucher->document_number);
        $this->assertSame(0.0, app(TreasuryTransactionService::class)->getBalance($this->treasury));
        $this->assertSame(700.0, app(TreasuryTransactionService::class)->getBalance($otherTreasury));
        $this->assertSame(1, TreasuryTransaction::query()->whereMorphedTo('source', $voucher)->count());
        $this->assertSame(1, PartyTransaction::query()->whereMorphedTo('source', $voucher)->count());

        $voucher = $service->update($voucher, [
            ...$this->payload(),
            'receipt_type' => ReceiptVoucher::TYPE_GENERAL,
            'treasury_id' => $otherTreasury->id,
            'amount' => 250,
            'payer_name' => 'دافع عام',
        ]);
        $this->assertSame(0, PartyTransaction::query()->whereMorphedTo('source', $voucher)->count());
        $this->assertSame(250.0, app(TreasuryTransactionService::class)->getBalance($otherTreasury));

        $service->delete($voucher);
        $this->assertDatabaseMissing('receipt_vouchers', ['id' => $voucher->id]);
        $this->assertSame(0, TreasuryTransaction::query()->whereMorphedTo('source', $voucher)->count());
        $this->assertSame(0.0, app(TreasuryTransactionService::class)->getBalance($otherTreasury));
    }

    public function test_print_is_authenticated_clean_and_edit_action_opens_new_tab(): void
    {
        $voucher = app(ReceiptVoucherService::class)->create($this->payload());
        $url = route('cash-receipt-vouchers.print', $voucher);

        auth()->logout();
        $this->get($url)->assertRedirect();
        $this->actingAs($this->user);
        $this->get($url)
            ->assertOk()
            ->assertSee('company-document-header', escape: false)
            ->assertSee($voucher->document_number)
            ->assertSee('عميل النقدية')
            ->assertSee('500.00 ج.م')
            ->assertDontSee('fi-sidebar', escape: false)
            ->assertDontSee('Laravel');

        Livewire::test(EditCashReceiptVoucher::class, ['record' => $voucher->id])
            ->assertActionExists('print', fn ($action): bool => $action->shouldOpenUrlInNewTab());
    }

    /** @return array<string, mixed> */
    private function payload(): array
    {
        return [
            'receipt_type' => ReceiptVoucher::TYPE_CUSTOMER,
            'treasury_id' => $this->treasury->id,
            'customer_id' => $this->customer->id,
            'date' => '2026-07-27',
            'amount' => 500,
            'payment_method' => 'cash',
            'reference_number' => 'REF-001',
            'notes' => 'استلام نقدي',
        ];
    }
}
