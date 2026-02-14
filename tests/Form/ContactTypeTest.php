<?php

namespace App\Tests\Form;

use App\Form\ContactType;
use Symfony\Component\Form\Test\TypeTestCase;

class ContactTypeTest extends TypeTestCase
{
    public function testSubmitValidData(): void
    {
        // Données simulées pour remplir le formulaire
        $formData = [
            'nom' => 'Dupont',
            'prenom' => 'Jean',
            'email' => 'jean.dupont@example.com',
            'message' => 'Ceci est un message de test.',
            'save' => 'Envoyer', // Le bouton submit
        ];

        // Crée le formulaire
        $form = $this->factory->create(ContactType::class);

        // Soumet les données
        $form->submit($formData);

        // Vérifie que le formulaire est synchronisé
        $this->assertTrue($form->isSynchronized(), 'Le formulaire doit être synchronisé');

        // Vérifie que les champs existent dans la vue du formulaire
        $view = $form->createView();
        $children = $view->children;

        foreach (array_keys($formData) as $fieldName) {
            $this->assertArrayHasKey($fieldName, $children);
        }

        // Vérifie que les données soumises correspondent exactement
        $this->assertSame('Dupont', $form->get('nom')->getData());
        $this->assertSame('Jean', $form->get('prenom')->getData());
        $this->assertSame('jean.dupont@example.com', $form->get('email')->getData());
        $this->assertSame('Ceci est un message de test.', $form->get('message')->getData());
    }
}

