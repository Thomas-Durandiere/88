<?php

namespace App\Form;

use App\Entity\Photo;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class PhotoType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom',
                'attr' => ['class' => 'input']
                ])
            // ->add('url', TextType::class, [
            //     'label' => 'URL'])
            ->add('title', TextType::class, [
                'label' => 'Titre',
                'attr' => ['class' => 'input']
                ])
            ->add('alt', TextType::class, [
                'label' => 'Alt',
                'attr' => ['class' => 'input']
                ])
            ->add('category', ChoiceType::class, [
                'label' => 'Catégorie',
                'attr' => ['class' => 'input'],
                'choices' => [
                    'Couleur' => 'Couleur',
                    'Event' => 'Event',
                    'Coupe' => 'Coupe',
                ],
                'placeholder' => 'Choisir une catégorie',
            ])
            ->add('imageFile', FileType::class, [
                'label' => 'Image',
                'attr' => ['class' => 'input'],
                'mapped' => false, // ne va pas dans l'entity automatiquement
                'required' => true,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Photo::class,
        ]);
    }
}
