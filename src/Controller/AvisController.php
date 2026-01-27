<?php

namespace App\Controller;

use App\Document\Avis;
use App\Form\AvisType;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AvisController extends AbstractController
{
    #[Route('/add-avis', name: 'app_add_avis')]
    public function add(Request $request, DocumentManager $dm): Response
    {
        $avis = new Avis();
        $form = $this->createForm(AvisType::class, $avis);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $avis->setCreatedAt(new \DateTime('now', new \DateTimeZone('Europe/Paris')));
            $dm->persist($avis);
            $dm->flush();

            $this->addFlash('success', 'Votre avis a bien été enregistré.');
            return $this->redirectToRoute('app_avis_list');
        }

        return $this->render('avis/add.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/avis', name: 'app_avis_list')]
    public function avis_list(DocumentManager $dm): Response
    {
        // Récupère tous les avis avec findAll
        $avisList = $dm->getRepository(Avis::class)->findBy([], ['createdAt' => 'DESC']);

        // Passe la liste des avis à la vue Twig
        return $this->render('avis/list.html.twig', [
            'avis' => $avisList,
        ]);
    }
}
