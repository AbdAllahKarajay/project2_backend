<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RedeemLoyaltyRewardRequest;
use App\Models\LoyaltyReward;
use App\Models\LoyaltyRewardRedemption;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class LoyaltyController extends Controller
{
    /**
     * Get the authenticated user's loyalty points.
     */
    public function points(): JsonResponse
    {
        $user = Auth::user();
        
        $pointsData = [
            'total_points' => $user->total_loyalty_points,
            'formatted_points' => number_format($user->total_loyalty_points) . ' points',
            'recent_activity' => $user->loyaltyPoints()
                ->with('sourceRequest.service')
                ->latest()
                ->take(5)
                ->get()
                ->map(function ($point) {
                    return [
                        'id' => $point->id,
                        'points' => $point->formatted_points,
                        'source' => $point->sourceRequest ? $point->sourceRequest->service->name : 'Manual',
                        'created_at' => $point->created_at->format('M d, Y'),
                    ];
                }),
        ];

        return response()->json([
            'success' => true,
            'data' => $pointsData
        ]);
    }

    /**
     * Get available loyalty rewards.
     */
    public function rewards(Request $request): JsonResponse
    {
        $user = Auth::user();
        $userPoints = $user->total_loyalty_points;

        $query = LoyaltyReward::where('is_active', true)
            ->where(function ($q) use ($userPoints) {
                $q->where('points_required', '<=', $userPoints)
                  ->orWhereNull('max_redemptions')
                  ->orWhere('current_redemptions', '<', DB::raw('max_redemptions'));
            });

        // Apply filters
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('min_points')) {
            $query->where('points_required', '>=', $request->min_points);
        }

        if ($request->has('max_points')) {
            $query->where('points_required', '<=', $request->max_points);
        }

        $rewards = $query->orderBy('points_required', 'asc')
            ->paginate($request->get('per_page', 15));

        // Add availability info for each reward
        $rewards->getCollection()->transform(function ($reward) use ($userPoints) {
            $reward->can_afford = $userPoints >= $reward->points_required;
            $reward->is_available = $reward->isAvailable();
            return $reward;
        });

        return response()->json([
            'success' => true,
            'data' => [
                'rewards' => $rewards->items(),
                'pagination' => [
                    'current_page' => $rewards->currentPage(),
                    'last_page' => $rewards->lastPage(),
                    'per_page' => $rewards->perPage(),
                    'total' => $rewards->total(),
                ],
                'user_points' => $userPoints,
            ]
        ]);
    }

    /**
     * Redeem a loyalty reward.
     */
    public function redeem(RedeemLoyaltyRewardRequest $request): JsonResponse
    {
        $user = Auth::user();
        $reward = LoyaltyReward::findOrFail($request->loyalty_reward_id);

        // Validate reward can be redeemed
        if (!$reward->canBeRedeemed()) {
            return response()->json([
                'success' => false,
                'message' => 'This reward is not available for redemption.',
            ], 400);
        }

        // Check if user has enough points
        if (!$user->hasEnoughPoints($reward->points_required)) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient loyalty points for this reward.',
            ], 400);
        }

        try {
            DB::beginTransaction();

            // Create redemption record
            $redemption = LoyaltyRewardRedemption::create([
                'user_id' => $user->id,
                'loyalty_reward_id' => $reward->id,
                'points_spent' => $reward->points_required,
                'status' => 'active',
                'expires_at' => $reward->valid_until ? Carbon::parse($reward->valid_until) : null,
                'metadata' => [
                    'reward_type' => $reward->type,
                    'reward_value' => $reward->value,
                    'reward_code' => $reward->code,
                ],
            ]);

            // Deduct points from user
            $user->deductLoyaltyPoints($reward->points_required);

            // Increment reward redemptions
            $reward->incrementRedemptions();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Reward redeemed successfully!',
                'data' => [
                    'redemption_id' => $redemption->id,
                    'reward_name' => $reward->name,
                    'points_spent' => $reward->points_required,
                    'remaining_points' => $user->total_loyalty_points,
                    'reward_details' => [
                        'type' => $reward->type_label,
                        'value' => $reward->formatted_value,
                        'code' => $reward->code,
                        'expires_at' => $redemption->formatted_expiry,
                    ],
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to redeem reward. Please try again.',
            ], 500);
        }
    }

    /**
     * Get user's redemption history.
     */
    public function redemptions(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        $query = $user->loyaltyRewardRedemptions()
            ->with('reward')
            ->orderBy('created_at', 'desc');

        // Apply filters
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $redemptions = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => [
                'redemptions' => $redemptions->items(),
                'pagination' => [
                    'current_page' => $redemptions->currentPage(),
                    'last_page' => $redemptions->lastPage(),
                    'per_page' => $redemptions->perPage(),
                    'total' => $redemptions->total(),
                ]
            ]
        ]);
    }

    /**
     * Get loyalty statistics and summary.
     */
    public function stats(): JsonResponse
    {
        $user = Auth::user();
        
        $stats = [
            'total_points_earned' => $user->loyaltyPoints()->where('points', '>', 0)->sum('points'),
            'total_points_spent' => abs($user->loyaltyPoints()->where('points', '<', 0)->sum('points')),
            'current_balance' => $user->total_loyalty_points,
            'total_rewards_redeemed' => $user->loyaltyRewardRedemptions()->count(),
            'active_rewards' => $user->loyaltyRewardRedemptions()->where('status', 'active')->count(),
            'used_rewards' => $user->loyaltyRewardRedemptions()->where('status', 'used')->count(),
            'expired_rewards' => $user->loyaltyRewardRedemptions()->where('status', 'expired')->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }
}
