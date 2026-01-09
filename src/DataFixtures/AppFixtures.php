<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use App\Entity\User;
use App\Entity\Products;
use App\Entity\Order;
use App\Entity\OrderProducts;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // --- User 1 ---
        $user1 = new User();
        $user1->setName('Dupont');
        $user1->setFirstname('Jean');
        $user1->setEmail('jean.dupont@test.com');
        $user1->setPassword('password1'); // pour tests, pas besoin de hash réel
        $user1->setRoles(['ROLE_USER']);
        $user1->setAddress('10 rue de Paris');
        $user1->setPostal('75001');
        $user1->setCity('Paris');
        $user1->setPhone('0102030405');

        $manager->persist($user1);

        // --- User 2 ---
        $user2 = new User();
        $user2->setName('Martin');
        $user2->setFirstname('Lisa');
        $user2->setEmail('lisa.martin@test.com');
        $user2->setPassword('password2');
        $user2->setRoles(['ROLE_USER']);
        $user2->setAddress('20 avenue de Lyon');
        $user2->setPostal('69001');
        $user2->setCity('Lyon');
        $user2->setPhone('0607080910');

        $manager->persist($user2);

        // --- Création d'un produit ---
        // --- Produit 1 ---
        $product1 = new Products();
        $product1->setName('Produit A');
        $product1->setDescription('Description du Produit A');
        $product1->setPic('produit_a.jpg');
        $product1->setPrice('19.99'); // en string pour DECIMAL
        $product1->setStock(10);

        $manager->persist($product1);

        // --- Produit 2 ---
        $product2 = new Products();
        $product2->setName('Produit B');
        $product2->setDescription('Description du Produit B');
        $product2->setPic('produit_b.jpg');
        $product2->setPrice('29.99');
        $product2->setStock(5);

        $manager->persist($product2);

        // --- Création d'une commande ---
        
        // Récupérer les users déjà créés
        // $userRepo = $manager->getRepository(User::class);
        // $user1 = $userRepo->findOneBy(['email' => 'jean.dupont@test.com']);
        // $user2 = $userRepo->findOneBy(['email' => 'lisa.martin@test.com']);

        // --- Commande 1 ---
        $order1 = new Order();
        $order1->setUser($user1);
        $order1->setDate(new \DateTimeImmutable('now'));
        $order1->setTotalQuantity(3); // exemple
        $order1->setTotalPrice('59.97'); // exemple
        $order1->setStatus('pending');

        $manager->persist($order1);

        // --- Commande 2 ---
        $order2 = new Order();
        $order2->setUser($user2);
        $order2->setDate(new \DateTimeImmutable('now'));
        $order2->setTotalQuantity(2);
        $order2->setTotalPrice('49.98');
        $order2->setStatus('confirmed');

        $manager->persist($order2);

        // --- Lien OrderProduct ---
        
        // Récupérer les commandes et produits existants
        // $orderRepo = $manager->getRepository(Order::class);
        // $productRepo = $manager->getRepository(Products::class);

        // $order1 = $orderRepo->findOneBy([]);
        // $order2 = $orderRepo->findOneBy([], ['id' => 'DESC']);

        // $product1 = $productRepo->findOneBy([]);
        // $product2 = $productRepo->findOneBy([], ['id' => 'DESC']);

        // --- OrderProduct 1 : order1 + product1 ---
        $op1 = new OrderProducts();
        $op1->setOrderRef($order1);
        $op1->setProducts($product1);
        $op1->setQuantity(2);
        $op1->setPriceUnit($product1->getPrice()); // utilise le prix du produit
        $manager->persist($op1);

        // --- OrderProduct 2 : order1 + product2 ---
        $op2 = new OrderProducts();
        $op2->setOrderRef($order1);
        $op2->setProducts($product2);
        $op2->setQuantity(1);
        $op2->setPriceUnit($product2->getPrice());
        $manager->persist($op2);

        // --- OrderProduct 3 : order2 + product1 ---
        $op3 = new OrderProducts();
        $op3->setOrderRef($order2);
        $op3->setProducts($product1);
        $op3->setQuantity(3);
        $op3->setPriceUnit($product1->getPrice());
        $manager->persist($op3);

        // --- Sauvegarde en base ---
        $manager->flush();
    }
}
