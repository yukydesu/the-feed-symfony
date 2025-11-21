<?php

namespace App\Form;

use App\Entity\Utilisateur;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Regex;

class UtilisateurType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('login', TextType::class, [
                'attr' => [
                    'minlength' => 4,
                    'maxlength' => 20
                ]
            ])
            ->add('adresseEmail', EmailType::class)
            ->add('plainPassword', PasswordType::class, [
                'mapped' => false,
                'constraints' => [
                    new NotBlank(message: 'Le mot de passe ne peut pas être vide.'),
                    new NotNull(message: 'Le mot de passe ne peut pas être null.'),
                    new Length(
                        min: 8,
                        max: 30,
                        minMessage: 'Le mot de passe doit contenir au moins {{ limit }} caractères.',
                        maxMessage: 'Le mot de passe ne peut pas dépasser {{ limit }} caractères.'
                    ),
                    new Regex(
                        pattern: '#^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[a-zA-Z\d\w\W]{8,30}$#',
                        message: 'Le mot de passe doit contenir au moins une minuscule, une majuscule et un chiffre.'
                    )
                ],
                'attr' => [
                    'minlength' => 8,
                    'maxlength' => 30,
                    'pattern' => '^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[a-zA-Z\d\w\W]{8,30}$'
                ]
            ])
            ->add('fichierPhotoProfil', FileType::class, [
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File(
                        maxSize: '10M',
                        extensions: ['jpg', 'png'],
                        maxSizeMessage: 'Le fichier ne doit pas dépasser {{ limit }}{{ suffix }}.',
                        extensionsMessage: 'Seuls les formats JPG et PNG sont autorisés.'
                    )
                ],
                'attr' => [
                    'accept' => 'image/png, image/jpeg'
                ]
            ])
            ->add('inscription', SubmitType::class)
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Utilisateur::class,
        ]);
    }
}
