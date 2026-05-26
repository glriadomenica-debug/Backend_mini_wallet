<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Contracts\Service\Attribute\Required;

class TransactionController extends Controller
{
    public function transfer(Request $request)
    {
        $request->validate([
            'username' => 'required',
            'phone_number' => 'required',
            'amount' => 'required|numeric|min:1'
        ]);

        $senderUser = Auth::user();

        // cari user tujuan
        $receiverUser = User::where('username', $request->username)
            ->where('phone_number', $request->phone_number)
            ->first();

        // user tidak ditemukan
        if (!$receiverUser) {
            return response()->json([
                'message' => 'Receiver not found'
            ], 404);
        }

        // tidak boleh transfer ke diri sendiri
        if ($receiverUser->id == $senderUser->id) {
            return response()->json([
                'message' => 'You cannot transfer to yourself'
            ], 400);
        }

        $senderWallet = Wallet::where('user_id', $senderUser->id)->first();

        $receiverWallet = Wallet::where('user_id', $receiverUser->id)->first();

        if ($senderWallet->balance < $request->amount) {
            return response()->json([
                'message' => 'Insufficient balance'
            ], 400);
        }

        DB::transaction(function () use (
            $senderWallet,
            $receiverWallet,
            $request,
            $senderUser,
            $receiverUser
        ) {

            $senderWallet->balance -= $request->amount;
            $senderWallet->save();

            $receiverWallet->balance += $request->amount;
            $receiverWallet->save();

            Transaction::create([
                'sender_id' => $senderUser->id,
                'receiver_id' => $receiverUser->id,
                'type' => 'transfer',
                'amount' => $request->amount,
                'description' => 'Transfer to ' . $receiverUser->username
            ]);
        });

        return response()->json([
            'message' => 'Transfer successful'
        ]);
    }

    public function history()
    {
        $userId = Auth::id();

        $transactions = Transaction::where('sender_id', $userId)
            ->orWhere('receiver_id', $userId)
            ->with(['sender', 'receiver'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($trx) use ($userId) {

                // default
                $title = '';
                $sign = '+';

                // TOPUP
                if ($trx->type === 'topup') {
                    $title = 'Top Up Balance';
                    $sign = '+';
                }

                // TRANSFER KELUAR
                else if ($trx->sender_id == $userId) {
                    $title = 'Transfer to ' . $trx->receiver->username;
                    $sign = '-';
                }

                // TRANSFER MASUK
                else if ($trx->receiver_id == $userId) {
                    $title = 'Received from ' . $trx->sender->username;
                    $sign = '+';
                }

                return [
                    'id' => $trx->id,
                    'title' => $title,
                    'type' => $trx->type,
                    'amount' => $trx->amount,
                    'sign' => $sign,
                    'created_at' => $trx->created_at,
                ];
            });

        return response()->json([
            'message' => 'Success get transactions',
            'data' => $transactions
        ]);
    }
}
