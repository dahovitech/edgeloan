<?php

namespace App\Form;

use App\Entity\Role;
use App\Entity\Permission;
use App\Repository\PermissionRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Range;
use Symfony\Component\Validator\Constraints\Regex;

class RoleType extends AbstractType
{
    public function __construct(private PermissionRepository $permissionRepository)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Role|null $role */
        $role = $builder->getData();
        $isSystemRole = $role && $role->isSystemRole();

        $builder
            ->add('code', TextType::class, [
                'label' => 'admin.role.form.code.label',
                'help' => 'admin.role.form.code.help',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'admin.role.form.code.placeholder',
                    'readonly' => $isSystemRole,
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'admin.role.form.code.not_blank',
                    ]),
                    new Length([
                        'min' => 3,
                        'max' => 50,
                        'minMessage' => 'admin.role.form.code.min_length',
                        'maxMessage' => 'admin.role.form.code.max_length',
                    ]),
                    new Regex([
                        'pattern' => '/^[A-Z_]+$/',
                        'message' => 'admin.role.form.code.invalid_format',
                    ]),
                ],
                'disabled' => $isSystemRole,
            ])
            ->add('name', TextType::class, [
                'label' => 'admin.role.form.name.label',
                'help' => 'admin.role.form.name.help',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'admin.role.form.name.placeholder',
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'admin.role.form.name.not_blank',
                    ]),
                    new Length([
                        'min' => 2,
                        'max' => 100,
                        'minMessage' => 'admin.role.form.name.min_length',
                        'maxMessage' => 'admin.role.form.name.max_length',
                    ]),
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'admin.role.form.description.label',
                'help' => 'admin.role.form.description.help',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'admin.role.form.description.placeholder',
                    'rows' => 3,
                ],
                'constraints' => [
                    new Length([
                        'max' => 255,
                        'maxMessage' => 'admin.role.form.description.max_length',
                    ]),
                ],
            ])
            ->add('priority', IntegerType::class, [
                'label' => 'admin.role.form.priority.label',
                'help' => 'admin.role.form.priority.help',
                'attr' => [
                    'class' => 'form-control',
                    'min' => 0,
                    'max' => 100,
                ],
                'constraints' => [
                    new Range([
                        'min' => 0,
                        'max' => 100,
                        'notInRangeMessage' => 'admin.role.form.priority.range',
                    ]),
                ],
            ])
            ->add('isActive', CheckboxType::class, [
                'label' => 'admin.role.form.active.label',
                'help' => 'admin.role.form.active.help',
                'required' => false,
                'attr' => [
                    'class' => 'form-check-input',
                ],
                'disabled' => $isSystemRole,
            ]);

        // Multi-language support fields
        if ($options['enable_translations']) {
            $this->addTranslationFields($builder, $options['supported_locales']);
        }

        // Permissions field (only if not system role and permissions are enabled)
        if (!$isSystemRole && $options['enable_permissions']) {
            $this->addPermissionsField($builder);
        }
    }

    private function addTranslationFields(FormBuilderInterface $builder, array $locales): void
    {
        foreach ($locales as $locale) {
            $builder
                ->add('name_' . $locale, TextType::class, [
                    'label' => 'admin.role.form.name_translation.label',
                    'label_translation_parameters' => ['%locale%' => strtoupper($locale)],
                    'required' => false,
                    'mapped' => false,
                    'attr' => [
                        'class' => 'form-control',
                        'placeholder' => 'admin.role.form.name_translation.placeholder',
                    ],
                ])
                ->add('description_' . $locale, TextareaType::class, [
                    'label' => 'admin.role.form.description_translation.label',
                    'label_translation_parameters' => ['%locale%' => strtoupper($locale)],
                    'required' => false,
                    'mapped' => false,
                    'attr' => [
                        'class' => 'form-control',
                        'placeholder' => 'admin.role.form.description_translation.placeholder',
                        'rows' => 2,
                    ],
                ]);
        }
    }

    private function addPermissionsField(FormBuilderInterface $builder): void
    {
        $permissions = $this->permissionRepository->findGroupedByCategory();

        $builder->add('permissions', EntityType::class, [
            'class' => Permission::class,
            'choice_label' => 'name',
            'choices' => $this->flattenPermissions($permissions),
            'multiple' => true,
            'expanded' => true,
            'label' => 'admin.role.form.permissions.label',
            'help' => 'admin.role.form.permissions.help',
            'attr' => [
                'class' => 'permissions-list',
            ],
            'choice_attr' => function (Permission $permission) {
                return [
                    'class' => 'form-check-input permission-checkbox',
                    'data-category' => $permission->getCategory(),
                    'title' => $permission->getDescription(),
                ];
            },
            'group_by' => function (Permission $permission) {
                return $permission->getCategory() ?? 'UNCATEGORIZED';
            },
        ]);
    }

    private function flattenPermissions(array $groupedPermissions): array
    {
        $flat = [];
        foreach ($groupedPermissions as $permissions) {
            foreach ($permissions as $permission) {
                $flat[] = $permission;
            }
        }
        return $flat;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Role::class,
            'translation_domain' => 'admin',
            'enable_permissions' => true,
            'enable_translations' => false,
            'supported_locales' => ['fr', 'en', 'de', 'es'],
            'attr' => [
                'novalidate' => 'novalidate',
                'class' => 'needs-validation',
            ],
        ]);

        $resolver->setAllowedTypes('enable_permissions', 'bool');
        $resolver->setAllowedTypes('enable_translations', 'bool');
        $resolver->setAllowedTypes('supported_locales', 'array');
    }
}