<?php

namespace App\Repository;

use App\Entity\LoanReview;
use App\Entity\LoanApplication;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LoanReview>
 */
class LoanReviewRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LoanReview::class);
    }

    public function findByApplication(LoanApplication $application): array
    {
        return $this->createQueryBuilder('lr')
            ->where('lr.application = :application')
            ->setParameter('application', $application)
            ->orderBy('lr.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByReviewer(User $reviewer): array
    {
        return $this->createQueryBuilder('lr')
            ->where('lr.reviewer = :reviewer')
            ->setParameter('reviewer', $reviewer)
            ->orderBy('lr.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findPendingReviews(): array
    {
        return $this->createQueryBuilder('lr')
            ->where('lr.status = :status')
            ->setParameter('status', 'pending')
            ->orderBy('lr.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findInProgressReviews(): array
    {
        return $this->createQueryBuilder('lr')
            ->where('lr.status = :status')
            ->setParameter('status', 'in_progress')
            ->orderBy('lr.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findCompletedReviews(): array
    {
        return $this->createQueryBuilder('lr')
            ->where('lr.status = :status')
            ->setParameter('status', 'completed')
            ->orderBy('lr.completedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByStatus(string $status): array
    {
        return $this->createQueryBuilder('lr')
            ->where('lr.status = :status')
            ->setParameter('status', $status)
            ->orderBy('lr.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByDecision(string $decision): array
    {
        return $this->createQueryBuilder('lr')
            ->where('lr.decision = :decision')
            ->setParameter('decision', $decision)
            ->orderBy('lr.completedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findOverdueReviews(int $daysOverdue = 7): array
    {
        $cutoffDate = new \DateTimeImmutable("-{$daysOverdue} days");
        
        return $this->createQueryBuilder('lr')
            ->where('lr.status IN (:statuses)')
            ->andWhere('lr.createdAt <= :cutoffDate')
            ->setParameter('statuses', ['pending', 'in_progress'])
            ->setParameter('cutoffDate', $cutoffDate)
            ->orderBy('lr.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function getReviewerWorkload(User $reviewer): array
    {
        $qb = $this->createQueryBuilder('lr');
        
        $total = $qb->select('COUNT(lr.id)')
            ->where('lr.reviewer = :reviewer')
            ->setParameter('reviewer', $reviewer)
            ->getQuery()
            ->getSingleScalarResult();

        $pending = $qb->select('COUNT(lr.id)')
            ->where('lr.reviewer = :reviewer')
            ->andWhere('lr.status = :status')
            ->setParameter('reviewer', $reviewer)
            ->setParameter('status', 'pending')
            ->getQuery()
            ->getSingleScalarResult();

        $inProgress = $qb->select('COUNT(lr.id)')
            ->where('lr.reviewer = :reviewer')
            ->andWhere('lr.status = :status')
            ->setParameter('reviewer', $reviewer)
            ->setParameter('status', 'in_progress')
            ->getQuery()
            ->getSingleScalarResult();

        $completed = $qb->select('COUNT(lr.id)')
            ->where('lr.reviewer = :reviewer')
            ->andWhere('lr.status = :status')
            ->setParameter('reviewer', $reviewer)
            ->setParameter('status', 'completed')
            ->getQuery()
            ->getSingleScalarResult();

        return [
            'total' => (int) $total,
            'pending' => (int) $pending,
            'in_progress' => (int) $inProgress,
            'completed' => (int) $completed,
            'active' => (int) $pending + (int) $inProgress,
        ];
    }

    public function getReviewStatistics(): array
    {
        $qb = $this->createQueryBuilder('lr');
        
        $total = $qb->select('COUNT(lr.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $approved = $qb->select('COUNT(lr.id)')
            ->where('lr.decision = :decision')
            ->setParameter('decision', 'approved')
            ->getQuery()
            ->getSingleScalarResult();

        $rejected = $qb->select('COUNT(lr.id)')
            ->where('lr.decision = :decision')
            ->setParameter('decision', 'rejected')
            ->getQuery()
            ->getSingleScalarResult();

        $needsInfo = $qb->select('COUNT(lr.id)')
            ->where('lr.decision = :decision')
            ->setParameter('decision', 'needs_info')
            ->getQuery()
            ->getSingleScalarResult();

        $averageScore = $qb->select('AVG(lr.score)')
            ->where('lr.score IS NOT NULL')
            ->getQuery()
            ->getSingleScalarResult();

        return [
            'total' => (int) $total,
            'approved' => (int) $approved,
            'rejected' => (int) $rejected,
            'needs_info' => (int) $needsInfo,
            'average_score' => $averageScore ? round($averageScore, 1) : null,
            'approval_rate' => $total > 0 ? round(($approved / $total) * 100, 1) : 0,
        ];
    }

    public function findReviewersWithPendingWork(): array
    {
        return $this->createQueryBuilder('lr')
            ->select('DISTINCT u.id, u.email, u.firstName, u.lastName, COUNT(lr.id) as pending_count')
            ->leftJoin('lr.reviewer', 'u')
            ->where('lr.status IN (:statuses)')
            ->setParameter('statuses', ['pending', 'in_progress'])
            ->groupBy('u.id')
            ->orderBy('pending_count', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findAverageReviewTime(): ?int
    {
        $qb = $this->createQueryBuilder('lr');
        
        $completedReviews = $qb->select('lr.startedAt, lr.completedAt')
            ->where('lr.status = :status')
            ->andWhere('lr.startedAt IS NOT NULL')
            ->andWhere('lr.completedAt IS NOT NULL')
            ->setParameter('status', 'completed')
            ->getQuery()
            ->getResult();

        if (empty($completedReviews)) {
            return null;
        }

        $totalMinutes = 0;
        $count = 0;

        foreach ($completedReviews as $review) {
            if ($review['startedAt'] && $review['completedAt']) {
                $diff = $review['startedAt']->diff($review['completedAt']);
                $totalMinutes += ($diff->days * 24 * 60) + ($diff->h * 60) + $diff->i;
                $count++;
            }
        }

        return $count > 0 ? (int) ($totalMinutes / $count) : null;
    }
}