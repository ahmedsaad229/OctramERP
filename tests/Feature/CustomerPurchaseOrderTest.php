<?php

namespace Tests\Feature;

use App\Filament\Resources\CustomerPurchaseOrders\CustomerPurchaseOrderResource;
use App\Models\Category;
use App\Models\Customer;
use App\Models\CustomerPurchaseOrder;
use App\Models\Item;
use App\Models\PartyTransaction;
use App\Models\StockTransaction;
use App\Models\TreasuryTransaction;
use App\Models\Unit;
use App\Models\User;
use App\Services\CustomerPurchaseOrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CustomerPurchaseOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_is_operational_only_and_calculates_progress(): void
    {
        $this->actingAs(User::factory()->create());
        $customer = Customer::create(['name' => 'عميل الاختبار', 'active' => true]);
        $category = Category::create(['name' => 'تصنيف', 'active' => true]);
        $unit = Unit::create(['name' => 'قطعة', 'short_name' => 'قطعة', 'active' => true]);
        $item = Item::create(['name' => 'صنف', 'category_id' => $category->id, 'unit_id' => $unit->id, 'active' => true]);

        $order = app(CustomerPurchaseOrderService::class)->create([
            'customer_id' => $customer->id, 'order_date' => '2026-08-01', 'required_delivery_date' => '2026-08-10',
            'customer_order_number' => 'CLIENT-1', 'items' => [[
                'item_id' => $item->id, 'unit_id' => $unit->id, 'ordered_quantity' => 10, 'unit_price' => 25,
            ]],
        ]);
        $this->assertStringStartsWith('CPO-', $order->document_number);
        $this->assertSame(CustomerPurchaseOrder::STATUS_NEW, $order->status);
        $this->assertSame('10.00', $order->items->first()->remaining_quantity);
        $this->assertSame(0, StockTransaction::count());
        $this->assertSame(0, PartyTransaction::count());
        $this->assertSame(0, TreasuryTransaction::count());
        $this->assertSame('المبيعات', CustomerPurchaseOrderResource::getNavigationGroup());
        $this->assertSame('أوامر توريد العملاء', CustomerPurchaseOrderResource::getNavigationLabel());
        $this->assertFalse($order->isDelayed());

        $this->travelTo('2026-08-11');
        $this->assertTrue($order->fresh()->isDelayed());
    }

    public function test_validation_rejects_empty_or_invalid_orders(): void
    {
        $this->actingAs(User::factory()->create());
        foreach ([
            ['order_date' => '2026-08-01', 'items' => [['item_id' => 1, 'ordered_quantity' => 1]]],
            ['customer_id' => 1, 'order_date' => '2026-08-01', 'items' => []],
            ['customer_id' => 1, 'order_date' => '2026-08-01', 'items' => [['item_id' => 1, 'ordered_quantity' => 0]]],
        ] as $data) {
            try {
                app(CustomerPurchaseOrderService::class)->create($data);
                $this->fail('Invalid order accepted.');
            } catch (ValidationException) {
                $this->assertDatabaseCount('customer_purchase_orders', 0);
            }
        }
    }
}
