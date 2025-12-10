# Quickstart Guide: TD3 - Advanced Features

**Feature**: TD3 - Advanced Features
**Date**: 2025-12-10
**Audience**: Developers implementing TD3

## Overview

This quickstart guide provides step-by-step instructions for implementing all TD3 features. Follow the order to ensure dependencies are properly handled.

---

## Prerequisites

### Environment Setup

1. **Docker running** with `thefeed-backend` container
2. **Composer installed** in container
3. **Symfony installed** (version 6.x or 7.x)
4. **MySQL database** accessible from container
5. **Stripe account** (test mode keys)

### Required Tools

- Docker Desktop
- Terminal access
- Text editor (VS Code, PHPStorm, etc.)
- Web browser

---

## Implementation Order

### Phase 1: Database & Entities (30 min)

**1.1 Add Premium Field to User Entity**

```bash
docker exec -it thefeed-backend bash
php bin/console make:entity User
```

**Interactive prompts**:
- Add property: `premium`
- Type: `boolean`
- Nullable: `no`
- Default value: `false`

**1.2 Add Validation Groups to Publication Entity**

Edit `src/Entity/Publication.php` manually:

```php
use Symfony\Component\Validator\Constraints as Assert;

class Publication
{
    #[ORM\Column(type: 'text')]
    #[Assert\NotBlank]
    #[Assert\Length(max: 50, groups: ['non_premium'])]
    #[Assert\Length(max: 200, groups: ['premium'])]
    private ?string $content = null;
}
```

**1.3 Create and Run Migration**

```bash
php bin/console make:migration
php bin/console doctrine:migrations:migrate
```

**Verify**: Check database - `user` table should have `premium` column.

---

### Phase 2: Security Configuration (15 min)

**2.1 Configure Role Hierarchy**

Edit `config/packages/security.yaml`:

```yaml
security:
    role_hierarchy:
        ROLE_ADMIN: ROLE_USER
```

**2.2 Create Publication Voter**

```bash
php bin/console make:voter
```

**Name**: `PublicationVoter`

Edit `src/Security/Voter/PublicationVoter.php`:

```php
class PublicationVoter extends Voter
{
    public const DELETE = 'PUBLICATION_DELETE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $attribute === self::DELETE && $subject instanceof Publication;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) return false;

        /** @var Publication $subject */
        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            return true;
        }

        return $subject->getAuteur() === $user;
    }
}
```

**Verify**: Voter file created in `src/Security/Voter/`.

---

### Phase 3: Premium Service Configuration (5 min)

**3.1 Add Premium Price Parameter**

Edit `config/services.yaml`:

```yaml
parameters:
    premium_price: 999  # 9.99 EUR in cents
```

**Verify**: Parameter accessible in controllers.

---

### Phase 4: Form Validation Groups (10 min)

**4.1 Update PublicationType Form**

Edit `src/Form/PublicationType.php`:

```php
use Symfony\Component\Security\Core\Security;

class PublicationType extends AbstractType
{
    public function __construct(
        private Security $security
    ) {}

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Publication::class,
            'validation_groups' => function (FormInterface $form) {
                $user = $this->security->getUser();
                if ($user && $user->isPremium()) {
                    return ['Default', 'premium'];
                }
                return ['Default', 'non_premium'];
            },
        ]);
    }
}
```

**4.2 Add isPremium() Method to User**

Edit `src/Entity/User.php`:

```php
public function isPremium(): bool
{
    return $this->premium;
}
```

**Verify**: Form uses correct validation groups based on user status.

---

### Phase 5: AJAX Delete Route (20 min)

**5.1 Add DELETE Route to PublicationController**

Edit `src/Controller/PublicationController.php`:

```php
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

#[Route('/publications/{id}', name: 'publication_delete', methods: ['DELETE'], options: ['expose' => true])]
#[IsGranted('PUBLICATION_DELETE', subject: 'publication')]
public function delete(Publication $publication, EntityManagerInterface $em): JsonResponse
{
    $em->remove($publication);
    $em->flush();

    return new JsonResponse(null, 204);
}
```

**5.2 Install FOSJsRoutingBundle**

```bash
composer require friendsofsymfony/jsrouting-bundle
```

**5.3 Configure FOSJsRoutingBundle**

Create `config/packages/fos_js_routing.yaml`:

```yaml
fos_js_routing:
    routes_to_expose: ['publication_delete']
```

**5.4 Add Router Scripts to Base Template**

Edit `templates/base.html.twig`:

```twig
<head>
    ...
    <script src="{{ asset('bundles/fosjsrouting/js/router.min.js') }}"></script>
    <script src="{{ path('fos_js_routing_js', {callback: 'fos.Router.setData'}) }}"></script>
</head>
```

**5.5 Create JavaScript Delete Handler**

Create `public/js/publications.js`:

```javascript
document.addEventListener('DOMContentLoaded', () => {
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
                    alert('Non autorisé');
                } else if (response.status === 404) {
                    alert('Publication introuvable');
                }
            } catch (error) {
                alert('Erreur réseau');
            }
        });
    });
});
```

**5.6 Update Publication Template**

Edit `templates/publication/index.html.twig`:

