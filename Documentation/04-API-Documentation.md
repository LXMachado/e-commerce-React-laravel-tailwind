# API Documentation - Complete Reference

## Overview

This document provides a comprehensive reference for the Weekender E-commerce API. The API follows REST principles and uses JSON for all responses.

**Base URL**: `http://localhost:8000/api`
**Authentication**: Laravel Sanctum (Token-based)

## Authentication

### Register User
```http
POST /auth/register
Content-Type: application/json

{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "password123",
  "password_confirmation": "password123"
}
```

**Response** (201):
```json
{
  "success": true,
  "data": {
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com"
    },
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."
  },
  "message": "User registered successfully"
}
```

### Login User
```http
POST /auth/login
Content-Type: application/json

{
  "email": "john@example.com",
  "password": "password123"
}
```

**Response** (200):
```json
{
  "success": true,
  "data": {
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com"
    },
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."
  },
  "message": "Login successful"
}
```

### Logout User
```http
POST /auth/logout
Authorization: Bearer {token}
```

**Response** (200):
```json
{
  "success": true,
  "message": "Logged out successfully"
}
```

## Product Catalog API (Public)

### Get All Products
```http
GET /catalog/products
Query Parameters:
- page: Page number (default: 1)
- per_page: Items per page (default: 15, max: 100)
- category_id: Filter by category
- search: Search term for name, description, or SKU
- min_price: Minimum price filter
- max_price: Maximum price filter
- sort_by: Sort field (name, price, created_at)
- sort_direction: Sort direction (asc, desc)
- attributes: JSON object with attribute filters
  Example: {"1": [1, 2], "2": [5]} // attribute 1 with values 1,2 AND attribute 2 with value 5
```

**Response** (200):
```json
{
  "success": true,
  "data": {
    "data": [
      {
        "id": 1,
        "name": "Aurora Solar Backpack",
        "slug": "aurora-solar-backpack",
        "description": "Portable solar-powered backpack...",
        "price": 299.99,
        "compare_at_price": 399.99,
        "categories": [
          {
            "id": 1,
            "name": "Backpacks",
            "slug": "backpacks"
          }
        ],
        "primaryVariant": {
          "id": 1,
          "sku": "ASB-001",
          "price": 299.99,
          "stock_quantity": 25
        },
        "attributeValues": [
          {
            "id": 1,
            "value": "Black",
            "attribute": {
              "id": 1,
              "name": "Color"
            }
          }
        ]
      }
    ],
    "current_page": 1,
    "per_page": 15,
    "total": 150,
    "last_page": 10
  },
  "message": "Products retrieved successfully"
}
```

### Get Product by ID
```http
GET /catalog/products/{id}
```

**Response** (200):
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "Aurora Solar Backpack",
    "slug": "aurora-solar-backpack",
    "description": "Full product description...",
    "short_description": "Solar-powered backpack with USB charging",
    "price": 299.99,
    "compare_at_price": 399.99,
    "categories": [...],
    "variants": [
      {
        "id": 1,
        "sku": "ASB-001-BLK",
        "price": 299.99,
        "compare_at_price": 399.99,
        "stock_quantity": 25,
        "is_active": true
      }
    ],
    "attributeValues": [...]
  },
  "message": "Product retrieved successfully"
}
```

### Search Products
```http
GET /catalog/search?q={searchTerm}
Query Parameters:
- q: Search query (required)
- category_id: Optional category filter
- per_page: Results per page
```

**Response** (200):
```json
{
  "success": true,
  "data": {
    "products": [...],
    "suggestions": ["solar backpack", "portable solar"],
    "total": 42
  },
  "message": "Search completed successfully"
}
```

### Get Categories
```http
GET /catalog/categories
```

**Response** (200):
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Backpacks",
      "slug": "backpacks",
      "description": "Adventure backpacks"
    }
  ],
  "message": "Categories retrieved successfully"
}
```

## Shopping Cart API

### Get Cart
```http
GET /cart
```

