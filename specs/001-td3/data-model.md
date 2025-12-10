# Data Model: TD3 - Advanced Features

**Feature**: TD3 - Advanced Features
**Date**: 2025-12-10
**Status**: Complete

## Overview

This document defines the data model changes and entities for TD3. The model extends existing User and Publication entities with premium features and validation rules.

## Entity Changes

### User Entity

**File**: `src/Entity/User.php`

**New Fields**:

| Field | Type | Default | Nullable | Description |
|-------|------|---------|----------|-------------|
| premium | boolean | false | No | Premium user status |

**Existing Fields** (reference):
- id (int, PK)
- email (string, unique)
- password (string, hashed)
- roles (json, array)
- publications (OneToMany → Publication)

**Validation Rules**:
- `premium` field has no validation constraints
- Default value ensures all existing users are non-premium

**Business Rules**:
- Premium status determines publication content length limits
- Premium users have golden styling in UI
- Premium status can be granted via Stripe payment or CLI command
- ROLE_ADMIN is separate from premium status (can exist independently)

**Methods to Add**:
```php
public function isPremium(): bool
{
    return $this->premium;
}

public function setPremium(bool $premium): self
{
    $this->premium = $premium;
    return $this;
}
```

---

### Publication Entity

**File**: `src/Entity/Publication.php`

**Modified Fields**:

| Field | Type | Validation Groups | Constraints | Description |
|-------|------|------------------|-------------|-------------|
| content | string | Default | NotBlank | Publication text content |
| content | string | non_premium | Length(max=50) | Max 50 chars for non-premium |
| content | string | premium | Length(max=200) | Max 200 chars for premium |

**Existing Fields** (reference):
- id (int, PK)
- content (string)
- auteur (ManyToOne → User)
- datePublication (datetime)

**Validation Rules**:
- `Default` group: NotBlank applied to all users
- `non_premium` group: Length(max=50) for non-premium users
- `premium` group: Length(max=200) for premium users
- Groups determined dynamically in PublicationType form

**Business Rules**:
- Content length varies based on author's premium status
- Author relationship used for authorization (deletion voter)
- Delete button only visible to author in Twig templates

**No New Methods Required** - Existing getters/setters sufficient

---

## Relationships

### User ↔ Publication

**Type**: OneToMany (User) ↔ ManyToOne (Publication)

**Relationship Details**:
- One User can have many Publications
- Each Publication belongs to exactly one User (auteur)
- Cascade: No cascade delete (per TD3 spec)
- Orphan removal: No (publications remain if user deleted)

**Authorization Impact**:
- Relationship used in PublicationVoter to check authorship
- `$publication->getAuteur() === $user` determines delete permission

---

## Database Migration

**File**: `migrations/VersionYYYYMMDDHHMMSS.php` (generated)

**SQL Changes**:
```sql
ALTER TABLE user ADD premium TINYINT(1) DEFAULT 0 NOT NULL;
```

**Migration Commands**:
```bash
docker exec -it thefeed-backend bash
php bin/console make:entity User
# Add premium field via interactive prompt
php bin/console make:migration
php bin/console doctrine:migrations:migrate
```

**Rollback Strategy**:
```sql
ALTER TABLE user DROP premium;
```

**Data Impact**:
- All existing users will have `premium = false` by default
- No data loss
- No manual data updates required

---

## Validation Groups Flow

### Form Submission Flow

1. **User submits publication form**
2. **PublicationType::configureOptions()** determines validation groups:
   - Gets current user from Security component
   - Checks `$user->isPremium()`
   - Returns `['Default', 'premium']` if premium
   - Returns `['Default', 'non_premium']` if not premium
3. **Symfony validator** applies constraints for selected groups:
   - NotBlank (Default group) always applied
   - Length(max=50) if non_premium group
   - Length(max=200) if premium group
4. **Form validation** passes or fails based on content length
5. **Entity persisted** only if validation passes

### Example Scenarios

