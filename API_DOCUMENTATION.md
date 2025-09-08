# API Documentation - Service Booking Platform

## Overview
This document provides comprehensive API documentation for the Service Booking Platform backend. All endpoints return JSON responses and use standard HTTP status codes.

**Base URL**: `https://your-domain.com/api`  
**API Version**: v1  
**Rate Limiting**: 60 requests per minute per user  
**Authentication**: Bearer token (Laravel Sanctum)

---

## 🔐 Authentication

### Authentication Flow
1. User registers/logs in via `/register` or `/login`
2. Backend returns a Bearer token
3. Include token in `Authorization` header for subsequent requests
4. Token expires when user logs out or token is invalid

### Headers
```http
Authorization: Bearer {token}
Content-Type: application/json
Accept: application/json
```

---

## 📱 User Management

### User Registration
**POST** `/register`

Creates a new user account.

#### Request Body
```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "phone": "+1234567890",
  "password": "password123"
}
```

#### Response (201 Created)
```json
{
  "message": "User registered successfully",
  "token": "1|abc123...",
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "phone": "+1234567890",
    "role": "customer",
    "wallet_balance": "0.00",
    "created_at": "2024-01-01T00:00:00.000000Z"
  }
}
```

#### Validation Rules
- `name`: Required, max 255 characters
- `email`: Optional, must be valid email, unique
- `phone`: Required, unique, max 20 characters
- `password`: Required, min 6 characters

---

### User Login
**POST** `/login`

Authenticates user and returns access token.

#### Request Body
```json
{
  "phone": "+1234567890",
  "password": "password123"
}
```

#### Response (200 OK)
```json
{
  "message": "Login successful",
  "token": "1|abc123...",
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "phone": "+1234567890",
    "role": "customer",
    "wallet_balance": "50.00"
  }
}
```

#### Error Response (422 Unprocessable Entity)
```json
{
  "message": "The provided credentials are incorrect.",
  "errors": {
    "credentials": ["The provided credentials are incorrect."]
  }
}
```

---

### User Logout
**POST** `/logout`

Revokes the current user's access token.

#### Response (200 OK)
```json
{
  "message": "Logged out successfully"
}
```

---

## 👤 Profile Management

### Get User Profile
**GET** `/profile`

Returns the authenticated user's profile information.

#### Response (200 OK)
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "phone": "+1234567890",
    "role": "customer",
    "wallet_balance": "50.00",
    "created_at": "2024-01-01T00:00:00.000000Z",
    "updated_at": "2024-01-01T00:00:00.000000Z",
    "service_requests_count": 5,
    "locations_count": 2,
    "ratings_count": 3,
    "loyalty_points_total": 150
  }
}
```

---

### Update User Profile
**PUT** `/profile`

Updates the authenticated user's profile information.

#### Request Body
```json
{
  "name": "John Smith",
  "email": "johnsmith@example.com",
  "phone": "+1234567890"
}
```

#### Response (200 OK)
```json
{
  "success": true,
  "message": "Profile updated successfully.",
  "data": {
    "id": 1,
    "name": "John Smith",
    "email": "johnsmith@example.com",
    "phone": "+1234567890",
    "role": "customer",
    "wallet_balance": "50.00"
  }
}
```

#### Validation Rules
- `name`: Optional, max 255 characters
- `email`: Optional, valid email, unique
- `phone`: Optional, unique, max 20 characters

---

### Change Password
**PUT** `/profile/password`

Changes the user's password.

#### Request Body
```json
{
  "current_password": "oldpassword123",
  "password": "newpassword123",
  "password_confirmation": "newpassword123"
}
```

#### Response (200 OK)
```json
{
  "success": true,
  "message": "Password changed successfully. Please login again with your new password."
}
```

#### Validation Rules
- `current_password`: Required, must match current password
- `password`: Required, min 8 characters, confirmed
- `password_confirmation`: Required, must match password

---

### Get User Statistics
**GET** `/profile/stats`

Returns user statistics and summary.

#### Response (200 OK)
```json
{
  "success": true,
  "data": {
    "total_bookings": 5,
    "completed_bookings": 3,
    "pending_bookings": 1,
    "total_locations": 2,
    "total_ratings": 3,
    "average_rating": 4.5,
    "total_spent": "150.00",
    "loyalty_points": 150
  }
}
```

---

## 🛠️ Services

### List All Services
**GET** `/services`

Returns all available services.

#### Response (200 OK)
```json
[
  {
    "id": 1,
    "name": "House Cleaning",
    "description": "Complete house cleaning service",
    "category": "cleaning",
    "base_price": "50.00",
    "duration_minutes": 120,
    "average_rating": 4.5
  },
  {
    "id": 2,
    "name": "Plumbing Repair",
    "description": "Professional plumbing services",
    "category": "repair",
    "base_price": "80.00",
    "duration_minutes": 90,
    "average_rating": 4.8
  }
]
```

---

### Get Service Details
**GET** `/services/{id}`

Returns detailed information about a specific service.

#### Response (200 OK)
```json
{
  "id": 1,
  "name": "House Cleaning",
  "description": "Complete house cleaning service including dusting, vacuuming, and sanitizing.",
  "category": "cleaning",
  "base_price": "50.00",
  "duration_minutes": 120,
  "average_rating": 4.5
}
```

---

## 📍 Location Management

### List User Locations
**GET** `/locations`

Returns all locations for the authenticated user.

#### Response (200 OK)
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "user_id": 1,
      "address_text": "123 Main St, City, State 12345",
      "latitude": "40.7128",
      "longitude": "-74.0060",
      "created_at": "2024-01-01T00:00:00.000000Z"
    }
  ]
}
```

