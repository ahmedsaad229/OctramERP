<?php

namespace Tests\Feature;

use App\Filament\Pages\CustomerPurchaseOrderMonitoring;
use App\Models\Category;
use App\Models\Customer;
use App\Models\CustomerPurchaseOrder;
use App\Models\CustomerPurchaseOrderExecution;
use App\Models\Item;
use App\Models\SalesInvoice;
use App\Models\StockBalance;
use App\Models\StockTransaction;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\CustomerPurchaseOrderConversionService;
use App\Services\CustomerPurchaseOrderMonitoringService;
use App\Services\CustomerPurchaseOrderService;
use App\Services\Inventory\SalesInvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class CustomerPurchaseOrderMonitoringTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_monitoring_summaries_filters_and_navigation_are_correct(): void
    {
        Carbon::setTestNow('2026-08-10');
        [$customer, $other, $item, $unit] = $this->fixtures();
        $new = $this->order($customer, $item, $unit, '2026-08-05');
        $delayed = $this->order($customer, $item, $unit, '2026-08-09');
        $dueSoon = $this->order($customer, $item, $unit, '2026-08-15');
        $completed = $this->order($other, $item, $unit, '2026-08-01');
        $completed->update(['status' => CustomerPurchaseOrder::STATUS_COMPLETED, 'execution_percentage' => 100]);
        $cancelled = $this->order($other, $item, $unit, '2026-08-01');
        $cancelled->update(['status' => CustomerPurchaseOrder::STATUS_CANCELLED]);

        $report = app(CustomerPurchaseOrderMonitoringService::class)->report();
        $this->assertSame(5, $report['summary']['total']);
        $this->assertSame(2, $report['summary']['delayed']);
        $this->assertSame(1, $report['summary']['dueSoon']);
        $this->assertSame(1, $report['summary']['completed']);
        $this->assertFalse($completed->fresh()->isDelayed());
        $this->assertFalse($cancelled->fresh()->isDelayed());
        $this->assertCount(3, app(CustomerPurchaseOrderMonitoringService::class)
            ->report(['customer_id' => $customer->getKey()])['rows']);
        $this->assertSame('المبيعات', CustomerPurchaseOrderMonitoring::getNavigationGroup());
        $this->assertSame('متابعة أوامر التوريد', CustomerPurchaseOrderMonitoring::getNavigationLabel());

        $this->get('/admin/customer-purchase-order-monitoring')->assertRedirect();
        $this->actingAs(User::factory()->create());
        $component = Livewire::test(CustomerPurchaseOrderMonitoring::class)
            ->assertSee($new->document_number)
            ->assertSee($dueSoon->document_number)
            ->assertSeeHtml('octram-report-scroll');
        $this->assertSame(1, substr_count($component->html(), 'تصدير Excel'));

        $export = $this->get(route('customer-purchase-order-monitoring.excel', ['customer_id' => $customer->id]));
        $export->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString($new->document_number, $export->streamedContent());
        $this->get(route('customer-purchase-order-monitoring.print', ['customer_id' => $customer->id]))
            ->assertOk()
            ->assertSee($new->document_number);
    }

    public function test_import_lines_support_selection_partial_quantities_and_validation(): void
    {
        [$customer, , $item, $unit] = $this->fixtures();
        $order = $this->order($customer, $item, $unit, '2026-08-20', 10);
        $service = app(CustomerPurchaseOrderConversionService::class);
        $lines = $service->lines($order->getKey());
        $this->assertSame(10.0, $lines[0]['import_quantity']);
        $lines[0]['import_quantity'] = 4;
        $items = $service->invoiceItems($lines);
        $this->assertSame(4.0, $items[0]['quantity']);
        $this->assertSame($order->items->first()->getKey(), $items[0]['customer_purchase_order_item_id']);

        $lines[0]['import_quantity'] = 11;
        $this->expectException(ValidationException::class);
        $service->invoiceItems($lines);
    }

    public function test_linked_invoice_is_grouped_and_history_is_chronological(): void
    {
        [$customer, , $item, $unit] = $this->fixtures();
        $warehouse = Warehouse::create(['name' => 'مخزن', 'active' => true]);
        $order = $this->order($customer, $item, $unit, '2026-08-20', 10);
        $line = $order->items->first();
        $otherItem = Item::create(['name' => 'صنف ثان', 'category_id' => $item->category_id, 'unit_id' => $unit->id, 'active' => true]);
        $otherLine = $order->items()->create(['item_id' => $otherItem->id, 'unit_id' => $unit->id, 'ordered_quantity' => 10, 'remaining_quantity' => 10]);
        $invoice = SalesInvoice::create([
            'electronic_invoice_number' => 1001, 'invoice_date' => '2026-08-11',
            'customer_id' => $customer->id, 'warehouse_id' => $warehouse->id,
            'payment_type' => 'cash', 'tax_type' => 'none',
        ]);
        $invoiceItem = $invoice->items()->create(['item_id' => $item->id, 'unit_id' => $unit->id, 'quantity' => 4, 'unit_price' => 10, 'line_total' => 40]);
        CustomerPurchaseOrderExecution::create([
            'customer_purchase_order_id' => $order->id, 'customer_purchase_order_item_id' => $line->id,
            'source_type' => $invoice->getMorphClass(), 'source_id' => $invoice->id,
            'source_item_id' => $invoiceItem->id, 'executed_quantity' => 2, 'execution_date' => '2026-08-11',
        ]);
        CustomerPurchaseOrderExecution::create([
            'customer_purchase_order_id' => $order->id, 'customer_purchase_order_item_id' => $otherLine->id,
            'source_type' => $invoice->getMorphClass(), 'source_id' => $invoice->id,
            'source_item_id' => $invoiceItem->id, 'executed_quantity' => 2, 'execution_date' => '2026-08-11',
        ]);
        $secondInvoice = SalesInvoice::create([
            'electronic_invoice_number' => 1002, 'invoice_date' => '2026-08-12',
            'customer_id' => $customer->id, 'warehouse_id' => $warehouse->id,
            'payment_type' => 'cash', 'tax_type' => 'none',
        ]);
        CustomerPurchaseOrderExecution::create([
            'customer_purchase_order_id' => $order->id, 'customer_purchase_order_item_id' => $line->id,
            'source_type' => $secondInvoice->getMorphClass(), 'source_id' => $secondInvoice->id,
            'executed_quantity' => 2, 'execution_date' => '2026-08-12',
        ]);
        $documents = app(CustomerPurchaseOrderService::class)->linkedDocuments($order);
        $this->assertCount(2, $documents);
        $this->assertSame(4.0, $documents[0]['quantity']);
        $this->assertSame(0.0, $documents[0]['details'][0]['previous']);
        $this->assertSame(2.0, $documents[1]['details'][0]['previous']);
        $this->assertSame(6.0, $documents[1]['details'][0]['remainingAfter']);
    }

    public function test_sales_invoice_execution_is_replaced_and_removed_without_duplicate_posting(): void
    {
        $this->actingAs(User::factory()->create());
        [$customer, , $item, $unit] = $this->fixtures();
        $warehouse = Warehouse::create(['name' => 'مخزن التنفيذ', 'active' => true]);
        StockTransaction::create([
            'warehouse_id' => $warehouse->id, 'item_id' => $item->id, 'transaction_type' => 'opening',
            'quantity' => 20, 'unit_cost' => 10, 'transaction_date' => '2026-08-01', 'reference_no' => 'OPEN-TEST',
        ]);
        StockBalance::create(['warehouse_id' => $warehouse->id, 'item_id' => $item->id, 'quantity' => 20, 'average_cost' => 10]);
        $order = $this->order($customer, $item, $unit, '2026-08-20', 10);
        $line = $order->items->first();
        $service = app(SalesInvoiceService::class);
        $data = [
            'electronic_invoice_number' => 2001, 'invoice_date' => '2026-08-10',
            'customer_id' => $customer->id, 'warehouse_id' => $warehouse->id,
            'customer_purchase_order_id' => $order->id, 'payment_type' => 'cash', 'tax_type' => 'none',
            'items' => [['item_id' => $item->id, 'unit_id' => $unit->id, 'quantity' => 4, 'unit_price' => 15, 'customer_purchase_order_item_id' => $line->id]],
        ];
        $invoice = $service->create($data);
        $this->assertSame(4.0, (float) $line->fresh()->executed_quantity);
        $this->assertSame(1, $invoice->fresh()->customerPurchaseOrder?->executions()->count());

        $data['items'][0]['quantity'] = 2;
        $service->update($invoice, $data);
        $this->assertSame(2.0, (float) $line->fresh()->executed_quantity);
        $this->assertSame(1, CustomerPurchaseOrderExecution::where('source_id', $invoice->id)->count());

        $service->delete($invoice);
        $this->assertSame(0.0, (float) $line->fresh()->executed_quantity);
        $this->assertSame(10.0, (float) $line->fresh()->remaining_quantity);
        $this->assertSame(0, CustomerPurchaseOrderExecution::where('source_id', $invoice->id)->count());
    }

    private function order(Customer $customer, Item $item, Unit $unit, string $delivery, float $quantity = 1): CustomerPurchaseOrder
    {
        return app(CustomerPurchaseOrderService::class)->create([
            'customer_id' => $customer->id, 'order_date' => '2026-08-01', 'required_delivery_date' => $delivery,
            'items' => [['item_id' => $item->id, 'unit_id' => $unit->id, 'ordered_quantity' => $quantity, 'unit_price' => 10]],
        ]);
    }

    private function fixtures(): array
    {
        $category = Category::create(['name' => 'تصنيف', 'active' => true]);
        $unit = Unit::create(['name' => 'قطعة', 'short_name' => 'قطعة', 'active' => true]);
        $item = Item::create(['name' => 'صنف', 'category_id' => $category->id, 'unit_id' => $unit->id, 'active' => true]);

        return [
            Customer::create(['name' => 'عميل أول', 'active' => true]),
            Customer::create(['name' => 'عميل ثان', 'active' => true]),
            $item, $unit,
        ];
    }
}
