<?php

namespace App\Entity;

use App\Repository\LoanContractRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: LoanContractRepository::class)]
#[ORM\Table(name: 'loan_contracts')]
#[ORM\HasLifecycleCallbacks]
class LoanContract
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\OneToOne(targetEntity: LoanApplication::class, inversedBy: 'contract')]
    #[ORM\JoinColumn(nullable: false)]
    private LoanApplication $loanApplication;

    #[ORM\Column(type: 'string', length: 20, unique: true)]
    private string $contractNumber;

    #[ORM\Column(type: 'text')]
    private string $contractContent;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $customerSignature = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $signedAt = null;

    #[ORM\Column(type: 'string', length: 45, nullable: true)]
    private ?string $signedFromIp = null;

    #[ORM\Column(type: 'string', length: 20)]
    #[Assert\Choice(choices: ['draft', 'sent', 'signed', 'active', 'completed', 'terminated'])]
    private string $status = 'draft';

    #[ORM\ManyToOne(targetEntity: Media::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Media $pdfDocument = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $activatedAt = null;

    #[ORM\Column(type: 'json')]
    private array $metadata = [];

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->contractNumber = $this->generateContractNumber();
    }

    #[ORM\PreUpdate]
    public function updateTimestamp(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    private function generateContractNumber(): string
    {
        return 'CTR' . date('Y') . str_pad((string)rand(100000, 999999), 6, '0', STR_PAD_LEFT);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLoanApplication(): LoanApplication
    {
        return $this->loanApplication;
    }

    public function setLoanApplication(LoanApplication $loanApplication): static
    {
        $this->loanApplication = $loanApplication;
        return $this;
    }

    public function getContractNumber(): string
    {
        return $this->contractNumber;
    }

    public function setContractNumber(string $contractNumber): static
    {
        $this->contractNumber = $contractNumber;
        return $this;
    }

    public function getContractContent(): string
    {
        return $this->contractContent;
    }

    public function setContractContent(string $contractContent): static
    {
        $this->contractContent = $contractContent;
        return $this;
    }

    public function getCustomerSignature(): ?string
    {
        return $this->customerSignature;
    }

    public function setCustomerSignature(?string $customerSignature): static
    {
        $this->customerSignature = $customerSignature;
        
        if ($customerSignature && $this->signedAt === null) {
            $this->signedAt = new \DateTimeImmutable();
        }
        
        return $this;
    }

    public function getSignedAt(): ?\DateTimeImmutable
    {
        return $this->signedAt;
    }

    public function setSignedAt(?\DateTimeImmutable $signedAt): static
    {
        $this->signedAt = $signedAt;
        return $this;
    }

    public function getSignedFromIp(): ?string
    {
        return $this->signedFromIp;
    }

    public function setSignedFromIp(?string $signedFromIp): static
    {
        $this->signedFromIp = $signedFromIp;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        
        if ($status === 'active' && $this->activatedAt === null) {
            $this->activatedAt = new \DateTimeImmutable();
        }
        
        return $this;
    }

    public function getPdfDocument(): ?Media
    {
        return $this->pdfDocument;
    }

    public function setPdfDocument(?Media $pdfDocument): static
    {
        $this->pdfDocument = $pdfDocument;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getActivatedAt(): ?\DateTimeImmutable
    {
        return $this->activatedAt;
    }

    public function setActivatedAt(?\DateTimeImmutable $activatedAt): static
    {
        $this->activatedAt = $activatedAt;
        return $this;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function setMetadata(array $metadata): static
    {
        $this->metadata = $metadata;
        return $this;
    }

    public function isSigned(): bool
    {
        return $this->customerSignature !== null && $this->signedAt !== null;
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function getStatusBadgeClass(): string
    {
        return match ($this->status) {
            'draft' => 'bg-secondary',
            'sent' => 'bg-info',
            'signed' => 'bg-warning',
            'active' => 'bg-success',
            'completed' => 'bg-primary',
            'terminated' => 'bg-danger',
            default => 'bg-secondary',
        };
    }

    public function getStatusLabel(): string
    {
        return match ($this->status) {
            'draft' => 'Brouillon',
            'sent' => 'Envoyé',
            'signed' => 'Signé',
            'active' => 'Actif',
            'completed' => 'Terminé',
            'terminated' => 'Résilié',
            default => 'Inconnu',
        };
    }

    public function generateContractContent(): string
    {
        $application = $this->loanApplication;
        $customer = $application->getCustomer();
        
        $content = "CONTRAT DE PRÊT N° {$this->contractNumber}\n\n";
        $content .= "Entre :\n";
        $content .= "- La société EasiLoan, ci-après dénommée 'Le Prêteur'\n";
        $content .= "- {$customer->getFullName()}, ci-après dénommé 'L'Emprunteur'\n\n";
        
        $content .= "ARTICLE 1 - OBJET DU PRÊT\n";
        $content .= "Le Prêteur accorde à l'Emprunteur un prêt de {$application->getRequestedAmount()}€\n";
        $content .= "sur une durée de {$application->getRequestedDuration()} mois.\n\n";
        
        if ($application->getInterestRate()) {
            $content .= "ARTICLE 2 - TAUX ET REMBOURSEMENT\n";
            $content .= "Taux d'intérêt annuel : {$application->getInterestRate()}%\n";
            
            if ($application->getMonthlyPayment()) {
                $content .= "Mensualité : {$application->getMonthlyPayment()}€\n";
            }
        }
        
        $content .= "\nARTICLE 3 - CONDITIONS GÉNÉRALES\n";
        $content .= "L'Emprunteur s'engage à rembourser le prêt selon l'échéancier convenu.\n";
        $content .= "En cas de retard de paiement, des pénalités pourront être appliquées.\n\n";
        
        $content .= "Fait le " . (new \DateTime())->format('d/m/Y') . "\n\n";
        $content .= "Signature de l'Emprunteur :\n";
        
        return $content;
    }
}