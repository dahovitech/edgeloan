<?php

namespace App\Controller;

use App\Entity\LoanApplication;
use App\Entity\LoanContract;
use App\Entity\LoanDocument;
use App\Entity\Media;
use App\Repository\LoanApplicationRepository;
use App\Service\LoanService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

#[Route('/loans')]
#[IsGranted('ROLE_USER')]
class LoanController extends AbstractController
{
    public function __construct(
        private LoanService $loanService,
        private EntityManagerInterface $entityManager,
        private LoanApplicationRepository $applicationRepository,
        private ValidatorInterface $validator,
        private LoggerInterface $logger
    ) {}

    #[Route('/', name: 'loan_dashboard')]
    public function dashboard(): Response
    {
        $user = $this->getUser();
        $applications = $this->loanService->getCustomerApplications($user);

        return $this->render('loan/dashboard.html.twig', [
            'applications' => $applications,
            'user' => $user,
        ]);
    }

    #[Route('/apply', name: 'loan_apply')]
    public function apply(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            // Vérification CSRF
            if (!$this->isCsrfTokenValid('loan_application', $request->request->get('_token'))) {
                $this->addFlash('error', 'Token de sécurité invalide.');
                return $this->render('loan/apply.html.twig');
            }

            $data = [
                'loan_type' => $request->request->get('loan_type'),
                'requested_amount' => (float) $request->request->get('requested_amount'),
                'requested_duration' => (int) $request->request->get('requested_duration'),
                'purpose' => trim($request->request->get('purpose', '')),
            ];

            // Validation étendue
            $violations = $this->validator->validate($data, [
                'loan_type' => [
                    new \Symfony\Component\Validator\Constraints\NotBlank(['message' => 'Le type de prêt est requis']),
                    new \Symfony\Component\Validator\Constraints\Choice([
                        'choices' => ['personal', 'business', 'auto', 'home', 'education'],
                        'message' => 'Type de prêt invalide'
                    ])
                ],
                'requested_amount' => [
                    new \Symfony\Component\Validator\Constraints\NotBlank(['message' => 'Le montant est requis']),
                    new \Symfony\Component\Validator\Constraints\Positive(['message' => 'Le montant doit être positif']),
                    new \Symfony\Component\Validator\Constraints\LessThanOrEqual([
                        'value' => 500000,
                        'message' => 'Le montant ne peut pas dépasser 500 000€'
                    ])
                ],
                'requested_duration' => [
                    new \Symfony\Component\Validator\Constraints\NotBlank(['message' => 'La durée est requise']),
                    new \Symfony\Component\Validator\Constraints\Positive(['message' => 'La durée doit être positive']),
                    new \Symfony\Component\Validator\Constraints\LessThanOrEqual([
                        'value' => 360,
                        'message' => 'La durée ne peut pas dépasser 30 ans'
                    ])
                ]
            ]);

            if (count($violations) > 0) {
                foreach ($violations as $violation) {
                    $this->addFlash('error', $violation->getMessage());
                }
            } else {
                try {
                    $application = $this->loanService->createLoanApplication($this->getUser(), $data);
                    $this->addFlash('success', 'Votre demande de prêt a été soumise avec succès !');
                    return $this->redirectToRoute('loan_application_show', ['id' => $application->getId()]);
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Erreur lors de la soumission: ' . $e->getMessage());
                }
            }
        }

