<?php

namespace App\Controller;


use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Form\ContactType;
use App\Service\Meteo;
use App\Form\ProductsType;
use App\Entity\Products;
use App\Entity\Order;
use App\Entity\OrderProducts;
use App\Repository\OrderRepository;
use Symfony\Component\HttpFoundation\JsonResponse;

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
    

    /* ------------------------------------ Boutique ------------------------------------ */



    #[Route('/boutique', name: 'app_boutique')]
    public function boutique(EntityManagerInterface $em)
    {
        $repo = $em->getRepository(Products::class);
        $product = $repo->findAll();

        return $this->render('boutique.html.twig', [
            'listProduct' => $product
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

    /* ------------------------------------ Panier ------------------------------------ */

    
    #[Route('/panier', name: 'app_panier')]
    public function panier(OrderRepository $or): Response
    {

        $user = $this->getUser();
        $panier = $or->findCartByUser($user);

        return $this->render('panier.html.twig', [
            'panier' => $panier,
        ]);
    }

    #[Route('/panier/add/{id}', name: 'app_panierAdd')]
    public function panierAdd(Products $p, OrderRepository $or, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();

        // 1️⃣ Récupérer le panier ou créer
        $order = $or->findCartByUser($user);

        if (!$order) {
            $order = new Order();
            $order->setUser($user);
            $order->setStatus('cart');
            $order->setDate(new \DateTimeImmutable());
            $order->setTotalQuantity(0);
            $order->setTotalPrice(0);
            $em->persist($order);
        }

        // 2️⃣ Chercher si le produit est déjà dans le panier
        $found = false;
        foreach ($order->getOrderProducts() as $op) {
            if ($op->getProducts() === $p) {
                $op->setQuantity($op->getQuantity() + 1);
                $found = true;
                break;
            }
        }

        // 3️⃣ Sinon créer la ligne dans OrderProducts
        if (!$found) {
            $op = new OrderProducts();
            $op->setOrderRef($order); // lien vers Order
            $op->setProducts($p);     // lien vers Product
            $op->setQuantity(1);
            $op->setPriceUnit($p->getPrice());
            $em->persist($op);
            $order->addOrderProduct($op); // ajoute aussi dans la collection
        }

        // 4️⃣ Mettre à jour totals
        $totalQuantity = 0;
        $totalPrice = "0.00";
        foreach ($order->getOrderProducts() as $op) {
            $quantity = (string)$op->getQuantity();
            $priceUnit = (string)$op->getPriceUnit(); // ⚠️ utiliser price_unit
            $lineTotal = bcmul($priceUnit, $quantity, 2);
            $totalPrice = bcadd($totalPrice, $lineTotal, 2);
            $totalQuantity += $op->getQuantity();
        }
        $order->setTotalQuantity($totalQuantity);
        $order->setTotalPrice($totalPrice);

        // 5️⃣ Flush
        $em->flush();

        // 6️⃣ Rediriger vers boutique
        return $this->redirectToRoute('app_boutique');
    }   
    

    #[Route('/panier/update/{id}', name: 'panier_update', methods: ['POST'])]
    public function update(OrderProducts $op, Request $r, EntityManagerInterface $em): JsonResponse
    {
        $action = $r->request->get('action');
        $order =$op->getOrderRef();

        $removeLine = false;

        if ($action === 'increase') {
            $op->setQuantity($op->getQuantity() + 1);
        } elseif ($action === 'decrease') {            
            if ($op->getQuantity() > 1) {
                $op->setQuantity($op->getQuantity() - 1);
            } else {
                $removeLine = true;
                $em->remove($op);
            }
        } elseif ($action === 'remove') {
            $removeLine = true;
            $em->remove($op);
        }

        $em->flush();

        // Mettre à jour totals
        $totalQuantity = 0;
        $totalPrice = "0.00";
        foreach ($order->getOrderProducts() as $line) {
            $quantity = (string)$line->getQuantity();
            $priceUnit = (string)$line->getPriceUnit();
            $lineTotal = bcmul($priceUnit, $quantity, 2);
            $totalPrice = bcadd($totalPrice, $lineTotal, 2);
            $totalQuantity += (int)$quantity;
        }
        $order->setTotalQuantity($totalQuantity);
        $order->setTotalPrice($totalPrice);

        $em->flush();

        

    
        

        // Vérifier si le panier est vide
        if ($order->getOrderProducts()->isEmpty()) {
            $em->remove($order);
            $em->flush();
        
            return $this->json([
                'quantity' => 0,
                'lineTotal' => '0.00',
                'totalQuantity' => 0,
                'totalPrice' => '0.00'
            ]);
        }


        return $this->json([
            'quantity' => $removeLine ? 0 : $op->getQuantity(),
            'lineTotal' => $removeLine ? '0.00' : number_format((float)$op->getQuantity() * (float)$op->getPriceUnit(), 2, '.', ','),
            'totalQuantity' => $order->getTotalQuantity(),
            'totalPrice' => number_format($order->getTotalPrice(), 2, '.', ',')
        ]);
    }



    /* ------------------------------------ Infos/Contact ------------------------------------ */



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
