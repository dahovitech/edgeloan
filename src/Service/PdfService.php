<?php

namespace App\Service;

use App\Entity\LoanContract;
use App\Entity\Media;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\File;
use Twig\Environment;

class PdfService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private Environment $twig,
        private LoggerInterface $logger,
        #[Autowire('%kernel.project_dir%')]
        private string $projectDir,
        private string $uploadsDir = '/public/uploads/contracts'
    ) {
        $fullPath = $this->projectDir . $this->uploadsDir;
        
        // Créer le dossier s'il n'existe pas
        if (!is_dir($fullPath)) {
            if (!mkdir($fullPath, 0755, true) && !is_dir($fullPath)) {
                throw new \RuntimeException('Impossible de créer le dossier de destination: ' . $fullPath);
            }
        }
    }

    public function generateContractPdf(LoanContract $contract): Media
    {
        try {
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

            // Générer le nom de fichier sécurisé
            $filename = $this->generateSecureFilename($contract);
            $fullPath = $this->projectDir . $this->uploadsDir . '/' . $filename;

            // Générer le PDF
            $this->generatePdfFromHtml($html, $fullPath);

            if (!file_exists($fullPath)) {
                throw new \RuntimeException('Échec de génération du fichier PDF');
            }

            // Créer l'entité Media
            $media = $this->createMediaEntity($filename, $fullPath);

            // Associer le PDF au contrat
            $contract->setPdfDocument($media);
            $this->entityManager->flush();

            $this->logger->info('PDF de contrat généré avec succès', [
                'contract_id' => $contract->getId(),
                'filename' => $filename,
                'size' => filesize($fullPath)
            ]);

            return $media;
            
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la génération du PDF', [
                'contract_id' => $contract->getId(),
                'error' => $e->getMessage()
            ]);
            
            throw new \RuntimeException('Impossible de générer le PDF: ' . $e->getMessage(), 0, $e);
        }
    }

    private function generatePdfFromHtml(string $html, string $filepath): void
    {
        // Cette méthode simule la génération PDF
        // Dans un vrai projet, utilisez une bibliothèque comme:
        // - DomPDF: $dompdf = new Dompdf(); $dompdf->loadHtml($html); etc.
        // - wkhtmltopdf via Process
        // - Puppeteer via Node.js

        $htmlWithStyles = $this->generateStyledHtml($html);
        
        if (file_put_contents($filepath, $htmlWithStyles) === false) {
            throw new \RuntimeException('Impossible d\'écrire le fichier PDF');
        }
    }

    private function generateStyledHtml(string $content): string
    {
        return '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Contrat de Prêt</title>
            <style>
                @page {
                    margin: 40px;
                    @bottom-center {
                        content: counter(page) " / " counter(pages);
                    }
                }
                body { 
                    font-family: "Helvetica Neue", Arial, sans-serif; 
                    margin: 0; 
                    padding: 20px;
                    line-height: 1.6; 
                    color: #333;
                }
                .header { 
                    text-align: center; 
                    margin-bottom: 40px; 
                    border-bottom: 2px solid #007bff;
                    padding-bottom: 20px;
                }
                .contract-number { 
                    font-weight: bold; 
                    color: #007bff; 
                    font-size: 1.2em;
                }
                .section { 
                    margin-bottom: 25px; 
                    padding: 15px 0;
                }
                .signature-section { 
                    margin-top: 60px; 
                    padding-top: 40px; 
                    border-top: 1px solid #ddd; 
                    page-break-inside: avoid;
                }
                table { 
                    width: 100%; 
                    border-collapse: collapse; 
                    margin: 25px 0;
                    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
                }
                th, td { 
                    padding: 12px; 
                    border: 1px solid #ddd; 
                    text-align: left; 
                }
                th { 
                    background-color: #f8f9fa; 
                    font-weight: 600;
                }
                .highlight {
                    background-color: #fff3cd;
                    padding: 10px;
                    border-left: 4px solid #ffc107;
                    margin: 20px 0;
                }
            </style>
        </head>
        <body>
            ' . $content . '
        </body>
        </html>';
    }

    private function generateSecureFilename(LoanContract $contract): string
    {
        $sanitizedNumber = preg_replace('/[^A-Za-z0-9]/', '_', $contract->getContractNumber());
        $timestamp = date('Ymd_His');
        $randomSuffix = bin2hex(random_bytes(4));
        
        return sprintf('contract_%s_%s_%s.pdf', $sanitizedNumber, $timestamp, $randomSuffix);
    }

    private function createMediaEntity(string $filename, string $fullPath): Media
    {
        $media = new Media();
        $file = new File($fullPath);
        
        $media->setFilename($filename)
              ->setOriginalName($filename)
              ->setMimeType('application/pdf')
              ->setSize($file->getSize())
              ->setPath('/uploads/contracts/' . $filename);

        $this->entityManager->persist($media);
        
        return $media;
    }

    public function generatePaymentReceipt(array $paymentData): string
    {
        try {
            $html = $this->twig->render('pdf/payment_receipt.html.twig', $paymentData);

            $filename = sprintf(
                'receipt_%s_%s_%s.pdf',
                $paymentData['payment']->getId(),
                date('Ymd_His'),
                bin2hex(random_bytes(3))
            );

            $fullPath = $this->projectDir . $this->uploadsDir . '/' . $filename;
            $this->generatePdfFromHtml($html, $fullPath);

            $this->logger->info('Reçu de paiement généré', [
                'payment_id' => $paymentData['payment']->getId(),
                'filename' => $filename
            ]);

            return '/uploads/contracts/' . $filename;
            
        } catch (\Exception $e) {
            $this->logger->error('Erreur génération reçu', ['error' => $e->getMessage()]);
            throw new \RuntimeException('Impossible de générer le reçu: ' . $e->getMessage(), 0, $e);
        }
    }

    public function generateLoanStatement(array $statementData): string
    {
        try {
            $html = $this->twig->render('pdf/loan_statement.html.twig', $statementData);

            $filename = sprintf(
                'statement_%s_%s_%s.pdf',
                $statementData['application']->getId(),
                date('Ymd_His'),
                bin2hex(random_bytes(3))
            );

            $fullPath = $this->projectDir . $this->uploadsDir . '/' . $filename;
            $this->generatePdfFromHtml($html, $fullPath);

            $this->logger->info('Relevé de prêt généré', [
                'application_id' => $statementData['application']->getId(),
                'filename' => $filename
            ]);

            return '/uploads/contracts/' . $filename;
            
        } catch (\Exception $e) {
            $this->logger->error('Erreur génération relevé', ['error' => $e->getMessage()]);
            throw new \RuntimeException('Impossible de générer le relevé: ' . $e->getMessage(), 0, $e);
        }
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