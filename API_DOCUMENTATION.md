# Exchange API Documentation

## Overview
This API provides comprehensive functionality for a cryptocurrency exchange platform supporting both user and admin operations.

## Base URL
```
http://localhost:8000/api
```

## Authentication
The API uses Laravel Sanctum for authentication. Include the bearer token in the Authorization header:
```
Authorization: Bearer {token}
```

## Response Format
All API responses follow this structure:
```json
{
    "success": true|false,
    "message": "Response message",
    "data": {}, // Response data
    "errors": {} // Validation errors (if any)
}
```

---

## Authentication Endpoints

### Register User
- **POST** `/auth/register`
- **Description**: Register a new user account
- **Body**:
```json
{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "password123",
    "password_confirmation": "password123",
    "phone": "09123456789",
    "national_id": "1234567890",
    "bank_account": "123-456-789",
    "bank_name": "Bank Name"
}
```
- **Response**:
```json
{
    "success": true,
    "message": "User registered successfully",
    "data": {
        "user": {
            "id": 1,
            "name": "John Doe",
            "email": "john@example.com",
            "role": "user",
            "is_active": true
        },
        "token": "1|abc123...",
        "token_type": "Bearer"
    }
}
```

### Login
- **POST** `/auth/login`
- **Body**:
```json
{
    "email": "john@example.com",
    "password": "password123"
}
```

### Get Profile
- **GET** `/auth/profile`
- **Headers**: `Authorization: Bearer {token}`

### Update Profile
- **PUT** `/auth/profile`
- **Headers**: `Authorization: Bearer {token}`
- **Body**:
```json
{
    "name": "Updated Name",
    "phone": "09987654321",
    "bank_account": "987-654-321",
    "bank_name": "New Bank"
}
```

### Change Password
- **PUT** `/auth/change-password`
- **Headers**: `Authorization: Bearer {token}`
- **Body**:
```json
{
    "current_password": "oldpassword",
    "new_password": "newpassword123",
    "new_password_confirmation": "newpassword123"
}
```

### Logout
- **POST** `/auth/logout`
- **Headers**: `Authorization: Bearer {token}`

### Logout All Devices
- **POST** `/auth/logout-all`
- **Headers**: `Authorization: Bearer {token}`

---

## Admin Authentication

### Admin Login
- **POST** `/admin/auth/login`
- **Description**: Login endpoint specifically for admin users
- **Body**:
```json
{
    "email": "admin@exchange.com",
    "password": "password"
}
```
- **Response**:
```json
{
    "success": true,
    "message": "Admin login successful",
    "data": {
        "user": {
            "id": 1,
            "name": "Admin User",
            "email": "admin@exchange.com",
            "role": "admin",
            "is_active": true
        },
        "token": "1|abc123...",
        "token_type": "Bearer"
    }
}
```
- **Error Response (403 - Not Admin)**:
```json
{
    "success": false,
    "message": "Access denied. Admin privileges required."
}
```

---

## Currency Endpoints

### Get All Currencies
- **GET** `/currencies`
- **Query Parameters**:
  - `active`: Filter by active status (1/0)
  - `tradeable`: Filter by tradeable status (1/0)
  - `search`: Search by symbol or name
- **Response**:
```json
{
    "success": true,
    "data": {
        "data": [
            {
                "id": 1,
                "symbol": "BTC",
                "name": "Bitcoin",
                "description": "Bitcoin - The first cryptocurrency",
                "buy_price": "4350000000.00000000",
                "sell_price": "4320000000.00000000",
                "buy_commission": "0.50",
                "sell_commission": "0.50",
                "is_active": true,
                "is_tradeable": true
            }
        ]
    }
}
```

### Get Trading Currencies
- **GET** `/currencies/trading`
- **Description**: Get only active and tradeable currencies

### Get Currency Details
- **GET** `/currencies/{id}`

---

## Wallet Endpoints

