# Feature Specification: TD3 - Advanced Features

**Feature Branch**: `001-td3`
**Created**: 2025-12-10
**Status**: Draft
**Input**: User description: "TD3: Suppression AJAX, Premium features, Stripe integration, Voters, and CLI commands"

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Asynchronous Publication Deletion (Priority: P1)

As a publication author, I want to delete my publications instantly without page reload, so that I can quickly manage my content with a smooth user experience.

**Why this priority**: This is the foundation for modern UX and teaches core async JavaScript concepts. It's independently valuable and can be delivered first.

**Independent Test**: Can be fully tested by creating a publication, clicking the delete button, and verifying it disappears from the page without reload, with the server confirming deletion.

**Acceptance Scenarios**:

1. **Given** I am logged in and viewing a feed with my publications, **When** I click the delete button on one of my publications, **Then** the publication disappears immediately from the page without reload
2. **Given** I am logged in and viewing a publication I authored, **When** I click delete and the server confirms deletion, **Then** I receive visual feedback and the publication is permanently removed
3. **Given** I am viewing publications from other users, **When** I look at the publication actions, **Then** I do not see a delete button (only authors see delete buttons)
4. **Given** I attempt to delete a publication via AJAX, **When** the server responds with an error (403 or 404), **Then** I see an appropriate error message and the publication remains visible

---

### User Story 2 - Premium User Status and Benefits (Priority: P2)

As a user, I want to upgrade to premium status to unlock extended capabilities, so that I can create longer, more detailed publications.

**Why this priority**: Premium features create a clear value proposition and demonstrate data-driven validation. Must come after basic CRUD operations.

**Independent Test**: Can be fully tested by upgrading a user to premium status and verifying they can create longer publications while non-premium users are limited.

**Acceptance Scenarios**:

1. **Given** I am a non-premium user creating a publication, **When** I type content longer than 50 characters, **Then** I receive a validation error
2. **Given** I am a premium user creating a publication, **When** I type content up to 200 characters, **Then** my publication is accepted and saved
3. **Given** I am a premium user viewing the feed, **When** I see my username, **Then** it appears with a distinctive golden/premium styling
4. **Given** I am browsing the feed, **When** I see different users' publications, **Then** I can visually distinguish premium users from non-premium users

---

### User Story 3 - Premium Purchase via Stripe (Priority: P3)

As a non-premium user, I want to purchase premium status securely with my credit card, so that I can unlock premium benefits immediately after payment.

**Why this priority**: Monetization completes the premium feature set. Depends on premium user status being implemented first.

**Independent Test**: Can be fully tested by navigating to the premium page, completing a Stripe checkout, and verifying the user is upgraded to premium after successful payment.

**Acceptance Scenarios**:

1. **Given** I am a non-premium user, **When** I navigate to the premium page, **Then** I see the premium price and a button to start checkout
2. **Given** I am on the premium page, **When** I click the checkout button, **Then** I am redirected to Stripe's secure payment page with the correct amount
3. **Given** I complete payment on Stripe successfully, **When** Stripe redirects me back, **Then** I see a confirmation page and my account is upgraded to premium
4. **Given** I cancel payment on Stripe, **When** Stripe redirects me back, **Then** I see a cancellation message and my account remains non-premium
5. **Given** Stripe sends a webhook after successful payment, **When** the system receives it with valid signature, **Then** the user is automatically upgraded to premium status

---

### User Story 4 - Content Access Control (Priority: P2)

As a user, I want my publications to be protected so that only I can delete them, unless an administrator needs to moderate content.

**Why this priority**: Security is critical and must be implemented correctly from the start. Demonstrates proper authorization patterns.

**Independent Test**: Can be fully tested by attempting to delete publications as different users (author, non-author, admin) and verifying only authorized actions succeed.

**Acceptance Scenarios**:

1. **Given** I am the author of a publication, **When** I attempt to delete it, **Then** the deletion succeeds
2. **Given** I am not the author of a publication, **When** I attempt to delete it, **Then** I receive a 403 Forbidden response
3. **Given** I am an administrator, **When** I attempt to delete any publication, **Then** the deletion succeeds regardless of authorship
4. **Given** I am viewing a publication I did not author, **When** the page loads, **Then** no delete button is visible to me

---

### User Story 5 - Administrative User Management (Priority: P3)

As an administrator, I want command-line tools to manage user permissions and premium status, so that I can efficiently handle user accounts without using the web interface.

**Why this priority**: Admin tools are supporting functionality needed for testing and management, but not user-facing.

**Independent Test**: Can be fully tested by running each command in the terminal and verifying the user's status changes correctly in the database and is reflected in the web interface.

**Acceptance Scenarios**:

1. **Given** I run the `give:premium` command with a user identifier, **When** the command executes successfully, **Then** the user's premium status is set to true
2. **Given** I run the `revoke:premium` command with a user identifier, **When** the command executes successfully, **Then** the user's premium status is set to false
3. **Given** I run the `promote:admin` command with a user identifier, **When** the command executes successfully, **Then** the user is granted ROLE_ADMIN
4. **Given** I run the `revoke:admin` command with a user identifier, **When** the command executes successfully, **Then** the user's ROLE_ADMIN is removed
5. **Given** I run any command with an invalid user identifier, **When** the command executes, **Then** I receive a clear error message

---

### Edge Cases

