<?php

namespace App\Form;

use App\Entity\LoanDocument;
use App\Entity\Enum\DocumentType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotBlank;

class LoanDocumentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('documentType', EnumType::class, [
                'class' => DocumentType::class,
                'choices' => DocumentType::cases(),
                'choice_label' => fn(DocumentType $type) => $type->getLabel(),
                'attr' => [
                    'class' => 'form-select',
                ],
                'label' => 'document.type.label',
                'constraints' => [
                    new NotBlank(['message' => 'document.type.required'])
                ]
            ])
            ->add('file', FileType::class, [
                'label' => 'document.file.label',
                'mapped' => false,
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'accept' => implode(',', [
                        'application/pdf',
                        'image/jpeg',
                        'image/png',
                        'image/gif',
                        'application/msword',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
                    ])
                ],
                'constraints' => [
                    new File([
                        'maxSize' => '10M',
                        'mimeTypes' => [
                            'application/pdf',
                            'image/jpeg',
                            'image/png',
                            'image/gif',
                            'application/msword',
                            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
                        ],
                        'mimeTypesMessage' => 'document.file.invalid_type',
                        'maxSizeMessage' => 'document.file.too_large'
                    ])
                ],
                'help' => 'document.file.help'
            ])
            ->add('description', TextareaType::class, [
                'label' => 'document.description.label',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 3,
                    'placeholder' => 'document.description.placeholder'
                ],
                'help' => 'document.description.help'
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'document.upload.submit',
                'attr' => [
                    'class' => 'btn btn-primary'
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => LoanDocument::class,
            'translation_domain' => 'messages'
        ]);
    }
}