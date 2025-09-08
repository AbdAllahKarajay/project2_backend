# Analytics Dashboard Documentation

## Overview

The Analytics Dashboard provides comprehensive insights and reporting capabilities for both users and administrators. It offers detailed analytics on revenue, customer behavior, service usage, loyalty programs, and more.

## Features

### 🎯 **User Analytics**
- Personal spending analytics
- Booking history and trends
- Loyalty points tracking
- Wallet transaction history
- Service preferences analysis
- Spending trends over time

### 📊 **Admin Analytics**
- Dashboard overview with key metrics
- Revenue analytics and growth tracking
- Service usage statistics
- Customer analytics and retention
- Loyalty program performance
- Coupon usage analytics
- Comprehensive reporting
- Data export capabilities

## API Endpoints

### User Analytics Endpoints

#### Get Personal Analytics
```
GET /api/analytics/personal?period=month
Authorization: Bearer {token}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "total_spent": 1250.50,
        "total_bookings": 15,
        "completed_bookings": 12,
        "average_booking_value": 83.37,
        "total_loyalty_points": 250,
        "points_earned": 50,
        "wallet_topups": 500.00,
        "wallet_spent": 300.00,
        "ratings_given": 8,
        "average_rating_given": 4.5
    },
    "period": "month"
}
```

#### Get Booking History
```
GET /api/analytics/bookings?period=month
Authorization: Bearer {token}
```

#### Get Loyalty Analytics
```
GET /api/analytics/loyalty?period=month
Authorization: Bearer {token}
```

#### Get Wallet Analytics
```
GET /api/analytics/wallet?period=month
Authorization: Bearer {token}
```

#### Get Spending Trends
```
GET /api/analytics/spending-trends?period=month
Authorization: Bearer {token}
```

#### Get Service Preferences
```
GET /api/analytics/service-preferences?period=month
Authorization: Bearer {token}
```

### Admin Analytics Endpoints

#### Get Dashboard Overview
```
GET /api/admin/analytics/dashboard
Authorization: Bearer {admin_token}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "overview": {
            "total_users": 1250,
            "total_services": 25,
            "total_bookings": 3500,
            "total_revenue": 125000.50,
            "active_users": 850,
            "average_rating": 4.3
        },
        "bookings": {
            "completed": 3200,
            "pending": 150,
            "in_progress": 100,
            "cancelled": 50
        },
        "completion_rate": 91.43
    }
}
```

#### Get Revenue Analytics
```
GET /api/admin/analytics/revenue?period=month
Authorization: Bearer {admin_token}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "total_revenue": 25000.00,
        "total_transactions": 150,
        "average_transaction_value": 166.67,
        "revenue_growth": 15.5,
        "revenue_data": [
            {
                "period": "2024-01",
                "revenue": 12000.00,
                "transactions": 75
            }
        ],
        "revenue_by_method": [
            {
                "method": "wallet",
                "revenue": 15000.00,
                "count": 100
            }
        ]
    },
    "period": "month"
}
```

#### Get Service Usage Analytics
```
GET /api/admin/analytics/services?period=month
Authorization: Bearer {admin_token}
```

#### Get Customer Analytics
```
GET /api/admin/analytics/customers?period=month
Authorization: Bearer {admin_token}
```

#### Get Loyalty Analytics
```
GET /api/admin/analytics/loyalty?period=month
Authorization: Bearer {admin_token}
```

#### Get Coupon Analytics
```
GET /api/admin/analytics/coupons?period=month
Authorization: Bearer {admin_token}
```

#### Get Comprehensive Report
```
GET /api/admin/analytics/comprehensive?period=month
Authorization: Bearer {admin_token}
```

#### Export Analytics Data
```
POST /api/admin/analytics/export
Authorization: Bearer {admin_token}
Content-Type: application/json

{
    "format": "json",
    "period": "month"
}
```

## Analytics Metrics

### 📈 **Revenue Metrics**
- Total revenue
- Revenue growth rate
- Average transaction value
- Revenue by payment method
- Monthly/Weekly/Daily revenue trends
- Revenue per service

### 👥 **Customer Metrics**
- Total customers
- New customer acquisition
- Active customers
- Customer retention rate
- Customer lifetime value (CLV)
- Top customers by spending
- Customer acquisition cost

### 🛠️ **Service Metrics**
- Most popular services
- Service completion rates
- Average service ratings
- Service revenue contribution
- Service booking trends
- Service performance metrics

### 🎁 **Loyalty Program Metrics**
- Total points distributed
- Total points redeemed
- Active loyalty members
- Points distribution by source
- Top loyalty users
- Loyalty program effectiveness

