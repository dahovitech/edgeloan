<?php

namespace App\Service;

use App\Entity\LoanContract;
use App\Entity\Media;
use Doctrine\ORM\EntityManagerInterface;
use Twig\Environment;

class PdfService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private Environment $twig,
        private string $uploadsDir = 'public/uploads/contracts'
    ) {
        // Créer le dossier s'il n'existe pas
        if (!is_dir($this->uploadsDir)) {
            mkdir($this->uploadsDir, 0755, true);
        }
    }

    public function generateContractPdf(LoanContract $contract): Media
    {
        // Vérifier si un PDF existe déjà
        if ($contract->getPdfDocument()) {
            return $contract->getPdfDocument();
        }

        // Générer le HTML du contrat
        $html = $this->twig->render('pdf/loan_contract.html.twig', [
            'contract' => $contract,
            'application' => $contract->getLoanApplication(),
            'customer' => $contract->getLoanApplication()->getCustomer()
        ]);

        // Générer le nom de fichier
        $filename = sprintf(
            'contract_%s_%s.pdf',
            $contract->getContractNumber(),
            date('Ymd_His')
        );

        $filepath = $this->uploadsDir . '/' . $filename;

        // Utiliser une bibliothèque PDF comme DomPDF ou wkhtmltopdf
        // Pour simplicité, on simule ici - remplacer par une vraie génération PDF
        $this->generatePdfFromHtml($html, $filepath);

        // Créer l'entité Media
        $media = new Media();
        $media->setFilename($filename)
              ->setOriginalName($filename)
              ->setMimeType('application/pdf')
              ->setSize(filesize($filepath))
              ->setPath('/uploads/contracts/' . $filename);

        $this->entityManager->persist($media);

        // Associer le PDF au contrat
        $contract->setPdfDocument($media);

        $this->entityManager->flush();

        return $media;
    }

    private function generatePdfFromHtml(string $html, string $filepath): void
    {
        // Cette méthode simule la génération PDF
        // Dans un vrai projet, utilisez une bibliothèque comme:
        // - DomPDF: $dompdf = new Dompdf(); $dompdf->loadHtml($html); etc.
        // - wkhtmltopdf via Process
        // - Puppeteer via Node.js

        // Pour le moment, on crée un fichier HTML avec extension PDF
        $htmlWithStyles = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Contrat de Prêt</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 40px; line-height: 1.6; }
                .header { text-align: center; margin-bottom: 30px; }
                .contract-number { font-weight: bold; color: #333; }
                .section { margin-bottom: 20px; }
                .signature-section { margin-top: 50px; padding-top: 30px; border-top: 1px solid #ddd; }
                table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                th, td { padding: 10px; border: 1px solid #ddd; text-align: left; }
                th { background-color: #f5f5f5; }
            </style>
        </head>
        <body>
            ' . $html . '
        </body>
        </html>';

        file_put_contents($filepath, $htmlWithStyles);
    }

    public function generatePaymentReceipt(array $paymentData): string
    {
        $html = $this->twig->render('pdf/payment_receipt.html.twig', $paymentData);

        $filename = sprintf(
            'receipt_%s_%s.pdf',
            $paymentData['payment']->getId(),
            date('Ymd_His')
        );

        $filepath = $this->uploadsDir . '/' . $filename;
        $this->generatePdfFromHtml($html, $filepath);

        return '/uploads/contracts/' . $filename;
    }

    public function generateLoanStatement(array $statementData): string
    {
        $html = $this->twig->render('pdf/loan_statement.html.twig', $statementData);

        $filename = sprintf(
            'statement_%s_%s.pdf',
            $statementData['application']->getId(),
            date('Ymd_His')
        );

        $filepath = $this->uploadsDir . '/' . $filename;
        $this->generatePdfFromHtml($html, $filepath);

        return '/uploads/contracts/' . $filename;
    }

    /**
     * Méthode pour intégrer une vraie bibliothèque PDF comme DomPDF
     * Décommentez et installez dompdf/dompdf pour utiliser
     */
    /*
    private function generatePdfFromHtmlWithDomPdf(string $html, string $filepath): void
    {
        $options = new Options();
        $options->set('defaultFont', 'Arial');
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isPhpEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        file_put_contents($filepath, $dompdf->output());
    }
    */
}