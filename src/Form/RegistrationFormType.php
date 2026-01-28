<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Validator\Constraints\Regex;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom',
                'attr' => ['class' => 'input']
                ])
            ->add('firstname', TextType::class, [
                'label' => 'Prénom',
                'attr' => ['class' => 'input']
                ])
            ->add('email', EmailType::class, [
                'label' => 'email',
                'attr' => ['class' => 'input']
                ])
            ->add('plainPassword', PasswordType::class, [
                // instead of being set onto the object directly,
                // this is read and encoded in the controller
                'mapped' => false,
                'attr' => ['autocomplete' => 'new-password',
                           'Class' => 'input'],
                'label' => 'Mot de passe',
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter a password',
                    ]),
                    new Length([
                        'min' => 12,
                        'minMessage' => 'Votre mot de passe doit faire au moins {{ limit }} characters',
                        // max length allowed by Symfony for security reasons
                        'max' => 25,
                        'maxMessage' => 'Votre mot de passe ne peut pas dépasser {{ limit }} characters',
                    ]),
                    new Regex([
                        'pattern' => '/^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[^ a-zA-Z0-9]).{8,}$/',
                        'message' => 'Votre mot de passe doit contenir au moins : une lettre minuscule, une lettre majuscule, un chiffre et un caractère spécial.',
                    ]),
                ],
            ])
            ->add('address', TextType::class, [
                'label' => 'Adresse',
                'attr' => ['class' => 'input']
                ])
            ->add('postal', TextType::class, [
                'label' => 'Code postal',
                'attr' => ['class' => 'input']
                ])
            ->add('city', TextType::class, [
                'label' => 'Ville',
                'attr' => ['class' => 'input']
                ])
            ->add('phone', TextType::class, [
                'label' => 'Téléphone',
                'attr' => ['class' => 'input']
                ])
            ->add('accepteCGV', CheckboxType::class, [
                'label' => "J'accepte les conditions d'utilisation",
                'row_attr' => ['class' => 'checkBox'],
                'constraints' => [
                    new IsTrue([
                        'message' => 'Vous devez accepter nos conditions ',
                    ]),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
