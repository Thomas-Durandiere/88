<?php

namespace App\Tests\Controller;

use App\Document\Avis;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AvisControllerTest extends WebTestCase
{
    private $client;
    private $dm;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $container = static::getContainer();
        $this->dm = $container->get(DocumentManager::class);

        // Nettoie MongoDB test
        $avisList = $this->dm->getRepository(Avis::class)->findAll();
        foreach ($avisList as $avis) {
            $this->dm->remove($avis);
        }
        $this->dm->flush();
    }

    public function testAvisList(): void
    {
        $crawler = $this->client->request('GET', '/avis');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('.avis-item');  // Adapte à ton template
        $this->assertSelectorTextContains('h1', 'Avis');  // Ou titre list
    }

    public function testAddAvisGet(): void
    {
        $crawler = $this->client->request('GET', '/add-avis');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');  // Formulaire présent
    }

    public function testAddAvisSuccess(): void
    {
        $crawler = $this->client->request('GET', '/add-avis');

        $form = $crawler->selectButton('Ajouter')  // Ou 'Envoyer'
            ->form([
                'avis[message]' => 'Super produit !',  // Adapte noms champs AvisType
                'avis[note]' => '5',  // Si champ note 1-5
                // 'avis[user]' => 'test@example.com',  // Si lié user
            ]);

        $this->client->submit($form);
        $this->assertResponseRedirects('/avis');
        $this->client->followRedirect();

        // Vérif avis créé + flash
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.alert-success', 'enregistré');  // Flash success
        $this->assertCount(1, $this->dm->getRepository(Avis::class)->findAll());

        $avis = $this->dm->getRepository(Avis::class)->findAll()[0];
        $this->assertSame('Super produit !', $avis->getMessage());
        $this->assertEquals(5, $avis->getNote());  // Si champ
        $this->assertInstanceOf(\DateTime::class, $avis->getCreatedAt());
    }

    public function testAddAvisInvalid(): void
    {
        $crawler = $this->client->request('GET', '/add-avis');

        $form = $crawler->selectButton('Ajouter')->form([
            'avis[message]' => '',  // Message vide → invalid
        ]);

        $this->client->submit($form);
        $this->assertResponseIsSuccessful();  // Pas de redirect (erreurs)
        $this->assertSelectorExists('.is-invalid');  // Bootstrap erreurs
        $this->assertCount(0, $this->dm->getRepository(Avis::class)->findAll());  // Pas persisté
    }
}
