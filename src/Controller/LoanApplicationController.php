<?php

namespace App\Controller;

use App\Entity\LoanApplication;
use App\Entity\Enum\LoanStatus;
use App\Form\LoanApplicationFormType;
use App\Repository\LoanApplicationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/loan-application', name: 'loan_application_')]
#[IsGranted('ROLE_USER')]
class LoanApplicationController extends AbstractController
{
    public function __construct(
        private LoanApplicationRepository $loanApplicationRepository,
        private EntityManagerInterface $entityManager,
        private TranslatorInterface $translator
    ) {}

    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        $user = $this->getUser();
        $applications = $this->loanApplicationRepository->findByCustomer($user);
        
        return $this->render('loan_application/index.html.twig', [
            'applications' => $applications,
            'statistics' => [
                'total' => count($applications),
                'draft' => count(array_filter($applications, fn($app) => $app->getStatus() === 'draft')),
                'pending' => count(array_filter($applications, fn($app) => in_array($app->getStatus(), ['submitted', 'under_review']))),
                'approved' => count(array_filter($applications, fn($app) => $app->getStatus() === 'approved')),
            ]
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $application = new LoanApplication();
        $application->setApplicant($this->getUser());
        
        $form = $this->createForm(LoanApplicationFormType::class, $application);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Determine the action based on which button was clicked
            $isDraft = $form->get('save')->isClicked();
            $isSubmitted = $form->get('submit')->isClicked();

            if ($isDraft) {
                $application->setStatus('draft');
                $flashMessage = 'loan.message.draft_saved';
                $redirectRoute = 'loan_application_edit';
                $redirectParams = ['id' => $application->getUuid()];
            } else {
                $application->setStatus('submitted');
                $application->setSubmittedAt(new \DateTimeImmutable());
                $flashMessage = 'loan.message.application_submitted';
                $redirectRoute = 'loan_application_show';
                $redirectParams = ['id' => $application->getUuid()];
            }

            $this->entityManager->persist($application);
            $this->entityManager->flush();

            $this->addFlash('success', $this->translator->trans($flashMessage));

            return $this->redirectToRoute($redirectRoute, $redirectParams);
        }

        return $this->render('loan_application/new.html.twig', [
            'application' => $application,
            'form' => $form,
            'page_title' => $this->translator->trans('loan.page.new_application'),
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'], requirements: ['id' => '[0-9a-f-]+'])]
    public function show(string $id): Response
    {
        $application = $this->loanApplicationRepository->findByUuid($id);
        
        if (!$application) {
            throw $this->createNotFoundException('loan.error.application_not_found');
        }

        // Check if user can view this application
        if ($application->getApplicant() !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('loan.error.access_denied');
        }

        return $this->render('loan_application/show.html.twig', [
            'application' => $application,
            'page_title' => $this->translator->trans('loan.page.application_details'),
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'], requirements: ['id' => '[0-9a-f-]+'])]
    public function edit(string $id, Request $request): Response
    {
        $application = $this->loanApplicationRepository->findByUuid($id);
        
        if (!$application) {
            throw $this->createNotFoundException('loan.error.application_not_found');
        }

        // Check if user can edit this application
        if ($application->getApplicant() !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('loan.error.access_denied');
        }

        // Only allow editing if status allows it
        if (!in_array($application->getStatus(), ['draft', 'additional_info_required'])) {
            $this->addFlash('error', $this->translator->trans('loan.error.cannot_edit_submitted'));
            return $this->redirectToRoute('loan_application_show', ['id' => $id]);
        }

        $form = $this->createForm(LoanApplicationFormType::class, $application, [
            'show_admin_fields' => $this->isGranted('ROLE_ADMIN')
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Determine the action based on which button was clicked
            $isDraft = $form->get('save')->isClicked();
            $isSubmitted = $form->get('submit')->isClicked();

            if ($isDraft) {
                $application->setStatus('draft');
                $flashMessage = 'loan.message.changes_saved';
            } else {
                $application->setStatus('submitted');
                $application->setSubmittedAt(new \DateTimeImmutable());
                $flashMessage = 'loan.message.application_submitted';
            }

            $this->entityManager->flush();

            $this->addFlash('success', $this->translator->trans($flashMessage));

            return $this->redirectToRoute('loan_application_show', ['id' => $id]);
        }

        return $this->render('loan_application/edit.html.twig', [
            'application' => $application,
            'form' => $form,
            'page_title' => $this->translator->trans('loan.page.edit_application'),
        ]);
    }

    #[Route('/{id}/cancel', name: 'cancel', methods: ['POST'], requirements: ['id' => '[0-9a-f-]+'])]
    public function cancel(string $id, Request $request): Response
    {
        $application = $this->loanApplicationRepository->findByUuid($id);
        
        if (!$application) {
            throw $this->createNotFoundException('loan.error.application_not_found');
        }

        // Check if user can cancel this application
        if ($application->getApplicant() !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('loan.error.access_denied');
        }

        // Only allow cancellation if status allows it
        if (!in_array($application->getStatus(), ['draft', 'submitted', 'under_review', 'additional_info_required'])) {
            $this->addFlash('error', $this->translator->trans('loan.error.cannot_cancel'));
            return $this->redirectToRoute('loan_application_show', ['id' => $id]);
        }

        // Verify CSRF token
        if (!$this->isCsrfTokenValid('cancel_application_' . $id, $request->get('_token'))) {
            $this->addFlash('error', $this->translator->trans('error.invalid_csrf_token'));
            return $this->redirectToRoute('loan_application_show', ['id' => $id]);
        }

        $application->setStatus('cancelled');
        $this->entityManager->flush();

        $this->addFlash('success', $this->translator->trans('loan.message.application_cancelled'));

        return $this->redirectToRoute('loan_application_index');
    }

    #[Route('/{id}/duplicate', name: 'duplicate', methods: ['POST'], requirements: ['id' => '[0-9a-f-]+'])]
    public function duplicate(string $id, Request $request): Response
    {
        $originalApplication = $this->loanApplicationRepository->findByUuid($id);
        
        if (!$originalApplication) {
            throw $this->createNotFoundException('loan.error.application_not_found');
        }

        // Check if user can duplicate this application
        if ($originalApplication->getApplicant() !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('loan.error.access_denied');
        }

        // Verify CSRF token
        if (!$this->isCsrfTokenValid('duplicate_application_' . $id, $request->get('_token'))) {
            $this->addFlash('error', $this->translator->trans('error.invalid_csrf_token'));
            return $this->redirectToRoute('loan_application_show', ['id' => $id]);
        }

        // Create new application based on the original
        $newApplication = new LoanApplication();
        $newApplication->setApplicant($this->getUser());
        $newApplication->setLoanType($originalApplication->getLoanType());
        $newApplication->setAmount($originalApplication->getAmountFloat());
        $newApplication->setRequestedDuration($originalApplication->getRequestedDuration());
        $newApplication->setPurpose($originalApplication->getPurpose());
        $newApplication->setCreditScore($originalApplication->getCreditScore());
        $newApplication->setStatus('draft');

        $this->entityManager->persist($newApplication);
        $this->entityManager->flush();

        $this->addFlash('success', $this->translator->trans('loan.message.application_duplicated'));

        return $this->redirectToRoute('loan_application_edit', ['id' => $newApplication->getUuid()]);
    }

    #[Route('/{id}/calculate', name: 'calculate', methods: ['POST'], requirements: ['id' => '[0-9a-f-]+'])]
    public function calculatePayment(string $id, Request $request): Response
    {
        $application = $this->loanApplicationRepository->findByUuid($id);
        
        if (!$application) {
            throw $this->createNotFoundException('loan.error.application_not_found');
        }

        // Get parameters from request
        $amount = (float) $request->get('amount', $application->getAmountFloat());
        $duration = (int) $request->get('duration', $application->getRequestedDuration());
        $interestRate = (float) $request->get('interest_rate', $application->getInterestRateFloat() ?? 5.0);

        // Calculate monthly payment
        $monthlyPayment = $this->calculateLoanPayment($amount, $duration, $interestRate);
        $totalAmount = $monthlyPayment * $duration;
        $totalInterest = $totalAmount - $amount;

        return $this->json([
            'monthly_payment' => number_format($monthlyPayment, 2),
            'total_amount' => number_format($totalAmount, 2),
            'total_interest' => number_format($totalInterest, 2),
            'interest_rate' => $interestRate,
        ]);
    }

    private function calculateLoanPayment(float $amount, int $months, float $annualRate): float
    {
        if ($amount <= 0 || $months <= 0) {
            return 0;
        }

        if ($annualRate <= 0) {
            return $amount / $months;
        }

        $monthlyRate = $annualRate / 100 / 12;
        $factor = pow(1 + $monthlyRate, $months);
        
        return $amount * ($monthlyRate * $factor) / ($factor - 1);
    }
}