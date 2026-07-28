<?php

namespace Tests\Feature;

use App\Filament\Resources\CashPaymentVouchers\CashPaymentVoucherResource;
use App\Filament\Resources\CashPaymentVouchers\Pages\EditCashPaymentVoucher;
use App\Models\PartyTransaction;
use App\Models\Supplier;
use App\Models\SupplierPaymentVoucher;
use App\Models\Treasury;
use App\Models\TreasuryTransaction;
use App\Models\User;
use App\Services\SupplierPaymentVoucherService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CashPaymentVoucherTest extends TestCase
{
    use RefreshDatabase;

    public function test_general_and_supplier_cash_payments_use_one_posting_flow(): void
    {
        $this->actingAs(User::factory()->create());
        $supplier = Supplier::create(['name' => 'مورد الاختبار', 'active' => true]);
        $treasury = Treasury::create([
            'name' => 'الخزينة الرئيسية',
            'opening_balance' => 1000,
            'is_active' => true,
        ]);
        $service = app(SupplierPaymentVoucherService::class);

        $general = $service->create([
            'voucher_date' => '2026-08-01',
            'payment_type' => SupplierPaymentVoucher::TYPE_GENERAL,
            'supplier_id' => $supplier->getKey(),
            'treasury_id' => $treasury->getKey(),
            'payment_method' => 'cash',
            'amount' => 100,
            'payment_reason' => SupplierPaymentVoucher::REASON_OPERATING_EXPENSE,
            'beneficiary_name' => 'جهة الاختبار',
        ]);

        $this->assertNull($general->supplier_id);
        $this->assertSame(1, $this->treasuryTransactions($general));
        $this->assertSame(0, $this->partyTransactions($general));

        $supplierVoucher = $service->create([
            'voucher_date' => '2026-08-01',
            'payment_type' => SupplierPaymentVoucher::TYPE_SUPPLIER,
            'supplier_id' => $supplier->getKey(),
            'treasury_id' => $treasury->getKey(),
            'payment_method' => 'cash',
            'amount' => 150,
        ]);

        $this->assertSame(1, $this->treasuryTransactions($supplierVoucher));
        $this->assertSame(1, $this->partyTransactions($supplierVoucher));
        $this->assertDatabaseHas('party_transactions', [
            'source_id' => $supplierVoucher->getKey(),
            'debit' => 150,
            'credit' => 0,
        ]);

        $service->update($supplierVoucher, [
            'voucher_date' => '2026-08-02',
            'payment_type' => SupplierPaymentVoucher::TYPE_GENERAL,
            'treasury_id' => $treasury->getKey(),
            'payment_method' => 'cash',
            'amount' => 125,
            'payment_reason' => SupplierPaymentVoucher::REASON_OTHER,
        ]);

        $this->assertSame(1, $this->treasuryTransactions($supplierVoucher));
        $this->assertSame(0, $this->partyTransactions($supplierVoucher));
        $this->assertSame('الخزينة', CashPaymentVoucherResource::getNavigationGroup());
        $this->assertSame('سندات صرف النقدية', CashPaymentVoucherResource::getNavigationLabel());
    }

    public function test_print_is_authenticated_and_action_opens_a_new_tab(): void
    {
        $this->get('/admin/cash-payment-vouchers/1/print')->assertRedirect();

        $this->actingAs(User::factory()->create());
        $treasury = Treasury::create(['name' => 'خزينة', 'opening_balance' => 500, 'is_active' => true]);
        $voucher = app(SupplierPaymentVoucherService::class)->create([
            'voucher_date' => '2026-08-01',
            'payment_type' => SupplierPaymentVoucher::TYPE_GENERAL,
            'treasury_id' => $treasury->getKey(),
            'payment_method' => 'cash',
            'amount' => 50,
            'beneficiary_name' => 'المستفيد',
            'payment_reason' => SupplierPaymentVoucher::REASON_OTHER,
        ]);

        $this->get(route('cash-payment-vouchers.print', $voucher))
            ->assertOk()
            ->assertSee($voucher->document_number)
            ->assertSee('المستفيد');

        Livewire::test(EditCashPaymentVoucher::class, ['record' => $voucher->getRouteKey()])
            ->assertActionExists('print', fn ($action): bool => $action->shouldOpenUrlInNewTab());
    }

    private function treasuryTransactions(SupplierPaymentVoucher $voucher): int
    {
        return TreasuryTransaction::where('source_type', $voucher->getMorphClass())
            ->where('source_id', $voucher->getKey())->count();
    }

    private function partyTransactions(SupplierPaymentVoucher $voucher): int
    {
        return PartyTransaction::where('source_type', $voucher->getMorphClass())
            ->where('source_id', $voucher->getKey())->count();
    }
}