**Response** (200):
```json
{
  "success": true,
  "data": {
    "id": 1,
    "session_id": "abc123",
    "user_id": null,
    "is_active": true,
    "items": [
      {
        "id": 1,
        "quantity": 2,
        "price_at_time": 299.99,
        "productVariant": {
          "id": 1,
          "sku": "ASB-001-BLK",
          "product": {
            "id": 1,
            "name": "Aurora Solar Backpack"
          }
        }
      }
    ],
    "subtotal": 599.98,
    "item_count": 2
  },
  "message": "Cart retrieved successfully"
}
```

### Add Item to Cart
```http
POST /cart/items
Content-Type: application/json

{
  "product_variant_id": 1,
  "quantity": 2
}
```

**Response** (201):
```json
{
  "success": true,
  "data": {
    "id": 1,
    "items": [...],
    "subtotal": 599.98,
    "item_count": 2
  },
  "message": "Item added to cart successfully"
}
```

### Update Cart Item
```http
PUT /cart/items/{itemId}
Content-Type: application/json

{
  "quantity": 3
}
```

**Response** (200):
```json
{
  "success": true,
  "data": {
    "id": 1,
    "items": [...],
    "subtotal": 899.97
  },
  "message": "Cart item updated successfully"
}
```

### Remove Cart Item
```http
DELETE /cart/items/{itemId}
```

**Response** (200):
```json
{
  "success": true,
  "data": {
    "id": 1,
    "items": [],
    "subtotal": 0
  },
  "message": "Item removed from cart successfully"
}
```

### Get Cart Totals
```http
GET /cart/totals
```

**Response** (200):
```json
{
  "success": true,
  "data": {
    "item_count": 2,
    "subtotal": 599.98,
    "tax_amount": 60.00,
    "shipping_amount": 15.00,
    "total_amount": 674.98,
    "currency": "USD"
  },
  "message": "Cart totals retrieved successfully"
}
```

## Checkout API

### Initiate Checkout
```http
POST /checkout/initiate
Content-Type: application/json

{
  "cart_id": 1,
  "success_url": "https://yoursite.com/success",
  "cancel_url": "https://yoursite.com/cancel"
}
```

**Response** (200):
```json
{
  "success": true,
  "data": {
    "payment_intent_id": "pi_1234567890",
    "client_secret": "pi_1234567890_secret_abcdef",
    "amount": 67498,
    "currency": "usd",
    "cart_id": 1,
    "item_count": 2,
    "subtotal": 599.98
  },
  "message": "Checkout initiated successfully"
}
```

### Process Payment
```http
POST /checkout/process
Content-Type: application/json

{
  "payment_intent_id": "pi_1234567890",
  "cart_id": 1,
  "shipping_postcode": "2000",
  "shipping_method_code": "express_post"
}
```

**Response** (200):
```json
{
  "success": true,
  "data": {
    "payment_intent_id": "pi_1234567890",
    "payment_status": "succeeded",
    "order_id": 123,
    "order_number": "ORD-20241109-ABC12345"
  },
  "message": "Payment processed successfully"
}
```

## Order Management API

### Get User Orders
```http
GET /orders
Query Parameters:
- page: Page number
- per_page: Items per page
- status: Filter by order status
```

**Response** (200):
```json
{
  "success": true,
  "data": {
    "data": [
      {
        "id": 123,
        "order_number": "ORD-20241109-ABC12345",
        "status": "paid",
        "subtotal": 599.98,
        "total_amount": 674.98,
        "created_at": "2024-11-09T10:00:00.000000Z"
      }
    ],
    "current_page": 1,
    "per_page": 15,
    "total": 5
  },
  "message": "Orders retrieved successfully"
}
```

### Get Order Details
```http
GET /orders/{orderId}
```