### Get User Wallets
- **GET** `/wallet`
- **Headers**: `Authorization: Bearer {token}`
- **Response**:
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "currency_id": 2,
            "balance": "0.00100000",
            "frozen_balance": "0.00000000",
            "currency": {
                "id": 2,
                "symbol": "BTC",
                "name": "Bitcoin"
            }
        }
    ]
}
```

### Get Portfolio Summary
- **GET** `/wallet/portfolio`
- **Headers**: `Authorization: Bearer {token}`

### Get Specific Wallet
- **GET** `/wallet/{currencyId}`
- **Headers**: `Authorization: Bearer {token}`

### Get Wallet Transactions
- **GET** `/wallet/{currencyId}/transactions`
- **Headers**: `Authorization: Bearer {token}`

### Deposit Cryptocurrency
- **POST** `/wallet/deposit`
- **Headers**: `Authorization: Bearer {token}`
- **Body**:
```json
{
    "currency_id": 2,
    "amount": "0.001",
    "tx_hash": "abc123...",
    "from_address": "1BvBMSE..."
}
```

### Withdraw Cryptocurrency
- **POST** `/wallet/withdraw`
- **Headers**: `Authorization: Bearer {token}`
- **Body**:
```json
{
    "currency_id": 2,
    "amount": "0.001",
    "to_address": "1BvBMSE..."
}
```

---

## Order Endpoints

### Get User Orders
- **GET** `/orders`
- **Headers**: `Authorization: Bearer {token}`
- **Query Parameters**:
  - `type`: Filter by order type (buy/sell/exchange)
  - `status`: Filter by status

### Get Price Quote
- **POST** `/orders/quote`
- **Headers**: `Authorization: Bearer {token}`
- **Body**:
```json
{
    "type": "buy",
    "to_currency_id": 2,
    "amount": "0.001",
    "discount_code": "SAVE10"
}
```

### Create Buy Order
- **POST** `/orders/buy`
- **Headers**: `Authorization: Bearer {token}`
- **Body**:
```json
{
    "currency_id": 2,
    "amount": "0.001",
    "discount_code": "SAVE10"
}
```

### Create Sell Order
- **POST** `/orders/sell`
- **Headers**: `Authorization: Bearer {token}`
- **Body**:
```json
{
    "currency_id": 2,
    "amount": "0.001",
    "discount_code": "SAVE10"
}
```

### Exchange Currencies
- **POST** `/orders/exchange`
- **Headers**: `Authorization: Bearer {token}`
- **Body**:
```json
{
    "from_currency_id": 2,
    "to_currency_id": 3,
    "amount": "0.001"
}
```

### Get Order Details
- **GET** `/orders/{id}`
- **Headers**: `Authorization: Bearer {token}`

### Cancel Order
- **PUT** `/orders/{id}/cancel`
- **Headers**: `Authorization: Bearer {token}`
- **Body**:
```json
{
    "reason": "Changed my mind"
}
```

---

## Transaction Endpoints

### Get User Transactions
- **GET** `/transactions`
- **Headers**: `Authorization: Bearer {token}`
- **Query Parameters**:
  - `type`: Filter by transaction type
  - `status`: Filter by status
  - `currency_id`: Filter by currency
  - `from_date`: Start date filter
  - `to_date`: End date filter

### Get Transaction Details
- **GET** `/transactions/{id}`
- **Headers**: `Authorization: Bearer {token}`

---

## Discount Endpoints

### Get Available Discounts
- **GET** `/discounts`
- **Headers**: `Authorization: Bearer {token}`
- **Query Parameters**:
  - `currency_id`: Filter by currency (optional)
  - `per_page`: Number of items per page

### Get Discount Details
- **GET** `/discounts/{id}`
- **Headers**: `Authorization: Bearer {token}`

### Validate Discount Code
- **POST** `/discounts/validate`
- **Headers**: `Authorization: Bearer {token}`
- **Body**:
```json
{
    "code": "SAVE10",
    "amount": 1000000,
    "currency_id": 2
}
```

### Get My Discount Usage History
- **GET** `/discounts/my-usage`
- **Headers**: `Authorization: Bearer {token}`
- **Query Parameters**:
  - `per_page`: Number of items per page

---

## Admin Endpoints
**Note**: All admin endpoints require admin role and `Authorization: Bearer {admin_token}`

### Dashboard
- **GET** `/admin/dashboard`
- **Response**: Overview statistics, revenue data, recent activities

### User Management

#### Get All Users
- **GET** `/admin/users`
- **Query Parameters**:
  - `search`: Search by name or email
  - `active`: Filter by active status
  - `role`: Filter by role

#### Update User Status
- **PUT** `/admin/users/{id}/status`
- **Body**:
```json
{
    "is_active": true
}
```

#### Get User Activity
- **GET** `/admin/users/{id}/activity`

### Currency Management

#### Create Currency
- **POST** `/admin/currencies`
- **Body** (multipart/form-data):
```json
{
    "symbol": "XRP",
    "name": "Ripple",
    "description": "XRP - Digital payment protocol",
    "buy_price": "35000",
    "sell_price": "34500",
    "buy_commission": "0.3",
    "sell_commission": "0.3",
    "treasury_balance": "10000",
    "decimal_places": "6",
    "image": "file"
}
```

#### Update Currency
- **PUT** `/admin/currencies/{id}`

#### Delete Currency
- **DELETE** `/admin/currencies/{id}`

#### Update Treasury Balance
- **PUT** `/admin/currencies/{id}/treasury`
- **Body**:
```json
{
    "amount": "100",
    "operation": "add" // add, subtract, set
}
```

### Order Management

#### Get All Orders
- **GET** `/admin/orders`
- **Query Parameters**: Same as user orders plus user_id filter

#### Get Pending Orders
- **GET** `/admin/orders/pending`

#### Update Order Status
- **PUT** `/admin/orders/{id}/status`
- **Body**:
```json
{
    "status": "completed",
    "reason": "Manual processing"
}
```

### Transaction Management

#### Get All Transactions
- **GET** `/admin/transactions`

#### Get Pending Transactions
- **GET** `/admin/transactions/pending`

#### Update Transaction Status
- **PUT** `/admin/transactions/{id}/status`
- **Body**:
```json
{
    "status": "completed"
}
```

### Discount Management

#### Get All Discounts
- **GET** `/admin/discounts`
- **Query Parameters**:
  - `status`: Filter by status (active, inactive, expired)
  - `type`: Filter by type (percentage, fixed)
  - `search`: Search by code, title or description
  - `per_page`: Number of items per page

#### Create Discount
- **POST** `/admin/discounts`
- **Body**:
```json
{
    "code": "SAVE20",
    "title": "Save 20%",
    "description": "Limited time offer",
    "type": "percentage",
    "value": 20,
    "currency_id": null,
    "user_id": null,
    "min_order_amount": 1000000,
    "max_discount_amount": 500000,
    "usage_limit": 100,
    "user_usage_limit": 2,
    "starts_at": "2024-01-01 00:00:00",
    "expires_at": "2024-12-31 23:59:59",
    "is_active": true
}
```

#### Update Discount
- **PUT** `/admin/discounts/{id}`
- **Body**: Same as create (all fields optional)

#### Delete Discount
- **DELETE** `/admin/discounts/{id}`

### Exchange Rate Management

#### Create Exchange Rate
- **POST** `/admin/exchange-rates`
- **Body**:
```json
{
    "from_currency_id": 2,
    "to_currency_id": 3,
    "rate": 24.17,
    "buy_rate": 24.17,
    "sell_rate": 24.10,
    "is_active": true
}
```

#### Update Exchange Rate
- **PUT** `/admin/exchange-rates/{id}`
- **Body**: Same as create (all fields optional)

#### Delete Exchange Rate
- **DELETE** `/admin/exchange-rates/{id}`

### Reports

#### Trading Report
- **GET** `/admin/reports/trading`
- **Query Parameters**:
  - `period`: day, week, month, year

#### Revenue Report
- **GET** `/admin/reports/revenue`
- **Query Parameters**:
  - `period`: day, week, month, year

---

## Error Codes

| Code | Description |
|------|-------------|
| 200 | Success |
| 201 | Created |
| 400 | Bad Request |
| 401 | Unauthorized |
| 403 | Forbidden |
| 404 | Not Found |
| 422 | Validation Error |
| 500 | Internal Server Error |

## Rate Limiting
- 60 requests per minute for authenticated users
- 30 requests per minute for guest users

## Example Usage

### Buy Bitcoin with JavaScript
```javascript
const response = await fetch('/api/orders/buy', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'Authorization': 'Bearer ' + token
    },
    body: JSON.stringify({
        currency_id: 2,
        amount: 0.001
    })
});

