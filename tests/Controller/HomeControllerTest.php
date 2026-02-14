<?php

namespace App\Tests\Controller;

use App\Service\Meteo;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\Entity\Photo;
use App\Entity\User;
use App\Entity\Products;
use App\Entity\Order;
use App\Entity\OrderProducts;
use App\Form\PhotoType;
use App\Repository\PhotoRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class HomeControllerTest extends WebTestCase
{
    public function testInfosPage(): void
    {
        // Crée un client Symfony
        $client = static::createClient();

        // Mock du service Meteo pour ne pas appeler l'API réelle
        $meteoMock = $this->createMock(Meteo::class);
        $meteoMock->method('getWeather')->willReturn([
            'weather' => [['description' => 'ciel dégagé']],
            'main' => ['temp' => 20],
            'name' => 'Nieul-sur-Mer',
            'wind' => ['speed' => 5.5],
        ]);

        // Remplacer le service réel par le mock
        $client->getContainer()->set(Meteo::class, $meteoMock);

        // Faire la requête GET sur /infos
        $crawler = $client->request('GET', '/infos');

        // Vérifie que la page est accessible (200)
        $this->assertResponseIsSuccessful();

        // Vérifie que le formulaire contact est présent
        $this->assertSelectorExists('form#contact');

        // Vérifie le contenu météo dans les <p> de la div .meteo
        $description = $crawler->filter('.meteo p')->eq(1)->text();
        $this->assertStringContainsString('Ciel dégagé', $description);

        $temp = $crawler->filter('.meteo p')->eq(0)->text();
        $this->assertStringContainsString('20', $temp);

        $wind = $crawler->filter('.meteo p')->eq(2)->text();
        $this->assertStringContainsString('5.5', $wind);
    }

    public function testContactMessagesFlow(): void
    {
        $client = static::createClient();

        // Faire une requête GET pour récupérer le formulaire
        $crawler = $client->request('GET', '/infos');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form#contact');

        // Remplir le formulaire
        $form = $crawler->selectButton('Envoyer')->form([
            'contact[nom]' => 'Dupont',
            'contact[prenom]' => 'Jean',
            'contact[email]' => 'jean.dupont@example.com',
            'contact[message]' => 'Bonjour, test PHPUnit !',
        ]);

        // Soumettre le formulaire
        $client->submit($form);

        // Suivre le redirect
        $crawler = $client->followRedirect();

        // Vérifier que le flash message apparaît
        $this->assertSelectorExists('.alert.darkMode');
        $this->assertSelectorTextContains('.alert.darkMode span', 'Message envoyé avec succès !');

        // Vérifier que le message a bien été ajouté dans le fichier JSON
        $file = static::getContainer()->getParameter('kernel.project_dir') . '/var/messages/contact.json';
        $this->assertFileExists($file);

        $messages = json_decode(file_get_contents($file), true);
        $this->assertNotEmpty($messages);

        // Vérifier le dernier message
        $lastMessage = end($messages);
        $this->assertSame('Dupont', $lastMessage['nom']);
        $this->assertSame('Jean', $lastMessage['prenom']);
        $this->assertSame('jean.dupont@example.com', $lastMessage['email']);
        $this->assertSame('Bonjour, test PHPUnit !', $lastMessage['message']);
    }

    //--------------------------- Message ----------------------------


    public function testMessagesPageDisplaysMessages(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $em = static::getContainer()->get('doctrine')->getManager();

        $user = new \App\Entity\User();
        $user->setEmail('user_' . uniqid() . '@test.fr');
        $user->setPassword('TestAt6a3jkd!'); // ou hashé si nécessaire
        $user->setName('Test');
        $user->setFirstname('User');
        $user->setAddress('10 rue de Paris');
        $user->setPostal('75001');
        $user->setCity('Paris');
        $user->setPhone('0102030405');
        $user->setRoles(['ROLE_ADMIN']);               

        $em->persist($user);
        $em->flush();

        // Connecter l’utilisateur
        $client->loginUser($user);

        // Préparer un fichier JSON de test
        $file = $container->getParameter('kernel.project_dir') . '/var/messages/contact.json';
        $testMessages = [
            ['nom' => 'Test', 'prenom' => 'User', 'email' => 'test@example.com', 'message' => 'Hello', 'date' => '2026-01-09 12:00:00'],
        ];
        file_put_contents($file, json_encode($testMessages, JSON_PRETTY_PRINT));

        // Accéder à la page /messages
        $crawler = $client->request('GET', '/messages');
        $this->assertResponseIsSuccessful();

        // Vérifier que le message est bien affiché
        $this->assertSelectorTextContains('body', 'Hello');

        // Nettoyer le fichier pour éviter les interférences
        unlink($file);
    }

    public function testDeleteMessageRemovesMessageAndRedirects(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $em = static::getContainer()->get('doctrine')->getManager();

        $user = new \App\Entity\User();
        $user->setEmail('user_' . uniqid() . '@test.fr');
        $user->setPassword('TestAt6a3jkd!'); // ou hashé si nécessaire
        $user->setName('Test');
        $user->setFirstname('User');
        $user->setAddress('10 rue de Paris');
        $user->setPostal('75001');
        $user->setCity('Paris');
        $user->setPhone('0102030405');
        $user->setRoles(['ROLE_ADMIN']);               

        $em->persist($user);
        $em->flush();

        // Connecter l’utilisateur
        $client->loginUser($user);

        // Préparer un fichier JSON de test
        $file = $container->getParameter('kernel.project_dir') . '/var/messages/contact.json';
        $testMessages = [
            ['nom' => 'Test', 'prenom' => 'User', 'email' => 'test@example.com', 'message' => 'Hello', 'date' => '2026-01-09 12:00:00'],
            ['nom' => 'Another', 'prenom' => 'User2', 'email' => 'another@example.com', 'message' => 'Bye', 'date' => '2026-01-09 12:05:00'],
        ];
        file_put_contents($file, json_encode($testMessages, JSON_PRETTY_PRINT));

        // Supprimer le premier message
        $client->request('GET', '/messages/delete/0');

        // Vérifier la redirection
        $this->assertResponseRedirects('/messages');

        // Suivre la redirection
        $crawler = $client->followRedirect();
        $this->assertResponseIsSuccessful();

        // Vérifier que le fichier JSON ne contient plus le premier message
        $messagesAfter = json_decode(file_get_contents($file), true);
        $this->assertCount(1, $messagesAfter);
        $this->assertSame('Bye', $messagesAfter[0]['message']);

        // Nettoyer le fichier
        unlink($file);
    }


    //--------------------------- Static ----------------------------


    public function testStaticPages(): void
    {
        $client = static::createClient();
        $client->disableReboot(); // <--- c’est le truc magique
        $container = $client->getContainer();
        $dmMock = $this->createMock(\Doctrine\ODM\MongoDB\DocumentManager::class);
        $container->set(\Doctrine\ODM\MongoDB\DocumentManager::class, $dmMock);


        $pages = [
            '/mentionsLegales' => 'Mentions légales',
            '/cgu' => 'CGU',
            '/prestations' => 'Prestations',
        ];

        foreach ($pages as $url => $title) {
            $crawler = $client->request('GET', $url);

            // Vérifie que la page répond bien
            $this->assertResponseIsSuccessful();

            // Vérifie que le titre attendu est présent
            $this->assertSelectorTextContains('body', $title);
        }
    }




    //--------------------------- Photo ----------------------------

    public function testPhotosPage(): void
    {
        $client = static::createClient();

        // Mock du repository pour findAll et findBy
        $photoMock = $this->createMock(PhotoRepository::class);
        $photoMock->method('findAll')->willReturn([]);
        $photoMock->method('findBy')->willReturn([]);

        $client->getContainer()->set(PhotoRepository::class, $photoMock);

        // 1️⃣ Test sans catégorie
        $crawler = $client->request('GET', '/photos');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form'); // Vérifie qu'un formulaire de filtre ou ajout est présent

        // 2️⃣ Test avec catégorie
        $crawler = $client->request('GET', '/photos?category=Couleur');
        $this->assertResponseIsSuccessful();
    }

    public function testPhotosAddPage(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get('doctrine')->getManager();

        // Créer un utilisateur avec ROLE_USER
        $user = new \App\Entity\User();
        $user->setEmail('user_' . uniqid() . '@test.fr');
        $user->setPassword('TestAt6a3jkd!'); // ou hashé si nécessaire
        $user->setName('Test');
        $user->setFirstname('User');
        $user->setAddress('10 rue de Paris');
        $user->setPostal('75001');
        $user->setCity('Paris');
        $user->setPhone('0102030405');
        $user->setRoles(['ROLE_ADMIN']);               

        $em->persist($user);
        $em->flush();

        // Connecter l’utilisateur
        $client->loginUser($user);

        // Accéder à la page protégée
        $crawler = $client->request('GET', '/photos/add');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form'); // Vérifie que le formulaire est présent
    }

    public function testDeletePicRoute(): void
    {
        $client = static::createClient();

        // Crée un Photo en base pour tester
        $em = $client->getContainer()->get('doctrine')->getManager();
        $photo = new \App\Entity\Photo();
        $photo->setName('Test')->setTitle('Test')->setUrl('/test.jpg')->setAlt('Test')->setCategory('Couleur');
        $em->persist($photo);
        $em->flush();

        $id = $photo->getId();

        $client->request('GET', '/photo/delete/'.$id);

        $this->assertResponseRedirects('/photos');
    }

    public function testPhotosAddSubmit(): void
    {
        $client = static::createClient();
        $em = $client->getContainer()->get('doctrine')->getManager();

        // Créer un utilisateur avec ROLE_USER
        $user = new \App\Entity\User();
        $user->setEmail('user_' . uniqid() . '@test.fr');
        $user->setPassword('TestAt6a3jkd!'); // ou hashé si nécessaire
        $user->setName('Test');
        $user->setFirstname('User');
        $user->setAddress('10 rue de Paris');
        $user->setPostal('75001');
        $user->setCity('Paris');
        $user->setPhone('0102030405');
        $user->setRoles(['ROLE_ADMIN']);               

        $em->persist($user);
        $em->flush();

        // Connecter l’utilisateur
        $client->loginUser($user);

        // Créer un fichier temporaire simulant un upload
        $tmpFile = tempnam(sys_get_temp_dir(), 'upl');
        imagepng(imagecreatetruecolor(10, 10), $tmpFile); // créer une image 10x10
        $uploadedFile = new \Symfony\Component\HttpFoundation\File\UploadedFile(
            $tmpFile,
            'test.png',
            'image/png',
            null,
            true // test mode, ne fait pas de vérification réelle
        );

        // Récupérer le formulaire
        $crawler = $client->request('GET', '/photos/add');
        $form = $crawler->selectButton('Envoyer')->form(); // adapter le nom du bouton

        // Remplir le formulaire
        $form['photo[name]'] = 'Nom test';
        $form['photo[title]'] = 'Titre test';
        $form['photo[alt]'] = 'Alt test';
        $form['photo[category]'] = 'Couleur';
        $form['photo[imageFile]'] = $uploadedFile;

        $client->submit($form);

        // Vérifier la redirection après ajout
        $this->assertResponseRedirects('/photos/add');

        // Vérifier que la photo a été persistée
        $photo = $em->getRepository(\App\Entity\Photo::class)
            ->findOneBy(['name' => 'Nom test']);

        $this->assertNotNull($photo);
        $this->assertStringContainsString('/images/photos/', $photo->getUrl());

        // Nettoyage : supprimer le fichier temporaire
        @unlink($tmpFile);
    }
    


        //--------------------------- Boutique ----------------------------


    public function testAjouterProductPage(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get('doctrine')->getManager();

        $user = new \App\Entity\User();
        $user->setEmail('user_' . uniqid() . '@test.fr');
        $user->setPassword('TestAt6a3jkd!'); // ou hashé si nécessaire
        $user->setName('Test');
        $user->setFirstname('User');
        $user->setAddress('10 rue de Paris');
        $user->setPostal('75001');
        $user->setCity('Paris');
        $user->setPhone('0102030405');
        $user->setRoles(['ROLE_ADMIN']);               

        $em->persist($user);
        $em->flush();

        // Connecter l’utilisateur
        $client->loginUser($user);

        $crawler = $client->request('GET', '/ajouter');
        $this->assertResponseIsSuccessful();

        // Vérifier que le formulaire existe
        $this->assertSelectorExists('form');
    }

    public function testAjouterProductSubmit(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $em = static::getContainer()->get('doctrine')->getManager();

        $user = new \App\Entity\User();
        $user->setEmail('user_' . uniqid() . '@test.fr');
        $user->setPassword('TestAt6a3jkd!'); // ou hashé si nécessaire
        $user->setName('Test');
        $user->setFirstname('User');
        $user->setAddress('10 rue de Paris');
        $user->setPostal('75001');
        $user->setCity('Paris');
        $user->setPhone('0102030405');
        $user->setRoles(['ROLE_ADMIN']);               

        $em->persist($user);
        $em->flush();

        // Connecter l’utilisateur
        $client->loginUser($user);

        // Accéder à la page GET pour récupérer le formulaire
        $crawler = $client->request('GET', '/ajouter');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');

        // Récupérer le formulaire via le bouton submit
        $form = $crawler->selectButton('Ajouter')->form([
            'products[name]' => 'Test Product',
            'products[description]' => 'Description test',
            'products[pic]' => 'https://letsenhance.io/static/73136da51c245e80edc6ccfe44888a99/396e9/MainBefore.jpg',
            'products[price]' => '19.99',
            'products[stock]' => '10',
        ]);

        // Soumettre le formulaire
        $client->submit($form);

        // Vérifier la redirection après succès
        $this->assertResponseRedirects('/ajouter');

        // Suivre la redirection
        $crawler = $client->followRedirect();
        $this->assertResponseIsSuccessful();

        // Vérifier que le produit a bien été persisté dans la base de test
        /** @var \Doctrine\ORM\EntityManagerInterface $em */
        $em = $container->get(EntityManagerInterface::class);
        $product = $em->getRepository(\App\Entity\Products::class)
            ->findOneBy(['name' => 'Test Product']);

        $this->assertNotNull($product, 'Le produit doit exister en base après soumission du formulaire');
        $this->assertSame('Description test', $product->getDescription());
        $this->assertSame('https://letsenhance.io/static/73136da51c245e80edc6ccfe44888a99/396e9/MainBefore.jpg', $product->getPic());
        $this->assertSame('19.99', (string)$product->getPrice());
        $this->assertSame(10, $product->getStock());

        // Optionnel : nettoyer le produit pour ne pas polluer la DB test
        $em->remove($product);
        $em->flush();
    }



    public function testModifProduct(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get('doctrine')->getManager();
        
        $user = new User();
        $user->setEmail('user_' . uniqid() . '@test.fr');
        $user->setPassword('TestAt6a3jkd!');
        $user->setName('Doe');
        $user->setFirstname('John');
        $user->setAddress('1 rue test');
        $user->setPostal('75000');
        $user->setCity('Paris');
        $user->setPhone('0600000000');
        $user->setRoles(['ROLE_ADMIN']);

        $em->persist($user);
        $em->flush();

        $client->loginUser($user);

        $crawler = $client->request('GET', '/modif/1');
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Modifier')->form([
            'products[name]' => 'Produit test modifié',
            'products[description]' => 'Nouvelle description',
            'products[pic]' => 'https://example.com/image.jpg',
            'products[price]' => '12.34',
            'products[stock]' => '5',
        ]);

        $client->submit($form);
        $this->assertResponseRedirects('/boutique');

    }



    public function testDeleteProductController(): void
    {
        $product = new \App\Entity\Products();

        // Mock du repository
        $repo = $this->createMock(\Doctrine\ORM\EntityRepository::class);
        $repo->method('find')->willReturn($product);

        // Mock de l'EntityManager
        $em = $this->createMock(\Doctrine\ORM\EntityManagerInterface::class);
        $em->method('getRepository')->willReturn($repo);
        $em->expects($this->once())->method('remove')->with($product);
        $em->expects($this->once())->method('flush');

        // Crée un container et une session "mockée"
        $session = new Session(new MockArraySessionStorage());
        $request = new Request();
        $request->setSession($session);
        $requestStack = new RequestStack();
        $requestStack->push($request);

        $container = self::getContainer();
        $container->set('request_stack', $requestStack);

        $controller = new \App\Controller\HomeController();
        $controller->setContainer($container);

        // Maintenant addFlash() fonctionne
        $response = $controller->delete(1, $em);

        $this->assertInstanceOf(\Symfony\Component\HttpFoundation\RedirectResponse::class, $response);
        $this->assertStringContainsString('/boutique', $response->getTargetUrl());
    }


        /* ------------------------------------ Panier ------------------------------------ */



    public function testPanierPage(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get('doctrine')->getManager();

        $user = new User();
        $user->setEmail('user_' . uniqid() . '@test.fr');
        $user->setPassword('TestAt6a3jkd!');
        $user->setName('Doe');
        $user->setFirstname('John');
        $user->setAddress('1 rue test');
        $user->setPostal('75000');
        $user->setCity('Paris');
        $user->setPhone('0600000000');
        $user->setRoles(['ROLE_USER']);

        $em->persist($user);
        $em->flush();

        $client->loginUser($user);

        $client->request('GET', '/panier');

        $this->assertResponseIsSuccessful();
    }

    public function testAddProductCreatesCart(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get('doctrine')->getManager();

        $user = new User();
        $user->setEmail('user_' . uniqid() . '@test.fr');
        $user->setPassword('TestAt6a3jkd!');
        $user->setName('Doe');
        $user->setFirstname('John');
        $user->setAddress('1 rue test');
        $user->setPostal('75000');
        $user->setCity('Paris');
        $user->setPhone('0600000000');
        $user->setRoles(['ROLE_USER']);

        $product = new Products();
        $product->setName('Produit test');
        $product->setDescription('Description test');
        $product->setPic('https://letsenhance.io/static/73136da51c245e80edc6ccfe44888a99/396e9/MainBefore.jpg');
        $product->setPrice('10.00');
        $product->setStock('8');

        $em->persist($user);
        $em->persist($product);
        $em->flush();

        $client->loginUser($user);

        $client->request('GET', '/panier/add/' . $product->getId());

        $this->assertResponseRedirects();

        $order = $em->getRepository(Order::class)
            ->findOneBy(['user' => $user]);

        $this->assertNotNull($order);
        $this->assertEquals(1, $order->getTotalQuantity());
    }

    public function testPanierUpdateIncrease(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get('doctrine')->getManager();

        $user = new User();
        $user->setEmail('user_' . uniqid() . '@test.fr');
        $user->setPassword('TestAt6a3jkd!');
        $user->setName('Doe');
        $user->setFirstname('John');
        $user->setAddress('1 rue test');
        $user->setPostal('75000');
        $user->setCity('Paris');
        $user->setPhone('0600000000');
        $user->setRoles(['ROLE_USER']);

        $product = new Products();
        $product->setName('Produit test');
        $product->setDescription('Description test');
        $product->setPic('https://letsenhance.io/static/73136da51c245e80edc6ccfe44888a99/396e9/MainBefore.jpg');
        $product->setPrice('10.00');
        $product->setStock('8');

        $order = new Order();
        $order->setUser($user);
        $order->setStatus('cart');
        $order->setDate(new \DateTimeImmutable());
        $order->setTotalQuantity(1);
        $order->setTotalPrice('10.00');

        $op = new OrderProducts();
        $op->setOrderRef($order);
        $op->setProducts($product);
        $op->setQuantity(1);
        $op->setPriceUnit('10.00');

        $order->addOrderProduct($op);

        $em->persist($user);
        $em->persist($product);
        $em->persist($order);
        $em->persist($op);
        $em->flush();

        $client->loginUser($user);

        $client->request('POST', '/panier/update/' . $op->getId(), [
            'action' => 'increase'
        ]);

        $this->assertResponseIsSuccessful();

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(2, $data['quantity']);
    }

    public function testPanierUpdateDecrease(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get('doctrine')->getManager();

        $user = new User();
        $user->setEmail('user_' . uniqid() . '@test.fr');
        $user->setPassword('TestAt6a3jkd!');
        $user->setName('Doe');
        $user->setFirstname('John');
        $user->setAddress('1 rue test');
        $user->setPostal('75000');
        $user->setCity('Paris');
        $user->setPhone('0600000000');
        $user->setRoles(['ROLE_USER']);

        $product = new Products();
        $product->setName('Produit test');
        $product->setDescription('Description test');
        $product->setPic('https://letsenhance.io/static/73136da51c245e80edc6ccfe44888a99/396e9/MainBefore.jpg');
        $product->setPrice('10.00');
        $product->setStock('8');

        $order = new Order();
        $order->setUser($user);
        $order->setStatus('cart');
        $order->setDate(new \DateTimeImmutable());
        $order->setTotalQuantity(2);
        $order->setTotalPrice('20.00');

        $op = new OrderProducts();
        $op->setOrderRef($order);
        $op->setProducts($product);
        $op->setQuantity(2);
        $op->setPriceUnit('10.00');

        $order->addOrderProduct($op);

        $em->persist($user);
        $em->persist($product);
        $em->persist($order);
        $em->persist($op);
        $em->flush();

        $client->loginUser($user);

        // Décrémenter
        $client->request('POST', '/panier/update/' . $op->getId(), [
            'action' => 'decrease'
        ]);

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals(1, $data['quantity']);
        $this->assertEquals('10.00', $data['lineTotal']);
        $this->assertEquals(1, $data['totalQuantity']);
        $this->assertEquals('10.00', $data['totalPrice']);
    }

    public function testPanierUpdateRemove(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get('doctrine')->getManager();

        $user = new User();
        $user->setEmail('user_' . uniqid() . '@test.fr');
        $user->setPassword('TestAt6a3jkd!');
        $user->setName('Doe');
        $user->setFirstname('John');
        $user->setAddress('1 rue test');
        $user->setPostal('75000');
        $user->setCity('Paris');
        $user->setPhone('0600000000');
        $user->setRoles(['ROLE_USER']);

        $product = new Products();
        $product->setName('Produit test');
        $product->setDescription('Description test');
        $product->setPic('https://letsenhance.io/static/73136da51c245e80edc6ccfe44888a99/396e9/MainBefore.jpg');
        $product->setPrice('10.00');
        $product->setStock('8');

        $order = new Order();
        $order->setUser($user);
        $order->setStatus('cart');
        $order->setDate(new \DateTimeImmutable());
        $order->setTotalQuantity(1);
        $order->setTotalPrice('10.00');

        $op = new OrderProducts();
        $op->setOrderRef($order);
        $op->setProducts($product);
        $op->setQuantity(1);
        $op->setPriceUnit('10.00');

        $order->addOrderProduct($op);

        $em->persist($user);
        $em->persist($product);
        $em->persist($order);
        $em->persist($op);
        $em->flush();

        $orderId = $order->getId();

        $client->loginUser($user);

        // Supprimer
        $client->request('POST', '/panier/update/' . $op->getId(), [
            'action' => 'remove'
        ]);

        $this->assertResponseIsSuccessful();

        $data = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals(0, $data['quantity']);
        $this->assertEquals('0.00', $data['lineTotal']);
        $this->assertEquals(0, $data['totalQuantity']);
        $this->assertEquals('0.00', $data['totalPrice']);

        // On détache tous les objets pour éviter les problèmes avec l’EntityManager
        $em->clear();

        $deletedOrder = $em->getRepository(Order::class)->find($orderId);
        $this->assertNull($deletedOrder);

    }




    /* ------------------------------------ Historique ------------------------------------ */



    public function testHistoryPage(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get('doctrine')->getManager();

        // USER
        $user = new User();
        $user->setEmail('user_' . uniqid() . '@test.fr');
        $user->setPassword('TestAt6a3jkd!');
        $user->setName('Doe');
        $user->setFirstname('John');
        $user->setAddress('1 rue test');
        $user->setPostal('75000');
        $user->setCity('Paris');
        $user->setPhone('0600000000');
        $user->setRoles(['ROLE_USER']);

        // PAID ORDER
        $order = new Order();
        $order->setUser($user);
        $order->setStatus('paid'); // 🔴 IMPORTANT
        $order->setDate(new \DateTimeImmutable());
        $order->setTotalQuantity(2);
        $order->setTotalPrice('20.00');

        $em->persist($user);
        $em->persist($order);
        $em->flush();

        $client->loginUser($user);

        // REQUEST
        $client->request('GET', '/history');

        // ASSERTS
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('div');
    }



    
    /* ------------------------------------ Success ------------------------------------ */


    public function testSuccessPage(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get('doctrine')->getManager();
        
        $user = new User();
        $user->setEmail('user_' . uniqid() . '@test.fr');
        $user->setPassword('TestAt6a3jkd!');
        $user->setName('Doe');
        $user->setFirstname('John');
        $user->setAddress('1 rue test');
        $user->setPostal('75000');
        $user->setCity('Paris');
        $user->setPhone('0600000000');
        $user->setRoles(['ROLE_ADMIN']);

        $em->persist($user);
        $em->flush();

        $client->loginUser($user);

        $client->request('GET', '/panier/success');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('body', 'réussi');
    }

    public function testCancelRedirectsToPanier(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get('doctrine')->getManager();
        
        $user = new User();
        $user->setEmail('user_' . uniqid() . '@test.fr');
        $user->setPassword('TestAt6a3jkd!');
        $user->setName('Doe');
        $user->setFirstname('John');
        $user->setAddress('1 rue test');
        $user->setPostal('75000');
        $user->setCity('Paris');
        $user->setPhone('0600000000');
        $user->setRoles(['ROLE_ADMIN']);

        $em->persist($user);
        $em->flush();

        $client->loginUser($user);

        $client->request('GET', '/panier/cancel');

        $this->assertResponseRedirects('/panier');
    }
}
