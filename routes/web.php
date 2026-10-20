<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PrintDocumentController;

Route::get('/', function () {
    return redirect('/admin');
});

// Fallback named route 'login' untuk mencegah 500 error dari middleware auth bawaan Laravel
Route::redirect('/login', '/admin/login')->name('login');

Route::get('/print/delivery-order/{deliveryOrder}', [PrintDocumentController::class, 'printDeliveryOrder'])
    ->name('print.delivery-order')
    ->middleware('auth');

Route::get('/print/delivery-order/{deliveryOrder}/invoice', [PrintDocumentController::class, 'printInvoiceFromDeliveryOrder'])
    ->name('print.delivery-order.invoice')
    ->middleware('auth');

Route::get('/print/invoice/{invoice}', [PrintDocumentController::class, 'printInvoice'])
    ->name('print.invoice')
    ->middleware('auth');

Route::get('/print/receipt/{invoice}', [PrintDocumentController::class, 'printReceipt'])
    ->name('print.receipt')
    ->middleware('auth');

Route::get('/print/goods-receipt/{goodsReceipt}', [PrintDocumentController::class, 'printGoodsReceipt'])
    ->name('print.goods-receipt')
    ->middleware('auth');

Route::get('/health', function () {
    $status = 'ok';
    try {
        \Illuminate\Support\Facades\DB::connection()->getPdo();
    } catch (\Exception $e) {
        $status = 'error';
    }
    return response()->json([
        'status' => $status,
        'timestamp' => now()->toIso8601String(),
    ], $status === 'ok' ? 200 : 500);
});
