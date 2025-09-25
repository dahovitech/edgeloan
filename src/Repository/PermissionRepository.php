<?php

namespace App\Repository;

use App\Entity\Permission;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;

/**
 * @extends ServiceEntityRepository<Permission>
 */
class PermissionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Permission::class);
    }

    /**
     * Find all active permissions ordered by category and name
     *
     * @return Permission[]
     */
    public function findActivePermissions(): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('p.category', 'ASC')
            ->addOrderBy('p.priority', 'DESC')
            ->addOrderBy('p.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find permissions grouped by category
     */
    public function findGroupedByCategory(): array
    {
        $permissions = $this->findActivePermissions();
        $grouped = [];

        foreach ($permissions as $permission) {
            $category = $permission->getCategory() ?? 'UNCATEGORIZED';
            if (!isset($grouped[$category])) {
                $grouped[$category] = [];
            }
            $grouped[$category][] = $permission;
        }

        return $grouped;
    }

    /**
     * Find permission by code
     */
    public function findByCode(string $code): ?Permission
    {
        return $this->createQueryBuilder('p')
            ->where('p.code = :code')
            ->setParameter('code', strtoupper($code))
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find permissions by category
     *
     * @return Permission[]
     */
    public function findByCategory(string $category): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.category = :category')
            ->andWhere('p.isActive = :active')
            ->setParameter('category', $category)
            ->setParameter('active', true)
            ->orderBy('p.priority', 'DESC')
            ->addOrderBy('p.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find permissions by resource and action
     *
     * @return Permission[]
     */
    public function findByResourceAndAction(?string $resource = null, ?string $action = null): array
    {
        $qb = $this->createQueryBuilder('p')
            ->where('p.isActive = :active')
            ->setParameter('active', true);

        if ($resource) {
            $qb->andWhere('p.resource = :resource')
               ->setParameter('resource', $resource);
        }

        if ($action) {
            $qb->andWhere('p.action = :action')
               ->setParameter('action', strtoupper($action));
        }

        return $qb->orderBy('p.priority', 'DESC')
                  ->addOrderBy('p.name', 'ASC')
                  ->getQuery()
                  ->getResult();
    }

    /**
     * Find permissions not assigned to any role
     *
     * @return Permission[]
     */
    public function findUnassignedPermissions(): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.roles', 'r')
            ->where('r.id IS NULL')
            ->andWhere('p.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('p.category', 'ASC')
            ->addOrderBy('p.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get permission statistics
     */
    public function getPermissionStatistics(): array
    {
        return [
            'total_permissions' => $this->countTotal(),
            'active_permissions' => $this->countActive(),
            'system_permissions' => $this->countSystemPermissions(),
            'permissions_by_category' => $this->getPermissionsByCategory(),
            'unassigned_permissions' => $this->countUnassignedPermissions(),
        ];
    }

    private function countTotal(): int
    {
        return (int) $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function countActive(): int
    {
        return (int) $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->where('p.isActive = :active')
            ->setParameter('active', true)
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function countSystemPermissions(): int
    {
        return (int) $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->where('p.isSystemPermission = :systemPermission')
            ->setParameter('systemPermission', true)
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function getPermissionsByCategory(): array
    {
        return $this->createQueryBuilder('p')
            ->select('p.category, COUNT(p.id) as count')
            ->where('p.isActive = :active')
            ->setParameter('active', true)
            ->groupBy('p.category')
            ->orderBy('p.category', 'ASC')
            ->getQuery()
            ->getResult();
    }

    private function countUnassignedPermissions(): int
    {
        return (int) $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->leftJoin('p.roles', 'r')
            ->where('r.id IS NULL')
            ->andWhere('p.isActive = :active')
            ->setParameter('active', true)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Search permissions by name, code, or description
     *
     * @return Permission[]
     */
    public function searchByKeyword(string $keyword): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.name LIKE :keyword OR p.description LIKE :keyword OR p.code LIKE :keyword')
            ->andWhere('p.isActive = :active')
            ->setParameter('keyword', '%' . $keyword . '%')
            ->setParameter('active', true)
            ->orderBy('p.category', 'ASC')
            ->addOrderBy('p.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find permissions with role count
     */
    public function findWithRoleCount(): array
    {
        return $this->createQueryBuilder('p')
            ->select('p', 'COUNT(r.id) as role_count')
            ->leftJoin('p.roles', 'r')
            ->where('p.isActive = :active')
            ->setParameter('active', true)
            ->groupBy('p.id')
            ->orderBy('p.category', 'ASC')
            ->addOrderBy('p.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find permissions that can be deleted (not system permissions and not assigned to any role)
     *
     * @return Permission[]
     */
    public function findDeletablePermissions(): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.roles', 'r')
            ->where('p.isSystemPermission = :systemPermission')
            ->andWhere('r.id IS NULL')
            ->setParameter('systemPermission', false)
            ->orderBy('p.category', 'ASC')
            ->addOrderBy('p.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get all available categories
     *
     * @return string[]
     */
    public function getAvailableCategories(): array
    {
        $result = $this->createQueryBuilder('p')
            ->select('DISTINCT p.category')
            ->where('p.isActive = :active')
            ->andWhere('p.category IS NOT NULL')
            ->setParameter('active', true)
            ->orderBy('p.category', 'ASC')
            ->getQuery()
            ->getResult();

        return array_column($result, 'category');
    }

    /**
     * Get all available resources
     *
     * @return string[]
     */
    public function getAvailableResources(): array
    {
        $result = $this->createQueryBuilder('p')
            ->select('DISTINCT p.resource')
            ->where('p.isActive = :active')
            ->andWhere('p.resource IS NOT NULL')
            ->setParameter('active', true)
            ->orderBy('p.resource', 'ASC')
            ->getQuery()
            ->getResult();

        return array_column($result, 'resource');
    }

    /**
     * Get all available actions
     *
     * @return string[]
     */
    public function getAvailableActions(): array
    {
        $result = $this->createQueryBuilder('p')
            ->select('DISTINCT p.action')
            ->where('p.isActive = :active')
            ->andWhere('p.action IS NOT NULL')
            ->setParameter('active', true)
            ->orderBy('p.action', 'ASC')
            ->getQuery()
            ->getResult();

        return array_column($result, 'action');
    }

    /**
     * Custom query builder for advanced filtering
     */
    public function createFilterQueryBuilder(array $filters = []): QueryBuilder
    {
        $qb = $this->createQueryBuilder('p');

        if (isset($filters['active'])) {
            $qb->andWhere('p.isActive = :active')
               ->setParameter('active', $filters['active']);
        }

        if (isset($filters['systemPermission'])) {
            $qb->andWhere('p.isSystemPermission = :systemPermission')
               ->setParameter('systemPermission', $filters['systemPermission']);
        }

        if (isset($filters['category'])) {
            $qb->andWhere('p.category = :category')
               ->setParameter('category', $filters['category']);
        }

        if (isset($filters['resource'])) {
            $qb->andWhere('p.resource = :resource')
               ->setParameter('resource', $filters['resource']);
        }

        if (isset($filters['action'])) {
            $qb->andWhere('p.action = :action')
               ->setParameter('action', strtoupper($filters['action']));
        }

        if (isset($filters['hasRoles']) && $filters['hasRoles']) {
            $qb->join('p.roles', 'r');
        } elseif (isset($filters['hasRoles']) && !$filters['hasRoles']) {
            $qb->leftJoin('p.roles', 'r')
               ->andWhere('r.id IS NULL');
        }

        return $qb->orderBy('p.category', 'ASC')
                  ->addOrderBy('p.priority', 'DESC')
                  ->addOrderBy('p.name', 'ASC');
    }

    /**
     * Check if a permission exists for a specific resource and action
     */
    public function existsForResourceAction(string $resource, string $action): bool
    {
        $count = (int) $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->where('p.resource = :resource')
            ->andWhere('p.action = :action')
            ->andWhere('p.isActive = :active')
            ->setParameter('resource', $resource)
            ->setParameter('action', strtoupper($action))
            ->setParameter('active', true)
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
    }
}