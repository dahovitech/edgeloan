<?php

namespace App\Service;

use App\Entity\ApplicationStatusHistory;
use App\Entity\Enum\ReviewStatus;
use App\Entity\LoanApplication;
use App\Entity\LoanReview;
use App\Entity\User;
use App\Repository\ApplicationStatusHistoryRepository;
use App\Repository\LoanReviewRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Security;

class LoanReviewService
{
    private const MAX_ASSIGNMENTS_PER_REVIEWER = 10;
    
    public function __construct(
        private EntityManagerInterface $entityManager,
        private LoanReviewRepository $reviewRepository,
        private UserRepository $userRepository,
        private ApplicationStatusHistoryRepository $statusHistoryRepository,
        private LoggerInterface $logger,
        private Security $security
    ) {}

    /**
     * Assigns a loan application to a reviewer using round-robin algorithm
     */
    public function assignApplicationForReview(LoanApplication $application, ?User $preferredReviewer = null): LoanReview
    {
        // If a preferred reviewer is specified and available, use them
        if ($preferredReviewer && $this->isReviewerAvailable($preferredReviewer)) {
            $reviewer = $preferredReviewer;
        } else {
            // Find the best available reviewer using round-robin
            $reviewer = $this->findBestAvailableReviewer();
            if (!$reviewer) {
                throw new \RuntimeException('No available reviewers found');
            }
        }

        // Create the review
        $review = new LoanReview();
        $review->setApplication($application);
        $review->setReviewer($reviewer);
        $review->setReviewType('initial');
        $review->setStatus(ReviewStatus::PENDING);

        $this->entityManager->persist($review);

        // Update application status
        $previousStatus = $application->getStatus();
        $application->setStatus('under_review');
        $application->setAssignedTo($reviewer);

        // Create status history entry
        $statusHistory = $this->statusHistoryRepository->createStatusChange(
            $application,
            $previousStatus,
            'under_review',
            $this->getCurrentUser(),
            'Application assigned for review',
            true
        );

        $this->entityManager->flush();

        $this->logger->info('Application assigned for review', [
            'application_id' => $application->getUuid(),
            'reviewer_id' => $reviewer->getId(),
            'review_id' => $review->getId()
        ]);

        return $review;
    }

    /**
     * Starts a review (changes status from pending to in_progress)
     */
    public function startReview(LoanReview $review): void
    {
        if ($review->getStatus() !== ReviewStatus::PENDING) {
            throw new \InvalidArgumentException('Review must be in pending status to start');
        }

        $review->setStatus(ReviewStatus::IN_PROGRESS);
        $this->entityManager->flush();

        $this->logger->info('Review started', [
            'review_id' => $review->getId(),
            'application_id' => $review->getApplication()->getUuid()
        ]);
    }

    /**
     * Completes a review with decision
     */
    public function completeReview(LoanReview $review): void
    {
        if ($review->getStatus() !== ReviewStatus::IN_PROGRESS) {
            throw new \InvalidArgumentException('Review must be in progress to complete');
        }

        if (!$review->getDecision()) {
            throw new \InvalidArgumentException('Review must have a decision to be completed');
        }

        $review->setStatus(ReviewStatus::COMPLETED);
        
        $application = $review->getApplication();
        $previousStatus = $application->getStatus();

        // Update application status based on review decision
        $newStatus = $this->determineApplicationStatusFromDecision($review);
        $application->setStatus($newStatus);

        // Create status history entry
        $statusHistory = $this->statusHistoryRepository->createStatusChange(
            $application,
            $previousStatus,
            $newStatus,
            $review->getReviewer(),
            sprintf('Review completed with decision: %s', $review->getDecision()->value),
            false
        );

        $this->entityManager->flush();

        $this->logger->info('Review completed', [
            'review_id' => $review->getId(),
            'application_id' => $application->getUuid(),
            'decision' => $review->getDecision()->value,
            'new_status' => $newStatus
        ]);
    }

    /**
     * Escalates a review to a higher level
     */
    public function escalateReview(LoanReview $review, string $reason): LoanReview
    {
        // Create new escalated review
        $escalatedReview = new LoanReview();
        $escalatedReview->setApplication($review->getApplication());
        $escalatedReview->setReviewer($this->findSeniorReviewer());
        $escalatedReview->setReviewType('appeal');
        $escalatedReview->setStatus(ReviewStatus::PENDING);
        $escalatedReview->setComments($reason);

        $this->entityManager->persist($escalatedReview);

        // Update original review
        $review->setStatus(ReviewStatus::COMPLETED);
        
        // Update application status
        $application = $review->getApplication();
        $previousStatus = $application->getStatus();
        $application->setStatus('escalated');

        // Create status history entry
        $statusHistory = $this->statusHistoryRepository->createStatusChange(
            $application,
            $previousStatus,
            'escalated',
            $review->getReviewer(),
            'Review escalated: ' . $reason,
            false
        );

        $this->entityManager->flush();

        $this->logger->info('Review escalated', [
            'original_review_id' => $review->getId(),
            'escalated_review_id' => $escalatedReview->getId(),
            'application_id' => $application->getUuid(),
            'reason' => $reason
        ]);

        return $escalatedReview;
    }

