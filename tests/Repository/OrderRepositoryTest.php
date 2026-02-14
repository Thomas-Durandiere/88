<?php

namespace App\Tests\Repository;

use App\Entity\Order;
use App\Entity\User;
use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class OrderRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private OrderRepository $orderRepository;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->entityManager = self::getContainer()
            ->get('doctrine')
            ->getManager();

        $this->orderRepository = $this->entityManager
            ->getRepository(Order::class);

        $this->truncateEntities();
    }

    private function truncateEntities(): void
    {
        $connection = $this->entityManager->getConnection();

        $connection->executeStatement('SET FOREIGN_KEY_CHECKS=0');

        $connection->executeStatement('TRUNCATE TABLE order_products');
        $connection->executeStatement('TRUNCATE TABLE `Order`');
        $connection->executeStatement('TRUNCATE TABLE user');

        $connection->executeStatement('SET FOREIGN_KEY_CHECKS=1');
    }

    public function testFindCartByUser(): void
    {
        $user = $this->createUser();

        $cartOrder = (new Order())
            ->setUser($user)
            ->setStatus('cart')
            ->setDate(new \DateTimeImmutable())
            ->setTotalQuantity(1)
            ->setTotalPrice('10.00');

        $paidOrder = (new Order())
            ->setUser($user)
            ->setStatus('paid')
            ->setDate(new \DateTimeImmutable())
            ->setTotalQuantity(2)
            ->setTotalPrice('20.00');

        $this->entityManager->persist($cartOrder);
        $this->entityManager->persist($paidOrder);
        $this->entityManager->flush();

        $result = $this->orderRepository->findCartByUser($user);

        $this->assertInstanceOf(Order::class, $result);
        $this->assertSame('cart', $result->getStatus());
    }

    public function testFindPaidByUser(): void
    {
        $user = $this->createUser();

        $paidOrder1 = (new Order())
            ->setUser($user)
            ->setStatus('paid')
            ->setDate(new \DateTimeImmutable('-1 day'))
            ->setTotalQuantity(1)
            ->setTotalPrice('15.00');

        $paidOrder2 = (new Order())
            ->setUser($user)
            ->setStatus('paid')
            ->setDate(new \DateTimeImmutable())
            ->setTotalQuantity(3)
            ->setTotalPrice('30.00');

        $this->entityManager->persist($paidOrder1);
        $this->entityManager->persist($paidOrder2);
        $this->entityManager->flush();

        $results = $this->orderRepository->findPaidByUser($user);

        $this->assertCount(2, $results);
        $this->assertSame('paid', $results[0]->getStatus());

        // Vérifie l’ordre DESC par date
        $this->assertGreaterThan(
            $results[1]->getDate(),
            $results[0]->getDate()
        );
    }

    private function createUser(): User
    {
        $user = (new User())
            ->setEmail('test@example.com')
            ->setPassword('password')
            ->setName('Doe')
            ->setFirstname('John')
            ->setAddress('1 rue de test')
            ->setPostal('75000')
            ->setCity('Paris')
            ->setPhone('0600000000')
            ->setRoles(['ROLE_USER']);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
    }
}
