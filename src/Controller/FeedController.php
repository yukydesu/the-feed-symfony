<?php

namespace App\Controller;

use App\Entity\Publication;
use App\Form\PublicationType;
use App\Repository\PublicationRepository;
use App\Service\FlashMessageHelperInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class FeedController extends AbstractController
{
    #[Route('/', name: 'feed', methods: ["GET", "POST"])]
    public function feed(
        Request $request,
        EntityManagerInterface $entityManager,
        PublicationRepository $publicationRepository,
        FlashMessageHelperInterface $flashMessageHelper
    ): Response
    {
        // Sécurisation : seuls les utilisateurs connectés peuvent créer une publication
        if ($request->isMethod('POST')) {
            $this->denyAccessUnlessGranted('ROLE_USER');
        }

        // Création du formulaire
        $publication = new Publication();
        $form = $this->createForm(PublicationType::class, $publication, [
            'method' => 'POST',
            'action' => $this->generateUrl('feed')
        ]);

        // Traitement du formulaire
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // Affecter l'utilisateur connecté comme auteur
            $utilisateur = $this->getUser();
            $publication->setAuteur($utilisateur);

            $entityManager->persist($publication);
            $entityManager->flush();

            // Redirection pour éviter la resoumission
            return $this->redirectToRoute('feed');
        }

        // Si le formulaire a été soumis mais n'est pas valide, on affiche les erreurs
        if ($form->isSubmitted() && !$form->isValid()) {
            $flashMessageHelper->addFormErrorsAsFlash($form);
        }

        // Récupération de toutes les publications
        $publications = $publicationRepository->findAllOrderedByDate();

        return $this->render('feed.html.twig', [
            'formulairePublication' => $form,
            'publications' => $publications
        ]);
    }
}
