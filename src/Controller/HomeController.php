<?php

namespace App\Controller;


use Doctrine\ORM\EntityManagerInterface;
use App\Controller\EntityManagementInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Form\ContactType;
use App\Service\Meteo;
use App\Form\ProductsType;
use App\Entity\Products;

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
    public function boutique(EntityManagerInterface $em)
    {
        $repo = $em->getRepository(Products::class);
        $product = $repo->findAll();

        return $this->render('boutique.html.twig', [
            'listProduct' => $product
        ]);
    }

    #[Route('/panier', name: 'app_panier')]
    public function panier(): Response
    {
        return $this->render('panier.html.twig', [
            'controller_name' => 'HomeController',
        ]);
    }

    #[Route('/ajouter', name: 'app_ajouter')]
    public function ajouter(Request $request, EntityManagerInterface $em)
    {
        $product = new Products();

        $form = $this->createForm(ProductsType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($product);
            $em->flush();
            $this->addFlash('success', 'Nouveau produit ajouté avec succès');

            return $this->redirectToRoute('app_ajouter');
        }
        return $this->render('ajouter.html.twig', [
            'form' => $form,
        ]);
    }

    #[route('/modif/{id}', name: 'app_modif')]
    public function modif(Request $request, EntityManagerInterface $em, $id)
    {
        $p = $em->getRepository(Products::class)->find($id);

        $form = $this->createForm(ProductsType::class, $p);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            $em->flush();
            $this->addFlash(
                'success',
                'Article modifié avec succès'
            );

            return $this->redirectToRoute('app_boutique');
        }
        
         return $this->render("modifier.html.twig", [
            "form" => $form,
        ]);
    }

    #[route('/delete/{id}', name: 'app_delete')]
    public function delete(EntityManagerInterface $em, $id)
    {
        $p = $em->getRepository(Products::class)->find($id);
        $em->remove($p);
        $em->flush();
        $this->addFlash(
                'success',
                'Article supprimé avec succès'
            );

        return $this->redirectToRoute('app_boutique');
    }

    #[Route('/infos', name: 'app_infos')]
    public function infos(Request $request, Meteo $meteo): Response
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

        $weather = null;

        try {
            $weather = $meteo->getWeather('Nieul-sur-Mer');
        } catch (\Throwable $e) {}

        return $this->render('infos.html.twig', [
            'form' => $form,
            'weather' => $weather,
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

    #[Route('/messages/delete/{index}', name: 'app_deleteMessage')]
    public function deleteMessage(int $index): Response
    {
        $file = $this->getParameter('kernel.project_dir') . '/var/messages/contact.json';

        if (!file_exists($file)) {
            return $this->redirectToRoute('app_messages');
        }

        $messages = json_decode(file_get_contents($file), true);

        // on supprime l’élément s’il existe
        if (isset($messages[$index])) {
            unset($messages[$index]);
            $messages = array_values($messages); // réindexation
            file_put_contents($file, json_encode($messages, JSON_PRETTY_PRINT));
        }

        return $this->redirectToRoute('app_messages');
    }


}
