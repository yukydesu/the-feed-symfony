# API Routes Contract: TD3 - Advanced Features

**Feature**: TD3 - Advanced Features
**Date**: 2025-12-10
**Status**: Complete

## Overview

This document defines all HTTP routes/endpoints for TD3 features. All routes follow RESTful conventions and Symfony routing standards.

---

## 1. Publication Delete (AJAX)

### DELETE /publications/{id}

Deletes a publication via AJAX request. Returns JSON response with appropriate HTTP status codes.

**Route Name**: `publication_delete`

**Method**: DELETE

**URL Pattern**: `/publications/{id}`

**Route Options**:
```php
#[Route('/publications/{id}', name: 'publication_delete', methods: ['DELETE'], options: ['expose' => true])]
```

**Parameters**:
| Name | Type | Location | Required | Description |
|------|------|----------|----------|-------------|
| id | integer | Path | Yes | Publication ID to delete |

**Authorization**:
- User must be authenticated
- `#[IsGranted('PUBLICATION_DELETE', subject: 'publication')]`
- Voter checks: user is author OR has ROLE_ADMIN

**Request Headers**:
```
Accept: application/json
```

**Response Codes**:

| Code | Condition | Body |
|------|-----------|------|
| 204 No Content | Deletion successful | (empty) |
| 403 Forbidden | User not authorized (not author, not admin) | `{"error": "Forbidden"}` |
| 404 Not Found | Publication does not exist | `{"error": "Publication not found"}` |
| 401 Unauthorized | User not authenticated | Redirect to login |

**Response Headers**:
```
Content-Type: application/json
```

**Example Request**:
```javascript
fetch('/publications/42', {
    method: 'DELETE',
    headers: {'Accept': 'application/json'}
});
```

**Example Success Response** (204):
```
(empty body)
```

**Example Error Response** (403):
```json
{
    "error": "Forbidden"
}
```

**JavaScript Integration**:
```javascript
const url = Routing.generate('publication_delete', {id: publicationId});
const response = await fetch(url, {method: 'DELETE'});

if (response.status === 204) {
    // Remove from DOM
    element.remove();
} else if (response.status === 403) {
    alert('Non autorisé');
} else if (response.status === 404) {
    alert('Publication introuvable');
}
```

---

## 2. Premium Page

### GET /premium

Displays the premium subscription page with pricing and Stripe checkout button.

**Route Name**: `premium_index`

**Method**: GET

**URL Pattern**: `/premium`

**Route Annotation**:
```php
#[Route('/premium', name: 'premium_index')]
```

**Parameters**: None

**Authorization**:
- User must be authenticated: `#[IsGranted('ROLE_USER')]`
- No restriction on premium status (non-premium users see purchase option, premium users see status)

**Response Code**: 200 OK

**Response Content-Type**: text/html

**Template**: `templates/premium/index.html.twig`

**Template Variables**:
```php
[
    'price' => float,  // Premium price in EUR (e.g., 9.99)
    'user' => User,    // Current user object
]
```

**Template Content**:
- Premium benefits description
- Price display (from `premium_price` parameter)
- "Acheter Premium" button (if non-premium)
- Premium status badge (if already premium)

---

## 3. Stripe Checkout Creation

### POST /premium/checkout

Creates a Stripe Checkout session and redirects to Stripe payment page.

**Route Name**: `premium_checkout`

**Method**: POST

**URL Pattern**: `/premium/checkout`

**Route Annotation**:
```php
#[Route('/premium/checkout', name: 'premium_checkout', methods: ['POST'])]
```

**Parameters**: None (user ID from security context)

**Authorization**:
- User must be authenticated: `#[IsGranted('ROLE_USER')]`

**Stripe Session Configuration**:
```php
[
    'mode' => 'payment',
    'line_items' => [[
        'price_data' => [
            'currency' => 'eur',
            'product_data' => ['name' => 'Premium Membership'],
            'unit_amount' => $premiumPrice,  // in cents
        ],
        'quantity' => 1,
    ]],
    'metadata' => [
        'userId' => $user->getId(),
        // Optional: 'studentToken' => $user->getStudentToken()
    ],
    'success_url' => 'http://localhost/premium/confirm?session_id={CHECKOUT_SESSION_ID}',
    'cancel_url' => 'http://localhost/premium/cancel',
]
```

**Response**: Redirect (303) to Stripe Checkout URL

**Error Handling**:
- Stripe API errors: Flash message + redirect back to /premium
- User already premium: Flash message + redirect to /premium

---

## 4. Payment Confirmation

### GET /premium/confirm

Confirms successful payment and upgrades user to premium.

**Route Name**: `premium_confirm`

**Method**: GET

**URL Pattern**: `/premium/confirm`

**Route Annotation**:
```php
#[Route('/premium/confirm', name: 'premium_confirm')]
```

**Parameters**:
| Name | Type | Location | Required | Description |
|------|------|----------|----------|-------------|
| session_id | string | Query | Yes | Stripe Checkout Session ID |

**Authorization**:
- User must be authenticated: `#[IsGranted('ROLE_USER')]`

**Process**:
1. Retrieve Stripe Session by `session_id`
2. Verify `payment_status === 'paid'`
3. Extract `userId` from session metadata
4. Verify current user matches metadata userId
5. Set `user.premium = true`
6. Persist and flush

**Response Code**: 200 OK

**Response Content-Type**: text/html

**Template**: `templates/premium/confirm.html.twig`

**Template Variables**:
```php
[
    'user' => User,  // Updated user with premium=true
]
```

