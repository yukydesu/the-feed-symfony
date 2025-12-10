# Tasks: TD3 - Advanced Features

**Input**: Design documents from `/specs/001-td3/`
**Prerequisites**: plan.md, spec.md, research.md, data-model.md, contracts/

**Tests**: Manual testing only per constitution (no automated test tasks)

**Organization**: Tasks are grouped by user story to enable independent implementation and testing of each story.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies)
- **[Story]**: Which user story this task belongs to (e.g., US1, US2, US3, US4, US5)
- Include exact file paths in descriptions

## Path Conventions

Symfony web application structure:
- Controllers: `src/Controller/`
- Entities: `src/Entity/`
- Forms: `src/Form/`
- Voters: `src/Security/Voter/`
- Commands: `src/Command/`
- Templates: `templates/`
- JavaScript: `public/js/`
- CSS: `public/css/`
- Migrations: `migrations/`

All Symfony commands executed via: `docker exec -it thefeed-backend php bin/console <command>`

---

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Install dependencies and configure basic infrastructure

- [ ] T001 Install FOSJsRoutingBundle via composer require friendsofsymfony/jsrouting-bundle
- [ ] T002 Install Stripe PHP SDK via composer require stripe/stripe-php
- [ ] T003 [P] Create FOSJsRoutingBundle configuration in config/packages/fos_js_routing.yaml
- [ ] T004 [P] Add Stripe environment variables to .env (STRIPE_SECRET_KEY, STRIPE_PUBLIC_KEY, STRIPE_WEBHOOK_SECRET)

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Core infrastructure that MUST be complete before ANY user story can be implemented

**⚠️ CRITICAL**: No user story work can begin until this phase is complete

- [ ] T005 Add premium field (boolean, default false, NOT nullable) to User entity in src/Entity/User.php via make:entity
- [ ] T006 Set premium property default value to false directly in src/Entity/User.php
- [ ] T007 Add options: ["default" => false] to ORM\Column attribute for premium field in src/Entity/User.php
- [ ] T008 Add isPremium() method to User entity in src/Entity/User.php
- [ ] T009 Add addRole($role) method to User entity in src/Entity/User.php
- [ ] T010 Add removeRole($role) method to User entity in src/Entity/User.php
- [ ] T011 Create database migration via make:migration
- [ ] T012 Run database migration via doctrine:migrations:migrate
- [ ] T013 Configure role hierarchy (ROLE_ADMIN inherits ROLE_USER) in config/packages/security.yaml
- [ ] T014 Configure premium_price parameter (999 cents) in config/services.yaml

**Checkpoint**: Foundation ready - user story implementation can now begin in parallel

---

## Phase 3: User Story 1 - Asynchronous Publication Deletion (Priority: P1) 🎯 MVP

**Goal**: Allow publication authors to delete their publications instantly via AJAX without page reload

**Independent Test**: Create a publication, click delete button, verify it disappears from page without reload and server confirms deletion

### Implementation for User Story 1

- [ ] T015 [US1] Add DELETE route /publications/{id} with expose=true in src/Controller/PublicationController.php
- [ ] T016 [US1] Add IsGranted('ROLE_USER') to delete method in src/Controller/PublicationController.php
- [ ] T017 [US1] Implement delete method with manual author verification returning JsonResponse in src/Controller/PublicationController.php
- [ ] T018 [US1] Add publication_delete route to routes_to_expose in config/packages/fos_js_routing.yaml
- [ ] T019 [US1] Include FOSJsRoutingBundle router scripts in templates/base.html.twig (router.min.js and fos_js_routing_js)
- [ ] T020 [US1] Create JavaScript file public/js/publications.js with delete handler
- [ ] T021 [US1] Implement fetch() DELETE request using Routing.generate() in public/js/publications.js
- [ ] T022 [US1] Implement DOM removal on 204 response in public/js/publications.js
- [ ] T023 [US1] Implement error handling for 403/404 responses in public/js/publications.js
- [ ] T024 [US1] Add delete button with .delete-feedy class and data-publication-id attribute in templates/publication/index.html.twig
- [ ] T025 [US1] Add Twig condition to show delete button only for authors (publication.auteur == app.user) in templates/publication/index.html.twig
- [ ] T026 [US1] Load publications.js script conditionally for authenticated users in templates/publication/index.html.twig
- [ ] T027 [US1] Upgrade IsGranted to use Expression with subject checking user is author in src/Controller/PublicationController.php
- [ ] T028 [US1] Create PublicationVoter in src/Security/Voter/PublicationVoter.php via make:voter
- [ ] T029 [US1] Implement supports() method checking PUBLICATION_DELETE attribute and Publication subject in src/Security/Voter/PublicationVoter.php
- [ ] T030 [US1] Implement voteOnAttribute() checking user is author OR has ROLE_ADMIN in src/Security/Voter/PublicationVoter.php
- [ ] T031 [US1] Update delete method to use IsGranted('PUBLICATION_DELETE', subject: 'publication') in src/Controller/PublicationController.php
- [ ] T032 [US1] Update delete button visibility to use is_granted('PUBLICATION_DELETE', publication) in templates/publication/index.html.twig

