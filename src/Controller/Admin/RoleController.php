<?php

namespace App\Controller\Admin;

use App\Entity\Role;
use App\Entity\Permission;
use App\Repository\RoleRepository;
use App\Repository\PermissionRepository;
use App\Form\RoleType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/admin/roles', name: 'admin_role_')]
#[IsGranted('ROLE_ADMIN')]
class RoleController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private RoleRepository $roleRepository,
        private PermissionRepository $permissionRepository,
        private TranslatorInterface $translator
    ) {}

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $search = $request->query->get('search', '');
        $filter = $request->query->get('filter', 'all');

        if ($search) {
            $roles = $this->roleRepository->searchByKeyword($search);
        } else {
            $roles = match($filter) {
                'active' => $this->roleRepository->findBy(['isActive' => true], ['priority' => 'DESC', 'name' => 'ASC']),
                'inactive' => $this->roleRepository->findBy(['isActive' => false], ['name' => 'ASC']),
                'system' => $this->roleRepository->findBy(['isSystemRole' => true], ['priority' => 'DESC']),
                'user' => $this->roleRepository->findBy(['isSystemRole' => false], ['priority' => 'DESC']),
                default => $this->roleRepository->findBy([], ['priority' => 'DESC', 'name' => 'ASC'])
            };
        }

        $statistics = $this->roleRepository->getRoleStatistics();

        return $this->render('admin/role/index.html.twig', [
            'roles' => $roles,
            'statistics' => $statistics,
            'current_search' => $search,
            'current_filter' => $filter,
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $role = new Role();
        $form = $this->createForm(RoleType::class, $role);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->entityManager->persist($role);
                $this->entityManager->flush();

                $this->addFlash('success', $this->translator->trans('admin.role.created_successfully', [
                    '%name%' => $role->getName()
                ], 'admin'));

                return $this->redirectToRoute('admin_role_show', ['id' => $role->getId()]);
            } catch (\Exception $e) {
                $this->addFlash('error', $this->translator->trans('admin.role.creation_failed', [], 'admin'));
            }
        }

        return $this->render('admin/role/new.html.twig', [
            'role' => $role,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Role $role): Response
    {
        $permissions = $role->getPermissions();
        $permissionsByCategory = [];
        
        foreach ($permissions as $permission) {
            $category = $permission->getCategory() ?? 'UNCATEGORIZED';
            if (!isset($permissionsByCategory[$category])) {
                $permissionsByCategory[$category] = [];
            }
            $permissionsByCategory[$category][] = $permission;
        }

        return $this->render('admin/role/show.html.twig', [
            'role' => $role,
            'permissions_by_category' => $permissionsByCategory,
            'users_count' => $role->getUsers()->count(),
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(Request $request, Role $role): Response
    {
        if ($role->isSystemRole()) {
            $this->addFlash('warning', $this->translator->trans('admin.role.cannot_edit_system_role', [], 'admin'));
            return $this->redirectToRoute('admin_role_show', ['id' => $role->getId()]);
        }

        $form = $this->createForm(RoleType::class, $role);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->entityManager->flush();

                $this->addFlash('success', $this->translator->trans('admin.role.updated_successfully', [
                    '%name%' => $role->getName()
                ], 'admin'));

                return $this->redirectToRoute('admin_role_show', ['id' => $role->getId()]);
            } catch (\Exception $e) {
                $this->addFlash('error', $this->translator->trans('admin.role.update_failed', [], 'admin'));
            }
        }

        return $this->render('admin/role/edit.html.twig', [
            'role' => $role,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, Role $role): Response
    {
        if ($role->isSystemRole()) {
            $this->addFlash('error', $this->translator->trans('admin.role.cannot_delete_system_role', [], 'admin'));
            return $this->redirectToRoute('admin_role_index');
        }

        if ($role->getUsers()->count() > 0) {
            $this->addFlash('error', $this->translator->trans('admin.role.cannot_delete_role_with_users', [
                '%count%' => $role->getUsers()->count()
            ], 'admin'));
            return $this->redirectToRoute('admin_role_show', ['id' => $role->getId()]);
        }

        if ($this->isCsrfTokenValid('delete'.$role->getId(), $request->request->get('_token'))) {
            try {
                $roleName = $role->getName();
                $this->entityManager->remove($role);
                $this->entityManager->flush();

                $this->addFlash('success', $this->translator->trans('admin.role.deleted_successfully', [
                    '%name%' => $roleName
                ], 'admin'));
            } catch (\Exception $e) {
                $this->addFlash('error', $this->translator->trans('admin.role.deletion_failed', [], 'admin'));
            }
        }

        return $this->redirectToRoute('admin_role_index');
    }

    #[Route('/{id}/toggle-status', name: 'toggle_status', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function toggleStatus(Request $request, Role $role): Response
    {
        if ($role->isSystemRole()) {
            $this->addFlash('error', $this->translator->trans('admin.role.cannot_modify_system_role', [], 'admin'));
            return $this->redirectToRoute('admin_role_index');
        }

        if ($this->isCsrfTokenValid('toggle'.$role->getId(), $request->request->get('_token'))) {
            try {
                $role->setIsActive(!$role->isActive());
                $this->entityManager->flush();

                $status = $role->isActive() ? 'activated' : 'deactivated';
                $this->addFlash('success', $this->translator->trans('admin.role.status_' . $status, [
                    '%name%' => $role->getName()
                ], 'admin'));
            } catch (\Exception $e) {
                $this->addFlash('error', $this->translator->trans('admin.role.status_change_failed', [], 'admin'));
            }
        }

        return $this->redirectToRoute('admin_role_show', ['id' => $role->getId()]);
    }

    #[Route('/{id}/permissions', name: 'permissions', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function managePermissions(Request $request, Role $role): Response
    {
        if ($role->isSystemRole()) {
            $this->addFlash('warning', $this->translator->trans('admin.role.cannot_edit_system_role_permissions', [], 'admin'));
            return $this->redirectToRoute('admin_role_show', ['id' => $role->getId()]);
        }

        $allPermissions = $this->permissionRepository->findGroupedByCategory();
        $currentPermissions = $role->getPermissions()->toArray();

        if ($request->isMethod('POST')) {
            $selectedPermissions = $request->request->all('permissions') ?? [];

            if ($this->isCsrfTokenValid('permissions'.$role->getId(), $request->request->get('_token'))) {
                try {
                    // Clear current permissions
                    foreach ($currentPermissions as $permission) {
                        $role->removePermission($permission);
                    }

                    // Add selected permissions
                    foreach ($selectedPermissions as $permissionId) {
                        $permission = $this->permissionRepository->find($permissionId);
                        if ($permission && $permission->isActive()) {
                            $role->addPermission($permission);
                        }
                    }

                    $this->entityManager->flush();

                    $this->addFlash('success', $this->translator->trans('admin.role.permissions_updated', [
                        '%name%' => $role->getName()
                    ], 'admin'));

                    return $this->redirectToRoute('admin_role_show', ['id' => $role->getId()]);
                } catch (\Exception $e) {
                    $this->addFlash('error', $this->translator->trans('admin.role.permissions_update_failed', [], 'admin'));
                }
            }
        }

        return $this->render('admin/role/permissions.html.twig', [
            'role' => $role,
            'all_permissions' => $allPermissions,
            'current_permissions' => array_map(fn($p) => $p->getId(), $currentPermissions),
        ]);
    }

    #[Route('/statistics', name: 'statistics', methods: ['GET'])]
    public function statistics(): Response
    {
        $roleStats = $this->roleRepository->getRoleStatistics();
        $permissionStats = $this->permissionRepository->getPermissionStatistics();
        
        $detailedRoles = $this->roleRepository->findDetailedRoleInfo();

        return $this->render('admin/role/statistics.html.twig', [
            'role_stats' => $roleStats,
            'permission_stats' => $permissionStats,
            'detailed_roles' => $detailedRoles,
        ]);
    }

    #[Route('/bulk-actions', name: 'bulk_actions', methods: ['POST'])]
    public function bulkActions(Request $request): Response
    {
        $action = $request->request->get('bulk_action');
        $selectedIds = $request->request->all('selected_roles') ?? [];

        if (empty($selectedIds)) {
            $this->addFlash('warning', $this->translator->trans('admin.role.no_roles_selected', [], 'admin'));
            return $this->redirectToRoute('admin_role_index');
        }

        $roles = $this->roleRepository->findBy(['id' => $selectedIds]);
        $processedCount = 0;

        try {
            foreach ($roles as $role) {
                if ($role->isSystemRole()) {
                    continue; // Skip system roles
                }

                switch ($action) {
                    case 'activate':
                        $role->setIsActive(true);
                        $processedCount++;
                        break;
                    case 'deactivate':
                        $role->setIsActive(false);
                        $processedCount++;
                        break;
                    case 'delete':
                        if ($role->getUsers()->count() === 0) {
                            $this->entityManager->remove($role);
                            $processedCount++;
                        }
                        break;
                }
            }

            if ($processedCount > 0) {
                $this->entityManager->flush();
                $this->addFlash('success', $this->translator->trans('admin.role.bulk_action_completed', [
                    '%count%' => $processedCount,
                    '%action%' => $action
                ], 'admin'));
            } else {
                $this->addFlash('warning', $this->translator->trans('admin.role.no_roles_processed', [], 'admin'));
            }
        } catch (\Exception $e) {
            $this->addFlash('error', $this->translator->trans('admin.role.bulk_action_failed', [], 'admin'));
        }

        return $this->redirectToRoute('admin_role_index');
    }
}