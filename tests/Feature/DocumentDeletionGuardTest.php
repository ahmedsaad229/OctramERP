<?php

namespace Tests\Feature;

use App\Exceptions\DocumentDeletionBlockedException;
use App\Models\Category;
use App\Models\Item;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseRequest;
use App\Models\SalesInvoice;
use App\Models\SupplierPurchaseOrder;
use App\Services\Documents\DocumentDeletionGuard;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DocumentDeletionGuardTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->schema();
    }

    public function test_purchase_request_reports_the_linked_order_code(): void
    {
        DB::table('purchase_requests')->insert(['id' => 1]);
        DB::table('supplier_purchase_orders')->insert([
            'id' => 10,
            'purchase_request_id' => 1,
            'code' => 'PO-000010',
        ]);

        try {
            app(DocumentDeletionGuard::class)->assertCanDelete(PurchaseRequest::findOrFail(1));
            $this->fail('Linked request deletion must be blocked.');
        } catch (DocumentDeletionBlockedException $exception) {
            $this->assertSame('لا يمكن حذف طلب الشراء', $exception->title());
            $this->assertSame('PO-000010', $exception->dependentDocumentNumber());
            $this->assertSame(1, $exception->linkedCount());
            $this->assertStringContainsString('PO-000010', $exception->getMessage());
        }
    }

    public function test_order_is_blocked_by_an_invoice_item_link_even_without_header_link(): void
    {
        DB::table('supplier_purchase_orders')->insert(['id' => 10, 'code' => 'PO-1']);
        DB::table('supplier_purchase_order_items')->insert(['id' => 20, 'supplier_purchase_order_id' => 10]);
        DB::table('purchase_invoices')->insert(['id' => 30, 'code' => 'PIV-1']);
        DB::table('purchase_invoice_items')->insert([
            'purchase_invoice_id' => 30,
            'supplier_purchase_order_item_id' => 20,
        ]);

        $this->expectException(DocumentDeletionBlockedException::class);
        $this->expectExceptionMessage('فاتورة شراء');
        app(DocumentDeletionGuard::class)->assertCanDelete(SupplierPurchaseOrder::findOrFail(10));
    }

    public function test_purchase_and_sales_invoices_are_blocked_by_payment_allocations(): void
    {
        DB::table('purchase_invoices')->insert(['id' => 1, 'code' => 'PIV-1']);
        DB::table('supplier_payment_voucher_allocations')->insert([
            ['purchase_invoice_id' => 1],
            ['purchase_invoice_id' => 1],
        ]);
        DB::table('sales_invoices')->insert(['id' => 2]);
        DB::table('receipt_voucher_allocations')->insert(['sales_invoice_id' => 2]);

        foreach ([PurchaseInvoice::findOrFail(1), SalesInvoice::findOrFail(2)] as $invoice) {
            try {
                app(DocumentDeletionGuard::class)->assertCanDelete($invoice);
                $this->fail('Allocated invoice deletion must be blocked.');
            } catch (DocumentDeletionBlockedException $exception) {
                $this->assertNotEmpty($exception->title());
                $this->assertNotEmpty($exception->getMessage());
            }
        }
    }

    public function test_master_data_usage_is_blocked_and_unreferenced_records_are_allowed(): void
    {
        DB::table('categories')->insert([['id' => 1], ['id' => 2]]);
        DB::table('items')->insert(['id' => 5, 'category_id' => 1]);
        DB::table('stock_transactions')->insert(['item_id' => 5]);

        foreach ([Category::findOrFail(1), Item::findOrFail(5)] as $record) {
            try {
                app(DocumentDeletionGuard::class)->assertCanDelete($record);
                $this->fail('Used master record deletion must be blocked.');
            } catch (DocumentDeletionBlockedException $exception) {
                $this->assertStringNotContainsString('SQL', $exception->getMessage());
                $this->assertStringNotContainsString('foreign', $exception->getMessage());
            }
        }

        app(DocumentDeletionGuard::class)->assertCanDelete(Category::findOrFail(2));
        $this->addToAssertionCount(1);
    }

    private function schema(): void
    {
        foreach ([
            'receipt_voucher_allocations', 'supplier_payment_voucher_allocations',
            'purchase_invoice_items', 'supplier_purchase_order_items',
            'sales_invoices', 'purchase_invoices', 'supplier_purchase_orders',
            'purchase_requests', 'stock_transactions', 'items', 'categories',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('purchase_requests', fn (Blueprint $table) => $table->id());
        Schema::create('supplier_purchase_orders', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('purchase_request_id')->nullable();
            $table->string('code');
        });
        Schema::create('supplier_purchase_order_items', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('supplier_purchase_order_id');
        });
        Schema::create('purchase_invoices', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('supplier_purchase_order_id')->nullable();
            $table->string('code');
        });
        Schema::create('purchase_invoice_items', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('purchase_invoice_id');
            $table->unsignedBigInteger('supplier_purchase_order_item_id')->nullable();
        });
        Schema::create('supplier_payment_voucher_allocations', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('purchase_invoice_id');
        });
        Schema::create('sales_invoices', fn (Blueprint $table) => $table->id());
        Schema::create('receipt_voucher_allocations', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('sales_invoice_id');
        });
        Schema::create('categories', fn (Blueprint $table) => $table->id());
        Schema::create('items', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('category_id')->nullable();
        });
        Schema::create('stock_transactions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('item_id');
        });
    }
}
