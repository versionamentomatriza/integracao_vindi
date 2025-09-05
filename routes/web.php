<?php

use App\Http\Controllers\PaymentsController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    abort(403, 'Unauthorized access');
});

// payments routes
Route::get('/payments/checkout', [PaymentsController::class, 'checkout']);
Route::get(
    '/payments/update_customer_info',
    [PaymentsController::class, 'update_customer_info']
)->name('payments.update_customer_info');
Route::post('/payments/save_customer_info', [PaymentsController::class, 'save_customer_info'])->name('payments.save_customer_info');
