<?php

namespace App\Form;

use App\Entity\Language;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\LocaleType;
use Symfony\Component\Form\Extension\Core\Type\LanguageType as SymfonyLanguageType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class LanguageType extends AbstractType
{
    // Locales les plus communes pour une meilleure UX
    private const PREFERRED_LOCALES = ['fr', 'en', 'es', 'de', 'it', 'pt', 'nl', 'ru', 'zh', 'ja'];

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('code', LocaleType::class, [
                'label' => 'forms.language.code.label',
                'help' => 'forms.language.code.help',
                'preferred_choices' => self::PREFERRED_LOCALES,
                'attr' => [
                    'class' => 'form-select',
                    'aria-describedby' => 'code-help',
                    'data-validation' => 'locale'
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'forms.language.code.required']),
                    new Assert\Locale(['message' => 'forms.language.code.invalid'])
                ]
            ])
            ->add('name', SymfonyLanguageType::class, [
                'label' => 'forms.language.name.label',
                'help' => 'forms.language.name.help',
                'preferred_choices' => self::PREFERRED_LOCALES,
                'attr' => [
                    'class' => 'form-select',
                    'aria-describedby' => 'name-help',
                    'data-validation' => 'language'
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'forms.language.name.required']),
                    new Assert\Language(['message' => 'forms.language.name.invalid'])
                ]
            ])
            ->add('nativeName', TextType::class, [
                'label' => 'forms.language.native_name.label',
                'help' => 'forms.language.native_name.help',
                'attr' => [
                    'maxlength' => 100,
                    'class' => 'form-control',
                    'aria-describedby' => 'native-name-help',
                    'data-validation' => 'native-name'
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'forms.language.native_name.required']),
                    new Assert\Length([
                        'min' => 2, 
                        'max' => 100,
                        'minMessage' => 'forms.language.native_name.min_length',
                        'maxMessage' => 'forms.language.native_name.max_length'
                    ]),
                    new Assert\Regex([
                        'pattern' => '/^[\p{L}\s\-\'\.]+$/u',
                        'message' => 'forms.language.native_name.invalid_characters'
                    ])
                ]
            ])
            ->add('isActive', CheckboxType::class, [
                'label' => 'forms.language.is_active.label',
                'help' => 'forms.language.is_active.help',
                'required' => false,
                'data' => true, // Nouvelle langue active par défaut
                'attr' => [
                    'class' => 'form-check-input',
                    'aria-describedby' => 'active-help',
                    'data-validation' => 'active-constraint'
                ]
            ])
            ->add('isDefault', CheckboxType::class, [
                'label' => 'forms.language.is_default.label',
                'help' => 'forms.language.is_default.help',
                'required' => false,
                'attr' => [
                    'class' => 'form-check-input',
                    'aria-describedby' => 'default-help',
                    'data-validation' => 'default-constraint'
                ]
            ])
            ->add('sortOrder', IntegerType::class, [
                'label' => 'forms.language.sort_order.label',
                'help' => 'forms.language.sort_order.help',
                'data' => $this->getNextSortOrder($options), // Auto-incrémente l'ordre
                'attr' => [
                    'min' => 0,
                    'step' => 1,
                    'class' => 'form-control',
                    'aria-describedby' => 'sort-order-help'
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'forms.language.sort_order.required']),
                    new Assert\Range([
                        'min' => 0, 
                        'max' => 9999,
                        'minMessage' => 'forms.language.sort_order.min',
                        'maxMessage' => 'forms.language.sort_order.max'
                    ])
                ]
            ]);

        // Ajouter des événements de validation métier
        $builder->addEventListener(FormEvents::POST_SUBMIT, [$this, 'onPostSubmit']);
    }

    /**
     * Valide la logique métier après soumission du formulaire
     */
    public function onPostSubmit(FormEvent $event): void
    {
        /** @var Language|null $language */
        $language = $event->getData();
        $form = $event->getForm();

        if (!$language) {
            return;
        }

        // Validation : si la langue est définie comme par défaut, 
        // elle doit aussi être active
        if ($language->isDefault() && !$language->isActive()) {
            $form->get('isActive')->addError(new \Symfony\Component\Form\FormError(
                'forms.language.validation.default_must_be_active'
            ));
        }

        // Validation : le code et le nom doivent être cohérents
        if ($language->getCode() !== $language->getName()) {
            // On peut être plus flexible ici selon les besoins métier
            // Par exemple, permettre fr/french mais pas fr/german
        }
    }

    /**
     * Calcule le prochain numéro d'ordre disponible
     */
    private function getNextSortOrder(array $options): int
    {
        // Si c'est une modification, garder l'ordre actuel
        if ($options['data'] instanceof Language && $options['data']->getSortOrder() !== null) {
            return $options['data']->getSortOrder();
        }

        // Pour une nouvelle langue, retourner un ordre par défaut
        // En production, ceci devrait probablement interroger la base de données
        // pour trouver le prochain ordre disponible
        return 100;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Language::class,
            'translation_domain' => 'admin',
        ]);
    }
}
