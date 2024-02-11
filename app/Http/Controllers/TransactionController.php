<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transaction;
use App\Models\TransactionItem;

class TransactionController extends Controller
{
    public function getTransactions(Request $request)
    {
        $userId = $request->userId;

        $transactions = Transaction::where('user_id', $userId)->get();
        return response()->json(['transactions' => $transactions], 200);
    }

    public function getAllTransactions()
    {
        $transactions = Transaction::all();
        return response()->json(['transactions' => $transactions], 200);
    }

    public function deleteTransaction($transactionId)
    {
        $transaction = Transaction::find($transactionId);

        $transaction->delete();

        $transactionItems = TransactionItem::where('transaction_id', $transactionId)->get();

        foreach ($transactionItems as $transactionItem) {
            $transactionItem->delete();
        }

        return response()->json(['message' => 'Transaction and transaction items deleted successfully'], 200);
    }

    public function transactionDetail($transactionId)
    {
        $transactionItems = TransactionItem::with('product')
            ->where('transaction_id', $transactionId)
            ->get();

        if ($transactionItems->isEmpty()) {
            return response()->json(['message' => 'Transaction items not found'], 404);
        }

        $transactionItems = $transactionItems->map(function ($item) {
            return [
                'product_name' => $item->product->name,
                'quantity' => $item->quantity,
                'price' => $item->price,
            ];
        });

        return response()->json(['transaction_items' => $transactionItems], 200);
    }

    public function cancelTransaction($transactionId)
    {
        $transaction = Transaction::find($transactionId);

        if (!$transaction) {
            return response()->json(['message' => 'Transaction not found'], 404);
        }

        $transaction->status = 'cancelled';
        $transaction->cancelled = true;
        $transaction->save();

        return response()->json(['message' => 'Transaction cancelled successfully'], 200);
    }

    public function updateStatus(Request $request, $transactionId)
    {
        $transaction = Transaction::find($transactionId);

        if (!$transaction) {
            return response()->json(['message' => 'Transaction not found'], 404);
        }

        $request->validate([
            'status' => 'required|in:on progress,completed,cancelled',
        ]);

        $transaction->status = $request->status;
        $transaction->save();

        return response()->json(['message' => 'Transaction status updated successfully'], 200);
    }

    public function checkout(Request $request)
    {
        $request->validate([
            'totalAmount' => 'required|numeric',
            'cartItems' => 'required|array',
            'cartItems.*.product_id' => 'required|exists:products,id',
            'cartItems.*.quantity' => 'required|integer|min:1',
            'cartItems.*.price' => 'required|numeric|min:0',
        ]);

        try {
            $transaction = new Transaction();
            $transaction->user_id = $request->userId;
            $transaction->total_amount = $request->input('totalAmount');
            $transaction->save();

            $cartItems = $request->input('cartItems');

            foreach ($cartItems as $item) {
                $transactionItem = new TransactionItem();
                $transactionItem->transaction_id = $transaction->id;
                $transactionItem->product_id = $item['product_id'];
                $transactionItem->quantity = $item['quantity'];
                $transactionItem->price = $item['price'];
                $transactionItem->save();
            }

            return response()->json(['message' => 'Checkout successful'], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Checkout failed', 'error' => $e->getMessage()], 500);
        }
    }
}
