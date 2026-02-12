<?php

namespace App\Tests;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class RegistrationControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private UserRepository $userRepository;

    protected function setUp(): void
    {
        $this->client = static::createClient();

        // Ensure we have a clean database
        $container = static::getContainer();

        /** @var EntityManager $em */
        $em = $container->get('doctrine')->getManager();
        $this->userRepository = $container->get(UserRepository::class);

        foreach ($this->userRepository->findAll() as $user) {
            $em->remove($user);
        }

        $em->flush();
    }

    public function testRegister(): void
    {
        // Register a new user
        $this->client->request('GET', '/register');
        self::assertResponseIsSuccessful();
        self::assertPageTitleContains('Inscription');

        $this->client->submitForm('Envoyer', [
            'registration_form[email]' => 'me@example.com',
            'registration_form[plainPassword]' => 'TestAt6a3jkd!',
            'registration_form[name]' => 'Jean',
            'registration_form[firstname]' => 'Dupont',
            'registration_form[address]' => '10 rue de Paris',
            'registration_form[postal]' => '75001',
            'registration_form[city]' => 'Paris',
            'registration_form[phone]' => '0102030405',
        ]);

        // Ensure the response redirects after submitting the form, the user exists, and is not verified
        // self::assertResponseRedirects('/'); @TODO: set the appropriate path that the user is redirected to.
        self::assertCount(0, $this->userRepository->findAll());
    }
}
