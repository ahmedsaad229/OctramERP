<?php

namespace Tests\Feature;

use App\Filament\Resources\SupplierPaymentVouchers\Pages\CreateSupplierPaymentVoucher;
use App\Filament\Resources\SupplierPaymentVouchers\Pages\ListSupplierPaymentVouchers;
use App\Filament\Resources\SupplierPaymentVouchers\Schemas\SupplierPaymentVoucherForm;
use App\Models\PartyTransaction;
use App\Models\PurchaseInvoice;
use App\Models\Supplier;
use App\Models\SupplierPaymentVoucher;
use App\Models\Treasury;
use App\Models\TreasuryTransaction;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\PartyTransactionService;
use App\Services\SupplierPaymentVoucherService;
use App\Services\TreasuryTransactionService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class SupplierPaymentVoucherTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-07-26 12:00:00');
        $this->createSchema();
        $migration = require database_path(
            'migrations/2026_07_26_000001_create_supplier_payment_vouchers.php',
        );
        $migration->up();
        $generalization = require database_path(
            'migrations/2026_08_01_000002_generalize_supplier_payment_vouchers_for_cash_payments.php',
        );
        $generalization->up();
        $this->actingAs(User::factory()->create());
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_supplier_payment_voucher_full_lifecycle(): void
    {
        [$supplier, $otherSupplier, $treasury, $warehouse] = $this->fixtures();
        $invoice = $this->invoice($supplier, $warehouse, 'SUP-INV-001', 1000, 'credit');
        $secondInvoice = $this->invoice($supplier, $warehouse, 'SUP-INV-002', 600, 'cash');
        $otherInvoice = $this->invoice($otherSupplier, $warehouse, 'OTHER-INV-001', 500, 'cash');

        $initialOptions = SupplierPaymentVoucherForm::invoiceOptions($supplier->getKey());
        $this->assertArrayHasKey($invoice->getKey(), $initialOptions);
        $this->assertArrayHasKey($secondInvoice->getKey(), $initialOptions);
        $this->assertStringContainsString(
            "فاتورة SUP-INV-001 — {$invoice->code} — المتبقي: 1,000.00 ج.م",
            $initialOptions[$invoice->getKey()],
        );
        $this->assertStringContainsString(
            "فاتورة SUP-INV-002 — {$secondInvoice->code} — المتبقي: 600.00 ج.م",
            $initialOptions[$secondInvoice->getKey()],
        );
        $this->assertArrayNotHasKey($otherInvoice->getKey(), $initialOptions);

        Livewire::test(CreateSupplierPaymentVoucher::class)
            ->set('data.supplier_id', $supplier->getKey())
            ->assertSee('SUP-INV-001')
            ->assertSee('SUP-INV-002')
            ->set('data.purchase_invoice_id', $invoice->getKey())
            ->set('data.supplier_id', $otherSupplier->getKey())
            ->assertSet('data.purchase_invoice_id', null)
            ->assertSee('OTHER-INV-001')
            ->assertDontSee('SUP-INV-001')
            ->assertDontSee('SUP-INV-002');

        app(PartyTransactionService::class)->replaceDocumentTransaction(
            $supplier,
            PartyTransaction::TYPE_PURCHASE_INVOICE,
            $invoice,
            $invoice->invoice_date,
            0,
            $invoice->totalAmount(),
            $invoice->code,
        );

        $component = Livewire::test(CreateSupplierPaymentVoucher::class)
            ->set('data.voucher_date', '2026-07-10')
            ->set('data.supplier_id', $supplier->getKey())
            ->set('data.treasury_id', $treasury->getKey())
            ->set('data.payment_method', 'cash')
            ->set('data.amount', 250)
            ->set('data.purchase_invoice_id', $invoice->getKey())
            ->assertSee('1,000.00 ج.م')
            ->assertSee('750.00 ج.م')
            ->call('create')
            ->assertHasNoErrors();

        $firstVoucher = SupplierPaymentVoucher::query()->firstOrFail();
        $this->assertStringStartsWith('PAY-', $firstVoucher->document_number);
        $this->assertSame(1, $firstVoucher->allocations()->count());
        $this->assertDatabaseHas('supplier_payment_voucher_allocations', [
            'supplier_payment_voucher_id' => $firstVoucher->getKey(),
            'purchase_invoice_id' => $invoice->getKey(),
            'amount' => 250,
        ]);
        $this->assertPosted($firstVoucher, 250);
        $this->assertSame(750.0, app(TreasuryTransactionService::class)->getBalance($treasury));
        $this->assertSame(750.0, $this->supplierPayable($supplier));
        $this->assertSame('partially_paid', $invoice->paymentStatus());
        $this->assertSame('مدفوعة جزئيًا', $invoice->paymentStatusLabel());

        $secondVoucher = app(SupplierPaymentVoucherService::class)->create([
            'voucher_date' => '2026-07-11',
            'supplier_id' => $supplier->getKey(),
            'treasury_id' => $treasury->getKey(),
            'payment_method' => 'bank_transfer',
            'amount' => 300,
            'purchase_invoice_id' => $invoice->getKey(),
        ]);

        $this->assertSame(0.0, $invoice->previouslyPaidBeforeSupplierPayment($firstVoucher));
        $this->assertSame(250.0, $invoice->previouslyPaidBeforeSupplierPayment($secondVoucher));
        $this->assertSame(750.0, $invoice->remainingBeforeSupplierPayment($secondVoucher));
        $this->assertSame([
            'invoice_total' => 1000.0,
            'previously_paid' => 250.0,
            'current_payment' => 300.0,
            'remaining_after_payment' => 450.0,
        ], $secondVoucher->fresh([
            'allocations.purchaseInvoice.items',
        ])->paymentSummaryBefore());

        $sameDayVoucher = app(SupplierPaymentVoucherService::class)->create([
            'voucher_date' => '2026-07-11',
            'supplier_id' => $supplier->getKey(),
            'treasury_id' => $treasury->getKey(),
            'payment_method' => 'cheque',
            'amount' => 100,
            'purchase_invoice_id' => $invoice->getKey(),
        ]);
        $this->assertSame(550.0, $invoice->previouslyPaidBeforeSupplierPayment($sameDayVoucher));

        $firstTreasuryTransactionId = TreasuryTransaction::query()
            ->where('source_type', $firstVoucher->getMorphClass())
            ->where('source_id', $firstVoucher->getKey())
            ->value('id');
        $firstPartyTransactionId = PartyTransaction::query()
            ->where('source_type', $firstVoucher->getMorphClass())
            ->where('source_id', $firstVoucher->getKey())
            ->value('id');

        $firstVoucher = app(SupplierPaymentVoucherService::class)->update($firstVoucher, [
            'voucher_date' => '2026-07-12',
            'supplier_id' => $supplier->getKey(),
            'treasury_id' => $treasury->getKey(),
            'payment_method' => 'card',
            'amount' => 200,
            'purchase_invoice_id' => $secondInvoice->getKey(),
            'notes' => 'تحويل الدفعة إلى فاتورة أخرى',
        ]);

        $this->assertDatabaseMissing('treasury_transactions', ['id' => $firstTreasuryTransactionId]);
        $this->assertDatabaseMissing('party_transactions', ['id' => $firstPartyTransactionId]);
        $this->assertDatabaseMissing('supplier_payment_voucher_allocations', [
            'supplier_payment_voucher_id' => $firstVoucher->getKey(),
            'purchase_invoice_id' => $invoice->getKey(),
        ]);
        $this->assertDatabaseHas('supplier_payment_voucher_allocations', [
            'supplier_payment_voucher_id' => $firstVoucher->getKey(),
            'purchase_invoice_id' => $secondInvoice->getKey(),
            'amount' => 200,
        ]);
        $this->assertPosted($firstVoucher, 200);
        $this->assertSame(0.0, $invoice->previouslyPaidBeforeSupplierPayment($secondVoucher));

        $finalVoucher = app(SupplierPaymentVoucherService::class)->create([
            'voucher_date' => '2026-07-13',
            'supplier_id' => $supplier->getKey(),
            'treasury_id' => $treasury->getKey(),
            'payment_method' => 'other',
            'amount' => 400,
            'purchase_invoice_id' => $secondInvoice->getKey(),
        ]);
        $this->assertSame('fully_paid', $secondInvoice->paymentStatus());
        $this->assertSame('مدفوعة بالكامل', $secondInvoice->paymentStatusLabel());
        $this->assertSame('unpaid', $otherInvoice->paymentStatus());
        $this->assertSame('غير مدفوعة', $otherInvoice->paymentStatusLabel());

        $options = SupplierPaymentVoucherForm::invoiceOptions($supplier->getKey());
        $this->assertArrayNotHasKey($secondInvoice->getKey(), $options);
        $this->assertArrayHasKey(
            $secondInvoice->getKey(),
            SupplierPaymentVoucherForm::invoiceOptions(
                $supplier->getKey(),
                $finalVoucher->getKey(),
            ),
        );
        $this->assertArrayNotHasKey(
            $otherInvoice->getKey(),
            SupplierPaymentVoucherForm::invoiceOptions($supplier->getKey()),
        );

        $this->assertInvalidCreate([
            'voucher_date' => '2026-07-14',
            'supplier_id' => $supplier->getKey(),
            'treasury_id' => $treasury->getKey(),
            'payment_method' => 'cash',
            'amount' => 50,
            'purchase_invoice_id' => $otherInvoice->getKey(),
        ], 'data.purchase_invoice_id');
        $this->assertInvalidCreate([
            'voucher_date' => '2026-07-14',
            'supplier_id' => $supplier->getKey(),
            'treasury_id' => $treasury->getKey(),
            'payment_method' => 'cash',
            'amount' => 1000,
            'purchase_invoice_id' => $invoice->getKey(),
        ], 'data.amount');
        foreach ([0, -10] as $invalidAmount) {
            $this->assertInvalidCreate([
                'voucher_date' => '2026-07-14',
                'supplier_id' => $supplier->getKey(),
                'treasury_id' => $treasury->getKey(),
                'payment_method' => 'cash',
                'amount' => $invalidAmount,
                'purchase_invoice_id' => $invoice->getKey(),
            ], 'data.amount');
        }

        Livewire::test(ListSupplierPaymentVouchers::class)
            ->assertSee('رقم فاتورة المورد')
            ->assertSee('رقم المستند الداخلي')
            ->assertTableColumnStateSet('purchase_invoice_number', 'SUP-INV-001', $secondVoucher)
            ->assertTableColumnStateSet('purchase_invoice_code', $invoice->code, $secondVoucher)
            ->assertTableColumnStateSet('previously_paid', '0.00 ج.م', $secondVoucher)
            ->assertTableColumnStateSet('remaining_after_payment', '700.00 ج.م', $secondVoucher)
            ->filterTable('supplier_id', $supplier->getKey())
            ->assertCanSeeTableRecords([$firstVoucher, $secondVoucher, $sameDayVoucher, $finalVoucher])
            ->filterTable('payment_method', 'bank_transfer')
            ->assertCanSeeTableRecords([$secondVoucher])
            ->assertCanNotSeeTableRecords([$firstVoucher, $sameDayVoucher, $finalVoucher]);

        Livewire::test(ListSupplierPaymentVouchers::class)
            ->searchTable('SUP-INV-001')
            ->assertCanSeeTableRecords([$secondVoucher, $sameDayVoucher]);

        $deletingId = $firstVoucher->getKey();
        app(SupplierPaymentVoucherService::class)->delete($firstVoucher);
        $this->assertDatabaseMissing('supplier_payment_vouchers', ['id' => $deletingId]);
        $this->assertDatabaseMissing('supplier_payment_voucher_allocations', [
            'supplier_payment_voucher_id' => $deletingId,
        ]);
        $this->assertDatabaseMissing('treasury_transactions', [
            'source_type' => SupplierPaymentVoucher::class,
            'source_id' => $deletingId,
        ]);
        $this->assertDatabaseMissing('party_transactions', [
            'source_type' => SupplierPaymentVoucher::class,
            'source_id' => $deletingId,
        ]);
    }

    private function assertPosted(SupplierPaymentVoucher $voucher, float $amount): void
    {
        $this->assertDatabaseHas('treasury_transactions', [
            'source_type' => $voucher->getMorphClass(),
            'source_id' => $voucher->getKey(),
            'type' => TreasuryTransaction::TYPE_PAYMENT,
            'direction' => TreasuryTransaction::DIRECTION_CREDIT,
            'amount' => $amount,
        ]);
        $this->assertDatabaseHas('party_transactions', [
            'source_type' => $voucher->getMorphClass(),
            'source_id' => $voucher->getKey(),
            'transaction_type' => PartyTransaction::TYPE_SUPPLIER_PAYMENT,
            'debit' => $amount,
            'credit' => 0,
        ]);
        $this->assertSame(1, TreasuryTransaction::query()
            ->where('source_type', $voucher->getMorphClass())
            ->where('source_id', $voucher->getKey())
            ->count());
        $this->assertSame(1, PartyTransaction::query()
            ->where('source_type', $voucher->getMorphClass())
            ->where('source_id', $voucher->getKey())
            ->count());
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function assertInvalidCreate(array $data, string $errorKey): void
    {
        $voucherCount = SupplierPaymentVoucher::query()->count();
        $treasuryCount = TreasuryTransaction::query()->count();
        $partyCount = PartyTransaction::query()->count();

        try {
            app(SupplierPaymentVoucherService::class)->create($data);
            $this->fail('Invalid supplier payment should have been rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey($errorKey, $exception->errors());
        }

        $this->assertSame($voucherCount, SupplierPaymentVoucher::query()->count());
        $this->assertSame($treasuryCount, TreasuryTransaction::query()->count());
        $this->assertSame($partyCount, PartyTransaction::query()->count());
    }

    private function supplierPayable(Supplier $supplier): float
    {
        return (float) PartyTransaction::query()
            ->where('party_type', $supplier->getMorphClass())
            ->where('party_id', $supplier->getKey())
            ->selectRaw('COALESCE(SUM(credit - debit), 0) as balance')
            ->value('balance');
    }

    private function invoice(
        Supplier $supplier,
        Warehouse $warehouse,
        string $invoiceNumber,
        float $total,
        string $paymentType,
    ): PurchaseInvoice {
        $invoice = PurchaseInvoice::create([
            'supplier_id' => $supplier->getKey(),
            'invoice_number' => $invoiceNumber,
            'invoice_date' => '2026-07-01',
            'warehouse_id' => $warehouse->getKey(),
            'payment_type' => $paymentType,
            'due_date' => $paymentType === 'credit' ? '2026-08-01' : null,
        ]);
        $invoice->items()->create([
            'item_id' => 1,
            'quantity' => 1,
            'unit_cost' => $total,
            'total_cost' => $total,
        ]);

        return $invoice;
    }

    /**
     * @return array{Supplier, Supplier, Treasury, Warehouse}
     */
    private function fixtures(): array
    {
        return [
            Supplier::create(['name' => 'مورد السداد', 'active' => true]),
            Supplier::create(['name' => 'مورد آخر', 'active' => true]),
            Treasury::create([
                'name' => 'الخزينة الرئيسية',
                'opening_balance' => 1000,
                'is_active' => true,
            ]),
            Warehouse::create(['name' => 'المخزن الرئيسي', 'active' => true]),
        ];
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
        Schema::create('document_number_counters', function (Blueprint $table): void {
            $table->id();
            $table->string('document_type')->unique();
            $table->unsignedBigInteger('last_number')->default(0);
            $table->timestamps();
        });
        Schema::create('suppliers', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
        Schema::create('treasuries', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->decimal('opening_balance', 15, 2)->default(0);
            $table->boolean('is_default')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('warehouses', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
        Schema::create('purchase_invoices', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->unique();
            $table->unsignedBigInteger('supplier_id');
            $table->string('invoice_number');
            $table->date('invoice_date');
            $table->unsignedBigInteger('warehouse_id');
            $table->string('payment_type')->default('cash');
            $table->date('due_date')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('posted')->default(false);
            $table->timestamps();
        });
        Schema::create('purchase_invoice_items', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('purchase_invoice_id');
            $table->unsignedBigInteger('item_id');
            $table->decimal('quantity', 15, 2);
            $table->decimal('unit_cost', 15, 2);
            $table->decimal('total_cost', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
        Schema::create('treasury_transactions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('treasury_id');
            $table->date('transaction_date');
            $table->string('type', 50);
            $table->decimal('amount', 15, 2);
            $table->string('direction');
            $table->string('source_type');
            $table->unsignedBigInteger('source_id');
            $table->string('document_number')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->unique(['source_type', 'source_id']);
        });
        Schema::create('party_transactions', function (Blueprint $table): void {
            $table->id();
            $table->string('party_type');
            $table->unsignedBigInteger('party_id');
            $table->string('transaction_type', 50);
            $table->string('source_type')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('reference_no')->nullable();
            $table->date('transaction_date');
            $table->decimal('debit', 15, 2)->default(0);
            $table->decimal('credit', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['source_type', 'source_id']);
        });
    }
}
