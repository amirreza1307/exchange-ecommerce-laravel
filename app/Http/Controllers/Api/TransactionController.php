<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    /**
     * Display user transactions
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        $query = $user->transactions()->with('currency');
        
        // Filter by type
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }
        
        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        // Filter by currency
        if ($request->has('currency_id')) {
            $query->where('currency_id', $request->currency_id);
        }
        
        // Date range filter
        if ($request->has('from_date') || $request->has('from')) {
            $fromDate = $request->from_date ?? $request->from;
            $query->whereDate('created_at', '>=', $fromDate);
        }
        
        if ($request->has('to_date') || $request->has('to')) {
            $toDate = $request->to_date ?? $request->to;
            $query->whereDate('created_at', '<=', $toDate);
        }
        
        $transactions = $query->orderBy('created_at', 'desc')->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $transactions
        ]);
    }

    /**
     * Show specific transaction
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();
        $transaction = $user->transactions()->with('currency')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $transaction
        ]);
    }

    /**
     * Get transaction statistics
     */
    public function statistics(Request $request)
    {
        $user = $request->user();
        
        $stats = [
            'total_transactions' => $user->transactions()->count(),
            'completed_transactions' => $user->transactions()->completed()->count(),
            'pending_transactions' => $user->transactions()->pending()->count(),
            'total_deposits' => $user->transactions()->byType('deposit')->completed()->sum('final_amount'),
            'total_withdrawals' => $user->transactions()->byType('withdraw')->completed()->sum('final_amount'),
            'total_fees_paid' => $user->transactions()->completed()->sum('fee'),
        ];
        
        // Monthly transaction summary
        $monthlyData = $user->transactions()
            ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month')
            ->selectRaw('COUNT(*) as count')
            ->selectRaw('SUM(final_amount) as total_amount')
            ->selectRaw('SUM(fee) as total_fees')
            ->completed()
            ->groupBy('month')
            ->orderBy('month', 'desc')
            ->limit(12)
            ->get();
        
        $stats['monthly_data'] = $monthlyData;

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }
}
