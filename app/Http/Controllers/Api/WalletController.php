<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class WalletController extends Controller
{
    public function balance(Request $request)
    {
        $wallet = Wallet::where('user_id', $request->user()->id)->first();

        return response()->json([
            'success' => true,
            'data' => $wallet
        ]);
    }

    public function topup(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1'
        ]);

        $wallet = Wallet::where('user_id', Auth::id())->first();
        $wallet->balance += $request->amount;
        $wallet->save();

        Transaction::create([
            'sender_id' => null,
            'receiver_id' => Auth::id(),
            'type' => 'topup',
            'amount' => $request->amount,
            'description' => 'Topup balance'
        ]);
        return response()->json([
            'message' => 'Topup successful',
            'balance' => $wallet->balance
        ]);
    }

    public function addBalance(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'amount' => 'required|numeric|min:1',
        ]);

        try {
            DB::beginTransaction();

            $wallet = Wallet::where('user_id', $request->user_id)
                ->lockForUpdate()
                ->first();

            if (!$wallet) {
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'Wallet not found.'
                ], 404);
            }

            $wallet->balance += $request->amount;
            $wallet->save();

            Transaction::create([
                'user_id' => $request->user_id,
                'performed_by' => Auth::id(),
                'type' => 'balance_add',
                'amount' => $request->amount,
                'description' => 'Admin added balance',
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Balance added successfully.',
                'data' => [
                    'user_id' => $request->user_id,
                    'balance' => $wallet->balance,
                ]
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to add balance.',
                'error' => $th->getMessage(),
            ], 500);
        }
    }


    public function shopeePayment(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'amount' => 'required|numeric|min:1',
            'currency' => 'required|in:IDR,USD',
        ]);

        try {
            DB::beginTransaction();

            // Exchange rate
            // 1 USD = Rp16,000
            $exchangeRate = 16000;

            $paymentAmount = (float) $request->amount;
            $currency = strtoupper($request->currency);

            // Convert payment amount to USD
            if ($currency === 'IDR') {
                $walletDeduction = $paymentAmount / $exchangeRate;
            } else {
                $walletDeduction = $paymentAmount;
            }

            // Round to 2 decimal places
            $walletDeduction = round($walletDeduction, 2);

            $wallet = Wallet::where('user_id', $request->user_id)
                ->lockForUpdate()
                ->first();

            if (!$wallet) {
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'Wallet not found.'
                ], 404);
            }

            // Check customer's USD wallet balance
            if ((float) $wallet->balance < $walletDeduction) {
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient balance.',
                    'data' => [
                        'wallet_balance' => (float) $wallet->balance,
                        'required_amount' => $walletDeduction,
                        'currency' => 'USD',
                    ]
                ], 400);
            }

            // Deduct USD from wallet
            $wallet->balance -= $walletDeduction;
            $wallet->save();

            // Store wallet transaction in USD
            Transaction::create([
                'user_id' => $request->user_id,
                'performed_by' => Auth::id(),
                'type' => 'shopee_payment',
                'amount' => $walletDeduction,
                'description' => $currency === 'IDR'
                    ? 'Shopee payment - Rp ' . number_format($paymentAmount, 0, ',', '.')
                    : 'Shopee payment - $' . number_format($paymentAmount, 2),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Shopee payment successful.',
                'data' => [
                    'user_id' => $request->user_id,

                    // Original amount entered by admin
                    'payment_amount' => $paymentAmount,

                    // Original currency
                    'payment_currency' => $currency,

                    // USD amount deducted from wallet
                    'wallet_deducted' => $walletDeduction,

                    // Remaining USD wallet balance
                    'remaining_balance' => (float) $wallet->balance,

                    // Exchange rate used
                    'exchange_rate' => $exchangeRate,
                ]
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to process Shopee payment.',
                'error' => $th->getMessage(),
            ], 500);
        }
    }
}
