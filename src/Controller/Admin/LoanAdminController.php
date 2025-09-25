<?php

namespace App\Controller\Admin;

use App\Entity\LoanApplication;
use App\Entity\LoanContract;
use App\Entity\LoanPayment;
use App\Repository\LoanApplicationRepository;
use App\Repository\LoanContractRepository;
use App\Repository\LoanPaymentRepository;
use App\Service\LoanService;
use App\Service\PdfService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/loans')]
#[IsGranted('ROLE_ADMIN')]
class LoanAdminController extends AbstractController
{
    public function __construct(
        private LoanService $loanService,
        private PdfService $pdfService,
        private EntityManagerInterface $entityManager,
        private LoanApplicationRepository $applicationRepository,
        private LoanContractRepository $contractRepository,
        private LoanPaymentRepository $paymentRepository
    ) {}

    #[Route('/', name: 'admin_loans_dashboard')]
    public function dashboard(): Response
    {
        $statistics = $this->loanService->getDashboardStatistics();
        $recentApplications = $this->applicationRepository->findRecentApplications(5);
        $overduePayments = $this->loanService->getOverduePayments();
        $upcomingPayments = $this->loanService->getUpcomingPayments();

        return $this->render('admin/loans/dashboard.html.twig', [
            'statistics' => $statistics,
            'recent_applications' => $recentApplications,
            'overdue_payments' => $overduePayments,
            'upcoming_payments' => $upcomingPayments,
        ]);
    }

    #[Route('/applications', name: 'admin_loan_applications')]
    public function applications(Request $request): Response
    {
        $status = $request->query->get('status');
        $search = $request->query->get('search');

        $criteria = [];
        if ($status) {
            $criteria['status'] = $status;
        }
        if ($search) {
            $criteria['customer_name'] = $search;
        }

        $applications = empty($criteria) 
            ? $this->applicationRepository->findAll()
            : $this->applicationRepository->searchApplications($criteria);

        return $this->render('admin/loans/applications.html.twig', [
            'applications' => $applications,
            'current_status' => $status,
            'search_term' => $search,
        ]);
    }

    #[Route('/applications/{id}', name: 'admin_loan_application_show')]
    public function showApplication(LoanApplication $application): Response
    {
        return $this->render('admin/loans/application_show.html.twig', [
            'application' => $application,
            'customer' => $application->getCustomer(),
            'documents' => $application->getDocuments(),
            'payments' => $application->getPayments(),
            'contract' => $application->getContract(),
        ]);
    }

    #[Route('/applications/{id}/approve', name: 'admin_loan_application_approve', methods: ['POST'])]
    public function approveApplication(LoanApplication $application, Request $request): Response
    {
        $notes = $request->request->get('notes');

        try {
            $contract = $this->loanService->approveApplication($application, $notes);
            $this->addFlash('success', 'Demande de prêt approuvée avec succès. Le contrat a été généré.');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de l\'approbation: ' . $e->getMessage());
        }

        return $this->redirectToRoute('admin_loan_application_show', ['id' => $application->getId()]);
    }

    #[Route('/applications/{id}/reject', name: 'admin_loan_application_reject', methods: ['POST'])]
    public function rejectApplication(LoanApplication $application, Request $request): Response
    {
        $reason = $request->request->get('reason');

        if (empty($reason)) {
            $this->addFlash('error', 'Une raison de rejet est requise.');
            return $this->redirectToRoute('admin_loan_application_show', ['id' => $application->getId()]);
        }

        try {
            $this->loanService->rejectApplication($application, $reason);
            $this->addFlash('success', 'Demande de prêt rejetée.');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors du rejet: ' . $e->getMessage());
        }

        return $this->redirectToRoute('admin_loan_application_show', ['id' => $application->getId()]);
    }

    #[Route('/contracts', name: 'admin_loan_contracts')]
    public function contracts(Request $request): Response
    {
        $status = $request->query->get('status');
        
        $contracts = $status 
            ? $this->contractRepository->findByStatus($status)
            : $this->contractRepository->findAll();

        return $this->render('admin/loans/contracts.html.twig', [
            'contracts' => $contracts,
            'current_status' => $status,
        ]);
    }

    #[Route('/contracts/{id}', name: 'admin_loan_contract_show')]
    public function showContract(LoanContract $contract): Response
    {
        return $this->render('admin/loans/contract_show.html.twig', [
            'contract' => $contract,
            'application' => $contract->getLoanApplication(),
            'customer' => $contract->getLoanApplication()->getCustomer(),
        ]);
    }

    #[Route('/contracts/{id}/generate-pdf', name: 'admin_loan_contract_generate_pdf')]
    public function generateContractPdf(LoanContract $contract): Response
    {
        try {
            $pdf = $this->pdfService->generateContractPdf($contract);
            $this->addFlash('success', 'PDF du contrat généré avec succès.');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de la génération du PDF: ' . $e->getMessage());
        }

        return $this->redirectToRoute('admin_loan_contract_show', ['id' => $contract->getId()]);
    }

    #[Route('/payments', name: 'admin_loan_payments')]
    public function payments(Request $request): Response
    {
        $status = $request->query->get('status', 'all');
        
        $payments = match($status) {
            'overdue' => $this->paymentRepository->findOverduePayments(),
            'pending' => $this->paymentRepository->findPendingPayments(),
            'paid' => $this->paymentRepository->findByStatus('paid'),
            default => $this->paymentRepository->findAll()
        };

        return $this->render('admin/loans/payments.html.twig', [
            'payments' => $payments,
            'current_status' => $status,
            'statistics' => $this->paymentRepository->getPaymentStatistics(),
        ]);
    }

    #[Route('/payments/{id}/record', name: 'admin_loan_payment_record', methods: ['POST'])]
    public function recordPayment(LoanPayment $payment, Request $request): Response
    {
        $amount = (float) $request->request->get('amount');
        $receiptNumber = $request->request->get('receipt_number');
        $notes = $request->request->get('notes');

        if ($amount <= 0) {
            $this->addFlash('error', 'Le montant doit être supérieur à zéro.');
            return $this->redirectToRoute('admin_loan_payments');
        }

        try {
            $this->loanService->recordPayment($payment, $amount, $receiptNumber);
            
            if ($notes) {
                $payment->setNotes($notes);
                $this->entityManager->flush();
            }

            $this->addFlash('success', 'Paiement enregistré avec succès.');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de l\'enregistrement: ' . $e->getMessage());
        }

        return $this->redirectToRoute('admin_loan_payments');
    }

    #[Route('/statistics', name: 'admin_loan_statistics')]
    public function statistics(): Response
    {
        $statistics = $this->loanService->getDashboardStatistics();
        $monthlyPayments = $this->paymentRepository->getMonthlyPaymentSummary();

        return $this->render('admin/loans/statistics.html.twig', [
            'statistics' => $statistics,
            'monthly_payments' => $monthlyPayments,
        ]);
    }

    #[Route('/export', name: 'admin_loan_export')]
    public function export(Request $request): Response
    {
        $format = $request->query->get('format', 'csv');
        $type = $request->query->get('type', 'applications');

        // Logique d'exportation à implémenter selon les besoins
        $this->addFlash('info', 'Fonctionnalité d\'exportation à implémenter.');

        return $this->redirectToRoute('admin_loans_dashboard');
    }
}