<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Wallet;
use App\Models\Currency;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class WalletController extends Controller
{
    /**
     * Display user wallets
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $wallets = $user->wallets()->with('currency')->get();

        return response()->json([
            'success' => true,
            'data' => $wallets
        ]);
    }

    /**
     * Get specific wallet
     */
    public function show(Request $request, $currencyId)
    {
        $user = $request->user();
        $wallet = $user->getOrCreateWallet($currencyId);
        $wallet->load('currency');

        return response()->json([
            'success' => true,
            'data' => $wallet
        ]);
    }

    /**
     * Deposit cryptocurrency to wallet
     */
    public function deposit(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'currency_id' => 'required|exists:currencies,id',
            'amount' => 'required|numeric|min:0.00000001',
            'tx_hash' => 'required|string',
            'from_address' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();
        $currency = Currency::findOrFail($request->currency_id);
        
        if (!$currency->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Currency is not active'
            ], 422);
        }

        DB::beginTransaction();
        try {
            $wallet = $user->getOrCreateWallet($request->currency_id);
            
            // Create transaction record
            $transaction = Transaction::create([
                'user_id' => $user->id,
                'currency_id' => $request->currency_id,
                'type' => 'deposit',
                'amount' => $request->amount,
                'fee' => 0,
                'final_amount' => $request->amount,
                'status' => 'completed',
                'reference_id' => $request->tx_hash,
                'metadata' => [
                    'from_address' => $request->from_address,
                    'tx_hash' => $request->tx_hash
                ],
                'description' => 'Cryptocurrency deposit',
                'processed_at' => now()
            ]);

            // Add balance to wallet
            $wallet->addBalance($request->amount);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Deposit successful',
                'data' => [
                    'transaction' => $transaction,
                    'wallet' => $wallet->fresh()
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Deposit failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Withdraw cryptocurrency from wallet
     */
    public function withdraw(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'currency_id' => 'required|exists:currencies,id',
            'amount' => 'required|numeric|min:0.00000001',
            'to_address' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();
        $currency = Currency::findOrFail($request->currency_id);
        
        if (!$currency->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Currency is not active'
            ], 422);
        }

        $wallet = $user->getWalletForCurrency($request->currency_id);
        
        if (!$wallet || !$wallet->hasEnoughBalance($request->amount)) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient balance'
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Calculate withdrawal fee (could be a percentage or fixed amount)
            $fee = $request->amount * 0.001; // 0.1% fee
            $finalAmount = $request->amount - $fee;

            // Create pending transaction
            $transaction = Transaction::create([
                'user_id' => $user->id,
                'currency_id' => $request->currency_id,
                'type' => 'withdraw',
                'amount' => $request->amount,
                'fee' => $fee,
                'final_amount' => $finalAmount,
                'status' => 'pending',
                'metadata' => [
                    'to_address' => $request->to_address
                ],
                'description' => 'Cryptocurrency withdrawal'
            ]);

            // Freeze the amount in wallet
            $wallet->freezeBalance($request->amount);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Withdrawal request submitted successfully',
                'data' => [
                    'transaction' => $transaction,
                    'wallet' => $wallet->fresh()
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Withdrawal failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Transfer between wallets (internal)
     */
    public function transfer(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'from_currency_id' => 'required|exists:currencies,id',
            'to_currency_id' => 'required|exists:currencies,id|different:from_currency_id',
            'amount' => 'required|numeric|min:0.00000001',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();
        $fromCurrency = Currency::findOrFail($request->from_currency_id);
        $toCurrency = Currency::findOrFail($request->to_currency_id);

        if (!$fromCurrency->is_active || !$toCurrency->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'One or both currencies are not active'
            ], 422);
        }

        $fromWallet = $user->getWalletForCurrency($request->from_currency_id);
        
        if (!$fromWallet || !$fromWallet->hasEnoughBalance($request->amount)) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient balance'
            ], 422);
        }

        // This is where you would implement exchange rate logic
        // For now, we'll use a simple 1:1 exchange rate
        $exchangeRate = 1; // You should get this from ExchangeRate model
        $convertedAmount = $request->amount * $exchangeRate;

        DB::beginTransaction();
        try {
            $toWallet = $user->getOrCreateWallet($request->to_currency_id);

            // Subtract from source wallet
            $fromWallet->subtractBalance($request->amount);
            
            // Add to destination wallet
            $toWallet->addBalance($convertedAmount);

            // Create transaction records
            Transaction::create([
                'user_id' => $user->id,
                'currency_id' => $request->from_currency_id,
                'type' => 'exchange',
                'amount' => -$request->amount,
                'fee' => 0,
                'final_amount' => -$request->amount,
                'status' => 'completed',
                'metadata' => [
                    'to_currency_id' => $request->to_currency_id,
                    'exchange_rate' => $exchangeRate
                ],
                'description' => "Exchange to {$toCurrency->symbol}",
                'processed_at' => now()
            ]);

            Transaction::create([
                'user_id' => $user->id,
                'currency_id' => $request->to_currency_id,
                'type' => 'exchange',
                'amount' => $convertedAmount,
                'fee' => 0,
                'final_amount' => $convertedAmount,
                'status' => 'completed',
                'metadata' => [
                    'from_currency_id' => $request->from_currency_id,
                    'exchange_rate' => $exchangeRate
                ],
                'description' => "Exchange from {$fromCurrency->symbol}",
                'processed_at' => now()
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Transfer completed successfully',
                'data' => [
                    'from_wallet' => $fromWallet->fresh(),
                    'to_wallet' => $toWallet->fresh(),
                    'exchange_rate' => $exchangeRate
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Transfer failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get wallet transaction history
     */
    public function transactions(Request $request, $currencyId)
    {
        $user = $request->user();
        
        $transactions = Transaction::where('user_id', $user->id)
            ->where('currency_id', $currencyId)
            ->with('currency')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $transactions
        ]);
    }

    /**
     * Get total portfolio value
     */
    public function portfolio(Request $request)
    {
        $user = $request->user();
        $wallets = $user->wallets()->with('currency')->get();
        
        $totalValue = 0;
        $portfolioData = [];
        
        foreach ($wallets as $wallet) {
            $value = $wallet->balance * $wallet->currency->sell_price;
            $totalValue += $value;
            
            $portfolioData[] = [
                'currency' => $wallet->currency,
                'balance' => $wallet->balance,
                'frozen_balance' => $wallet->frozen_balance,
                'available_balance' => $wallet->getAvailableBalanceAttribute(),
                'value_in_rial' => $value
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'total_value' => $totalValue,
                'rial_balance' => $user->rial_balance,
                'wallets' => $portfolioData
            ]
        ]);
    }
}
