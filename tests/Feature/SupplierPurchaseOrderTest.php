<?php

namespace Tests\Feature;

use App\Enums\TaxType;
use App\Filament\Resources\SupplierPurchaseOrders\Pages\CreateSupplierPurchaseOrder;
use App\Models\CompanySetting;
use App\Models\Item;
use App\Models\PartyTransaction;
use App\Models\PurchaseRequest;
use App\Models\StockBalance;
use App\Models\StockTransaction;
use App\Models\Supplier;
use App\Models\TreasuryTransaction;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\PurchaseRequestService;
use App\Services\SupplierPurchaseOrderService;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\Concerns\BuildsProcurementSchema;
use Tests\TestCase;

class SupplierPurchaseOrderTest extends TestCase
{
    use BuildsProcurementSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildProcurementSchema();
    }

    public function test_order_imports_remaining_items_generates_code_and_calculates_totals(): void
    {
        [$request, $supplier, $warehouse] = $this->context(10);
        $service = app(SupplierPurchaseOrderService::class);
        $items = $service->importRemainingItems($request->id);

        $this->assertCount(1, $items);
        $this->assertSame(10.0, $items[0]['ordered_quantity']);
        $items[0]['unit_price'] = 25;
        $order = $service->create($this->orderData($request, $supplier, $warehouse, $items, 10, 20));

        $this->assertSame('PO-000001', $order->code);
        $this->assertSame(250.0, (float) $order->subtotal);
        $this->assertSame(273.6, (float) $order->total);
        $this->assertSame(33.6, (float) $order->tax_amount);
        $this->assertSame(250.0, (float) $order->items->first()->line_total);
        $this->assertDatabaseCount((new StockTransaction)->getTable(), 0);
        $this->assertDatabaseCount((new PartyTransaction)->getTable(), 0);
        $this->assertDatabaseCount((new TreasuryTransaction)->getTable(), 0);
    }

    public function test_partial_orders_import_only_remaining_and_fully_ordered_items_are_omitted(): void
    {
        [$request, $supplier, $warehouse] = $this->context(10);
        $service = app(SupplierPurchaseOrderService::class);
        $firstItems = $service->importRemainingItems($request->id);
        $firstItems[0]['ordered_quantity'] = 6;
        $firstItems[0]['unit_price'] = 1;
        $service->create($this->orderData($request, $supplier, $warehouse, $firstItems));

        $remaining = $service->importRemainingItems($request->id);
        $this->assertSame(4.0, $remaining[0]['ordered_quantity']);
        $this->assertSame(6.0, $remaining[0]['previously_ordered_quantity']);
        $remaining[0]['unit_price'] = 1;
        $service->create($this->orderData($request, $supplier, $warehouse, $remaining));

        $this->assertSame([], $service->importRemainingItems($request->id));
        $this->assertSame(0.0, $request->items->first()->remainingToOrderQuantity());
    }

    public function test_vat_is_server_calculated_and_submitted_totals_are_ignored(): void
    {
        [$request, $supplier, $warehouse] = $this->context(10);
        $items = app(SupplierPurchaseOrderService::class)->importRemainingItems($request->id);
        $items[0]['unit_price'] = 1000;
        $data = $this->orderData($request, $supplier, $warehouse, $items, 1000, 999999);
        $data['tax_type'] = TaxType::Vat14->value;
        $data['total'] = 1;

        $order = app(SupplierPurchaseOrderService::class)->create($data);

        $this->assertSame(9000.0, (float) $order->subtotal - (float) $order->discount_amount);
        $this->assertSame(1260.0, (float) $order->tax_amount);
        $this->assertSame(10260.0, (float) $order->total);
        $this->assertDatabaseCount((new StockTransaction)->getTable(), 0);
        $this->assertDatabaseCount((new PartyTransaction)->getTable(), 0);
        $this->assertDatabaseCount((new TreasuryTransaction)->getTable(), 0);
    }

    public function test_company_default_and_explicit_order_tax_override_are_respected(): void
    {
        CompanySetting::current()->update(['default_tax_type' => TaxType::None->value]);
        [$request, $supplier, $warehouse] = $this->context(10);
        $items = app(SupplierPurchaseOrderService::class)->importRemainingItems($request->id);
        $items[0]['unit_price'] = 10;

        $withoutTax = app(SupplierPurchaseOrderService::class)->create(
            $this->orderData($request, $supplier, $warehouse, $items),
        );
        $this->assertSame(TaxType::None, $withoutTax->tax_type);
        $this->assertSame(100.0, (float) $withoutTax->total);

        [$secondRequest, $secondSupplier, $secondWarehouse] = $this->context(10);
        $secondItems = app(SupplierPurchaseOrderService::class)->importRemainingItems($secondRequest->id);
        $secondItems[0]['unit_price'] = 10;
        $explicit = $this->orderData($secondRequest, $secondSupplier, $secondWarehouse, $secondItems);
        $explicit['tax_type'] = TaxType::Vat14->value;
        $withVat = app(SupplierPurchaseOrderService::class)->create($explicit);
        $this->assertSame(TaxType::Vat14, $withVat->tax_type);
        $this->assertSame(114.0, (float) $withVat->total);
    }

    public function test_multiple_orders_cannot_exceed_request_quantity(): void
    {
        [$request, $supplier, $warehouse] = $this->context(10);
        $service = app(SupplierPurchaseOrderService::class);
        $items = $service->importRemainingItems($request->id);
        $items[0]['ordered_quantity'] = 11;
        $items[0]['unit_price'] = 1;

        $this->expectException(ValidationException::class);
        $service->create($this->orderData($request, $supplier, $warehouse, $items));
    }

    public function test_edit_excludes_current_order_quantity_and_recalculates_totals(): void
    {
        [$request, $supplier, $warehouse] = $this->context(10);
        $service = app(SupplierPurchaseOrderService::class);
        $items = $service->importRemainingItems($request->id);
        $items[0]['ordered_quantity'] = 6;
        $items[0]['unit_price'] = 2;
        $order = $service->create($this->orderData($request, $supplier, $warehouse, $items));

        $editable = $service->importRemainingItems($request->id, $order->id);
        $this->assertSame(10.0, $editable[0]['remaining_quantity']);
        $editable[0]['ordered_quantity'] = 8;
        $editable[0]['unit_price'] = 3;
        $updated = $service->update($order, $this->orderData($request, $supplier, $warehouse, $editable));

        $this->assertSame(8.0, (float) $updated->items->first()->ordered_quantity);
        $this->assertSame(27.36, (float) $updated->total);
        $this->assertSame(2.0, $request->items->first()->remainingToOrderQuantity());
    }

    public function test_supplier_price_and_linked_request_are_validated(): void
    {
        [$request, $supplier, $warehouse] = $this->context(10);
        $service = app(SupplierPurchaseOrderService::class);
        $items = $service->importRemainingItems($request->id);
        $items[0]['unit_price'] = -1;

        try {
            $service->create($this->orderData($request, $supplier, $warehouse, $items));
            $this->fail('Negative price should fail.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('items.0.unit_price', $exception->errors());
        }

        $items[0]['unit_price'] = 1;
        $data = $this->orderData($request, $supplier, $warehouse, $items);
        $data['supplier_id'] = null;

        $this->expectException(ValidationException::class);
        $service->create($data);
    }

    public function test_deleting_order_restores_computed_remaining_quantity(): void
    {
        [$request, $supplier, $warehouse] = $this->context(10);
        $service = app(SupplierPurchaseOrderService::class);
        $items = $service->importRemainingItems($request->id);
        $items[0]['ordered_quantity'] = 7;
        $items[0]['unit_price'] = 1;
        $order = $service->create($this->orderData($request, $supplier, $warehouse, $items));

        $this->assertSame(3.0, $request->items->first()->remainingToOrderQuantity());
        $service->delete($order);
        $this->assertSame(10.0, $request->items->first()->remainingToOrderQuantity());
    }

    public function test_supplier_purchase_order_routes_are_registered(): void
    {
        $this->assertNotNull(route('filament.admin.resources.supplier-purchase-orders.index'));
        $this->assertNotNull(route('filament.admin.resources.supplier-purchase-orders.create'));
        $this->assertNotNull(route('filament.admin.resources.supplier-purchase-orders.edit', 1));
    }

    public function test_create_page_loads_with_null_initial_selections(): void
    {
        $user = User::create(['name' => 'مستخدم الاختبار']);

        Livewire::actingAs($user)
            ->test(CreateSupplierPurchaseOrder::class)
            ->assertStatus(200)
            ->assertSet('data.supplier_id', null)
            ->assertSet('data.purchase_request_id', null)
            ->assertSet('data.warehouse_id', null)
            ->assertSet('data.items', []);

        $this->assertSame([], app(SupplierPurchaseOrderService::class)->purchaseRequestOptions());
    }

    public function test_purchase_request_is_required_with_the_requested_arabic_message(): void
    {
        [, $supplier, $warehouse] = $this->context(10);

        try {
            app(SupplierPurchaseOrderService::class)->create([
                'order_date' => '2026-07-26',
                'supplier_id' => $supplier->id,
                'purchase_request_id' => null,
                'warehouse_id' => $warehouse->id,
                'items' => [],
            ]);
            $this->fail('A purchase request must be required.');
        } catch (ValidationException $exception) {
            $this->assertSame(
                'يجب اختيار طلب شراء لإنشاء أمر التوريد.',
                $exception->errors()['purchase_request_id'][0],
            );
        }
    }

    public function test_selector_labels_and_selection_payload_include_request_header_and_remaining_items(): void
    {
        [$request, , $warehouse] = $this->context(10);
        $request->update([
            'required_date' => '2026-08-05',
            'purpose' => 'أدوات كهربائية',
            'department' => 'الصيانة',
        ]);
        $requestItem = $request->items->first();
        StockBalance::create([
            'warehouse_id' => $warehouse->id,
            'item_id' => $requestItem->item_id,
            'quantity' => 7,
            'average_cost' => 1,
        ]);
        $service = app(SupplierPurchaseOrderService::class);

        $options = $service->purchaseRequestOptions();
        $this->assertArrayHasKey($request->id, $options);
        $this->assertStringContainsString($request->code, $options[$request->id]);
        $this->assertStringContainsString('طلب بتاريخ 26/07/2026', $options[$request->id]);
        $this->assertStringContainsString('أدوات كهربائية', $options[$request->id]);
        $this->assertStringContainsString($warehouse->name, $options[$request->id]);

        $payload = $service->requestSelectionPayload($request->id);
        $this->assertSame($request->id, $payload['purchase_request_id']);
        $this->assertSame($warehouse->id, $payload['warehouse_id']);
        $this->assertSame('2026-08-05', $payload['request_required_date']);
        $this->assertSame('أدوات كهربائية', $payload['request_purpose']);
        $this->assertSame('الصيانة', $payload['request_department']);
        $this->assertCount(1, $payload['items']);
        $this->assertSame($requestItem->id, $payload['items'][0]['purchase_request_item_id']);
        $this->assertSame(10.0, $payload['items'][0]['remaining_quantity']);
        $this->assertSame(7.0, $payload['items'][0]['warehouse_balance']);
        $this->assertSame(7.0, $payload['items'][0]['total_balance']);
    }

    public function test_unrelated_items_are_rejected_and_changing_request_replaces_the_payload(): void
    {
        [$firstRequest, $supplier, $warehouse] = $this->context(10);
        [$secondRequest] = $this->context(5);
        $service = app(SupplierPurchaseOrderService::class);
        $firstPayload = $service->requestSelectionPayload($firstRequest->id);
        $secondPayload = $service->requestSelectionPayload($secondRequest->id);

        $this->assertNotSame(
            $firstPayload['items'][0]['purchase_request_item_id'],
            $secondPayload['items'][0]['purchase_request_item_id'],
        );
        $this->assertCount(1, $secondPayload['items']);

        $unrelatedItems = $firstPayload['items'];
        $unrelatedItems[0]['purchase_request_item_id'] = $secondPayload['items'][0]['purchase_request_item_id'];
        $unrelatedItems[0]['item_id'] = $secondPayload['items'][0]['item_id'];
        $unrelatedItems[0]['unit_price'] = 1;

        $this->expectException(ValidationException::class);
        $service->create($this->orderData($firstRequest, $supplier, $warehouse, $unrelatedItems));
    }

    public function test_completed_requests_are_excluded_but_current_request_remains_available_while_editing(): void
    {
        [$request, $supplier, $warehouse] = $this->context(10);
        $service = app(SupplierPurchaseOrderService::class);
        $items = $service->importRemainingItems($request->id);
        $items[0]['unit_price'] = 1;
        $order = $service->create($this->orderData($request, $supplier, $warehouse, $items));

        $this->assertArrayNotHasKey($request->id, $service->purchaseRequestOptions());
        $this->assertArrayHasKey($request->id, $service->purchaseRequestOptions($order->purchase_request_id));
        $this->assertCount(1, $service->requestSelectionPayload($request->id, $order->id)['items']);
    }

    private function context(float $quantity): array
    {
        $unit = Unit::create(['name' => 'قطعة', 'short_name' => 'ق']);
        $warehouse = Warehouse::create(['name' => 'الرئيسي', 'active' => true]);
        $item = Item::create([
            'name' => 'صنف اختبار',
            'unit_id' => $unit->id,
            'purchase_price' => 0,
            'sale_price' => 0,
            'minimum_stock' => 0,
            'allow_negative_stock' => false,
            'active' => true,
        ]);
        $supplier = Supplier::create(['name' => 'مورد الاختبار', 'active' => true]);
        $request = app(PurchaseRequestService::class)->create([
            'request_date' => '2026-07-26',
            'warehouse_id' => $warehouse->id,
            'items' => [[
                'item_id' => $item->id,
                'unit_id' => $unit->id,
                'requested_quantity' => $quantity,
            ]],
        ]);

        return [$request, $supplier, $warehouse];
    }

    private function orderData(
        PurchaseRequest $request,
        Supplier $supplier,
        Warehouse $warehouse,
        array $items,
        float $discount = 0,
        float $tax = 0,
    ): array {
        return [
            'order_date' => '2026-07-26',
            'supplier_id' => $supplier->id,
            'purchase_request_id' => $request->id,
            'warehouse_id' => $warehouse->id,
            'payment_type' => 'cash',
            'discount_amount' => $discount,
            'tax_amount' => $tax,
            'items' => $items,
        ];
    }
}