        return $this->render('loan/apply.html.twig');
    }

    #[Route('/applications/{id}', name: 'loan_application_show')]
    public function showApplication(LoanApplication $application): Response
    {
        // Vérifier que l'utilisateur peut voir cette demande
        if ($application->getCustomer() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $documentsStatus = [];
        if ($application->getDocuments()->count() > 0) {
            // Calculer le statut des documents (cette logique devrait être dans le repository)
            $documentsStatus = [
                'total' => $application->getDocuments()->count(),
                'verified' => $application->getDocuments()->filter(fn($doc) => $doc->isVerified())->count()
            ];
        }

        return $this->render('loan/application_show.html.twig', [
            'application' => $application,
            'documents' => $application->getDocuments(),
            'payments' => $application->getPayments(),
            'contract' => $application->getContract(),
            'documents_status' => $documentsStatus,
        ]);
    }

    #[Route('/applications/{id}/upload-document', name: 'loan_upload_document', methods: ['POST'])]
    public function uploadDocument(LoanApplication $application, Request $request): Response
    {
        // Vérifier l'accès
        if ($application->getCustomer() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        // Vérification CSRF
        if (!$this->isCsrfTokenValid('upload_document_' . $application->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('loan_application_show', ['id' => $application->getId()]);
        }

        /** @var UploadedFile $uploadedFile */
        $uploadedFile = $request->files->get('document');
        $documentType = $request->request->get('document_type');
        $description = trim($request->request->get('description', ''));

        if (!$uploadedFile || !$documentType) {
            $this->addFlash('error', 'Fichier et type de document requis.');
            return $this->redirectToRoute('loan_application_show', ['id' => $application->getId()]);
        }

        // Validation du fichier
        $allowedMimes = ['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'];
        $maxSize = 10 * 1024 * 1024; // 10MB
        
        if (!in_array($uploadedFile->getMimeType(), $allowedMimes, true)) {
            $this->addFlash('error', 'Format de fichier non autorisé. Seuls PDF, JPEG et PNG sont acceptés.');
            return $this->redirectToRoute('loan_application_show', ['id' => $application->getId()]);
        }

        if ($uploadedFile->getSize() > $maxSize) {
            $this->addFlash('error', 'Fichier trop volumineux. Taille maximale : 10MB.');
            return $this->redirectToRoute('loan_application_show', ['id' => $application->getId()]);
        }

        // Validation du type de document
        $validDocTypes = ['identity', 'income_proof', 'bank_statement', 'employment_proof', 'business_registration', 'tax_return', 'other'];
        if (!in_array($documentType, $validDocTypes, true)) {
            $this->addFlash('error', 'Type de document invalide.');
            return $this->redirectToRoute('loan_application_show', ['id' => $application->getId()]);
        }

        try {
            // Génération nom de fichier sécurisé
            $extension = $uploadedFile->guessExtension();
            $secureFilename = sprintf(
                'doc_%s_%s_%s.%s',
                $application->getId(),
                date('Ymd_His'),
                bin2hex(random_bytes(8)),
                $extension
            );

            // Créer l'entité Media
            $media = new Media();
            $media->setOriginalName($uploadedFile->getClientOriginalName())
                  ->setFilename($secureFilename)
                  ->setMimeType($uploadedFile->getMimeType())
                  ->setSize($uploadedFile->getSize());

            // Déplacer le fichier de manière sécurisée
            $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/documents';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $uploadedFile->move($uploadDir, $secureFilename);
            $media->setPath('/uploads/documents/' . $secureFilename);

            $this->entityManager->persist($media);

            // Créer le document de prêt
            $loanDocument = new LoanDocument();
            $loanDocument->setLoanApplication($application)
                        ->setMedia($media)
                        ->setDocumentType($documentType)
                        ->setDescription($description ?: null);

            $this->entityManager->persist($loanDocument);
            $this->entityManager->flush();

            $this->addFlash('success', 'Document téléchargé avec succès.');
            
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors du téléchargement: ' . $e->getMessage());
        }

        return $this->redirectToRoute('loan_application_show', ['id' => $application->getId()]);
    }

    #[Route('/contracts/{id}/view', name: 'loan_contract_view')]
    public function viewContract(LoanContract $contract): Response
    {
        // Vérifier l'accès
        if ($contract->getLoanApplication()->getCustomer() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('loan/contract_view.html.twig', [
            'contract' => $contract,
            'application' => $contract->getLoanApplication(),
        ]);
    }

    #[Route('/contracts/{id}/sign', name: 'loan_contract_sign', methods: ['POST'])]
    public function signContract(LoanContract $contract, Request $request): Response
    {
        // Vérifier l'accès
        if ($contract->getLoanApplication()->getCustomer() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        // Vérification CSRF pour les requêtes AJAX aussi
        if (!$this->isCsrfTokenValid('sign_contract_' . $contract->getId(), $request->request->get('_token'))) {
            return new JsonResponse(['success' => false, 'message' => 'Token de sécurité invalide']);
        }

        $signature = trim($request->request->get('signature', ''));
        
        if (empty($signature)) {
            return new JsonResponse(['success' => false, 'message' => 'Signature requise']);
        }

        // Validation de la signature (longueur minimale, format, etc.)
        if (strlen($signature) < 10) {
            return new JsonResponse(['success' => false, 'message' => 'Signature trop courte']);
        }

        // Vérifier que le contrat peut être signé
        if (!in_array($contract->getStatus(), ['sent', 'draft'], true)) {
            return new JsonResponse(['success' => false, 'message' => 'Ce contrat ne peut pas être signé']);
        }

        try {
            $ipAddress = $request->getClientIp() ?: 'unknown';
            
            // Log de la tentative de signature
            $this->logger->info('Tentative de signature de contrat', [
                'contract_id' => $contract->getId(),
                'customer_id' => $this->getUser()->getId(),
                'ip_address' => $ipAddress
            ]);
            
            $this->loanService->signContract($contract, $signature, $ipAddress);
            
            return new JsonResponse([
                'success' => true, 
                'message' => 'Contrat signé avec succès',
                'redirect' => $this->generateUrl('loan_application_show', [
                    'id' => $contract->getLoanApplication()->getId()
                ])
            ]);
            
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la signature', [
                'contract_id' => $contract->getId(),
                'error' => $e->getMessage()
            ]);
            
            return new JsonResponse(['success' => false, 'message' => 'Erreur lors de la signature: ' . $e->getMessage()]);
        }
    }

    #[Route('/profile', name: 'loan_profile')]
    public function profile(Request $request): Response
    {
        $user = $this->getUser();

        if ($request->isMethod('POST')) {
            // Mise à jour des informations de profil liées aux prêts
            $user->setPhone($request->request->get('phone'))
                ->setMonthlyIncome((float) $request->request->get('monthly_income'))
                ->setMonthlyCharges((float) $request->request->get('monthly_charges'))
                ->setEmploymentStatus($request->request->get('employment_status'))
                ->setEmployer($request->request->get('employer'));

            // Champs spécifiques aux entreprises
            if ($user->isBusiness()) {
                $user->setBusinessName($request->request->get('business_name'))
                    ->setBusinessRegistration($request->request->get('business_registration'))
                    ->setBusinessYears((int) $request->request->get('business_years'))
                    ->setAnnualRevenue((float) $request->request->get('annual_revenue'));
            }

            $this->entityManager->flush();
            $this->addFlash('success', 'Profil mis à jour avec succès.');
        }

        return $this->render('loan/profile.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/calculator', name: 'loan_calculator')]
    public function calculator(): Response
    {
        return $this->render('loan/calculator.html.twig');
    }

    #[Route('/calculator/calculate', name: 'loan_calculate', methods: ['POST'])]
    public function calculate(Request $request): JsonResponse
    {
        $amount = (float) $request->request->get('amount');
        $duration = (int) $request->request->get('duration');
        $rate = (float) $request->request->get('rate', 8.5);

        if ($amount <= 0 || $duration <= 0) {
            return new JsonResponse(['error' => 'Paramètres invalides']);
        }

        // Calcul de la mensualité
        $monthlyRate = ($rate / 100) / 12;
        $monthlyPayment = $amount * ($monthlyRate * pow(1 + $monthlyRate, $duration)) / 
                         (pow(1 + $monthlyRate, $duration) - 1);

        $totalAmount = $monthlyPayment * $duration;
        $totalInterest = $totalAmount - $amount;

        return new JsonResponse([
            'monthly_payment' => round($monthlyPayment, 2),
            'total_amount' => round($totalAmount, 2),
            'total_interest' => round($totalInterest, 2),
            'rate' => $rate
        ]);
    }

    #[Route('/help', name: 'loan_help')]
    public function help(): Response
    {
        return $this->render('loan/help.html.twig');
    }
}