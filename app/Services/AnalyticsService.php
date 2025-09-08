<?php

namespace App\Services;

use App\Models\User;
use App\Models\Service;
use App\Models\ServiceRequest;
use App\Models\Payment;
use App\Models\WalletTransaction;
use App\Models\LoyaltyPoints;
use App\Models\CouponUsage;
use App\Models\Rating;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AnalyticsService
{
    /**
     * Get overall dashboard statistics
     */
    public function getDashboardStats(): array
    {
        $totalUsers = User::count();
        $totalServices = Service::count();
        $totalBookings = ServiceRequest::count();
        $totalRevenue = Payment::where('status', 'paid')->sum('amount');
        
        $activeUsers = User::whereHas('serviceRequests', function($query) {
            $query->where('created_at', '>=', now()->subDays(30));
        })->count();
        
        $completedBookings = ServiceRequest::where('status', 'completed')->count();
        $pendingBookings = ServiceRequest::where('status', 'pending')->count();
        $inProgressBookings = ServiceRequest::where('status', 'in_progress')->count();
        
        $averageRating = Rating::avg('rating') ?? 0;
        
        return [
            'overview' => [
                'total_users' => $totalUsers,
                'total_services' => $totalServices,
                'total_bookings' => $totalBookings,
                'total_revenue' => $totalRevenue,
                'active_users' => $activeUsers,
                'average_rating' => round($averageRating, 2),
            ],
            'bookings' => [
                'completed' => $completedBookings,
                'pending' => $pendingBookings,
                'in_progress' => $inProgressBookings,
                'cancelled' => ServiceRequest::where('status', 'cancelled')->count(),
            ],
            'completion_rate' => $totalBookings > 0 ? round(($completedBookings / $totalBookings) * 100, 2) : 0,
        ];
    }

    /**
     * Get revenue analytics
     */
    public function getRevenueAnalytics(string $period = 'month'): array
    {
        $dateFormat = $this->getDateFormat($period);
        $startDate = $this->getStartDate($period);
        
        $revenueData = Payment::where('status', 'paid')
            ->where('created_at', '>=', $startDate)
            ->selectRaw("DATE_FORMAT(created_at, '{$dateFormat}') as period, SUM(amount) as revenue, COUNT(*) as transactions")
            ->groupBy('period')
            ->orderBy('period')
            ->get();
        
        $totalRevenue = $revenueData->sum('revenue');
        $totalTransactions = $revenueData->sum('transactions');
        $averageTransactionValue = $totalTransactions > 0 ? $totalRevenue / $totalTransactions : 0;
        
        // Revenue by payment method
        $revenueByMethod = Payment::where('status', 'paid')
            ->where('created_at', '>=', $startDate)
            ->selectRaw('method, SUM(amount) as revenue, COUNT(*) as count')
            ->groupBy('method')
            ->get();
        
        // Monthly revenue growth
        $currentMonthRevenue = Payment::where('status', 'paid')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('amount');
        
        $lastMonthRevenue = Payment::where('status', 'paid')
            ->whereMonth('created_at', now()->subMonth()->month)
            ->whereYear('created_at', now()->subMonth()->year)
            ->sum('amount');
        
        $revenueGrowth = $lastMonthRevenue > 0 
            ? round((($currentMonthRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100, 2)
            : 0;
        
        return [
            'total_revenue' => $totalRevenue,
            'total_transactions' => $totalTransactions,
            'average_transaction_value' => round($averageTransactionValue, 2),
            'revenue_growth' => $revenueGrowth,
            'revenue_data' => $revenueData,
            'revenue_by_method' => $revenueByMethod,
        ];
    }

    /**
     * Get service usage analytics
     */
    public function getServiceUsageAnalytics(string $period = 'month'): array
    {
        $startDate = $this->getStartDate($period);
        
        // Most popular services
        $popularServices = ServiceRequest::with('service')
            ->where('created_at', '>=', $startDate)
            ->selectRaw('service_id, COUNT(*) as bookings, SUM(total_price) as revenue')
            ->groupBy('service_id')
            ->orderByDesc('bookings')
            ->limit(10)
            ->get()
            ->map(function($item) {
                return [
                    'service_id' => $item->service_id,
                    'service_name' => $item->service->name,
                    'bookings' => $item->bookings,
                    'revenue' => $item->revenue,
                ];
            });
        
        // Service completion rates
        $serviceStats = ServiceRequest::with('service')
            ->where('created_at', '>=', $startDate)
            ->selectRaw('service_id, status, COUNT(*) as count')
            ->groupBy('service_id', 'status')
            ->get()
            ->groupBy('service_id')
            ->map(function($statuses) {
                $total = $statuses->sum('count');
                $completed = $statuses->where('status', 'completed')->sum('count');
                return [
                    'total_bookings' => $total,
                    'completed_bookings' => $completed,
                    'completion_rate' => $total > 0 ? round(($completed / $total) * 100, 2) : 0,
                ];
            });
        
        // Average service rating
        $serviceRatings = Rating::with('serviceRequest.service')
            ->whereHas('serviceRequest', function($query) use ($startDate) {
                $query->where('created_at', '>=', $startDate);
            })
            ->selectRaw('service_requests.service_id, AVG(ratings.rating) as average_rating, COUNT(*) as total_ratings')
            ->join('service_requests', 'ratings.service_request_id', '=', 'service_requests.id')
            ->groupBy('service_requests.service_id')
            ->get()
            ->map(function($item) {
                return [
                    'service_id' => $item->service_id,
                    'average_rating' => round($item->average_rating, 2),
                    'total_ratings' => $item->total_ratings,
                ];
            });
        
        return [
            'popular_services' => $popularServices,
            'service_stats' => $serviceStats,
            'service_ratings' => $serviceRatings,
        ];
    }

    /**
     * Get customer analytics
     */
    public function getCustomerAnalytics(string $period = 'month'): array
    {
        $startDate = $this->getStartDate($period);
        
        // Customer acquisition
        $newCustomers = User::where('created_at', '>=', $startDate)->count();
        $totalCustomers = User::count();
        
        // Customer activity levels
        $activeCustomers = User::whereHas('serviceRequests', function($query) use ($startDate) {
            $query->where('created_at', '>=', $startDate);
        })->count();
        
        // Top customers by spending
        $topCustomers = User::withCount('serviceRequests')
            ->whereHas('serviceRequests', function($query) use ($startDate) {
                $query->where('created_at', '>=', $startDate);
            })
            ->with(['serviceRequests' => function($query) use ($startDate) {
                $query->where('created_at', '>=', $startDate);
            }])
            ->get()
            ->map(function($user) {
                $totalSpent = $user->serviceRequests->sum('total_price');
                return [
                    'user_id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'total_spent' => $totalSpent,
                    'total_bookings' => $user->service_requests_count,
                    'average_booking_value' => $user->service_requests_count > 0 
                        ? round($totalSpent / $user->service_requests_count, 2) 
                        : 0,
                ];
            })
            ->sortByDesc('total_spent')
            ->take(10)
            ->values();
        
        // Customer retention
        $retentionData = $this->calculateCustomerRetention($startDate);
        
        // Customer lifetime value
        $customerLTV = $this->calculateCustomerLTV();
        
        return [
            'new_customers' => $newCustomers,
            'total_customers' => $totalCustomers,
            'active_customers' => $activeCustomers,
            'customer_retention' => $retentionData,
            'customer_ltv' => $customerLTV,
            'top_customers' => $topCustomers,
        ];
    }

    /**
     * Get loyalty program analytics
     */
    public function getLoyaltyAnalytics(string $period = 'month'): array
    {
        $startDate = $this->getStartDate($period);
        
        // Total points distributed
        $totalPointsDistributed = LoyaltyPoints::where('created_at', '>=', $startDate)
            ->where('points', '>', 0)
            ->sum('points');
        
        // Total points redeemed
        $totalPointsRedeemed = abs(LoyaltyPoints::where('created_at', '>=', $startDate)
            ->where('points', '<', 0)
            ->sum('points'));
        
        // Active loyalty members
        $activeLoyaltyMembers = User::whereHas('loyaltyPoints', function($query) use ($startDate) {
            $query->where('created_at', '>=', $startDate);
        })->count();
        
        // Points distribution by source
        $pointsBySource = LoyaltyPoints::where('created_at', '>=', $startDate)
            ->where('points', '>', 0)
            ->selectRaw('source, SUM(points) as total_points, COUNT(*) as transactions')
            ->groupBy('source')
            ->get();
        
        // Top loyalty users
        $topLoyaltyUsers = User::withSum('loyaltyPoints', 'points')
            ->whereHas('loyaltyPoints', function($query) use ($startDate) {
                $query->where('created_at', '>=', $startDate);
            })
            ->orderByDesc('loyalty_points_sum_points')
            ->limit(10)
            ->get()
            ->map(function($user) {
                return [
                    'user_id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'total_points' => $user->loyalty_points_sum_points ?? 0,
                ];
            });
        
        return [
            'total_points_distributed' => $totalPointsDistributed,
            'total_points_redeemed' => $totalPointsRedeemed,
            'active_loyalty_members' => $activeLoyaltyMembers,
            'points_by_source' => $pointsBySource,
            'top_loyalty_users' => $topLoyaltyUsers,
        ];
    }

    /**
     * Get coupon analytics
     */
    public function getCouponAnalytics(string $period = 'month'): array
    {
        $startDate = $this->getStartDate($period);
        
        // Coupon usage statistics
        $couponUsage = CouponUsage::with('coupon')
            ->where('created_at', '>=', $startDate)
            ->selectRaw('coupon_id, COUNT(*) as usage_count')
            ->groupBy('coupon_id')
            ->orderByDesc('usage_count')
            ->get()
            ->map(function($usage) {
                return [
                    'coupon_id' => $usage->coupon_id,
                    'coupon_code' => $usage->coupon->code,
                    'coupon_name' => $usage->coupon->name,
                    'usage_count' => $usage->usage_count,
                ];
            });
        
        // Total discount given
        $totalDiscount = CouponUsage::with('coupon', 'serviceRequest')
            ->where('created_at', '>=', $startDate)
            ->get()
            ->sum(function($usage) {
                $coupon = $usage->coupon;
                $serviceRequest = $usage->serviceRequest;
                
                if ($coupon->type === 'percentage') {
                    return ($coupon->value / 100) * $serviceRequest->total_price;
                } else {
                    return $coupon->value;
                }
            });
        
        // Most effective coupons
        $effectiveCoupons = CouponUsage::with('coupon')
            ->where('created_at', '>=', $startDate)
            ->selectRaw('coupon_id, COUNT(*) as usage_count')
            ->groupBy('coupon_id')
            ->orderByDesc('usage_count')
            ->limit(5)
            ->get();
        
        return [
            'coupon_usage' => $couponUsage,
            'total_discount_given' => round($totalDiscount, 2),
            'effective_coupons' => $effectiveCoupons,
        ];
    }

    /**
     * Get user-specific analytics
     */
    public function getUserAnalytics(User $user, string $period = 'month'): array
    {
        $startDate = $this->getStartDate($period);
        
        // User's booking history
        $bookings = ServiceRequest::with('service')
            ->where('user_id', $user->id)
            ->where('created_at', '>=', $startDate)
            ->get();
        
        $totalSpent = $bookings->sum('total_price');
        $completedBookings = $bookings->where('status', 'completed')->count();
        $averageBookingValue = $bookings->count() > 0 ? $totalSpent / $bookings->count() : 0;
        
        // User's loyalty points
        $totalPoints = $user->total_loyalty_points;
        $pointsEarned = $user->loyaltyPoints()
            ->where('created_at', '>=', $startDate)
            ->where('points', '>', 0)
            ->sum('points');
        
        // User's wallet activity
        $walletTransactions = WalletTransaction::where('user_id', $user->id)
            ->where('created_at', '>=', $startDate)
            ->get();
        
        $walletTopups = $walletTransactions->where('type', 'topup')->sum('amount');
        $walletSpent = abs($walletTransactions->where('type', 'payment')->sum('amount'));
        
        // User's ratings given
        $ratingsGiven = Rating::where('user_id', $user->id)
            ->where('created_at', '>=', $startDate)
            ->count();
        
        $averageRatingGiven = Rating::where('user_id', $user->id)
            ->where('created_at', '>=', $startDate)
            ->avg('rating') ?? 0;
        
        return [
            'total_spent' => $totalSpent,
            'total_bookings' => $bookings->count(),
            'completed_bookings' => $completedBookings,
            'average_booking_value' => round($averageBookingValue, 2),
            'total_loyalty_points' => $totalPoints,
            'points_earned' => $pointsEarned,
            'wallet_topups' => $walletTopups,
            'wallet_spent' => $walletSpent,
            'ratings_given' => $ratingsGiven,
            'average_rating_given' => round($averageRatingGiven, 2),
        ];
    }

    /**
     * Calculate customer retention rate
     */
    private function calculateCustomerRetention(Carbon $startDate): array
    {
        $totalCustomers = User::where('created_at', '<', $startDate)->count();
        
        if ($totalCustomers === 0) {
            return ['retention_rate' => 0, 'retained_customers' => 0];
        }
        
        $retainedCustomers = User::where('created_at', '<', $startDate)
            ->whereHas('serviceRequests', function($query) use ($startDate) {
                $query->where('created_at', '>=', $startDate);
            })
            ->count();
        
        $retentionRate = round(($retainedCustomers / $totalCustomers) * 100, 2);
        
        return [
            'retention_rate' => $retentionRate,
            'retained_customers' => $retainedCustomers,
            'total_customers' => $totalCustomers,
        ];
    }

    /**
     * Calculate customer lifetime value
     */
    private function calculateCustomerLTV(): float
    {
        $totalRevenue = Payment::where('status', 'paid')->sum('amount');
        $totalCustomers = User::count();
        
        return $totalCustomers > 0 ? round($totalRevenue / $totalCustomers, 2) : 0;
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

    /**
     * Get start date based on period
     */
    private function getStartDate(string $period): Carbon
    {
        return match($period) {
            'day' => now()->subDay(),
            'week' => now()->subWeek(),
            'month' => now()->subMonth(),
            'year' => now()->subYear(),
            default => now()->subMonth(),
        };
    }
}
