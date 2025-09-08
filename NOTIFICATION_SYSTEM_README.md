# Notification System Documentation

## Overview

The notification system has been successfully implemented using Firebase Cloud Messaging (FCM) to send push notifications to mobile devices. The system is integrated throughout the application to notify users about various events.

## Features

### 1. FCM Token Management
- Users can register their FCM tokens for receiving notifications
- Token validation and cleanup for invalid tokens
- Token status checking

### 2. Notification Types
- **Service Request Updates**: Notifications when service request status changes
- **Payment Notifications**: Notifications for successful payments and wallet top-ups
- **Loyalty Points**: Notifications for points earned or spent
- **Coupon Notifications**: Notifications when coupons are applied
- **Custom Notifications**: Admin can send custom notifications

### 3. Admin Features
- Send notifications to all users
- Send notifications by user role (customer/admin)
- Send notifications to specific users
- Bulk notification management

## API Endpoints

### User Endpoints (Requires Authentication)

#### Update FCM Token
```
POST /api/notifications/fcm-token
Content-Type: application/json
Authorization: Bearer {token}

{
    "fcm_token": "your_fcm_token_here"
}
```

#### Clear FCM Token
```
DELETE /api/notifications/fcm-token
Authorization: Bearer {token}
```

#### Get FCM Token Status
```
GET /api/notifications/fcm-token/status
Authorization: Bearer {token}
```

#### Send Test Notification
```
POST /api/notifications/test
Authorization: Bearer {token}
```

### Admin Endpoints (Requires Admin Authentication)

#### Send to All Users
```
POST /api/admin/notifications/send-all
Content-Type: application/json
Authorization: Bearer {admin_token}

{
    "title": "Notification Title",
    "body": "Notification Body",
    "data": {
        "custom_key": "custom_value"
    }
}
```

#### Send by Role
```
POST /api/admin/notifications/send-by-role
Content-Type: application/json
Authorization: Bearer {admin_token}

{
    "role": "customer",
    "title": "Notification Title",
    "body": "Notification Body",
    "data": {
        "custom_key": "custom_value"
    }
}
```

#### Send to Specific Users
```
POST /api/admin/notifications/send-specific
Content-Type: application/json
Authorization: Bearer {admin_token}

{
    "user_ids": [1, 2, 3],
    "title": "Notification Title",
    "body": "Notification Body",
    "data": {
        "custom_key": "custom_value"
    }
}
```

## Integration Points

### 1. Service Requests
- **Booking Confirmation**: When a service is booked
- **Status Updates**: When service request status changes (assigned, in_progress, completed, cancelled)
- **Cancellation**: When a service request is cancelled

### 2. Payments
- **Payment Success**: When payment is completed successfully
- **Wallet Top-up**: When wallet is topped up

### 3. Loyalty System
- **Points Deduction**: When loyalty points are spent on rewards

### 4. Coupons
- **Coupon Applied**: When a coupon is successfully applied

## Configuration

### Firebase Setup
1. The service account JSON file is located at `service_account.json`
2. The FCM service is registered as a singleton in the service container
3. Service provider is registered in `config/app.php`

### Database Changes
- Added `fcm_token` column to the `users` table
- Migration: `2025_09_08_054322_add_fcm_token_to_users_table.php`

## Usage Examples

### Sending a Custom Notification
```php
use App\Services\FcmService;

$fcmService = app(FcmService::class);

// Send to a single user
$fcmService->sendToUser($user, 'Title', 'Body', ['type' => 'custom']);

// Send to multiple users
$fcmService->sendToUsers($users, 'Title', 'Body', ['type' => 'custom']);

// Send to all users
$fcmService->sendToAllUsers('Title', 'Body', ['type' => 'custom']);
```

### Service-Specific Notifications
```php
// Service request update
$fcmService->sendServiceRequestUpdate($user, 'completed', 'Plumbing Service');

// Payment notification
$fcmService->sendPaymentNotification($user, 50.00, 'completed');

// Loyalty points notification
$fcmService->sendLoyaltyPointsNotification($user, 100, 'Service completed');

// Coupon notification
$fcmService->sendCouponNotification($user, 'SAVE20', '20% off your next service');
```

## Error Handling

- Invalid FCM tokens are automatically detected and removed
- Failed notifications are logged for debugging
- Graceful fallback when FCM service is unavailable
- User-friendly error messages for API responses

## Security

- All notification endpoints require authentication
- Admin endpoints require admin role verification
- FCM tokens are validated before sending notifications
- Sensitive data is not included in notification payloads

## Testing

You can test the notification system using the test endpoint:

```bash
curl -X POST http://your-domain/api/notifications/test \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json"
```

## Troubleshooting

### Common Issues

1. **Invalid FCM Token**: The system automatically detects and removes invalid tokens
2. **No Notifications Received**: Check if the user has a valid FCM token registered
3. **Firebase Connection Issues**: Verify the service account JSON file is correct
4. **Permission Issues**: Ensure the Firebase project has FCM enabled

### Logs

Check the Laravel logs for FCM-related messages:
- Successful notifications: `FCM notification sent to user {id}`
- Failed notifications: `Failed to send FCM notification to user {id}: {error}`
- Token cleanup: `Cleared invalid FCM token for user {id}`

## Future Enhancements

- Notification preferences per user
- Scheduled notifications
- Rich media notifications
- Notification history tracking
- Push notification analytics
