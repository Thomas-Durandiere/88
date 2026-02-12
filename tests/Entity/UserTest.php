<?php

namespace App\Tests\Entity;

use App\Entity\User;
use App\Entity\Order;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    public function testGettersAndSetters(): void
    {
        $user = new User();

        $user->setName('Doe');
        $user->setFirstname('John');
        $user->setEmail('john@example.com');
        $user->setPassword('password123');
        $user->setRoles(['ROLE_ADMIN']);
        $user->setAddress('123 Main St');
        $user->setPostal('75001');
        $user->setCity('Paris');
        $user->setPhone('0123456789');

        $this->assertSame('Doe', $user->getName());
        $this->assertSame('John', $user->getFirstname());
        $this->assertSame('john@example.com', $user->getEmail());
        $this->assertSame('password123', $user->getPassword());
        $this->assertContains('ROLE_ADMIN', $user->getRoles());
        $this->assertContains('ROLE_USER', $user->getRoles()); // ROLE_USER est ajouté automatiquement
        $this->assertSame('123 Main St', $user->getAddress());
        $this->assertSame('75001', $user->getPostal());
        $this->assertSame('Paris', $user->getCity());
        $this->assertSame('0123456789', $user->getPhone());
    }

    public function testUserIdentifier(): void
    {
        $user = new User();
        $user->setEmail('john@example.com');

        $this->assertSame('john@example.com', $user->getUserIdentifier());
    }

    public function testAddAndRemoveOrders(): void
    {
        $user = new User();
        $order = new Order(); // Assurez-vous que Order existe ou mockez-le

        $this->assertCount(0, $user->getOrders());

        $user->addOrder($order);
        $this->assertCount(1, $user->getOrders());
        $this->assertSame($user, $order->getUser());

        $user->removeOrder($order);
        $this->assertCount(0, $user->getOrders());
        $this->assertNull($order->getUser());
    }

    public function testSerialize(): void
    {
        $user = new User();
        $user->setPassword('mysecret');

        $serialized = $user->__serialize();
        $this->assertArrayHasKey("\0".User::class."\0password", $serialized);
        $this->assertSame(hash('crc32c', 'mysecret'), $serialized["\0".User::class."\0password"]);
    }
}
