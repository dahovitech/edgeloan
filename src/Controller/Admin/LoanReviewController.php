<?php

namespace App\Controller\Admin;

use App\Entity\Enum\ReviewDecision;
use App\Entity\Enum\ReviewStatus;
use App\Entity\LoanReview;
use App\Form\LoanReviewType;
use App\Repository\LoanReviewRepository;
use App\Service\LoanReviewService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/admin/loan-reviews', name: 'admin_loan_review_')]
#[IsGranted('ROLE_REVIEWER')]
class LoanReviewController extends AbstractController
{
    public function __construct(
        private LoanReviewService $reviewService,
        private LoanReviewRepository $reviewRepository,
        private EntityManagerInterface $entityManager,
        private TranslatorInterface $translator
    ) {}

    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        $user = $this->getUser();
        
        $pendingReviews = $this->reviewService->getPendingReviewsForReviewer($user);
        $inProgressReviews = $this->reviewService->getInProgressReviewsForReviewer($user);
        $workload = $this->reviewRepository->getReviewerWorkload($user);
        
        return $this->render('admin/loan_review/index.html.twig', [
            'pending_reviews' => $pendingReviews,
            'in_progress_reviews' => $inProgressReviews,
            'workload' => $workload,
            'user' => $user
        ]);
    }

    #[Route('/queue', name: 'queue', methods: ['GET'])]
    #[IsGranted('ROLE_SENIOR_REVIEWER')]
    public function queue(): Response
    {
        $allReviews = $this->reviewRepository->findBy([], ['createdAt' => 'DESC']);
        $workloads = $this->reviewService->getReviewerWorkloads();
        $statistics = $this->reviewRepository->getReviewStatistics();
        
        return $this->render('admin/loan_review/queue.html.twig', [
            'reviews' => $allReviews,
            'workloads' => $workloads,
            'statistics' => $statistics
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'], requirements: ['id' => '[0-9a-f-]+'])]
    public function show(string $id): Response
    {
        $review = $this->reviewRepository->find($id);
        if (!$review) {
            throw $this->createNotFoundException($this->translator->trans('review.not_found'));
        }

        // Check if user can access this review
        if (!$this->canAccessReview($review)) {
            throw $this->createAccessDeniedException($this->translator->trans('review.access_denied'));
        }

        $application = $review->getApplication();
        $documents = $application->getDocuments();
        $timeline = $application->getStatusHistory();

        return $this->render('admin/loan_review/show.html.twig', [
            'review' => $review,
            'application' => $application,
            'documents' => $documents,
            'timeline' => $timeline
        ]);
    }

    #[Route('/{id}/conduct', name: 'conduct', methods: ['GET', 'POST'], requirements: ['id' => '[0-9a-f-]+'])]
    public function conduct(string $id, Request $request): Response
    {
        $review = $this->reviewRepository->find($id);
        if (!$review) {
            throw $this->createNotFoundException($this->translator->trans('review.not_found'));
        }

        // Check if user can conduct this review
        if (!$this->canConductReview($review)) {
            throw $this->createAccessDeniedException($this->translator->trans('review.access_denied'));
        }

        // Start the review if it's pending
        if ($review->getStatus() === ReviewStatus::PENDING) {
            $this->reviewService->startReview($review);
        }

        $form = $this->createForm(LoanReviewType::class, $review);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $submitType = $form->getClickedButton()->getName();
            
            if ($submitType === 'submit') {
                // Complete the review
                try {
                    $this->reviewService->completeReview($review);
                    $this->addFlash('success', $this->translator->trans('review.completed_successfully'));
                    
                    return $this->redirectToRoute('admin_loan_review_show', ['id' => $id]);
                } catch (\Exception $e) {
                    $this->addFlash('error', $this->translator->trans('review.completion_error', ['%error%' => $e->getMessage()]));
                }
            } elseif ($submitType === 'save_draft') {
                // Save as draft (in progress)
                $this->entityManager->flush();
                $this->addFlash('success', $this->translator->trans('review.draft_saved'));
            }
        }

        $application = $review->getApplication();
        $documents = $application->getDocuments();

        return $this->render('admin/loan_review/conduct.html.twig', [
            'review' => $review,
            'application' => $application,
            'documents' => $documents,
            'form' => $form->createView()
        ]);
    }

    #[Route('/{id}/escalate', name: 'escalate', methods: ['POST'], requirements: ['id' => '[0-9a-f-]+'])]
    public function escalate(string $id, Request $request): Response
    {
        $review = $this->reviewRepository->find($id);
        if (!$review) {
            throw $this->createNotFoundException($this->translator->trans('review.not_found'));
        }

        // Check if user can escalate this review
        if (!$this->canConductReview($review)) {
            throw $this->createAccessDeniedException($this->translator->trans('review.access_denied'));
        }

        $reason = $request->request->get('reason');
        if (empty($reason)) {
            $this->addFlash('error', $this->translator->trans('review.escalation_reason_required'));
            return $this->redirectToRoute('admin_loan_review_conduct', ['id' => $id]);
        }

        try {
            $escalatedReview = $this->reviewService->escalateReview($review, $reason);
            $this->addFlash('success', $this->translator->trans('review.escalated_successfully'));
            
            return $this->redirectToRoute('admin_loan_review_show', ['id' => $escalatedReview->getId()]);
        } catch (\Exception $e) {
            $this->addFlash('error', $this->translator->trans('review.escalation_error', ['%error%' => $e->getMessage()]));
            return $this->redirectToRoute('admin_loan_review_conduct', ['id' => $id]);
        }
    }

    #[Route('/assign/{applicationId}', name: 'assign', methods: ['POST'])]
    #[IsGranted('ROLE_SENIOR_REVIEWER')]
    public function assign(string $applicationId, Request $request): Response
    {
        $application = $this->entityManager->getRepository(\App\Entity\LoanApplication::class)->findByUuid($applicationId);
        if (!$application) {
            throw $this->createNotFoundException($this->translator->trans('loan_application.not_found'));
        }

        $reviewerId = $request->request->get('reviewer_id');
        $reviewer = null;
        
        if ($reviewerId) {
            $reviewer = $this->entityManager->getRepository(\App\Entity\User::class)->find($reviewerId);
        }

        try {
            $review = $this->reviewService->assignApplicationForReview($application, $reviewer);
            $this->addFlash('success', $this->translator->trans('review.assigned_successfully'));
            
            return $this->redirectToRoute('admin_loan_review_show', ['id' => $review->getId()]);
        } catch (\Exception $e) {
            $this->addFlash('error', $this->translator->trans('review.assignment_error', ['%error%' => $e->getMessage()]));
            return $this->redirectToRoute('admin_loan_review_queue');
        }
    }

    #[Route('/statistics', name: 'statistics', methods: ['GET'])]
    #[IsGranted('ROLE_SENIOR_REVIEWER')]
    public function statistics(): Response
    {
        $statistics = $this->reviewRepository->getReviewStatistics();
        $workloads = $this->reviewService->getReviewerWorkloads();
        $overdueReviews = $this->reviewRepository->findOverdueReviews();
        $averageReviewTime = $this->reviewRepository->findAverageReviewTime();

        return $this->render('admin/loan_review/statistics.html.twig', [
            'statistics' => $statistics,
            'workloads' => $workloads,
            'overdue_reviews' => $overdueReviews,
            'average_review_time' => $averageReviewTime
        ]);
    }

    #[Route('/auto-assign', name: 'auto_assign', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function autoAssign(): Response
    {
        try {
            $assignedCount = $this->reviewService->autoAssignOverdueApplications();
            $this->addFlash('success', $this->translator->trans('review.auto_assigned', ['%count%' => $assignedCount]));
        } catch (\Exception $e) {
            $this->addFlash('error', $this->translator->trans('review.auto_assign_error', ['%error%' => $e->getMessage()]));
        }

        return $this->redirectToRoute('admin_loan_review_queue');
    }

    /**
     * Check if the current user can access a review
     */
    private function canAccessReview(LoanReview $review): bool
    {
        $user = $this->getUser();
        
        // Reviewers can access their own reviews
        if ($review->getReviewer() === $user) {
            return true;
        }
        
        // Senior reviewers and admins can access all reviews
        if ($this->isGranted('ROLE_SENIOR_REVIEWER') || $this->isGranted('ROLE_ADMIN')) {
            return true;
        }
        
        return false;
    }

    /**
     * Check if the current user can conduct a review
     */
    private function canConductReview(LoanReview $review): bool
    {
        $user = $this->getUser();
        
        // Only the assigned reviewer can conduct the review
        if ($review->getReviewer() !== $user) {
            return false;
        }
        
        // Review must not be completed
        if ($review->getStatus() === ReviewStatus::COMPLETED) {
            return false;
        }
        
        return true;
    }
}