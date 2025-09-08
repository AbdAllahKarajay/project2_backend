<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\FcmService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class NotificationController extends Controller
{
    private FcmService $fcmService;

    public function __construct(FcmService $fcmService)
    {
        $this->fcmService = $fcmService;
    }

    /**
     * Update user's FCM token
     */
    public function updateFcmToken(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'fcm_token' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = Auth::user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated'
            ], 401);
        }

        $updated = $user->updateFcmToken($request->fcm_token);

        if ($updated) {
            return response()->json([
                'success' => true,
                'message' => 'FCM token updated successfully'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to update FCM token'
        ], 500);
    }

    /**
     * Clear user's FCM token
     */
    public function clearFcmToken(): JsonResponse
    {
        $user = Auth::user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated'
            ], 401);
        }

        $cleared = $user->clearFcmToken();

        if ($cleared) {
            return response()->json([
                'success' => true,
                'message' => 'FCM token cleared successfully'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to clear FCM token'
        ], 500);
    }

    /**
     * Get user's FCM token status
     */
    public function getFcmTokenStatus(): JsonResponse
    {
        $user = Auth::user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated'
            ], 401);
        }

        return response()->json([
            'success' => true,
            'has_fcm_token' => $user->hasFcmToken(),
            'fcm_token' => $user->hasFcmToken() ? substr($user->fcm_token, 0, 20) . '...' : null
        ]);
    }

    /**
     * Send test notification to authenticated user
     */
    public function sendTestNotification(): JsonResponse
    {
        $user = Auth::user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated'
            ], 401);
        }

        if (!$user->hasFcmToken()) {
            return response()->json([
                'success' => false,
                'message' => 'User does not have FCM token'
            ], 400);
        }

        $sent = $this->fcmService->sendToUser(
            $user,
            'Test Notification',
            'This is a test notification from the service app',
            ['type' => 'test']
        );

        if ($sent) {
            return response()->json([
                'success' => true,
                'message' => 'Test notification sent successfully'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to send test notification'
        ], 500);
    }

    /**
     * Send notification to all users (Admin only)
     */
    public function sendToAllUsers(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        if (!$user || !$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Admin access required.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'body' => 'required|string|max:1000',
            'data' => 'sometimes|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $results = $this->fcmService->sendToAllUsers(
            $request->title,
            $request->body,
            $request->data ?? []
        );

        return response()->json([
            'success' => true,
            'message' => 'Notification sent to all users',
            'results' => $results
        ]);
    }

    /**
     * Send notification to users by role (Admin only)
     */
    public function sendToUsersByRole(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        if (!$user || !$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Admin access required.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'role' => 'required|string|in:customer,admin',
            'title' => 'required|string|max:255',
            'body' => 'required|string|max:1000',
            'data' => 'sometimes|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $results = $this->fcmService->sendToUsersByRole(
            $request->role,
            $request->title,
            $request->body,
            $request->data ?? []
        );

        return response()->json([
            'success' => true,
            'message' => "Notification sent to {$request->role} users",
            'results' => $results
        ]);
    }

    /**
     * Send notification to specific users (Admin only)
     */
    public function sendToSpecificUsers(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        if (!$user || !$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Admin access required.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'integer|exists:users,id',
            'title' => 'required|string|max:255',
            'body' => 'required|string|max:1000',
            'data' => 'sometimes|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $users = User::whereIn('id', $request->user_ids)
            ->whereNotNull('fcm_token')
            ->get();

        if ($users->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No users found with FCM tokens'
            ], 404);
        }

        $results = $this->fcmService->sendToUsers(
            $users->toArray(),
            $request->title,
            $request->body,
            $request->data ?? []
        );

        return response()->json([
            'success' => true,
            'message' => 'Notification sent to specified users',
            'results' => $results
        ]);
    }
}