<?php

namespace Tests\Feature;

use App\Enums\PaymentType;
use App\Filament\Resources\PurchaseInvoices\Pages\CreatePurchaseInvoice;
use App\Filament\Resources\PurchaseInvoices\Pages\EditPurchaseInvoice;
use App\Filament\Resources\PurchaseInvoices\Pages\ListPurchaseInvoices;
use App\Filament\Resources\SalesInvoices\Pages\CreateSalesInvoice;
use App\Filament\Resources\SalesInvoices\Pages\EditSalesInvoice;
use App\Filament\Resources\SalesInvoices\Pages\ListSalesInvoices;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Item;
use App\Models\PartyTransaction;
use App\Models\PurchaseInvoice;
use App\Models\SalesInvoice;
use App\Models\StockBalance;
use App\Models\StockTransaction;
use App\Models\Supplier;
use App\Models\TreasuryTransaction;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\Inventory\InventoryService;
use App\Services\Inventory\PurchaseInvoiceService;
use App\Services\Inventory\SalesInvoiceService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class InvoicePaymentTermsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-07-25 12:00:00');
        $this->createTestSchema();
        DB::table('sales_invoices')->insert([
            'document_number' => 'SAL-LEGACY',
            'electronic_invoice_number' => 1,
            'invoice_date' => '2026-07-01',
            'customer_id' => 999,
            'warehouse_id' => 999,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('purchase_invoices')->insert([
            'code' => 'PUR-LEGACY',
            'supplier_id' => 999,
            'invoice_number' => 'LEGACY',
            'invoice_date' => '2026-07-01',
            'warehouse_id' => 999,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $migration = require database_path(
            'migrations/2026_07_25_000001_add_payment_terms_to_invoice_tables.php',
        );
        $migration->up();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_informational_payment_terms_and_posting_regressions(): void
    {
        $this->actingAs(User::factory()->create());

        [$customer, $supplier, $warehouse, $item] = $this->fixtures();

        $this->assertDatabaseHas('sales_invoices', [
            'document_number' => 'SAL-LEGACY',
            'payment_type' => 'cash',
            'due_date' => null,
        ]);
        $this->assertDatabaseHas('purchase_invoices', [
            'code' => 'PUR-LEGACY',
            'payment_type' => 'cash',
            'due_date' => null,
        ]);

        $this->assertSame('cash', Livewire::test(CreateSalesInvoice::class)->get('data.payment_type'));
        $this->assertSame('cash', Livewire::test(CreatePurchaseInvoice::class)->get('data.payment_type'));

        Livewire::test(CreateSalesInvoice::class)
            ->set('data.payment_type', 'credit')
            ->set('data.due_date', '2026-08-01')
            ->set('data.payment_type', 'cash')
            ->assertSet('data.due_date', null);
        Livewire::test(CreatePurchaseInvoice::class)
            ->set('data.payment_type', 'credit')
            ->set('data.due_date', '2026-08-01')
            ->set('data.payment_type', 'cash')
            ->assertSet('data.due_date', null);

        $this->assertInvalidPaymentTerms($customer, $warehouse);
        $this->assertInvalidPurchasePaymentTerms($supplier, $warehouse);

        $cashSale = SalesInvoice::create([
            'electronic_invoice_number' => 500,
            'invoice_date' => '2026-07-20',
            'customer_id' => $customer->getKey(),
            'warehouse_id' => $warehouse->getKey(),
            'payment_type' => PaymentType::Cash,
            'due_date' => '2026-08-01',
        ]);
        $this->assertNull($cashSale->fresh()->due_date);
        $this->assertSame(SalesInvoice::DUE_STATUS_CASH, $cashSale->fresh()->dueStatus());

        $upcomingSale = $this->salesInvoice($customer, $warehouse, 501, '2026-07-26');
        $todaySale = $this->salesInvoice($customer, $warehouse, 502, '2026-07-25');
        $overdueSale = $this->salesInvoice($customer, $warehouse, 503, '2026-07-24');

        $this->assertSame(SalesInvoice::DUE_STATUS_UPCOMING, $upcomingSale->dueStatus());
        $this->assertSame(SalesInvoice::DUE_STATUS_TODAY, $todaySale->dueStatus());
        $this->assertSame(SalesInvoice::DUE_STATUS_OVERDUE, $overdueSale->dueStatus());
        $this->assertSame([$upcomingSale->getKey()], SalesInvoice::query()
            ->dueStatus(SalesInvoice::DUE_STATUS_UPCOMING)->pluck('id')->all());
        $this->assertSame([$todaySale->getKey()], SalesInvoice::query()
            ->dueStatus(SalesInvoice::DUE_STATUS_TODAY)->pluck('id')->all());
        $this->assertSame([$overdueSale->getKey()], SalesInvoice::query()
            ->dueStatus(SalesInvoice::DUE_STATUS_OVERDUE)->pluck('id')->all());
        Livewire::test(ListSalesInvoices::class)
            ->filterTable('payment_type', 'credit')
            ->assertCanSeeTableRecords([$upcomingSale, $todaySale, $overdueSale])
            ->assertCanNotSeeTableRecords([$cashSale])
            ->filterTable('overdue')
            ->assertCanSeeTableRecords([$overdueSale])
            ->assertCanNotSeeTableRecords([$upcomingSale, $todaySale]);

        $creditPurchase = $this->purchaseInvoice($supplier, $warehouse, 'SUP-TERM-1', '2026-07-26');
        $todayPurchase = $this->purchaseInvoice($supplier, $warehouse, 'SUP-TERM-2', '2026-07-25');
        $overduePurchase = $this->purchaseInvoice($supplier, $warehouse, 'SUP-TERM-3', '2026-07-24');
        $this->assertSame(PurchaseInvoice::DUE_STATUS_UPCOMING, $creditPurchase->dueStatus());
        $this->assertSame(PurchaseInvoice::DUE_STATUS_TODAY, $todayPurchase->dueStatus());
        $this->assertSame(PurchaseInvoice::DUE_STATUS_OVERDUE, $overduePurchase->dueStatus());
        $this->assertSame([$overduePurchase->getKey()], PurchaseInvoice::query()
            ->dueStatus(PurchaseInvoice::DUE_STATUS_OVERDUE)->pluck('id')->all());
        Livewire::test(ListPurchaseInvoices::class)
            ->filterTable('payment_type', 'credit')
            ->assertCanSeeTableRecords([$creditPurchase, $todayPurchase, $overduePurchase])
            ->filterTable('overdue')
            ->assertCanSeeTableRecords([$overduePurchase])
            ->assertCanNotSeeTableRecords([$creditPurchase, $todayPurchase]);

        Livewire::test(EditSalesInvoice::class, ['record' => $upcomingSale->getKey()])
            ->assertSet('data.payment_type', 'credit')
            ->assertSet('data.due_date', '2026-07-26 00:00:00');
        Livewire::test(EditPurchaseInvoice::class, ['record' => $creditPurchase->getKey()])
            ->assertSet('data.payment_type', 'credit')
            ->assertSet('data.due_date', '2026-07-26');

        $this->assertPostingRemainsUnchanged($customer, $supplier, $warehouse, $item);
    }

    private function assertInvalidPaymentTerms(Customer $customer, Warehouse $warehouse): void
    {
        foreach ([
            [],
            ['payment_type' => 'invalid'],
            ['payment_type' => 'credit'],
            ['payment_type' => 'credit', 'due_date' => '2026-07-24'],
        ] as $terms) {
            try {
                SalesInvoice::create([
                    'electronic_invoice_number' => random_int(600, 900),
                    'invoice_date' => '2026-07-25',
                    'customer_id' => $customer->getKey(),
                    'warehouse_id' => $warehouse->getKey(),
                    ...$terms,
                ]);
                $this->fail('Invalid payment terms should be rejected.');
            } catch (ValidationException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    private function assertInvalidPurchasePaymentTerms(
        Supplier $supplier,
        Warehouse $warehouse,
    ): void {
        foreach ([
            [],
            ['payment_type' => 'invalid'],
            ['payment_type' => 'credit'],
            ['payment_type' => 'credit', 'due_date' => '2026-07-24'],
        ] as $index => $terms) {
            try {
                PurchaseInvoice::create([
                    'supplier_id' => $supplier->getKey(),
                    'invoice_number' => "INVALID-{$index}",
                    'invoice_date' => '2026-07-25',
                    'warehouse_id' => $warehouse->getKey(),
                    ...$terms,
                ]);
                $this->fail('Invalid purchase payment terms should be rejected.');
            } catch (ValidationException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    private function assertPostingRemainsUnchanged(
        Customer $customer,
        Supplier $supplier,
        Warehouse $warehouse,
        Item $item,
    ): void {
        app(InventoryService::class)->replaceDocumentTransactions('OPEN-PAYMENT-TERMS', [[
            'warehouse_id' => $warehouse->getKey(),
            'item_id' => $item->getKey(),
            'transaction_type' => StockTransaction::TYPE_OPENING,
            'quantity' => 10,
            'unit_cost' => 50,
            'transaction_date' => '2026-07-01',
            'notes' => null,
        ]]);

        $sale = app(SalesInvoiceService::class)->create([
            'electronic_invoice_number' => 950,
            'invoice_date' => '2026-07-20',
            'customer_id' => $customer->getKey(),
            'warehouse_id' => $warehouse->getKey(),
            'payment_type' => 'credit',
            'due_date' => '2026-08-01',
            'items' => [[
                'item_id' => $item->getKey(),
                'quantity' => 2,
                'unit_price' => 100,
            ]],
        ]);
        $this->assertSame(8.0, $this->balance($warehouse, $item));
        $this->assertSame(1, PartyTransaction::query()
            ->where('source_type', $sale->getMorphClass())
            ->where('source_id', $sale->getKey())
            ->count());
        $this->assertDatabaseHas('party_transactions', [
            'source_type' => $sale->getMorphClass(),
            'source_id' => $sale->getKey(),
            'transaction_type' => PartyTransaction::TYPE_CUSTOMER_DEBIT,
            'debit' => 200,
            'credit' => 0,
        ]);

        $purchase = PurchaseInvoice::create([
            'supplier_id' => $supplier->getKey(),
            'invoice_number' => 'SUP-POST-1',
            'invoice_date' => '2026-07-20',
            'warehouse_id' => $warehouse->getKey(),
            'payment_type' => 'cash',
        ]);
        $purchase->items()->create([
            'item_id' => $item->getKey(),
            'quantity' => 2,
            'unit_cost' => 100,
            'total_cost' => 200,
        ]);
        app(PurchaseInvoiceService::class)->post($purchase);

        $this->assertSame(10.0, $this->balance($warehouse, $item));
        $this->assertEqualsWithDelta(
            60.0,
            (float) StockBalance::query()
                ->where('warehouse_id', $warehouse->getKey())
                ->where('item_id', $item->getKey())
                ->value('average_cost'),
            0.01,
        );
        $this->assertSame(1, PartyTransaction::query()
            ->where('source_type', $purchase->getMorphClass())
            ->where('source_id', $purchase->getKey())
            ->count());
        $this->assertSame(0, TreasuryTransaction::query()->count());
    }

    private function salesInvoice(
        Customer $customer,
        Warehouse $warehouse,
        int $electronicNumber,
        string $dueDate,
    ): SalesInvoice {
        $invoice = SalesInvoice::create([
            'electronic_invoice_number' => $electronicNumber,
            'invoice_date' => '2026-07-20',
            'customer_id' => $customer->getKey(),
            'warehouse_id' => $warehouse->getKey(),
            'payment_type' => 'credit',
            'due_date' => $dueDate,
        ]);
        $invoice->items()->create([
            'item_id' => Item::query()->firstOrFail()->getKey(),
            'quantity' => 1,
            'unit_price' => 100,
            'line_total' => 100,
        ]);

        return $invoice;
    }

    private function purchaseInvoice(
        Supplier $supplier,
        Warehouse $warehouse,
        string $invoiceNumber,
        string $dueDate,
    ): PurchaseInvoice {
        $invoice = PurchaseInvoice::create([
            'supplier_id' => $supplier->getKey(),
            'invoice_number' => $invoiceNumber,
            'invoice_date' => '2026-07-20',
            'warehouse_id' => $warehouse->getKey(),
            'payment_type' => 'credit',
            'due_date' => $dueDate,
        ]);
        $invoice->items()->create([
            'item_id' => Item::query()->firstOrFail()->getKey(),
            'quantity' => 1,
            'unit_cost' => 100,
            'total_cost' => 100,
        ]);

        return $invoice;
    }

    /**
     * @return array{Customer, Supplier, Warehouse, Item}
     */
    private function fixtures(): array
    {
        $customer = Customer::create(['name' => 'عميل شروط التعامل', 'active' => true]);
        $supplier = Supplier::create(['name' => 'مورد شروط التعامل', 'active' => true]);
        $warehouse = Warehouse::create(['name' => 'مخزن شروط التعامل', 'active' => true]);
        $category = Category::create(['name' => 'تصنيف شروط التعامل', 'active' => true]);
        $unit = Unit::create([
            'name' => 'قطعة',
            'short_name' => 'قطعة',
            'active' => true,
        ]);
        $item = Item::create([
            'name' => 'صنف شروط التعامل',
            'category_id' => $category->getKey(),
            'unit_id' => $unit->getKey(),
            'active' => true,
        ]);

        return [$customer, $supplier, $warehouse, $item];
    }

    private function balance(Warehouse $warehouse, Item $item): float
    {
        return (float) StockBalance::query()
            ->where('warehouse_id', $warehouse->getKey())
            ->where('item_id', $item->getKey())
            ->value('quantity');
    }

    private function createTestSchema(): void
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
        foreach (['customers', 'suppliers'] as $tableName) {
            Schema::create($tableName, function (Blueprint $table): void {
                $table->id();
                $table->string('code')->unique();
                $table->string('name');
                $table->boolean('active')->default(true);
                $table->timestamps();
            });
        }
        Schema::create('warehouses', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
        Schema::create('categories', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
        Schema::create('units', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('short_name', 20);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
        Schema::create('items', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->unsignedBigInteger('category_id');
            $table->unsignedBigInteger('unit_id');
            $table->decimal('sale_price', 15, 2)->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
        Schema::create('sales_invoices', function (Blueprint $table): void {
            $table->id();
            $table->string('document_number')->unique();
            $table->unsignedBigInteger('electronic_invoice_number');
            $table->date('invoice_date');
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('warehouse_id');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
        Schema::create('purchase_invoices', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->unique();
            $table->unsignedBigInteger('supplier_id');
            $table->string('invoice_number');
            $table->date('invoice_date');
            $table->unsignedBigInteger('warehouse_id');
            $table->text('notes')->nullable();
            $table->boolean('posted')->default(false);
            $table->timestamps();
        });
        Schema::create('sales_invoice_items', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('sales_invoice_id');
            $table->unsignedBigInteger('item_id');
            $table->decimal('quantity', 15, 2);
            $table->decimal('unit_price', 15, 2);
            $table->decimal('line_total', 15, 2);
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
        Schema::create('stock_transactions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('warehouse_id');
            $table->unsignedBigInteger('item_id');
            $table->string('transaction_type');
            $table->decimal('quantity', 15, 2);
            $table->decimal('unit_cost', 15, 2)->default(0);
            $table->date('transaction_date');
            $table->string('reference_no')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
        Schema::create('stock_balances', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('warehouse_id');
            $table->unsignedBigInteger('item_id');
            $table->decimal('quantity', 15, 2)->default(0);
            $table->decimal('average_cost', 15, 2)->default(0);
            $table->timestamps();
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
            $table->unsignedBigInteger('created_by');
            $table->timestamps();
        });
        Schema::create('receipt_voucher_allocations', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('receipt_voucher_id');
            $table->unsignedBigInteger('sales_invoice_id');
            $table->decimal('amount', 15, 2);
            $table->timestamps();
        });
    }
}
