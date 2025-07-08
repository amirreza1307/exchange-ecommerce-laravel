<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CurrencyController extends Controller
{
    /**
     * Display a listing of currencies
     */
    public function index(Request $request)
    {
        $query = Currency::query();

        // Filter by active status
        if ($request->has('active')) {
            $query->where('is_active', $request->active);
        }

        // Filter by tradeable status
        if ($request->has('tradeable')) {
            $query->where('is_tradeable', $request->tradeable);
        }

        // Search by symbol or name
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('symbol', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%");
            });
        }

        $currencies = $query->orderBy('symbol')->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $currencies
        ]);
    }

    /**
     * Store a newly created currency
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'symbol' => 'required|string|max:10|unique:currencies',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'buy_price' => 'required|numeric|min:0',
            'sell_price' => 'required|numeric|min:0',
            'buy_commission' => 'required|numeric|min:0|max:100',
            'sell_commission' => 'required|numeric|min:0|max:100',
            'treasury_balance' => 'required|numeric|min:0',
            'decimal_places' => 'required|integer|min:0|max:18',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $request->only([
            'symbol', 'name', 'description', 'buy_price', 'sell_price',
            'buy_commission', 'sell_commission', 'treasury_balance', 'decimal_places'
        ]);

        // Handle image upload
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $filename = time() . '_' . $image->getClientOriginalName();
            $path = 'currencies/' . $filename;
            
            // Create directory if it doesn't exist
            if (!file_exists(public_path('storage/currencies'))) {
                mkdir(public_path('storage/currencies'), 0755, true);
            }
            
            $image->move(public_path('storage/currencies'), $filename);
            $data['image'] = $path;
        }

        $currency = Currency::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Currency created successfully',
            'data' => $currency
        ], 201);
    }

    /**
     * Display the specified currency
     */
    public function show(string $id)
    {
        $currency = Currency::findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $currency
        ]);
    }

    /**
     * Update the specified currency
     */
    public function update(Request $request, string $id)
    {
        $currency = Currency::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'symbol' => 'sometimes|string|max:10|unique:currencies,symbol,' . $id,
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'buy_price' => 'sometimes|numeric|min:0',
            'sell_price' => 'sometimes|numeric|min:0',
            'buy_commission' => 'sometimes|numeric|min:0|max:100',
            'sell_commission' => 'sometimes|numeric|min:0|max:100',
            'treasury_balance' => 'sometimes|numeric|min:0',
            'decimal_places' => 'sometimes|integer|min:0|max:18',
            'is_active' => 'sometimes|boolean',
            'is_tradeable' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $request->only([
            'symbol', 'name', 'description', 'buy_price', 'sell_price',
            'buy_commission', 'sell_commission', 'treasury_balance', 
            'decimal_places', 'is_active', 'is_tradeable'
        ]);

        // Handle image upload
        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($currency->image && file_exists(public_path('storage/' . $currency->image))) {
                unlink(public_path('storage/' . $currency->image));
            }

            $image = $request->file('image');
            $filename = time() . '_' . $image->getClientOriginalName();
            $path = 'currencies/' . $filename;
            
            if (!file_exists(public_path('storage/currencies'))) {
                mkdir(public_path('storage/currencies'), 0755, true);
            }
            
            $image->move(public_path('storage/currencies'), $filename);
            $data['image'] = $path;
        }

        $currency->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Currency updated successfully',
            'data' => $currency
        ]);
    }

    /**
     * Remove the specified currency
     */
    public function destroy(string $id)
    {
        $currency = Currency::findOrFail($id);

        // Check if currency has any wallets or transactions
        if ($currency->wallets()->count() > 0 || $currency->transactions()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete currency with existing wallets or transactions'
            ], 422);
        }

        // Delete image if exists
        if ($currency->image && file_exists(public_path('storage/' . $currency->image))) {
            unlink(public_path('storage/' . $currency->image));
        }

        $currency->delete();

        return response()->json([
            'success' => true,
            'message' => 'Currency deleted successfully'
        ]);
    }

    /**
     * Get active currencies for trading
     */
    public function trading()
    {
        $currencies = Currency::active()->tradeable()->get();

        return response()->json([
            'success' => true,
            'data' => $currencies
        ]);
    }

    /**
     * Update treasury balance
     */
    public function updateTreasury(Request $request, string $id)
    {
        $currency = Currency::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric',
            'operation' => 'required|in:add,subtract,set'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $amount = $request->amount;
        $operation = $request->operation;

        switch ($operation) {
            case 'add':
                $currency->increment('treasury_balance', $amount);
                break;
            case 'subtract':
                if ($currency->treasury_balance < $amount) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Insufficient treasury balance'
                    ], 422);
                }
                $currency->decrement('treasury_balance', $amount);
                break;
            case 'set':
                $currency->update(['treasury_balance' => $amount]);
                break;
        }

        return response()->json([
            'success' => true,
            'message' => 'Treasury balance updated successfully',
            'data' => $currency->fresh()
        ]);
    }
}