---

### Create New Location
**POST** `/locations`

Creates a new location for the authenticated user.

#### Request Body
```json
{
  "address_text": "456 Oak Ave, City, State 12345",
  "latitude": "40.7589",
  "longitude": "-73.9851"
}
```

#### Response (201 Created)
```json
{
  "success": true,
  "message": "Location created successfully",
  "data": {
    "id": 2,
    "user_id": 1,
    "address_text": "456 Oak Ave, City, State 12345",
    "latitude": "40.7589",
    "longitude": "-73.9851",
    "created_at": "2024-01-01T00:00:00.000000Z"
  }
}
```

#### Validation Rules
- `address_text`: Required, max 500 characters
- `latitude`: Required, numeric, between -90 and 90
- `longitude`: Required, numeric, between -180 and 180

---

### Get Location Details
**GET** `/locations/{id}`

Returns details of a specific location.

#### Response (200 OK)
```json
{
  "success": true,
  "data": {
    "id": 1,
    "user_id": 1,
    "address_text": "123 Main St, City, State 12345",
    "latitude": "40.7128",
    "longitude": "-74.0060",
    "created_at": "2024-01-01T00:00:00.000000Z"
  }
}
```

---

### Update Location
**PUT** `/locations/{id}`

Updates an existing location.

#### Request Body
```json
{
  "address_text": "123 Main St, City, State 12345",
  "latitude": "40.7128",
  "longitude": "-74.0060"
}
```

#### Response (200 OK)
```json
{
  "success": true,
  "message": "Location updated successfully",
  "data": {
    "id": 1,
    "user_id": 1,
    "address_text": "123 Main St, City, State 12345",
    "latitude": "40.7128",
    "longitude": "-74.0060"
  }
}
```

---

### Delete Location
**DELETE** `/locations/{id}`

Deletes a location.

#### Response (200 OK)
```json
{
  "success": true,
  "message": "Location deleted successfully"
}
```

---

## 📅 Service Requests (Bookings)

### List User Bookings
**GET** `/bookings`

Returns all service requests for the authenticated user.

#### Response (200 OK)
```json
[
  {
    "id": 1,
    "service": {
      "id": 1,
      "name": "House Cleaning",
      "description": "Complete house cleaning service",
      "category": "cleaning",
      "base_price": "50.00",
      "duration_minutes": 120,
      "average_rating": 4.5
    },
    "scheduled_at": "2024-01-15T10:00:00.000000Z",
    "status": "pending",
    "total_price": "50.00",
    "special_instructions": "Please clean the kitchen thoroughly",
    "location": {
      "id": 1,
      "address_text": "123 Main St, City, State 12345",
      "latitude": "40.7128",
      "longitude": "-74.0060"
    }
  }
]
```

