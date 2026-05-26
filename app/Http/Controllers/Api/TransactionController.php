<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
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
            'receiver_id' => 'required|exists:users,id',
            'amount' => 'required|numeric|min:1'
        ]);

        $sender = Wallet::where('user_id', Auth::id())->first();
        $receiver = Wallet::where('user_id', $request->receiver_id)->first();

        if (!$receiver) {
            return response()->json(['message' => 'Receiver wallet not found'], 404);
        }

        if ($sender->balance < $request->amount) {
            return response()->json(['message' => 'Insufficient balance'], 400);
        }

        DB::transaction(function () use ($sender, $receiver, $request) {
            $sender->balance -= $request->amount;
            $sender->save();

            $receiver->balance += $request->amount;
            $receiver->save();

            Transaction::create([
                'sender_id' => Auth::id(),
                'receiver_id' => $request->receiver_id,
                'type' => 'transfer',
                'amount' => $request->amount,
                'description' => 'Transfer to user id ' . $request->receiver_id
            ]);
        });
        return response()->json(['message' => 'Transfer successful']);
    }

    public function history()
    {
        $transactions = Transaction::where('sender_id', Auth::id())
            ->orWhere('receiver_id', Auth::id())
            ->with(['sender', 'receiver'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($transactions);
    }
}
