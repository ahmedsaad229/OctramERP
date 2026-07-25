<?php

namespace Tests\Feature;

use App\Filament\Resources\ReceiptVouchers\Pages\CreateReceiptVoucher;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Item;
use App\Models\SalesInvoice;
use App\Models\Treasury;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class ReceiptVoucherSummaryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createTestSchema();
    }

    public function test_selecting_a_sales_invoice_binds_its_id_and_creates_the_allocation(): void
    {
        $this->actingAs(User::query()->first() ?? User::factory()->create());

        $customer = Customer::create([
            'name' => 'عميل اختبار ملخص سند القبض',
            'active' => true,
        ]);
        $warehouse = Warehouse::create([
            'name' => 'مخزن اختبار ملخص سند القبض',
            'active' => true,
        ]);
        $treasury = Treasury::create([
            'name' => 'خزينة اختبار سند القبض',
            'opening_balance' => 0,
            'is_active' => true,
        ]);
        $item = $this->item();
        $invoice = SalesInvoice::create([
            'document_number' => 'SAL-TEST-0001',
            'electronic_invoice_number' => 500,
            'invoice_date' => '2026-07-25',
            'customer_id' => $customer->getKey(),
            'warehouse_id' => $warehouse->getKey(),
        ]);
        $invoice->items()->create([
            'item_id' => $item->getKey(),
            'quantity' => 1,
            'unit_price' => 1000,
            'line_total' => 1000,
        ]);

        $component = Livewire::test(CreateReceiptVoucher::class)
            ->set('data.customer_id', $customer->getKey())
            ->set('data.treasury_id', $treasury->getKey())
            ->set('data.date', '2026-07-25');

        $allocationRowKey = array_key_first($component->get('data.allocations'));

        $component
            ->set(
                "data.allocations.{$allocationRowKey}.sales_invoice_id",
                $invoice->getKey(),
            )
            ->assertSet(
                "data.allocations.{$allocationRowKey}.sales_invoice_id",
                $invoice->getKey(),
            )
            ->assertSee($invoice->document_number)
            ->assertSee('2026-07-25')
            ->assertSee('1,000.00')
            ->assertSee('0.00')
            ->set("data.allocations.{$allocationRowKey}.amount", 250)
            ->call('create')
            ->assertHasNoErrors([
                "data.allocations.{$allocationRowKey}.sales_invoice_id",
            ]);

        $this->assertDatabaseHas('receipt_voucher_allocations', [
            'sales_invoice_id' => $invoice->getKey(),
            'amount' => 250,
        ]);
    }

    private function item(): Item
    {
        if ($item = Item::query()->first()) {
            return $item;
        }

        $category = Category::create([
            'name' => 'تصنيف اختبار ملخص سند القبض',
            'active' => true,
        ]);
        $unit = Unit::create([
            'name' => 'وحدة اختبار ملخص سند القبض',
            'active' => true,
        ]);

        return Item::create([
            'name' => 'صنف اختبار ملخص سند القبض',
            'category_id' => $category->getKey(),
            'unit_id' => $unit->getKey(),
            'active' => true,
        ]);
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
        Schema::create('customers', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->nullable();
            $table->string('name');
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
        Schema::create('treasuries', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->nullable();
            $table->string('name');
            $table->decimal('opening_balance', 15, 2)->default(0);
            $table->boolean('is_default')->default(false);
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('warehouses', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->nullable();
            $table->string('name');
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
        Schema::create('categories', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->nullable();
            $table->string('name');
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
        Schema::create('units', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->nullable();
            $table->string('name');
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
        Schema::create('items', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->nullable();
            $table->string('name');
            $table->unsignedBigInteger('category_id');
            $table->unsignedBigInteger('unit_id');
            $table->decimal('sale_price', 15, 2)->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
        Schema::create('sales_invoices', function (Blueprint $table): void {
            $table->id();
            $table->string('document_number');
            $table->unsignedBigInteger('electronic_invoice_number');
            $table->date('invoice_date');
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('warehouse_id');
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
        Schema::create('document_number_counters', function (Blueprint $table): void {
            $table->id();
            $table->string('document_type')->unique();
            $table->unsignedBigInteger('last_number')->default(0);
            $table->timestamps();
        });
        Schema::create('receipt_vouchers', function (Blueprint $table): void {
            $table->id();
            $table->string('document_number')->unique();
            $table->unsignedBigInteger('treasury_id');
            $table->unsignedBigInteger('customer_id');
            $table->date('date');
            $table->decimal('amount', 15, 2);
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
    }
}