```twig
{# Add delete button for author only #}
{% if publication.auteur == app.user %}
    <button class="delete-feedy" data-publication-id="{{ publication.id }}">
        Supprimer
    </button>
{% endif %}

{# Load JS conditionally #}
{% block javascripts %}
    {% if app.user %}
        <script src="{{ asset('js/publications.js') }}"></script>
    {% endif %}
{% endblock %}
```

**Verify**: Delete button works without page reload.

---

### Phase 6: Premium Controllers (30 min)

**6.1 Install Stripe PHP SDK**

```bash
composer require stripe/stripe-php
```

**6.2 Add Stripe Keys to .env**

Edit `.env`:

```env
STRIPE_SECRET_KEY=sk_test_your_key
STRIPE_PUBLIC_KEY=pk_test_your_key
STRIPE_WEBHOOK_SECRET=whsec_your_secret
```

**6.3 Create Premium Controller**

```bash
php bin/console make:controller PremiumController
```

Edit `src/Controller/PremiumController.php`:

```php
use Stripe\Stripe;
use Stripe\Checkout\Session;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class PremiumController extends AbstractController
{
    public function __construct(
        private ParameterBagInterface $params
    ) {}

    #[Route('/premium', name: 'premium_index')]
    #[IsGranted('ROLE_USER')]
    public function index(): Response
    {
        $price = $this->params->get('premium_price') / 100;
        return $this->render('premium/index.html.twig', [
            'price' => $price,
        ]);
    }

    #[Route('/premium/checkout', name: 'premium_checkout', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function checkout(): Response
    {
        Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']);

        $session = Session::create([
            'mode' => 'payment',
            'line_items' => [[
                'price_data' => [
                    'currency' => 'eur',
                    'product_data' => ['name' => 'Premium Membership'],
                    'unit_amount' => $this->params->get('premium_price'),
                ],
                'quantity' => 1,
            ]],
            'metadata' => [
                'userId' => $this->getUser()->getId(),
            ],
            'success_url' => $this->generateUrl('premium_confirm', [], UrlGeneratorInterface::ABSOLUTE_URL) . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => $this->generateUrl('premium_cancel', [], UrlGeneratorInterface::ABSOLUTE_URL),
        ]);

        return $this->redirect($session->url, 303);
    }

    #[Route('/premium/confirm', name: 'premium_confirm')]
    #[IsGranted('ROLE_USER')]
    public function confirm(Request $request, EntityManagerInterface $em): Response
    {
        Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']);

        $sessionId = $request->query->get('session_id');
        $session = Session::retrieve($sessionId);

        if ($session->payment_status === 'paid') {
            $user = $this->getUser();
            $user->setPremium(true);
            $em->flush();
        }

        return $this->render('premium/confirm.html.twig');
    }

    #[Route('/premium/cancel', name: 'premium_cancel')]
    #[IsGranted('ROLE_USER')]
    public function cancel(): Response
    {
        return $this->render('premium/cancel.html.twig');
    }
}
```

**6.4 Create Webhook Controller**

```bash
php bin/console make:controller WebhookController
```

Edit `src/Controller/WebhookController.php`:

```php
use Stripe\Webhook;
use Stripe\Exception\SignatureVerificationException;

class WebhookController extends AbstractController
{
    #[Route('/webhook/stripe', name: 'webhook_stripe', methods: ['POST'])]
    public function stripe(Request $request, UserRepository $userRepo, EntityManagerInterface $em): JsonResponse
    {
        $payload = $request->getContent();
        $sigHeader = $request->headers->get('Stripe-Signature');
        $webhookSecret = $_ENV['STRIPE_WEBHOOK_SECRET'];

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $webhookSecret);
        } catch (SignatureVerificationException $e) {
            return new JsonResponse(['error' => 'Invalid signature'], 400);
        }

        if ($event->type === 'checkout.session.completed') {
            $session = $event->data->object;

            if ($session->payment_status === 'paid') {
                $userId = $session->metadata->userId;
                $user = $userRepo->find($userId);

                if ($user) {
                    $user->setPremium(true);
                    $em->flush();
                }
            }
        }

        return new JsonResponse(['status' => 'success'], 200);
    }
}
```

**6.5 Create Premium Templates**

Create `templates/premium/index.html.twig`:

```twig
{% extends 'base.html.twig' %}

{% block body %}
    <h1>Premium Membership</h1>
    <p>Prix: {{ price }} EUR</p>

    {% if not app.user.premium %}
        <form method="post" action="{{ path('premium_checkout') }}">
            <button type="submit">Acheter Premium</button>
        </form>
    {% else %}
        <p>Vous êtes déjà premium!</p>
    {% endif %}
{% endblock %}
```

Create `templates/premium/confirm.html.twig`:

```twig
{% extends 'base.html.twig' %}

{% block body %}
    <h1>Paiement réussi!</h1>
    <p>Votre compte est maintenant premium.</p>
    <a href="{{ path('app_home') }}">Retour à l'accueil</a>
{% endblock %}
```

Create `templates/premium/cancel.html.twig`:

