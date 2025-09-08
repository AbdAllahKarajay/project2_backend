<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AnalyticsController extends Controller
{
    private AnalyticsService $analyticsService;

    public function __construct(AnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }

    /**
     * Get user's personal analytics
     */
    public function getUserAnalytics(Request $request): JsonResponse
    {
        $user = Auth::user();
        $period = $request->get('period', 'month');
        
        $analytics = $this->analyticsService->getUserAnalytics($user, $period);
        
        return response()->json([
            'success' => true,
            'data' => $analytics,
            'period' => $period,
        ]);
    }

    /**
     * Get user's booking history analytics
     */
    public function getBookingHistory(Request $request): JsonResponse
    {
        $user = Auth::user();
        $period = $request->get('period', 'month');
        $startDate = $this->getStartDate($period);
        
        $bookings = $user->serviceRequests()
            ->with('service')
            ->where('created_at', '>=', $startDate)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function($booking) {
                return [
                    'id' => $booking->id,
                    'service_name' => $booking->service->name,
                    'status' => $booking->status,
                    'total_price' => $booking->total_price,
                    'scheduled_at' => $booking->scheduled_at,
                    'created_at' => $booking->created_at,
                ];
            });
        
        $stats = [
            'total_bookings' => $bookings->count(),
            'completed_bookings' => $bookings->where('status', 'completed')->count(),
            'pending_bookings' => $bookings->where('status', 'pending')->count(),
            'cancelled_bookings' => $bookings->where('status', 'cancelled')->count(),
            'total_spent' => $bookings->sum('total_price'),
            'average_booking_value' => $bookings->count() > 0 
                ? round($bookings->sum('total_price') / $bookings->count(), 2) 
                : 0,
        ];
        
        return response()->json([
            'success' => true,
            'data' => [
                'bookings' => $bookings,
                'stats' => $stats,
            ],
            'period' => $period,
        ]);
    }

    /**
     * Get user's loyalty points analytics
     */
    public function getLoyaltyAnalytics(Request $request): JsonResponse
    {
        $user = Auth::user();
        $period = $request->get('period', 'month');
        $startDate = $this->getStartDate($period);
        
        $pointsHistory = $user->loyaltyPoints()
            ->where('created_at', '>=', $startDate)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function($point) {
                return [
                    'id' => $point->id,
                    'points' => $point->points,
                    'source' => $point->source,
                    'description' => $point->description,
                    'created_at' => $point->created_at,
                ];
            });
        
        $stats = [
            'total_points' => $user->total_loyalty_points,
            'points_earned' => $pointsHistory->where('points', '>', 0)->sum('points'),
            'points_spent' => abs($pointsHistory->where('points', '<', 0)->sum('points')),
            'transactions_count' => $pointsHistory->count(),
        ];
        
        return response()->json([
            'success' => true,
            'data' => [
                'points_history' => $pointsHistory,
                'stats' => $stats,
            ],
            'period' => $period,
        ]);
    }

    /**
     * Get user's wallet analytics
     */
    public function getWalletAnalytics(Request $request): JsonResponse
    {
        $user = Auth::user();
        $period = $request->get('period', 'month');
        $startDate = $this->getStartDate($period);
        
        $transactions = $user->walletTransactions()
            ->where('created_at', '>=', $startDate)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function($transaction) {
                return [
                    'id' => $transaction->id,
                    'type' => $transaction->type,
                    'amount' => $transaction->amount,
                    'balance_before' => $transaction->balance_before,
                    'balance_after' => $transaction->balance_after,
                    'description' => $transaction->description,
                    'status' => $transaction->status,
                    'created_at' => $transaction->created_at,
                ];
            });
        
        $stats = [
            'current_balance' => $user->wallet_balance,
            'total_topups' => $transactions->where('type', 'topup')->sum('amount'),
            'total_spent' => abs($transactions->where('type', 'payment')->sum('amount')),
            'transactions_count' => $transactions->count(),
        ];
        
        return response()->json([
            'success' => true,
            'data' => [
                'transactions' => $transactions,
                'stats' => $stats,
            ],
            'period' => $period,
        ]);
    }

    /**
     * Get user's spending trends
     */
    public function getSpendingTrends(Request $request): JsonResponse
    {
        $user = Auth::user();
        $period = $request->get('period', 'month');
        $startDate = $this->getStartDate($period);
        $dateFormat = $this->getDateFormat($period);
        
        $spendingData = $user->serviceRequests()
            ->where('created_at', '>=', $startDate)
            ->selectRaw("DATE_FORMAT(created_at, '{$dateFormat}') as period, SUM(total_price) as total_spent, COUNT(*) as bookings_count")
            ->groupBy('period')
            ->orderBy('period')
            ->get();
        
        return response()->json([
            'success' => true,
            'data' => $spendingData,
            'period' => $period,
        ]);
    }

    /**
     * Get user's service preferences
     */
    public function getServicePreferences(Request $request): JsonResponse
    {
        $user = Auth::user();
        $period = $request->get('period', 'month');
        $startDate = $this->getStartDate($period);
        
        $servicePreferences = $user->serviceRequests()
            ->with('service')
            ->where('created_at', '>=', $startDate)
            ->selectRaw('service_id, COUNT(*) as bookings_count, SUM(total_price) as total_spent')
            ->groupBy('service_id')
            ->orderByDesc('bookings_count')
            ->get()
            ->map(function($preference) {
                return [
                    'service_id' => $preference->service_id,
                    'service_name' => $preference->service->name,
                    'bookings_count' => $preference->bookings_count,
                    'total_spent' => $preference->total_spent,
                ];
            });
        
        return response()->json([
            'success' => true,
            'data' => $servicePreferences,
            'period' => $period,
        ]);
    }

    /**
     * Get start date based on period
     */
    private function getStartDate(string $period): \Carbon\Carbon
    {
        return match($period) {
            'day' => now()->subDay(),
            'week' => now()->subWeek(),
            'month' => now()->subMonth(),
            'year' => now()->subYear(),
            default => now()->subMonth(),
        };
    }

    /**
     * Get date format for grouping
     */
    private function getDateFormat(string $period): string
    {
        return match($period) {
            'day' => '%Y-%m-%d',
            'week' => '%Y-%u',
            'month' => '%Y-%m',
            'year' => '%Y',
            default => '%Y-%m',
        };
    }
}