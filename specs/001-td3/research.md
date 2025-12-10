# Research: TD3 - Advanced Features

**Feature**: TD3 - Advanced Features
**Date**: 2025-12-10
**Status**: Complete

## Overview

This document contains research findings for implementing TD3 advanced features. All technical decisions are based on Symfony best practices, TD3 requirements, and the project constitution.

## Technology Research

### 1. FOSJsRoutingBundle

**Decision**: Use friendsofsymfony/jsrouting-bundle for JavaScript routing

**Rationale**:
- Official Symfony-compatible bundle for exposing routes to JavaScript
- Allows `Routing.generate('route_name', {params})` pattern matching Symfony's router
- Required for AJAX DELETE requests with dynamic publication IDs
- Widely used in Symfony ecosystem

**Installation**:
```bash
composer require friendsofsymfony/jsrouting-bundle
```

**Configuration**:
- Add bundle to `bundles.php` (auto-configured with Symfony Flex)
- Create `config/packages/fos_js_routing.yaml`
- Include router scripts in base template:
  - `/bundles/fosjsrouting/js/router.min.js`
  - Generated routing file via route

**Route Exposure**:
```php
#[Route('/publications/{id}', name: 'publication_delete', methods: ['DELETE'], options: ['expose' => true])]
```

**Alternatives Considered**:
- Manual URL construction in JavaScript - Rejected: error-prone, doesn't respect route changes
- Hardcoded URLs - Rejected: violates DRY, breaks on route changes

---

### 2. Symfony Security Voters

**Decision**: Implement PublicationVoter with `PUBLICATION_DELETE` attribute

**Rationale**:
- Constitutional requirement (Section IV - Sécurité et Permissions)
- Symfony best practice for complex authorization logic
- Centralizes logic: "user is author OR has ROLE_ADMIN"
- Allows use of `#[IsGranted]` annotations with subjects

**Implementation Pattern**:
```php
class PublicationVoter extends Voter
{
    const DELETE = 'PUBLICATION_DELETE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $attribute === self::DELETE && $subject instanceof Publication;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) return false;

        // Admin can delete anything
        if (in_array('ROLE_ADMIN', $user->getRoles())) return true;

        // Author can delete own publication
        return $subject->getAuteur() === $user;
    }
}
```

**Controller Usage**:
```php
#[IsGranted('PUBLICATION_DELETE', subject: 'publication')]
public function delete(Publication $publication): Response
```

**Alternatives Considered**:
- Manual checks in controller - Rejected: violates constitution, not reusable
- Security expressions only - Rejected: too complex for this logic

---

### 3. Stripe PHP SDK Integration

**Decision**: Use official stripe/stripe-php SDK

**Rationale**:
- Official Stripe SDK for PHP
- Supports Checkout Sessions API (payment mode)
- Built-in webhook signature verification
- Constitutional requirement (Section VI)

**Installation**:
```bash
composer require stripe/stripe-php
```

**Checkout Flow**:
1. Create Session with metadata (userId)
2. Redirect user to Stripe
3. Handle success/cancel redirects
4. Verify payment via webhook

**Webhook Security**:
- Verify signature using `\Stripe\Webhook::constructEvent()`
- Use endpoint secret from Stripe dashboard
- Return 400 for invalid signatures

**Environment Variables** (in `.env`):
```env
STRIPE_SECRET_KEY=sk_test_...
STRIPE_PUBLIC_KEY=pk_test_...
STRIPE_WEBHOOK_SECRET=whsec_...
```

**Alternatives Considered**:
- Manual API calls - Rejected: reinventing wheel, less secure
- Third-party payment processors - Rejected: TD3 specifies Stripe

---

### 4. Validation Groups (Dynamic Constraints)

**Decision**: Use Symfony validation groups based on user premium status

**Rationale**:
- Constitutional requirement (Section V - Logique Premium)
- Symfony native feature for conditional validation
- Clean separation of concerns

**Implementation Pattern**:

Entity (Publication.php):
```php
#[Assert\Length(max: 50, groups: ['non_premium'])]
#[Assert\Length(max: 200, groups: ['premium'])]
private ?string $content = null;
```

Form Type (PublicationType.php):
```php
public function configureOptions(OptionsResolver $resolver): void
{
    $resolver->setDefaults([
        'data_class' => Publication::class,
        'validation_groups' => function (FormInterface $form) {
            $user = $this->security->getUser();
            return $user && $user->isPremium() ? ['Default', 'premium'] : ['Default', 'non_premium'];
        },
    ]);
}
```

