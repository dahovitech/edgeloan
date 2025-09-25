<?php

namespace App\Service;

use App\Entity\LoanApplication;
use App\Entity\LoanContract;
use App\Entity\LoanPayment;
use App\Entity\User;
use App\Repository\LoanApplicationRepository;
use App\Repository\LoanContractRepository;
use App\Repository\LoanPaymentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;

class LoanService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private LoanApplicationRepository $applicationRepository,
        private LoanContractRepository $contractRepository, 
        private LoanPaymentRepository $paymentRepository,
        private MailerInterface $mailer,
        private Environment $twig,
        private string $fromEmail = 'noreply@easiloan.com'
    ) {}

    public function createLoanApplication(User $customer, array $data): LoanApplication
    {
        $application = new LoanApplication();
        $application->setCustomer($customer)
                   ->setLoanType($data['loan_type'] ?? 'personal')
                   ->setRequestedAmount($data['requested_amount'])
                   ->setRequestedDuration($data['requested_duration'])
                   ->setPurpose($data['purpose'] ?? null);

        // Calculer le taux d'intérêt basé sur le profil client
        $interestRate = $this->calculateInterestRate($customer, $data['requested_amount']);
        $application->setInterestRate($interestRate);

        // Calculer la mensualité
        $monthlyPayment = $this->calculateMonthlyPayment(
            $data['requested_amount'],
            $interestRate,
            $data['requested_duration']
        );
        $application->setMonthlyPayment($monthlyPayment);

        $this->entityManager->persist($application);
        $this->entityManager->flush();

        // Envoyer email de confirmation
        $this->sendApplicationConfirmationEmail($application);

        return $application;
    }

    public function approveApplication(LoanApplication $application, string $notes = null): LoanContract
    {
        if ($application->getStatus() !== 'pending') {
            throw new \InvalidArgumentException('Seules les demandes en attente peuvent être approuvées');
        }

        // Mettre à jour le statut de la demande
        $application->setStatus('approved');
        
        if ($notes) {
            $application->setNotes($notes);
        }

        // Créer le contrat
        $contract = $this->createContract($application);

        $this->entityManager->flush();

        // Générer l'échéancier de paiement
        $this->generatePaymentSchedule($application);

        // Envoyer email d'approbation avec lien vers le contrat
        $this->sendApprovalEmail($application, $contract);

        return $contract;
    }

    public function rejectApplication(LoanApplication $application, string $reason): void
    {
        if ($application->getStatus() !== 'pending') {
            throw new \InvalidArgumentException('Seules les demandes en attente peuvent être rejetées');
        }

        $application->setStatus('rejected')
                   ->setNotes($reason);

        $this->entityManager->flush();

        // Envoyer email de rejet
        $this->sendRejectionEmail($application, $reason);
    }

    public function signContract(LoanContract $contract, string $signature, string $ipAddress): void
    {
        if ($contract->getStatus() !== 'sent') {
            throw new \InvalidArgumentException('Ce contrat ne peut pas être signé');
        }

        $contract->setCustomerSignature($signature)
                ->setSignedFromIp($ipAddress)
                ->setStatus('signed');

        // Activer le prêt
        $contract->getLoanApplication()->setStatus('active');
        $contract->setStatus('active');

        $this->entityManager->flush();

        // Envoyer confirmation de signature
        $this->sendContractSignedEmail($contract);
    }

    public function recordPayment(LoanPayment $payment, float $amount, string $receiptNumber = null): void
    {
        $payment->setPaidAmount($amount)
                ->setPaidDate(new \DateTime())
                ->setReceiptNumber($receiptNumber);

        // Déterminer le statut basé sur le montant payé
        if ($amount >= $payment->getAmount()) {
            $payment->setStatus('paid');
        } else {
            $payment->setStatus('partial');
        }

        $this->entityManager->flush();

        // Envoyer confirmation de paiement
        $this->sendPaymentConfirmationEmail($payment);
    }

    private function calculateInterestRate(User $customer, float $amount): float
    {
        // Taux de base
        $baseRate = 8.5;

        // Ajustements basés sur le profil client
        $debtRatio = $customer->getDebtRatio();
        if ($debtRatio && $debtRatio > 30) {
            $baseRate += 2.0; // +2% si ratio d'endettement > 30%
        }

        // Ajustement pour le type de client
        if ($customer->isBusiness()) {
            $baseRate += 1.5; // +1.5% pour les entreprises
        }

        // Ajustement pour le montant
        if ($amount > 50000) {
            $baseRate -= 0.5; // -0.5% pour les gros montants
        }

        // Vérification du compte
        if (!$customer->isAccountVerified()) {
            $baseRate += 1.0; // +1% si compte non vérifié
        }

        return round($baseRate, 2);
    }

    private function calculateMonthlyPayment(float $amount, float $annualRate, int $months): float
    {
        $monthlyRate = ($annualRate / 100) / 12;
        
        if ($monthlyRate == 0) {
            return $amount / $months;
        }

        $payment = $amount * ($monthlyRate * pow(1 + $monthlyRate, $months)) / 
                   (pow(1 + $monthlyRate, $months) - 1);

        return round($payment, 2);
    }

    private function createContract(LoanApplication $application): LoanContract
    {
        $contract = new LoanContract();
        $contract->setLoanApplication($application);

        // Générer le contenu du contrat
        $contractContent = $contract->generateContractContent();
        $contract->setContractContent($contractContent)
                ->setStatus('sent');

        $this->entityManager->persist($contract);

        return $contract;
    }

    private function generatePaymentSchedule(LoanApplication $application): void
    {
        $payments = $this->paymentRepository->createPaymentSchedule($application);
        
        foreach ($payments as $payment) {
            $this->entityManager->persist($payment);
        }

        $this->entityManager->flush();
    }

    private function sendApplicationConfirmationEmail(LoanApplication $application): void
    {
        $customer = $application->getCustomer();

        $email = (new Email())
            ->from($this->fromEmail)
            ->to($customer->getEmail())
            ->subject('Confirmation de votre demande de prêt')
            ->html($this->twig->render('emails/loan_application_confirmation.html.twig', [
                'application' => $application,
                'customer' => $customer
            ]));

        $this->mailer->send($email);
    }

    private function sendApprovalEmail(LoanApplication $application, LoanContract $contract): void
    {
        $customer = $application->getCustomer();

        $email = (new Email())
            ->from($this->fromEmail)
            ->to($customer->getEmail())
            ->subject('Votre demande de prêt a été approuvée !')
            ->html($this->twig->render('emails/loan_approval.html.twig', [
                'application' => $application,
                'contract' => $contract,
                'customer' => $customer
            ]));

        $this->mailer->send($email);
    }

    private function sendRejectionEmail(LoanApplication $application, string $reason): void
    {
        $customer = $application->getCustomer();

        $email = (new Email())
            ->from($this->fromEmail)
            ->to($customer->getEmail())
            ->subject('Mise à jour de votre demande de prêt')
            ->html($this->twig->render('emails/loan_rejection.html.twig', [
                'application' => $application,
                'customer' => $customer,
                'reason' => $reason
            ]));

        $this->mailer->send($email);
    }

    private function sendContractSignedEmail(LoanContract $contract): void
    {
        $customer = $contract->getLoanApplication()->getCustomer();

        $email = (new Email())
            ->from($this->fromEmail)
            ->to($customer->getEmail())
            ->subject('Contrat signé - Votre prêt est activé')
            ->html($this->twig->render('emails/contract_signed.html.twig', [
                'contract' => $contract,
                'customer' => $customer
            ]));

        $this->mailer->send($email);
    }

    private function sendPaymentConfirmationEmail(LoanPayment $payment): void
    {
        $customer = $payment->getLoanApplication()->getCustomer();

        $email = (new Email())
            ->from($this->fromEmail)
            ->to($customer->getEmail())
            ->subject('Confirmation de paiement')
            ->html($this->twig->render('emails/payment_confirmation.html.twig', [
                'payment' => $payment,
                'customer' => $customer
            ]));

        $this->mailer->send($email);
    }

    public function getDashboardStatistics(): array
    {
        return [
            'applications' => $this->applicationRepository->getStatistics(),
            'contracts' => $this->contractRepository->getContractStatistics(),
            'payments' => $this->paymentRepository->getPaymentStatistics(),
        ];
    }

    public function getCustomerApplications(User $customer): array
    {
        return $this->applicationRepository->findByCustomer($customer);
    }

    public function getOverduePayments(): array
    {
        return $this->paymentRepository->findOverduePayments();
    }

    public function getUpcomingPayments(int $days = 7): array
    {
        return $this->paymentRepository->findUpcomingPayments($days);
    }

    public function getApplicationRepository(): LoanApplicationRepository
    {
        return $this->applicationRepository;
    }

    public function getContractRepository(): LoanContractRepository
    {
        return $this->contractRepository;
    }

    public function getPaymentRepository(): LoanPaymentRepository
    {
        return $this->paymentRepository;
    }
}