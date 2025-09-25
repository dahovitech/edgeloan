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
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'forms.user.email.label',
                'translation_domain' => 'admin',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'forms.user.email.placeholder'
                ]
            ])
            ->add('firstName', TextType::class, [
                'label' => 'forms.user.first_name.label',
                'translation_domain' => 'admin',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'forms.user.first_name.placeholder'
                ]
            ])
            ->add('lastName', TextType::class, [
                'label' => 'forms.user.last_name.label',
                'translation_domain' => 'admin',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'forms.user.last_name.placeholder'
                ]
            ])
            ->add('roles', ChoiceType::class, [
                'label' => 'forms.user.roles.label',
                'translation_domain' => 'admin',
                'choices' => [
                    'forms.user.roles.choices.user' => 'ROLE_USER',
                    'forms.user.roles.choices.admin' => 'ROLE_ADMIN',
                    'forms.user.roles.choices.super_admin' => 'ROLE_SUPER_ADMIN',
                ],
                'multiple' => true,
                'expanded' => true,
                'data' => $options['data']->getRoles() ?? ['ROLE_USER'],
                'attr' => [
                    'class' => 'form-check-input'
                ]
            ])
            ->add('isActive', CheckboxType::class, [
                'label' => 'forms.user.is_active.label',
                'translation_domain' => 'admin',
                'required' => false,
                'attr' => [
                    'class' => 'form-check-input'
                ],
                'help' => 'forms.user.is_active.help'
            ]);

        // Add password fields only for new users or when explicitly requested
        if ($options['include_password']) {
            $builder->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'mapped' => false,
                'required' => $options['password_required'],
                'translation_domain' => 'admin',
                'first_options' => [
                    'label' => 'forms.user.password.label',
                    'attr' => [
                        'class' => 'form-control',
                        'autocomplete' => 'new-password'
                    ]
                ],
                'second_options' => [
                    'label' => 'forms.user.password.confirm_label',
                    'attr' => [
                        'class' => 'form-control',
                        'autocomplete' => 'new-password'
                    ]
                ],
                'invalid_message' => 'forms.user.password.mismatch',
                'constraints' => $options['password_required'] ? [
                    new NotBlank([
                        'message' => 'forms.user.password.required',
                    ]),
                    new Length([
                        'min' => 6,
                        'minMessage' => 'forms.user.password.min_length',
                        'max' => 4096,
                    ]),
                ] : [],
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'include_password' => true,
            'password_required' => true,
        ]);
    }
}