**Response** (200):
```json
{
  "success": true,
  "data": {
    "id": 123,
    "order_number": "ORD-20241109-ABC12345",
    "status": "paid",
    "subtotal": 599.98,
    "tax_amount": 60.00,
    "shipping_amount": 15.00,
    "total_amount": 674.98,
    "currency": "USD",
    "items": [
      {
        "id": 1,
        "quantity": 2,
        "price_at_time": 299.99,
        "line_total": 599.98,
        "productVariant": {
          "sku": "ASB-001-BLK",
          "product": {
            "name": "Aurora Solar Backpack"
          }
        }
      }
    ]
  },
  "message": "Order retrieved successfully"
}
```

## Shipping API

### Get Shipping Quote
```http
POST /shipping/quote
Content-Type: application/json

{
  "postcode": "2000",
  "weight": 2.5,
  "items": [
    {
      "product_id": 1,
      "quantity": 2,
      "weight": 1.25
    }
  ]
}
```

**Response** (200):
```json
{
  "success": true,
  "data": {
    "quotes": [
      {
        "method": {
          "code": "standard_post",
          "name": "Standard Post",
          "description": "3-5 business days"
        },
        "rate": {
          "price": 15.00,
          "currency": "AUD"
        },
        "delivery_time": "3-5 business days"
      }
    ]
  },
  "message": "Shipping quote calculated successfully"
}
```

### Validate Australian Address
```http
POST /shipping/validate-address
Content-Type: application/json

{
  "postcode": "2000",
  "suburb": "Sydney",
  "state": "NSW"
}
```

**Response** (200):
```json
{
  "success": true,
  "data": {
    "valid": true,
    "normalized": {
      "postcode": "2000",
      "suburb": "SYDNEY",
      "state": "NSW"
    }
  },
  "message": "Address validated successfully"
}
```

## Admin API (Protected)

### Create Product
```http
POST /admin/catalog/products
Authorization: Bearer {token}
Content-Type: application/json

{
  "name": "New Product",
  "slug": "new-product",
  "description": "Product description",
  "short_description": "Short description",
  "sku": "NP-001",
  "price": 199.99,
  "category_ids": [1, 2],
  "attribute_value_ids": [1, 3, 5],
  "is_active": true
}
```

### Update Order Status
```http
PUT /admin/orders/{orderId}/status
Authorization: Bearer {token}
Content-Type: application/json

{
  "status": "shipped",
  "tracking_number": "AU123456789"
}
```

**Response** (200):
```json
{
  "success": true,
  "data": {
    "id": 123,
    "status": "shipped",
    "shipping_status": "shipped"
  },
  "message": "Order status updated successfully"
}
```

## Error Responses

All API endpoints return consistent error responses:

### Validation Error (422)
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "email": ["The email field is required."],
    "password": ["The password must be at least 8 characters."]
  }
}
```

### Not Found (404)
```json
{
  "success": false,
  "message": "Product not found"
}
```

### Unauthorized (401)
```json
{
  "success": false,
  "message": "Unauthenticated"
}
```

### Server Error (500)
```json
{
  "success": false,
  "message": "Internal server error",
  "error": "Detailed error message for debugging"
}
```

## Rate Limiting

- **Public endpoints**: 100 requests per minute per IP
- **Authenticated endpoints**: 200 requests per minute per user
- **Admin endpoints**: 500 requests per minute per user

## Pagination

All list endpoints support pagination:

```json
{
  "data": [...],
  "current_page": 1,
  "per_page": 15,
  "total": 150,
  "last_page": 10,
  "from": 1,
  "to": 15
}
```

## Filtering and Sorting

### Product Filtering Example
```http
GET /catalog/products?category_id=1&min_price=100&max_price=500&sort_by=price&sort_direction=asc&attributes={"color":[1,2],"size":[3]}
```

### Response Headers
- `X-Total-Count`: Total number of records
- `X-Page-Current`: Current page number
- `X-Page-Per-Page`: Items per page
- `X-Page-Last`: Last page number

This comprehensive API documentation demonstrates the complete e-commerce functionality, from product browsing to order fulfillment, with proper error handling and security measures.