**Checkpoint**: At this point, AJAX deletion should work independently - test by creating and deleting publications without page reload

---

## Phase 4: User Story 2 - Premium User Status and Benefits (Priority: P2)

**Goal**: Allow users to have premium status unlocking extended publication length (200 chars vs 50 chars)

**Independent Test**: Upgrade user to premium via CLI, verify they can create longer publications while non-premium users are limited

### Implementation for User Story 2

- [ ] T033 [US2] Add Length(max=50, groups=['non_premium']) constraint to Publication.content in src/Entity/Publication.php
- [ ] T034 [US2] Add Length(max=200, groups=['premium']) constraint to Publication.content in src/Entity/Publication.php
- [ ] T035 [US2] Inject Security service into PublicationType constructor in src/Form/PublicationType.php
- [ ] T036 [US2] Implement dynamic validation_groups in configureOptions() checking user.isPremium() in src/Form/PublicationType.php
- [ ] T037 [US2] Return ['Default', 'premium'] for premium users in src/Form/PublicationType.php
- [ ] T038 [US2] Return ['Default', 'non_premium'] for non-premium users in src/Form/PublicationType.php
- [ ] T039 [US2] Create CSS file public/css/premium.css with .premium-user golden styling
- [ ] T040 [US2] Add .premium-user class conditionally to username in templates/publication/index.html.twig
- [ ] T041 [US2] Include premium.css stylesheet in templates/base.html.twig

**Checkpoint**: Premium users can create 200-char publications, non-premium limited to 50 chars, premium users have golden styling

---

## Phase 5: User Story 3 - Premium Purchase via Stripe (Priority: P3)

**Goal**: Allow non-premium users to purchase premium status securely via Stripe checkout

**Independent Test**: Navigate to /premium, complete Stripe checkout (test mode), verify user upgraded to premium after payment

### Implementation for User Story 3

- [ ] T042 [P] [US3] Create PremiumController in src/Controller/PremiumController.php via make:controller
- [ ] T043 [P] [US3] Create WebhookController in src/Controller/WebhookController.php via make:controller
- [ ] T044 [US3] Inject ParameterBagInterface into PremiumController constructor in src/Controller/PremiumController.php
- [ ] T045 [US3] Implement premium index action (GET /premium) with IsGranted ROLE_USER in src/Controller/PremiumController.php
- [ ] T046 [US3] Pass premium_price parameter to template in premium index action in src/Controller/PremiumController.php
- [ ] T047 [US3] Implement premium checkout action (POST /premium/checkout) creating Stripe session in src/Controller/PremiumController.php
- [ ] T048 [US3] Configure Stripe session with payment mode, line_items, and userId metadata in src/Controller/PremiumController.php
- [ ] T049 [US3] Set success_url to premium_confirm route with session_id parameter in src/Controller/PremiumController.php
- [ ] T050 [US3] Set cancel_url to premium_cancel route in src/Controller/PremiumController.php
- [ ] T051 [US3] Redirect to Stripe checkout URL with 303 status in src/Controller/PremiumController.php
- [ ] T052 [US3] Implement premium confirm action (GET /premium/confirm) retrieving Stripe session in src/Controller/PremiumController.php
- [ ] T053 [US3] Verify payment_status === paid in premium confirm action in src/Controller/PremiumController.php
- [ ] T054 [US3] Set user.premium = true and flush in premium confirm action in src/Controller/PremiumController.php
- [ ] T055 [US3] Implement premium cancel action (GET /premium/cancel) in src/Controller/PremiumController.php
- [ ] T056 [US3] Implement webhook stripe action (POST /webhook/stripe) with no IsGranted in src/Controller/WebhookController.php
- [ ] T057 [US3] Verify Stripe webhook signature using Webhook::constructEvent() in src/Controller/WebhookController.php
- [ ] T058 [US3] Return 400 JsonResponse for invalid signature in src/Controller/WebhookController.php
- [ ] T059 [US3] Check event type === checkout.session.completed in src/Controller/WebhookController.php
- [ ] T060 [US3] Extract userId from session metadata in src/Controller/WebhookController.php
- [ ] T061 [US3] Find user, set premium = true, flush in src/Controller/WebhookController.php
- [ ] T062 [US3] Return 200 JsonResponse with success status in src/Controller/WebhookController.php
- [ ] T063 [P] [US3] Create template templates/premium/index.html.twig with price display and checkout button
- [ ] T064 [P] [US3] Create template templates/premium/confirm.html.twig with success message
- [ ] T065 [P] [US3] Create template templates/premium/cancel.html.twig with cancellation message

