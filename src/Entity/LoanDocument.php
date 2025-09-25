<?php

namespace App\Entity;

use App\Repository\LoanDocumentRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: LoanDocumentRepository::class)]
#[ORM\Table(name: 'loan_documents')]
#[ORM\HasLifecycleCallbacks]
class LoanDocument
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: LoanApplication::class, inversedBy: 'documents')]
    #[ORM\JoinColumn(nullable: false)]
    private LoanApplication $loanApplication;

    #[ORM\ManyToOne(targetEntity: Media::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Media $media;

    #[ORM\Column(length: 50)]
    #[Assert\Choice(choices: ['identity', 'income_proof', 'bank_statement', 'employment_proof', 'business_registration', 'tax_return', 'other'])]
    private string $documentType;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $description = null;

    #[ORM\Column]
    private bool $isRequired = true;

    #[ORM\Column]
    private bool $isVerified = false;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $uploadedAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $verifiedAt = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $verifiedBy = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $verificationNotes = null;

    public function __construct()
    {
        $this->uploadedAt = new \DateTimeImmutable();
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

    public function getMedia(): Media
    {
        return $this->media;
    }

    public function setMedia(Media $media): static
    {
        $this->media = $media;
        return $this;
    }

    public function getDocumentType(): string
    {
        return $this->documentType;
    }

    public function setDocumentType(string $documentType): static
    {
        $this->documentType = $documentType;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function isRequired(): bool
    {
        return $this->isRequired;
    }

    public function setIsRequired(bool $isRequired): static
    {
        $this->isRequired = $isRequired;
        return $this;
    }

    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    public function setIsVerified(bool $isVerified): static
    {
        $this->isVerified = $isVerified;
        
        if ($isVerified && $this->verifiedAt === null) {
            $this->verifiedAt = new \DateTimeImmutable();
        }
        
        return $this;
    }

    public function getUploadedAt(): \DateTimeImmutable
    {
        return $this->uploadedAt;
    }

    public function getVerifiedAt(): ?\DateTimeImmutable
    {
        return $this->verifiedAt;
    }

    public function setVerifiedAt(?\DateTimeImmutable $verifiedAt): static
    {
        $this->verifiedAt = $verifiedAt;
        return $this;
    }

    public function getVerifiedBy(): ?string
    {
        return $this->verifiedBy;
    }

    public function setVerifiedBy(?string $verifiedBy): static
    {
        $this->verifiedBy = $verifiedBy;
        return $this;
    }

    public function getVerificationNotes(): ?string
    {
        return $this->verificationNotes;
    }

    public function setVerificationNotes(?string $verificationNotes): static
    {
        $this->verificationNotes = $verificationNotes;
        return $this;
    }

    public function getDocumentTypeLabel(): string
    {
        return match ($this->documentType) {
            'identity' => 'Pièce d\'identité',
            'income_proof' => 'Justificatif de revenus',
            'bank_statement' => 'Relevé bancaire',
            'employment_proof' => 'Justificatif d\'emploi',
            'business_registration' => 'Extrait Kbis',
            'tax_return' => 'Déclaration fiscale',
            'other' => 'Autre document',
            default => 'Document inconnu',
        };
    }

    public function getStatusBadgeClass(): string
    {
        if (!$this->isRequired) {
            return 'bg-secondary';
        }
        
        return $this->isVerified ? 'bg-success' : 'bg-warning';
    }

    public function getStatusLabel(): string
    {
        if (!$this->isRequired) {
            return 'Optionnel';
        }
        
        return $this->isVerified ? 'Vérifié' : 'En attente';
    }
}