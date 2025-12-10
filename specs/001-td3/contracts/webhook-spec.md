# Webhook Specification: Stripe Integration

**Feature**: TD3 - Advanced Features
**Date**: 2025-12-10
**Status**: Complete

## Overview

This document specifies the Stripe webhook integration for handling premium payment events. The webhook ensures reliable premium upgrades even if users close their browser after payment.

---

## Webhook Endpoint

**URL**: `POST /webhook/stripe`

**Purpose**: Receive and process Stripe payment events

**Public Endpoint**: Yes (secured by signature verification)

---

## Stripe Webhook Configuration

### Stripe Dashboard Setup

1. **Navigate to**: Stripe Dashboard → Developers → Webhooks
2. **Add endpoint**: `https://yourdomain.com/webhook/stripe`
3. **Events to subscribe**:
   - `checkout.session.completed`
4. **Webhook signing secret**: Copy the `whsec_...` secret
5. **Environment variable**: Store in `.env` as `STRIPE_WEBHOOK_SECRET`

### Local Development

**Option 1: Stripe CLI (Recommended)**
```bash
stripe listen --forward-to localhost/webhook/stripe
```

**Option 2: ngrok**
```bash
ngrok http 80
# Use ngrok URL in Stripe dashboard
```

---

## Request Specification

### Headers

**Required Headers**:
```
POST /webhook/stripe HTTP/1.1
Host: yourdomain.com
Content-Type: application/json
Stripe-Signature: t=1702390000,v1=abc123...,v2=def456...
User-Agent: Stripe/1.0
```

**Signature Header Format**:
```
Stripe-Signature: t=<timestamp>,v1=<signature_v1>[,v2=<signature_v2>]
```

### Request Body

**Event Structure**:
```json
{
    "id": "evt_1234567890abcdef",
    "object": "event",
    "api_version": "2023-10-16",
    "created": 1702390000,
    "type": "checkout.session.completed",
    "data": {
        "object": {
            "id": "cs_test_abc123...",
            "object": "checkout.session",
            "amount_total": 999,
            "currency": "eur",
            "customer": "cus_abc123",
            "payment_status": "paid",
            "metadata": {
                "userId": "42",
                "studentToken": "optional_token"
            },
            "mode": "payment",
            "status": "complete",
            "success_url": "https://yourdomain.com/premium/confirm?session_id={CHECKOUT_SESSION_ID}",
            "cancel_url": "https://yourdomain.com/premium/cancel"
        }
    },
    "livemode": false,
    "pending_webhooks": 1,
    "request": {
        "id": "req_abc123",
        "idempotency_key": null
    },
    "type": "checkout.session.completed"
}
```

**Key Fields**:
- `type`: Event type (must be `checkout.session.completed`)
- `data.object.payment_status`: Must be `"paid"`
- `data.object.metadata.userId`: User ID to upgrade
- `data.object.metadata.studentToken`: Optional student identifier

---

## Signature Verification

### Process

1. **Extract Components**:
   - Request body (raw, before parsing)
   - Stripe-Signature header
   - Webhook signing secret (from environment)

2. **Verify Signature**:
```php
use Stripe\Webhook;
use Stripe\Exception\SignatureVerificationException;

try {
    $event = Webhook::constructEvent(
        $payload,          // Raw request body
        $sigHeader,        // Stripe-Signature header
        $webhookSecret     // STRIPE_WEBHOOK_SECRET from .env
    );
} catch (SignatureVerificationException $e) {
    // Invalid signature
    return new JsonResponse(['error' => 'Invalid signature'], 400);
}
```

3. **Security Rules**:
   - MUST verify signature before processing
   - MUST use raw request body (not parsed JSON)
   - MUST reject invalid signatures immediately
   - MUST use endpoint-specific signing secret

### Signature Algorithm

**Stripe uses HMAC SHA-256**:
```
signed_payload = timestamp + "." + payload
expected_signature = HMAC_SHA256(signed_payload, webhook_secret)
```

