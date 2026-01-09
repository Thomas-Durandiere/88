<?php

namespace App\Tests\Entity;

use App\Entity\Photo;
use PHPUnit\Framework\TestCase;

class PhotoTest extends TestCase
{
    public function testPhotoEntity(): void
    {
        $photo = new Photo();

        // Test des setters et getters
        $photo->setName('test-name');
        $this->assertSame('test-name', $photo->getName());

        $photo->setUrl('/images/test.jpg');
        $this->assertSame('/images/test.jpg', $photo->getUrl());

        $photo->setTitle('Titre test');
        $this->assertSame('Titre test', $photo->getTitle());

        $photo->setAlt('Alt test');
        $this->assertSame('Alt test', $photo->getAlt());

        $photo->setCategory('Couleur');
        $this->assertSame('Couleur', $photo->getCategory());

        // L'id doit être null par défaut (pas encore en base)
        $this->assertNull($photo->getId());
    }
}
