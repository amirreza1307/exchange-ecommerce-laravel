<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CurrencyController;
use App\Http\Controllers\Api\WalletController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\DiscountController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Public routes
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
});

// Admin login (public route)
Route::post('/admin/auth/login', [AuthController::class, 'adminLogin']);

// Public currency info
Route::get('/currencies', [CurrencyController::class, 'index']);
Route::get('/currencies/trading', [CurrencyController::class, 'trading']);
Route::get('/currencies/{id}', [CurrencyController::class, 'show']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    
    // Auth routes
    Route::prefix('auth')->group(function () {
        Route::get('/profile', [AuthController::class, 'profile']);
        Route::put('/profile', [AuthController::class, 'updateProfile']);
        Route::put('/change-password', [AuthController::class, 'changePassword']);
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/logout-all', [AuthController::class, 'logoutAll']);
    });

    // Wallet routes
    Route::prefix('wallets')->group(function () {
        Route::get('/', [WalletController::class, 'index']);
        Route::get('/portfolio', [WalletController::class, 'portfolio']);
        Route::get('/{id}', [WalletController::class, 'show']);
        Route::get('/{id}/transactions', [WalletController::class, 'transactions']);
        Route::post('/deposit', [WalletController::class, 'deposit']);
        Route::post('/withdraw', [WalletController::class, 'withdraw']);
        Route::post('/transfer', [WalletController::class, 'transfer']);
    });

    // Order routes
    Route::prefix('orders')->group(function () {
        Route::get('/', [OrderController::class, 'index']);
        Route::get('/{id}', [OrderController::class, 'show']);
        Route::post('/buy', [OrderController::class, 'buy']);
        Route::post('/sell', [OrderController::class, 'sell']);
        Route::post('/exchange', [OrderController::class, 'exchange']);
        Route::post('/quote', [OrderController::class, 'quote']);
        Route::put('/{id}/cancel', [OrderController::class, 'cancel']);
    });

    // Transaction routes
    Route::prefix('transactions')->group(function () {
        Route::get('/', [TransactionController::class, 'index']);
        Route::get('/{id}', [TransactionController::class, 'show']);
    });

    // Discount routes
    Route::prefix('discounts')->group(function () {
        Route::get('/', [DiscountController::class, 'index']);
        Route::get('/my-usage', [DiscountController::class, 'myUsage']);
        Route::get('/{id}', [DiscountController::class, 'show']);
        Route::post('/validate', [DiscountController::class, 'validate']);
    });

    // Admin routes
    Route::middleware('admin')->prefix('admin')->group(function () {
        
        // Currency management
        Route::prefix('currencies')->group(function () {
            Route::post('/', [CurrencyController::class, 'store']);
            Route::put('/{id}', [CurrencyController::class, 'update']);
            Route::delete('/{id}', [CurrencyController::class, 'destroy']);
            Route::put('/{id}/treasury', [CurrencyController::class, 'updateTreasury']);
        });

        // Exchange rate management
        Route::prefix('exchange-rates')->group(function () {
            Route::post('/', [AdminController::class, 'createExchangeRate']);
            Route::put('/{id}', [AdminController::class, 'updateExchangeRate']);
            Route::delete('/{id}', [AdminController::class, 'deleteExchangeRate']);
        });

        // Discount management
        Route::prefix('discounts')->group(function () {
            Route::get('/', [AdminController::class, 'listDiscounts']);
            Route::post('/', [AdminController::class, 'createDiscount']);
            Route::put('/{id}', [AdminController::class, 'updateDiscount']);
            Route::delete('/{id}', [AdminController::class, 'deleteDiscount']);
        });

        // Admin dashboard and reports
        Route::get('/dashboard', [AdminController::class, 'dashboard']);
        Route::get('/users', [AdminController::class, 'users']);
        Route::get('/orders', [AdminController::class, 'orders']);
        Route::get('/transactions', [AdminController::class, 'transactions']);
        Route::get('/reports/trading', [AdminController::class, 'tradingReport']);
        Route::get('/reports/revenue', [AdminController::class, 'revenueReport']);
        
        // User management
        Route::put('/users/{id}', [AdminController::class, 'updateUser']);
        Route::put('/users/{id}/status', [AdminController::class, 'updateUserStatus']);
        Route::get('/users/{id}/activity', [AdminController::class, 'userActivity']);
        
        // Order management
        Route::put('/orders/{id}', [AdminController::class, 'updateOrder']);
        Route::put('/orders/{id}/status', [AdminController::class, 'updateOrderStatus']);
        Route::get('/orders/pending', [AdminController::class, 'pendingOrders']);
        
        // Transaction management
        Route::put('/transactions/{id}/status', [AdminController::class, 'updateTransactionStatus']);
        Route::get('/transactions/pending', [AdminController::class, 'pendingTransactions']);
    });
});

// Fallback route for undefined API endpoints
Route::fallback(function () {
    return response()->json([
        'success' => false,
        'message' => 'API endpoint not found'
    ], 404);
});
