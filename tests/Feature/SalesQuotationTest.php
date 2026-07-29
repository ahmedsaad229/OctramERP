<?php

namespace Tests\Feature;

use App\Enums\TaxType;
use App\Exceptions\DocumentDeletionBlockedException;
use App\Filament\Resources\SalesQuotations\Pages\CreateSalesQuotation;
use App\Filament\Resources\SalesQuotations\Pages\ListSalesQuotations;
use App\Filament\Resources\SalesQuotations\Pages\ViewSalesQuotation;
use App\Filament\Resources\SalesQuotations\Widgets\SalesQuotationStats;
use App\Models\Category;
use App\Models\CompanySetting;
use App\Models\Customer;
use App\Models\Item;
use App\Models\PartyTransaction;
use App\Models\SalesQuotation;
use App\Models\StockBalance;
use App\Models\StockTransaction;
use App\Models\TreasuryTransaction;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\Inventory\SalesInvoiceService;
use App\Services\SalesQuotationConversionService;
use App\Services\SalesQuotationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class SalesQuotationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Customer $customer;

    private Warehouse $warehouse;

    private Unit $unit;

    private Item $item;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->admin()->create();
        $this->actingAs($this->user);
        $this->customer = Customer::create(['code' => 'CUS-Q', 'name' => 'عميل العرض']);
        $this->warehouse = Warehouse::create(['code' => 'WH-Q', 'name' => 'مخزن العرض']);
        $category = Category::create(['code' => 'CAT-Q', 'name' => 'تصنيف']);
        $this->unit = Unit::create(['code' => 'UNT-Q', 'name' => 'قطعة', 'short_name' => 'ق']);
        $this->item = Item::create([
            'code' => 'ITM-Q', 'name' => 'صنف العرض', 'category_id' => $category->id,
            'unit_id' => $this->unit->id, 'sale_price' => 100,
        ]);
    }

    public function test_create_numbers_and_calculates_quotation_without_posting(): void
    {
        $quotation = $this->quotation();
        $second = $this->quotation(['tax_type' => TaxType::None->value]);

        $this->assertSame('QUO-000001', $quotation->quotation_number);
        $this->assertSame('QUO-000002', $second->quotation_number);
        $this->assertSame(200.0, (float) $quotation->subtotal);
        $this->assertSame(10.0, (float) $quotation->discount_amount);
        $this->assertSame(26.6, (float) $quotation->tax_amount);
        $this->assertSame(216.6, (float) $quotation->total_amount);
        $this->assertDatabaseCount('stock_transactions', 0);
        $this->assertDatabaseCount('stock_balances', 0);
        $this->assertSame(0, PartyTransaction::count());
        $this->assertSame(0, TreasuryTransaction::count());
    }

    public function test_tax_defaults_switching_and_edit_preservation(): void
    {
        $defaultPayload = $this->payload();
        unset($defaultPayload['tax_type']);
        $defaultPayload['items'][0]['discount_amount'] = 0;
        $quotation = app(SalesQuotationService::class)->create($defaultPayload);

        $this->assertSame(TaxType::Vat14, $quotation->tax_type);
        $this->assertSame(28.0, (float) $quotation->tax_amount);
        $this->assertSame(228.0, (float) $quotation->total_amount);

        $withoutTax = app(SalesQuotationService::class)->update($quotation, [
            ...$this->payload(),
            'tax_type' => TaxType::None->value,
            'items' => [[
                'item_id' => $this->item->id,
                'unit_id' => $this->unit->id,
                'quantity' => 2,
                'unit_price' => 100,
                'discount_amount' => 0,
            ]],
        ]);
        $this->assertSame(TaxType::None, $withoutTax->tax_type);
        $this->assertSame(0.0, (float) $withoutTax->tax_amount);
        $this->assertSame(200.0, (float) $withoutTax->total_amount);

        $withVat = app(SalesQuotationService::class)->update($withoutTax, [
            ...$this->payload(),
            'tax_type' => TaxType::Vat14->value,
            'items' => [[
                'item_id' => $this->item->id,
                'unit_id' => $this->unit->id,
                'quantity' => 2,
                'unit_price' => 100,
                'discount_amount' => 0,
            ]],
        ]);
        $this->assertSame(TaxType::Vat14, $withVat->tax_type);
        $this->assertSame(28.0, (float) $withVat->tax_amount);
        $this->assertSame(228.0, (float) $withVat->total_amount);

        $preservedPayload = $this->payload();
        unset($preservedPayload['tax_type']);
        $preserved = app(SalesQuotationService::class)->update($withVat, $preservedPayload);
        $this->assertSame(TaxType::Vat14, $preserved->tax_type);
    }

    public function test_company_tax_default_and_explicit_quotation_override_are_respected(): void
    {
        CompanySetting::current()->update(['default_tax_type' => TaxType::None->value]);
        $companyDefault = $this->payload();
        unset($companyDefault['tax_type']);
        $companyDefault['items'][0]['discount_amount'] = 0;

        $withoutTax = app(SalesQuotationService::class)->create($companyDefault);
        $this->assertSame(TaxType::None, $withoutTax->tax_type);
        $this->assertSame(0.0, (float) $withoutTax->tax_amount);
        $this->assertSame(200.0, (float) $withoutTax->total_amount);

        $withExplicitVat = app(SalesQuotationService::class)->create([
            ...$companyDefault,
            'tax_type' => TaxType::Vat14->value,
        ]);
        $this->assertSame(TaxType::Vat14, $withExplicitVat->tax_type);
        $this->assertSame(28.0, (float) $withExplicitVat->tax_amount);
        $this->assertSame(228.0, (float) $withExplicitVat->total_amount);
    }

    public function test_sales_invoice_uses_company_default_and_edit_preserves_its_tax_type(): void
    {
        CompanySetting::current()->update(['default_tax_type' => TaxType::None->value]);
        $this->seedStock(20);
        $data = [
            'electronic_invoice_number' => 9020,
            'payment_type' => 'cash',
            'invoice_date' => '2026-07-26',
            'customer_id' => $this->customer->id,
            'warehouse_id' => $this->warehouse->id,
            'items' => [[
                'item_id' => $this->item->id,
                'quantity' => 2,
                'unit_price' => 100,
            ]],
        ];

        $invoice = app(SalesInvoiceService::class)->create($data);
        $this->assertSame(TaxType::None, $invoice->tax_type);
        $this->assertSame(200.0, $invoice->totalAmount());

        CompanySetting::current()->update(['default_tax_type' => TaxType::Vat14->value]);
        $updated = app(SalesInvoiceService::class)->update($invoice, $data);
        $this->assertSame(TaxType::None, $updated->tax_type);
        $this->assertSame(200.0, $updated->totalAmount());
    }

    public function test_filament_list_and_create_pages_load(): void
    {
        Livewire::test(ListSalesQuotations::class)->assertOk();
        $createPage = Livewire::test(CreateSalesQuotation::class)->assertOk();
        $html = $createPage->html();
        $this->assertGreaterThanOrEqual(4, substr_count($html, 'octram-quotation-centered-entry'));
        $this->assertStringContainsString('octram-quotation-money-box', $html);
        $this->assertStringContainsString('octram-quotation-stock-box', $html);
        $this->assertStringContainsString('octram-quotation-unit-box', $html);
        $this->assertStringContainsString('octram-quotation-item-code-box', $html);
        $this->assertStringContainsString('0.00 ج.م', $html);
        Livewire::test(SalesQuotationStats::class)->assertOk();
        Livewire::test(ViewSalesQuotation::class, ['record' => $this->quotation()->getRouteKey()])
            ->assertOk();
    }

    public function test_selected_item_code_and_unit_render_inside_read_only_boxes(): void
    {
        Livewire::test(CreateSalesQuotation::class)
            ->fillForm([
                'items' => [[
                    'item_id' => $this->item->id,
                    'item_code_state' => $this->item->code,
                    'unit_id' => $this->unit->id,
                    'unit_name' => $this->unit->name,
                    'quantity' => 1,
                    'unit_price' => 100,
                    'discount_amount' => 0,
                ]],
            ])
            ->assertSee($this->item->code)
            ->assertSee($this->unit->name)
            ->assertSee('octram-quotation-item-code-box', escape: false)
            ->assertSee('octram-quotation-unit-box', escape: false)
            ->assertSee('dir="ltr"', escape: false)
            ->assertSee('unicode-bidi: isolate', escape: false);
    }

    public function test_item_without_unit_is_excluded_and_valid_item_can_be_saved(): void
    {
        $itemWithoutUnit = Item::create([
            'code' => 'ITM-NO-UNIT',
            'name' => 'صنف بدون وحدة',
            'category_id' => $this->item->category_id,
            'unit_id' => null,
            'sale_price' => 50,
            'active' => true,
        ]);

        Livewire::test(CreateSalesQuotation::class)
            ->assertDontSee($itemWithoutUnit->name)
            ->assertSee($this->item->name)
            ->fillForm([
                'quotation_date' => '2026-07-26',
                'valid_until' => '2026-08-26',
                'customer_id' => $this->customer->id,
                'warehouse_id' => $this->warehouse->id,
                'tax_type' => TaxType::Vat14->value,
                'items' => [[
                    'item_id' => $this->item->id,
                    'unit_id' => $this->unit->id,
                    'quantity' => 2,
                    'unit_price' => 100,
                    'discount_amount' => 0,
                ]],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('sales_quotations', [
            'customer_id' => $this->customer->id,
            'total_amount' => 228,
        ]);
        $this->assertDatabaseHas('sales_quotation_items', [
            'item_id' => $this->item->id,
            'unit_id' => $this->unit->id,
            'quantity' => 2,
        ]);
    }

    public function test_edit_and_delete_unlinked_quotation(): void
    {
        $quotation = $this->quotation();
        $updated = app(SalesQuotationService::class)->update($quotation, [
            ...$this->payload(),
            'notes' => 'تم التعديل',
            'items' => [[
                'item_id' => $this->item->id, 'unit_id' => $this->unit->id,
                'quantity' => 3, 'unit_price' => 50, 'discount_amount' => 0,
            ]],
        ]);
        $this->assertSame('تم التعديل', $updated->notes);
        $this->assertSame(150.0, (float) $updated->subtotal);
        $this->assertTrue(app(SalesQuotationService::class)->delete($updated));
        $this->assertDatabaseCount('sales_quotations', 0);
    }

    public function test_validation_rejects_invalid_unit_and_valid_until(): void
    {
        $other = Unit::create(['code' => 'UNT-X', 'name' => 'أخرى', 'short_name' => 'خ']);
        foreach ([
            ['valid_until' => '2026-07-25'],
            ['items' => [[
                'item_id' => $this->item->id, 'unit_id' => $other->id,
                'quantity' => 2, 'unit_price' => 100, 'discount_amount' => 0,
            ]]],
        ] as $override) {
            try {
                app(SalesQuotationService::class)->create(array_replace($this->payload(), $override));
                $this->fail('Invalid quotation should fail.');
            } catch (ValidationException) {
                $this->assertTrue(true);
            }
        }
    }

    public function test_partial_multiple_conversion_remaining_edit_delete_and_protection(): void
    {
        $quotation = $this->quotation([
            'tax_type' => TaxType::None->value,
            'items' => [[
                'item_id' => $this->item->id, 'unit_id' => $this->unit->id,
                'quantity' => 10, 'unit_price' => 100, 'discount_amount' => 0,
            ]],
        ]);
        $this->seedStock(20);
        $conversion = app(SalesQuotationConversionService::class);
        $firstData = $conversion->payload($quotation->id);
        $firstData['items'][0]['quantity'] = 4;
        $first = app(SalesInvoiceService::class)->create([
            ...$firstData, 'electronic_invoice_number' => 9001, 'payment_type' => 'cash',
            'invoice_date' => '2026-07-26',
        ]);

        $quotation->refresh()->load('items');
        $this->assertSame(4.0, $quotation->items->first()->invoicedQuantity());
        $this->assertSame(6.0, $quotation->items->first()->remainingQuantity());
        $this->assertTrue($quotation->isPartiallyConverted());
        $this->expectException(DocumentDeletionBlockedException::class);
        app(SalesQuotationService::class)->delete($quotation);
    }

    public function test_full_conversion_and_invoice_deletion_restore_availability(): void
    {
        $quotation = $this->quotation([
            'tax_type' => TaxType::None->value,
            'items' => [[
                'item_id' => $this->item->id, 'unit_id' => $this->unit->id,
                'quantity' => 10, 'unit_price' => 100, 'discount_amount' => 0,
            ]],
        ]);
        $this->seedStock(20);
        $payload = app(SalesQuotationConversionService::class)->payload($quotation->id);
        $invoice = app(SalesInvoiceService::class)->create([
            ...$payload, 'electronic_invoice_number' => 9002, 'payment_type' => 'cash',
            'invoice_date' => '2026-07-26',
        ]);
        $this->assertTrue($quotation->fresh()->load('items')->isFullyConverted());
        $this->assertArrayNotHasKey($quotation->id, app(SalesQuotationConversionService::class)->options());
        app(SalesInvoiceService::class)->delete($invoice);
        $this->assertSame(10.0, $quotation->items()->first()->remainingQuantity());
        $this->assertArrayHasKey($quotation->id, app(SalesQuotationConversionService::class)->options());
    }

    public function test_cannot_exceed_remaining_or_link_another_customer(): void
    {
        $quotation = $this->quotation([
            'tax_type' => TaxType::None->value,
            'items' => [[
                'item_id' => $this->item->id, 'unit_id' => $this->unit->id,
                'quantity' => 10, 'unit_price' => 100, 'discount_amount' => 0,
            ]],
        ]);
        $this->seedStock(30);
        $payload = app(SalesQuotationConversionService::class)->payload($quotation->id);
        $payload['items'][0]['quantity'] = 11;
        $base = [...$payload, 'electronic_invoice_number' => 9003, 'payment_type' => 'cash', 'invoice_date' => '2026-07-26'];
        foreach ([
            $base,
            [...$base, 'customer_id' => Customer::create(['code' => 'CUS-X', 'name' => 'آخر'])->id],
        ] as $data) {
            try {
                app(SalesInvoiceService::class)->create($data);
                $this->fail('Invalid quotation conversion should fail.');
            } catch (ValidationException) {
                $this->assertTrue(true);
            }
        }
    }

    public function test_multiple_invoices_and_invoice_edit_recalculate_remaining_quantity(): void
    {
        $quotation = $this->quotation([
            'tax_type' => TaxType::None->value,
            'items' => [[
                'item_id' => $this->item->id, 'unit_id' => $this->unit->id,
                'quantity' => 10, 'unit_price' => 100, 'discount_amount' => 0,
            ]],
        ]);
        $this->seedStock(30);
        $conversion = app(SalesQuotationConversionService::class);
        $firstPayload = $conversion->payload($quotation->id);
        $firstPayload['items'][0]['quantity'] = 4;
        $first = app(SalesInvoiceService::class)->create([
            ...$firstPayload, 'electronic_invoice_number' => 9010, 'payment_type' => 'cash',
            'invoice_date' => '2026-07-26',
        ]);

        $secondPayload = $conversion->payload($quotation->id);
        $this->assertSame(6.0, $secondPayload['items'][0]['quantity']);
        $secondPayload['items'][0]['quantity'] = 3;
        app(SalesInvoiceService::class)->create([
            ...$secondPayload, 'electronic_invoice_number' => 9011, 'payment_type' => 'cash',
            'invoice_date' => '2026-07-26',
        ]);
        $this->assertSame(3.0, $quotation->items()->first()->remainingQuantity());

        $editPayload = $conversion->payload($quotation->id, $first->id);
        $editPayload['items'][0]['quantity'] = 2;
        app(SalesInvoiceService::class)->update($first, [
            ...$editPayload, 'electronic_invoice_number' => 9010, 'payment_type' => 'cash',
            'invoice_date' => '2026-07-26',
        ]);

        $this->assertSame(5.0, $quotation->items()->first()->remainingQuantity());
    }

    private function quotation(array $override = []): SalesQuotation
    {
        return app(SalesQuotationService::class)->create(array_replace_recursive($this->payload(), $override));
    }

    private function payload(): array
    {
        return [
            'quotation_date' => '2026-07-26', 'valid_until' => '2026-08-26',
            'customer_id' => $this->customer->id, 'warehouse_id' => $this->warehouse->id,
            'tax_type' => TaxType::Vat14->value, 'notes' => null, 'terms_and_conditions' => null,
            'items' => [[
                'item_id' => $this->item->id, 'unit_id' => $this->unit->id,
                'quantity' => 2, 'unit_price' => 100, 'discount_amount' => 10, 'notes' => null,
            ]],
        ];
    }

    private function seedStock(float $quantity): void
    {
        StockTransaction::create([
            'warehouse_id' => $this->warehouse->id, 'item_id' => $this->item->id,
            'transaction_type' => StockTransaction::TYPE_OPENING, 'quantity' => $quantity,
            'unit_cost' => 50, 'transaction_date' => '2026-07-25', 'reference_no' => 'OPEN-Q',
        ]);
        StockBalance::create([
            'warehouse_id' => $this->warehouse->id, 'item_id' => $this->item->id,
            'quantity' => $quantity, 'average_cost' => 50,
        ]);
    }
}