**Verification Steps**:
1. Extract timestamp and signatures from header
2. Reconstruct signed payload
3. Compute expected signature
4. Compare with received signature (constant-time comparison)
5. Verify timestamp is within tolerance (5 minutes)

---

## Event Processing

### Supported Events

**Primary Event**: `checkout.session.completed`

**Event Conditions**:
```php
if ($event->type === 'checkout.session.completed') {
    $session = $event->data->object;

    if ($session->payment_status === 'paid') {
        // Process premium upgrade
    }
}
```

### Processing Logic

**Step-by-Step**:

1. **Verify Event Type**:
```php
if ($event->type !== 'checkout.session.completed') {
    return new JsonResponse(['status' => 'ignored'], 200);
}
```

2. **Extract Session**:
```php
$session = $event->data->object;
```

3. **Verify Payment Status**:
```php
if ($session->payment_status !== 'paid') {
    return new JsonResponse(['status' => 'not_paid'], 200);
}
```

4. **Extract User ID**:
```php
$userId = $session->metadata->userId ?? null;

if (!$userId) {
    return new JsonResponse(['error' => 'Missing userId'], 400);
}
```

5. **Find User**:
```php
$user = $userRepository->find($userId);

if (!$user) {
    return new JsonResponse(['error' => 'User not found'], 400);
}
```

6. **Upgrade to Premium**:
```php
$user->setPremium(true);
$entityManager->flush();
```

7. **Return Success**:
```php
return new JsonResponse(['status' => 'success'], 200);
```

---

## Response Specification

### Success Response

**HTTP Status**: 200 OK

**Content-Type**: application/json

**Body**:
```json
{
    "status": "success"
}
```