    /**
     * Gets workload statistics for all reviewers
     */
    public function getReviewerWorkloads(): array
    {
        $reviewers = $this->getAvailableReviewers();
        $workloads = [];

        foreach ($reviewers as $reviewer) {
            $workloads[] = [
                'reviewer' => $reviewer,
                'workload' => $this->reviewRepository->getReviewerWorkload($reviewer)
            ];
        }

        // Sort by active workload (pending + in_progress)
        usort($workloads, fn($a, $b) => $a['workload']['active'] <=> $b['workload']['active']);

        return $workloads;
    }

    /**
     * Finds the best available reviewer using round-robin algorithm
     */
    private function findBestAvailableReviewer(): ?User
    {
        $workloads = $this->getReviewerWorkloads();
        
        foreach ($workloads as $workloadData) {
            $reviewer = $workloadData['reviewer'];
            $workload = $workloadData['workload'];
            
            if ($workload['active'] < self::MAX_ASSIGNMENTS_PER_REVIEWER) {
                return $reviewer;
            }
        }

        return null; // All reviewers are at capacity
    }

    /**
     * Checks if a reviewer is available for new assignments
     */
    private function isReviewerAvailable(User $reviewer): bool
    {
        $workload = $this->reviewRepository->getReviewerWorkload($reviewer);
        return $workload['active'] < self::MAX_ASSIGNMENTS_PER_REVIEWER;
    }

    /**
     * Gets all available reviewers (users with REVIEWER role)
     */
    private function getAvailableReviewers(): array
    {
        return $this->userRepository->findByRole('ROLE_REVIEWER');
    }

    /**
     * Finds a senior reviewer for escalation
     */
    private function findSeniorReviewer(): User
    {
        $seniorReviewers = $this->userRepository->findByRole('ROLE_SENIOR_REVIEWER');
        if (empty($seniorReviewers)) {
            // Fallback to admin if no senior reviewers
            $admins = $this->userRepository->findByRole('ROLE_ADMIN');
            if (empty($admins)) {
                throw new \RuntimeException('No senior reviewers or admins available for escalation');
            }
            return $admins[0];
        }

        // Return the one with least workload
        $bestReviewer = null;
        $lowestWorkload = PHP_INT_MAX;

        foreach ($seniorReviewers as $reviewer) {
            $workload = $this->reviewRepository->getReviewerWorkload($reviewer);
            if ($workload['active'] < $lowestWorkload) {
                $lowestWorkload = $workload['active'];
                $bestReviewer = $reviewer;
            }
        }

        return $bestReviewer;
    }

    /**
     * Determines the new application status based on review decision
     */
    private function determineApplicationStatusFromDecision(LoanReview $review): string
    {
        return match($review->getDecision()) {
            \App\Entity\Enum\ReviewDecision::APPROVED => 'approved',
            \App\Entity\Enum\ReviewDecision::REJECTED => 'rejected',
            \App\Entity\Enum\ReviewDecision::NEEDS_INFO => 'additional_info_requested',
            \App\Entity\Enum\ReviewDecision::ESCALATED => 'escalated',
            default => 'under_review',
        };
    }

    /**
     * Gets the current user
     */
    private function getCurrentUser(): User
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new \RuntimeException('Current user must be a User entity');
        }
        return $user;
    }

    /**
     * Gets pending reviews for a specific reviewer
     */
    public function getPendingReviewsForReviewer(User $reviewer): array
    {
        return $this->reviewRepository->findBy([
            'reviewer' => $reviewer,
            'status' => ReviewStatus::PENDING
        ], ['createdAt' => 'ASC']);
    }

    /**
     * Gets in-progress reviews for a specific reviewer
     */
    public function getInProgressReviewsForReviewer(User $reviewer): array
    {
        return $this->reviewRepository->findBy([
            'reviewer' => $reviewer,
            'status' => ReviewStatus::IN_PROGRESS
        ], ['startedAt' => 'ASC']);
    }

    /**
     * Auto-assigns overdue applications
     */
    public function autoAssignOverdueApplications(): int
    {
        $overdueApplications = $this->getOverdueApplications();
        $assigned = 0;

        foreach ($overdueApplications as $application) {
            if ($application->getStatus() === 'submitted') {
                try {
                    $this->assignApplicationForReview($application);
                    $assigned++;
                } catch (\Exception $e) {
                    $this->logger->error('Failed to auto-assign overdue application', [
                        'application_id' => $application->getUuid(),
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }

        return $assigned;
    }

    /**
     * Gets applications that have been submitted but not assigned for review
     */
    private function getOverdueApplications(int $hoursOverdue = 24): array
    {
        $cutoffTime = new \DateTimeImmutable("-{$hoursOverdue} hours");
        
        return $this->entityManager->createQuery(
            'SELECT la FROM App\Entity\LoanApplication la 
             WHERE la.status = :status 
             AND la.submittedAt < :cutoff 
             AND la.assignedTo IS NULL'
        )
        ->setParameter('status', 'submitted')
        ->setParameter('cutoff', $cutoffTime)
        ->getResult();
    }
}