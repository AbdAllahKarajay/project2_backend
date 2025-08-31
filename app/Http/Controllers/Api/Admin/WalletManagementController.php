<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class WalletManagementController extends Controller
{
    public function __construct()
    {
        // Ensure only admins can access this controller
        $this->middleware(function ($request, $next) {
            if (!Auth::user()->isAdmin()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. Admin access required.'
                ], 403);
            }
            return $next($request);
        });
    }

    /**
     * Get all users with their wallet balances.
     */
    public function index(Request $request): JsonResponse
    {
        $query = User::with('walletTransactions')
            ->select('id', 'name', 'email', 'phone', 'wallet_balance', 'created_at');

        // Apply filters
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($request->has('min_balance')) {
            $query->where('wallet_balance', '>=', $request->min_balance);
        }

        if ($request->has('max_balance')) {
            $query->where('wallet_balance', '<=', $request->max_balance);
        }

        $users = $query->orderBy('name')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => [
                'users' => $users->items(),
                'pagination' => [
                    'current_page' => $users->currentPage(),
                    'last_page' => $users->lastPage(),
                    'per_page' => $users->perPage(),
                    'total' => $users->total(),
                ]
            ]
        ]);
    }

    /**
     * Get a specific user's wallet details and transactions.
     */
    public function show($userId): JsonResponse
    {
        $user = User::with(['walletTransactions' => function ($query) {
            $query->orderBy('created_at', 'desc');
        }])->findOrFail($userId);

        $walletData = [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'wallet_balance' => $user->wallet_balance,
                'formatted_balance' => '$' . number_format($user->wallet_balance, 2),
            ],
            'transactions' => $user->walletTransactions->take(10)->map(function ($transaction) {
                return [
                    'id' => $transaction->id,
                    'type' => $transaction->type,
                    'type_label' => $transaction->type_label,
                    'amount' => $transaction->amount,
                    'formatted_amount' => $transaction->formatted_amount,
                    'balance_before' => $transaction->balance_before,
                    'balance_after' => $transaction->balance_after,
                    'description' => $transaction->description,
                    'status' => $transaction->status,
                    'created_at' => $transaction->created_at->format('M d, Y H:i'),
                ];
            }),
            'statistics' => [
                'total_transactions' => $user->walletTransactions()->count(),
                'total_topups' => $user->walletTransactions()->where('type', 'topup')->sum('amount'),
                'total_payments' => $user->walletTransactions()->where('type', 'payment')->sum('amount'),
                'total_refunds' => $user->walletTransactions()->where('type', 'refund')->sum('amount'),
            ]
        ];

        return response()->json([
            'success' => true,
            'data' => $walletData
        ]);
    }

    /**
     * Refill a user's wallet (admin action).
     */
    public function refill(Request $request, $userId): JsonResponse
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01|max:10000.00',
            'description' => 'required|string|max:500',
            'reference' => 'nullable|string|max:255',
        ]);

        $user = User::findOrFail($userId);
        $admin = Auth::user();

        try {
            DB::beginTransaction();

            $balanceBefore = $user->wallet_balance;
            $amount = $request->amount;

            // Create wallet transaction record
            $transaction = WalletTransaction::create([
                'user_id' => $user->id,
                'type' => 'topup',
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceBefore + $amount,
                'reference' => $request->reference ?? 'ADMIN_REFILL',
                'description' => $request->description,
                'metadata' => [
                    'admin_id' => $admin->id,
                    'admin_name' => $admin->name,
                    'refill_amount' => $amount,
                    'refill_reason' => $request->description,
                ],
                'status' => 'completed',
            ]);

            // Update user's wallet balance
            $user->addToWallet($amount);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Wallet refilled successfully.',
                'data' => [
                    'transaction_id' => $transaction->id,
                    'user_name' => $user->name,
                    'amount' => $amount,
                    'new_balance' => $user->wallet_balance,
                    'formatted_new_balance' => '$' . number_format($user->wallet_balance, 2),
                    'admin_name' => $admin->name,
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to refill wallet. Please try again.',
            ], 500);
        }
    }

    /**
     * Deduct from a user's wallet (admin action).
     */
    public function deduct(Request $request, $userId): JsonResponse
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01|max:10000.00',
            'description' => 'required|string|max:500',
            'reference' => 'nullable|string|max:255',
        ]);

        $user = User::findOrFail($userId);
        $admin = Auth::user();

        if (!$user->hasSufficientBalance($request->amount)) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient wallet balance for deduction.',
            ], 400);
        }

        try {
            DB::beginTransaction();

            $balanceBefore = $user->wallet_balance;
            $amount = $request->amount;

            // Create wallet transaction record
            $transaction = WalletTransaction::create([
                'user_id' => $user->id,
                'type' => 'deduction',
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceBefore - $amount,
                'reference' => $request->reference ?? 'ADMIN_DEDUCTION',
                'description' => $request->description,
                'metadata' => [
                    'admin_id' => $admin->id,
                    'admin_name' => $admin->name,
                    'deduction_amount' => $amount,
                    'deduction_reason' => $request->description,
                ],
                'status' => 'completed',
            ]);

            // Update user's wallet balance
            $user->deductFromWallet($amount);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Amount deducted successfully.',
                'data' => [
                    'transaction_id' => $transaction->id,
                    'user_name' => $user->name,
                    'amount' => $amount,
                    'new_balance' => $user->wallet_balance,
                    'formatted_new_balance' => '$' . number_format($user->wallet_balance, 2),
                    'admin_name' => $admin->name,
                ]
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to deduct amount. Please try again.',
            ], 500);
        }
    }

    /**
     * Get system-wide wallet statistics.
     */
    public function stats(): JsonResponse
    {
        $stats = [
            'total_users' => User::count(),
            'users_with_balance' => User::where('wallet_balance', '>', 0)->count(),
            'total_wallet_balance' => User::sum('wallet_balance'),
            'average_wallet_balance' => User::avg('wallet_balance'),
            'total_transactions' => WalletTransaction::count(),
            'total_topups' => WalletTransaction::where('type', 'topup')->sum('amount'),
            'total_payments' => WalletTransaction::where('type', 'payment')->sum('amount'),
            'total_refunds' => WalletTransaction::where('type', 'refund')->sum('amount'),
            'total_deductions' => WalletTransaction::where('type', 'deduction')->sum('amount'),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }
}

