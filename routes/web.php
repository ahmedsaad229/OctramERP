<?php

use App\Http\Controllers\CashPaymentVoucherPrintController;
use App\Http\Controllers\CashReceiptVoucherPrintController;
use App\Http\Controllers\CustomerPurchaseOrderMonitoringReportController;
use App\Http\Controllers\CustomerPurchaseOrderPrintController;
use App\Http\Controllers\CustomerStatementPrintController;
use App\Http\Controllers\InventoryReportController;
use App\Http\Controllers\ItemMovementExportController;
use App\Http\Controllers\ItemMovementPrintController;
use App\Http\Controllers\PartyStatementExportController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SalesQuotationPrintController;
use App\Http\Controllers\SupplierStatementPrintController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/admin/customer-purchase-orders/{customerPurchaseOrder}/print', CustomerPurchaseOrderPrintController::class)
        ->name('customer-purchase-orders.print');
    Route::get('/admin/customer-purchase-order-monitoring/print', [CustomerPurchaseOrderMonitoringReportController::class, 'print'])
        ->name('customer-purchase-order-monitoring.print');
    Route::get('/admin/customer-purchase-order-monitoring/excel', [CustomerPurchaseOrderMonitoringReportController::class, 'excel'])
        ->name('customer-purchase-order-monitoring.excel');
    Route::get('/admin/cash-payment-vouchers/{supplierPaymentVoucher}/print', CashPaymentVoucherPrintController::class)
        ->name('cash-payment-vouchers.print');
    Route::get('/admin/cash-receipt-vouchers/{receiptVoucher}/print', CashReceiptVoucherPrintController::class)
        ->name('cash-receipt-vouchers.print');
    Route::get('/admin/customer-statement/print', CustomerStatementPrintController::class)
        ->name('customer-statement.print');
    Route::get('/admin/customer-statement/excel', [PartyStatementExportController::class, 'customer'])
        ->name('customer-statement.excel');
    Route::get('/admin/item-movement/print', ItemMovementPrintController::class)
        ->name('item-movement.print');
    Route::get('/admin/item-movement/excel', ItemMovementExportController::class)
        ->name('item-movement.excel');
    Route::get('/admin/inventory-reports/{report}/print', [InventoryReportController::class, 'print'])
        ->whereIn('report', ['balances', 'movements', 'low-stock'])
        ->name('inventory-reports.print');
    Route::get('/admin/inventory-reports/{report}/excel', [InventoryReportController::class, 'excel'])
        ->whereIn('report', ['balances', 'movements', 'low-stock'])
        ->name('inventory-reports.excel');
    Route::get('/admin/supplier-statement/print', SupplierStatementPrintController::class)
        ->name('supplier-statement.print');
    Route::get('/admin/supplier-statement/excel', [PartyStatementExportController::class, 'supplier'])
        ->name('supplier-statement.excel');
    Route::get('/admin/sales-quotations/{salesQuotation}/print', SalesQuotationPrintController::class)
        ->name('sales-quotations.print');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