---

### Get Booking Details
**GET** `/bookings/{id}`

Returns details of a specific service request.

#### Response (200 OK)
```json
{
  "id": 1,
  "service": {
    "id": 1,
    "name": "House Cleaning",
    "description": "Complete house cleaning service",
    "category": "cleaning",
    "base_price": "50.00",
    "duration_minutes": 120,
    "average_rating": 4.5
  },
  "scheduled_at": "2024-01-15T10:00:00.000000Z",
  "status": "pending",
  "total_price": "50.00",
  "special_instructions": "Please clean the kitchen thoroughly",
  "location": {
    "id": 1,
    "address_text": "123 Main St, City, State 12345",
    "latitude": "40.7128",
    "longitude": "-74.0060"
  }
}
```

---

### Create New Booking
**POST** `/bookings`

Creates a new service request.

#### Request Body
```json
{
  "service_id": 1,
  "location_id": 1,
  "scheduled_at": "2024-01-15T10:00:00.000000Z",
  "special_instructions": "Please clean the kitchen thoroughly"
}
```

#### Response (201 Created)
```json
{
  "message": "Service booked successfully.",
  "booking": {
    "id": 1,
    "service": {
      "id": 1,
      "name": "House Cleaning",
      "description": "Complete house cleaning service",
      "category": "cleaning",
      "base_price": "50.00",
      "duration_minutes": 120,
      "average_rating": 4.5
    },
    "scheduled_at": "2024-01-15T10:00:00.000000Z",
    "status": "pending",
    "total_price": "50.00",
    "special_instructions": "Please clean the kitchen thoroughly",
    "location": {
      "id": 1,
      "address_text": "123 Main St, City, State 12345",
      "latitude": "40.7128",
      "longitude": "-74.0060"
    }
  }
}
```

#### Validation Rules
- `service_id`: Required, must exist
- `location_id`: Required if not providing location text
- `location`: Optional text if not providing location_id
- `scheduled_at`: Required, must be in the future
- `special_instructions`: Optional, max 1000 characters

---

### Update Booking
**PUT** `/bookings/{id}`

Updates an existing service request.

#### Request Body
```json
{
  "scheduled_at": "2024-01-16T10:00:00.000000Z",
  "special_instructions": "Updated cleaning instructions"
}
```

#### Response (200 OK)
```json
{
  "message": "Booking updated successfully",
  "booking": {
    "id": 1,
    "service": {
      "id": 1,
      "name": "House Cleaning"
    },
    "scheduled_at": "2024-01-16T10:00:00.000000Z",
    "status": "pending",
    "total_price": "50.00",
    "special_instructions": "Updated cleaning instructions",
    "location": {
      "id": 1,
      "address_text": "123 Main St, City, State 12345"
    }
  }
}
```

---

### Update Booking Status
**PUT** `/bookings/{id}/status`

Updates the status of a service request.

#### Request Body
```json
{
  "status": "completed",
  "notes": "Service completed successfully"
}
```

#### Response (200 OK)
```json
{
  "message": "Booking status updated successfully",
  "booking": {
    "id": 1,
    "status": "completed",
    "notes": "Service completed successfully"
  }
}
```

#### Valid Status Values
- `pending`: Initial status
- `assigned`: Service provider assigned
- `in_progress`: Service in progress
- `completed`: Service completed
- `cancelled`: Booking cancelled

---

### Cancel Booking
**DELETE** `/bookings/{id}`

Cancels a service request.

#### Response (200 OK)
```json
{
  "message": "Booking cancelled successfully"
}
```

---

## 💰 Payment Management

### List User Payments
**GET** `/payments`

Returns payment history for the authenticated user.

