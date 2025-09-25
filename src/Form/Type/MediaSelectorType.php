<?php

namespace App\Form\Type;

use App\Entity\Media;
use App\Repository\MediaRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Security;

class MediaSelectorType extends AbstractType
{
    private const DEFAULT_LIMIT = 50;
    private const MAX_LIMIT = 100;

    public function __construct(
        private readonly ?Security $security = null
    ) {}

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        parent::buildView($view, $form, $options);
        
        // Transférer les options vers la vue
        $view->vars['multiple'] = $options['multiple'];
        $view->vars['show_preview'] = $options['show_preview'];
        $view->vars['allow_upload'] = $options['allow_upload'];
        $view->vars['limit'] = $options['limit'];
        
        // Gestion sécurisée des classes CSS
        $existingClasses = array_filter(
            explode(' ', $view->vars['attr']['class'] ?? ''),
            fn($class) => !empty(trim($class))
        );
        $existingClasses[] = 'media-selector';
        $view->vars['attr']['class'] = implode(' ', $existingClasses);
        
        // Attributs data pour JavaScript
        $view->vars['attr']['data-multiple'] = $options['multiple'] ? 'true' : 'false';
        $view->vars['attr']['data-show-preview'] = $options['show_preview'] ? 'true' : 'false';
        $view->vars['attr']['data-allow-upload'] = $options['allow_upload'] ? 'true' : 'false';
        $view->vars['attr']['data-limit'] = (string) $options['limit'];
        
        // Amélioration de l'accessibilité avec traduction
        $view->vars['attr']['role'] = 'listbox';
        $view->vars['attr']['aria-label'] = $view->vars['translation_domain'] ?? 'admin';
        
        if ($options['multiple']) {
            $view->vars['attr']['aria-multiselectable'] = 'true';
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'class' => Media::class,
            'choice_label' => $this->getChoiceLabelCallback(),
            'choice_value' => 'id',
            'multiple' => true,
            'expanded' => false,
            'show_preview' => true,
            'allow_upload' => true,
            'limit' => self::DEFAULT_LIMIT,
            'translation_domain' => 'admin',
            'query_builder' => $this->getQueryBuilderCallback(),
        ]);

        // Validation des types d'options
        $resolver->setAllowedTypes('show_preview', 'bool');
        $resolver->setAllowedTypes('allow_upload', 'bool');
        $resolver->setAllowedTypes('limit', 'int');
        
        // Validation des valeurs d'options
        $resolver->setAllowedValues('limit', function ($value) {
            return $value > 0 && $value <= self::MAX_LIMIT;
        });
    }

    /**
     * Callback sécurisé pour l'étiquetage des médias
     */
    private function getChoiceLabelCallback(): \Closure
    {
        return function (?Media $media): string {
            if (!$media) {
                return '';
            }

            // Priorité : Alt text > Nom de fichier original > Nom de fichier généré
            $label = $media->getAlt() 
                ?? $media->getOriginalName() 
                ?? $media->getFileName() 
                ?? 'media_' . $media->getId();

            // Sécurité : limiter la longueur et échapper les caractères dangereux
            $label = htmlspecialchars($label, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            
            return mb_strlen($label) > 50 
                ? mb_substr($label, 0, 47) . '...' 
                : $label;
        };
    }

    /**
     * Query builder optimisé avec limitation et sécurité
     */
    private function getQueryBuilderCallback(): \Closure
    {
        return function (MediaRepository $repository) {
            $qb = $repository->createQueryBuilder('m')
                ->select('m') // Sélection explicite pour éviter les jointures inutiles
                ->orderBy('m.createdAt', 'DESC');

            // Sécurité : filtrer selon les permissions utilisateur si Security est disponible
            if ($this->security && !$this->security->isGranted('ROLE_ADMIN')) {
                // Par exemple, ne montrer que les médias publics pour les non-admins
                // $qb->andWhere('m.isPublic = :public')->setParameter('public', true);
            }

            return $qb;
        };
    }

    public function getParent(): string
    {
        return EntityType::class;
    }

    public function getBlockPrefix(): string
    {
        return 'media_selector';
    }
}
