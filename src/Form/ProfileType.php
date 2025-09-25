<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\PositiveOrZero;

class ProfileType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // Personal Information
            ->add('firstName', TextType::class, [
                'label' => 'profile.form.first_name.label',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'profile.form.first_name.placeholder',
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'profile.form.first_name.not_blank',
                    ]),
                    new Length([
                        'min' => 2,
                        'max' => 100,
                        'minMessage' => 'profile.form.first_name.min_length',
                        'maxMessage' => 'profile.form.first_name.max_length',
                    ]),
                ],
            ])
            ->add('lastName', TextType::class, [
                'label' => 'profile.form.last_name.label',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'profile.form.last_name.placeholder',
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'profile.form.last_name.not_blank',
                    ]),
                    new Length([
                        'min' => 2,
                        'max' => 100,
                        'minMessage' => 'profile.form.last_name.min_length',
                        'maxMessage' => 'profile.form.last_name.max_length',
                    ]),
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'profile.form.email.label',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'profile.form.email.placeholder',
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'profile.form.email.not_blank',
                    ]),
                    new Email([
                        'message' => 'profile.form.email.invalid',
                    ]),
                ],
            ])
            ->add('phone', TelType::class, [
                'label' => 'profile.form.phone.label',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'profile.form.phone.placeholder',
                ],
                'constraints' => [
                    new Length([
                        'max' => 20,
                        'maxMessage' => 'profile.form.phone.max_length',
                    ]),
                ],
            ])

            // Profile Image
            ->add('profileImage', FileType::class, [
                'label' => 'profile.form.profile_image.label',
                'mapped' => false,
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'accept' => 'image/*',
                ],
                'help' => 'profile.form.profile_image.help',
                'constraints' => [
                    new File([
                        'maxSize' => '5M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/gif',
                            'image/webp',
                        ],
                        'mimeTypesMessage' => 'profile.form.profile_image.invalid_type',
                        'maxSizeMessage' => 'profile.form.profile_image.too_large',
                    ]),
                ],
            ])

            // Client Type
            ->add('clientType', ChoiceType::class, [
                'label' => 'profile.form.client_type.label',
                'choices' => [
                    'profile.form.client_type.individual' => 'individual',
                    'profile.form.client_type.business' => 'business',
                ],
                'attr' => [
                    'class' => 'form-select',
                ],
                'help' => 'profile.form.client_type.help',
            ])

            // Financial Information
            ->add('monthlyIncome', MoneyType::class, [
                'label' => 'profile.form.monthly_income.label',
                'required' => false,
                'currency' => 'EUR',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'profile.form.monthly_income.placeholder',
                ],
                'help' => 'profile.form.monthly_income.help',
                'constraints' => [
                    new PositiveOrZero([
                        'message' => 'profile.form.monthly_income.positive',
                    ]),
                ],
            ])
            ->add('monthlyCharges', MoneyType::class, [
                'label' => 'profile.form.monthly_charges.label',
                'required' => false,
                'currency' => 'EUR',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'profile.form.monthly_charges.placeholder',
                ],
                'help' => 'profile.form.monthly_charges.help',
                'constraints' => [
                    new PositiveOrZero([
                        'message' => 'profile.form.monthly_charges.positive',
                    ]),
                ],
            ])

            // Employment Information
            ->add('employmentStatus', ChoiceType::class, [
                'label' => 'profile.form.employment_status.label',
                'required' => false,
                'choices' => [
                    'profile.form.employment_status.employed' => 'employed',
                    'profile.form.employment_status.self_employed' => 'self_employed',
                    'profile.form.employment_status.unemployed' => 'unemployed',
                    'profile.form.employment_status.retired' => 'retired',
                    'profile.form.employment_status.student' => 'student',
                ],
                'attr' => [
                    'class' => 'form-select',
                ],
                'placeholder' => 'profile.form.employment_status.placeholder',
            ])
            ->add('employer', TextType::class, [
                'label' => 'profile.form.employer.label',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'profile.form.employer.placeholder',
                ],
                'constraints' => [
                    new Length([
                        'max' => 100,
                        'maxMessage' => 'profile.form.employer.max_length',
                    ]),
                ],
            ])
            ->add('employmentStartDate', DateType::class, [
                'label' => 'profile.form.employment_start_date.label',
                'required' => false,
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'form-control',
                ],
                'help' => 'profile.form.employment_start_date.help',
            ])

            // Business Information (conditionally displayed)
            ->add('businessName', TextType::class, [
                'label' => 'profile.form.business_name.label',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'profile.form.business_name.placeholder',
                ],
                'constraints' => [
                    new Length([
                        'max' => 100,
                        'maxMessage' => 'profile.form.business_name.max_length',
                    ]),
                ],
            ])
            ->add('businessRegistration', TextType::class, [
                'label' => 'profile.form.business_registration.label',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'profile.form.business_registration.placeholder',
                ],
                'help' => 'profile.form.business_registration.help',
                'constraints' => [
                    new Length([
                        'max' => 20,
                        'maxMessage' => 'profile.form.business_registration.max_length',
                    ]),
                ],
            ])
            ->add('businessYears', IntegerType::class, [
                'label' => 'profile.form.business_years.label',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'min' => 0,
                    'max' => 100,
                    'placeholder' => 'profile.form.business_years.placeholder',
                ],
                'help' => 'profile.form.business_years.help',
                'constraints' => [
                    new PositiveOrZero([
                        'message' => 'profile.form.business_years.positive',
                    ]),
                ],
            ])
            ->add('annualRevenue', MoneyType::class, [
                'label' => 'profile.form.annual_revenue.label',
                'required' => false,
                'currency' => 'EUR',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'profile.form.annual_revenue.placeholder',
                ],
                'help' => 'profile.form.annual_revenue.help',
                'constraints' => [
                    new PositiveOrZero([
                        'message' => 'profile.form.annual_revenue.positive',
                    ]),
                ],
            ])

            // Language Preference
            ->add('preferredLanguage', ChoiceType::class, [
                'label' => 'profile.form.preferred_language.label',
                'choices' => [
                    'profile.form.preferred_language.french' => 'fr',
                    'profile.form.preferred_language.english' => 'en',
                    'profile.form.preferred_language.german' => 'de',
                    'profile.form.preferred_language.spanish' => 'es',
                ],
                'attr' => [
                    'class' => 'form-select',
                ],
                'help' => 'profile.form.preferred_language.help',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'translation_domain' => 'profile',
            'attr' => [
                'novalidate' => 'novalidate',
                'class' => 'needs-validation',
            ],
        ]);
    }
}