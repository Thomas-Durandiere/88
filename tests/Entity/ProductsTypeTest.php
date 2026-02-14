<?php

namespace App\Tests\Form;

use App\Entity\Products;
use App\Form\ProductsType;
use Symfony\Component\Form\Test\TypeTestCase;

class ProductsTypeTest extends TypeTestCase
{
    public function testSubmitValidData(): void
    {
        // Données simulées pour remplir le formulaire
        $formData = [
            'name' => 'Produit Test',
            'description' => 'Description du produit',
            'pic' => 'http://example.com/photo.jpg',
            'price' => '49.99',
            'stock' => '10',
        ];

        // Entité vide
        $product = new Products();

        // Création du formulaire
        $form = $this->factory->create(ProductsType::class, $product);

        // Soumission des données
        $form->submit($formData);

        // Vérification que le formulaire est synchronisé
        $this->assertTrue($form->isSynchronized(), 'Le formulaire doit être synchronisé');

        // Vérification que les données sont correctement mappées sur l'entité
        $this->assertSame('Produit Test', $product->getName());
        $this->assertSame('Description du produit', $product->getDescription());
        $this->assertSame('http://example.com/photo.jpg', $product->getPic());
        $this->assertSame('49.99', $product->getPrice());
        $this->assertSame(10, $product->getStock());

        // Vérification de la structure du formulaire (facultatif mais utile)
        $view = $form->createView();
        $children = $view->children;

        foreach (array_keys($formData) as $fieldName) {
            $this->assertArrayHasKey($fieldName, $children);
        }
    }
}
