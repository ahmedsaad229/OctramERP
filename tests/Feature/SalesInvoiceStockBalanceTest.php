<?php

namespace Tests\Feature;

use App\Enums\TaxType;
use App\Filament\Resources\SalesInvoices\Pages\CreateSalesInvoice;
use App\Filament\Resources\SalesInvoices\Pages\EditSalesInvoice;
use App\Filament\Resources\StockBalances\Pages\ListStockBalances;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Item;
use App\Models\PartyTransaction;
use App\Models\SalesInvoice;
use App\Models\StockBalance;
use App\Models\StockTransaction;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\Inventory\GoodsIssueService;
use App\Services\Inventory\InventoryService;
use App\Services\Inventory\SalesInvoiceService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class SalesInvoiceStockBalanceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createTestSchema();
    }

    public function test_sales_invoice_stock_display_validation_and_posting_lifecycle(): void
    {
        $this->assertCreateFormReactsToItemAndWarehouseBalances();
        $this->assertEditFormAddsBackTheCurrentInvoiceIssue();
        $this->assertCreateUpdateValidationAndDeleteKeepInventoryOneForOne();
    }

    public function test_sales_vat_is_server_calculated_and_customer_posting_is_replaced(): void
    {
        [$warehouse] = $this->warehouses();
        [$item] = $this->items();
        $customer = $this->customer();
        $this->seedStock($warehouse, $item, 10);
        $service = app(SalesInvoiceService::class);
        $data = [
            'electronic_invoice_number' => 700,
            'payment_type' => 'cash',
            'invoice_date' => '2026-07-26',
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'tax_type' => TaxType::Vat14->value,
            'discount_amount' => 10,
            'tax_amount' => 999999,
            'total' => 1,
            'items' => [['item_id' => $item->id, 'quantity' => 2, 'unit_price' => 100]],
        ];

        $invoice = $service->create($data);

        $this->assertSame(26.6, (float) $invoice->tax_amount);
        $this->assertSame(216.6, $invoice->totalAmount());
        $this->assertSame(8.0, $this->balance($warehouse, $item));
        $this->assertSame(216.6, (float) PartyTransaction::query()
            ->where('source_type', $invoice->getMorphClass())
            ->where('source_id', $invoice->id)
            ->value('debit'));

        $data['tax_type'] = TaxType::None->value;
        $service->update($invoice, $data);

        $this->assertSame(1, PartyTransaction::query()
            ->where('source_type', $invoice->getMorphClass())
            ->where('source_id', $invoice->id)
            ->count());
    }

    public function test_stock_balance_resource_reads_current_balances_and_polls_for_external_postings(): void
    {
        $this->actingAs(User::factory()->create());
        [$warehouse] = $this->warehouses();
        [$item] = $this->items();
        $this->seedStock($warehouse, $item, 7);

        Livewire::test(ListStockBalances::class)
            ->assertOk()
            ->assertSee($item->name)
            ->assertSee('7')
            ->assertSeeHtml('wire:poll.5s');
    }

    public function test_selling_more_than_available_stock_rolls_back_everything(): void
    {
        [$warehouse] = $this->warehouses();
        [$item] = $this->items();
        $customer = $this->customer();
        $this->seedStock($warehouse, $item, 2);

        try {
            app(SalesInvoiceService::class)->create([
                'electronic_invoice_number' => 701,
                'payment_type' => 'cash',
                'invoice_date' => '2026-07-26',
                'customer_id' => $customer->getKey(),
                'warehouse_id' => $warehouse->getKey(),
                'items' => [[
                    'item_id' => $item->getKey(),
                    'quantity' => 3,
                    'unit_price' => 100,
                ]],
            ]);

            $this->fail('The unavailable stock sale should have failed.');
        } catch (ValidationException $exception) {
            $this->assertContains(
                'الكمية المطلوبة غير متوفرة في المخزن.',
                $exception->errors()['items'],
            );
        }

        $this->assertDatabaseCount('sales_invoices', 0);
        $this->assertDatabaseCount('sales_invoice_items', 0);
        $this->assertDatabaseCount('stock_transactions', 1);
        $this->assertSame(2.0, $this->balance($warehouse, $item));
    }

    public function test_goods_issue_more_than_available_stock_rolls_back_everything(): void
    {
        [$warehouse] = $this->warehouses();
        [$item] = $this->items();
        $this->seedStock($warehouse, $item, 2);

        try {
            app(GoodsIssueService::class)->create([
                'voucher_date' => '2026-07-26',
                'warehouse_id' => $warehouse->getKey(),
                'items' => [[
                    'item_id' => $item->getKey(),
                    'quantity' => 3,
                    'unit_cost' => 50,
                ]],
            ]);

            $this->fail('The unavailable stock goods issue should have failed.');
        } catch (ValidationException $exception) {
            $this->assertContains(
                'الكمية المطلوبة غير متوفرة في المخزن.',
                $exception->errors()['items'],
            );
        }

        $this->assertDatabaseCount('goods_issue_vouchers', 0);
        $this->assertDatabaseCount('goods_issue_items', 0);
        $this->assertDatabaseCount('stock_transactions', 1);
        $this->assertSame(2.0, $this->balance($warehouse, $item));
    }

    private function assertCreateFormReactsToItemAndWarehouseBalances(): void
    {
        $this->actingAs(User::factory()->create());

        [$firstWarehouse, $secondWarehouse] = $this->warehouses();
        [$stockedItem, $otherItem] = $this->items();

        $this->seedStock($firstWarehouse, $stockedItem, 25);
        $this->seedStock($secondWarehouse, $stockedItem, 4);
        $this->seedStock($firstWarehouse, $otherItem, 8);

        $component = Livewire::test(CreateSalesInvoice::class)
            ->set('data.warehouse_id', $firstWarehouse->getKey())
            ->assertSeeHtml('type="text"')
            ->assertSeeHtml('inputmode="decimal"')
            ->assertSeeHtml('octram-quantity-input');

        $rowKey = array_key_first($component->get('data.items'));

        $component
            ->set("data.items.{$rowKey}.item_id", $stockedItem->getKey())
            ->assertSee('25')
            ->set("data.items.{$rowKey}.item_id", $otherItem->getKey())
            ->assertSee('8')
            ->set('data.warehouse_id', $secondWarehouse->getKey())
            ->assertSee('0')
            ->set("data.items.{$rowKey}.item_id", $stockedItem->getKey())
            ->assertSee('4')
            ->set("data.items.{$rowKey}.item_id", null)
            ->assertSee('0')
            ->set('data.items', [
                $rowKey => [
                    'item_id' => $stockedItem->getKey(),
                    'quantity' => 1,
                    'unit_price' => 100,
                ],
                (string) Str::uuid() => [
                    'item_id' => $otherItem->getKey(),
                    'quantity' => 1,
                    'unit_price' => 200,
                ],
            ])
            ->set('data.warehouse_id', $firstWarehouse->getKey())
            ->assertSee('25')
            ->assertSee('8')
            ->set('data.warehouse_id', $secondWarehouse->getKey())
            ->assertSee('4')
            ->assertSee('0');
    }

    private function assertEditFormAddsBackTheCurrentInvoiceIssue(): void
    {
        $this->actingAs(User::factory()->create());

        [$warehouse] = $this->warehouses();
        [$item] = $this->items();
        $customer = $this->customer();
        $this->seedStock($warehouse, $item, 10);

        $invoice = app(SalesInvoiceService::class)->create([
            'electronic_invoice_number' => 500,
            'payment_type' => 'cash',
            'invoice_date' => '2026-07-25',
            'customer_id' => $customer->getKey(),
            'warehouse_id' => $warehouse->getKey(),
            'items' => [[
                'item_id' => $item->getKey(),
                'quantity' => 3,
                'unit_price' => 100,
            ]],
        ]);

        $this->assertSame(7.0, $this->balance($warehouse, $item));
        $this->assertSame(
            10.0,
            app(InventoryService::class)->availableForSalesInvoice(
                $warehouse->getKey(),
                $item->getKey(),
                $invoice->getKey(),
            ),
        );

        Livewire::test(EditSalesInvoice::class, ['record' => $invoice->getKey()])
            ->assertSee('10');
    }

    private function assertCreateUpdateValidationAndDeleteKeepInventoryOneForOne(): void
    {
        $this->actingAs(User::factory()->create());

        [$warehouse] = $this->warehouses();
        [$item] = $this->items();
        $customer = $this->customer();
        $service = app(SalesInvoiceService::class);

        $this->seedStock($warehouse, $item, 10);

        $invoice = $service->create([
            'electronic_invoice_number' => 600,
            'payment_type' => 'cash',
            'invoice_date' => '2026-07-25',
            'customer_id' => $customer->getKey(),
            'warehouse_id' => $warehouse->getKey(),
            'items' => [[
                'item_id' => $item->getKey(),
                'quantity' => 3,
                'unit_price' => 100,
            ]],
        ]);

        $this->assertSame(7.0, $this->balance($warehouse, $item));
        $this->assertSame(1, $this->saleTransactionCount($invoice));

        try {
            $service->update($invoice, [
                'electronic_invoice_number' => 600,
                'payment_type' => 'cash',
                'invoice_date' => '2026-07-25',
                'customer_id' => $customer->getKey(),
                'warehouse_id' => $warehouse->getKey(),
                'items' => [[
                    'item_id' => $item->getKey(),
                    'quantity' => 11,
                    'unit_price' => 100,
                ]],
            ]);

            $this->fail('The unavailable stock update should have failed.');
        } catch (ValidationException $exception) {
            $this->assertContains(
                'الكمية المطلوبة غير متوفرة في المخزن.',
                $exception->errors()['items'],
            );
        }

        $this->assertSame(7.0, $this->balance($warehouse, $item));
        $this->assertSame(1, $this->saleTransactionCount($invoice));

        $invoice = $service->update($invoice, [
            'electronic_invoice_number' => 600,
            'payment_type' => 'cash',
            'invoice_date' => '2026-07-25',
            'customer_id' => $customer->getKey(),
            'warehouse_id' => $warehouse->getKey(),
            'items' => [[
                'item_id' => $item->getKey(),
                'quantity' => 4,
                'unit_price' => 100,
            ]],
        ]);

        $this->assertSame(6.0, $this->balance($warehouse, $item));
        $this->assertSame(1, $this->saleTransactionCount($invoice));
        $this->assertSame(4.0, (float) StockTransaction::query()
            ->where('reference_no', $invoice->document_number)
            ->where('transaction_type', StockTransaction::TYPE_SALE)
            ->value('quantity'));

        $service->delete($invoice);

        $this->assertSame(10.0, $this->balance($warehouse, $item));
        $this->assertSame(0, $this->saleTransactionCount($invoice));
    }

    /**
     * @return array{0: Warehouse, 1: Warehouse}
     */
    private function warehouses(): array
    {
        return [
            Warehouse::create(['name' => 'المخزن الأول', 'active' => true]),
            Warehouse::create(['name' => 'المخزن الثاني', 'active' => true]),
        ];
    }

    /**
     * @return array{0: Item, 1: Item}
     */
    private function items(): array
    {
        $category = Category::create(['name' => 'تصنيف اختبار', 'active' => true]);
        $unit = Unit::create([
            'name' => 'قطعة',
            'short_name' => 'قطعة',
            'active' => true,
        ]);

        return [
            Item::create([
                'name' => 'الصنف الأول',
                'category_id' => $category->getKey(),
                'unit_id' => $unit->getKey(),
                'sale_price' => 100,
                'active' => true,
            ]),
            Item::create([
                'name' => 'الصنف الثاني',
                'category_id' => $category->getKey(),
                'unit_id' => $unit->getKey(),
                'sale_price' => 200,
                'active' => true,
            ]),
        ];
    }

    private function customer(): Customer
    {
        return Customer::create([
            'name' => 'عميل اختبار فاتورة البيع',
            'active' => true,
        ]);
    }

    private function seedStock(Warehouse $warehouse, Item $item, float $quantity): void
    {
        app(InventoryService::class)->replaceDocumentTransactions(
            "OPEN-{$warehouse->getKey()}-{$item->getKey()}",
            [[
                'warehouse_id' => $warehouse->getKey(),
                'item_id' => $item->getKey(),
                'transaction_type' => StockTransaction::TYPE_OPENING,
                'quantity' => $quantity,
                'unit_cost' => 50,
                'transaction_date' => '2026-07-01',
                'notes' => null,
            ]],
        );
    }

    private function balance(Warehouse $warehouse, Item $item): float
    {
        return (float) StockBalance::query()
            ->where('warehouse_id', $warehouse->getKey())
            ->where('item_id', $item->getKey())
            ->value('quantity');
    }

    private function saleTransactionCount(SalesInvoice $invoice): int
    {
        return StockTransaction::query()
            ->where('reference_no', $invoice->document_number)
            ->where('transaction_type', StockTransaction::TYPE_SALE)
            ->count();
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
        Schema::create('customers', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
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
            $table->unsignedBigInteger('sales_quotation_id')->nullable();
            $table->string('payment_type')->default('cash');
            $table->date('due_date')->nullable();
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->string('tax_type')->default('none');
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
        Schema::create('sales_invoice_items', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('sales_invoice_id');
            $table->unsignedBigInteger('item_id');
            $table->unsignedBigInteger('sales_quotation_item_id')->nullable();
            $table->unsignedBigInteger('unit_id')->nullable();
            $table->decimal('quantity', 15, 2);
            $table->decimal('unit_price', 15, 2);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('line_total', 15, 2);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
        Schema::create('sales_quotations', function (Blueprint $table): void {
            $table->id();
            $table->string('quotation_number')->unique();
            $table->date('quotation_date');
            $table->date('valid_until')->nullable();
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('warehouse_id')->nullable();
            $table->string('tax_type')->default('none');
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->text('terms_and_conditions')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamps();
        });
        Schema::create('sales_quotation_items', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('sales_quotation_id');
            $table->unsignedBigInteger('item_id');
            $table->unsignedBigInteger('unit_id');
            $table->decimal('quantity', 15, 2);
            $table->decimal('unit_price', 15, 2);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('line_total', 15, 2);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
        Schema::create('goods_issue_vouchers', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->unique();
            $table->date('voucher_date');
            $table->unsignedBigInteger('warehouse_id');
            $table->text('notes')->nullable();
            $table->boolean('posted')->default(false);
            $table->timestamps();
        });
        Schema::create('goods_issue_items', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('goods_issue_voucher_id');
            $table->unsignedBigInteger('item_id');
            $table->decimal('quantity', 15, 2);
            $table->decimal('unit_cost', 15, 2)->default(0);
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
    }
}
