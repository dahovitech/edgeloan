<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

class PasswordChangeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('currentPassword', PasswordType::class, [
                'label' => 'profile.form.current_password.label',
                'mapped' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'profile.form.current_password.placeholder',
                    'autocomplete' => 'current-password',
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'profile.form.current_password.not_blank',
                    ]),
                ],
                'help' => 'profile.form.current_password.help',
            ])
            ->add('newPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'mapped' => false,
                'invalid_message' => 'profile.form.new_password.mismatch',
                'first_options' => [
                    'label' => 'profile.form.new_password.label',
                    'attr' => [
                        'class' => 'form-control',
                        'placeholder' => 'profile.form.new_password.placeholder',
                        'autocomplete' => 'new-password',
                    ],
                    'help' => 'profile.form.new_password.help',
                ],
                'second_options' => [
                    'label' => 'profile.form.confirm_password.label',
                    'attr' => [
                        'class' => 'form-control',
                        'placeholder' => 'profile.form.confirm_password.placeholder',
                        'autocomplete' => 'new-password',
                    ],
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'profile.form.new_password.not_blank',
                    ]),
                    new Length([
                        'min' => 8,
                        'max' => 128,
                        'minMessage' => 'profile.form.new_password.min_length',
                        'maxMessage' => 'profile.form.new_password.max_length',
                    ]),
                    new Regex([
                        'pattern' => '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]/',
                        'message' => 'profile.form.new_password.complexity',
                    ]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'translation_domain' => 'profile',
            'attr' => [
                'novalidate' => 'novalidate',
                'class' => 'needs-validation password-change-form',
            ],
        ]);
    }
}