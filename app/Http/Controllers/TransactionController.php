<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transaction;
use App\Models\TransactionItem;

class TransactionController extends Controller
{
    private function jsonResponse($data, $message = null, $statusCode = 200)
    {
        return response()->json([
            'success' => $statusCode >= 200 && $statusCode < 300,
            'message' => $message,
            'data' => $data
        ], $statusCode);
    }

    public function getTransactions(Request $request)
    {
        $userId = $request->userId;

        $transactions = Transaction::where('user_id', $userId)->get();
        return $this->jsonResponse(['transactions' => $transactions]);
    }

    public function getAllTransactions()
    {
        $transactions = Transaction::all();
        return $this->jsonResponse(['transactions' => $transactions]);
    }

    public function deleteTransaction($transactionId)
    {
        $transaction = Transaction::find($transactionId);

        $transaction->delete();

        $transactionItems = TransactionItem::where('transaction_id', $transactionId)->get();

        foreach ($transactionItems as $transactionItem) {
            $transactionItem->delete();
        }

        return $this->jsonResponse(null, 'Transaction and transaction items deleted successfully');
    }

    public function transactionDetail($transactionId)
    {
        $transactionItems = TransactionItem::with('product')
            ->where('transaction_id', $transactionId)
            ->get();

        if ($transactionItems->isEmpty()) {
            return $this->jsonResponse(null, 'Transaction items not found', 404);
        }

        $transactionItems = $transactionItems->map(function ($item) {
            return [
                'product_name' => $item->product->name,
                'quantity' => $item->quantity,
                'price' => $item->price,
            ];
        });

        return $this->jsonResponse(['transaction_items' => $transactionItems]);
    }

    public function cancelTransaction($transactionId)
    {
        $transaction = Transaction::find($transactionId);

        if (!$transaction) {
            return $this->jsonResponse(null, 'Transaction not found', 404);
        }

        $transaction->status = 'cancelled';
        $transaction->cancelled = true;
        $transaction->save();

        return $this->jsonResponse(null, 'Transaction cancelled successfully');
    }

    public function updateStatus(Request $request, $transactionId)
    {
        $transaction = Transaction::find($transactionId);

        if (!$transaction) {
            return $this->jsonResponse(null, 'Transaction not found', 404);
        }

        $request->validate([
            'status' => 'required|in:on progress,completed,cancelled',
        ]);

        $transaction->status = $request->status;
        $transaction->save();

        return $this->jsonResponse(null, 'Transaction status updated successfully');
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

            return $this->jsonResponse(['transaction_id' => $transaction->id], 'Checkout successful');
        } catch (\Exception $e) {
            return $this->jsonResponse(null, 'Checkout failed', 500);
        }
    }
}
