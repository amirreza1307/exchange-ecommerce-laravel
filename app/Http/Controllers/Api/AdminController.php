<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Order;
use App\Models\Transaction;
use App\Models\Currency;
use App\Models\ExchangeRate;
use App\Models\Discount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    /**
     * Admin dashboard data
     */
    public function dashboard()
    {
        $stats = [
            'total_users' => User::count(),
            'active_users' => User::active()->count(),
            'total_orders' => Order::count(),
            'pending_orders' => Order::pending()->count(),
            'completed_orders' => Order::completed()->count(),
            'total_transactions' => Transaction::count(),
            'pending_transactions' => Transaction::pending()->count(),
            'total_currencies' => Currency::count(),
            'active_currencies' => Currency::active()->count(),
        ];

        // Revenue data
        $revenue = [
            'today' => Order::whereDate('created_at', today())->sum('commission_amount'),
            'this_week' => Order::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->sum('commission_amount'),
            'this_month' => Order::whereMonth('created_at', now()->month)->sum('commission_amount'),
            'this_year' => Order::whereYear('created_at', now()->year)->sum('commission_amount'),
        ];

        // Recent activities
        $recentOrders = Order::with(['user', 'fromCurrency', 'toCurrency'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $recentUsers = User::orderBy('created_at', 'desc')
            ->limit(10)
            ->get(['id', 'name', 'email', 'created_at', 'is_active']);

        return response()->json([
            'success' => true,
            'data' => [
                'stats' => $stats,
                'revenue' => $revenue,
                'recent_orders' => $recentOrders,
                'recent_users' => $recentUsers
            ]
        ]);
    }

    /**
     * Get all users
     */
    public function users(Request $request)
    {
        $query = User::query();

        // Search by name or email
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($request->has('active')) {
            $query->where('is_active', $request->active);
        }

        // Filter by role
        if ($request->has('role')) {
            $query->where('role', $request->role);
        }

        $users = $query->withCount(['orders', 'transactions'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $users
        ]);
    }

    /**
     * Update user
     */
    public function updateUser(Request $request, $id)
    {
        $user = User::findOrFail($id);
        
        $request->validate([
            'name' => 'nullable|string|max:255',
            'email' => 'nullable|email|unique:users,email,' . $id,
            'is_active' => 'boolean',
            'role' => 'nullable|in:admin,user'
        ]);

        $user->update($request->only(['name', 'email', 'is_active', 'role']));

        return response()->json([
            'success' => true,
            'message' => 'User updated successfully',
            'data' => $user
        ]);
    }

    /**
     * Update user status
     */
    public function updateUserStatus(Request $request, $id)
    {
        $user = User::findOrFail($id);
        
        $request->validate([
            'is_active' => 'required|boolean'
        ]);

        $user->update(['is_active' => $request->is_active]);

        return response()->json([
            'success' => true,
            'message' => 'User status updated successfully',
            'data' => $user
        ]);
    }

    /**
     * Get user activity
     */
    public function userActivity($id)
    {
        $user = User::findOrFail($id);
        
        $activity = [
            'user' => $user,
            'wallets' => $user->wallets()->with('currency')->get(),
            'recent_orders' => $user->orders()->with(['fromCurrency', 'toCurrency'])
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get(),
            'recent_transactions' => $user->transactions()->with('currency')
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get(),
            'stats' => [
                'total_orders' => $user->orders()->count(),
                'completed_orders' => $user->orders()->completed()->count(),
                'total_volume' => $user->orders()->completed()->sum('final_amount'),
                'total_fees_paid' => $user->orders()->completed()->sum('commission_amount'),
            ]
        ];

        return response()->json([
            'success' => true,
            'data' => $activity
        ]);
    }

    /**
     * Get all orders for admin
     */
    public function orders(Request $request)
    {
        $query = Order::with(['user', 'fromCurrency', 'toCurrency']);

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by type
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        // Filter by user
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Date range filter
        if ($request->has('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        
        if ($request->has('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        $orders = $query->orderBy('created_at', 'desc')->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $orders
        ]);
    }

    /**
     * Get pending orders
     */
    public function pendingOrders()
    {
        $orders = Order::pending()
            ->with(['user', 'fromCurrency', 'toCurrency'])
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $orders
        ]);
    }

    /**
     * Update order
     */
    public function updateOrder(Request $request, $id)
    {
        $order = Order::findOrFail($id);
        
        $request->validate([
            'status' => 'nullable|in:pending,processing,completed,cancelled,failed',
            'cancellation_reason' => 'nullable|string'
        ]);

        $updateData = $request->only(['status', 'cancellation_reason']);
        
        if ($request->status === 'completed') {
            $updateData['processed_at'] = now();
        }

        $order->update($updateData);

        return response()->json([
            'success' => true,
            'message' => 'Order updated successfully',
            'data' => $order
        ]);
    }

    /**
     * Update order status
     */
    public function updateOrderStatus(Request $request, $id)
    {
        $order = Order::findOrFail($id);
        
        $request->validate([
            'status' => 'required|in:pending,processing,completed,cancelled,failed',
            'reason' => 'nullable|string'
        ]);

        $order->update([
            'status' => $request->status,
            'cancellation_reason' => $request->reason
        ]);

        if ($request->status === 'completed') {
            $order->update(['processed_at' => now()]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Order status updated successfully',
            'data' => $order
        ]);
    }

    /**
     * Get all transactions for admin
     */
    public function transactions(Request $request)
    {
        $query = Transaction::with(['user', 'currency']);

        // Filter by type
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by user
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Date range filter
        if ($request->has('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        
        if ($request->has('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        $transactions = $query->orderBy('created_at', 'desc')->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $transactions
        ]);
    }

    /**
     * Get pending transactions
     */
    public function pendingTransactions()
    {
        $transactions = Transaction::pending()
            ->with(['user', 'currency'])
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $transactions
        ]);
    }

    /**
     * Update transaction status
     */
    public function updateTransactionStatus(Request $request, $id)
    {
        $transaction = Transaction::findOrFail($id);
        
        $request->validate([
            'status' => 'required|in:pending,completed,failed,cancelled'
        ]);

        if ($request->status === 'completed') {
            $transaction->markAsCompleted();
        } elseif ($request->status === 'failed') {
            $transaction->markAsFailed();
        } elseif ($request->status === 'cancelled') {
            $transaction->markAsCancelled();
        } else {
            $transaction->update(['status' => $request->status]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Transaction status updated successfully',
            'data' => $transaction
        ]);
    }

    /**
     * Trading report
     */
    public function tradingReport(Request $request)
    {
        $period = $request->get('period', 'month'); // day, week, month, year
        
        $isSqlite = config('database.default') === 'sqlite';
        
        $dateColumn = match($period) {
            'day' => $isSqlite ? 'DATE(created_at)' : 'DATE(created_at)',
            'week' => $isSqlite ? 'strftime("%Y-%W", created_at)' : 'YEARWEEK(created_at)',
            'month' => $isSqlite ? 'strftime("%Y-%m", created_at)' : 'DATE_FORMAT(created_at, "%Y-%m")',
            'year' => $isSqlite ? 'strftime("%Y", created_at)' : 'YEAR(created_at)'
        };

        // Trading volume by period
        $volumeData = Order::selectRaw("{$dateColumn} as period")
            ->selectRaw('SUM(final_amount) as total_volume')
            ->selectRaw('SUM(commission_amount) as total_commission')
            ->selectRaw('COUNT(*) as total_orders')
            ->completed()
            ->groupBy('period')
            ->orderBy('period', 'desc')
            ->limit(30)
            ->get();

        // Currency trading statistics
        $currencyStats = Order::select('to_currency_id')
            ->selectRaw('SUM(to_amount) as total_volume')
            ->selectRaw('SUM(commission_amount) as total_commission')
            ->selectRaw('COUNT(*) as total_orders')
            ->with('toCurrency')
            ->completed()
            ->groupBy('to_currency_id')
            ->orderBy('total_volume', 'desc')
            ->get();

        // Top traders
        $topTraders = Order::select('user_id')
            ->selectRaw('SUM(final_amount) as total_volume')
            ->selectRaw('COUNT(*) as total_orders')
            ->with('user')
            ->completed()
            ->groupBy('user_id')
            ->orderBy('total_volume', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'volume_data' => $volumeData,
                'currency_stats' => $currencyStats,
                'top_traders' => $topTraders
            ]
        ]);
    }

    /**
     * Revenue report
     */
    public function revenueReport(Request $request)
    {
        $period = $request->get('period', 'month');
        
        $isSqlite = config('database.default') === 'sqlite';
        
        $dateColumn = match($period) {
            'day' => $isSqlite ? 'DATE(created_at)' : 'DATE(created_at)',
            'week' => $isSqlite ? 'strftime("%Y-%W", created_at)' : 'YEARWEEK(created_at)',
            'month' => $isSqlite ? 'strftime("%Y-%m", created_at)' : 'DATE_FORMAT(created_at, "%Y-%m")',
            'year' => $isSqlite ? 'strftime("%Y", created_at)' : 'YEAR(created_at)'
        };

        // Revenue by period
        $revenueData = Order::selectRaw("{$dateColumn} as period")
            ->selectRaw('SUM(commission_amount) as total_revenue')
            ->selectRaw('SUM(commission_amount - discount_amount) as net_revenue')
            ->selectRaw('SUM(discount_amount) as total_discounts')
            ->selectRaw('COUNT(*) as total_orders')
            ->completed()
            ->groupBy('period')
            ->orderBy('period', 'desc')
            ->limit(30)
            ->get();

        // Revenue by currency
        $currencyRevenue = Order::select('to_currency_id')
            ->selectRaw('SUM(commission_amount) as total_revenue')
            ->selectRaw('AVG(commission_rate) as avg_commission_rate')
            ->with('toCurrency')
            ->completed()
            ->groupBy('to_currency_id')
            ->orderBy('total_revenue', 'desc')
            ->get();

        // Revenue by order type
        $typeRevenue = Order::select('type')
            ->selectRaw('SUM(commission_amount) as total_revenue')
            ->selectRaw('COUNT(*) as total_orders')
            ->completed()
            ->groupBy('type')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'revenue_data' => $revenueData,
                'currency_revenue' => $currencyRevenue,
                'type_revenue' => $typeRevenue
            ]
        ]);
    }

    /**
     * Create exchange rate
     */
    public function createExchangeRate(Request $request)
    {
        $request->validate([
            'from_currency_id' => 'required|exists:currencies,id',
            'to_currency_id' => 'required|exists:currencies,id',
            'rate' => 'required|numeric|min:0',
            'buy_rate' => 'nullable|numeric|min:0',
            'sell_rate' => 'nullable|numeric|min:0',
            'is_active' => 'boolean'
        ]);

        $exchangeRate = ExchangeRate::create([
            'from_currency_id' => $request->from_currency_id,
            'to_currency_id' => $request->to_currency_id,
            'rate' => $request->rate,
            'buy_rate' => $request->buy_rate,
            'sell_rate' => $request->sell_rate,
            'is_active' => $request->is_active ?? true,
            'last_updated' => now()
        ]);

        $exchangeRate->load(['fromCurrency', 'toCurrency']);

        return response()->json([
            'success' => true,
            'message' => 'Exchange rate created successfully',
            'data' => $exchangeRate
        ], 201);
    }

    /**
     * Update exchange rate
     */
    public function updateExchangeRate(Request $request, $id)
    {
        $exchangeRate = ExchangeRate::findOrFail($id);
        
        $request->validate([
            'rate' => 'nullable|numeric|min:0',
            'buy_rate' => 'nullable|numeric|min:0',
            'sell_rate' => 'nullable|numeric|min:0',
            'is_active' => 'boolean'
        ]);

        $updateData = $request->only(['rate', 'buy_rate', 'sell_rate', 'is_active']);
        $updateData['last_updated'] = now();
        
        $exchangeRate->update($updateData);
        $exchangeRate->load(['fromCurrency', 'toCurrency']);

        return response()->json([
            'success' => true,
            'message' => 'Exchange rate updated successfully',
            'data' => $exchangeRate
        ]);
    }

    /**
     * Delete exchange rate
     */
    public function deleteExchangeRate($id)
    {
        $exchangeRate = ExchangeRate::findOrFail($id);
        $exchangeRate->delete();

        return response()->json([
            'success' => true,
            'message' => 'Exchange rate deleted successfully'
        ]);
    }

    /**
     * Create discount
     */
    public function createDiscount(Request $request)
    {
        $request->validate([
            'code' => 'required|string|unique:discounts,code',
            'title' => 'required|string',
            'description' => 'nullable|string',
            'type' => 'required|in:percentage,fixed',
            'value' => 'required|numeric|min:0',
            'min_order_amount' => 'nullable|numeric|min:0',
            'max_discount_amount' => 'nullable|numeric|min:0',
            'usage_limit' => 'nullable|integer|min:1',
            'user_usage_limit' => 'nullable|integer|min:1',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after:starts_at',
            'is_active' => 'boolean'
        ]);

        $discount = Discount::create([
            'code' => $request->code,
            'title' => $request->title,
            'description' => $request->description,
            'type' => $request->type,
            'value' => $request->value,
            'min_order_amount' => $request->min_order_amount,
            'max_discount_amount' => $request->max_discount_amount,
            'usage_limit' => $request->usage_limit,
            'user_usage_limit' => $request->user_usage_limit,
            'starts_at' => $request->starts_at,
            'expires_at' => $request->expires_at,
            'is_active' => $request->is_active ?? true
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Discount created successfully',
            'data' => $discount
        ], 201);
    }

    /**
     * Update discount
     */
    public function updateDiscount(Request $request, $id)
    {
        $discount = Discount::findOrFail($id);
        
        $request->validate([
            'code' => 'nullable|string|unique:discounts,code,' . $id,
            'title' => 'nullable|string',
            'description' => 'nullable|string',
            'type' => 'nullable|in:percentage,fixed',
            'value' => 'nullable|numeric|min:0',
            'min_order_amount' => 'nullable|numeric|min:0',
            'max_discount_amount' => 'nullable|numeric|min:0',
            'usage_limit' => 'nullable|integer|min:1',
            'user_usage_limit' => 'nullable|integer|min:1',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date',
            'is_active' => 'boolean'
        ]);

        $discount->update($request->only([
            'code', 'title', 'description', 'type', 'value', 
            'min_order_amount', 'max_discount_amount', 
            'usage_limit', 'user_usage_limit', 'starts_at', 
            'expires_at', 'is_active'
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Discount updated successfully',
            'data' => $discount
        ]);
    }

    /**
     * List all discounts
     */
    public function listDiscounts(Request $request)
    {
        $query = Discount::with(['currency:id,symbol,name', 'user:id,name,email']);

        // Filter by status
        if ($request->has('status')) {
            if ($request->status === 'active') {
                $query->active();
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            } elseif ($request->status === 'expired') {
                $query->where('expires_at', '<', now());
            }
        }

        // Filter by type
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $discounts = $query->orderBy('created_at', 'desc')
                          ->paginate($request->per_page ?? 15);

        return response()->json([
            'success' => true,
            'data' => $discounts
        ]);
    }

    /**
     * Delete discount
     */
    public function deleteDiscount($id)
    {
        $discount = Discount::findOrFail($id);
        $discount->delete();

        return response()->json([
            'success' => true,
            'message' => 'Discount deleted successfully'
        ]);
    }
}
