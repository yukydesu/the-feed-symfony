<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Http\Event\LoginFailureEvent;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;
use Symfony\Component\Security\Http\Event\LogoutEvent;

class AuthenticationEventListener
{
    public function __construct(private RequestStack $requestStack) {}

    #[AsEventListener]
    public function onLoginSuccess(LoginSuccessEvent $event): void
    {
        $flashBag = $this->requestStack->getSession()->getFlashBag();
        $flashBag->add('success', 'Connexion réussie !');
    }

    #[AsEventListener]
    public function onLoginFailure(LoginFailureEvent $event): void
    {
        $flashBag = $this->requestStack->getSession()->getFlashBag();
        $flashBag->add('error', 'Login et/ou mot de passe incorrect !');
    }

    #[AsEventListener]
    public function onLogout(LogoutEvent $event): void
    {
        $flashBag = $this->requestStack->getSession()->getFlashBag();
        $flashBag->add('success', 'Déconnexion réussie !');
    }
}