### 🎫 **Coupon Metrics**
- Coupon usage statistics
- Total discount given
- Most effective coupons
- Coupon redemption rates
- Discount impact on revenue

### 💰 **Wallet Metrics**
- Total wallet transactions
- Wallet top-up amounts
- Wallet spending patterns
- Average wallet balance
- Wallet transaction trends

## Period Options

All analytics endpoints support the following period options:
- `day` - Last 24 hours
- `week` - Last 7 days
- `month` - Last 30 days
- `year` - Last 365 days

## Usage Examples

### Get User's Monthly Spending Analytics
```bash
curl -X GET "https://your-api.com/api/analytics/personal?period=month" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### Get Admin Revenue Report
```bash
curl -X GET "https://your-api.com/api/admin/analytics/revenue?period=month" \
  -H "Authorization: Bearer ADMIN_TOKEN"
```

### Export Analytics Data
```bash
curl -X POST "https://your-api.com/api/admin/analytics/export" \
  -H "Authorization: Bearer ADMIN_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"format": "json", "period": "month"}'
```

## Data Structure

### User Analytics Data
```json
{
    "total_spent": "Total amount spent by user",
    "total_bookings": "Total number of bookings",
    "completed_bookings": "Number of completed bookings",
    "average_booking_value": "Average value per booking",
    "total_loyalty_points": "Current loyalty points balance",
    "points_earned": "Points earned in period",
    "wallet_topups": "Total wallet top-ups",
    "wallet_spent": "Total wallet spending",
    "ratings_given": "Number of ratings given",
    "average_rating_given": "Average rating given by user"
}
```

### Admin Dashboard Data
```json
{
    "overview": {
        "total_users": "Total registered users",
        "total_services": "Total available services",
        "total_bookings": "Total bookings made",
        "total_revenue": "Total revenue generated",
        "active_users": "Users active in period",
        "average_rating": "Average service rating"
    },
    "bookings": {
        "completed": "Completed bookings",
        "pending": "Pending bookings",
        "in_progress": "In-progress bookings",
        "cancelled": "Cancelled bookings"
    },
    "completion_rate": "Percentage of completed bookings"
}
```

## Performance Considerations

### Database Optimization
- Analytics queries are optimized for performance
- Proper indexing on frequently queried columns
- Efficient aggregation queries
- Caching for frequently accessed data

### Caching Strategy
- Dashboard overview data is cached for 5 minutes
- Revenue analytics are cached for 10 minutes
- User-specific analytics are cached for 2 minutes
- Cache is automatically invalidated when data changes

### Query Optimization
- Uses database aggregation functions
- Minimizes N+1 query problems
- Efficient date range filtering
- Optimized JOIN operations

## Security

### Access Control
- User analytics require user authentication
- Admin analytics require admin role verification
- All endpoints validate user permissions
- Sensitive data is properly protected

### Data Privacy
- User-specific data is only accessible to the user
- Admin analytics aggregate data without exposing individual user details
- Personal information is anonymized in reports
- GDPR compliance considerations

## Error Handling

### Common Error Responses
```json
{
    "success": false,
    "message": "Error description",
    "errors": {
        "field": ["Validation error message"]
    }
}
```

### Error Codes
- `401` - Unauthorized (missing or invalid token)
- `403` - Forbidden (insufficient permissions)
- `422` - Validation error
- `500` - Internal server error

## Future Enhancements

### Planned Features
- Real-time analytics dashboard
- Custom date range analytics
- Advanced filtering options
- Data visualization charts
- Automated report generation
- Email report scheduling
- Advanced customer segmentation
- Predictive analytics
- A/B testing analytics

### Integration Possibilities
- Google Analytics integration
- Business intelligence tools
- Data warehouse integration
- Machine learning insights
- Third-party reporting tools

## Troubleshooting

### Common Issues

1. **Slow Analytics Loading**
   - Check database performance
   - Verify proper indexing
   - Consider data caching

2. **Missing Data**
   - Verify date range parameters
   - Check data integrity
   - Ensure proper permissions

3. **Permission Errors**
   - Verify user authentication
   - Check admin role assignment
   - Validate API token

### Debug Mode
Enable debug mode in your `.env` file:
```
ANALYTICS_DEBUG=true
```

This will provide detailed query information and performance metrics.

## Support

For technical support or feature requests, please contact the development team or create an issue in the project repository.

---

**Last Updated:** [Current Date]
**Version:** 1.0.0
**Status:** Production Ready
