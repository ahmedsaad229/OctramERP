<?php

use App\Http\Controllers\CashPaymentVoucherPrintController;
use App\Http\Controllers\CashReceiptVoucherPrintController;
use App\Http\Controllers\CustomerPurchaseOrderMonitoringReportController;
use App\Http\Controllers\CustomerPurchaseOrderPrintController;
use App\Http\Controllers\CustomerStatementPrintController;
use App\Http\Controllers\CustomerFollowUpPrintController;
use App\Http\Controllers\InventoryReportController;
use App\Http\Controllers\ItemMovementExportController;
use App\Http\Controllers\ItemMovementPrintController;
use App\Http\Controllers\PartyStatementExportController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SalesQuotationPrintController;
use App\Http\Controllers\SalesInvoicePrintController;
use App\Http\Controllers\SupplierStatementPrintController;
use App\Http\Controllers\PurchasingDocumentPrintController;
use App\Http\Controllers\SalesReportController;
use App\Http\Controllers\DueObligationsPrintController;
use App\Http\Controllers\TrialBalancePrintController;
use App\Http\Controllers\IncomeStatementPrintController;
use App\Http\Controllers\BalanceSheetPrintController;
use App\Http\Controllers\CashFlowStatementPrintController;
use Illuminate\Support\Facades\Route;


