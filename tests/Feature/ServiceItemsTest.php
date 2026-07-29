<?php

namespace Tests\Feature;

use App\Enums\TaxType;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Item;
use App\Models\StockBalance;
use App\Models\StockTransaction;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\Inventory\SalesInvoiceService;
use App\Services\SalesQuotationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ServiceItemsTest extends TestCase
{
    use RefreshDatabase;

    private Customer $customer;

    private Warehouse $warehouse;

    private Unit $unit;

    private Item $stockItem;

    private Item $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->admin()->create());
        $category = Category::create(['name' => 'بنود الاختبار']);
        $this->customer = Customer::create(['name' => 'عميل الخدمات']);
        $this->warehouse = Warehouse::create(['name' => 'المخزن الرئيسي']);
        $this->unit = Unit::create(['name' => 'قطعة', 'short_name' => 'قطعة']);
        $this->stockItem = Item::create([
            'name' => 'صنف مخزني',
            'category_id' => $category->id,
            'unit_id' => $this->unit->id,
            'sale_price' => 100,
        ]);
        $this->service = Item::create([
            'name' => 'خدمة تركيب',
            'category_id' => $category->id,
            'is_stock_item' => false,
            'unit_id' => null,
            'sale_price' => 200,
        ]);
    }

    public function test_existing_default_is_stock_and_service_can_be_created_without_unit(): void
    {
        $this->assertTrue($this->stockItem->fresh()->isStockItem());
        $this->assertTrue($this->service->fresh()->isNonStockItem());
        $this->assertNull($this->service->unit_id);
    }

    public function test_service_only_quotation_can_be_created_without_unit_or_warehouse(): void
    {
        $quotation = app(SalesQuotationService::class)->create([
            'quotation_date' => '2026-07-29',
            'customer_id' => $this->customer->id,
            'warehouse_id' => null,
            'tax_type' => TaxType::Vat14->value,
            'items' => [[
                'item_id' => $this->service->id,
                'unit_id' => null,
                'quantity' => 2,
                'unit_price' => 200,
                'discount_amount' => 0,
            ]],
        ]);

        $this->assertNull($quotation->warehouse_id);
        $this->assertNull($quotation->items->first()->unit_id);
        $this->assertSame(56.0, (float) $quotation->tax_amount);
        $this->assertSame(456.0, (float) $quotation->total_amount);
    }

    public function test_mixed_quotation_accepts_stock_and_service_items(): void
    {
        $quotation = app(SalesQuotationService::class)->create([
            'quotation_date' => '2026-07-29',
            'customer_id' => $this->customer->id,
            'warehouse_id' => $this->warehouse->id,
            'tax_type' => TaxType::None->value,
            'items' => [$this->stockLine(), $this->serviceLine()],
        ]);

        $this->assertCount(2, $quotation->items);
        $this->assertSame(300.0, (float) $quotation->total_amount);
    }

    public function test_service_invoice_does_not_create_stock_transactions_or_balances(): void
    {
        $invoice = app(SalesInvoiceService::class)->create($this->invoiceData([$this->serviceLine()], null, 5001));

        $this->assertNull($invoice->warehouse_id);
        $this->assertDatabaseCount('stock_transactions', 0);
        $this->assertDatabaseCount('stock_balances', 0);

        app(SalesInvoiceService::class)->update($invoice, $this->invoiceData([$this->serviceLine()], null, 5001));
        $this->assertDatabaseCount('stock_transactions', 0);
        app(SalesInvoiceService::class)->delete($invoice);
        $this->assertDatabaseCount('stock_transactions', 0);
    }

    public function test_mixed_invoice_posts_stock_item_only_and_requires_warehouse(): void
    {
        StockBalance::create([
            'warehouse_id' => $this->warehouse->id,
            'item_id' => $this->stockItem->id,
            'quantity' => 10,
            'average_cost' => 50,
        ]);
        StockTransaction::create([
            'warehouse_id' => $this->warehouse->id,
            'item_id' => $this->stockItem->id,
            'transaction_type' => StockTransaction::TYPE_OPENING,
            'quantity' => 10,
            'unit_cost' => 50,
            'transaction_date' => '2026-07-28',
            'reference_no' => 'OPEN-SERVICE-TEST',
        ]);

        $invoice = app(SalesInvoiceService::class)->create(
            $this->invoiceData([$this->stockLine(), $this->serviceLine()], $this->warehouse->id, 5002),
        );

        $this->assertSame(1, StockTransaction::query()->where('transaction_type', StockTransaction::TYPE_SALE)->count());
        $this->assertDatabaseHas('stock_transactions', [
            'reference_no' => $invoice->document_number,
            'item_id' => $this->stockItem->id,
            'transaction_type' => StockTransaction::TYPE_SALE,
        ]);
        $this->assertDatabaseMissing('stock_transactions', ['item_id' => $this->service->id]);
        $this->assertDatabaseMissing('stock_balances', ['item_id' => $this->service->id]);

        try {
            app(SalesInvoiceService::class)->create($this->invoiceData([$this->stockLine()], null, 5003));
            $this->fail('Expected warehouse validation to fail.');
        } catch (ValidationException $exception) {
            $this->assertSame(
                'يجب تحديد المخزن عند وجود بنود مخزنية.',
                $exception->errors()['warehouse_id'][0],
            );
        }
    }

    private function stockLine(): array
    {
        return ['item_id' => $this->stockItem->id, 'unit_id' => $this->unit->id, 'quantity' => 1, 'unit_price' => 100];
    }

    private function serviceLine(): array
    {
        return ['item_id' => $this->service->id, 'unit_id' => null, 'quantity' => 1, 'unit_price' => 200];
    }

    private function invoiceData(array $items, ?int $warehouseId, int $electronicNumber): array
    {
        return [
            'electronic_invoice_number' => $electronicNumber,
            'invoice_date' => '2026-07-29',
            'customer_id' => $this->customer->id,
            'warehouse_id' => $warehouseId,
            'payment_type' => 'cash',
            'tax_type' => TaxType::None->value,
            'discount_amount' => 0,
            'items' => $items,
        ];
    }
}