- What happens when a user tries to delete a publication while their session expires?
- How does the system handle Stripe webhook delivery failures or duplicates?
- What happens if a premium user's payment is refunded?
- What happens when JavaScript is disabled in the browser (delete button should still work with page reload)?
- How does the system handle concurrent deletion attempts on the same publication?
- What happens when Stripe webhook signature verification fails?
- How does the system behave if a user has ROLE_ADMIN but is not premium?

## Requirements *(mandatory)*

### Functional Requirements

#### AJAX Deletion

- **FR-001**: System MUST provide a DELETE route for publications that returns JSON responses
- **FR-002**: System MUST return HTTP 204 when deletion succeeds
- **FR-003**: System MUST return HTTP 403 when user is not authorized to delete
- **FR-004**: System MUST return HTTP 404 when publication does not exist
- **FR-005**: Delete buttons MUST only appear for the author of the publication
- **FR-006**: JavaScript MUST remove the publication from the DOM immediately upon successful deletion
- **FR-007**: JavaScript MUST display error messages when deletion fails
- **FR-008**: The delete functionality MUST work without page reload

#### JavaScript Routing

- **FR-009**: System MUST expose DELETE route URLs to JavaScript via FOSJsRoutingBundle
- **FR-010**: JavaScript MUST use `Routing.generate()` to build DELETE URLs dynamically
- **FR-011**: System MUST include router JavaScript files in the base template

#### Premium User Features

- **FR-012**: User entity MUST have a boolean `premium` field with default value `false`
- **FR-013**: Premium users MUST be visually distinguished with golden/special styling on their username
- **FR-014**: Non-premium users MUST be limited to publications with maximum 50 characters
- **FR-015**: Premium users MUST be allowed to create publications with maximum 200 characters
- **FR-016**: Validation constraints MUST be applied dynamically based on user's premium status
- **FR-017**: System MUST use validation groups to differentiate premium and non-premium constraints

#### Premium Page

- **FR-018**: System MUST provide a premium page accessible to non-premium authenticated users
- **FR-019**: Premium page MUST display the premium price from configuration
- **FR-020**: Premium page MUST provide a button to initiate Stripe checkout
- **FR-021**: Premium price MUST be configurable via application parameters

#### Stripe Integration

- **FR-022**: System MUST create Stripe checkout sessions in `payment` mode
- **FR-023**: Stripe checkout MUST include user ID in session metadata
- **FR-024**: System MUST handle successful payment redirects and display confirmation
- **FR-025**: System MUST handle cancelled payment redirects and display cancellation message
- **FR-026**: System MUST verify payment status before upgrading user to premium
- **FR-027**: System MUST provide a webhook endpoint for Stripe events
- **FR-028**: Webhook endpoint MUST verify Stripe signature before processing events
- **FR-029**: Webhook endpoint MUST upgrade users to premium upon successful payment
- **FR-030**: Webhook endpoint MUST return HTTP 200 for valid events
- **FR-031**: Webhook endpoint MUST return HTTP 400 for invalid signatures or events

#### Security and Authorization

- **FR-032**: System MUST use security voters to determine publication deletion permissions
- **FR-033**: System MUST check if user is the author OR has ROLE_ADMIN for deletion
- **FR-034**: System MUST define role hierarchy where ROLE_ADMIN inherits ROLE_USER
- **FR-035**: Controllers MUST use IsGranted annotations with subject and ExpressionLanguage
- **FR-036**: System MUST NOT perform manual 403/404 checks when voters handle authorization

#### Administrative Commands

- **FR-037**: System MUST provide a `give:premium` CLI command
- **FR-038**: System MUST provide a `revoke:premium` CLI command
- **FR-039**: System MUST provide a `promote:admin` CLI command
- **FR-040**: System MUST provide a `revoke:admin` CLI command
- **FR-041**: All CLI commands MUST accept a user identifier as argument
- **FR-042**: All CLI commands MUST provide clear success or error messages
- **FR-043**: All CLI commands MUST persist changes to the database

### Key Entities

- **User**: Represents a registered user with authentication credentials, premium status (boolean), and roles (ROLE_USER, ROLE_ADMIN). Premium status determines publication length limits.
- **Publication**: Represents user-generated content with text content, author relationship, and timestamps. Content length is validated based on author's premium status.
- **Stripe Session**: Represents a Stripe checkout session with payment status, user metadata, and amount. Used to track premium purchases.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Users can delete their publications without page reload in under 1 second
- **SC-002**: Premium users can create publications up to 200 characters while non-premium users are limited to 50 characters
- **SC-003**: Users can complete premium purchase via Stripe in under 3 minutes
- **SC-004**: Premium status appears visually distinct (golden styling) immediately after purchase confirmation
- **SC-005**: Non-authors cannot delete publications they don't own (100% authorization accuracy)
- **SC-006**: Administrators can delete any publication regardless of authorship
- **SC-007**: CLI commands execute successfully and persist changes within 2 seconds
- **SC-008**: Stripe webhook processes payments securely with signature verification (100% validation rate)
- **SC-009**: Delete buttons only appear for publication authors (100% visibility accuracy)
- **SC-010**: JavaScript deletion provides immediate visual feedback (under 300ms DOM update)

### Qualitative Outcomes

- Users experience smooth, modern interface with async operations
- Security is enforced consistently through voter-based authorization
- Premium purchase flow is secure and trustworthy
- Administrative tasks are efficient and scriptable
