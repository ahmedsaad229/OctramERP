<?php

namespace Tests\Feature;

use App\Enums\TaxType;
use App\Filament\Resources\SalesQuotations\Pages\ViewSalesQuotation;
use App\Models\Category;
use App\Models\CompanySetting;
use App\Models\Customer;
use App\Models\Item;
use App\Models\PartyTransaction;
use App\Models\SalesQuotation;
use App\Models\StockTransaction;
use App\Models\TreasuryTransaction;
use App\Models\Unit;
use App\Models\User;
use App\Services\SalesQuotationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class SalesQuotationPrintTest extends TestCase
{
    use RefreshDatabase;

    private SalesQuotation $quotation;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->app['auth']->logout();
        $this->quotation = $this->quotation();
    }

    public function test_unauthenticated_user_cannot_access_print_route(): void
    {
        $this->get(route('sales-quotations.print', $this->quotation))
            ->assertRedirect();
    }

    public function test_authorized_print_page_displays_stored_quotation_information_and_items(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get(route('sales-quotations.print', $this->quotation));

        $response->assertOk()
            ->assertSee($this->quotation->quotation_number)
            ->assertSee('26/07/2026')
            ->assertSee('عميل الطباعة')
            ->assertSee('ITM-PRINT-1')
            ->assertSee('ITM-PRINT-2')
            ->assertSee('123.45 ج.م')
            ->assertSee('332.77 ج.م')
            ->assertSee('إجمالي ضريبة القيمة المضافة (14%)')
            ->assertSee('thead')
            ->assertSee('table-header-group');

        $this->assertSame(1, substr_count($response->getContent(), 'عميل الطباعة'));
    }

    public function test_print_uses_commercial_company_name_and_never_laravel_fallback(): void
    {
        $this->actingAs(User::factory()->create());
        CompanySetting::current()->update(['company_name' => 'Laravel']);

        $this->get(route('sales-quotations.print', $this->quotation))
            ->assertOk()
            ->assertSee('أوكترام للمقاولات والتوريدات')
            ->assertDontSee('Laravel');

        CompanySetting::current()->update(['company_name' => 'شركة أوكترام']);
        $this->get(route('sales-quotations.print', $this->quotation))
            ->assertOk()
            ->assertSee('شركة أوكترام');
    }

    public function test_print_page_uses_stored_values_and_does_not_mutate_or_post_document(): void
    {
        $this->actingAs(User::factory()->create());
        $updatedAt = $this->quotation->updated_at;
        Item::query()->where('code', 'ITM-PRINT-1')->update(['sale_price' => 9999]);

        $this->get(route('sales-quotations.print', $this->quotation))
            ->assertOk()
            ->assertSee('123.45 ج.م')
            ->assertDontSee('9,999.00 ج.م');

        $this->assertTrue($this->quotation->fresh()->updated_at->equalTo($updatedAt));
        $this->assertSame(0, StockTransaction::count());
        $this->assertSame(0, PartyTransaction::count());
        $this->assertSame(0, TreasuryTransaction::count());
    }

    public function test_no_tax_print_displays_zero_tax_without_vat_label(): void
    {
        $this->actingAs(User::factory()->create());
        $quotation = $this->quotation(TaxType::None);

        $this->get(route('sales-quotations.print', $quotation))
            ->assertOk()
            ->assertSee('إجمالي الضريبة')
            ->assertSee('0.00 ج.م')
            ->assertDontSee('إجمالي ضريبة القيمة المضافة (14%)');
    }

    public function test_logo_is_safe_and_missing_logo_does_not_render_broken_image(): void
    {
        $this->actingAs(User::factory()->create());
        Storage::disk('public')->put('company/logo.png', 'logo');
        CompanySetting::current()->update(['logo_path' => 'company/logo.png']);

        $this->get(route('sales-quotations.print', $this->quotation))
            ->assertOk()
            ->assertSee(Storage::disk('public')->url('company/logo.png'), escape: false);

        CompanySetting::current()->update(['logo_path' => null]);
        $this->get(route('sales-quotations.print', $this->quotation))
            ->assertOk()
            ->assertDontSee('<img', escape: false);
    }

    public function test_notes_and_terms_are_escaped_and_multiline_content_is_present(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('sales-quotations.print', $this->quotation))
            ->assertOk()
            ->assertSee('&lt;script&gt;alert(&quot;x&quot;)&lt;/script&gt;', escape: false)
            ->assertDontSee('<script>alert("x")</script>', escape: false)
            ->assertSee("السطر الأول\nالسطر الثاني");
    }

    public function test_empty_notes_and_terms_sections_are_hidden(): void
    {
        $this->actingAs(User::factory()->create());
        $this->quotation->update(['notes' => null, 'terms_and_conditions' => null]);

        $this->get(route('sales-quotations.print', $this->quotation))
            ->assertOk()
            ->assertDontSee('شروط العرض')
            ->assertDontSee('<div class="block-title">ملاحظات</div>', escape: false);
    }

    public function test_view_page_print_action_targets_dedicated_route(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(ViewSalesQuotation::class, ['record' => $this->quotation->getRouteKey()])
            ->assertOk()
            ->assertSee('طباعة عرض السعر')
            ->assertSee(route('sales-quotations.print', $this->quotation), escape: false);
    }

    private function quotation(TaxType $taxType = TaxType::Vat14): SalesQuotation
    {
        $creator = User::factory()->create();
        $customer = Customer::firstOrCreate(
            ['code' => 'CUS-PRINT'],
            [
                'name' => 'عميل الطباعة',
                'mobile' => '01000000000',
                'address' => 'القاهرة',
                'tax_number' => 'TAX-123',
            ],
        );
        $category = Category::firstOrCreate(['code' => 'CAT-PRINT'], ['name' => 'تصنيف الطباعة']);
        $unit = Unit::firstOrCreate(
            ['code' => 'UNT-PRINT'],
            ['name' => 'قطعة', 'short_name' => 'ق'],
        );
        $first = Item::firstOrCreate(
            ['code' => 'ITM-PRINT-1'],
            [
                'name' => 'صنف الطباعة الأول',
                'category_id' => $category->id,
                'unit_id' => $unit->id,
                'sale_price' => 123.45,
            ],
        );
        $second = Item::firstOrCreate(
            ['code' => 'ITM-PRINT-2'],
            [
                'name' => 'وصف صنف طويل قابل للالتفاف داخل جدول عرض السعر',
                'category_id' => $category->id,
                'unit_id' => $unit->id,
                'sale_price' => 50,
            ],
        );

        return app(SalesQuotationService::class)->create([
            'quotation_date' => '2026-07-26',
            'valid_until' => '2026-08-26',
            'customer_id' => $customer->id,
            'created_by' => $creator->id,
            'tax_type' => $taxType->value,
            'notes' => '<script>alert("x")</script>',
            'terms_and_conditions' => "السطر الأول\nالسطر الثاني",
            'items' => [
                [
                    'item_id' => $first->id,
                    'unit_id' => $unit->id,
                    'quantity' => 2,
                    'unit_price' => 123.45,
                    'discount_amount' => 0,
                ],
                [
                    'item_id' => $second->id,
                    'unit_id' => $unit->id,
                    'quantity' => 1,
                    'unit_price' => 50,
                    'discount_amount' => 5,
                ],
            ],
        ]);
    }
}
