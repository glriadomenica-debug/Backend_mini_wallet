<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Models\User;

class TransactionController extends Controller
{
    public function history(Request $request)
    {
        $transactions = Transaction::where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($transaction) {

                $type = strtolower($transaction->type ?? '');

                /*
                |--------------------------------------------------------------------------
                | DETERMINE TRANSACTION DIRECTION
                |--------------------------------------------------------------------------
                */

                $incomeTypes = [
                    'topup',
                    'balance_add',
                    'deposit',
                    'money_in',
                    'received',
                    'transfer_in',
                    'credit',
                ];

                $expenseTypes = [
                    'payment',
                    'shopee_payment',
                    'purchase',
                    'checkout',
                    'expense',
                    'debit',
                    'transfer',
                    'transfer_out',
                    'money_out',
                ];

                if (in_array($type, $incomeTypes)) {
                    $direction = 'income';
                } elseif (in_array($type, $expenseTypes)) {
                    $direction = 'expense';
                } else {
                    $direction = 'expense';
                }

                /*
                |--------------------------------------------------------------------------
                | TITLE
                |--------------------------------------------------------------------------
                */

                if (in_array($type, [
                    'topup',
                    'balance_add',
                    'deposit',
                ])) {
                    $title = 'Top Up';
                } elseif (in_array($type, [
                    'transfer_in',
                    'received',
                ])) {
                    $title = 'Money Received';
                } elseif (in_array($type, [
                    'transfer',
                    'transfer_out',
                ])) {
                    $title = 'Transfer';
                } elseif (in_array($type, [
                    'payment',
                    'shopee_payment',
                    'purchase',
                    'checkout',
                ])) {
                    $title = 'Payment';
                } else {
                    $title = $transaction->description ?? 'Transaction';
                }

                /*
                |--------------------------------------------------------------------------
                | DISPLAY AMOUNT
                |--------------------------------------------------------------------------
                */

                $amount = abs((float) $transaction->amount);

                $displayAmount = $direction === 'income'
                    ? $amount
                    : -$amount;

                /*
                |--------------------------------------------------------------------------
                | RETURN DATA
                |--------------------------------------------------------------------------
                */

                return [
                    'id' => $transaction->id,

                    'user_id' => $transaction->user_id,

                    'performed_by' => $transaction->performed_by,

                    'type' => $transaction->type,

                    'amount' => $amount,

                    'direction' => $direction,

                    'display_amount' => $displayAmount,

                    'title' => $title,

                    'description' => $transaction->description,

                    'created_at' => $transaction->created_at,
                    'updated_at' => $transaction->updated_at,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $transactions
        ]);
    }

    public function adminHistory()
    {
        $transactions = Transaction::with([
            'user:id,username,email',
            'performedBy:id,username'
        ])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $transactions
        ]);
    }

    public function customerHistory($id)
    {
        try {
            $customer = User::where('role', 'customer')->find($id);

            if (!$customer) {
                return response()->json([
                    'success' => false,
                    'message' => 'Customer not found.'
                ], 404);
            }

            $transactions = Transaction::with([
                'performedBy:id,username'
            ])
                ->where('user_id', $id)
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $transactions
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load customer transactions.',
                'error' => $th->getMessage()
            ], 500);
        }
    }
}
