# Implementation Plan: TD3 - Advanced Features

**Branch**: `001-td3` | **Date**: 2025-12-10 | **Spec**: [spec.md](./spec.md)
**Input**: Feature specification from `/specs/001-td3/spec.md`

## Summary

This plan implements TD3 advanced features for The Feed application: asynchronous publication deletion via AJAX, premium user status with extended content limits, Stripe payment integration for premium upgrades, security voters for authorization, and administrative CLI commands. The implementation uses Symfony with Doctrine ORM, Twig templating, vanilla JavaScript with FOSJsRoutingBundle, Stripe PHP SDK, and runs in a Docker environment.

## Technical Context

**Language/Version**: PHP 8.2+ (Symfony 6.x/7.x)
**Primary Dependencies**:
- Symfony Framework Bundle
- Doctrine ORM
- Twig
- FOSJsRoutingBundle
- Stripe PHP SDK
- Symfony Maker Bundle (dev)
- Symfony Security Bundle

**Storage**: MySQL (via Docker container)
**Testing**: Manual testing via web interface
**Target Platform**: Docker container `thefeed-backend`
**Project Type**: Web application (Symfony MVC)
**Performance Goals**:
- AJAX deletion response < 1 second
- JavaScript DOM updates < 300ms
- CLI commands execute < 2 seconds

**Constraints**:
- All Symfony commands executed via Docker: `docker exec -it thefeed-backend bash`
- No manual file creation when Symfony makers available
- Strict adherence to TD3 requirements only
- No frameworks for frontend (vanilla JS only)

**Scale/Scope**:
- 5 user stories
- 43 functional requirements
- 4 CLI commands
- 1 Stripe webhook endpoint
- 1 Voter class

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

### Stack Compliance ✓
- [x] Symfony framework (required by constitution)
- [x] Twig for templating (required by constitution)
- [x] Doctrine ORM (required by constitution)
- [x] MySQL database (required by constitution)
- [x] Vanilla JavaScript (required by constitution)
- [x] FOSJsRoutingBundle (required by constitution)
- [x] Stripe PHP SDK (required by constitution)
- [x] Docker environment (required by constitution)

### TD3 Scope Compliance ✓
- [x] No features beyond TD3 requirements
- [x] No unnecessary abstractions or complexity
- [x] Minimal but correct implementation
- [x] Symfony coding standards followed

### Security Compliance ✓
- [x] Voters used for authorization logic
- [x] IsGranted annotations with ExpressionLanguage
- [x] Role hierarchy: ROLE_ADMIN inherits ROLE_USER
- [x] No manual 403/404 checks in controllers

### Premium Logic Compliance ✓
- [x] Boolean `premium` field on User entity
- [x] Dynamic validation groups (premium vs non-premium)
- [x] Premium price configured in services.yaml
- [x] CLI commands for premium/admin management

### Stripe Integration Compliance ✓
- [x] Checkout → Confirmation → Webhook flow
- [x] Payment mode with userId metadata
- [x] Payment verification before upgrade
- [x] Webhook signature verification

### JavaScript Compliance ✓
- [x] AJAX deletion with fetch() DELETE
- [x] Immediate DOM manipulation
- [x] Conditional script loading via Twig
- [x] Routing.generate() for URLs
- [x] Proper error handling (403, 404, 500)

**Gate Status**: ✅ PASS - All constitutional requirements met

## Project Structure

### Documentation (this feature)

```text
specs/001-td3/
├── plan.md              # This file (/speckit.plan command output)
├── research.md          # Phase 0 output (/speckit.plan command)
├── data-model.md        # Phase 1 output (/speckit.plan command)
├── quickstart.md        # Phase 1 output (/speckit.plan command)
├── contracts/           # Phase 1 output (/speckit.plan command)
│   ├── api-routes.md
│   └── webhook-spec.md
├── checklists/
│   └── requirements.md  # Spec validation checklist
└── tasks.md             # Phase 2 output (/speckit.tasks command - NOT created by /speckit.plan)
```

### Source Code (repository root)

```text
# Symfony Web Application Structure
src/
├── Controller/
│   ├── PublicationController.php    # AJAX delete route
│   ├── PremiumController.php        # Premium page + Stripe checkout
│   └── WebhookController.php        # Stripe webhook handler
├── Entity/
│   ├── User.php                     # +premium field, roles
│   └── Publication.php              # Content validation groups
├── Form/
│   └── PublicationType.php          # Dynamic validation groups
├── Repository/
│   ├── UserRepository.php           # User queries for CLI
│   └── PublicationRepository.php
├── Security/
│   └── Voter/
│       └── PublicationVoter.php     # PUBLICATION_DELETE attribute
├── Command/
│   ├── GivePremiumCommand.php
│   ├── RevokePremiumCommand.php
│   ├── PromoteAdminCommand.php
│   └── RevokeAdminCommand.php
└── Kernel.php

config/
├── packages/
│   ├── security.yaml                # Role hierarchy
│   ├── services.yaml                # premium_price parameter
│   └── fos_js_routing.yaml          # JS routing config
└── routes.yaml                       # DELETE route with expose=true

public/
└── js/
    └── publications.js              # AJAX deletion logic

templates/
├── base.html.twig                   # Router scripts inclusion
├── publication/
│   └── index.html.twig              # Delete button, script loading
└── premium/
    ├── index.html.twig              # Premium page
    ├── confirm.html.twig            # Payment confirmation
    └── cancel.html.twig             # Payment cancellation

migrations/
└── VersionYYYYMMDDHHMMSS.php        # User.premium field migration

tests/                                # Manual testing only per constitution
```

**Structure Decision**: Standard Symfony MVC web application structure. All source code in `src/` following Symfony conventions. JavaScript in `public/js/` for asset management. Twig templates in `templates/` organized by controller. Docker execution required for all Symfony commands.

## Complexity Tracking

No constitutional violations. All complexity justified by TD3 requirements:

| Component | Why Needed | Aligns With Constitution |
|-----------|------------|-------------------------|
| FOSJsRoutingBundle | Required for JavaScript route generation in AJAX deletion | Section VII - JavaScript Asynchrone |
| Stripe PHP SDK | Required for premium payment processing | Section VI - Intégration Stripe |
| PublicationVoter | Required for authorization logic per security best practices | Section IV - Sécurité et Permissions |
| Validation Groups | Required for dynamic premium/non-premium constraints | Section V - Logique Premium |
| 4 CLI Commands | Required for administrative user management | Section V - Logique Premium |

All components are minimal implementations required by TD3 scope.