**Checkpoint**: Full Stripe checkout flow works - users can purchase premium and are upgraded automatically

---

## Phase 6: User Story 4 - Content Access Control (Priority: P2)

**Goal**: Ensure only publication authors can delete their publications, unless user is admin

**Independent Test**: Attempt to delete publications as different users (author, non-author, admin) and verify only authorized actions succeed

**Note**: This user story is already implemented via User Story 1 (PublicationVoter). No additional tasks required.

**Checkpoint**: Voter authorization working - only authors and admins can delete publications

---

## Phase 7: User Story 5 - Administrative User Management (Priority: P3)

**Goal**: Provide CLI commands for administrators to manage user permissions and premium status

**Independent Test**: Run each command in terminal and verify user status changes correctly

### Implementation for User Story 5

- [ ] T066 [P] [US5] Create GivePremiumCommand in src/Command/GivePremiumCommand.php via make:command app:give:premium
- [ ] T067 [P] [US5] Create RevokePremiumCommand in src/Command/RevokePremiumCommand.php via make:command app:revoke:premium
- [ ] T068 [P] [US5] Create PromoteAdminCommand in src/Command/PromoteAdminCommand.php via make:command app:promote:admin
- [ ] T069 [P] [US5] Create RevokeAdminCommand in src/Command/RevokeAdminCommand.php via make:command app:revoke:admin
- [ ] T070 [US5] Inject UserRepository and EntityManagerInterface into GivePremiumCommand constructor
- [ ] T071 [US5] Add user-identifier argument in GivePremiumCommand configure() method
- [ ] T072 [US5] Implement execute() finding user by email or ID in GivePremiumCommand
- [ ] T073 [US5] Set user.premium = true and flush in GivePremiumCommand
- [ ] T074 [US5] Output success/error messages in GivePremiumCommand
- [ ] T075 [US5] Inject UserRepository and EntityManagerInterface into RevokePremiumCommand constructor
- [ ] T076 [US5] Add user-identifier argument in RevokePremiumCommand configure() method
- [ ] T077 [US5] Implement execute() finding user by email or ID in RevokePremiumCommand
- [ ] T078 [US5] Set user.premium = false and flush in RevokePremiumCommand
- [ ] T079 [US5] Output success/error messages in RevokePremiumCommand
- [ ] T080 [US5] Inject UserRepository and EntityManagerInterface into PromoteAdminCommand constructor
- [ ] T081 [US5] Add user-identifier argument in PromoteAdminCommand configure() method
- [ ] T082 [US5] Implement execute() finding user by email or ID in PromoteAdminCommand
- [ ] T083 [US5] Use addRole() method to add ROLE_ADMIN and flush in PromoteAdminCommand
- [ ] T084 [US5] Output success/error messages in PromoteAdminCommand
- [ ] T085 [US5] Inject UserRepository and EntityManagerInterface into RevokeAdminCommand constructor
- [ ] T086 [US5] Add user-identifier argument in RevokeAdminCommand configure() method
- [ ] T087 [US5] Implement execute() finding user by email or ID in RevokeAdminCommand
- [ ] T088 [US5] Use removeRole() method to remove ROLE_ADMIN and flush in RevokeAdminCommand
- [ ] T089 [US5] Output success/error messages in RevokeAdminCommand

**Checkpoint**: All 4 CLI commands work correctly - test with docker exec -it thefeed-backend php bin/console app:give:premium user@example.com

---

## Phase 8: Polish & Cross-Cutting Concerns

**Purpose**: Final improvements and validation

- [ ] T090 [P] Clear Symfony cache via cache:clear
- [ ] T091 [P] Verify all routes with debug:router
- [ ] T092 Run complete manual test checklist from quickstart.md
- [ ] T093 Verify constitution compliance (no extra features, minimal code, proper makers used)
- [ ] T094 Test AJAX deletion across different browsers
- [ ] T095 Test Stripe webhook with Stripe CLI (stripe listen --forward-to localhost/webhook/stripe)
- [ ] T096 Verify premium user golden styling displays correctly
- [ ] T097 Verify validation groups work correctly for premium vs non-premium users

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: No dependencies - can start immediately
- **Foundational (Phase 2)**: Depends on Setup completion - BLOCKS all user stories
- **User Stories (Phase 3-7)**: All depend on Foundational phase completion
  - US1 (AJAX Delete): Can start after Phase 2 - independent
  - US2 (Premium Benefits): Can start after Phase 2 - independent
  - US3 (Stripe Purchase): Can start after Phase 2 - independent
  - US4 (Access Control): Already implemented via US1 - no work needed
  - US5 (CLI Commands): Can start after Phase 2 - independent
