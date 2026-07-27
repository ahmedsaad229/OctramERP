<?php

use App\Http\Controllers\CustomerStatementPrintController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SalesQuotationPrintController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/admin/customer-statement/print', CustomerStatementPrintController::class)
        ->name('customer-statement.print');
    Route::get('/admin/sales-quotations/{salesQuotation}/print', SalesQuotationPrintController::class)
        ->name('sales-quotations.print');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