**Scenario 1: Non-premium user, 30 chars**
- Groups: ['Default', 'non_premium']
- NotBlank: ✓ Pass
- Length(max=50): ✓ Pass (30 < 50)
- Result: ✓ **Valid**

**Scenario 2: Non-premium user, 75 chars**
- Groups: ['Default', 'non_premium']
- NotBlank: ✓ Pass
- Length(max=50): ✗ Fail (75 > 50)
- Result: ✗ **Invalid** - "Content too long (75 chars, max 50)"

**Scenario 3: Premium user, 150 chars**
- Groups: ['Default', 'premium']
- NotBlank: ✓ Pass
- Length(max=200): ✓ Pass (150 < 200)
- Result: ✓ **Valid**

**Scenario 4: Premium user, 250 chars**
- Groups: ['Default', 'premium']
- NotBlank: ✓ Pass
- Length(max=200): ✗ Fail (250 > 200)
- Result: ✗ **Invalid** - "Content too long (250 chars, max 200)"

---

## State Transitions

### Premium Status Transitions

```
[Non-Premium User] --[Stripe Payment Success]--> [Premium User]
[Non-Premium User] --[CLI: give:premium]-------> [Premium User]
[Premium User]     --[CLI: revoke:premium]-----> [Non-Premium User]
```

**Transition Rules**:
1. **Stripe Payment → Premium**:
   - Triggered by webhook after successful payment
   - User.premium set to true
   - Immediate effect (next publication can be longer)

2. **CLI Grant → Premium**:
   - Admin executes `app:give:premium <user-identifier>`
   - User.premium set to true
   - Immediate effect

3. **CLI Revoke → Non-Premium**:
   - Admin executes `app:revoke:premium <user-identifier>`
   - User.premium set to false
   - **Existing long publications remain** (no retroactive deletion)
   - **New publications** limited to 50 chars

### Role Transitions

```
[ROLE_USER] --[CLI: promote:admin]--> [ROLE_ADMIN + ROLE_USER]
[ROLE_ADMIN] --[CLI: revoke:admin]--> [ROLE_USER]
```

**Note**: Premium and Admin are independent:
- Admin user can be non-premium
- Premium user can be non-admin
- Both can coexist on same user

---

## Indexes and Performance

**No new indexes required for TD3.**

**Existing Indexes** (sufficient):
- `user.id` (Primary Key) - used for delete authorization
- `publication.id` (Primary Key) - used for AJAX delete
- `publication.auteur_id` (Foreign Key) - used for voter authorship check

**Query Patterns**:
- Voter: `SELECT auteur_id FROM publication WHERE id = ?` (indexed)
- CLI commands: `SELECT * FROM user WHERE email = ? OR id = ?` (email indexed)
- Publications feed: `SELECT * FROM publication ORDER BY datePublication DESC` (existing index)

---

## Entity Diagram

```
┌─────────────────────────┐
│        User             │
│─────────────────────────│
│ id (PK)                 │
│ email (unique)          │
│ password                │
│ roles (json)            │
│ premium (bool) ★NEW★    │
└─────────────────────────┘
         │ 1
         │
         │ auteur
         │
         │ *
┌─────────────────────────┐
│     Publication         │
│─────────────────────────│
│ id (PK)                 │
│ content                 │
│ datePublication         │
│ auteur_id (FK)          │
└─────────────────────────┘

Validation Groups on Publication.content:
├─ Default: NotBlank
├─ non_premium: Length(max=50)
└─ premium: Length(max=200)

User Roles:
├─ ROLE_USER (default)
└─ ROLE_ADMIN (inherits ROLE_USER)
```

---

## Summary

**Entities Modified**: 2 (User, Publication)
**New Fields**: 1 (User.premium)
**Validation Groups**: 2 (non_premium, premium)
**Migrations**: 1 (add premium field)
**Relationships**: No changes (existing User ↔ Publication used)
**Indexes**: No changes (existing indexes sufficient)

All data model changes align with TD3 requirements and constitution.
