<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

use App\Http\Controllers\PrintDocumentController;
Route::get('/print/delivery-order/{deliveryOrder}', [PrintDocumentController::class, 'printDeliveryOrder'])->name('print.delivery-order')->middleware('auth');

Route::get('/print/invoice/{invoice}', [PrintDocumentController::class, 'printInvoice'])->name('print.invoice')->middleware('auth');
Route::get('/print/receipt/{invoice}', [PrintDocumentController::class, 'printReceipt'])->name('print.receipt')->middleware('auth');


Route::get('/health', function () {
    $health = ['status' => 'ok', 'timestamp' => now()->toIso8601String()];
    try {
        \Illuminate\Support\Facades\DB::connection()->getPdo();
        $health['database'] = 'connected';
    } catch (\Exception $e) {
        $health['status'] = 'error';
        $health['database'] = 'disconnected';
    }
    $freeSpace = disk_free_space(storage_path());
    $health['disk_free_gb'] = round($freeSpace / 1073741824, 2);
    if ($freeSpace < 1073741824) {
        $health['status'] = 'error';
        $health['disk_space'] = 'low';
    }
    $health['failed_jobs'] = \Illuminate\Support\Facades\DB::table('failed_jobs')->count();
    return response()->json($health, $health['status'] === 'ok' ? 200 : 500);
});
