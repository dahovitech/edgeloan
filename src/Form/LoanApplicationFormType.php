<?php

namespace App\Form;

use App\Entity\LoanApplication;
use App\Entity\Enum\LoanStatus;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Contracts\Translation\TranslatorInterface;

class LoanApplicationFormType extends AbstractType
{
    public function __construct(
        private TranslatorInterface $translator
    ) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('loanType', ChoiceType::class, [
                'choices' => [
                    'loan.type.personal' => 'personal',
                    'loan.type.business' => 'business',
                    'loan.type.auto' => 'auto',
                    'loan.type.home' => 'home',
                    'loan.type.education' => 'education',
                    'loan.type.investment' => 'investment',
                ],
                'label' => 'loan.form.loan_type',
                'placeholder' => 'loan.form.choose_type',
                'attr' => [
                    'class' => 'form-control',
                ],
                'help' => 'loan.form.loan_type.help',
                'constraints' => [
                    new Assert\NotBlank(message: 'loan.validation.loan_type_required'),
                ]
            ])
            ->add('amount', MoneyType::class, [
                'label' => 'loan.form.amount',
                'currency' => 'EUR',
                'attr' => [
                    'class' => 'form-control',
                    'min' => '1000',
                    'max' => '1000000',
                    'step' => '100',
                    'placeholder' => '10000'
                ],
                'help' => 'loan.form.amount.help',
                'constraints' => [
                    new Assert\NotBlank(message: 'loan.validation.amount_required'),
                    new Assert\Range(
                        min: 1000,
                        max: 1000000,
                        minMessage: 'loan.validation.amount_min',
                        maxMessage: 'loan.validation.amount_max'
                    )
                ]
            ])
            ->add('requestedDuration', IntegerType::class, [
                'label' => 'loan.form.duration',
                'attr' => [
                    'class' => 'form-control',
                    'min' => '6',
                    'max' => '360',
                    'step' => '1',
                    'placeholder' => '24'
                ],
                'help' => 'loan.form.duration.help',
                'constraints' => [
                    new Assert\NotBlank(message: 'loan.validation.duration_required'),
                    new Assert\Range(
                        min: 6,
                        max: 360,
                        minMessage: 'loan.validation.duration_min',
                        maxMessage: 'loan.validation.duration_max'
                    )
                ]
            ])
            ->add('purpose', TextareaType::class, [
                'label' => 'loan.form.purpose',
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 4,
                    'placeholder' => 'loan.form.purpose.placeholder',
                    'maxlength' => 500
                ],
                'help' => 'loan.form.purpose.help',
                'required' => false,
                'constraints' => [
                    new Assert\Length(
                        max: 500,
                        maxMessage: 'loan.validation.purpose_max_length'
                    )
                ]
            ])
            ->add('creditScore', IntegerType::class, [
                'label' => 'loan.form.credit_score',
                'attr' => [
                    'class' => 'form-control',
                    'min' => '300',
                    'max' => '850',
                    'placeholder' => '750'
                ],
                'help' => 'loan.form.credit_score.help',
                'required' => false,
                'constraints' => [
                    new Assert\Range(
                        min: 300,
                        max: 850,
                        minMessage: 'loan.validation.credit_score_min',
                        maxMessage: 'loan.validation.credit_score_max'
                    )
                ]
            ])
            ->add('notes', TextareaType::class, [
                'label' => 'loan.form.notes',
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 3,
                    'placeholder' => 'loan.form.notes.placeholder'
                ],
                'help' => 'loan.form.notes.help',
                'required' => false
            ]);

        // Add conditional fields based on user role
        if ($options['show_admin_fields']) {
            $builder
                ->add('status', ChoiceType::class, [
                    'choices' => LoanStatus::getSelectableOptions(),
                    'label' => 'loan.form.status',
                    'attr' => [
                        'class' => 'form-control'
                    ]
                ])
                ->add('interestRate', NumberType::class, [
                    'label' => 'loan.form.interest_rate',
                    'scale' => 3,
                    'attr' => [
                        'class' => 'form-control',
                        'min' => '0',
                        'max' => '50',
                        'step' => '0.001',
                        'placeholder' => '5.750'
                    ],
                    'help' => 'loan.form.interest_rate.help',
                    'required' => false
                ])
                ->add('monthlyPayment', MoneyType::class, [
                    'label' => 'loan.form.monthly_payment',
                    'currency' => 'EUR',
                    'attr' => [
                        'class' => 'form-control',
                        'readonly' => true
                    ],
                    'help' => 'loan.form.monthly_payment.help',
                    'required' => false
                ])
                ->add('reviewNotes', TextareaType::class, [
                    'label' => 'loan.form.review_notes',
                    'attr' => [
                        'class' => 'form-control',
                        'rows' => 3,
                        'placeholder' => 'loan.form.review_notes.placeholder'
                    ],
                    'required' => false
                ]);
        }

        // Add action buttons
        $builder
            ->add('save', SubmitType::class, [
                'label' => 'loan.form.save_draft',
                'attr' => [
                    'class' => 'btn btn-outline-primary me-2',
                    'formnovalidate' => true
                ]
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'loan.form.submit_application',
                'attr' => [
                    'class' => 'btn btn-primary'
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => LoanApplication::class,
            'show_admin_fields' => false,
            'attr' => [
                'novalidate' => true, // Use HTML5 validation
                'class' => 'loan-application-form'
            ]
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'loan_application';
    }
}