- **Polish (Phase 8)**: Depends on all user stories being complete

### User Story Dependencies

- **US1 (P1)**: Independent - only needs Foundational phase
- **US2 (P2)**: Independent - only needs Foundational phase
- **US3 (P3)**: Independent - only needs Foundational phase
- **US4 (P2)**: Implemented by US1 voter - no separate work
- **US5 (P3)**: Independent - only needs Foundational phase

### Within Each User Story

- US1: Voter → Controller → JavaScript → Templates (sequential)
- US2: Entity validation → Form groups → CSS → Templates (sequential)
- US3: Controllers can be created in parallel (T035, T036) → Implementation sequential → Templates in parallel
- US5: All 4 commands can be created in parallel (T059-T062) → Each command implemented sequentially

### Parallel Opportunities

**Phase 1** (all parallel):
- T001, T002, T003, T004 can all run in parallel

**Phase 3 (US1)**:
- T019-T022 (JavaScript implementation) can be written while waiting for controller completion

**Phase 5 (US3)**:
- T042, T043 (create controllers) in parallel
- T063, T064, T065 (create templates) in parallel

**Phase 7 (US5)**:
- T066, T067, T068, T069 (create all 4 commands) in parallel

**Phase 8** (most tasks parallel):
- T090, T091, T094, T096, T097 can run in parallel

---

## Parallel Example: User Story 5 (CLI Commands)

```bash
# Launch all 4 command creations together:
Task: "Create GivePremiumCommand via make:command app:give:premium"
Task: "Create RevokePremiumCommand via make:command app:revoke:premium"
Task: "Create PromoteAdminCommand via make:command app:promote:admin"
Task: "Create RevokeAdminCommand via make:command app:revoke:admin"

# Then implement each sequentially
```

---

## Implementation Strategy

### MVP First (User Story 1 Only)

1. Complete Phase 1: Setup (T001-T004)
2. Complete Phase 2: Foundational (T005-T014) - CRITICAL
3. Complete Phase 3: User Story 1 (T015-T032)
4. **STOP and VALIDATE**: Test AJAX deletion independently
5. Deploy/demo if ready

### Incremental Delivery

1. Complete Setup + Foundational → Foundation ready
2. Add User Story 1 (AJAX Delete) → Test independently → Deploy/Demo (MVP!)
3. Add User Story 2 (Premium Benefits) → Test independently → Deploy/Demo
4. Add User Story 3 (Stripe Purchase) → Test independently → Deploy/Demo
5. Add User Story 5 (CLI Commands) → Test independently → Deploy/Demo
6. Each story adds value without breaking previous stories

### Parallel Team Strategy

With multiple developers:

1. Team completes Setup + Foundational together (T001-T014)
2. Once Foundational is done (after T014):
   - Developer A: User Story 1 (T015-T032) - AJAX Delete
   - Developer B: User Story 2 (T033-T041) - Premium Benefits
   - Developer C: User Story 3 (T042-T065) - Stripe Purchase
   - Developer D: User Story 5 (T066-T089) - CLI Commands
3. Stories complete and integrate independently

---

## Task Summary

**Total Tasks**: 97

**By Phase**:
- Phase 1 (Setup): 4 tasks
- Phase 2 (Foundational): 10 tasks
- Phase 3 (US1 - AJAX Delete): 18 tasks
- Phase 4 (US2 - Premium Benefits): 9 tasks
- Phase 5 (US3 - Stripe Purchase): 24 tasks
- Phase 6 (US4 - Access Control): 0 tasks (covered by US1)
- Phase 7 (US5 - CLI Commands): 24 tasks
- Phase 8 (Polish): 8 tasks

**By User Story**:
- US1: 18 tasks (AJAX deletion with progressive permissions)
- US2: 9 tasks (Premium features)
- US3: 24 tasks (Stripe integration)
- US4: 0 tasks (voter from US1)
- US5: 24 tasks (4 CLI commands)

**Parallel Tasks**: 18 tasks marked [P]

**Independent Test Criteria**: Each user story has clear test criteria for validation

**MVP Scope**: Phase 1 + Phase 2 + Phase 3 (US1 only) = 32 tasks for basic AJAX deletion with proper permissions progression

---

## Notes

- [P] tasks = different files, no dependencies, can run in parallel
- [Story] label maps task to specific user story for traceability
- Each user story can be implemented and tested independently
- Manual testing only per constitution (no automated test tasks)
- All Symfony commands executed via Docker: `docker exec -it thefeed-backend bash`
- Use Symfony makers whenever possible per constitution
- Commit after each logical group of tasks
- Stop at any checkpoint to validate story independently
- Verify constitution compliance throughout implementation
