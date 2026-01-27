<?php

namespace App\Tests\Document;

use App\Document\Avis;
use PHPUnit\Framework\TestCase;

class AvisTest extends TestCase
{
    private Avis $avis;

    protected function setUp(): void
    {
        $this->avis = new Avis();
    }

    public function testConstructSetsCreatedAt(): void
    {
        $createdAt = $this->avis->getCreatedAt();
        $this->assertInstanceOf(\DateTime::class, $createdAt);
        $this->assertLessThan(new \DateTime('+1 minute'), $createdAt);
    }

    public function testSetNomReturnsSelf(): void
    {
        $result = $this->avis->setNom('John Doe');
        $this->assertSame($this->avis, $result);
    }

    public function testGetNomReturnsSetValue(): void
    {
        $this->avis->setNom('John Doe');
        $this->assertEquals('John Doe', $this->avis->getNom());
    }

    public function testSetEmailReturnsSelf(): void
    {
        $result = $this->avis->setEmail('john@example.com');
        $this->assertSame($this->avis, $result);
    }

    public function testGetEmailReturnsSetValue(): void
    {
        $this->avis->setEmail('john@example.com');
        $this->assertEquals('john@example.com', $this->avis->getEmail());
    }

    public function testSetMessageReturnsSelf(): void
    {
        $result = $this->avis->setMessage('Great product!');
        $this->assertSame($this->avis, $result);
    }

    public function testGetMessageReturnsSetValue(): void
    {
        $this->avis->setMessage('Great product!');
        $this->assertEquals('Great product!', $this->avis->getMessage());
    }

    public function testSetNoteStoresInteger(): void
    {
        $this->avis->setNote(5);
        $this->assertEquals(5, $this->avis->getNote());
    }

    public function testNoteCannotBeNullAfterSet(): void
    {
        $this->avis->setNote(4);
        $this->assertNotNull($this->avis->getNote());
    }

    public function testFullAvisCreation(): void
    {
        $avis = (new Avis())
            ->setNom('Marie Dupont')
            ->setEmail('marie@test.com')
            ->setMessage('Super service !')
            ->setNote(5);

        $this->assertEquals('Marie Dupont', $avis->getNom());
        $this->assertEquals('marie@test.com', $avis->getEmail());
        $this->assertEquals('Super service !', $avis->getMessage());
        $this->assertEquals(5, $avis->getNote());
        $this->assertInstanceOf(\DateTime::class, $avis->getCreatedAt());
    }

    public function testIdIsInitiallyNull(): void
    {
        $this->assertNull($this->avis->getId());
    }
}
