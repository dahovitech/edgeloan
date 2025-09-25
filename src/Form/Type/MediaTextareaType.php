<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MediaTextareaType extends AbstractType
{
    private const MIN_HEIGHT = 150;
    private const MAX_HEIGHT = 800;
    private const DEFAULT_HEIGHT = 300;

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        parent::buildView($view, $form, $options);
        
        // Transférer les options vers la vue de manière sécurisée
        $view->vars['enable_media'] = $options['enable_media'];
        $view->vars['enable_editor'] = $options['enable_editor'];
        $view->vars['editor_height'] = $options['editor_height'];
        $view->vars['max_length'] = $options['max_length'];
        $view->vars['toolbar_config'] = $options['toolbar_config'];
        
        // Gestion robuste des classes CSS
        $existingClasses = $this->parseExistingClasses($view->vars['attr']['class'] ?? '');
        $newClasses = $this->generateNewClasses($options);
        
        $view->vars['attr']['class'] = implode(' ', array_unique(array_merge($existingClasses, $newClasses)));
        
        // Configuration des attributs data de manière sécurisée
        $this->setDataAttributes($view, $options);
        
        // Amélioration de l'accessibilité
        $this->setAccessibilityAttributes($view, $options);
        
        // Configuration de validation côté client
        $this->setValidationAttributes($view, $options);
    }

    /**
     * Parse les classes CSS existantes de manière sécurisée
     */
    private function parseExistingClasses(string $classString): array
    {
        return array_filter(
            array_map('trim', explode(' ', $classString)),
            fn($class) => !empty($class) && ctype_alnum(str_replace(['-', '_'], '', $class))
        );
    }

    /**
     * Génère les nouvelles classes CSS selon les options
     */
    private function generateNewClasses(array $options): array
    {
        $classes = ['media-textarea'];
        
        if ($options['enable_editor']) {
            $classes[] = 'custom-editor';
            $classes[] = 'editor-' . $options['toolbar_config'];
        }
        
        if ($options['enable_media']) {
            $classes[] = 'media-enabled';
        }
        
        return $classes;
    }

    /**
     * Configure les attributs data de manière sécurisée
     */
    private function setDataAttributes(FormView $view, array $options): void
    {
        $view->vars['attr']['data-editor-height'] = (string) $options['editor_height'];
        $view->vars['attr']['data-max-length'] = (string) $options['max_length'];
        $view->vars['attr']['data-toolbar'] = htmlspecialchars($options['toolbar_config'], ENT_QUOTES);
        
        if ($options['enable_media']) {
            $view->vars['attr']['data-enable-media'] = 'true';
            $view->vars['attr']['data-media-types'] = 'image/*,video/*,audio/*,application/pdf';
        }
        
        if ($options['enable_editor']) {
            $view->vars['attr']['data-enable-editor'] = 'true';
        }
    }

    /**
     * Configure les attributs d'accessibilité
     */
    private function setAccessibilityAttributes(FormView $view, array $options): void
    {
        $view->vars['attr']['role'] = 'textbox';
        $view->vars['attr']['aria-multiline'] = 'true';
        $view->vars['attr']['aria-label'] = 'forms.media.textarea.label';
        
        if ($options['max_length'] > 0) {
            $view->vars['attr']['aria-describedby'] = ($view->vars['attr']['aria-describedby'] ?? '') . ' char-count';
            $view->vars['attr']['maxlength'] = (string) $options['max_length'];
        }
        
        if ($options['enable_editor']) {
            $view->vars['attr']['aria-describedby'] = ($view->vars['attr']['aria-describedby'] ?? '') . ' editor-help';
        }
    }

    /**
     * Configure les attributs de validation côté client
     */
    private function setValidationAttributes(FormView $view, array $options): void
    {
        if ($options['required'] ?? false) {
            $view->vars['attr']['required'] = 'required';
            $view->vars['attr']['aria-required'] = 'true';
        }
        
        if ($options['max_length'] > 0) {
            $view->vars['attr']['data-validation'] = 'length';
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'enable_media' => true,
            'enable_editor' => true,
            'editor_height' => self::DEFAULT_HEIGHT,
            'max_length' => 0, // 0 = pas de limite
            'toolbar_config' => 'standard',
            'translation_domain' => 'admin',
        ]);

        // Validation des types
        $resolver->setAllowedTypes('enable_media', 'bool');
        $resolver->setAllowedTypes('enable_editor', 'bool');
        $resolver->setAllowedTypes('editor_height', 'int');
        $resolver->setAllowedTypes('max_length', 'int');
        $resolver->setAllowedTypes('toolbar_config', 'string');
        
        // Validation des valeurs
        $resolver->setAllowedValues('editor_height', function ($value) {
            return $value >= self::MIN_HEIGHT && $value <= self::MAX_HEIGHT;
        });
        
        $resolver->setAllowedValues('max_length', function ($value) {
            return $value >= 0; // 0 signifie pas de limite
        });
        
        $resolver->setAllowedValues('toolbar_config', ['minimal', 'standard', 'full']);
    }

    public function getParent(): string
    {
        return TextareaType::class;
    }

    public function getBlockPrefix(): string
    {
        return 'media_textarea';
    }
}
