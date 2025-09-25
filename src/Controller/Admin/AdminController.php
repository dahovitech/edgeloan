<?php

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\LanguageRepository;
use App\Service\LoanService;

#[Route('', name: 'admin_')]
class AdminController extends AbstractController
{
    #[Route('/', name: 'dashboard')]
    public function dashboard(
        LanguageRepository $languageRepository,
        LoanService $loanService
    ): Response {
        $languages = $languageRepository->getAllOrderedBySortOrder();
        
        // Récupérer les statistiques des prêts
        $loanStatistics = $loanService->getDashboardStatistics();
        $recentApplications = $loanService->getApplicationRepository()->findRecentApplications(5);
        $overduePayments = $loanService->getOverduePayments();

        return $this->render('admin/dashboard.html.twig', [
            'languages' => $languages,
            'admin_languages' => $languageRepository->findActiveLanguages(),
            'loan_statistics' => $loanStatistics,
            'recent_loan_applications' => $recentApplications,
            'overdue_payments' => count($overduePayments),
        ]);
    }
}
