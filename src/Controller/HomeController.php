<?php

namespace App\Controller;


use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Service\Meteo;
use App\Form\ContactType;
use App\Form\ProductsType;
use App\Form\PhotoType;
use App\Entity\Products;
use App\Entity\Order;
use App\Entity\OrderProducts;
use App\Entity\User;
use App\Entity\Photo;
use App\Repository\OrderRepository;
use App\Repository\PhotoRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Stripe\StripeClient;
use Stripe\Checkout\Session;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Psr\Log\LoggerInterface;
use App\Controller\PanierController;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Document\Avis;
use App\Form\AvisType;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Contracts\HttpClient\HttpClientInterface;


final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_accueil')]
    public function accueil(DocumentManager $dm): Response
    {
        $avisList = $dm->getRepository(Avis::class)->findBy([], ['createdAt' => 'DESC'], 5);

        return $this->render('accueil.html.twig', [
            'controller_name' => 'HomeController',
            'avis' => $avisList,
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

    /* ------------------------------------ Photos ------------------------------------ */


    #[Route('/photos', name: 'app_photos')]
    public function photos(Request $r, PhotoRepository $pr): Response
    {
        $category = $r->query->get('category');

        if ($category) {
            $photos = $pr->findBy(['category' => $category]);
        } else {
            $photos = $pr->findAll();
        }

        $categories = ['Couleur' => 'Couleur', 'Coupe' => 'Coupe', 'Event' => 'Event'];

        
        return $this->render('photos.html.twig', [
            'photos' => $photos,
            'categories' => $categories,
            'selected' => $category,
        ]);
    }
    
    #[Route('/photos/add', name: 'app_photosAdd')]
    #[IsGranted('ROLE_ADMIN')]
    public function photosAdd(Request $r, EntityManagerInterface $em): Response
    {
        $photo = new Photo();
        $form = $this->createForm(PhotoType::class, $photo);
        $form->handleRequest($r);

        if ($form->isSubmitted() && $form->isValid()) {

            /** @var UploadedFile $file */
            $file = $form->get('imageFile')->getData();
            if ($file) {
                $fileName = uniqid().'.'.$file->guessExtension();

                $file->move(
                    $this->getParameter('photos_directory'),
                    $fileName
                );

                // URL à enregistrer en base
                $photo->setUrl('/images/photos/'.$fileName);
            }

            $em->persist($photo);
            $em->flush();

            $this->addFlash('success', 'Photo ajoutée avec succès!');
            return $this->redirectToRoute('app_photosAdd');
        }

        

        return $this->render('photosAdd.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[route('/photo/delete/{id}', name: 'app_deletePic')]
    public function deletePic(Photo $photo, EntityManagerInterface $em)
    {
        $filePath = $this->getParameter('kernel.project_dir') . '/public' . $photo->getUrl();
        
        if (file_exists($filePath)) {
            unlink($filePath);
        }

        $em->remove($photo);
        $em->flush();
        $this->addFlash(
                'success',
                'Photo supprimé avec succès'
            );

        return $this->redirectToRoute('app_photos');
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
    #[IsGranted('ROLE_ADMIN')]
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
    public function delete(int $id, EntityManagerInterface $em)
    {
        $p = $em->getRepository(Products::class)->find($id);
         if (!$p) {
            throw $this->createNotFoundException('Produit non trouvé');
        }
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
    #[IsGranted('ROLE_USER')]
    public function panier(OrderRepository $or, Request $r, EntityManagerInterface $em): Response
    {

        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $panier = $or->findCartByUser($user);

        $addressData = [
            'address' => $user->getAddress(),
            'postal' => $user->getPostal(),
            'city' => $user->getCity(),
        ];

        $form = $this->createFormBuilder($addressData)
            ->add('address', null, [
                'label' => 'Rue / voie',
                'attr' => ['class' => 'input']])
            ->add('postal', null, [
                'label' => 'Code postal',
                'attr' => ['class' => 'input']])
            ->add('city', null, [
                'label' => 'Ville',
                'attr' => ['class' => 'input']])
            ->getForm();

        $form->handleRequest($r);
    

        return $this->render('panier.html.twig', [
            'panier' => $panier,
            'addressForm' => $form->createView(),
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
            $lineTotal = \bcmul($priceUnit, $quantity, 2);
            $totalPrice = \bcadd($totalPrice, $lineTotal, 2);
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


    /* ------------------------------------ Historique ------------------------------------ */




    #[Route('/history', name: 'app_history')]
    #[IsGranted('ROLE_USER')]
    public function history(OrderRepository $or): Response
    {

        $user = $this->getUser();
        $paidOrders = $or->findPaidByUser($user);

        return $this->render('historique.html.twig', [
            'paidOrders' => $paidOrders,
        ]);
    }




    /* ------------------------------------ Paiement ------------------------------------ */

    

    #[Route('/panier/create-session', name: 'app_create_stripe_session', methods: ['POST'])]
    public function createStripeSession(OrderRepository $or): JsonResponse
    {
        $user = $this->getUser();
        $panier = $or->findCartByUser($user);

        $stripe = new StripeClient($_ENV['STRIPE_SECRET_KEY']);

        $session = $stripe->checkout->sessions->create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'eur',
                    'unit_amount' => (int) bcmul($panier->getTotalPrice(), '100'),
                    'product_data' => [
                        'name' => 'Commande numéro:' . $panier->getId(),
                        'description' => 'Commande sécurisé par carte bancaire',
                    ],
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => $this->generateUrl('app_success', [], UrlGeneratorInterface::ABSOLUTE_URL),
            'cancel_url' => $this->generateUrl('app_cancel', [], UrlGeneratorInterface::ABSOLUTE_URL),
            'metadata' => [
                'order_id' => $panier->getId(), // <-- AJOUTÉ
            ],

        ]);

        return $this->json(['id' => $session->id]);
    }

    #[Route('/panier/success', name: 'app_success')]
    public function success(): Response
    {
        return $this->render('success.html.twig');
    }

    #[Route('/panier/cancel', name: 'app_cancel')]
    public function cancel(): Response
    {
        return $this->redirectToRoute('app_panier');
    }

    #[Route('/stripe/webhook', name: 'stripe_webhook', methods: ['POST'])]
    public function webhook(Request $r, EntityManagerInterface $em, LoggerInterface $logger): Response {
        \Stripe\Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']);

        $payload = $r->getContent();
        $sigHeader = $r->headers->get('stripe-signature');

        try {
            $event = \Stripe\Webhook::constructEvent(
                $payload,
                $sigHeader,
                $_ENV['STRIPE_WEBHOOK_SECRET']
            );

            if ($event->type === 'checkout.session.completed') {
                $session = $event->data->object;
                $orderId = $session->metadata->order_id;

                if ($orderId) {
                    $order = $em->getRepository(Order::class)->find($orderId);

                    if ($order && $order->getStatus() !== 'paid') {
                        $order->setStatus('paid');

                        foreach ($order->getOrderProducts() as $item) {
                            $product = $item->getProducts();
                            $product->setStock(
                                $product->getStock() - $item->getQuantity()
                            );
                        }

                        $em->flush();
                    }
                }

            }
        }   catch(\Throwable $e) {
                $logger->error('Stripe webhook error: ' .$e->getMessage());
            }

        return new Response('OK');
    }

    /* ------------------------------------ Infos/Contact ------------------------------------ */



    #[Route('/infos', name: 'app_infos')]
    public function infos(Request $request, Meteo $meteo, HttpClientInterface $client): Response
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
            // $weather = $client->request(
            // 'GET',
            // 'https://www.prevision-meteo.ch/services/json/Nieul-sur-Mer');
            // var_dump($weather);
            // die("stop");
        } catch (\Throwable $e) {}

        return $this->render('infos.html.twig', [
            'form' => $form,
            'weather' => $weather,
        ]);
    }


        /* ------------------------------------ Messages ------------------------------------ */


    #[Route('/messages', name: 'app_messages')]
    #[IsGranted('ROLE_ADMIN')]
    public function messages(): Response
    {
        $file = $this->getParameter('kernel.project_dir') . '/var/messages/contact.json';
        
        if (file_exists($file)) {
            $messages = json_decode(file_get_contents($file), true);
            $messages = array_reverse($messages);
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
