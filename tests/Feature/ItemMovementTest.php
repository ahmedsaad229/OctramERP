<?php

namespace Tests\Feature;

use App\Filament\Pages\ItemMovement;
use App\Models\Category;
use App\Models\Item;
use App\Models\StockBalance;
use App\Models\StockTransaction;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\ItemMovementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ItemMovementTest extends TestCase
{
    use RefreshDatabase;

    public function test_report_replays_historical_weighted_average_and_filters_visible_rows(): void
    {
        $category = Category::create(['name' => 'تصنيف الاختبار', 'active' => true]);
        $unit = Unit::create(['name' => 'قطعة', 'short_name' => 'قطعة', 'active' => true]);
        $item = Item::create(['name' => 'صنف الاختبار', 'category_id' => $category->getKey(), 'unit_id' => $unit->getKey(), 'active' => true]);
        $warehouse = Warehouse::create(['name' => 'المخزن الأول', 'active' => true]);
        $otherWarehouse = Warehouse::create(['name' => 'المخزن الثاني', 'active' => true]);

        $this->movement($warehouse, $item, 'opening', 10, 10, '2026-01-01', 'OSV-000001');
        $this->movement($warehouse, $item, 'purchase', 10, 20, '2026-01-10', 'PUR-000001');
        $this->movement($warehouse, $item, 'sale', 5, 0, '2026-01-11', 'SAL-000001');
        $this->movement($otherWarehouse, $item, 'purchase', 4, 30, '2026-01-12', 'GRV-000001');

        $beforeTransactions = StockTransaction::count();
        $beforeBalances = StockBalance::count();
        $report = app(ItemMovementService::class)->report(
            $item->getKey(), null, '2026-01-10', '2026-01-12',
        );

        $this->assertSame(10.0, $report['openingQuantity']);
        $this->assertSame(100.0, $report['openingValue']);
        $this->assertSame(14.0, $report['totalInbound']);
        $this->assertSame(5.0, $report['totalOutbound']);
        $this->assertSame(19.0, $report['closingQuantity']);
        $this->assertEqualsWithDelta(345.0, $report['closingValue'], 0.001);
        $this->assertSame([20.0, 15.0, 19.0], $report['rows']->pluck('runningQuantity')->all());
        $this->assertEqualsWithDelta(75.0, $report['rows'][1]['movementValue'], 0.001);
        $this->assertSame($beforeTransactions, StockTransaction::count());
        $this->assertSame($beforeBalances, StockBalance::count());

        $salesOnly = app(ItemMovementService::class)->report(
            $item->getKey(), null, '2026-01-10', '2026-01-12', StockTransaction::TYPE_SALE,
        );
        $this->assertCount(1, $salesOnly['rows']);
        $this->assertSame(0.0, $salesOnly['totalInbound']);
        $this->assertSame(5.0, $salesOnly['totalOutbound']);
        $this->assertSame(19.0, $salesOnly['closingQuantity']);

        $warehouseReport = app(ItemMovementService::class)->report(
            $item->getKey(), $warehouse->getKey(), '2026-01-10', '2026-01-12',
        );
        $this->assertSame(15.0, $warehouseReport['closingQuantity']);
        $this->assertSame(225.0, $warehouseReport['closingValue']);
    }

    public function test_page_and_print_access_and_new_tab_link(): void
    {
        $this->get('/admin/item-movement')->assertRedirect();
        $this->get('/admin/item-movement/print')->assertRedirect();

        $user = User::factory()->create();
        $this->actingAs($user);
        $category = Category::create(['name' => 'تصنيف', 'active' => true]);
        $unit = Unit::create(['name' => 'قطعة', 'short_name' => 'قطعة', 'active' => true]);
        $item = Item::create(['name' => 'صنف', 'category_id' => $category->getKey(), 'unit_id' => $unit->getKey(), 'active' => true]);
        $warehouse = Warehouse::create(['name' => 'مخزن التقرير', 'active' => true]);
        $this->movement($warehouse, $item, 'opening', 5, 10, '2026-07-01', 'OSV-REPORT');

        $this->assertSame('المخازن', ItemMovement::getNavigationGroup());
        $this->assertSame('حركة الصنف', ItemMovement::getNavigationLabel());
        $this->assertSame('عمليات المخزون', ItemMovement::getNavigationParentItem());
        $this->assertSame(50, ItemMovement::getNavigationSort());

        $component = Livewire::test(ItemMovement::class)
            ->call('runReport')
            ->assertHasErrors(['data.item_id'])
            ->set('data.item_id', $item->getKey())
            ->call('runReport')
            ->assertHasNoErrors()
            ->assertSee('OSV-REPORT')
            ->assertSeeHtml('octram-report-scroll');
        $this->assertSame(1, substr_count($component->html(), 'تصدير Excel'));

        $url = route('item-movement.print', ['item' => $item->getKey()]);
        $this->get($url)->assertOk()->assertSee('حركة صنف')->assertSee('landscape', false);
        $export = $this->get(route('item-movement.excel', ['item' => $item->getKey()]));
        $export->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('OSV-REPORT', $export->streamedContent());
    }

    private function movement(Warehouse $warehouse, Item $item, string $type, float $quantity, float $cost, string $date, string $reference): void
    {
        StockTransaction::create([
            'warehouse_id' => $warehouse->getKey(),
            'item_id' => $item->getKey(),
            'transaction_type' => $type,
            'quantity' => $quantity,
            'unit_cost' => $cost,
            'transaction_date' => $date,
            'reference_no' => $reference,
        ]);
    }
}
