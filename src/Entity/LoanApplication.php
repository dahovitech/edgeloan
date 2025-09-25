<?php

namespace App\Entity;

use App\Repository\LoanApplicationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: LoanApplicationRepository::class)]
#[ORM\Table(name: 'loan_applications')]
#[ORM\HasLifecycleCallbacks]
class LoanApplication
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 20, unique: true)]
    private string $applicationNumber;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $customer;

    #[ORM\Column(type: 'string', length: 50)]
    #[Assert\Choice(choices: ['personal', 'business', 'auto', 'home', 'education'])]
    private string $loanType = 'personal';

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    #[Assert\NotBlank]
    #[Assert\Positive]
    private float $requestedAmount;

    #[ORM\Column(type: 'integer')]
    #[Assert\NotBlank]
    #[Assert\Positive]
    private int $requestedDuration; // en mois

    #[ORM\Column(type: 'decimal', precision: 5, scale: 3, nullable: true)]
    private ?float $interestRate = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private ?float $monthlyPayment = null;

    #[ORM\Column(type: 'string', length: 20)]
    #[Assert\Choice(choices: ['pending', 'under_review', 'approved', 'rejected', 'cancelled', 'active', 'completed'])]
    private string $status = 'pending';

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $purpose = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $creditScore = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(type: 'json')]
    private array $metadata = [];

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $approvedAt = null;

    // Relations
    #[ORM\OneToMany(mappedBy: 'loanApplication', targetEntity: LoanDocument::class, cascade: ['persist', 'remove'])]
    private Collection $documents;

    #[ORM\OneToMany(mappedBy: 'loanApplication', targetEntity: LoanPayment::class, cascade: ['persist', 'remove'])]
    private Collection $payments;

    #[ORM\OneToOne(mappedBy: 'loanApplication', targetEntity: LoanContract::class)]
    private ?LoanContract $contract = null;

    public function __construct()
    {
        $this->documents = new ArrayCollection();
        $this->payments = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->applicationNumber = $this->generateApplicationNumber();
    }

    #[ORM\PreUpdate]
    public function updateTimestamp(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    private function generateApplicationNumber(): string
    {
        return 'LA' . date('Y') . str_pad((string)rand(100000, 999999), 6, '0', STR_PAD_LEFT);
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getCustomer(): User
    {
        return $this->customer;
    }

    public function setCustomer(User $customer): static
    {
        $this->customer = $customer;
        return $this;
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

    public function getRequestedAmount(): float
    {
        return $this->requestedAmount;
    }

    public function setRequestedAmount(float $requestedAmount): static
    {
        $this->requestedAmount = $requestedAmount;
        return $this;
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

    public function getInterestRate(): ?float
    {
        return $this->interestRate;
    }

    public function setInterestRate(?float $interestRate): static
    {
        $this->interestRate = $interestRate;
        return $this;
    }

    public function getMonthlyPayment(): ?float
    {
        return $this->monthlyPayment;
    }

    public function setMonthlyPayment(?float $monthlyPayment): static
    {
        $this->monthlyPayment = $monthlyPayment;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        
        if ($status === 'approved' && $this->approvedAt === null) {
            $this->approvedAt = new \DateTimeImmutable();
        }
        
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

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function setMetadata(array $metadata): static
    {
        $this->metadata = $metadata;
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

    public function getApprovedAt(): ?\DateTimeImmutable
    {
        return $this->approvedAt;
    }

    public function setApprovedAt(?\DateTimeImmutable $approvedAt): static
    {
        $this->approvedAt = $approvedAt;
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
        return $this->monthlyPayment * $this->requestedDuration;
    }

    public function getTotalInterest(): ?float
    {
        $totalAmount = $this->getTotalAmount();
        if ($totalAmount === null) {
            return null;
        }
        return $totalAmount - $this->requestedAmount;
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