#### Query Parameters
- `status`: Filter by payment status (pending, paid, failed)
- `method`: Filter by payment method (cash, wallet, third_party)
- `date_from`: Filter from date (YYYY-MM-DD)
- `date_to`: Filter to date (YYYY-MM-DD)
- `per_page`: Number of items per page (default: 15)

#### Response (200 OK)
```json
{
  "success": true,
  "data": {
    "payments": [
      {
        "id": 1,
        "invoice_number": "INV1234567890",
        "serviceRequest": {
          "id": 1,
          "service": {
            "id": 1,
            "name": "House Cleaning"
          }
        },
        "amount": "50.00",
        "method": "wallet",
        "status": "paid",
        "created_at": "2024-01-01T00:00:00.000000Z"
      }
    ],
    "pagination": {
      "current_page": 1,
      "last_page": 1,
      "per_page": 15,
      "total": 1
    }
  }
}
```

---

### Create Payment
**POST** `/payments`

Creates a new payment for a service request.

#### Request Body
```json
{
  "service_request_id": 1,
  "method": "wallet"
}
```

#### Response (200 OK)
```json
{
  "message": "Payment successful.",
  "data": {
    "invoice_number": "INV1234567890",
    "amount": "50.00",
    "method": "wallet",
    "wallet_balance": "0.00"
  }
}
```

#### Validation Rules
- `service_request_id`: Required, must exist and belong to user
- `method`: Required, must be one of: cash, wallet, third_party

---

### Get Payment Details
**GET** `/payments/{payment}`

Returns details of a specific payment.

#### Response (200 OK)
```json
{
  "success": true,
  "data": {
    "id": 1,
    "invoice_number": "INV1234567890",
    "serviceRequest": {
      "id": 1,
      "service": {
        "id": 1,
        "name": "House Cleaning"
      },
      "location": {
        "id": 1,
        "address_text": "123 Main St, City, State 12345"
      }
    },
    "amount": "50.00",
    "method": "wallet",
    "status": "paid",
    "created_at": "2024-01-01T00:00:00.000000Z"
  }
}
```

---

### Process Refund
**POST** `/payments/{payment}/refund`

Processes a refund for a payment.

#### Request Body
```json
{
  "reason": "Service not satisfactory",
  "refund_amount": "25.00"
}
```

#### Response (200 OK)
```json
{
  "message": "Refund processed successfully.",
  "data": {
    "refund_amount": "25.00",
    "refund_reason": "Service not satisfactory",
    "wallet_balance": "25.00"
  }
}
```

#### Validation Rules
- `reason`: Required, max 500 characters
- `refund_amount`: Optional, must not exceed payment amount

---

## ⭐ Ratings & Reviews

### Create Rating
**POST** `/ratings`

Creates a rating for a completed service.

#### Request Body
```json
{
  "service_request_id": 1,
  "rating": 5,
  "comment": "Excellent service, very professional!"
}
```

#### Response (200 OK)
```json
{
  "message": "Thank you for your feedback.",
  "rating": {
    "id": 1,
    "service_request_id": 1,
    "user_id": 1,
    "rating": 5,
    "comment": "Excellent service, very professional!",
    "created_at": "2024-01-01T00:00:00.000000Z"
  }
}
```

#### Validation Rules
- `service_request_id`: Required, must exist and belong to user
- `rating`: Required, integer between 1 and 5
- `comment`: Optional, string

---

## 🎫 Coupons

### Apply Coupon
**POST** `/coupons/apply`

Applies a coupon to a service request.

#### Request Body
```json
{
  "code": "SAVE20",
  "service_request_id": 1
}
```

#### Response (200 OK)
```json
{
  "message": "Coupon applied successfully.",
  "discount_amount": 10.00
}
```

#### Validation Rules
- `code`: Required, must be valid coupon code
- `service_request_id`: Required, must exist and belong to user

---

## 💳 Wallet Management

### Get Wallet Balance
**GET** `/wallet`

Returns the user's current wallet balance.

#### Response (200 OK)
```json
{
  "success": true,
  "data": {
    "balance": "50.00",
    "currency": "USD",
    "formatted_balance": "$50.00"
  }
}
```

---

### Top Up Wallet
**POST** `/wallet/topup`

