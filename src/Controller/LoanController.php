<?php

namespace App\Controller;

use App\Entity\LoanApplication;
use App\Entity\LoanContract;
use App\Entity\LoanDocument;
use App\Entity\Media;
use App\Repository\LoanApplicationRepository;
use App\Service\LoanService;
use Doctrine\ORM\EntityManagerInterface;
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
        private ValidatorInterface $validator
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
            $data = [
                'loan_type' => $request->request->get('loan_type'),
                'requested_amount' => (float) $request->request->get('requested_amount'),
                'requested_duration' => (int) $request->request->get('requested_duration'),
                'purpose' => $request->request->get('purpose'),
            ];

            // Validation basique
            $errors = [];
            if (empty($data['loan_type'])) {
                $errors[] = 'Le type de prêt est requis';
            }
            if ($data['requested_amount'] <= 0) {
                $errors[] = 'Le montant doit être supérieur à zéro';
            }
            if ($data['requested_duration'] <= 0) {
                $errors[] = 'La durée doit être supérieure à zéro';
            }

            if (empty($errors)) {
                try {
                    $application = $this->loanService->createLoanApplication($this->getUser(), $data);
                    $this->addFlash('success', 'Votre demande de prêt a été soumise avec succès !');
                    return $this->redirectToRoute('loan_application_show', ['id' => $application->getId()]);
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Erreur lors de la soumission: ' . $e->getMessage());
                }
            } else {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error);
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

        /** @var UploadedFile $uploadedFile */
        $uploadedFile = $request->files->get('document');
        $documentType = $request->request->get('document_type');
        $description = $request->request->get('description');

        if (!$uploadedFile || !$documentType) {
            $this->addFlash('error', 'Fichier et type de document requis.');
            return $this->redirectToRoute('loan_application_show', ['id' => $application->getId()]);
        }

        try {
            // Créer l'entité Media (simplifiée - à adapter selon votre système)
            $media = new Media();
            $media->setOriginalName($uploadedFile->getClientOriginalName())
                  ->setFilename(uniqid() . '.' . $uploadedFile->guessExtension())
                  ->setMimeType($uploadedFile->getMimeType())
                  ->setSize($uploadedFile->getSize());

            // Déplacer le fichier (adapté selon votre configuration)
            $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/documents';
            $uploadedFile->move($uploadDir, $media->getFilename());
            $media->setPath('/uploads/documents/' . $media->getFilename());

            $this->entityManager->persist($media);

            // Créer le document de prêt
            $loanDocument = new LoanDocument();
            $loanDocument->setLoanApplication($application)
                        ->setMedia($media)
                        ->setDocumentType($documentType)
                        ->setDescription($description);

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

        $signature = $request->request->get('signature');
        
        if (empty($signature)) {
            return new JsonResponse(['success' => false, 'message' => 'Signature requise']);
        }

        try {
            $ipAddress = $request->getClientIp();
            $this->loanService->signContract($contract, $signature, $ipAddress);
            
            return new JsonResponse([
                'success' => true, 
                'message' => 'Contrat signé avec succès',
                'redirect' => $this->generateUrl('loan_application_show', ['id' => $contract->getLoanApplication()->getId()])
            ]);
        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'message' => $e->getMessage()]);
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