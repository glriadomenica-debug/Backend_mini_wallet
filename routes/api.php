<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\WalletController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\AuthController;

Route::post('/register', [AuthController::class, 'registration']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {

  Route::get('/balance', [WalletController::class, 'balance']);

  Route::post('/topup', [WalletController::class, 'topup']);

  Route::post('/transfer', [TransactionController::class, 'transfer']);

  Route::get('/transactions', [TransactionController::class, 'history']);

  Route::post('/logout', [AuthController::class, 'logout']);
});
