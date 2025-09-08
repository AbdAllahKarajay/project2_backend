<?php

namespace App\Http\Controllers\Api\Admin;

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
     * Get admin dashboard overview
     */
    public function getDashboardOverview(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        if (!$user || !$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Admin access required.'
            ], 403);
        }

        $analytics = $this->analyticsService->getDashboardStats();
        
        return response()->json([
            'success' => true,
            'data' => $analytics,
        ]);
    }

    /**
     * Get revenue analytics
     */
    public function getRevenueAnalytics(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        if (!$user || !$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Admin access required.'
            ], 403);
        }

        $period = $request->get('period', 'month');
        $analytics = $this->analyticsService->getRevenueAnalytics($period);
        
        return response()->json([
            'success' => true,
            'data' => $analytics,
            'period' => $period,
        ]);
    }

    /**
     * Get service usage analytics
     */
    public function getServiceUsageAnalytics(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        if (!$user || !$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Admin access required.'
            ], 403);
        }

        $period = $request->get('period', 'month');
        $analytics = $this->analyticsService->getServiceUsageAnalytics($period);
        
        return response()->json([
            'success' => true,
            'data' => $analytics,
            'period' => $period,
        ]);
    }

    /**
     * Get customer analytics
     */
    public function getCustomerAnalytics(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        if (!$user || !$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Admin access required.'
            ], 403);
        }

        $period = $request->get('period', 'month');
        $analytics = $this->analyticsService->getCustomerAnalytics($period);
        
        return response()->json([
            'success' => true,
            'data' => $analytics,
            'period' => $period,
        ]);
    }

    /**
     * Get loyalty program analytics
     */
    public function getLoyaltyAnalytics(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        if (!$user || !$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Admin access required.'
            ], 403);
        }

        $period = $request->get('period', 'month');
        $analytics = $this->analyticsService->getLoyaltyAnalytics($period);
        
        return response()->json([
            'success' => true,
            'data' => $analytics,
            'period' => $period,
        ]);
    }

    /**
     * Get coupon analytics
     */
    public function getCouponAnalytics(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        if (!$user || !$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Admin access required.'
            ], 403);
        }

        $period = $request->get('period', 'month');
        $analytics = $this->analyticsService->getCouponAnalytics($period);
        
        return response()->json([
            'success' => true,
            'data' => $analytics,
            'period' => $period,
        ]);
    }

    /**
     * Get comprehensive analytics report
     */
    public function getComprehensiveReport(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        if (!$user || !$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Admin access required.'
            ], 403);
        }

        $period = $request->get('period', 'month');
        
        $report = [
            'dashboard' => $this->analyticsService->getDashboardStats(),
            'revenue' => $this->analyticsService->getRevenueAnalytics($period),
            'service_usage' => $this->analyticsService->getServiceUsageAnalytics($period),
            'customers' => $this->analyticsService->getCustomerAnalytics($period),
            'loyalty' => $this->analyticsService->getLoyaltyAnalytics($period),
            'coupons' => $this->analyticsService->getCouponAnalytics($period),
        ];
        
        return response()->json([
            'success' => true,
            'data' => $report,
            'period' => $period,
            'generated_at' => now()->toISOString(),
        ]);
    }

    /**
     * Get analytics for specific date range
     */
    public function getDateRangeAnalytics(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        if (!$user || !$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Admin access required.'
            ], 403);
        }

        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $startDate = $request->start_date;
        $endDate = $request->end_date;
        
        // This would require extending the AnalyticsService to accept custom date ranges
        // For now, we'll return a message indicating this feature needs implementation
        return response()->json([
            'success' => true,
            'message' => 'Date range analytics feature will be implemented in the next update.',
            'data' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
        ]);
    }

    /**
     * Export analytics data
     */
    public function exportAnalytics(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        if (!$user || !$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Admin access required.'
            ], 403);
        }

        $format = $request->get('format', 'json');
        $period = $request->get('period', 'month');
        
        $report = [
            'dashboard' => $this->analyticsService->getDashboardStats(),
            'revenue' => $this->analyticsService->getRevenueAnalytics($period),
            'service_usage' => $this->analyticsService->getServiceUsageAnalytics($period),
            'customers' => $this->analyticsService->getCustomerAnalytics($period),
            'loyalty' => $this->analyticsService->getLoyaltyAnalytics($period),
            'coupons' => $this->analyticsService->getCouponAnalytics($period),
        ];
        
        $analytics = $report;
        
        // For now, return JSON data
        // In a real implementation, you would generate CSV, Excel, or PDF files
        return response()->json([
            'success' => true,
            'message' => 'Analytics data exported successfully.',
            'data' => $analytics,
            'format' => $format,
            'period' => $period,
        ]);
    }
}