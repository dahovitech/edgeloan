<?php

namespace App\Service;

use App\Entity\LoanContract;
use App\Entity\Media;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Service pour la génération de documents PDF
 * 
 * Note: Pour une implémentation complète en production, vous devriez
 * utiliser une bibliothèque comme TCPDF, DOMPDF ou wkhtmltopdf
 */
class PdfService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger,
        private string $uploadDir = 'uploads/contracts'
    ) {}

    /**
     * Génère un PDF pour un contrat de prêt
     */
    public function generateContractPdf(LoanContract $contract): Media
    {
        try {
            $this->logger->info('Génération PDF du contrat', [
                'contract_id' => $contract->getId(),
                'contract_number' => $contract->getContractNumber()
            ]);

            // Générer le contenu HTML du contrat
            $htmlContent = $this->generateContractHtml($contract);
            
            // Pour cette démo, nous créons un fichier texte
            // En production, vous utiliseriez une lib PDF
            $filename = sprintf('contract_%s_%s.pdf', 
                $contract->getContractNumber(), 
                date('YmdHis')
            );

            $filepath = $this->uploadDir . '/' . $filename;
            $fullPath = __DIR__ . '/../../public/' . $filepath;

            // Créer le répertoire si nécessaire
            $dir = dirname($fullPath);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            // Simuler la génération PDF (écrire le contenu HTML pour la démo)
            file_put_contents($fullPath, $htmlContent);

            // Créer l'entité Media
            $media = new Media();
            $media->setFileName($filename)
                  ->setOriginalName($filename)
                  ->setPath('/' . $filepath)
                  ->setMimeType('application/pdf')
                  ->setSize(strlen($htmlContent))
                  ->setExtension('pdf');

            $this->entityManager->persist($media);

            // Associer le PDF au contrat
            $contract->setPdfDocument($media);
            
            $this->entityManager->flush();

            $this->logger->info('PDF généré avec succès', [
                'contract_id' => $contract->getId(),
                'media_id' => $media->getId(),
                'filename' => $filename
            ]);

            return $media;

        } catch (\Exception $e) {
            $this->logger->error('Erreur génération PDF', [
                'contract_id' => $contract->getId(),
                'error' => $e->getMessage()
            ]);

            throw new \RuntimeException('Impossible de générer le PDF: ' . $e->getMessage());
        }
    }

    /**
     * Génère le contenu HTML du contrat pour la conversion PDF
     */
    private function generateContractHtml(LoanContract $contract): string
    {
        $application = $contract->getLoanApplication();
        $customer = $application->getCustomer();

        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Contrat de Prêt N° ' . $contract->getContractNumber() . '</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .header { text-align: center; margin-bottom: 30px; }
        .section { margin-bottom: 20px; }
        .signature { margin-top: 50px; }
        table { width: 100%; border-collapse: collapse; }
        td, th { padding: 8px; border: 1px solid #ddd; }
        .amount { font-weight: bold; color: #2c3e50; }
    </style>
</head>
<body>
    <div class="header">
        <h1>CONTRAT DE PRÊT</h1>
        <p><strong>N° ' . $contract->getContractNumber() . '</strong></p>
        <p>Généré le ' . (new \DateTime())->format('d/m/Y à H:i') . '</p>
    </div>

    <div class="section">
        <h3>PARTIES CONTRACTANTES</h3>
        <p><strong>Le Prêteur :</strong> EasiLoan - Société de financement</p>
        <p><strong>L\'Emprunteur :</strong> ' . $customer->getFullName() . '</p>
        <p><strong>Email :</strong> ' . $customer->getEmail() . '</p>
    </div>

    <div class="section">
        <h3>CONDITIONS DU PRÊT</h3>
        <table>
            <tr>
                <td>Montant demandé</td>
                <td class="amount">' . $application->getRequestedAmount() . ' €</td>
            </tr>
            <tr>
                <td>Durée</td>
                <td>' . $application->getRequestedDuration() . ' mois</td>
            </tr>
            <tr>
                <td>Taux d\'intérêt annuel</td>
                <td>' . ($application->getInterestRate() ?: 'À définir') . ' %</td>
            </tr>
            <tr>
                <td>Mensualité</td>
                <td class="amount">' . ($application->getMonthlyPayment() ?: 'À calculer') . ' €</td>
            </tr>
            <tr>
                <td>Montant total à rembourser</td>
                <td class="amount">' . ($application->getTotalAmount() ?: 'À calculer') . ' €</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <h3>OBJET DU PRÊT</h3>
        <p>' . ($application->getPurpose() ?: 'Non spécifié') . '</p>
    </div>

    <div class="section">
        <h3>CONDITIONS GÉNÉRALES</h3>
        <p>• L\'emprunteur s\'engage à rembourser le prêt selon l\'échéancier convenu.</p>
        <p>• Les mensualités sont prélevées automatiquement chaque mois.</p>
        <p>• En cas de retard, des pénalités de 2% du montant dû pourront être appliquées.</p>
        <p>• L\'emprunteur peut effectuer un remboursement anticipé sans pénalité.</p>
    </div>

    <div class="signature">
        <table style="border: none;">
            <tr style="border: none;">
                <td style="border: none; width: 50%;">
                    <p><strong>Signature du Prêteur</strong></p>
                    <br><br>
                    <p>EasiLoan</p>
                </td>
                <td style="border: none; width: 50%;">
                    <p><strong>Signature de l\'Emprunteur</strong></p>
                    <br><br>
                    <p>' . $customer->getFullName() . '</p>';

        if ($contract->isSigned()) {
            $html .= '<p><em>Signé électroniquement le ' . $contract->getSignedAt()->format('d/m/Y à H:i') . '</em></p>';
        } else {
            $html .= '<p><em>En attente de signature</em></p>';
        }

        $html .= '</td>
            </tr>
        </table>
    </div>

    <div style="margin-top: 30px; font-size: 12px; color: #666;">
        <p>Ce document a été généré automatiquement par le système EasiLoan.</p>
        <p>Pour toute question, contactez notre service client.</p>
    </div>

</body>
</html>';

        return $html;
    }

    /**
     * Génère un reçu de paiement PDF
     */
    public function generatePaymentReceipt($payment): Media
    {
        // Implémentation similaire pour les reçus de paiement
        throw new \BadMethodCallException('Méthode à implémenter');
    }

    /**
     * Génère un rapport d'activité PDF
     */
    public function generateActivityReport(array $data): Media
    {
        // Implémentation pour les rapports
        throw new \BadMethodCallException('Méthode à implémenter');
    }
}