<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});


Route::group(['prefix' => 'v1'], function () {
    Route::get('/', [UserController::class, 'index']);
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/products', [ProductController::class, 'index']);

    Route::middleware(['auth:api', 'role:admin'])->group(function () {
        Route::get('/product/{productId}', [ProductController::class, 'getProduct']);
        Route::put('/product/update/{productId}', [ProductController::class, 'updateProduct']);
        Route::post('/addproduct', [ProductController::class, 'addProduct']);
        Route::delete('/product/{productId}', [ProductController::class, 'deleteProduct']);
        Route::get('/transactions/{userId}', [TransactionController::class, 'getTransactions']);
        Route::put('/transactions/cancel/{transactionId}', [TransactionController::class, 'cancelTransaction']);
        Route::get('/transactions', [TransactionController::class,  'getAllTransactions']);
        Route::delete('/transactions/{transactionId}', [TransactionController::class, 'deleteTransaction']);
        Route::get('/transactions/detail/{transactionId}', [TransactionController::class, 'transactionDetail']);
        Route::put('/transactions/update_status/{transactionId}', [TransactionController::class, 'updateStatus']);
    });

    Route::middleware(['auth:api', 'role:user'])->group(function () {
        Route::post('/checkout', [TransactionController::class, 'checkout']);
        Route::get('/transactions/{userId}', [TransactionController::class, 'getTransactions']);
        Route::put('/transactions/cancel/{transactionId}', [TransactionController::class, 'cancelTransaction']);
    });

    Route::middleware(['auth:api'])->group(function () {
        Route::get('/transactions/detail/{transactionId}', [TransactionController::class, 'transactionDetail']);
    });
});
