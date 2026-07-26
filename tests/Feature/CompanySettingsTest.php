<?php

namespace Tests\Feature;

use App\Enums\TaxType;
use App\Filament\Pages\CompanySettings;
use App\Filament\Resources\SalesQuotations\Pages\ViewSalesQuotation;
use App\Filament\Resources\SalesQuotations\Schemas\SalesQuotationForm;
use App\Models\Category;
use App\Models\CompanySetting;
use App\Models\Customer;
use App\Models\Item;
use App\Models\SalesQuotation;
use App\Models\Unit;
use App\Models\User;
use App\Services\CompanyTaxSetting;
use App\Services\SalesQuotationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use ReflectionMethod;
use Tests\TestCase;

class CompanySettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->actingAs(User::factory()->create());
    }

    public function test_company_logo_path_is_persisted_and_displayed_in_quotation_view(): void
    {
        $logo = UploadedFile::fake()->image('logo.png', 320, 120);

        Livewire::test(CompanySettings::class)
            ->set('data.company_name', 'شركة أوكترام')
            ->set('data.default_tax_type', TaxType::Vat14->value)
            ->set('data.logo_path', [$logo])
            ->call('save')
            ->assertHasNoErrors();

        $settings = CompanySetting::current();
        $this->assertNotNull($settings->logo_path);
        Storage::disk('public')->assertExists($settings->logo_path);

        Livewire::test(ViewSalesQuotation::class, ['record' => $this->quotation()->getRouteKey()])
            ->assertSee('شركة أوكترام')
            ->assertSee(Storage::disk('public')->url($settings->logo_path), escape: false);
    }

    public function test_missing_logo_does_not_render_a_broken_image(): void
    {
        CompanySetting::current()->update([
            'company_name' => 'شركة بدون شعار',
            'logo_path' => null,
        ]);

        Livewire::test(ViewSalesQuotation::class, ['record' => $this->quotation()->getRouteKey()])
            ->assertSee('شركة بدون شعار')
            ->assertDontSee('<img', escape: false);
    }

    public function test_logo_replacement_removes_the_previous_file(): void
    {
        Storage::disk('public')->put('company/old.png', 'old');
        CompanySetting::current()->update(['logo_path' => 'company/old.png']);
        $replacement = UploadedFile::fake()->image('replacement.webp', 300, 100);

        Livewire::test(CompanySettings::class)
            ->set('data.company_name', 'Octram ERP')
            ->set('data.default_tax_type', TaxType::Vat14->value)
            ->set('data.logo_path', [$replacement])
            ->call('save')
            ->assertHasNoErrors();

        $newPath = CompanySetting::current()->logo_path;
        $this->assertNotSame('company/old.png', $newPath);
        Storage::disk('public')->assertMissing('company/old.png');
        Storage::disk('public')->assertExists($newPath);
    }

    public function test_company_tax_defaults_to_vat_and_can_be_changed_to_no_tax(): void
    {
        $this->assertSame(TaxType::Vat14, app(CompanyTaxSetting::class)->resolve());

        Livewire::test(CompanySettings::class)
            ->set('data.company_name', 'Octram ERP')
            ->set('data.default_tax_type', TaxType::None->value)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(TaxType::None, CompanySetting::current()->fresh()->default_tax_type);
        $this->assertSame(TaxType::None, app(CompanyTaxSetting::class)->resolve());
    }

    public function test_logo_can_be_removed(): void
    {
        Storage::disk('public')->put('company/remove.png', 'logo');
        CompanySetting::current()->update(['logo_path' => 'company/remove.png']);

        Livewire::test(CompanySettings::class)
            ->set('data.company_name', 'Octram ERP')
            ->set('data.default_tax_type', TaxType::Vat14->value)
            ->set('data.logo_path', [])
            ->call('save')
            ->assertHasNoErrors();

        $this->assertNull(CompanySetting::current()->fresh()->logo_path);
        Storage::disk('public')->assertMissing('company/remove.png');
    }

    public function test_quotation_item_grid_uses_requested_scoped_responsive_layout(): void
    {
        $form = file_get_contents(app_path('Filament/Resources/SalesQuotations/Schemas/SalesQuotationForm.php'));

        $this->assertStringContainsString('octram-quotation-item-code-box', $form);
        $this->assertStringContainsString('unicode-bidi: isolate', $form);
        $this->assertStringContainsString('overflow-wrap: normal', $styles = file_get_contents(resource_path('views/filament/styles/sales-quotation-entries.blade.php')));
        $this->assertStringContainsString("'dir' => 'ltr'", $form);
        $this->assertMatchesRegularExpression("/item_code.*columnSpan\\(\\['xl' => 2\\]\\)/s", $form);
        $this->assertMatchesRegularExpression("/decimal\\('quantity'.*columnSpan\\(\\['xl' => 1\\]\\)/", $form);
        $this->assertMatchesRegularExpression("/decimal\\('discount_amount'.*columnSpan\\(\\['xl' => 2\\]\\)/", $form);
        $this->assertStringContainsString("Grid::make(['default' => 1, 'md' => 2, 'xl' => 12])", $form);
    }

    public function test_quotation_read_only_item_values_use_scoped_centered_entry_styling(): void
    {
        $form = file_get_contents(app_path('Filament/Resources/SalesQuotations/Schemas/SalesQuotationForm.php'));
        $styles = file_get_contents(resource_path('views/filament/styles/sales-quotation-entries.blade.php'));

        foreach (['tax_amount_display', 'line_total_display', 'warehouse_balance', 'total_balance'] as $component) {
            $this->assertMatchesRegularExpression(
                "/Placeholder::make\\('{$component}'\\).*extraEntryWrapperAttributes\\(self::centeredWrapperAttributes\\(\\)\\)/s",
                $form,
            );
        }

        $this->assertStringContainsString('octram-quotation-centered-entry', $styles);
        $this->assertStringContainsString('justify-content: center', $styles);
        $this->assertStringContainsString('text-align: center', $styles);
        $this->assertStringContainsString('min-height: 2.5rem', $styles);
        $this->assertStringContainsString('unicode-bidi: isolate', $form);
        $this->assertDoesNotMatchRegularExpression('/(^|})\\s*\\.fi-/m', $styles);
    }

    public function test_quotation_unit_and_money_entries_use_compact_no_clipping_styles(): void
    {
        $form = file_get_contents(app_path('Filament/Resources/SalesQuotations/Schemas/SalesQuotationForm.php'));
        $styles = file_get_contents(resource_path('views/filament/styles/sales-quotation-entries.blade.php'));

        $this->assertMatchesRegularExpression(
            "/Placeholder::make\\('unit_name_display'\\).*octram-quotation-unit-box.*columnSpan\\(\\['xl' => 1\\]\\)/s",
            $form,
        );
        $this->assertStringContainsString('octram-quotation-summary-box', $form);
        $this->assertStringContainsString('min-width: 0', $styles);
        $this->assertStringContainsString('overflow: hidden', $styles);
        $this->assertStringContainsString('unicode-bidi: isolate', $form);
        $this->assertStringContainsString('line-height: 1.25', $styles);
        $this->assertStringContainsString('padding-inline: 0.5rem', $styles);
        $this->assertDoesNotMatchRegularExpression('/(^|})\\s*\\.fi-/m', $styles);

        $money = new ReflectionMethod(SalesQuotationForm::class, 'money');
        $this->assertSame('0.00 ج.م', $money->invoke(null, 0));
        $this->assertSame('28.00 ج.م', $money->invoke(null, 28));
        $this->assertSame('350.00 ج.م', $money->invoke(null, 350));
        $this->assertSame('2,850.00 ج.م', $money->invoke(null, 2850));
    }

    public function test_quotation_uses_two_row_twelve_column_item_layout_without_obsolete_width_rules(): void
    {
        $form = file_get_contents(app_path('Filament/Resources/SalesQuotations/Schemas/SalesQuotationForm.php'));
        $styles = file_get_contents(resource_path('views/filament/styles/sales-quotation-entries.blade.php'));

        $this->assertMatchesRegularExpression(
            "/decimal\\('quantity', 'الكمية', true\\).*columnSpan\\(\\['xl' => 1\\]\\)/",
            $form,
        );
        $this->assertMatchesRegularExpression(
            "/decimal\\('unit_price', 'سعر الوحدة'\\).*columnSpan\\(\\['xl' => 2\\]\\)/",
            $form,
        );
        $this->assertStringContainsString("Grid::make(['default' => 1, 'sm' => 2, 'xl' => 12])", $form);
        foreach (['tax_amount_display', 'line_total_display', 'warehouse_balance', 'total_balance'] as $component) {
            $this->assertMatchesRegularExpression(
                "/Placeholder::make\\('{$component}'\\).*columnSpan\\(\\['xl' => 3\\]\\)/s",
                $form,
            );
        }
        $this->assertMatchesRegularExpression("/Textarea::make\\('notes'\\).*columnSpanFull\\(\\)/", $form);
        $this->assertStringNotContainsString('octram-quotation-quantity-input', $form);
        $this->assertStringNotContainsString('octram-quotation-unit-price-input', $form);
        $this->assertStringNotContainsString('octram-quotation-unit-entry', $styles);
        $this->assertStringNotContainsString('max-width', $styles);
        $this->assertStringContainsString('octram-quotation-item-code-box', $form);
        $this->assertStringContainsString('octram-quotation-summary-box', $form);
        $this->assertDoesNotMatchRegularExpression('/(^|})\\s*\\.fi-/m', $styles);
    }

    private function quotation(): SalesQuotation
    {
        $customer = Customer::create(['code' => 'CUS-LOGO', 'name' => 'عميل الشعار']);
        $category = Category::create(['code' => 'CAT-LOGO', 'name' => 'تصنيف']);
        $unit = Unit::create(['code' => 'UNT-LOGO', 'name' => 'قطعة', 'short_name' => 'ق']);
        $item = Item::create([
            'code' => 'ITM-LOGO',
            'name' => 'صنف',
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'sale_price' => 100,
        ]);

        return app(SalesQuotationService::class)->create([
            'quotation_date' => '2026-07-26',
            'customer_id' => $customer->id,
            'items' => [[
                'item_id' => $item->id,
                'unit_id' => $unit->id,
                'quantity' => 1,
                'unit_price' => 100,
                'discount_amount' => 0,
            ]],
        ]);
    }
}
