<?php

namespace App\Services;

use App\Models\User;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Kreait\Firebase\Messaging\AndroidConfig;
use Kreait\Firebase\Messaging\ApnsConfig;
use Kreait\Firebase\Exception\MessagingException;
use Illuminate\Support\Facades\Log;

class FcmService
{
    private $messaging;

    public function __construct()
    {
        $factory = (new Factory)
            ->withServiceAccount(base_path('service_account.json'));

        $this->messaging = $factory->createMessaging();
    }

    /**
     * Send notification to a single user
     */
    public function sendToUser(User $user, string $title, string $body, array $data = []): bool
    {
        if (!$user->hasFcmToken()) {
            Log::warning("User {$user->id} does not have FCM token");
            return false;
        }

        try {
            $message = CloudMessage::withTarget('token', $user->fcm_token)
                ->withNotification(Notification::create($title, $body))
                ->withData($data)
                ->withAndroidConfig(AndroidConfig::fromArray([
                    'priority' => 'high',
                    'notification' => [
                        'sound' => 'default',
                        'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                    ],
                ]))
                ->withApnsConfig(ApnsConfig::fromArray([
                    'payload' => [
                        'aps' => [
                            'sound' => 'default',
                            'badge' => 1,
                        ],
                    ],
                ]));

            $this->messaging->send($message);
            
            Log::info("FCM notification sent to user {$user->id}");
            return true;
        } catch (MessagingException $e) {
            Log::error("Failed to send FCM notification to user {$user->id}: " . $e->getMessage());
            
            // If token is invalid, clear it from the user
            if (str_contains($e->getMessage(), 'invalid-registration-token') || 
                str_contains($e->getMessage(), 'registration-token-not-registered')) {
                $user->clearFcmToken();
                Log::info("Cleared invalid FCM token for user {$user->id}");
            }
            
            return false;
        }
    }

    /**
     * Send notification to multiple users
     */
    public function sendToUsers(array $users, string $title, string $body, array $data = []): array
    {
        $results = [];
        $validTokens = [];
        $userTokens = [];

        // Filter users with valid FCM tokens
        foreach ($users as $user) {
            if ($user->hasFcmToken()) {
                $validTokens[] = $user->fcm_token;
                $userTokens[$user->fcm_token] = $user;
            }
        }

        if (empty($validTokens)) {
            Log::warning("No users with valid FCM tokens found");
            return $results;
        }

        try {
            $message = CloudMessage::withTarget('token', $validTokens)
                ->withNotification(Notification::create($title, $body))
                ->withData($data)
                ->withAndroidConfig(AndroidConfig::fromArray([
                    'priority' => 'high',
                    'notification' => [
                        'sound' => 'default',
                        'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                    ],
                ]))
                ->withApnsConfig(ApnsConfig::fromArray([
                    'payload' => [
                        'aps' => [
                            'sound' => 'default',
                            'badge' => 1,
                        ],
                    ],
                ]));

            $response = $this->messaging->sendMulticast($message);
            
            // Process results
            foreach ($response->getItems() as $index => $item) {
                $token = $validTokens[$index];
                $user = $userTokens[$token];
                
                if ($item->isSuccess()) {
                    $results[] = [
                        'user_id' => $user->id,
                        'success' => true,
                        'message' => 'Notification sent successfully'
                    ];
                } else {
                    $results[] = [
                        'user_id' => $user->id,
                        'success' => false,
                        'message' => $item->getError()->getMessage()
                    ];
                    
                    // Clear invalid tokens
                    if (str_contains($item->getError()->getMessage(), 'invalid-registration-token') || 
                        str_contains($item->getError()->getMessage(), 'registration-token-not-registered')) {
                        $user->clearFcmToken();
                        Log::info("Cleared invalid FCM token for user {$user->id}");
                    }
                }
            }
            
            Log::info("FCM multicast notification sent to " . count($validTokens) . " users");
            return $results;
        } catch (MessagingException $e) {
            Log::error("Failed to send FCM multicast notification: " . $e->getMessage());
            return $results;
        }
    }

    /**
     * Send notification to all users with FCM tokens
     */
    public function sendToAllUsers(string $title, string $body, array $data = []): array
    {
        $users = User::whereNotNull('fcm_token')->get();
        return $this->sendToUsers($users->toArray(), $title, $body, $data);
    }

    /**
     * Send notification to users by role
     */
    public function sendToUsersByRole(string $role, string $title, string $body, array $data = []): array
    {
        $users = User::where('role', $role)
            ->whereNotNull('fcm_token')
            ->get();
        
        return $this->sendToUsers($users->toArray(), $title, $body, $data);
    }

    /**
     * Send service request status update notification
     */
    public function sendServiceRequestUpdate(User $user, string $status, string $serviceName): bool
    {
        $title = "Service Request Update";
        $body = "Your service request for '{$serviceName}' has been {$status}";
        
        $data = [
            'type' => 'service_request_update',
            'status' => $status,
            'service_name' => $serviceName,
        ];

        return $this->sendToUser($user, $title, $body, $data);
    }

    /**
     * Send payment notification
     */
    public function sendPaymentNotification(User $user, float $amount, string $type): bool
    {
        $title = "Payment {$type}";
        $body = "Payment of $" . number_format($amount, 2) . " has been {$type}";
        
        $data = [
            'type' => 'payment',
            'payment_type' => $type,
            'amount' => (string) $amount,
        ];

        return $this->sendToUser($user, $title, $body, $data);
    }

    /**
     * Send loyalty points notification
     */
    public function sendLoyaltyPointsNotification(User $user, int $points, string $reason): bool
    {
        $title = "Loyalty Points " . ($points > 0 ? 'Earned' : 'Deducted');
        $body = "You have " . ($points > 0 ? 'earned' : 'lost') . " " . abs($points) . " loyalty points. Reason: {$reason}";
        
        $data = [
            'type' => 'loyalty_points',
            'points' => (string) $points,
            'reason' => $reason,
        ];

        return $this->sendToUser($user, $title, $body, $data);
    }

    /**
     * Send coupon notification
     */
    public function sendCouponNotification(User $user, string $couponCode, string $description): bool
    {
        $title = "New Coupon Available";
        $body = "You have a new coupon: {$couponCode}. {$description}";
        
        $data = [
            'type' => 'coupon',
            'coupon_code' => $couponCode,
            'description' => $description,
        ];

        return $this->sendToUser($user, $title, $body, $data);
    }
}
