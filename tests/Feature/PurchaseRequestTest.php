<?php

namespace Tests\Feature;

use App\Exceptions\DocumentDeletionBlockedException;
use App\Filament\Resources\PurchaseRequests\Pages\CreatePurchaseRequest;
use App\Filament\Resources\PurchaseRequests\Pages\EditPurchaseRequest;
use App\Filament\Resources\PurchaseRequests\Schemas\PurchaseRequestForm;
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
use App\Services\Inventory\InventoryService;
use App\Services\PurchaseRequestService;
use App\Services\SupplierPurchaseOrderService;
use Filament\Forms\Components\DatePicker;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\Concerns\BuildsProcurementSchema;
use Tests\TestCase;

class PurchaseRequestTest extends TestCase
{
    use BuildsProcurementSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildProcurementSchema();
    }

    public function test_request_code_items_and_update_are_handled_atomically(): void
    {
        [$warehouse, $unit, $item] = $this->inventoryContext();
        $service = app(PurchaseRequestService::class);

        $request = $service->create($this->requestData($warehouse, $unit, $item, 10));

        $this->assertSame('PR-000001', $request->code);
        $this->assertCount(1, $request->items);
        $this->assertSame(10.0, (float) $request->items->first()->requested_quantity);

        $data = $this->requestData($warehouse, $unit, $item, 15);
        $data['department'] = 'المخازن';
        $updated = $service->update($request, $data);

        $this->assertSame('المخازن', $updated->department);
        $this->assertCount(1, $updated->items);
        $this->assertSame(15.0, (float) $updated->items->first()->requested_quantity);
    }

    public function test_positive_quantity_and_duplicate_items_are_validated(): void
    {
        [$warehouse, $unit, $item] = $this->inventoryContext();
        $service = app(PurchaseRequestService::class);

        try {
            $service->create($this->requestData($warehouse, $unit, $item, 0));
            $this->fail('Zero quantity should fail.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('items.0.requested_quantity', $exception->errors());
        }

        $data = $this->requestData($warehouse, $unit, $item, 1);
        $data['items'][] = $data['items'][0];

        $this->expectException(ValidationException::class);
        $service->create($data);
    }

    public function test_stock_helpers_return_warehouse_total_and_zero_balances(): void
    {
        [$warehouse, , $item] = $this->inventoryContext();
        $otherWarehouse = Warehouse::create(['name' => 'مخزن ثان', 'active' => true]);
        StockBalance::create(['warehouse_id' => $warehouse->id, 'item_id' => $item->id, 'quantity' => 4, 'average_cost' => 1]);
        StockBalance::create(['warehouse_id' => $otherWarehouse->id, 'item_id' => $item->id, 'quantity' => 6, 'average_cost' => 1]);
        $inventory = app(InventoryService::class);

        $this->assertSame(4.0, $inventory->warehouseBalance($warehouse->id, $item->id));
        $this->assertSame(6.0, $inventory->warehouseBalance($otherWarehouse->id, $item->id));
        $this->assertSame(10.0, $inventory->totalBalance($item->id));
        $this->assertSame(0.0, $inventory->warehouseBalance(null, $item->id));
        $this->assertSame(0.0, $inventory->totalBalance(999999));
    }

    public function test_request_creates_no_postings_and_reports_procurement_statuses(): void
    {
        [$warehouse, $unit, $item] = $this->inventoryContext();
        $request = app(PurchaseRequestService::class)->create($this->requestData($warehouse, $unit, $item, 10));

        $this->assertSame(PurchaseRequest::STATUS_NOT_ORDERED, $request->procurementStatus());
        $this->assertDatabaseCount((new StockTransaction)->getTable(), 0);
        $this->assertDatabaseCount((new PartyTransaction)->getTable(), 0);
        $this->assertDatabaseCount((new TreasuryTransaction)->getTable(), 0);

        $supplier = Supplier::create(['name' => 'مورد الاختبار', 'active' => true]);
        $orderService = app(SupplierPurchaseOrderService::class);
        $items = $orderService->importRemainingItems($request->id);
        $items[0]['ordered_quantity'] = 4;
        $items[0]['unit_price'] = 2;
        $orderService->create($this->orderData($request, $supplier, $warehouse, $items));

        $this->assertSame(PurchaseRequest::STATUS_PARTIALLY_ORDERED, $request->fresh()->procurementStatus());
        $items = $orderService->importRemainingItems($request->id);
        $items[0]['unit_price'] = 2;
        $orderService->create($this->orderData($request, $supplier, $warehouse, $items));
        $this->assertSame(PurchaseRequest::STATUS_FULLY_ORDERED, $request->fresh()->procurementStatus());
    }

    public function test_linked_request_cannot_be_deleted(): void
    {
        [$warehouse, $unit, $item] = $this->inventoryContext();
        $request = app(PurchaseRequestService::class)->create($this->requestData($warehouse, $unit, $item, 5));
        $supplier = Supplier::create(['name' => 'مورد', 'active' => true]);
        $items = app(SupplierPurchaseOrderService::class)->importRemainingItems($request->id);
        $items[0]['unit_price'] = 10;
        app(SupplierPurchaseOrderService::class)->create($this->orderData($request, $supplier, $warehouse, $items));

        $this->expectException(DocumentDeletionBlockedException::class);
        $this->expectExceptionMessage('PO-000001');
        app(PurchaseRequestService::class)->delete($request);
    }

    public function test_blocked_filament_delete_halts_without_redirecting(): void
    {
        [$warehouse, $unit, $item] = $this->inventoryContext();
        $request = app(PurchaseRequestService::class)->create($this->requestData($warehouse, $unit, $item, 5));
        $supplier = Supplier::create(['name' => 'مورد', 'active' => true]);
        $items = app(SupplierPurchaseOrderService::class)->importRemainingItems($request->id);
        $items[0]['unit_price'] = 10;
        app(SupplierPurchaseOrderService::class)->create(
            $this->orderData($request, $supplier, $warehouse, $items),
        );
        $user = User::create(['name' => 'مستخدم الاختبار']);

        Livewire::actingAs($user)
            ->test(EditPurchaseRequest::class, ['record' => $request->id])
            ->assertOk()
            ->callAction('delete')
            ->assertOk()
            ->assertNoRedirect()
            ->assertNotified('لا يمكن حذف طلب الشراء')
            ->assertSet('record.id', $request->id);

        $this->assertDatabaseHas('purchase_requests', ['id' => $request->id]);
    }

    public function test_purchase_request_routes_are_registered(): void
    {
        $this->assertNotNull(route('filament.admin.resources.purchase-requests.index'));
        $this->assertNotNull(route('filament.admin.resources.purchase-requests.create'));
        $this->assertNotNull(route('filament.admin.resources.purchase-requests.edit', 1));
    }

    public function test_dates_use_arabic_non_native_configuration_and_save_as_gregorian_dates(): void
    {
        $datePicker = PurchaseRequestForm::configureDatePicker(DatePicker::make('test_date'));

        $this->assertFalse($datePicker->isNative());
        $this->assertSame('ar', $datePicker->getLocale());
        $this->assertSame('d/m/Y', $datePicker->getDisplayFormat());
        $this->assertSame('Y-m-d', $datePicker->getFormat());
        $this->assertTrue($datePicker->shouldCloseOnDateSelection());
        $this->assertSame(6, $datePicker->getFirstDayOfWeek());
        $this->assertSame('يوم / شهر / سنة', $datePicker->getPlaceholder());

        [$warehouse, $unit, $item] = $this->inventoryContext();
        $data = $this->requestData($warehouse, $unit, $item, 1);
        $data['request_date'] = '2026-07-26';
        $data['required_date'] = '2026-08-05';
        $request = app(PurchaseRequestService::class)->create($data);

        $this->assertSame('2026-07-26', $request->request_date->format('Y-m-d'));
        $this->assertSame('2026-08-05', $request->required_date->format('Y-m-d'));
    }

    public function test_item_selection_uses_and_preserves_only_the_items_default_unit(): void
    {
        [$warehouse, $firstUnit, $firstItem] = $this->inventoryContext();
        $secondUnit = Unit::create(['name' => 'كيلوجرام', 'short_name' => 'كجم']);
        $secondItem = Item::create([
            'name' => 'صنف ثان',
            'unit_id' => $secondUnit->id,
            'purchase_price' => 0,
            'sale_price' => 0,
            'minimum_stock' => 0,
            'allow_negative_stock' => false,
            'active' => true,
        ]);

        $this->assertSame([
            'unit_id' => $firstUnit->id,
            'unit_name' => $firstUnit->name,
        ], PurchaseRequestForm::itemDefaults($firstItem->id));
        $this->assertSame([
            'unit_id' => $secondUnit->id,
            'unit_name' => $secondUnit->name,
        ], PurchaseRequestForm::itemDefaults($secondItem->id));

        $data = $this->requestData($warehouse, $firstUnit, $secondItem, 2);
        $data['items'][0]['unit_id'] = $firstUnit->id;
        $request = app(PurchaseRequestService::class)->create($data);

        $this->assertSame($secondUnit->id, $request->items->first()->unit_id);
    }

    public function test_item_without_default_unit_is_rejected_with_arabic_validation(): void
    {
        [$warehouse, $unit] = $this->inventoryContext();
        $item = Item::create([
            'name' => 'صنف بلا وحدة',
            'unit_id' => null,
            'purchase_price' => 0,
            'sale_price' => 0,
            'minimum_stock' => 0,
            'allow_negative_stock' => false,
            'active' => true,
        ]);

        try {
            app(PurchaseRequestService::class)->create($this->requestData($warehouse, $unit, $item, 1));
            $this->fail('An item without a default unit must be rejected.');
        } catch (ValidationException $exception) {
            $this->assertSame(
                'الصنف المختار لا توجد له وحدة افتراضية. يرجى تسجيل الوحدة في بطاقة الصنف أولًا.',
                $exception->errors()['items.0.unit_id'][0],
            );
        }
    }

    public function test_predefined_and_custom_departments_use_the_existing_department_column(): void
    {
        $options = PurchaseRequestForm::departmentOptions();
        $this->assertArrayHasKey('إدارة المشتريات', $options);
        $this->assertArrayHasKey(PurchaseRequestForm::OTHER_DEPARTMENT, $options);

        [$warehouse, $unit, $item] = $this->inventoryContext();
        $predefined = $this->requestData($warehouse, $unit, $item, 1);
        $predefined['department'] = 'إدارة المشتريات';
        $request = app(PurchaseRequestService::class)->create($predefined);
        $this->assertSame('إدارة المشتريات', $request->department);
        $this->assertSame([
            'department_choice' => 'إدارة المشتريات',
            'department_custom' => null,
        ], PurchaseRequestForm::departmentFormState($request->department));

        $custom = 'قسم التحول الرقمي';
        $request = app(PurchaseRequestService::class)->update($request, [
            ...$this->requestData($warehouse, $unit, $item, 1),
            'department' => $custom,
        ]);
        $this->assertSame($custom, $request->department);
        $this->assertSame([
            'department_choice' => PurchaseRequestForm::OTHER_DEPARTMENT,
            'department_custom' => $custom,
        ], PurchaseRequestForm::departmentFormState($request->department));
    }

    public function test_stock_warning_has_only_the_arabic_user_visible_label(): void
    {
        $source = file_get_contents(app_path(
            'Filament/Resources/PurchaseRequests/Schemas/PurchaseRequestForm.php',
        ));

        $this->assertStringNotContainsString("'Stock warning'", $source);
        $this->assertStringContainsString("->label('تنبيه الرصيد')", $source);
        $this->assertStringContainsString('الكمية المطلوبة أكبر من الرصيد المتاح بالمخزن.', $source);
    }

    public function test_quantity_greater_than_stock_is_informational_and_saves_successfully(): void
    {
        [$warehouse, $unit, $item] = $this->inventoryContext();
        StockBalance::create([
            'warehouse_id' => $warehouse->id,
            'item_id' => $item->id,
            'quantity' => 2,
            'average_cost' => 1,
        ]);

        $request = app(PurchaseRequestService::class)->create(
            $this->requestData($warehouse, $unit, $item, 100),
        );

        $this->assertSame(100.0, (float) $request->items->first()->requested_quantity);
        $this->assertDatabaseCount('purchase_requests', 1);
        $this->assertDatabaseCount('purchase_request_items', 1);
    }

    public function test_service_returns_exact_arabic_repeater_validation_messages(): void
    {
        [$warehouse, $unit, $item] = $this->inventoryContext();
        $service = app(PurchaseRequestService::class);

        foreach ([
            'no_items' => [
                'items' => [],
                'field' => 'items',
                'message' => 'يجب إضافة صنف واحد على الأقل.',
            ],
            'missing_item' => [
                'items' => [[
                    'item_id' => null,
                    'unit_id' => $unit->id,
                    'requested_quantity' => 1,
                ]],
                'field' => 'items.0.item_id',
                'message' => 'يجب اختيار الصنف.',
            ],
            'missing_quantity' => [
                'items' => [[
                    'item_id' => $item->id,
                    'unit_id' => $unit->id,
                    'requested_quantity' => null,
                ]],
                'field' => 'items.0.requested_quantity',
                'message' => 'يجب إدخال الكمية المطلوبة.',
            ],
            'zero_quantity' => [
                'items' => [[
                    'item_id' => $item->id,
                    'unit_id' => $unit->id,
                    'requested_quantity' => 0,
                ]],
                'field' => 'items.0.requested_quantity',
                'message' => 'يجب أن تكون الكمية المطلوبة أكبر من صفر.',
            ],
        ] as $case) {
            try {
                $service->create([
                    'request_date' => '2026-07-26',
                    'warehouse_id' => $warehouse->id,
                    'items' => $case['items'],
                ]);
                $this->fail("Validation case [{$case['field']}] should fail.");
            } catch (ValidationException $exception) {
                $this->assertSame($case['message'], $exception->errors()[$case['field']][0]);
            }
        }
    }

    public function test_optional_headers_do_not_block_create(): void
    {
        [$warehouse, $unit, $item] = $this->inventoryContext();
        $data = $this->requestData($warehouse, $unit, $item, 1);
        $data['warehouse_id'] = null;
        $data['requested_by'] = null;
        $data['department'] = null;
        $data['required_date'] = null;

        $request = app(PurchaseRequestService::class)->create($data);

        $this->assertNull($request->warehouse_id);
        $this->assertNull($request->requested_by);
        $this->assertNull($request->department);
        $this->assertNull($request->required_date);
    }

    public function test_filament_create_page_saves_valid_request_with_hidden_unit_state(): void
    {
        [$warehouse, $unit, $item] = $this->inventoryContext();
        $user = User::create(['name' => 'مستخدم الاختبار']);

        Livewire::actingAs($user)
            ->test(CreatePurchaseRequest::class)
            ->fillForm([
                'request_date' => '2026-07-26',
                'warehouse_id' => $warehouse->id,
                'items' => [[
                    'item_id' => $item->id,
                    'unit_id' => $unit->id,
                    'unit_name' => $unit->name,
                    'requested_quantity' => 50,
                ]],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('purchase_request_items', [
            'item_id' => $item->id,
            'unit_id' => $unit->id,
            'requested_quantity' => 50,
        ]);
    }

    public function test_filament_create_page_surfaces_missing_unit_and_danger_notification(): void
    {
        [$warehouse] = $this->inventoryContext();
        $item = Item::create([
            'name' => 'صنف بلا وحدة',
            'unit_id' => null,
            'purchase_price' => 0,
            'sale_price' => 0,
            'minimum_stock' => 0,
            'allow_negative_stock' => false,
            'active' => true,
        ]);
        $user = User::create(['name' => 'مستخدم الاختبار']);

        Livewire::actingAs($user)
            ->test(CreatePurchaseRequest::class)
            ->fillForm([
                'request_date' => '2026-07-26',
                'warehouse_id' => $warehouse->id,
                'items' => [[
                    'item_id' => $item->id,
                    'unit_id' => null,
                    'unit_name' => null,
                    'requested_quantity' => 1,
                ]],
            ])
            ->call('create')
            ->assertHasFormErrors(['items.0.unit_id' => 'required'])
            ->assertNotified('تعذر حفظ طلب الشراء');

        $this->assertDatabaseCount('purchase_requests', 0);
    }

    public function test_other_department_is_optional_and_stores_other_or_custom_text(): void
    {
        [$warehouse, $unit, $item] = $this->inventoryContext();
        $user = User::create(['name' => 'مستخدم الاختبار']);

        Livewire::actingAs($user)
            ->test(CreatePurchaseRequest::class)
            ->fillForm([
                'request_date' => '2026-07-26',
                'warehouse_id' => $warehouse->id,
                'items' => [[
                    'item_id' => $item->id,
                    'unit_id' => $unit->id,
                    'unit_name' => $unit->name,
                    'requested_quantity' => 1,
                ]],
            ])
            ->set('data.department_choice', PurchaseRequestForm::OTHER_DEPARTMENT)
            ->set('data.department_custom', null)
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('purchase_requests', [
            'department' => PurchaseRequestForm::OTHER_DEPARTMENT,
        ]);

        $customDepartment = 'إدارة العقود';
        Livewire::actingAs($user)
            ->test(CreatePurchaseRequest::class)
            ->fillForm([
                'request_date' => '2026-07-26',
                'warehouse_id' => $warehouse->id,
                'items' => [[
                    'item_id' => $item->id,
                    'unit_id' => $unit->id,
                    'unit_name' => $unit->name,
                    'requested_quantity' => 1,
                ]],
            ])
            ->set('data.department_choice', PurchaseRequestForm::OTHER_DEPARTMENT)
            ->set('data.department_custom', $customDepartment)
            ->call('create')
            ->assertHasNoFormErrors();

        $customRequest = PurchaseRequest::query()->where('department', $customDepartment)->firstOrFail();
        Livewire::actingAs($user)
            ->test(EditPurchaseRequest::class, ['record' => $customRequest->id])
            ->assertSet('data.department_choice', PurchaseRequestForm::OTHER_DEPARTMENT)
            ->assertSet('data.department_custom', $customDepartment);
    }

    public function test_authenticated_requester_is_read_only_and_edit_preserves_original_user(): void
    {
        [$warehouse, $unit, $item] = $this->inventoryContext();
        $originalRequester = User::create(['name' => 'طالب الشراء الأصلي']);
        $otherUser = User::create(['name' => 'مستخدم آخر']);

        Livewire::actingAs($originalRequester)
            ->test(CreatePurchaseRequest::class)
            ->assertSet('data.requested_by', $originalRequester->id)
            ->assertSet('data.requester_name', $originalRequester->name)
            ->assertSchemaComponentExists(
                'requester_name',
                'form',
                fn ($component): bool => $component->isDisabled(),
            )
            ->fillForm([
                'request_date' => '2026-07-26',
                'warehouse_id' => $warehouse->id,
                'items' => [[
                    'item_id' => $item->id,
                    'unit_id' => $unit->id,
                    'unit_name' => $unit->name,
                    'requested_quantity' => 1,
                ]],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $request = PurchaseRequest::query()->latest('id')->firstOrFail();
        $this->assertSame($originalRequester->id, $request->requested_by);

        Livewire::actingAs($otherUser)
            ->test(EditPurchaseRequest::class, ['record' => $request->id])
            ->assertSet('data.requested_by', $originalRequester->id)
            ->assertSet('data.requester_name', $originalRequester->name)
            ->set('data.requested_by', $otherUser->id)
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame($originalRequester->id, $request->fresh()->requested_by);
    }

    private function inventoryContext(): array
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

        return [$warehouse, $unit, $item];
    }

    private function requestData(Warehouse $warehouse, Unit $unit, Item $item, float $quantity): array
    {
        return [
            'request_date' => '2026-07-26',
            'warehouse_id' => $warehouse->id,
            'purpose' => 'تجديد المخزون',
            'items' => [[
                'item_id' => $item->id,
                'unit_id' => $unit->id,
                'requested_quantity' => $quantity,
            ]],
        ];
    }

    private function orderData(PurchaseRequest $request, Supplier $supplier, Warehouse $warehouse, array $items): array
    {
        return [
            'order_date' => '2026-07-26',
            'supplier_id' => $supplier->id,
            'purchase_request_id' => $request->id,
            'warehouse_id' => $warehouse->id,
            'payment_type' => 'cash',
            'discount_amount' => 0,
            'tax_amount' => 0,
            'items' => $items,
        ];
    }
}
