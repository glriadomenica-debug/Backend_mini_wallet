<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\WalletController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\AuthController;

// Route::post('/auth/register', [AuthController::class, 'registration']);
Route::post('/auth/login', [AuthController::class, 'login']);

Route::middleware(['auth:sanctum', 'admin'])->group(function () {
  Route::post('/admin/users', [AuthController::class, 'createCustomer']);
  Route::get('/admin/users', [AuthController::class, 'index']);
  Route::get('/admin/users/{id}', [AuthController::class, 'show']);

  Route::get(
    '/admin/users/{id}/transactions',
    [TransactionController::class, 'customerHistory']
  );
  Route::post('/admin/wallet/add', [WalletController::class, 'addBalance']);
  Route::post('/admin/wallet/shopee-payment', [WalletController::class, 'shopeePayment']);

  Route::get('/admin/transactions', [TransactionController::class, 'adminHistory']);
});

Route::middleware('auth:sanctum')->group(function () {
  Route::get('/balance', [WalletController::class, 'balance']);
  Route::get('/transactions', [TransactionController::class, 'history']);
});
