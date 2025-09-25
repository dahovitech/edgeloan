<?php

namespace App\Repository;

use App\Entity\LoanContract;
use App\Entity\LoanApplication;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LoanContract>
 */
class LoanContractRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LoanContract::class);
    }

    public function findByApplication(LoanApplication $application): ?LoanContract
    {
        return $this->createQueryBuilder('lc')
            ->where('lc.loanApplication = :application')
            ->setParameter('application', $application)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findByStatus(string $status): array
    {
        return $this->createQueryBuilder('lc')
            ->where('lc.status = :status')
            ->setParameter('status', $status)
            ->orderBy('lc.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findUnsignedContracts(): array
    {
        return $this->createQueryBuilder('lc')
            ->where('lc.status = :status')
            ->setParameter('status', 'sent')
            ->orderBy('lc.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findActiveContracts(): array
    {
        return $this->findByStatus('active');
    }

    public function findExpiringSoon(int $days = 30): array
    {
        $date = new \DateTime();
        $date->add(new \DateInterval('P' . $days . 'D'));

        return $this->createQueryBuilder('lc')
            ->join('lc.loanApplication', 'la')
            ->where('lc.status = :status')
            ->andWhere('DATE_ADD(lc.activatedAt, la.requestedDuration, \'MONTH\') <= :expiration_date')
            ->setParameter('status', 'active')
            ->setParameter('expiration_date', $date)
            ->orderBy('lc.activatedAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function getContractStatistics(): array
    {
        $qb = $this->createQueryBuilder('lc');
        
        $total = $qb->select('COUNT(lc.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $signed = $qb->select('COUNT(lc.id)')
            ->where('lc.status IN (:statuses)')
            ->setParameter('statuses', ['signed', 'active', 'completed'])
            ->getQuery()
            ->getSingleScalarResult();

        $active = $qb->select('COUNT(lc.id)')
            ->where('lc.status = :status')
            ->setParameter('status', 'active')
            ->getQuery()
            ->getSingleScalarResult();

        $pending = $qb->select('COUNT(lc.id)')
            ->where('lc.status = :status')
            ->setParameter('status', 'sent')
            ->getQuery()
            ->getSingleScalarResult();

        return [
            'total' => $total,
            'signed' => $signed,
            'active' => $active,
            'pending_signature' => $pending,
        ];
    }
}