<?php

namespace App\Service;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class FlashMessageHelper implements FlashMessageHelperInterface
{
    public function __construct(
        private RequestStack $requestStack
    ) {}

    public function addFormErrorsAsFlash(FormInterface $form): void
    {
        $errors = $form->getErrors(true);
        $flashBag = $this->requestStack->getSession()->getFlashBag();
        
        foreach ($errors as $error) {
            $flashBag->add('error', $error->getMessage());
        }
    }
}
