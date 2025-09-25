<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\Choice;

class UserType extends AbstractType
{
    // Constantes pour les rôles disponibles
    private const AVAILABLE_ROLES = [
        'ROLE_USER' => 'forms.user.roles.choices.user',
        'ROLE_ADMIN' => 'forms.user.roles.choices.admin', 
        'ROLE_SUPER_ADMIN' => 'forms.user.roles.choices.super_admin',
    ];

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Déterminer les rôles actuels de manière sécurisée
        $currentRoles = ['ROLE_USER']; // Valeur par défaut sûre
        if ($options['data'] instanceof User && $options['data']->getId() !== null) {
            // Utilisateur existant : récupérer ses rôles actuels
            $currentRoles = $options['data']->getRoles();
        }

        $builder
            ->add('email', EmailType::class, [
                'label' => 'forms.user.email.label',
                'help' => 'forms.user.email.help',
                'attr' => [
                    'class' => 'form-control',
                    'autocomplete' => 'email',
                    'inputmode' => 'email',
                    'aria-describedby' => 'email-help',
                    'data-validation' => 'email'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'forms.user.email.required']),
                    new Email(['message' => 'forms.user.email.invalid'])
                ]
            ])
            ->add('firstName', TextType::class, [
                'label' => 'forms.user.first_name.label',
                'help' => 'forms.user.first_name.help',
                'attr' => [
                    'class' => 'form-control',
                    'autocomplete' => 'given-name',
                    'aria-describedby' => 'firstname-help',
                    'maxlength' => 50
                ],
                'constraints' => [
                    new NotBlank(['message' => 'forms.user.first_name.required']),
                    new Length([
                        'min' => 2,
                        'max' => 50,
                        'minMessage' => 'forms.user.first_name.min_length',
                        'maxMessage' => 'forms.user.first_name.max_length'
                    ])
                ]
            ])
            ->add('lastName', TextType::class, [
                'label' => 'forms.user.last_name.label',
                'help' => 'forms.user.last_name.help',
                'attr' => [
                    'class' => 'form-control',
                    'autocomplete' => 'family-name',
                    'aria-describedby' => 'lastname-help',
                    'maxlength' => 50
                ],
                'constraints' => [
                    new NotBlank(['message' => 'forms.user.last_name.required']),
                    new Length([
                        'min' => 2,
                        'max' => 50,
                        'minMessage' => 'forms.user.last_name.min_length',
                        'maxMessage' => 'forms.user.last_name.max_length'
                    ])
                ]
            ])
            ->add('roles', ChoiceType::class, [
                'label' => 'forms.user.roles.label',
                'help' => 'forms.user.roles.help',
                'choices' => array_flip(self::AVAILABLE_ROLES),
                'multiple' => true,
                'expanded' => true,
                'data' => $currentRoles,
                'attr' => [
                    'class' => 'form-check-input',
                    'aria-describedby' => 'roles-help'
                ],
                'constraints' => [
                    new Choice([
                        'choices' => array_keys(self::AVAILABLE_ROLES),
                        'multiple' => true,
                        'min' => 1,
                        'minMessage' => 'forms.user.roles.min_required'
                    ])
                ]
            ])
            ->add('isActive', CheckboxType::class, [
                'label' => 'forms.user.is_active.label',
                'help' => 'forms.user.is_active.help',
                'required' => false,
                'data' => $options['data']?->isActive() ?? true, // Par défaut actif pour nouveaux utilisateurs
                'attr' => [
                    'class' => 'form-check-input',
                    'aria-describedby' => 'active-help'
                ]
            ]);

        // Ajouter les champs de mot de passe si nécessaire
        if ($options['include_password']) {
            $this->addPasswordField($builder, $options);
        }
    }

    private function addPasswordField(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('plainPassword', RepeatedType::class, [
            'type' => PasswordType::class,
            'mapped' => false,
            'required' => $options['password_required'],
            'first_options' => [
                'label' => 'forms.user.password.label',
                'help' => 'forms.user.password.help',
                'attr' => [
                    'class' => 'form-control',
                    'autocomplete' => 'new-password',
                    'aria-describedby' => 'password-help',
                    'minlength' => 8,
                    'data-validation' => 'password-strength'
                ]
            ],
            'second_options' => [
                'label' => 'forms.user.password.confirm_label',
                'help' => 'forms.user.password.confirm_help',
                'attr' => [
                    'class' => 'form-control',
                    'autocomplete' => 'new-password',
                    'aria-describedby' => 'password-confirm-help'
                ]
            ],
            'invalid_message' => 'forms.user.password.mismatch',
            'constraints' => $options['password_required'] ? [
                new NotBlank(['message' => 'forms.user.password.required']),
                new Length([
                    'min' => 8,
                    'max' => 4096,
                    'minMessage' => 'forms.user.password.min_length',
                    'maxMessage' => 'forms.user.password.max_length'
                ]),
                new Regex([
                    'pattern' => '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]/',
                    'message' => 'forms.user.password.strength_required'
                ])
            ] : [],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'include_password' => true,
            'password_required' => true,
            'translation_domain' => 'admin',
        ]);
        
        $resolver->setAllowedTypes('include_password', 'bool');
        $resolver->setAllowedTypes('password_required', 'bool');
    }
}
