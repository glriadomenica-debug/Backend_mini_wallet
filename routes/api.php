<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\WalletController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\AuthController;

// Route::post('/auth/register', [AuthController::class, 'registration']);
Route::post('/auth/login', [AuthController::class, 'login']);

Route::middleware(['auth:sanctum', 'admin'])->group(function () {
  Route::post('/admin/users', [AuthController::class, 'createCustomer']);

  // route lama 
  Route::get('/balance', [WalletController::class, 'balance']);
  Route::post('/topup', [WalletController::class, 'topup']);
  Route::post('/transfer', [TransactionController::class, 'transfer']);
  Route::get('/transactions', [TransactionController::class, 'history']);
});
