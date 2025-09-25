<?php

namespace App\Repository;

use App\Entity\Role;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;

/**
 * @extends ServiceEntityRepository<Role>
 */
class RoleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Role::class);
    }

    /**
     * Find all active roles ordered by priority and name
     *
     * @return Role[]
     */
    public function findActiveRoles(): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('r.priority', 'DESC')
            ->addOrderBy('r.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find roles available for assignment (active, non-system roles)
     *
     * @return Role[]
     */
    public function findAssignableRoles(): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.isActive = :active')
            ->andWhere('r.isSystemRole = :systemRole')
            ->setParameter('active', true)
            ->setParameter('systemRole', false)
            ->orderBy('r.priority', 'DESC')
            ->addOrderBy('r.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find role by code
     */
    public function findByCode(string $code): ?Role
    {
        return $this->createQueryBuilder('r')
            ->where('r.code = :code')
            ->setParameter('code', strtoupper($code))
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find roles with specific permission
     *
     * @return Role[]
     */
    public function findByPermissionCode(string $permissionCode): array
    {
        return $this->createQueryBuilder('r')
            ->join('r.permissions', 'p')
            ->where('p.code = :permissionCode')
            ->andWhere('r.isActive = :active')
            ->setParameter('permissionCode', strtoupper($permissionCode))
            ->setParameter('active', true)
            ->orderBy('r.priority', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find roles by category
     *
     * @return Role[]
     */
    public function findByCategory(string $category): array
    {
        return $this->createQueryBuilder('r')
            ->join('r.permissions', 'p')
            ->where('p.category = :category')
            ->andWhere('r.isActive = :active')
            ->setParameter('category', $category)
            ->setParameter('active', true)
            ->groupBy('r.id')
            ->orderBy('r.priority', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get role statistics
     */
    public function getRoleStatistics(): array
    {
        $qb = $this->createQueryBuilder('r');
        
        return [
            'total_roles' => $this->countTotal(),
            'active_roles' => $this->countActive(),
            'system_roles' => $this->countSystemRoles(),
            'user_assigned_roles' => $this->countUserAssignedRoles(),
            'roles_by_priority' => $this->getRolesByPriority(),
        ];
    }

    private function countTotal(): int
    {
        return (int) $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function countActive(): int
    {
        return (int) $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.isActive = :active')
            ->setParameter('active', true)
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function countSystemRoles(): int
    {
        return (int) $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.isSystemRole = :systemRole')
            ->setParameter('systemRole', true)
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function countUserAssignedRoles(): int
    {
        return (int) $this->createQueryBuilder('r')
            ->select('COUNT(DISTINCT r.id)')
            ->join('r.users', 'u')
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function getRolesByPriority(): array
    {
        return $this->createQueryBuilder('r')
            ->select('r.priority, COUNT(r.id) as count')
            ->groupBy('r.priority')
            ->orderBy('r.priority', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Search roles by name or description
     *
     * @return Role[]
     */
    public function searchByKeyword(string $keyword): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.name LIKE :keyword OR r.description LIKE :keyword OR r.code LIKE :keyword')
            ->andWhere('r.isActive = :active')
            ->setParameter('keyword', '%' . $keyword . '%')
            ->setParameter('active', true)
            ->orderBy('r.priority', 'DESC')
            ->addOrderBy('r.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get roles with permission count
     */
    public function findWithPermissionCount(): array
    {
        return $this->createQueryBuilder('r')
            ->select('r', 'COUNT(p.id) as permission_count')
            ->leftJoin('r.permissions', 'p')
            ->where('r.isActive = :active')
            ->setParameter('active', true)
            ->groupBy('r.id')
            ->orderBy('r.priority', 'DESC')
            ->addOrderBy('r.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get roles with their permissions and user count
     */
    public function findDetailedRoleInfo(): array
    {
        return $this->createQueryBuilder('r')
            ->select('r', 'COUNT(DISTINCT u.id) as user_count', 'COUNT(DISTINCT p.id) as permission_count')
            ->leftJoin('r.users', 'u')
            ->leftJoin('r.permissions', 'p')
            ->where('r.isActive = :active')
            ->setParameter('active', true)
            ->groupBy('r.id')
            ->orderBy('r.priority', 'DESC')
            ->addOrderBy('r.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find roles that can be deleted (not system roles and have no users)
     *
     * @return Role[]
     */
    public function findDeletableRoles(): array
    {
        return $this->createQueryBuilder('r')
            ->leftJoin('r.users', 'u')
            ->where('r.isSystemRole = :systemRole')
            ->andWhere('u.id IS NULL')
            ->setParameter('systemRole', false)
            ->orderBy('r.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Custom query builder for advanced filtering
     */
    public function createFilterQueryBuilder(array $filters = []): QueryBuilder
    {
        $qb = $this->createQueryBuilder('r');

        if (isset($filters['active'])) {
            $qb->andWhere('r.isActive = :active')
               ->setParameter('active', $filters['active']);
        }

        if (isset($filters['systemRole'])) {
            $qb->andWhere('r.isSystemRole = :systemRole')
               ->setParameter('systemRole', $filters['systemRole']);
        }

        if (isset($filters['priority'])) {
            $qb->andWhere('r.priority = :priority')
               ->setParameter('priority', $filters['priority']);
        }

        if (isset($filters['hasUsers']) && $filters['hasUsers']) {
            $qb->join('r.users', 'u');
        } elseif (isset($filters['hasUsers']) && !$filters['hasUsers']) {
            $qb->leftJoin('r.users', 'u')
               ->andWhere('u.id IS NULL');
        }

        if (isset($filters['hasPermissions']) && $filters['hasPermissions']) {
            $qb->join('r.permissions', 'p');
        } elseif (isset($filters['hasPermissions']) && !$filters['hasPermissions']) {
            $qb->leftJoin('r.permissions', 'p')
               ->andWhere('p.id IS NULL');
        }

        return $qb->orderBy('r.priority', 'DESC')
                  ->addOrderBy('r.name', 'ASC');
    }
}