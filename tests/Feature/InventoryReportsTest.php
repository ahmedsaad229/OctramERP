<?php

namespace Tests\Feature;

use App\Filament\Pages\InventoryMovementReport;
use App\Filament\Pages\InventoryStockBalanceReport;
use App\Filament\Pages\LowStockReport;
use App\Models\Category;
use App\Models\Item;
use App\Models\StockBalance;
use App\Models\StockTransaction;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\InventoryReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class InventoryReportsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Category $category;

    private Category $otherCategory;

    private Unit $unit;

    private Warehouse $warehouse;

    private Warehouse $otherWarehouse;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.env' => 'local']);
        $this->user = User::factory()->create();
        $this->category = Category::create(['name' => 'مواد كهربائية', 'active' => true]);
        $this->otherCategory = Category::create(['name' => 'مواد ميكانيكية', 'active' => true]);
        $this->unit = Unit::create(['name' => 'قطعة', 'short_name' => 'قطعة', 'active' => true]);
        $this->warehouse = Warehouse::create(['name' => 'المخزن الرئيسي', 'active' => true]);
        $this->otherWarehouse = Warehouse::create(['name' => 'المخزن الفرعي', 'active' => true]);
    }

    public function test_inventory_report_pages_require_authentication_and_load_for_authenticated_users(): void
    {
        foreach ([
            InventoryStockBalanceReport::getUrl(),
            InventoryMovementReport::getUrl(),
            LowStockReport::getUrl(),
        ] as $url) {
            $this->get($url)->assertRedirect();
        }

        $this->actingAs($this->user);
        $balanceResponse = $this->get(InventoryStockBalanceReport::getUrl());
        $movementResponse = $this->get(InventoryMovementReport::getUrl());
        $lowStockResponse = $this->get(LowStockReport::getUrl());

        $this->assertSame(200, $balanceResponse->status(), 'Balance report did not load.');
        $this->assertSame(200, $movementResponse->status(), 'Movement report did not load.');
        $this->assertSame(200, $lowStockResponse->status(), 'Low-stock report did not load.');
        $balanceResponse->assertSee('تقرير أرصدة المخزون');
        $movementResponse->assertSee('تقرير حركة المخزون');
        $lowStockResponse->assertSee('الأصناف منخفضة الرصيد');

        $this->assertSame('التقارير', InventoryStockBalanceReport::getNavigationGroup());
        $this->assertSame('تقارير المخزون', InventoryStockBalanceReport::getNavigationParentItem());
        $this->assertSame(10, InventoryStockBalanceReport::getNavigationSort());
        $this->assertSame(20, InventoryMovementReport::getNavigationSort());
        $this->assertSame(30, LowStockReport::getNavigationSort());
    }

    public function test_balance_report_filters_totals_and_inventory_value_are_correct(): void
    {
        $first = $this->item('كابل', $this->category, 10);
        $second = $this->item('مفتاح', $this->otherCategory, 5);

        StockBalance::create([
            'warehouse_id' => $this->warehouse->id,
            'item_id' => $first->id,
            'quantity' => 10,
            'average_cost' => 25,
        ]);
        StockBalance::create([
            'warehouse_id' => $this->otherWarehouse->id,
            'item_id' => $second->id,
            'quantity' => 0,
            'average_cost' => 40,
        ]);

        $service = app(InventoryReportService::class);
        $report = $service->balances();
        $this->assertCount(2, $report['rows']);
        $this->assertSame(10.0, $report['total_quantity']);
        $this->assertSame(250.0, $report['total_value']);
        $this->assertSame(250.0, $report['rows']->firstWhere('item_name', 'كابل')['inventory_value']);

        $warehouse = $service->balances(['warehouse_id' => $this->warehouse->id]);
        $this->assertCount(1, $warehouse['rows']);
        $this->assertSame('كابل', $warehouse['rows']->first()['item_name']);

        $category = $service->balances(['category_id' => $this->otherCategory->id]);
        $this->assertCount(1, $category['rows']);
        $this->assertSame('مفتاح', $category['rows']->first()['item_name']);

        $item = $service->balances(['item_id' => $first->id]);
        $this->assertCount(1, $item['rows']);

        $nonZero = $service->balances(['has_balance' => true]);
        $this->assertCount(1, $nonZero['rows']);

        $this->actingAs($this->user);
        $component = Livewire::test(InventoryStockBalanceReport::class)
            ->set('data.warehouse_id', $this->warehouse->id)
            ->call('refreshReport')
            ->assertSee('كابل')
            ->assertSeeHtml('class="octram-report')
            ->assertSeeHtml('octram-report-scroll');

        $this->assertSame(1, substr_count($component->html(), 'تصدير Excel'));
    }

    public function test_movement_report_filters_and_returns_newest_first_with_running_balance(): void
    {
        $item = $this->item('كابل حركة', $this->category, 4);
        $otherItem = $this->item('صنف آخر', $this->otherCategory, 3);

        $this->movement($item, $this->warehouse, 'opening', 10, 20, '2026-07-01', 'OSV-000001');
        $this->movement($item, $this->warehouse, 'sale', 3, 20, '2026-07-03', 'SAL-000001');
        $this->movement($item, $this->warehouse, 'purchase', 5, 30, '2026-07-04', 'PUR-000001');
        $this->movement($otherItem, $this->otherWarehouse, 'opening', 2, 10, '2026-07-02', 'OSV-000002');

        $service = app(InventoryReportService::class);
        $report = $service->movements(['item_id' => $item->id]);

        $this->assertSame(['PUR-000001', 'SAL-000001', 'OSV-000001'], $report['rows']->pluck('reference')->all());
        $this->assertSame([12.0, 7.0, 10.0], $report['rows']->pluck('running_balance')->all());
        $this->assertSame(5.0, $report['rows'][0]['inbound']);
        $this->assertSame(3.0, $report['rows'][1]['outbound']);

        $sales = $service->movements(['transaction_type' => 'sale']);
        $this->assertCount(1, $sales['rows']);
        $this->assertSame('SAL-000001', $sales['rows']->first()['reference']);

        $dated = $service->movements(['from_date' => '2026-07-03', 'to_date' => '2026-07-03']);
        $this->assertCount(1, $dated['rows']);
        $this->assertSame(7.0, $dated['rows']->first()['running_balance']);

        $reference = $service->movements(['reference_no' => 'PUR-']);
        $this->assertCount(1, $reference['rows']);

        $warehouse = $service->movements(['warehouse_id' => $this->otherWarehouse->id]);
        $this->assertCount(1, $warehouse['rows']);
    }

    public function test_reorder_report_classifies_zero_low_and_normal_stock_and_filters(): void
    {
        $out = $this->item('نفد المخزون', $this->category, 10);
        $low = $this->item('رصيد منخفض', $this->category, 10);
        $normal = $this->item('رصيد طبيعي', $this->otherCategory, 10);

        foreach ([[$out, 0], [$low, 5], [$normal, 20]] as [$item, $quantity]) {
            StockBalance::create([
                'warehouse_id' => $this->warehouse->id,
                'item_id' => $item->id,
                'quantity' => $quantity,
                'average_cost' => 1,
            ]);
        }

        $service = app(InventoryReportService::class);
        $report = $service->lowStock();
        $this->assertSame('نفد', $report['rows']->firstWhere('item_name', 'نفد المخزون')['status_label']);
        $this->assertSame('منخفض', $report['rows']->firstWhere('item_name', 'رصيد منخفض')['status_label']);
        $this->assertSame('طبيعي', $report['rows']->firstWhere('item_name', 'رصيد طبيعي')['status_label']);
        $this->assertSame(-10.0, $report['rows']->firstWhere('item_name', 'نفد المخزون')['difference']);
        $this->assertSame(-5.0, $report['rows']->firstWhere('item_name', 'رصيد منخفض')['difference']);
        $this->assertSame(10.0, $report['rows']->firstWhere('item_name', 'رصيد طبيعي')['difference']);

        $this->assertCount(1, $service->lowStock(['status' => 'out'])['rows']);
        $this->assertCount(1, $service->lowStock(['status' => 'low'])['rows']);
        $this->assertCount(1, $service->lowStock(['status' => 'normal'])['rows']);
        $this->assertCount(2, $service->lowStock(['category_id' => $this->category->id])['rows']);
        $this->assertCount(3, $service->lowStock(['warehouse_id' => $this->warehouse->id])['rows']);
    }

    public function test_print_and_excel_exports_are_authenticated_and_use_filtered_data(): void
    {
        $item = $this->item('صنف التصدير', $this->category, 10);
        StockBalance::create([
            'warehouse_id' => $this->warehouse->id,
            'item_id' => $item->id,
            'quantity' => 4,
            'average_cost' => 12.5,
        ]);

        $print = route('inventory-reports.print', ['report' => 'balances']);
        $excel = route('inventory-reports.excel', ['report' => 'balances']);
        $this->get($print)->assertRedirect();
        $this->get($excel)->assertRedirect();

        $this->actingAs($this->user);
        $this->get($print)
            ->assertOk()
            ->assertSee('تقرير أرصدة المخزون')
            ->assertSee('صنف التصدير')
            ->assertSee('landscape', false)
            ->assertSee('dir="rtl"', false);

        $response = $this->get($excel);
        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('attachment;', (string) $response->headers->get('content-disposition'));
        $this->assertStringContainsString('صنف التصدير', $response->streamedContent());

        foreach (['movements', 'low-stock'] as $type) {
            $this->get(route('inventory-reports.print', ['report' => $type]))->assertOk();
            $this->get(route('inventory-reports.excel', ['report' => $type]))->assertOk();
        }
    }

    private function item(string $name, Category $category, float $reorderLevel): Item
    {
        return Item::create([
            'name' => $name,
            'category_id' => $category->id,
            'unit_id' => $this->unit->id,
            'reorder_level' => $reorderLevel,
            'active' => true,
        ]);
    }

    private function movement(
        Item $item,
        Warehouse $warehouse,
        string $type,
        float $quantity,
        float $cost,
        string $date,
        string $reference,
    ): void {
        StockTransaction::create([
            'warehouse_id' => $warehouse->id,
            'item_id' => $item->id,
            'transaction_type' => $type,
            'quantity' => $quantity,
            'unit_cost' => $cost,
            'transaction_date' => $date,
            'reference_no' => $reference,
        ]);
    }
}
