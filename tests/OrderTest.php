<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use App\Entity\Order;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;
use App\Entity\Products;
use App\Entity\OrderProducts;

class OrderTest extends KernelTestCase
{
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->em = static::getContainer()->get('doctrine')->getManager();

        // On recharge les fixtures pour chaque test
        $loader = new \Doctrine\Common\DataFixtures\Loader();
        $loader->addFixture(new \App\DataFixtures\AppFixtures());

        $purger = new \Doctrine\Common\DataFixtures\Purger\ORMPurger();
        $executor = new \Doctrine\Common\DataFixtures\Executor\ORMExecutor($this->em, $purger);
        $executor->execute($loader->getFixtures());
    }

    public function testOrderTotal(): void
    {
        // Récupère la commande créée par la fixture
        $order = $this->em->getRepository(Order::class)->findOneBy([]);

        $totalExpected = 19.99*2 + 29.99*1; // produit price * quantity défini dans la fixture

        $totalActual = 0;
        foreach ($order->getOrderProducts() as $op) {
            $totalActual += $op->getProducts()->getPrice() * $op->getQuantity();
        }

        $this->assertEquals($totalExpected, $totalActual, "Le total de la commande est correct");
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->em->close();
    }
}
