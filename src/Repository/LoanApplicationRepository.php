<?php

namespace App\Repository;

use App\Entity\LoanApplication;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;

/**
 * @extends ServiceEntityRepository<LoanApplication>
 */
class LoanApplicationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LoanApplication::class);
    }

    public function findByCustomer(User $customer): array
    {
        return $this->createQueryBuilder('la')
            ->where('la.customer = :customer')
            ->setParameter('customer', $customer)
            ->orderBy('la.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByStatus(string $status): array
    {
        return $this->createQueryBuilder('la')
            ->where('la.status = :status')
            ->setParameter('status', $status)
            ->orderBy('la.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findPendingApplications(): array
    {
        return $this->findByStatus('pending');
    }

    public function findActiveLoans(): array
    {
        return $this->findByStatus('active');
    }

    public function getStatistics(): array
    {
        $qb = $this->createQueryBuilder('la');
        
        $total = $qb->select('COUNT(la.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $pending = $qb->select('COUNT(la.id)')
            ->where('la.status = :status')
            ->setParameter('status', 'pending')
            ->getQuery()
            ->getSingleScalarResult();

        $approved = $qb->select('COUNT(la.id)')
            ->where('la.status = :status')
            ->setParameter('status', 'approved')
            ->getQuery()
            ->getSingleScalarResult();

        $active = $qb->select('COUNT(la.id)')
            ->where('la.status = :status')
            ->setParameter('status', 'active')
            ->getQuery()
            ->getSingleScalarResult();

        $totalAmount = $qb->select('SUM(la.requestedAmount)')
            ->where('la.status IN (:statuses)')
            ->setParameter('statuses', ['approved', 'active'])
            ->getQuery()
            ->getSingleScalarResult() ?? 0;

        return [
            'total' => $total,
            'pending' => $pending,
            'approved' => $approved,
            'active' => $active,
            'total_amount' => $totalAmount,
        ];
    }

    public function findRecentApplications(int $limit = 10): array
    {
        return $this->createQueryBuilder('la')
            ->orderBy('la.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function searchApplications(array $criteria): array
    {
        $qb = $this->createQueryBuilder('la')
            ->join('la.customer', 'u');

        if (!empty($criteria['status'])) {
            $qb->andWhere('la.status = :status')
               ->setParameter('status', $criteria['status']);
        }

        if (!empty($criteria['customer_name'])) {
            $qb->andWhere('CONCAT(u.firstName, \' \', u.lastName) LIKE :customer_name')
               ->setParameter('customer_name', '%' . $criteria['customer_name'] . '%');
        }

        if (!empty($criteria['loan_type'])) {
            $qb->andWhere('la.loanType = :loan_type')
               ->setParameter('loan_type', $criteria['loan_type']);
        }

        if (!empty($criteria['min_amount'])) {
            $qb->andWhere('la.requestedAmount >= :min_amount')
               ->setParameter('min_amount', $criteria['min_amount']);
        }

        if (!empty($criteria['max_amount'])) {
            $qb->andWhere('la.requestedAmount <= :max_amount')
               ->setParameter('max_amount', $criteria['max_amount']);
        }

        return $qb->orderBy('la.createdAt', 'DESC')
                 ->getQuery()
                 ->getResult();
    }
}