Adds money to the user's wallet.

#### Request Body
```json
{
  "amount": "100.00",
  "payment_method": "card",
  "reference": "TOPUP001",
  "description": "Monthly top-up"
}
```

#### Response (201 Created)
```json
{
  "success": true,
  "message": "Wallet topped up successfully.",
  "data": {
    "transaction_id": 1,
    "amount": "100.00",
    "new_balance": "150.00",
    "formatted_new_balance": "$150.00"
  }
}
```

#### Validation Rules
- `amount`: Required, numeric, min 1.00, max 10000.00
- `payment_method`: Required, must be: card, bank_transfer, cash
- `reference`: Optional, max 255 characters
- `description`: Optional, max 500 characters

---

### Get Transaction History
**GET** `/wallet/transactions`

Returns wallet transaction history.

#### Query Parameters
- `type`: Filter by transaction type (topup, payment, refund, bonus, deduction)
- `status`: Filter by transaction status (completed, pending, failed)
- `date_from`: Filter from date (YYYY-MM-DD)
- `date_to`: Filter to date (YYYY-MM-DD)
- `per_page`: Number of items per page (default: 15)

#### Response (200 OK)
```json
{
  "success": true,
  "data": {
    "transactions": [
      {
        "id": 1,
        "type": "topup",
        "amount": "100.00",
        "balance_before": "50.00",
        "balance_after": "150.00",
        "description": "Monthly top-up",
        "reference": "TOPUP001",
        "status": "completed",
        "created_at": "2024-01-01T00:00:00.000000Z"
      }
    ],
    "pagination": {
      "current_page": 1,
      "last_page": 1,
      "per_page": 15,
      "total": 1
    }
  }
}
```

---

### Get Wallet Statistics
**GET** `/wallet/stats`

Returns wallet statistics and summary.

#### Response (200 OK)
```json
{
  "success": true,
  "data": {
    "total_topups": "200.00",
    "total_payments": "150.00",
    "total_refunds": "25.00",
    "total_bonuses": "10.00",
    "transaction_count": 5,
    "last_transaction": {
      "id": 1,
      "type": "topup",
      "amount": "100.00",
      "created_at": "2024-01-01T00:00:00.000000Z"
    }
  }
}
```

---

## 🎁 Loyalty System

### Get Loyalty Points
**GET** `/loyalty/points`

Returns the user's loyalty points information.

#### Response (200 OK)
```json
{
  "success": true,
  "data": {
    "total_points": 150,
    "formatted_points": "150 points",
    "recent_activity": [
      {
        "id": 1,
        "points": "+50",
        "source": "House Cleaning",
        "created_at": "Jan 01, 2024"
      }
    ]
  }
}
```

---

### Get Available Rewards
**GET** `/loyalty/rewards`

Returns available loyalty rewards.

#### Query Parameters
- `type`: Filter by reward type (discount, free_service, upgrade, cashback)
- `min_points`: Filter by minimum points required
- `max_points`: Filter by maximum points required
- `per_page`: Number of items per page (default: 15)

#### Response (200 OK)
```json
{
  "success": true,
  "data": {
    "rewards": [
      {
        "id": 1,
        "name": "20% Service Discount",
        "description": "Get 20% off your next service",
        "type": "discount",
        "points_required": 100,
        "value": "20",
        "code": "DISC20",
        "is_active": true,
        "can_afford": true,
        "is_available": true
      }
    ],
    "pagination": {
      "current_page": 1,
      "last_page": 1,
      "per_page": 15,
      "total": 1
    },
    "user_points": 150
  }
}
```

---

### Redeem Loyalty Reward
**POST** `/loyalty/redeem`

Redeems a loyalty reward.

#### Request Body
```json
{
  "loyalty_reward_id": 1
}
```

#### Response (201 Created)
```json
{
  "success": true,
  "message": "Reward redeemed successfully!",
  "data": {
    "redemption_id": 1,
    "reward_name": "20% Service Discount",
    "points_spent": 100,
    "remaining_points": 50,
    "reward_details": {
      "type": "Discount",
      "value": "20%",
      "code": "DISC20",
      "expires_at": "Never expires"
    }
  }
}
```

