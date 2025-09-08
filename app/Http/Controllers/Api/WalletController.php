<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\WalletTopupRequest;
use App\Models\WalletTransaction;
use App\Services\FcmService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class WalletController extends Controller
{
    private FcmService $fcmService;

    public function __construct(FcmService $fcmService)
    {
        $this->fcmService = $fcmService;
    }
    /**
     * Get the authenticated user's wallet balance.
     */
    public function show(): JsonResponse
    {
        $user = Auth::user();
        
        return response()->json([
            'success' => true,
            'data' => [
                'balance' => $user->wallet_balance,
                'currency' => 'USD',
                'formatted_balance' => '$' . number_format($user->wallet_balance, 2),
            ]
        ]);
    }

    /**
     * Add money to the user's wallet.
     */
    public function topup(WalletTopupRequest $request): JsonResponse
    {
        $user = Auth::user();
        $validated = $request->validated();

        try {
            DB::beginTransaction();

            $balanceBefore = $user->wallet_balance;
            $amount = $validated['amount'];

            // Create wallet transaction record
            $transaction = WalletTransaction::create([
                'user_id' => $user->id,
                'type' => 'topup',
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceBefore + $amount,
                'reference' => $validated['reference'] ?? null,
                'description' => $validated['description'] ?? 'Wallet top-up',
                'metadata' => [
                    'payment_method' => $validated['payment_method'],
                    'topup_amount' => $amount,
                ],
                'status' => 'completed',
            ]);

            // Update user's wallet balance
            $user->addToWallet($amount);

            DB::commit();

            // Send wallet topup notification
            if ($user->hasFcmToken()) {
                $this->fcmService->sendPaymentNotification(
                    $user,
                    $amount,
                    'received'
                );
            }

            return response()->json([
                'success' => true,
                'message' => 'Wallet topped up successfully.',
                'data' => [
                    'transaction_id' => $transaction->id,
                    'amount' => $amount,
                    'new_balance' => $user->wallet_balance,
                    'formatted_new_balance' => '$' . number_format($user->wallet_balance, 2),
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to process top-up. Please try again.',
            ], 500);
        }
    }

    /**
     * Get the authenticated user's wallet transaction history.
     */
    public function transactions(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        $query = $user->walletTransactions()
            ->orderBy('created_at', 'desc');

        // Apply filters
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $transactions = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => [
                'transactions' => $transactions->items(),
                'pagination' => [
                    'current_page' => $transactions->currentPage(),
                    'last_page' => $transactions->lastPage(),
                    'per_page' => $transactions->perPage(),
                    'total' => $transactions->total(),
                ]
            ]
        ]);
    }

    /**
     * Get wallet statistics and summary.
     */
    public function stats(): JsonResponse
    {
        $user = Auth::user();
        
        $stats = [
            'total_topups' => $user->walletTransactions()->where('type', 'topup')->sum('amount'),
            'total_payments' => $user->walletTransactions()->where('type', 'payment')->sum('amount'),
            'total_refunds' => $user->walletTransactions()->where('type', 'refund')->sum('amount'),
            'total_bonuses' => $user->walletTransactions()->where('type', 'bonus')->sum('amount'),
            'transaction_count' => $user->walletTransactions()->count(),
            'last_transaction' => $user->walletTransactions()->latest()->first(),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }
}
