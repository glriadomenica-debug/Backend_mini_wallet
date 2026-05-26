<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WalletController extends Controller
{
    public function balance()
    {
        $wallet = Wallet::where('user_id', Auth::id())->first();
        return response()->json([
            'balance' => $wallet->balance
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
}