**Alternatives Considered**:
- Custom validator - Rejected: overcomplicated, groups are built-in
- JavaScript-only validation - Rejected: insecure, must validate server-side

---

### 5. Symfony Console Commands

**Decision**: Create 4 commands using `make:command`

**Rationale**:
- Constitutional requirement (Section II - use makers)
- Standard Symfony pattern for CLI operations
- Provides argument validation, help messages, output formatting

**Command Names**:
- `app:give:premium`
- `app:revoke:premium`
- `app:promote:admin`
- `app:revoke:admin`

**Pattern**:
```php
protected function configure(): void
{
    $this->addArgument('user-identifier', InputArgument::REQUIRED, 'User email or ID');
}

protected function execute(InputInterface $input, OutputInterface $output): int
{
    $identifier = $input->getArgument('user-identifier');
    $user = $this->userRepository->findOneByEmailOrId($identifier);

    if (!$user) {
        $output->writeln('<error>User not found</error>');
        return Command::FAILURE;
    }

    // Perform operation
    $this->entityManager->flush();

    $output->writeln('<info>Success</info>');
    return Command::SUCCESS;
}
```

**Docker Execution**:
```bash
docker exec -it thefeed-backend php bin/console app:give:premium user@example.com
```

**Alternatives Considered**:
- Admin web UI - Rejected: not in TD3 scope
- Direct database manipulation - Rejected: bypasses Doctrine, error-prone

---

### 6. AJAX with Vanilla JavaScript

**Decision**: Use `fetch()` API with DELETE method

**Rationale**:
- Constitutional requirement (Section VII - JavaScript Asynchrone)
- Modern browser API, no jQuery needed
- Clean promise-based syntax

**Implementation Pattern**:
```javascript
document.querySelectorAll('.delete-feedy').forEach(button => {
    button.addEventListener('click', async (e) => {
        e.preventDefault();
        const publicationId = button.dataset.publicationId;
        const url = Routing.generate('publication_delete', {id: publicationId});

        try {
            const response = await fetch(url, {method: 'DELETE'});

            if (response.status === 204) {
                button.closest('.publication-item').remove();
            } else if (response.status === 403) {
                alert('Vous n\'êtes pas autorisé à supprimer cette publication');
            } else if (response.status === 404) {
                alert('Publication non trouvée');
            }
        } catch (error) {
            alert('Erreur réseau');
        }
    });
});
```

**Error Handling**:
- 204: Success, remove from DOM
- 403: Unauthorized, show error
- 404: Not found, show error
- Network error: Show generic error

**Alternatives Considered**:
- XMLHttpRequest - Rejected: older API, more verbose
- jQuery - Rejected: constitution forbids frameworks
- Axios - Rejected: constitution forbids frontend frameworks

---

### 7. Role Hierarchy

**Decision**: Configure `ROLE_ADMIN` to inherit `ROLE_USER` in `security.yaml`

**Rationale**:
- Constitutional requirement (Section IV)
- Symfony built-in feature
- Simplifies access control checks

**Configuration** (`config/packages/security.yaml`):
```yaml
security:
    role_hierarchy:
        ROLE_ADMIN: ROLE_USER
```

**Effect**:
- Users with ROLE_ADMIN automatically have ROLE_USER privileges
- Voters and IsGranted checks work seamlessly
- No need to grant both roles manually

**Alternatives Considered**:
- Manual role checks - Rejected: error-prone, not DRY
- Separate admin and user entities - Rejected: overcomplicated

---

### 8. Service Parameters

**Decision**: Store premium price in `config/services.yaml` as parameter

**Rationale**:
- Constitutional requirement (Section V)
- Symfony best practice for configuration values
- Easy to change without code modification
- Injectable into controllers/services

**Configuration** (`config/services.yaml`):
```yaml
parameters:
    premium_price: 999  # cents (9.99 EUR)
```

**Usage in Controller**:
```php
public function __construct(
    private ParameterBagInterface $params
) {}

public function premium(): Response
{
    $price = $this->params->get('premium_price');
    return $this->render('premium/index.html.twig', [
        'price' => $price / 100,  // Convert to EUR
    ]);
}
```

**Alternatives Considered**:
- Hardcoded values - Rejected: not configurable
- Database storage - Rejected: overcomplicated for single value
- Environment variables - Rejected: parameters more appropriate

---

## Summary

All research complete. No unresolved clarifications. All decisions align with:
- TD3 requirements
- Project constitution
- Symfony best practices
- Modern PHP/JavaScript standards

Ready to proceed to Phase 1 (Design & Contracts).
