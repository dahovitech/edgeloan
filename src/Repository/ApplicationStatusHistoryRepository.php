<?php

namespace App\Repository;

use App\Entity\ApplicationStatusHistory;
use App\Entity\LoanApplication;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ApplicationStatusHistory>
 */
class ApplicationStatusHistoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ApplicationStatusHistory::class);
    }

    public function findByApplication(LoanApplication $application): array
    {
        return $this->createQueryBuilder('ash')
            ->where('ash.application = :application')
            ->setParameter('application', $application)
            ->orderBy('ash.changedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('ash')
            ->where('ash.changedBy = :user')
            ->setParameter('user', $user)
            ->orderBy('ash.changedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findAutomatedChanges(): array
    {
        return $this->createQueryBuilder('ash')
            ->where('ash.automated = :automated')
            ->setParameter('automated', true)
            ->orderBy('ash.changedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findManualChanges(): array
    {
        return $this->createQueryBuilder('ash')
            ->where('ash.automated = :automated')
            ->setParameter('automated', false)
            ->orderBy('ash.changedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByStatus(string $status): array
    {
        return $this->createQueryBuilder('ash')
            ->where('ash.newStatus = :status')
            ->setParameter('status', $status)
            ->orderBy('ash.changedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByDateRange(\DateTimeImmutable $startDate, \DateTimeImmutable $endDate): array
    {
        return $this->createQueryBuilder('ash')
            ->where('ash.changedAt BETWEEN :start AND :end')
            ->setParameter('start', $startDate)
            ->setParameter('end', $endDate)
            ->orderBy('ash.changedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function getTimelineForApplication(LoanApplication $application): array
    {
        return $this->createQueryBuilder('ash')
            ->select('ash, u')
            ->leftJoin('ash.changedBy', 'u')
            ->where('ash.application = :application')
            ->setParameter('application', $application)
            ->orderBy('ash.changedAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function getStatusStatistics(): array
    {
        $qb = $this->createQueryBuilder('ash');
        
        $statusCounts = $qb->select('ash.newStatus, COUNT(ash.id) as count')
            ->groupBy('ash.newStatus')
            ->orderBy('count', 'DESC')
            ->getQuery()
            ->getResult();

        $totalChanges = $qb->select('COUNT(ash.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $automatedChanges = $qb->select('COUNT(ash.id)')
            ->where('ash.automated = :automated')
            ->setParameter('automated', true)
            ->getQuery()
            ->getSingleScalarResult();

        return [
            'total_changes' => (int) $totalChanges,
            'automated_changes' => (int) $automatedChanges,
            'manual_changes' => (int) $totalChanges - (int) $automatedChanges,
            'status_counts' => $statusCounts,
            'automation_rate' => $totalChanges > 0 ? round(($automatedChanges / $totalChanges) * 100, 1) : 0,
        ];
    }

    public function getMostActiveUsers(int $limit = 10): array
    {
        return $this->createQueryBuilder('ash')
            ->select('u.id, u.firstName, u.lastName, u.email, COUNT(ash.id) as changes_count')
            ->leftJoin('ash.changedBy', 'u')
            ->where('ash.automated = :automated')
            ->setParameter('automated', false)
            ->groupBy('u.id')
            ->orderBy('changes_count', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function getRecentChanges(int $limit = 20): array
    {
        return $this->createQueryBuilder('ash')
            ->select('ash, la, u')
            ->leftJoin('ash.application', 'la')
            ->leftJoin('ash.changedBy', 'u')
            ->orderBy('ash.changedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function getApplicationProcessingTime(LoanApplication $application): ?array
    {
        $timeline = $this->getTimelineForApplication($application);
        
        if (empty($timeline)) {
            return null;
        }

        $firstEntry = $timeline[0];
        $lastEntry = end($timeline);
        
        $totalTime = $firstEntry->getChangedAt()->diff($lastEntry->getChangedAt());
        
        $statusDurations = [];
        for ($i = 0; $i < count($timeline) - 1; $i++) {
            $current = $timeline[$i];
            $next = $timeline[$i + 1];
            
            $duration = $current->getChangedAt()->diff($next->getChangedAt());
            $statusDurations[$current->getNewStatus()] = [
                'days' => $duration->days,
                'hours' => $duration->h,
                'minutes' => $duration->i,
            ];
        }
        
        return [
            'total_days' => $totalTime->days,
            'total_hours' => $totalTime->h,
            'status_durations' => $statusDurations,
            'started_at' => $firstEntry->getChangedAt(),
            'last_update' => $lastEntry->getChangedAt(),
        ];
    }

    public function createStatusChange(
        LoanApplication $application,
        ?string $previousStatus,
        string $newStatus,
        User $changedBy,
        ?string $reason = null,
        bool $automated = false
    ): ApplicationStatusHistory {
        $statusHistory = new ApplicationStatusHistory();
        $statusHistory->setApplication($application);
        $statusHistory->setPreviousStatus($previousStatus);
        $statusHistory->setNewStatus($newStatus);
        $statusHistory->setChangedBy($changedBy);
        $statusHistory->setReason($reason);
        $statusHistory->setAutomated($automated);

        $this->getEntityManager()->persist($statusHistory);
        
        return $statusHistory;
    }
}