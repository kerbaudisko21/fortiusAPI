<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transaction;
use App\Models\TransactionItem;

class TransactionController extends Controller
{

    /**
     * @OA\Schema(
     *     schema="Transaction",
     *     title="Transaction",
     *     required={"id", "user_id", "total_amount", "status", "cancelled"},
     *     @OA\Property(property="id", type="integer", description="Transaction ID"),
     *     @OA\Property(property="user_id", type="integer", description="User ID"),
     *     @OA\Property(property="total_amount", type="number", format="float", description="Total amount"),
     *     @OA\Property(property="status", type="string", description="Transaction status"),
     *     @OA\Property(property="cancelled", type="boolean", description="Whether the transaction is cancelled")
     * )
     */

    private function jsonResponse($data, $message = null, $statusCode = 200)
    {
        return response()->json([
            'success' => $statusCode >= 200 && $statusCode < 300,
            'message' => $message,
            'data' => $data
        ], $statusCode);
    }

    /**
     * @OA\Get(
     *     path="/api/transactions",
     *     tags={"Transaction"},
     *     summary="Get user transactions",
     *     operationId="getTransactions",
     *     @OA\Parameter(
     *         name="userId",
     *         in="query",
     *         required=true,
     *         description="ID of the user",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="transactions", type="array", @OA\Items(ref="#/components/schemas/Transaction"))
     *         )
     *     )
     * )
     */
    public function getTransactions(Request $request)
    {
        $userId = $request->userId;

        $transactions = Transaction::where('user_id', $userId)->get();
        return $this->jsonResponse(['transactions' => $transactions]);
    }

    /**
     * @OA\Get(
     *     path="/api/transactions/all",
     *     tags={"Transaction"},
     *     summary="Get all transactions",
     *     operationId="getAllTransactions",
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="transactions", type="array", @OA\Items(ref="#/components/schemas/Transaction"))
     *         )
     *     )
     * )
     */
    public function getAllTransactions()
    {
        $transactions = Transaction::all();
        return $this->jsonResponse(['transactions' => $transactions]);
    }

    /**
     * @OA\Delete(
     *     path="/api/transactions/{transactionId}",
     *     tags={"Transaction"},
     *     summary="Delete a transaction",
     *     operationId="deleteTransaction",
     *     @OA\Parameter(
     *         name="transactionId",
     *         in="path",
     *         required=true,
     *         description="ID of the transaction",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Transaction and transaction items deleted successfully"
     *     )
     * )
     */
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

    /**
     * @OA\Get(
     *     path="/api/transactions/{transactionId}/detail",
     *     tags={"Transaction"},
     *     summary="Get transaction details",
     *     operationId="transactionDetail",
     *     @OA\Parameter(
     *         name="transactionId",
     *         in="path",
     *         required=true,
     *         description="ID of the transaction",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="transaction_items", type="array", @OA\Items(
     *                 @OA\Property(property="product_name", type="string"),
     *                 @OA\Property(property="quantity", type="integer"),
     *                 @OA\Property(property="price", type="number")
     *             ))
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Transaction items not found"
     *     )
     * )
     */
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

    /**
     * @OA\Put(
     *     path="/api/transactions/{transactionId}/cancel",
     *     tags={"Transaction"},
     *     summary="Cancel a transaction",
     *     operationId="cancelTransaction",
     *     @OA\Parameter(
     *         name="transactionId",
     *         in="path",
     *         required=true,
     *         description="ID of the transaction",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Transaction cancelled successfully"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Transaction not found"
     *     )
     * )
     */
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

    /**
     * @OA\Patch(
     *     path="/api/transactions/{transactionId}/update-status",
     *     tags={"Transaction"},
     *     summary="Update transaction status",
     *     operationId="updateStatus",
     *     @OA\Parameter(
     *         name="transactionId",
     *         in="path",
     *         required=true,
     *         description="ID of the transaction",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="Updated status",
     *         @OA\JsonContent(
     *             required={"status"},
     *             @OA\Property(property="status", type="string", enum={"on progress", "completed", "cancelled"})
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Transaction status updated successfully"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Transaction not found"
     *     )
     * )
     */
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

    /**
     * @OA\Post(
     *     path="/api/checkout",
     *     tags={"Transaction"},
     *     summary="Checkout",
     *     operationId="checkout",
     *     @OA\RequestBody(
     *         required=true,
     *         description="Checkout details",
     *         @OA\JsonContent(
     *             required={"totalAmount", "cartItems"},
     *             @OA\Property(property="totalAmount", type="number"),
     *             @OA\Property(property="cartItems", type="array", @OA\Items(
     *                 required={"product_id", "quantity", "price"},
     *                 @OA\Property(property="product_id", type="integer"),
     *                 @OA\Property(property="quantity", type="integer"),
     *                 @OA\Property(property="price", type="number")
     *             ))
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="transaction_id", type="integer")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Checkout failed"
     *     )
     * )
     */
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
