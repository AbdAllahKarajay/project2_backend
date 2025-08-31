<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Requests\ChangePasswordRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    /**
     * Get the authenticated user's profile.
     */
    public function show(): JsonResponse
    {
        $user = Auth::user();
        
        return response()->json([
            'success' => true,
            'data' => new UserResource($user)
        ]);
    }

    /**
     * Update the authenticated user's profile.
     */
    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user = Auth::user();
        $validated = $request->validated();

        // Only update fields that were provided
        $user->fill($validated);
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully.',
            'data' => new UserResource($user)
        ]);
    }

    /**
     * Change the authenticated user's password.
     */
    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        $user = Auth::user();
        $validated = $request->validated();

        // Update password
        $user->password = Hash::make($validated['password']);
        $user->save();

        // Revoke all tokens to force re-login
        $user->tokens()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Password changed successfully. Please login again with your new password.',
        ]);
    }

    /**
     * Get user statistics and summary.
     */
    public function stats(): JsonResponse
    {
        $user = Auth::user();
        
        $stats = [
            'total_bookings' => $user->serviceRequests()->count(),
            'completed_bookings' => $user->serviceRequests()->where('status', 'completed')->count(),
            'pending_bookings' => $user->serviceRequests()->where('status', 'pending')->count(),
            'total_locations' => $user->locations()->count(),
            'total_ratings' => $user->ratings()->count(),
            'average_rating' => $user->ratings()->avg('rating') ?? 0,
            'total_spent' => $user->serviceRequests()->where('status', 'completed')->sum('total_price'),
            'loyalty_points' => $user->loyaltyPoints()->sum('points'),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }
}
