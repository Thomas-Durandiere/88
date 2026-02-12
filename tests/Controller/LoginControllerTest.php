<?php

namespace App\Tests;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class LoginControllerTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $container = static::getContainer();
        $em = $container->get('doctrine.orm.entity_manager');
        $userRepository = $em->getRepository(User::class);

        

        // Remove any existing users from the test database
        foreach ($userRepository->findAll() as $user) {
            $em->remove($user);
        }

        $em->flush();

        // Create a User fixture
        /** @var UserPasswordHasherInterface $passwordHasher */
        $passwordHasher = $container->get('security.user_password_hasher');

        // $user = (new User())->setEmail('email@example.com');
        // $user->setPassword($passwordHasher->hashPassword($user, 'TestAt6a3jkd!'));

        // créer un utilisateur complet
            $user = (new User())
                ->setEmail('email@example.com')
                ->setName('Dupont')
                ->setFirstname('Jean')
                ->setRoles(['ROLE_USER'])
                ->setAddress('10 rue de Paris')
                ->setPostal('75001')
                ->setCity('Paris')
                ->setPhone('0102030405');

                // ->setPassword(
                //     $passwordHasher->hashPassword($user, 'TestAt6a3jkd!')
                // );

                // 2. Hash SÉPARÉ (user existe)
                $hashedPassword = $passwordHasher->hashPassword($user, 'TestAt6a3jkd!');

                // 3. Set password
                $user->setPassword($hashedPassword);

            $em->persist($user);
            $em->flush();
    }

    public function testLogin(): void
    {
        // Denied - Can't login with invalid email address.
        $this->client->request('GET', '/login');
        self::assertResponseIsSuccessful();

        $this->client->submitForm('Envoyer', [
            '_username' => 'doesNotExist@example.com',
            '_password' => 'TestAt6a3jkd!',
        ]);

        self::assertResponseRedirects('/login');
        $this->client->followRedirect();

        // Ensure we do not reveal if the user exists or not.
        self::assertSelectorTextContains('.alert-danger', 'Email ou mot de passe incorrect.');

        // Denied - Can't login with invalid password.
        $this->client->request('GET', '/login');
        self::assertResponseIsSuccessful();

        $this->client->submitForm('Envoyer', [
            '_username' => 'email@example.com',
            '_password' => 'bad-password',
        ]);

        self::assertResponseRedirects('/login');
        $this->client->followRedirect();

        // Ensure we do not reveal the user exists but the password is wrong.
        self::assertSelectorTextContains('.alert-danger', 'Email ou mot de passe incorrect.');

        // Success - Login with valid credentials is allowed.
        $this->client->submitForm('Envoyer', [
            '_username' => 'email@example.com',
            '_password' => 'TestAt6a3jkd!',
        ]);

        self::assertResponseRedirects('/boutique');
        $this->client->followRedirect();

        self::assertSelectorNotExists('.alert-danger');
    }
}
