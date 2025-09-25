<?php

namespace App\Entity;

use App\Repository\LoanApplicationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: LoanApplicationRepository::class)]
#[ORM\Table(name: 'loan_applications')]
#[ORM\HasLifecycleCallbacks]
class LoanApplication
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'uuid', unique: true)]
    private ?Uuid $uuid = null;

    #[ORM\Column(length: 20, unique: true)]
    private string $applicationNumber;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'loan.validation.customer_required')]
    private User $applicant;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: 'loan.validation.loan_type_required')]
    #[Assert\Choice(
        choices: ['personal', 'business', 'auto', 'home', 'education', 'investment'],
        message: 'loan.validation.loan_type_invalid'
    )]
    private string $loanType = 'personal';

    #[ORM\Column(type: 'decimal', precision: 12, scale: 2)]
    #[Assert\NotBlank(message: 'loan.validation.amount_required')]
    #[Assert\Range(
        min: 1000,
        max: 1000000,
        minMessage: 'loan.validation.amount_min',
        maxMessage: 'loan.validation.amount_max'
    )]
    private string $amount;

    #[ORM\Column(length: 500, nullable: true)]
    #[Assert\Length(max: 500, maxMessage: 'loan.validation.purpose_max_length')]
    private ?string $purpose = null;

    #[ORM\Column(length: 30)]
    #[Assert\NotBlank(message: 'loan.validation.status_required')]
    #[Assert\Choice(
        choices: ['draft', 'submitted', 'under_review', 'additional_info_required', 'approved', 'rejected', 'cancelled', 'active', 'completed', 'defaulted'],
        message: 'loan.validation.status_invalid'
    )]
    private string $status = 'draft';

    #[ORM\Column]
    #[Assert\NotBlank(message: 'loan.validation.duration_required')]
    #[Assert\Range(
        min: 6,
        max: 360,
        minMessage: 'loan.validation.duration_min',
        maxMessage: 'loan.validation.duration_max'
    )]
    private int $requestedDuration; // en mois

    #[ORM\Column(type: 'decimal', precision: 5, scale: 3, nullable: true)]
    private ?string $interestRate = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private ?string $monthlyPayment = null;

    #[ORM\Column(nullable: true)]
    #[Assert\Range(min: 300, max: 850, minMessage: 'loan.validation.credit_score_min', maxMessage: 'loan.validation.credit_score_max')]
    private ?int $creditScore = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $reviewNotes = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $reviewedBy = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $assignedTo = null;

    #[ORM\Column(type: 'json')]
    private array $metadata = [];

    // Multi-language support for translatable fields
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $translations = [];

    // Workflow timestamps
    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $submittedAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $reviewedAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $approvedAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $activatedAt = null;

    // Risk assessment fields
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $riskAssessment = [];

    #[ORM\Column(type: 'decimal', precision: 5, scale: 2, nullable: true)]
    private ?string $riskScore = null;

    #[ORM\Column(length: 20, nullable: true)]
    #[Assert\Choice(
        choices: ['low', 'medium', 'high', 'very_high'],
        message: 'loan.validation.risk_level_invalid'
    )]
    private ?string $riskLevel = null;

    // Relations
    #[ORM\OneToMany(mappedBy: 'loanApplication', targetEntity: LoanDocument::class, cascade: ['persist', 'remove'])]
    private Collection $documents;

    #[ORM\OneToMany(mappedBy: 'loanApplication', targetEntity: LoanPayment::class, cascade: ['persist', 'remove'])]
    private Collection $payments;

    #[ORM\OneToMany(mappedBy: 'application', targetEntity: LoanReview::class, cascade: ['persist', 'remove'])]
    private Collection $reviews;

    #[ORM\OneToMany(mappedBy: 'application', targetEntity: ApplicationStatusHistory::class, cascade: ['persist', 'remove'])]
    private Collection $statusHistory;

    #[ORM\OneToOne(mappedBy: 'loanApplication', targetEntity: LoanContract::class)]
    private ?LoanContract $contract = null;

    public function __construct()
    {
        $this->documents = new ArrayCollection();
        $this->payments = new ArrayCollection();
        $this->reviews = new ArrayCollection();
        $this->statusHistory = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->uuid = Uuid::v4();
        $this->applicationNumber = $this->generateApplicationNumber();
        $this->translations = [];
        $this->metadata = [];
        $this->riskAssessment = [];
    }

    #[ORM\PreUpdate]
    public function updateTimestamp(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    private function generateApplicationNumber(): string
    {
        return 'LA' . date('Y') . str_pad((string)random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUuid(): ?Uuid
    {
        return $this->uuid;
    }

    public function getApplicationNumber(): string
    {
        return $this->applicationNumber;
    }

    public function setApplicationNumber(string $applicationNumber): static
    {
        $this->applicationNumber = $applicationNumber;
        return $this;
    }

    public function getApplicant(): User
    {
        return $this->applicant;
    }

    public function setApplicant(User $applicant): static
    {
        $this->applicant = $applicant;
        return $this;
    }

    // Alias for backward compatibility
    public function getCustomer(): User
    {
        return $this->applicant;
    }

    public function setCustomer(User $customer): static
    {
        return $this->setApplicant($customer);
    }

    public function getLoanType(): string
    {
        return $this->loanType;
    }

    public function setLoanType(string $loanType): static
    {
        $this->loanType = $loanType;
        return $this;
    }

    public function getAmount(): string
    {
        return $this->amount ?? '0.00';
    }

    public function setAmount(float $amount): static
    {
        $this->amount = number_format($amount, 2, '.', '');
        return $this;
    }

    public function getAmountFloat(): float
    {
        return (float) $this->amount;
    }

    // Alias for backward compatibility
    public function getRequestedAmount(): string
    {
        return $this->getAmount();
    }

    public function setRequestedAmount(float $requestedAmount): static
    {
        return $this->setAmount($requestedAmount);
    }

    public function getRequestedAmountFloat(): float
    {
        return $this->getAmountFloat();
    }

    public function getRequestedDuration(): int
    {
        return $this->requestedDuration;
    }

    public function setRequestedDuration(int $requestedDuration): static
    {
        $this->requestedDuration = $requestedDuration;
        return $this;
    }

    public function getInterestRate(): ?string
    {
        return $this->interestRate;
    }

    public function setInterestRate(?float $interestRate): static
    {
        $this->interestRate = $interestRate ? number_format($interestRate, 3, '.', '') : null;
        return $this;
    }

    public function getInterestRateFloat(): ?float
    {
        return $this->interestRate ? (float) $this->interestRate : null;
    }

    public function getMonthlyPayment(): ?string
    {
        return $this->monthlyPayment;
    }

    public function setMonthlyPayment(?float $monthlyPayment): static
    {
        $this->monthlyPayment = $monthlyPayment ? number_format($monthlyPayment, 2, '.', '') : null;
        return $this;
    }

    public function getMonthlyPaymentFloat(): ?float
    {
        return $this->monthlyPayment ? (float) $this->monthlyPayment : null;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $oldStatus = $this->status;
        $this->status = $status;
        
        // Handle status transitions with timestamps
        match ($status) {
            'submitted' => $this->submittedAt ??= new \DateTimeImmutable(),
            'approved' => $this->approvedAt ??= new \DateTimeImmutable(),
            'active' => $this->activatedAt ??= new \DateTimeImmutable(),
            default => null
        };
        
        return $this;
    }

    public function getPurpose(): ?string
    {
        return $this->purpose;
    }

    public function setPurpose(?string $purpose): static
    {
        $this->purpose = $purpose;
        return $this;
    }

    public function getCreditScore(): ?int
    {
        return $this->creditScore;
    }

    public function setCreditScore(?int $creditScore): static
    {
        $this->creditScore = $creditScore;
        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): static
    {
        $this->notes = $notes;
        return $this;
    }

    public function getReviewNotes(): ?string
    {
        return $this->reviewNotes;
    }

    public function setReviewNotes(?string $reviewNotes): static
    {
        $this->reviewNotes = $reviewNotes;
        return $this;
    }

    public function getReviewedBy(): ?User
    {
        return $this->reviewedBy;
    }

    public function setReviewedBy(?User $reviewedBy): static
    {
        $this->reviewedBy = $reviewedBy;
        if ($reviewedBy && $this->reviewedAt === null) {
            $this->reviewedAt = new \DateTimeImmutable();
        }
        return $this;
    }

    public function getAssignedTo(): ?User
    {
        return $this->assignedTo;
    }

    public function setAssignedTo(?User $assignedTo): static
    {
        $this->assignedTo = $assignedTo;
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

    public function addMetadata(string $key, mixed $value): static
    {
        $this->metadata[$key] = $value;
        return $this;
    }

    public function getMetadataValue(string $key): mixed
    {
        return $this->metadata[$key] ?? null;
    }

    // Timestamp getters/setters
    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getSubmittedAt(): ?\DateTimeImmutable
    {
        return $this->submittedAt;
    }

    public function setSubmittedAt(?\DateTimeImmutable $submittedAt): static
    {
        $this->submittedAt = $submittedAt;
        return $this;
    }

    public function getReviewedAt(): ?\DateTimeImmutable
    {
        return $this->reviewedAt;
    }

    public function setReviewedAt(?\DateTimeImmutable $reviewedAt): static
    {
        $this->reviewedAt = $reviewedAt;
        return $this;
    }

    public function getApprovedAt(): ?\DateTimeImmutable
    {
        return $this->approvedAt;
    }

    public function setApprovedAt(?\DateTimeImmutable $approvedAt): static
    {
        $this->approvedAt = $approvedAt;
        return $this;
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

    // Risk assessment methods
    public function getRiskAssessment(): ?array
    {
        return $this->riskAssessment;
    }

    public function setRiskAssessment(?array $riskAssessment): static
    {
        $this->riskAssessment = $riskAssessment;
        return $this;
    }

    public function getRiskScore(): ?string
    {
        return $this->riskScore;
    }

    public function setRiskScore(?float $riskScore): static
    {
        $this->riskScore = $riskScore ? number_format($riskScore, 2, '.', '') : null;
        return $this;
    }

    public function getRiskScoreFloat(): ?float
    {
        return $this->riskScore ? (float) $this->riskScore : null;
    }

    public function getRiskLevel(): ?string
    {
        return $this->riskLevel;
    }

    public function setRiskLevel(?string $riskLevel): static
    {
        $this->riskLevel = $riskLevel;
        return $this;
    }

    // Multi-language support methods
    public function getTranslations(): ?array
    {
        return $this->translations;
    }

    public function setTranslations(?array $translations): static
    {
        $this->translations = $translations;
        return $this;
    }

    public function getTranslatedPurpose(string $locale = 'fr'): ?string
    {
        return $this->translations['purpose'][$locale] ?? $this->purpose;
    }

    public function setTranslatedPurpose(string $locale, ?string $purpose): static
    {
        if (!isset($this->translations['purpose'])) {
            $this->translations['purpose'] = [];
        }
        $this->translations['purpose'][$locale] = $purpose;
        return $this;
    }

    /**
     * @return Collection<int, LoanDocument>
     */
    public function getDocuments(): Collection
    {
        return $this->documents;
    }

    public function addDocument(LoanDocument $document): static
    {
        if (!$this->documents->contains($document)) {
            $this->documents->add($document);
            $document->setLoanApplication($this);
        }

        return $this;
    }

    public function removeDocument(LoanDocument $document): static
    {
        if ($this->documents->removeElement($document)) {
            if ($document->getLoanApplication() === $this) {
                $document->setLoanApplication(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, LoanPayment>
     */
    public function getPayments(): Collection
    {
        return $this->payments;
    }

    public function addPayment(LoanPayment $payment): static
    {
        if (!$this->payments->contains($payment)) {
            $this->payments->add($payment);
            $payment->setLoanApplication($this);
        }

        return $this;
    }

    public function removePayment(LoanPayment $payment): static
    {
        if ($this->payments->removeElement($payment)) {
            if ($payment->getLoanApplication() === $this) {
                $payment->setLoanApplication(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, LoanReview>
     */
    public function getReviews(): Collection
    {
        return $this->reviews;
    }

    public function addReview(LoanReview $review): static
    {
        if (!$this->reviews->contains($review)) {
            $this->reviews->add($review);
            $review->setApplication($this);
        }

        return $this;
    }

    public function removeReview(LoanReview $review): static
    {
        if ($this->reviews->removeElement($review)) {
            if ($review->getApplication() === $this) {
                $review->setApplication(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ApplicationStatusHistory>
     */
    public function getStatusHistory(): Collection
    {
        return $this->statusHistory;
    }

    public function addStatusHistory(ApplicationStatusHistory $statusHistory): static
    {
        if (!$this->statusHistory->contains($statusHistory)) {
            $this->statusHistory->add($statusHistory);
            $statusHistory->setApplication($this);
        }

        return $this;
    }

    public function removeStatusHistory(ApplicationStatusHistory $statusHistory): static
    {
        if ($this->statusHistory->removeElement($statusHistory)) {
            if ($statusHistory->getApplication() === $this) {
                $statusHistory->setApplication(null);
            }
        }

        return $this;
    }

    public function getContract(): ?LoanContract
    {
        return $this->contract;
    }

    public function setContract(?LoanContract $contract): static
    {
        $this->contract = $contract;
        return $this;
    }

    public function getTotalAmount(): ?float
    {
        if ($this->monthlyPayment === null) {
            return null;
        }
        return (float) $this->monthlyPayment * $this->requestedDuration;
    }

    public function getTotalInterest(): ?float
    {
        $totalAmount = $this->getTotalAmount();
        if ($totalAmount === null) {
            return null;
        }
        return $totalAmount - (float) $this->requestedAmount;
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function getStatusBadgeClass(): string
    {
        return match ($this->status) {
            'pending' => 'bg-warning',
            'under_review' => 'bg-info',
            'approved' => 'bg-success',
            'rejected' => 'bg-danger',
            'cancelled' => 'bg-secondary',
            'active' => 'bg-primary',
            'completed' => 'bg-dark',
            default => 'bg-secondary',
        };
    }
}