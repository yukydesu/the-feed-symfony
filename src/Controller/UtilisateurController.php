<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Form\UtilisateurType;
use App\Service\FlashMessageHelperInterface;
use App\Service\UtilisateurManagerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class UtilisateurController extends AbstractController
{
    #[Route('/inscription', name: 'inscription', methods: ['GET', 'POST'])]
    public function inscription(
        Request $request,
        EntityManagerInterface $entityManager,
        UtilisateurManagerInterface $utilisateurManager,
        FlashMessageHelperInterface $flashMessageHelper
    ): Response
    {
        // Si l'utilisateur est déjà connecté, on le redirige vers la page principale
        if ($this->isGranted('ROLE_USER')) {
            return $this->redirectToRoute('feed');
        }

        $utilisateur = new Utilisateur();
        $form = $this->createForm(UtilisateurType::class, $utilisateur, [
            'method' => 'POST',
            'action' => $this->generateUrl('inscription')
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // Récupération des champs non mappés
            $plainPassword = $form['plainPassword']->getData();
            $fichierPhotoProfil = $form['fichierPhotoProfil']->getData();

            // Traitement de l'utilisateur (hachage mot de passe + sauvegarde photo)
            $utilisateurManager->processNewUtilisateur($utilisateur, $plainPassword, $fichierPhotoProfil);

            // Enregistrement en base
            $entityManager->persist($utilisateur);
            $entityManager->flush();

            // Message de succès et redirection
            $this->addFlash('success', 'Inscription réussie !');
            return $this->redirectToRoute('feed');
        }

        // Gestion des erreurs du formulaire
        if ($form->isSubmitted() && !$form->isValid()) {
            $flashMessageHelper->addFormErrorsAsFlash($form);
        }

        return $this->render('utilisateur/inscription.html.twig', [
            'formulaireUtilisateur' => $form
        ]);
    }

    #[Route('/connexion', name: 'connexion', methods: ['GET', 'POST'])]
    public function connexion(AuthenticationUtils $authenticationUtils): Response
    {
        // Si l'utilisateur est déjà connecté, on le redirige vers la page principale
        if ($this->isGranted('ROLE_USER')) {
            return $this->redirectToRoute('feed');
        }

        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('utilisateur/connexion.html.twig', [
            'last_username' => $lastUsername
        ]);
    }

    #[Route('/utilisateurs/{login}/publications', name: 'pagePerso', methods: ['GET'])]
    public function pagePerso(?Utilisateur $utilisateur): Response
    {
        if ($utilisateur == null) {
            $this->addFlash('error', 'Utilisateur inexistant');
            return $this->redirectToRoute('feed');
        }

        return $this->render('utilisateur/page_perso.html.twig', [
            'utilisateur' => $utilisateur
        ]);
    }
}
