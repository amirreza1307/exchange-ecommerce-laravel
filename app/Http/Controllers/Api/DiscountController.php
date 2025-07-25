<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Discount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DiscountController extends Controller
{
    /**
     * Get all active discounts
     */
    public function index(Request $request)
    {
        $query = Discount::active()
            ->select('id', 'code', 'title', 'description', 'type', 'value', 
                    'min_order_amount', 'max_discount_amount', 'currency_id', 'expires_at');

        // Filter by currency if provided
        if ($request->has('currency_id')) {
            $query->where(function ($q) use ($request) {
                $q->whereNull('currency_id')
                  ->orWhere('currency_id', $request->currency_id);
            });
        } else {
            // Only show general discounts if no currency specified
            $query->whereNull('currency_id');
        }

        // Exclude user-specific discounts that don't belong to current user
        $query->where(function ($q) {
            $q->whereNull('user_id')
              ->orWhere('user_id', Auth::id());
        });

        $discounts = $query->paginate($request->per_page ?? 15);

        return response()->json([
            'success' => true,
            'data' => $discounts
        ]);
    }

    /**
     * Get discount details
     */
    public function show($id)
    {
        $discount = Discount::active()
            ->where('id', $id)
            ->where(function ($q) {
                $q->whereNull('user_id')
                  ->orWhere('user_id', Auth::id());
            })
            ->select('id', 'code', 'title', 'description', 'type', 'value', 
                    'min_order_amount', 'max_discount_amount', 'currency_id', 
                    'user_usage_limit', 'expires_at')
            ->with('currency:id,symbol,name')
            ->firstOrFail();

        // Check user usage count
        $userUsageCount = $discount->userUsages()
            ->where('user_id', Auth::id())
            ->count();

        $discount->user_usage_count = $userUsageCount;
        $discount->can_use = $discount->canBeUsedBy(Auth::user());

        return response()->json([
            'success' => true,
            'data' => $discount
        ]);
    }

    /**
     * Validate discount code
     */
    public function validate(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
            'amount' => 'required|numeric|min:0',
            'currency_id' => 'nullable|exists:currencies,id'
        ]);

        $discount = Discount::where('code', $request->code)->first();

        if (!$discount) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid discount code'
            ], 404);
        }

        // Check if discount is active
        if (!$discount->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'This discount code is not active'
            ], 400);
        }

        // Check expiration
        if ($discount->expires_at && $discount->expires_at < now()) {
            return response()->json([
                'success' => false,
                'message' => 'This discount code has expired'
            ], 400);
        }

        // Check usage limit
        if ($discount->usage_limit && $discount->usage_count >= $discount->usage_limit) {
            return response()->json([
                'success' => false,
                'message' => 'This discount code has reached its usage limit'
            ], 400);
        }

        // Check user-specific discount
        if ($discount->user_id && $discount->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'This discount code is not available for your account'
            ], 403);
        }

        // Check currency-specific discount
        if ($discount->currency_id && $request->currency_id && 
            $discount->currency_id != $request->currency_id) {
            return response()->json([
                'success' => false,
                'message' => 'This discount code is not valid for the selected currency'
            ], 400);
        }

        // Check minimum order amount
        if ($discount->min_order_amount && $request->amount < $discount->min_order_amount) {
            return response()->json([
                'success' => false,
                'message' => 'Order amount must be at least ' . number_format($discount->min_order_amount) . ' to use this discount'
            ], 400);
        }

        // Check user usage limit
        if ($discount->user_usage_limit) {
            $userUsageCount = $discount->userUsages()
                ->where('user_id', Auth::id())
                ->count();

            if ($userUsageCount >= $discount->user_usage_limit) {
                return response()->json([
                    'success' => false,
                    'message' => 'You have reached the usage limit for this discount code'
                ], 400);
            }
        }

        // Calculate discount amount
        $discountAmount = 0;
        if ($discount->type === 'percentage') {
            $discountAmount = $request->amount * ($discount->value / 100);
        } else {
            $discountAmount = $discount->value;
        }

        // Apply max discount cap if set
        if ($discount->max_discount_amount && $discountAmount > $discount->max_discount_amount) {
            $discountAmount = $discount->max_discount_amount;
        }

        // Make sure discount doesn't exceed order amount
        if ($discountAmount > $request->amount) {
            $discountAmount = $request->amount;
        }

        return response()->json([
            'success' => true,
            'data' => [
                'discount_id' => $discount->id,
                'code' => $discount->code,
                'title' => $discount->title,
                'type' => $discount->type,
                'value' => $discount->value,
                'discount_amount' => round($discountAmount, 2),
                'final_amount' => round($request->amount - $discountAmount, 2),
                'original_amount' => $request->amount
            ]
        ]);
    }

    /**
     * Get user's discount usage history
     */
    public function myUsage(Request $request)
    {
        $usages = Auth::user()->discountUsages()
            ->with(['discount:id,code,title,type,value', 'order:id,type,from_amount,to_amount,created_at'])
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 15);

        return response()->json([
            'success' => true,
            'data' => $usages
        ]);
    }
}