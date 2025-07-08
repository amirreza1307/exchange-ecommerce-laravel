<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Currency;
use App\Models\Discount;
use App\Models\ExchangeRate;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    /**
     * Display user orders
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        $query = $user->orders()->with(['fromCurrency', 'toCurrency']);
        
        // Filter by type
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }
        
        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        $orders = $query->orderBy('created_at', 'desc')->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $orders
        ]);
    }

    /**
     * Create buy order
     */
    public function buy(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'currency_id' => 'required|exists:currencies,id',
            'amount' => 'required|numeric|min:0.00000001',
            'discount_code' => 'nullable|string|exists:discounts,code',
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
        
        if (!$currency->is_active || !$currency->is_tradeable) {
            return response()->json([
                'success' => false,
                'message' => 'Currency is not available for trading'
            ], 422);
        }

        // Check treasury balance
        if (!$currency->hasEnoughTreasuryBalance($request->amount)) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient treasury balance'
            ], 422);
        }

        $buyPrice = $currency->buy_price;
        $totalCost = $request->amount * $buyPrice;
        $commission = ($totalCost * $currency->buy_commission) / 100;
        $finalCost = $totalCost + $commission;

        // Apply discount if provided
        $discountAmount = 0;
        $discount = null;
        
        if ($request->discount_code) {
            $discount = Discount::where('code', $request->discount_code)->first();
            
            if ($discount && $discount->isValidForAmount($finalCost)) {
                $discountAmount = $discount->calculateDiscount($commission);
                $finalCost -= $discountAmount;
            }
        }

        // Check user balance
        if (!$user->hasEnoughRialBalance($finalCost)) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient rial balance'
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Create order
            $order = Order::create([
                'user_id' => $user->id,
                'type' => 'buy',
                'from_currency_id' => 1, // Assuming 1 is IRR/Rial
                'to_currency_id' => $currency->id,
                'from_amount' => $finalCost,
                'to_amount' => $request->amount,
                'exchange_rate' => $buyPrice,
                'commission_rate' => $currency->buy_commission,
                'commission_amount' => $commission,
                'final_amount' => $finalCost,
                'discount_code' => $request->discount_code,
                'discount_amount' => $discountAmount,
                'status' => 'processing'
            ]);

            // Deduct rial balance
            $user->subtractRialBalance($finalCost);

            // Deduct from treasury
            $currency->decrement('treasury_balance', $request->amount);

            // Add to user wallet
            $wallet = $user->getOrCreateWallet($currency->id);
            $wallet->addBalance($request->amount);

            // Use discount if applied
            if ($discount) {
                $discount->use();
            }

            // Create transaction
            Transaction::create([
                'user_id' => $user->id,
                'currency_id' => $currency->id,
                'type' => 'buy',
                'amount' => $request->amount,
                'fee' => $commission,
                'final_amount' => $request->amount,
                'status' => 'completed',
                'reference_id' => $order->order_number,
                'metadata' => [
                    'order_id' => $order->id,
                    'buy_price' => $buyPrice,
                    'total_cost' => $finalCost
                ],
                'description' => "Buy {$currency->symbol}",
                'processed_at' => now()
            ]);

            $order->markAsCompleted();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Buy order completed successfully',
                'data' => $order->fresh(['fromCurrency', 'toCurrency'])
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Order failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create sell order
     */
    public function sell(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'currency_id' => 'required|exists:currencies,id',
            'amount' => 'required|numeric|min:0.00000001',
            'discount_code' => 'nullable|string|exists:discounts,code',
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
        
        if (!$currency->is_active || !$currency->is_tradeable) {
            return response()->json([
                'success' => false,
                'message' => 'Currency is not available for trading'
            ], 422);
        }

        $wallet = $user->getWalletForCurrency($currency->id);
        
        if (!$wallet || !$wallet->hasEnoughBalance($request->amount)) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient currency balance'
            ], 422);
        }

        $sellPrice = $currency->sell_price;
        $totalValue = $request->amount * $sellPrice;
        $commission = ($totalValue * $currency->sell_commission) / 100;
        $finalValue = $totalValue - $commission;

        // Apply discount if provided
        $discountAmount = 0;
        $discount = null;
        
        if ($request->discount_code) {
            $discount = Discount::where('code', $request->discount_code)->first();
            
            if ($discount && $discount->isValidForAmount($totalValue)) {
                $discountAmount = $discount->calculateDiscount($commission);
                $finalValue += $discountAmount;
            }
        }

        DB::beginTransaction();
        try {
            // Create order
            $order = Order::create([
                'user_id' => $user->id,
                'type' => 'sell',
                'from_currency_id' => $currency->id,
                'to_currency_id' => 1, // Assuming 1 is IRR/Rial
                'from_amount' => $request->amount,
                'to_amount' => $finalValue,
                'exchange_rate' => $sellPrice,
                'commission_rate' => $currency->sell_commission,
                'commission_amount' => $commission,
                'final_amount' => $finalValue,
                'discount_code' => $request->discount_code,
                'discount_amount' => $discountAmount,
                'status' => 'processing'
            ]);

            // Deduct from user wallet
            $wallet->subtractBalance($request->amount);

            // Add to treasury
            $currency->increment('treasury_balance', $request->amount);

            // Add rial to user
            $user->addRialBalance($finalValue);

            // Use discount if applied
            if ($discount) {
                $discount->use();
            }

            // Create transaction
            Transaction::create([
                'user_id' => $user->id,
                'currency_id' => $currency->id,
                'type' => 'sell',
                'amount' => -$request->amount,
                'fee' => $commission,
                'final_amount' => $finalValue,
                'status' => 'completed',
                'reference_id' => $order->order_number,
                'metadata' => [
                    'order_id' => $order->id,
                    'sell_price' => $sellPrice,
                    'total_value' => $finalValue
                ],
                'description' => "Sell {$currency->symbol}",
                'processed_at' => now()
            ]);

            $order->markAsCompleted();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Sell order completed successfully',
                'data' => $order->fresh(['fromCurrency', 'toCurrency'])
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Order failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create exchange order (crypto to crypto)
     */
    public function exchange(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'from_currency_id' => 'required|exists:currencies,id',
            'to_currency_id' => 'required|exists:currencies,id|different:from_currency_id',
            'amount' => 'required|numeric|min:0.00000001',
            'discount_code' => 'nullable|string|exists:discounts,code',
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
        
        if (!$fromCurrency->is_active || !$fromCurrency->is_tradeable ||
            !$toCurrency->is_active || !$toCurrency->is_tradeable) {
            return response()->json([
                'success' => false,
                'message' => 'One or both currencies are not available for trading'
            ], 422);
        }

        $fromWallet = $user->getWalletForCurrency($fromCurrency->id);
        
        if (!$fromWallet || !$fromWallet->hasEnoughBalance($request->amount)) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient balance'
            ], 422);
        }

        // Calculate exchange rate (from -> IRR -> to)
        $fromValue = $request->amount * $fromCurrency->sell_price;
        $toAmount = $fromValue / $toCurrency->buy_price;
        
        // Check if treasury has enough of target currency
        if (!$toCurrency->hasEnoughTreasuryBalance($toAmount)) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient treasury balance for target currency'
            ], 422);
        }

        // Calculate commission (average of both currencies)
        $avgCommission = ($fromCurrency->sell_commission + $toCurrency->buy_commission) / 2;
        $commission = ($fromValue * $avgCommission) / 100;
        $finalToAmount = $toAmount - (($commission / $toCurrency->buy_price));

        DB::beginTransaction();
        try {
            // Create order
            $order = Order::create([
                'user_id' => $user->id,
                'type' => 'exchange',
                'from_currency_id' => $fromCurrency->id,
                'to_currency_id' => $toCurrency->id,
                'from_amount' => $request->amount,
                'to_amount' => $finalToAmount,
                'exchange_rate' => $fromValue / $request->amount,
                'commission_rate' => $avgCommission,
                'commission_amount' => $commission,
                'final_amount' => $finalToAmount,
                'status' => 'processing'
            ]);

            // Update wallets
            $fromWallet->subtractBalance($request->amount);
            $toWallet = $user->getOrCreateWallet($toCurrency->id);
            $toWallet->addBalance($finalToAmount);

            // Update treasury
            $fromCurrency->increment('treasury_balance', $request->amount);
            $toCurrency->decrement('treasury_balance', $finalToAmount);

            // Create transactions
            Transaction::create([
                'user_id' => $user->id,
                'currency_id' => $fromCurrency->id,
                'type' => 'exchange',
                'amount' => -$request->amount,
                'fee' => $commission / 2,
                'final_amount' => -$request->amount,
                'status' => 'completed',
                'reference_id' => $order->order_number,
                'metadata' => [
                    'order_id' => $order->id,
                    'to_currency_id' => $toCurrency->id
                ],
                'description' => "Exchange to {$toCurrency->symbol}",
                'processed_at' => now()
            ]);

            Transaction::create([
                'user_id' => $user->id,
                'currency_id' => $toCurrency->id,
                'type' => 'exchange',
                'amount' => $finalToAmount,
                'fee' => $commission / 2,
                'final_amount' => $finalToAmount,
                'status' => 'completed',
                'reference_id' => $order->order_number,
                'metadata' => [
                    'order_id' => $order->id,
                    'from_currency_id' => $fromCurrency->id
                ],
                'description' => "Exchange from {$fromCurrency->symbol}",
                'processed_at' => now()
            ]);

            $order->markAsCompleted();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Exchange completed successfully',
                'data' => $order->fresh(['fromCurrency', 'toCurrency'])
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Exchange failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show specific order
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();
        $order = $user->orders()->with(['fromCurrency', 'toCurrency'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $order
        ]);
    }

    /**
     * Cancel order
     */
    public function cancel(Request $request, $id)
    {
        $user = $request->user();
        $order = $user->orders()->findOrFail($id);

        if (!in_array($order->status, ['pending', 'processing'])) {
            return response()->json([
                'success' => false,
                'message' => 'Order cannot be cancelled'
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'reason' => 'nullable|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Reverse the order operations based on type
            if ($order->type === 'buy') {
                // Refund rial
                $user->addRialBalance($order->final_amount);
                // Add back to treasury
                $order->toCurrency->increment('treasury_balance', $order->to_amount);
                // Remove from wallet
                $wallet = $user->getWalletForCurrency($order->to_currency_id);
                if ($wallet) {
                    $wallet->subtractBalance($order->to_amount);
                }
            } elseif ($order->type === 'sell') {
                // Refund currency
                $wallet = $user->getOrCreateWallet($order->from_currency_id);
                $wallet->addBalance($order->from_amount);
                // Remove from treasury
                $order->fromCurrency->decrement('treasury_balance', $order->from_amount);
                // Remove rial
                $user->subtractRialBalance($order->to_amount);
            }

            $order->cancel($request->reason);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Order cancelled successfully',
                'data' => $order->fresh()
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Cancellation failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get price quote
     */
    public function quote(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|in:buy,sell,exchange',
            'from_currency_id' => 'required_if:type,sell,exchange|exists:currencies,id',
            'to_currency_id' => 'required|exists:currencies,id',
            'amount' => 'required|numeric|min:0.00000001',
            'discount_code' => 'nullable|string|exists:discounts,code',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $type = $request->type;
        $amount = $request->amount;
        $quote = [];

        if ($type === 'buy') {
            $currency = Currency::findOrFail($request->to_currency_id);
            $totalCost = $amount * $currency->buy_price;
            $commission = ($totalCost * $currency->buy_commission) / 100;
            $finalCost = $totalCost + $commission;
            
            $quote = [
                'type' => 'buy',
                'currency' => $currency,
                'amount' => $amount,
                'unit_price' => $currency->buy_price,
                'total_cost' => $totalCost,
                'commission_rate' => $currency->buy_commission,
                'commission_amount' => $commission,
                'final_cost' => $finalCost
            ];
            
        } elseif ($type === 'sell') {
            $currency = Currency::findOrFail($request->from_currency_id);
            $totalValue = $amount * $currency->sell_price;
            $commission = ($totalValue * $currency->sell_commission) / 100;
            $finalValue = $totalValue - $commission;
            
            $quote = [
                'type' => 'sell',
                'currency' => $currency,
                'amount' => $amount,
                'unit_price' => $currency->sell_price,
                'total_value' => $totalValue,
                'commission_rate' => $currency->sell_commission,
                'commission_amount' => $commission,
                'final_value' => $finalValue
            ];
        }

        // Apply discount if provided
        if ($request->discount_code) {
            $discount = Discount::where('code', $request->discount_code)->first();
            
            if ($discount && $discount->isValidForAmount($quote['final_cost'] ?? $quote['final_value'])) {
                $discountAmount = $discount->calculateDiscount($quote['commission_amount']);
                $quote['discount'] = [
                    'code' => $discount->code,
                    'type' => $discount->type,
                    'value' => $discount->value,
                    'discount_amount' => $discountAmount
                ];
                
                if ($type === 'buy') {
                    $quote['final_cost'] -= $discountAmount;
                } else {
                    $quote['final_value'] += $discountAmount;
                }
            }
        }

        return response()->json([
            'success' => true,
            'data' => $quote
        ]);
    }
}
