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
            ->where('la.applicant = :customer')
            ->setParameter('customer', $customer)
            ->orderBy('la.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    // Alias for backward compatibility
    public function findByApplicant(User $applicant): array
    {
        return $this->findByCustomer($applicant);
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

        $totalAmount = $qb->select('SUM(la.amount)')
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
            ->join('la.applicant', 'u');

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
            $qb->andWhere('la.amount >= :min_amount')
               ->setParameter('min_amount', $criteria['min_amount']);
        }

        if (!empty($criteria['max_amount'])) {
            $qb->andWhere('la.amount <= :max_amount')
               ->setParameter('max_amount', $criteria['max_amount']);
        }

        return $qb->orderBy('la.createdAt', 'DESC')
                 ->getQuery()
                 ->getResult();
    }

    public function findByUuid(string $uuid): ?LoanApplication
    {
        return $this->createQueryBuilder('la')
            ->where('la.uuid = :uuid')
            ->setParameter('uuid', $uuid)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findByUuidAndCustomer(string $uuid, User $customer): ?LoanApplication
    {
        return $this->createQueryBuilder('la')
            ->where('la.uuid = :uuid')
            ->andWhere('la.applicant = :customer')
            ->setParameter('uuid', $uuid)
            ->setParameter('customer', $customer)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findByApplicationNumber(string $applicationNumber): ?LoanApplication
    {
        return $this->createQueryBuilder('la')
            ->where('la.applicationNumber = :applicationNumber')
            ->setParameter('applicationNumber', $applicationNumber)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findApplicationsNeedingReview(): array
    {
        return $this->createQueryBuilder('la')
            ->where('la.status IN (:statuses)')
            ->setParameter('statuses', ['submitted', 'under_review', 'additional_info_required'])
            ->orderBy('la.submittedAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findOverdueApplications(int $days = 30): array
    {
        $overdueDate = new \DateTimeImmutable("-{$days} days");
        
        return $this->createQueryBuilder('la')
            ->where('la.status IN (:statuses)')
            ->andWhere('la.submittedAt < :overdueDate')
            ->setParameter('statuses', ['submitted', 'under_review'])
            ->setParameter('overdueDate', $overdueDate)
            ->orderBy('la.submittedAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function getApplicationsByRiskLevel(string $riskLevel): array
    {
        return $this->createQueryBuilder('la')
            ->where('la.riskLevel = :riskLevel')
            ->setParameter('riskLevel', $riskLevel)
            ->orderBy('la.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function getMonthlyStatistics(\DateTimeImmutable $month): array
    {
        $startDate = $month->modify('first day of this month')->setTime(0, 0, 0);
        $endDate = $month->modify('last day of this month')->setTime(23, 59, 59);

        $qb = $this->createQueryBuilder('la');

        $applications = $qb->select('COUNT(la.id)')
            ->where('la.createdAt BETWEEN :startDate AND :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->getQuery()
            ->getSingleScalarResult();

        $approved = $qb->select('COUNT(la.id)')
            ->where('la.approvedAt BETWEEN :startDate AND :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->getQuery()
            ->getSingleScalarResult();

        $totalAmount = $qb->select('SUM(la.amount)')
            ->where('la.approvedAt BETWEEN :startDate AND :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->getQuery()
            ->getSingleScalarResult() ?? 0;

        return [
            'applications' => $applications,
            'approved' => $approved,
            'total_amount' => $totalAmount,
            'approval_rate' => $applications > 0 ? ($approved / $applications) * 100 : 0,
        ];
    }
}