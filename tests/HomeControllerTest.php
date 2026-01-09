<?php

namespace App\Tests\Controller;

use App\Service\Meteo;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\Entity\Photo;
use App\Form\PhotoType;
use App\Repository\PhotoRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;

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

        $pages = [
            '/' => 'Accueil',
            '/mentionsLegales' => 'Mentions légales',
            '/cgu' => 'CGU',
            '/prestaions' => 'Prestations',
        ];

        foreach ($pages as $url => $title) {
            $crawler = $client->request('GET', $url);

            // Vérifie que la page répond bien
            $this->assertResponseIsSuccessful();

            // Vérifie qu'au moins le titre du contrôleur apparaît dans le HTML
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

        // Mock EntityManager pour ne pas toucher à la base
        $emMock = $this->createMock(EntityManagerInterface::class);
        $emMock->expects($this->any())->method('persist');
        $emMock->expects($this->any())->method('flush');

        $client->getContainer()->set(EntityManagerInterface::class, $emMock);

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

}