**Error Cases**:
- Session not paid: Flash error + redirect to /premium
- User ID mismatch: Flash error + redirect to /premium
- Session not found: Flash error + redirect to /premium

---

## 5. Payment Cancellation

### GET /premium/cancel

Handles user cancelling the Stripe checkout.

**Route Name**: `premium_cancel`

**Method**: GET

**URL Pattern**: `/premium/cancel`

**Route Annotation**:
```php
#[Route('/premium/cancel', name: 'premium_cancel')]
```

**Parameters**: None

**Authorization**:
- User must be authenticated: `#[IsGranted('ROLE_USER')]`

**Response Code**: 200 OK

**Response Content-Type**: text/html

**Template**: `templates/premium/cancel.html.twig`

**Template Variables**:
```php
[
    'user' => User,  // Current user (premium unchanged)
]
```

**Template Content**:
- Cancellation message
- Link back to /premium to retry
- No changes to user account

---

## 6. Stripe Webhook

### POST /webhook/stripe

Receives Stripe webhook events for payment processing.

**Route Name**: `webhook_stripe`

**Method**: POST

**URL Pattern**: `/webhook/stripe`

**Route Annotation**:
```php
#[Route('/webhook/stripe', name: 'webhook_stripe', methods: ['POST'])]
```

**Parameters**: None (payload in request body)

**Authorization**: None (public endpoint, secured by signature verification)

**Request Headers**:
```
Stripe-Signature: t=...,v1=...,v2=...
Content-Type: application/json
```

**Request Body** (example):
```json
{
    "id": "evt_...",
    "type": "checkout.session.completed",
    "data": {
        "object": {
            "id": "cs_...",
            "payment_status": "paid",
            "metadata": {
                "userId": "42"
            }
        }
    }
}
```

**Security**:
1. Retrieve raw request body
2. Get `Stripe-Signature` header
3. Verify signature using `\Stripe\Webhook::constructEvent()`
4. Only process if signature valid

**Event Handling**:
- **Event Type**: `checkout.session.completed`
- **Condition**: `payment_status === 'paid'`
- **Action**: Upgrade user to premium

**Process**:
1. Verify webhook signature
2. Parse event
3. Check event type === 'checkout.session.completed'
4. Extract session object
5. Verify payment_status === 'paid'
6. Extract userId from metadata
7. Find user by ID
8. Set `user.premium = true`
9. Persist and flush

**Response Codes**:
| Code | Condition | Body |
|------|-----------|------|
| 200 OK | Event processed successfully | `{"status": "success"}` |
| 400 Bad Request | Invalid signature or event | `{"error": "Invalid signature"}` |

**Response Headers**:
```
Content-Type: application/json
```

**Example Success Response** (200):
```json
{
    "status": "success"
}
```

**Example Error Response** (400):
```json
{
    "error": "Invalid signature"
}
```

**Security Notes**:
- Endpoint MUST verify signature before processing
- Reject events with invalid signatures immediately
- Log all webhook events for audit trail
- Idempotent processing (handle duplicate events gracefully)

---

## Route Summary Table

| Route Name | Method | URL Pattern | Auth Required | Purpose |
|-----------|--------|-------------|---------------|---------|
| publication_delete | DELETE | /publications/{id} | Yes (Voter) | AJAX publication deletion |
| premium_index | GET | /premium | Yes (ROLE_USER) | Premium subscription page |
| premium_checkout | POST | /premium/checkout | Yes (ROLE_USER) | Create Stripe session |
| premium_confirm | GET | /premium/confirm | Yes (ROLE_USER) | Confirm payment |
| premium_cancel | GET | /premium/cancel | Yes (ROLE_USER) | Handle cancellation |
| webhook_stripe | POST | /webhook/stripe | No (signature) | Stripe webhook handler |

---

## JavaScript Routing Exposure

**Routes Exposed to JavaScript**:
```php
// Only expose DELETE route for AJAX
options: ['expose' => true]
```

**Routes Exposed**:
- `publication_delete` (for AJAX calls)

**Routes NOT Exposed**:
- Premium routes (standard server-side navigation)
- Webhook (server-to-server only)

**JavaScript Router Files** (in base.html.twig):
```twig
<script src="{{ asset('bundles/fosjsrouting/js/router.min.js') }}"></script>
<script src="{{ path('fos_js_routing_js', {callback: 'fos.Router.setData'}) }}"></script>
```

---

## Error Handling Standards

### Client Errors (4xx)
- **401 Unauthorized**: Redirect to login page
- **403 Forbidden**: JSON response with error message
- **404 Not Found**: JSON response with error message

### Server Errors (5xx)
- **500 Internal Server Error**: Log error, return generic JSON error
- Flash messages for user-facing errors
- Console errors for AJAX failures

### Stripe Errors
- API errors: Catch exception, flash message, redirect
- Webhook errors: Return 400 with error JSON
- Log all Stripe errors for debugging

---

## CORS and Security Headers

**CSRF Protection**:
- DELETE route: CSRF token not required (stateless JSON API)
- POST routes: CSRF token required (Symfony form protection)

**Content Security Policy**:
- Allow Stripe.js domain for checkout
- Allow FOSJsRoutingBundle router scripts

**Rate Limiting**:
- Not implemented in TD3 (out of scope)

---

## Summary

**Total Routes**: 6
- 1 AJAX DELETE endpoint
- 4 Premium/Stripe web routes
- 1 Webhook endpoint

**JavaScript Exposed**: 1 route (publication_delete)
**Public Endpoints**: 1 (webhook_stripe with signature verification)
**Authenticated Endpoints**: 5 (all others)

All routes follow RESTful conventions and Symfony best practices.
