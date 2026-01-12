<?php

namespace App\Form;

use App\Document\Avis;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;

class AvisType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Prénom',
                'attr' => ['class' => 'input']
                ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'attr' => ['class' => 'input']
                ])
            ->add('message', TextareaType::class, [
                'label' => 'Message',
                'attr' => ['class' => 'area']
                ])
            ->add('note', IntegerType::class, [
                'label' => 'Note',
                'attr' => ['class' => 'note-hidden'],
            ])
            ->add('save', SubmitType::class, [
                'label' => 'Envoyer',
                'attr' => ['class' => 'bout']
                ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Avis::class,
        ]);
    }
}
