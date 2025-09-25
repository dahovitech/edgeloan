<?php

namespace App\Form;

use App\Entity\Enum\ReviewDecision;
use App\Entity\Enum\RiskLevel;
use App\Entity\LoanReview;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Range;

class LoanReviewType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('decision', EnumType::class, [
                'class' => ReviewDecision::class,
                'choices' => ReviewDecision::cases(),
                'choice_label' => fn(ReviewDecision $decision) => $decision->getLabel(),
                'attr' => [
                    'class' => 'form-select',
                ],
                'label' => 'review.decision.label',
                'constraints' => [
                    new NotBlank(['message' => 'review.decision.required'])
                ]
            ])
            ->add('score', IntegerType::class, [
                'label' => 'review.score.label',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'min' => 300,
                    'max' => 850,
                    'placeholder' => 'review.score.placeholder'
                ],
                'constraints' => [
                    new Range([
                        'min' => 300,
                        'max' => 850,
                        'notInRangeMessage' => 'review.score.invalid_range'
                    ])
                ],
                'help' => 'review.score.help'
            ])
            ->add('riskLevel', EnumType::class, [
                'class' => RiskLevel::class,
                'choices' => RiskLevel::cases(),
                'choice_label' => fn(RiskLevel $risk) => $risk->getLabel(),
                'attr' => [
                    'class' => 'form-select',
                ],
                'label' => 'review.risk_level.label',
                'required' => false,
                'help' => 'review.risk_level.help'
            ])
            ->add('comments', TextareaType::class, [
                'label' => 'review.comments.label',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 4,
                    'placeholder' => 'review.comments.placeholder'
                ],
                'help' => 'review.comments.help'
            ])
            ->add('conditions', TextareaType::class, [
                'label' => 'review.conditions.label',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 3,
                    'placeholder' => 'review.conditions.placeholder'
                ],
                'help' => 'review.conditions.help'
            ])
            ->add('requestedDocuments', TextareaType::class, [
                'label' => 'review.requested_documents.label',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 3,
                    'placeholder' => 'review.requested_documents.placeholder'
                ],
                'help' => 'review.requested_documents.help'
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'review.submit',
                'attr' => [
                    'class' => 'btn btn-primary'
                ]
            ])
            ->add('save_draft', SubmitType::class, [
                'label' => 'review.save_draft',
                'attr' => [
                    'class' => 'btn btn-outline-secondary',
                    'formnovalidate' => true
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => LoanReview::class,
            'translation_domain' => 'messages'
        ]);
    }
}