const result = await response.json();
console.log(result);
```

### Get Portfolio with cURL
```bash
curl -X GET \
  http://localhost:8000/api/wallet/portfolio \
  -H 'Authorization: Bearer {token}' \
  -H 'Content-Type: application/json'
```

## Testing

### Test Coverage

The application includes comprehensive unit and feature tests covering all major functionality:

#### Feature Tests

1. **AuthTest** - Authentication and user management
   - User registration with validation
   - User login with credentials
   - Profile management and updates
   - Password change functionality
   - Token-based authentication

2. **CurrencyTest** - Currency operations
   - Currency listing (active and all)
   - Trading currencies endpoint
   - Currency details retrieval
   - Admin currency management

3. **OrderTest** - Order management
   - Buy/sell order creation
   - Order status tracking
   - Order cancellation
   - Order history and filtering

4. **WalletTest** - Wallet operations
   - Wallet listing and portfolio view
   - Cryptocurrency deposit/withdrawal
   - Balance management
   - Transaction validation

5. **TransactionTest** - Transaction tracking
   - Transaction history with filtering
   - Transaction status management
   - Transaction types (buy, sell, deposit, withdrawal)
   - Date range filtering

6. **AdminTest** - Administrative functions
   - User management and statistics
   - Order oversight and status updates
   - Exchange rate management
   - Discount code creation and management

#### Unit Tests

1. **WalletTest** - Wallet model functionality
   - Balance operations (add, subtract)
   - Sufficient balance validation
   - Model relationships

2. **TransactionTest** - Transaction model
   - Status management (completed, failed, pending)
   - Transaction type validation
   - Model scopes and filters

3. **DiscountTest** - Discount model
   - Discount validation and availability
   - Usage tracking and limits
   - Percentage and fixed amount calculations

### Running Tests

```bash
# Run all tests
php artisan test

# Run specific test suite
php artisan test --filter AuthTest
php artisan test --filter WalletTest

# Run with coverage (if configured)
php artisan test --coverage
```

### Test Database

Tests use SQLite in-memory database for fast execution and isolation. Database is automatically migrated and seeded for each test.
