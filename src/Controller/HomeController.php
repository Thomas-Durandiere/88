<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Form\ContactType;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_accueil')]
    public function accueil(): Response
    {
        return $this->render('accueil.html.twig', [
            'controller_name' => 'HomeController',
        ]);
    }

    #[Route('/mentionsLegales', name: 'app_mentions')]
    public function mentions(): Response
    {
        return $this->render('mentionsLegales.html.twig', [
            'controller_name' => 'HomeController',
        ]);
    }

    #[Route('/cgu', name: 'app_cgu')]
    public function cgu(): Response
    {
        return $this->render('CGU.html.twig', [
            'controller_name' => 'HomeController',
        ]);
    }

    #[Route('/prestaions', name: 'app_prestations')]
    public function prestations(): Response
    {
        return $this->render('prestations.html.twig', [
            'controller_name' => 'HomeController',
        ]);
    }
    
    #[Route('/photos', name: 'app_photos')]
    public function photos(): Response
    {
        return $this->render('photos.html.twig', [
            'controller_name' => 'HomeController',
        ]);
    }
    
    #[Route('/boutique', name: 'app_boutique')]
    public function boutique(): Response
    {
        return $this->render('boutique.html.twig', [
            'controller_name' => 'HomeController',
        ]);
    }

    #[Route('/infos', name: 'app_infos')]
    public function infos(Request $request): Response
    {
        $form = $this->createForm(ContactType::class, null, [
            'attr' => [
                'id' => 'contact'
            ]
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $message = [
                'nom' => $data['nom'],
                'prenom' => $data['prenom'],
                'email' => $data['email'],
                'message' => $data['message'],
                'date' => (new \DateTime('now', new \DateTimeZone('Europe/Paris')))->format('Y-M-d H:i:s')
            ];

            $file = $this->getParameter('kernel.project_dir') . '/var/messages/contact.json';

            if (!file_exists($file)) {
                file_put_contents($file, json_encode([]));
}

            $messages = json_decode(file_get_contents($file), true);

            $messages[] = $message;

            file_put_contents($file, json_encode($messages, JSON_PRETTY_PRINT));

            $this->addFlash('success', 'Message envoyé avec succès !');

            return $this->redirectToRoute('app_infos');
        }
        return $this->render('infos.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/messages', name: 'app_messages')]
    public function messages(): Response
    {
        $file = $this->getParameter('kernel.project_dir') . '/var/messages/contact.json';
        
        if (file_exists($file)) {
            $messages = json_decode(file_get_contents($file), true);    
        } else {
            $messages = [];
        }

        return $this->render('messages.html.twig', [
            'messages' => $messages
        ]);
}

    
}