Route::middleware('auth')->group(function () {

    Route::get('/admin/trial-balance/print', TrialBalancePrintController::class)->name('trial-balance.print');
    Route::get('/admin/income-statement/print', IncomeStatementPrintController::class)->name('income-statement.print');
    Route::get('/admin/balance-sheet/print', BalanceSheetPrintController::class)->name('balance-sheet.print');
    Route::get('/admin/cash-flow-statement/print', CashFlowStatementPrintController::class)->name('cash-flow-statement.print');

    Route::get(
        '/due-obligations/print',
        DueObligationsPrintController::class
    )->name('due-obligations.print');

    Route::get(
        '/sales-reports/print',
        [SalesReportController::class, 'print']
    )->name('sales-reports.print');

    Route::get(
        '/sales-reports/excel',
        [SalesReportController::class, 'excel']
    )->name('sales-reports.excel');

    Route::get(
        '/purchase-requests/{purchaseRequest}/print',
        [PurchasingDocumentPrintController::class, 'purchaseRequest']
    )->name('purchase-requests.print');

    Route::get(
        '/supplier-purchase-orders/{supplierPurchaseOrder}/print',
        [PurchasingDocumentPrintController::class, 'supplierPurchaseOrder']
    )->name('supplier-purchase-orders.print');

    Route::get(
        '/supplier-payment-vouchers/{supplierPaymentVoucher}/print',
        [PurchasingDocumentPrintController::class, 'supplierPaymentVoucher']
    )->name('supplier-payment-vouchers.print');

    Route::get(
        '/purchase-invoices/{purchaseInvoice}/print',
        [PurchasingDocumentPrintController::class, 'purchaseInvoice']
    )->name('purchase-invoices.print');
    Route::get(
        '/customer-follow-ups/{customerFollowUp}/print',
        CustomerFollowUpPrintController::class
    )->name('customer-follow-ups.print');
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

    Route::get('/admin/sales-invoices/{salesInvoice}/print', SalesInvoicePrintController::class)
        ->name('sales-invoices.print');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// require __DIR__.'/auth.php';

use App\Http\Controllers\GeneralLedgerPrintController;

Route::get('/reports/general-ledger/print', GeneralLedgerPrintController::class)
    ->middleware('auth')
    ->name('general-ledger.print');


Route::get(
    '/admin/fiscal-year-closing/print',
    \App\Http\Controllers\FiscalYearClosingPrintController::class
)
    ->middleware('auth')
    ->name('fiscal-year-closing.print');


Route::get(
    '/admin/sales-quotations/{salesQuotation}/delivery-note',
    \App\Http\Controllers\SalesQuotationDeliveryNoteController::class
)
    ->middleware('auth')
    ->name('sales-quotations.delivery-note');

Route::get(
    '/admin/item-trace/print',
    \App\Http\Controllers\ItemTracePrintController::class
)
    ->middleware('auth')
    ->name('item-trace.print');

/* Octram Report */
Route::get(
    '/admin/octram/{octramEntry}/print',
    \App\Http\Controllers\OctramEntryPrintController::class
)->middleware(['auth', 'permission:octram.print'])->name('octram.print');

Route::get(
    '/admin/octram/report/print-all',
    \App\Http\Controllers\OctramEntriesReportController::class
)->middleware(['auth', 'permission:octram.print'])->name('octram.print-all');

Route::get(
    '/admin/cash-advances/{cashAdvance}/print',
    \App\Http\Controllers\CashAdvancePrintController::class
)->middleware(['auth', 'permission:cash-advances.print'])->name('cash-advances.print');


Route::get(
    '/admin/purchase-invoices/export/excel',
    [\App\Http\Controllers\PurchaseInvoicesExportController::class, 'excel']
)->middleware(['auth', 'permission:purchase_invoices.view'])->name('purchase-invoices.export-excel');

Route::get(
    '/admin/purchase-invoices/export/pdf',
    [\App\Http\Controllers\PurchaseInvoicesExportController::class, 'pdf']
)->middleware(['auth', 'permission:purchase_invoices.view'])->name('purchase-invoices.export-pdf');


Route::get(
    '/admin/purchase-invoices/report/detailed',
    [\App\Http\Controllers\PurchaseInvoicesExportController::class, 'detailed']
)->middleware(['auth', 'permission:purchase_invoices.view'])->name('purchase-invoices.detailed-report');


Route::get(
    '/admin/sales-invoices/export/pdf',
    [\App\Http\Controllers\SalesInvoicesExportController::class, 'pdf']
)->middleware(['auth', 'permission:sales_invoices.view'])->name('sales-invoices.export-pdf');

Route::get(
    '/admin/sales-invoices/report/detailed',
    [\App\Http\Controllers\SalesInvoicesExportController::class, 'detailed']
)->middleware(['auth', 'permission:sales_invoices.view'])->name('sales-invoices.detailed-report');


Route::get(
    '/admin/sales-invoices/export/excel',
    [\App\Http\Controllers\SalesInvoicesExportController::class, 'excel']
)->middleware(['auth', 'permission:sales_invoices.view'])->name('sales-invoices.export-excel');


Route::get(
    '/admin/purchase-item-sales-tracking/print',
    [\App\Http\Controllers\PurchaseItemSalesTrackingExportController::class, 'print']
)->middleware(['auth', 'permission:purchase-item-sales-tracking.print'])->name('purchase-item-sales-tracking.print');

Route::get(
    '/admin/purchase-item-sales-tracking/excel',
    [\App\Http\Controllers\PurchaseItemSalesTrackingExportController::class, 'excel']
)->middleware(['auth', 'permission:purchase-item-sales-tracking.print'])->name('purchase-item-sales-tracking.excel');


Route::get(
    '/admin/sales-quotations/export/excel',
    [\App\Http\Controllers\SalesQuotationsExportController::class, 'excel']
)->middleware(['auth', 'permission:sales_quotations.view'])->name('sales-quotations.export-excel');

Route::get(
    '/admin/sales-quotations/export/pdf',
    [\App\Http\Controllers\SalesQuotationsExportController::class, 'pdf']
)->middleware(['auth', 'permission:sales_quotations.view'])->name('sales-quotations.export-pdf');

Route::get(
    '/admin/sales-quotations/report/detailed',
    [\App\Http\Controllers\SalesQuotationsExportController::class, 'detailed']
)->middleware(['auth', 'permission:sales_quotations.view'])->name('sales-quotations.detailed-report');