**Alternatives**:
```json
{
    "status": "ignored"
}
```
*(For events we don't process)*

---

### Error Responses

#### Invalid Signature

**HTTP Status**: 400 Bad Request

**Body**:
```json
{
    "error": "Invalid signature"
}
```

#### Missing User ID

**HTTP Status**: 400 Bad Request

**Body**:
```json
{
    "error": "Missing userId in metadata"
}
```

#### User Not Found

**HTTP Status**: 400 Bad Request

**Body**:
```json
{
    "error": "User not found"
}
```

#### Payment Not Completed

**HTTP Status**: 200 OK *(acknowledge but don't process)*

**Body**:
```json
{
    "status": "not_paid"
}
```

---

## Idempotency

### Handling Duplicate Events

**Stripe may send duplicate webhook events.**

**Strategy**:
```php
// Idempotent operation: setting premium=true multiple times is safe
$user->setPremium(true);  // No-op if already premium
$entityManager->flush();
```

**No explicit duplicate detection required** because:
- Setting `premium = true` when already true is safe
- No side effects (no emails, no notifications in TD3)
- Database transaction ensures consistency

**Optional Improvement** (out of TD3 scope):
- Store processed event IDs in database
- Check if event ID already processed
- Skip if duplicate

---

## Error Handling

### Stripe API Errors

**Scenario**: Stripe API unavailable during verification

**Handling**:
```php
try {
    $event = Webhook::constructEvent(...);
} catch (\Stripe\Exception\ApiErrorException $e) {
    // Log error
    error_log('Stripe API error: ' . $e->getMessage());

    // Return 500 to trigger retry
    return new JsonResponse(['error' => 'API error'], 500);
}
```

**Stripe Retry Policy**: Stripe will retry on 5xx errors

---

### Database Errors

**Scenario**: Database connection fails during flush

**Handling**:
```php
try {
    $entityManager->flush();
} catch (\Exception $e) {
    // Log error
    error_log('Database error: ' . $e->getMessage());

    // Return 500 to trigger retry
    return new JsonResponse(['error' => 'Database error'], 500);
}
```

---

### Validation Errors

**Scenario**: Invalid event structure

**Handling**:
```php
if (!isset($session->metadata->userId)) {
    // Log warning
    error_log('Webhook missing userId');

    // Return 400 (don't retry)
    return new JsonResponse(['error' => 'Missing userId'], 400);
}
```

---

## Testing

### Manual Testing with Stripe CLI

**Install Stripe CLI**:
```bash
brew install stripe/stripe-cli/stripe
```

**Login**:
```bash
stripe login
```

**Forward Events**:
```bash
stripe listen --forward-to localhost/webhook/stripe
```

**Trigger Test Event**:
```bash
stripe trigger checkout.session.completed
```

**Custom Test Event**:
```bash
stripe trigger checkout.session.completed \
    --add checkout_session:metadata.userId=42 \
    --add checkout_session:payment_status=paid
```

---

### Test Event Examples

**Successful Payment**:
```json
{
    "type": "checkout.session.completed",
    "data": {
        "object": {
            "payment_status": "paid",
            "metadata": {
                "userId": "1"
            }
        }
    }
}
```

**Expected**: User 1 upgraded to premium, 200 response

---

**Payment Incomplete**:
```json
{
    "type": "checkout.session.completed",
    "data": {
        "object": {
            "payment_status": "unpaid",
            "metadata": {
                "userId": "1"
            }
        }
    }
}
```

**Expected**: No upgrade, 200 response with "not_paid"

---

**Missing User ID**:
```json
{
    "type": "checkout.session.completed",
    "data": {
        "object": {
            "payment_status": "paid",
            "metadata": {}
        }
    }
}
```

**Expected**: 400 response with "Missing userId"

---

**Invalid Signature**:
```
POST /webhook/stripe
Stripe-Signature: t=1234567890,v1=invalid_signature_abc123
```

**Expected**: 400 response with "Invalid signature"

---

## Security Checklist

- [x] Verify Stripe signature on every request
- [x] Use raw request body for signature verification
- [x] Store webhook secret in environment variable
- [x] Return 400 for invalid signatures (don't process)
- [x] Validate event type before processing
- [x] Validate payment_status === 'paid'
- [x] Validate userId exists in metadata
- [x] Use HTTPS in production (not localhost)
- [x] Log all webhook events for audit trail
- [x] Handle idempotent operations (duplicate events)

---

## Monitoring and Logging

### Log Events

**What to Log**:
```php
// Successful processing
error_log("Webhook: User {$userId} upgraded to premium");

// Invalid signature
error_log("Webhook: Invalid signature from IP {$ip}");

// Missing data
error_log("Webhook: Event {$eventId} missing userId");

// Errors
error_log("Webhook: Error processing event {$eventId}: {$error}");
```

**Log Format**:
```
[2025-12-10 10:30:00] Webhook: User 42 upgraded to premium
[2025-12-10 10:31:00] Webhook: Invalid signature from IP 192.168.1.1
[2025-12-10 10:32:00] Webhook: Event evt_123 missing userId
```

---

### Stripe Dashboard Monitoring

**Check**:
- Webhook delivery status (Developers → Webhooks → View events)
- Failed webhook attempts
- Response codes and times

**Retry Policy**:
- Stripe retries failed webhooks (5xx errors) automatically
- Max 3 days of retries
- Exponential backoff

---

## Summary

**Webhook Endpoint**: `POST /webhook/stripe`
**Authentication**: Signature verification (HMAC SHA-256)
**Event Handled**: `checkout.session.completed`
**Condition**: `payment_status === 'paid'`
**Action**: Upgrade user to premium
**Idempotency**: Safe to process duplicates
**Error Handling**: Return 400 for invalid requests, 500 for retryable errors

**Security**: ✅ Signature verified before processing
**Reliability**: ✅ Idempotent operation, Stripe handles retries
**Monitoring**: ✅ All events logged

Ready for implementation per TD3 requirements.
