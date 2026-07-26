<?php

namespace Tests\Feature;

use App\Enums\TaxType;
use App\Filament\Resources\PurchaseInvoices\Pages\CreatePurchaseInvoice;
use App\Models\CompanySetting;
use App\Models\Item;
use App\Models\PartyTransaction;
use App\Models\StockBalance;
use App\Models\StockTransaction;
use App\Models\Supplier;
use App\Models\SupplierPurchaseOrder;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\Inventory\InventoryService;
use App\Services\Inventory\PurchaseInvoiceService;
use App\Services\PurchaseRequestService;
use App\Services\SupplierPurchaseOrderService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\Concerns\BuildsProcurementSchema;
use Tests\TestCase;

class PurchaseOrderInvoiceIntegrationTest extends TestCase
{
    use BuildsProcurementSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildProcurementSchema();
    }

    public function test_purchase_order_to_invoice_conversion_and_posting_lifecycle(): void
    {
        [$supplier, $warehouse, $item, $order] = $this->procurementContext();
        $invoiceService = app(PurchaseInvoiceService::class);

        $this->assertDatabaseCount('stock_transactions', 0);
        $this->assertDatabaseCount('party_transactions', 0);
        $this->assertArrayHasKey($order->id, $invoiceService->purchaseOrderOptions($supplier->id));

        $otherSupplier = Supplier::create(['name' => 'مورد آخر', 'active' => true]);
        $this->assertArrayNotHasKey($order->id, $invoiceService->purchaseOrderOptions($otherSupplier->id));

        $imported = $invoiceService->importRemainingOrderItems($order->id);
        $this->assertCount(1, $imported);
        $this->assertSame($item->id, $imported[0]['item_id']);
        $this->assertSame(10.0, $imported[0]['quantity']);
        $this->assertSame(25.0, $imported[0]['unit_cost']);

        $imported[0]['quantity'] = 6;
        $firstInvoice = $invoiceService->create($this->invoiceData(
            $supplier,
            $warehouse,
            $order,
            'SUP-INV-1',
            $imported,
        ));

        $this->assertSame(6.0, $order->fresh()->invoicedQuantity());
        $this->assertSame(4.0, $order->fresh()->remainingToInvoiceQuantity());
        $this->assertSame('partially_invoiced', $order->fresh()->invoiceConversionStatus());
        $this->assertSame(6.0, $this->balance($warehouse, $item));
        $this->assertSame(1, StockTransaction::query()->where('reference_no', $firstInvoice->code)->count());
        $this->assertSame(1, PartyTransaction::query()
            ->where('source_type', $firstInvoice->getMorphClass())
            ->where('source_id', $firstInvoice->id)
            ->count());

        $remaining = $invoiceService->importRemainingOrderItems($order->id);
        $this->assertSame(4.0, $remaining[0]['quantity']);
        $secondInvoice = $invoiceService->create($this->invoiceData(
            $supplier,
            $warehouse,
            $order,
            'SUP-INV-2',
            $remaining,
        ));

        $this->assertSame('fully_invoiced', $order->fresh()->invoiceConversionStatus());
        $this->assertArrayNotHasKey($order->id, $invoiceService->purchaseOrderOptions($supplier->id));
        $this->assertArrayHasKey(
            $order->id,
            $invoiceService->purchaseOrderOptions($supplier->id, $order->id),
        );

        $editable = $invoiceService->importRemainingOrderItems($order->id, $firstInvoice);
        $this->assertSame(6.0, $editable[0]['remaining_quantity']);
        $editable[0]['quantity'] = 5;
        $invoiceService->update($firstInvoice, $this->invoiceData(
            $supplier,
            $warehouse,
            $order,
            'SUP-INV-1',
            $editable,
        ));

        $this->assertSame(9.0, $order->fresh()->invoicedQuantity());
        $this->assertSame(1.0, $order->fresh()->remainingToInvoiceQuantity());
        $this->assertSame(2, StockTransaction::query()->count());
        $this->assertSame(2, PartyTransaction::query()
            ->where('transaction_type', PartyTransaction::TYPE_PURCHASE_INVOICE)
            ->count());

        $tooMuch = $invoiceService->importRemainingOrderItems($order->id, $firstInvoice);
        $tooMuch[0]['quantity'] = 7;

        try {
            $invoiceService->update($firstInvoice, $this->invoiceData(
                $supplier,
                $warehouse,
                $order,
                'SUP-INV-1',
                $tooMuch,
            ));
            $this->fail('Invoice quantity above the order remainder must fail.');
        } catch (ValidationException $exception) {
            $this->assertSame(
                'كمية الفاتورة أكبر من الكمية المتبقية في أمر التوريد.',
                $exception->errors()['items.0.quantity'][0],
            );
        }

        $invoiceService->delete($secondInvoice);
        $this->assertSame(5.0, $order->fresh()->remainingToInvoiceQuantity());
        $this->assertSame(5.0, $this->balance($warehouse, $item));
        $this->assertSame(1, StockTransaction::query()->count());
        $this->assertSame(1, PartyTransaction::query()
            ->where('transaction_type', PartyTransaction::TYPE_PURCHASE_INVOICE)
            ->count());
    }

    public function test_manual_purchase_invoice_remains_supported_and_linked_rows_are_protected(): void
    {
        [$supplier, $warehouse, $item, $order] = $this->procurementContext();
        $invoiceService = app(PurchaseInvoiceService::class);
        $manual = $invoiceService->create([
            'supplier_id' => $supplier->id,
            'invoice_number' => 'MANUAL-1',
            'invoice_date' => '2026-07-26',
            'warehouse_id' => $warehouse->id,
            'payment_type' => 'cash',
            'items' => [[
                'item_id' => $item->id,
                'quantity' => 2,
                'unit_cost' => 30,
            ]],
        ]);

        $this->assertNull($manual->supplier_purchase_order_id);
        $this->assertSame(68.4, $manual->totalAmount());
        $this->assertSame(2.0, $this->balance($warehouse, $item));

        $unrelated = $invoiceService->importRemainingOrderItems($order->id);
        $unrelated[0]['item_id'] = Item::create([
            'name' => 'صنف غير مرتبط',
            'unit_id' => Unit::query()->firstOrFail()->id,
            'purchase_price' => 0,
            'sale_price' => 0,
            'minimum_stock' => 0,
            'allow_negative_stock' => false,
            'active' => true,
        ])->id;

        $this->expectException(ValidationException::class);
        $invoiceService->create($this->invoiceData(
            $supplier,
            $warehouse,
            $order,
            'INVALID-LINK',
            $unrelated,
        ));
    }

    public function test_purchase_invoice_uses_company_default_and_edit_preserves_tax(): void
    {
        CompanySetting::current()->update(['default_tax_type' => TaxType::None->value]);
        [$supplier, $warehouse, $item] = $this->procurementContext();
        $data = [
            'supplier_id' => $supplier->id,
            'invoice_number' => 'TAX-DEFAULT-1',
            'invoice_date' => '2026-07-26',
            'warehouse_id' => $warehouse->id,
            'payment_type' => 'cash',
            'items' => [[
                'item_id' => $item->id,
                'quantity' => 2,
                'unit_cost' => 30,
            ]],
        ];

        $invoice = app(PurchaseInvoiceService::class)->create($data);
        $this->assertSame(TaxType::None, $invoice->tax_type);
        $this->assertSame(60.0, $invoice->totalAmount());

        CompanySetting::current()->update(['default_tax_type' => TaxType::Vat14->value]);
        $updated = app(PurchaseInvoiceService::class)->update($invoice, $data);
        $this->assertSame(TaxType::None, $updated->tax_type);
        $this->assertSame(60.0, $updated->totalAmount());
    }

    public function test_filament_supplier_and_order_selection_imports_and_clears_rows_reactively(): void
    {
        [$supplier, $warehouse, $item, $order] = $this->procurementContext();
        $otherSupplier = Supplier::create(['name' => 'مورد آخر', 'active' => true]);
        $user = User::create(['name' => 'مستخدم الاختبار']);

        $component = Livewire::actingAs($user)
            ->test(CreatePurchaseInvoice::class)
            ->set('data.supplier_id', $supplier->id)
            ->set('data.supplier_purchase_order_id', $order->id)
            ->assertSet('data.warehouse_id', $warehouse->id)
            ->assertSet('data.payment_type', 'credit')
            ->assertSet('data.due_date', '2026-08-26');

        $items = array_values($component->get('data.items'));
        $this->assertCount(1, $items);
        $this->assertSame($order->items->first()->id, $items[0]['supplier_purchase_order_item_id']);
        $this->assertSame($item->id, $items[0]['item_id']);
        $this->assertSame(10.0, (float) $items[0]['quantity']);
        $this->assertSame(25.0, (float) $items[0]['unit_cost']);

        $component
            ->set('data.supplier_id', $otherSupplier->id)
            ->assertSet('data.supplier_purchase_order_id', null);

        $this->assertSame([], $component->get('data.items'));
    }

    public function test_purchase_invoice_recalculates_order_tax_and_posts_vat_inclusive_liability(): void
    {
        [$supplier, $warehouse, $item, $order] = $this->procurementContext();
        $order->update(['tax_type' => TaxType::Vat14->value]);
        $service = app(PurchaseInvoiceService::class);
        $payload = $service->purchaseOrderSelectionPayload($order->id);

        $this->assertSame(TaxType::Vat14->value, $payload['tax_type']);
        $payload['items'][0]['quantity'] = 2;
        $invoice = $service->create([
            ...$this->invoiceData($supplier, $warehouse, $order, 'VAT-1', $payload['items']),
            'tax_type' => TaxType::Vat14->value,
            'discount_amount' => 10,
            'tax_amount' => 999999,
            'total' => 1,
        ]);

        $this->assertSame(5.6, (float) $invoice->tax_amount);
        $this->assertSame(45.6, $invoice->totalAmount());
        $this->assertSame(2.0, $this->balance($warehouse, $item));
        $this->assertSame(45.6, (float) PartyTransaction::query()
            ->where('source_type', $invoice->getMorphClass())
            ->where('source_id', $invoice->id)
            ->value('credit'));

        $service->update($invoice, [
            ...$this->invoiceData($supplier, $warehouse, $order, 'VAT-1', $payload['items']),
            'tax_type' => TaxType::None->value,
        ]);

        $this->assertSame(1, PartyTransaction::query()
            ->where('source_type', $invoice->getMorphClass())
            ->where('source_id', $invoice->id)
            ->count());
    }

    public function test_purchase_stock_posting_uses_invoice_quantity_and_rebuilds_on_move_and_delete(): void
    {
        [$supplier, $warehouse, $item, $order] = $this->procurementContext();
        $otherWarehouse = Warehouse::create(['name' => 'مخزن آخر', 'active' => true]);
        app(InventoryService::class)->replaceDocumentTransactions('OPENING-TEST', [[
            'warehouse_id' => $warehouse->id,
            'item_id' => $item->id,
            'transaction_type' => StockTransaction::TYPE_OPENING,
            'quantity' => 2,
            'unit_cost' => 20,
            'transaction_date' => '2026-07-01',
        ]]);

        $service = app(PurchaseInvoiceService::class);
        $items = $service->importRemainingOrderItems($order->id);
        $items[0]['quantity'] = 5;
        $items[0]['ordered_quantity'] = 10;
        $invoice = $service->create([
            ...$this->invoiceData($supplier, $warehouse, $order, 'STOCK-1', $items),
            'tax_type' => TaxType::Vat14->value,
            'tax_amount' => 999999,
        ]);

        $transaction = StockTransaction::query()->where('reference_no', $invoice->code)->sole();
        $this->assertSame(StockTransaction::TYPE_PURCHASE, $transaction->transaction_type);
        $this->assertSame($warehouse->id, $transaction->warehouse_id);
        $this->assertSame($item->id, $transaction->item_id);
        $this->assertSame(5.0, (float) $transaction->quantity);
        $this->assertSame(25.0, (float) $transaction->unit_cost);
        $this->assertSame(7.0, $this->balance($warehouse, $item));
        $this->assertSame(1, StockTransaction::query()->where('reference_no', $invoice->code)->count());
        $this->assertSame(142.5, $invoice->totalAmount());
        $this->assertSame(142.5, (float) PartyTransaction::query()
            ->where('source_type', $invoice->getMorphClass())
            ->where('source_id', $invoice->id)
            ->value('credit'));

        $items[0]['quantity'] = 3;
        $invoice = $service->update($invoice, [
            ...$this->invoiceData($supplier, $warehouse, $order, 'STOCK-1', $items),
            'tax_type' => TaxType::Vat14->value,
        ]);

        $this->assertSame(5.0, $this->balance($warehouse, $item));
        $this->assertSame(1, StockTransaction::query()->where('reference_no', $invoice->code)->count());

        $invoice = $service->update($invoice, [
            ...$this->invoiceData($supplier, $otherWarehouse, $order, 'STOCK-1', $items),
            'tax_type' => TaxType::Vat14->value,
        ]);

        $this->assertSame(2.0, $this->balance($warehouse, $item));
        $this->assertSame(3.0, $this->balance($otherWarehouse, $item));
        $this->assertSame($otherWarehouse->id, StockTransaction::query()
            ->where('reference_no', $invoice->code)
            ->sole()
            ->warehouse_id);

        $service->delete($invoice);

        $this->assertSame(2.0, $this->balance($warehouse, $item));
        $this->assertSame(0.0, $this->balance($otherWarehouse, $item));
        $this->assertDatabaseMissing('stock_transactions', ['reference_no' => $invoice->code]);
        $this->assertDatabaseMissing('party_transactions', [
            'source_type' => $invoice->getMorphClass(),
            'source_id' => $invoice->id,
        ]);
    }

    public function test_failed_purchase_invoice_leaves_no_partial_document_or_posting(): void
    {
        [$supplier, $warehouse, $item, $order] = $this->procurementContext();
        $items = app(PurchaseInvoiceService::class)->importRemainingOrderItems($order->id);
        $beforeInvoices = DB::table('purchase_invoices')->count();
        $beforeItems = DB::table('purchase_invoice_items')->count();
        $beforeStock = StockTransaction::query()->count();
        $beforeParties = PartyTransaction::query()->count();

        try {
            app(PurchaseInvoiceService::class)->create([
                ...$this->invoiceData($supplier, $warehouse, $order, 'INVALID-TAX', $items),
                'tax_type' => 'unsupported',
            ]);
            $this->fail('Unsupported tax type must fail.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('tax_type', $exception->errors());
        }

        $this->assertSame($beforeInvoices, DB::table('purchase_invoices')->count());
        $this->assertSame($beforeItems, DB::table('purchase_invoice_items')->count());
        $this->assertSame($beforeStock, StockTransaction::query()->count());
        $this->assertSame($beforeParties, PartyTransaction::query()->count());
    }

    private function procurementContext(): array
    {
        $unit = Unit::create(['name' => 'قطعة', 'short_name' => 'ق']);
        $warehouse = Warehouse::create(['name' => 'المخزن الرئيسي', 'active' => true]);
        $item = Item::create([
            'name' => 'صنف اختبار',
            'unit_id' => $unit->id,
            'purchase_price' => 0,
            'sale_price' => 0,
            'minimum_stock' => 0,
            'allow_negative_stock' => false,
            'active' => true,
        ]);
        $supplier = Supplier::create(['name' => 'المورد الرئيسي', 'active' => true]);
        $request = app(PurchaseRequestService::class)->create([
            'request_date' => '2026-07-26',
            'warehouse_id' => $warehouse->id,
            'items' => [[
                'item_id' => $item->id,
                'unit_id' => $unit->id,
                'requested_quantity' => 10,
            ]],
        ]);
        $orderItems = app(SupplierPurchaseOrderService::class)->importRemainingItems($request->id);
        $orderItems[0]['unit_price'] = 25;
        $order = app(SupplierPurchaseOrderService::class)->create([
            'order_date' => '2026-07-26',
            'supplier_id' => $supplier->id,
            'purchase_request_id' => $request->id,
            'warehouse_id' => $warehouse->id,
            'payment_type' => 'credit',
            'due_date' => '2026-08-26',
            'items' => $orderItems,
        ]);

        return [$supplier, $warehouse, $item, $order];
    }

    private function invoiceData(
        Supplier $supplier,
        Warehouse $warehouse,
        SupplierPurchaseOrder $order,
        string $number,
        array $items,
    ): array {
        return [
            'supplier_id' => $supplier->id,
            'supplier_purchase_order_id' => $order->id,
            'invoice_number' => $number,
            'invoice_date' => '2026-07-26',
            'warehouse_id' => $warehouse->id,
            'payment_type' => 'credit',
            'due_date' => '2026-08-26',
            'items' => $items,
        ];
    }

    private function balance(Warehouse $warehouse, Item $item): float
    {
        return (float) (StockBalance::query()
            ->where('warehouse_id', $warehouse->id)
            ->where('item_id', $item->id)
            ->value('quantity') ?? 0);
    }
}
