<?php

namespace App\Services\Documents;

use App\Exceptions\DocumentDeletionBlockedException;
use App\Models\Category;
use App\Models\CashAdvance;
use App\Models\Customer;
use App\Models\Item;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseRequest;
use App\Models\SalesInvoice;
use App\Models\SalesQuotation;
use App\Models\Supplier;
use App\Models\SupplierPurchaseOrder;
use App\Models\Treasury;
use App\Models\Unit;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DocumentDeletionGuard
{
    public function assertCanDelete(Model $document): void
    {
        match (true) {
            $document instanceof CashAdvance => $this->assertCashAdvanceCanBeDeleted($document),
            $document instanceof PurchaseRequest => $this->assertPurchaseRequestCanBeDeleted($document),
            $document instanceof SupplierPurchaseOrder => $this->assertSupplierPurchaseOrderCanBeDeleted($document),
            $document instanceof PurchaseInvoice => $this->assertPurchaseInvoiceCanBeDeleted($document),
            $document instanceof SalesInvoice => $this->assertSalesInvoiceCanBeDeleted($document),
            $document instanceof SalesQuotation => $this->assertSalesQuotationCanBeDeleted($document),
            $document instanceof Customer => $this->assertCustomerCanBeDeleted($document),
            $document instanceof Supplier => $this->assertSupplierCanBeDeleted($document),
            $document instanceof Item => $this->assertItemCanBeDeleted($document),
            $document instanceof Warehouse => $this->assertWarehouseCanBeDeleted($document),
            $document instanceof Unit => $this->assertUnitCanBeDeleted($document),
            $document instanceof Category => $this->assertCategoryCanBeDeleted($document),
            $document instanceof Treasury => $this->assertTreasuryCanBeDeleted($document),
            default => null,
        };
    }

    private function assertCashAdvanceCanBeDeleted(CashAdvance $advance): void
    {
        /*
         * System administrators may delete the cash advance even when
         * settlements exist. Normal users are blocked when the advance
         * has any settlement / expense / return movement.
         */
        if (auth()->user()?->is_admin === true) {
            return;
        }

        if ($advance->settlements()->exists()) {
            throw new DocumentDeletionBlockedException(
                'لا يمكن حذف العهدة النقدية',
                'لا يمكن حذف العهدة لوجود تسويات أو مصروفات أو مبالغ مرتجعة مرتبطة بها. مدير النظام فقط يمكنه حذف عهدة عليها حركات.'
            );
        }
    }
    public function assertPurchaseRequestCanBeDeleted(PurchaseRequest $request): void
    {
        $order = $this->first('supplier_purchase_orders', 'purchase_request_id', $request->getKey(), 'code');

        if ($order) {
            $code = $order->code ?? null;
            $message = $code
                ? "طلب الشراء مرتبط بأمر التوريد {$code}. احذف أمر التوريد أولًا ثم أعد المحاولة."
                : 'طلب الشراء مرتبط بأمر توريد واحد أو أكثر. احذف أوامر التوريد المرتبطة أولًا ثم أعد المحاولة.';

            throw new DocumentDeletionBlockedException(
                'لا يمكن حذف طلب الشراء',
                $message,
                'supplier_purchase_order',
                $code,
                $this->count('supplier_purchase_orders', 'purchase_request_id', $request->getKey()),
            );
        }
    }

    public function assertSupplierPurchaseOrderCanBeDeleted(SupplierPurchaseOrder $order): void
    {
        $invoice = $this->first('purchase_invoices', 'supplier_purchase_order_id', $order->getKey(), 'code');

        if (! $invoice && Schema::hasTable('purchase_invoice_items') && Schema::hasTable('supplier_purchase_order_items')) {
            $invoice = DB::table('purchase_invoices')
                ->join('purchase_invoice_items', 'purchase_invoice_items.purchase_invoice_id', '=', 'purchase_invoices.id')
                ->join('supplier_purchase_order_items', 'supplier_purchase_order_items.id', '=', 'purchase_invoice_items.supplier_purchase_order_item_id')
                ->where('supplier_purchase_order_items.supplier_purchase_order_id', $order->getKey())
                ->select('purchase_invoices.code')
                ->first();
        }

        if ($invoice) {
            throw new DocumentDeletionBlockedException(
                'لا يمكن حذف أمر التوريد',
                'أمر التوريد مرتبط بفاتورة شراء واحدة أو أكثر. احذف فواتير الشراء المرتبطة أولًا ثم أعد المحاولة.',
                'purchase_invoice',
                $invoice->code ?? null,
            );
        }
    }

    public function assertPurchaseInvoiceCanBeDeleted(PurchaseInvoice $invoice): void
    {
        $allocation = $this->first('supplier_payment_voucher_allocations', 'purchase_invoice_id', $invoice->getKey());

        if ($allocation) {
            $count = $this->count('supplier_payment_voucher_allocations', 'purchase_invoice_id', $invoice->getKey());
            throw new DocumentDeletionBlockedException(
                'لا يمكن حذف فاتورة الشراء',
                $count > 1
                    ? 'فاتورة الشراء مرتبطة بسندات صرف موردين. احذف سندات الصرف المرتبطة أولًا ثم أعد المحاولة.'
                    : 'فاتورة الشراء مرتبطة بسند صرف مورد. احذف سند الصرف المرتبط أولًا ثم أعد المحاولة.',
                'supplier_payment_voucher',
                null,
                $count,
            );
        }
    }

    public function assertSalesInvoiceCanBeDeleted(SalesInvoice $invoice): void
    {
        if ($this->first('receipt_voucher_allocations', 'sales_invoice_id', $invoice->getKey())) {
            throw new DocumentDeletionBlockedException(
                'لا يمكن حذف فاتورة البيع',
                'فاتورة البيع مرتبطة بسند قبض عميل. احذف سند القبض المرتبط أولًا ثم أعد المحاولة.',
                'receipt_voucher',
                null,
                $this->count('receipt_voucher_allocations', 'sales_invoice_id', $invoice->getKey()),
            );
        }
    }

    public function assertSalesQuotationCanBeDeleted(SalesQuotation $quotation): void
    {
        if ($this->first('sales_invoices', 'sales_quotation_id', $quotation->getKey())) {
            throw new DocumentDeletionBlockedException(
                'لا يمكن حذف عرض السعر',
                'عرض السعر مرتبط بفاتورة بيع واحدة أو أكثر. احذف فواتير البيع المرتبطة أولًا ثم أعد المحاولة.',
                'sales_invoice',
                null,
                $this->count('sales_invoices', 'sales_quotation_id', $quotation->getKey()),
            );
        }
    }

    private function assertCustomerCanBeDeleted(Customer $customer): void
    {
        if ($this->used($customer, [
            ['sales_invoices', 'customer_id'],
            ['sales_quotations', 'customer_id'],
            ['receipt_vouchers', 'customer_id'],
        ]) || $this->partyTransactionsExist($customer)) {
            $this->block('لا يمكن حذف العميل', 'لا يمكن حذف العميل لوجود معاملات أو مستندات مرتبطة به.');
        }
    }

    private function assertSupplierCanBeDeleted(Supplier $supplier): void
    {
        if ($this->used($supplier, [
            ['supplier_purchase_orders', 'supplier_id'],
            ['purchase_invoices', 'supplier_id'],
            ['supplier_payment_vouchers', 'supplier_id'],
        ]) || $this->partyTransactionsExist($supplier)) {
            $this->block('لا يمكن حذف المورد', 'لا يمكن حذف المورد لوجود معاملات أو مستندات مرتبطة به.');
        }
    }

    private function assertItemCanBeDeleted(Item $item): void
    {
        if ($this->used($item, [
            ['purchase_request_items', 'item_id'],
            ['supplier_purchase_order_items', 'item_id'],
            ['purchase_invoice_items', 'item_id'],
            ['sales_invoice_items', 'item_id'],
            ['sales_quotation_items', 'item_id'],
            ['stock_transactions', 'item_id'],
            ['stock_balances', 'item_id'],
            ['opening_stock_items', 'item_id'],
            ['goods_receipt_items', 'item_id'],
            ['goods_issue_items', 'item_id'],
        ])) {
            $this->block('لا يمكن حذف الصنف', 'لا يمكن حذف الصنف لوجود حركات مخزنية أو مستندات مرتبطة به.');
        }
    }

    private function assertWarehouseCanBeDeleted(Warehouse $warehouse): void
    {
        if ($this->used($warehouse, [
            ['purchase_requests', 'warehouse_id'],
            ['supplier_purchase_orders', 'warehouse_id'],
            ['purchase_invoices', 'warehouse_id'],
            ['sales_invoices', 'warehouse_id'],
            ['sales_quotations', 'warehouse_id'],
            ['opening_stock_vouchers', 'warehouse_id'],
            ['goods_receipt_vouchers', 'warehouse_id'],
            ['goods_issue_vouchers', 'warehouse_id'],
            ['stock_transactions', 'warehouse_id'],
            ['stock_balances', 'warehouse_id'],
        ])) {
            $this->block('لا يمكن حذف المخزن', 'لا يمكن حذف المخزن لوجود مستندات أو أرصدة أو حركات مخزنية مرتبطة به.');
        }
    }

    private function assertUnitCanBeDeleted(Unit $unit): void
    {
        if ($this->used($unit, [
            ['items', 'unit_id'],
            ['purchase_request_items', 'unit_id'],
            ['supplier_purchase_order_items', 'unit_id'],
            ['sales_quotation_items', 'unit_id'],
        ])) {
            $this->block('لا يمكن حذف الوحدة', 'لا يمكن حذف الوحدة لأنها مستخدمة في أصناف أو مستندات.');
        }
    }

    private function assertCategoryCanBeDeleted(Category $category): void
    {
        if ($this->used($category, [['items', 'category_id']])) {
            $this->block('لا يمكن حذف التصنيف', 'لا يمكن حذف التصنيف لوجود أصناف مرتبطة به.');
        }
    }

    private function assertTreasuryCanBeDeleted(Treasury $treasury): void
    {
        if ($this->used($treasury, [
            ['treasury_transactions', 'treasury_id'],
            ['receipt_vouchers', 'treasury_id'],
            ['supplier_payment_vouchers', 'treasury_id'],
        ])) {
            $this->block('لا يمكن حذف الخزينة', 'لا يمكن حذف الخزينة لوجود معاملات أو مستندات مرتبطة بها.');
        }
    }

    /** @param array<int, array{0: string, 1: string}> $dependencies */
    private function used(Model $model, array $dependencies): bool
    {
        foreach ($dependencies as [$table, $column]) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, $column)
                && DB::table($table)->where($column, $model->getKey())->exists()) {
                return true;
            }
        }

        return false;
    }

    private function partyTransactionsExist(Model $party): bool
    {
        return Schema::hasTable('party_transactions')
            && DB::table('party_transactions')
                ->where('party_type', $party->getMorphClass())
                ->where('party_id', $party->getKey())
                ->exists();
    }

    private function first(string $table, string $column, mixed $value, string $select = 'id'): ?object
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
            return null;
        }

        return DB::table($table)->where($column, $value)->select($select)->first();
    }

    private function count(string $table, string $column, mixed $value): int
    {
        return Schema::hasTable($table) && Schema::hasColumn($table, $column)
            ? DB::table($table)->where($column, $value)->count()
            : 0;
    }

    private function block(string $title, string $message): never
    {
        throw new DocumentDeletionBlockedException($title, $message);
    }
}
