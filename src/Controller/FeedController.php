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
        // Création du formulaire
        $publication = new Publication();
        $form = $this->createForm(PublicationType::class, $publication, [
            'method' => 'POST',
            'action' => $this->generateUrl('feed')
        ]);

        // Traitement du formulaire
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
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
        $publications = $publicationRepository->findAll();

        return $this->render('feed.html.twig', [
            'formulairePublication' => $form,
            'publications' => $publications
        ]);
    }
}