#### Validation Rules
- `loyalty_reward_id`: Required, must exist and be available

---

### Get Redemption History
**GET** `/loyalty/redemptions`

Returns loyalty reward redemption history.

#### Query Parameters
- `status`: Filter by redemption status (active, used, expired)
- `date_from`: Filter from date (YYYY-MM-DD)
- `date_to`: Filter to date (YYYY-MM-DD)
- `per_page`: Number of items per page (default: 15)

#### Response (200 OK)
```json
{
  "success": true,
  "data": {
    "redemptions": [
      {
        "id": 1,
        "reward": {
          "id": 1,
          "name": "20% Service Discount"
        },
        "points_spent": 100,
        "status": "active",
        "expires_at": null,
        "created_at": "2024-01-01T00:00:00.000000Z"
      }
    ],
    "pagination": {
      "current_page": 1,
      "last_page": 1,
      "per_page": 15,
      "total": 1
    }
  }
}
```

---

### Get Loyalty Statistics
**GET** `/loyalty/stats`

Returns loyalty system statistics.

#### Response (200 OK)
```json
{
  "success": true,
  "data": {
    "total_points_earned": 200,
    "total_points_spent": 100,
    "current_balance": 100,
    "total_rewards_redeemed": 2,
    "active_rewards": 1,
    "used_rewards": 1,
    "expired_rewards": 0
  }
}
```

---

## 🔒 Error Handling

### Standard Error Response Format
```json
{
  "success": false,
  "message": "Error description",
  "errors": {
    "field_name": ["Specific error message"]
  }
}
```

### Common HTTP Status Codes
- **200 OK**: Request successful
- **201 Created**: Resource created successfully
- **400 Bad Request**: Invalid request data
- **401 Unauthorized**: Authentication required
- **403 Forbidden**: Access denied
- **404 Not Found**: Resource not found
- **422 Unprocessable Entity**: Validation failed
- **429 Too Many Requests**: Rate limit exceeded
- **500 Internal Server Error**: Server error

### Validation Error Example
```json
{
  "success": false,
  "message": "The given data was invalid.",
  "errors": {
    "email": ["The email field must be a valid email address."],
    "password": ["The password field must be at least 8 characters."]
  }
}
```

---

## 📱 Push Notifications

### Notification Types
- **Booking Confirmations**: When bookings are created/confirmed
- **Status Updates**: When booking status changes
- **Payment Confirmations**: When payments are processed
- **Reminder Notifications**: Before scheduled services
- **Promotional Notifications**: Special offers and deals

### Notification Channels
- **Email**: HTML and text emails
- **SMS**: Text message notifications
- **Push**: Mobile push notifications
- **In-App**: In-application notifications

---

## 🔧 Development Tips

### Best Practices
1. **Always handle errors gracefully** - Check for error responses and display user-friendly messages
2. **Implement proper loading states** - Show loading indicators during API calls
3. **Cache responses when appropriate** - Reduce unnecessary API calls
4. **Validate data on frontend** - Implement client-side validation before API calls
5. **Handle token expiration** - Implement automatic logout when token expires
6. **Use proper HTTP methods** - GET for retrieving, POST for creating, PUT for updating, DELETE for removing

### Rate Limiting
- Implement exponential backoff for failed requests
- Show user-friendly messages when rate limit is exceeded
- Cache responses to reduce API calls

### Offline Support
- Cache essential data for offline viewing
- Queue actions for when connection is restored
- Show offline indicators to users

---

## 📚 Additional Resources

### Testing
- Use Postman or Insomnia for API testing
- Test all endpoints with various data scenarios
- Verify error handling and edge cases

### Documentation
- Keep this documentation updated as APIs evolve
- Document any custom headers or authentication methods
- Provide examples for complex request/response scenarios

---

**Last Updated**: {{ date('Y-m-d') }}  
**API Version**: v1  
**Backend Version**: Laravel 10.x  

---

*For technical support or API questions, contact the backend development team.*