```twig
{% extends 'base.html.twig' %}

{% block body %}
    <h1>Paiement annulé</h1>
    <p>Vous avez annulé le paiement.</p>
    <a href="{{ path('premium_index') }}">Réessayer</a>
{% endblock %}
```

**Verify**: Premium purchase flow works end-to-end.

---

### Phase 7: CLI Commands (40 min)

**7.1 Create Give Premium Command**

```bash
php bin/console make:command app:give:premium
```

Edit `src/Command/GivePremiumCommand.php`:

```php
class GivePremiumCommand extends Command
{
    public function __construct(
        private UserRepository $userRepository,
        private EntityManagerInterface $em
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('user-identifier', InputArgument::REQUIRED, 'User email or ID');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $identifier = $input->getArgument('user-identifier');
        $user = $this->userRepository->findOneBy(['email' => $identifier])
            ?? $this->userRepository->find($identifier);

        if (!$user) {
            $output->writeln('<error>User not found</error>');
            return Command::FAILURE;
        }

        $user->setPremium(true);
        $this->em->flush();

        $output->writeln('<info>Premium granted to ' . $user->getEmail() . '</info>');
        return Command::SUCCESS;
    }
}
```

**7.2 Create Revoke Premium Command**

```bash
php bin/console make:command app:revoke:premium
```

*(Similar pattern, set `setPremium(false)`)*

**7.3 Create Promote Admin Command**

```bash
php bin/console make:command app:promote:admin
```

Edit `src/Command/PromoteAdminCommand.php`:

```php
$roles = $user->getRoles();
if (!in_array('ROLE_ADMIN', $roles)) {
    $roles[] = 'ROLE_ADMIN';
    $user->setRoles($roles);
    $this->em->flush();
}
```

**7.4 Create Revoke Admin Command**

```bash
php bin/console make:command app:revoke:admin
```

*(Remove ROLE_ADMIN from roles array)*

**Verify**: All 4 commands work:

```bash
php bin/console app:give:premium user@example.com
php bin/console app:revoke:premium user@example.com
php bin/console app:promote:admin user@example.com
php bin/console app:revoke:admin user@example.com
```

---

### Phase 8: UI Enhancements (15 min)

**8.1 Add Premium Styling**

Edit `templates/publication/index.html.twig`:

```twig
<span class="username {% if publication.auteur.premium %}premium-user{% endif %}">
    {{ publication.auteur.email }}
</span>
```

Create CSS in `public/css/premium.css`:

```css
.premium-user {
    color: gold;
    font-weight: bold;
}
```

**8.2 Include CSS in Base Template**

Edit `templates/base.html.twig`:

```twig
<link rel="stylesheet" href="{{ asset('css/premium.css') }}">
```

**Verify**: Premium users have golden username display.

---

## Testing Checklist

### Manual Tests

- [ ] Non-premium user creates 30-char publication → Success
- [ ] Non-premium user creates 60-char publication → Validation error
- [ ] Premium user creates 150-char publication → Success
- [ ] Premium user creates 250-char publication → Validation error
- [ ] Author clicks delete button → Publication disappears (no reload)
- [ ] Non-author sees publication → No delete button visible
- [ ] Admin clicks delete on any publication → Success
- [ ] Navigate to /premium → See price and checkout button
- [ ] Click checkout → Redirect to Stripe
- [ ] Complete payment → Redirect to confirm page, user upgraded
- [ ] Cancel payment → Redirect to cancel page, user unchanged
- [ ] Webhook receives valid event → User upgraded to premium
- [ ] Webhook receives invalid signature → 400 error
- [ ] Run give:premium command → User upgraded
- [ ] Run revoke:premium command → User downgraded
- [ ] Run promote:admin command → User gains ROLE_ADMIN
- [ ] Run revoke:admin command → User loses ROLE_ADMIN
- [ ] Premium user displays with golden styling

---

## Common Issues

### Issue: Routes not exposed to JavaScript

**Solution**: Check `fos_js_routing.yaml` configuration, clear cache:

```bash
php bin/console cache:clear
```

### Issue: Stripe webhook signature verification fails

**Solution**: Check `STRIPE_WEBHOOK_SECRET` in `.env`, use Stripe CLI for local testing:

```bash
stripe listen --forward-to localhost/webhook/stripe
```

### Issue: Validation groups not applied

**Solution**: Ensure `Security` service injected in `PublicationType` constructor.

### Issue: Delete button visible to non-authors

**Solution**: Check Twig condition: `{% if publication.auteur == app.user %}`

---

## Performance Optimization (Optional)

- Add indexes on `user.premium` if filtering premium users frequently
- Cache Stripe session objects to reduce API calls
- Use Symfony Profiler to identify slow queries

---

## Next Steps

After completing TD3:
1. Test all features manually
2. Commit changes to Git
3. Review code for adherence to constitution
4. Prepare for TD4 (if applicable)

---

## Summary

**Implementation Time**: ~2-3 hours
**Features Implemented**: 10 (AJAX delete, premium field, validation groups, Stripe checkout, webhook, 4 CLI commands, UI styling)
**Files Modified**: ~15
**Files Created**: ~10

All TD3 requirements implemented per specification and constitution.
