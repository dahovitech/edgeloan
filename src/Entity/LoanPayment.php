<?php

namespace App\Entity;

use App\Repository\LoanPaymentRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: LoanPaymentRepository::class)]
#[ORM\Table(name: 'loan_payments')]
#[ORM\HasLifecycleCallbacks]
class LoanPayment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: LoanApplication::class, inversedBy: 'payments')]
    #[ORM\JoinColumn(nullable: false)]
    private LoanApplication $loanApplication;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    #[Assert\NotBlank]
    #[Assert\Positive]
    private float $amount;

    #[ORM\Column(type: 'date')]
    private \DateTimeInterface $dueDate;

    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeInterface $paidDate = null;

    #[ORM\Column(type: 'string', length: 20)]
    #[Assert\Choice(choices: ['pending', 'paid', 'late', 'partial', 'cancelled'])]
    private string $status = 'pending';

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private ?float $paidAmount = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $receiptNumber = null;

    #[ORM\ManyToOne(targetEntity: Media::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Media $receiptDocument = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function updateTimestamp(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
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

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function setAmount(float $amount): static
    {
        $this->amount = $amount;
        return $this;
    }

    public function getDueDate(): \DateTimeInterface
    {
        return $this->dueDate;
    }

    public function setDueDate(\DateTimeInterface $dueDate): static
    {
        $this->dueDate = $dueDate;
        return $this;
    }

    public function getPaidDate(): ?\DateTimeInterface
    {
        return $this->paidDate;
    }

    public function setPaidDate(?\DateTimeInterface $paidDate): static
    {
        $this->paidDate = $paidDate;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getPaidAmount(): ?float
    {
        return $this->paidAmount;
    }

    public function setPaidAmount(?float $paidAmount): static
    {
        $this->paidAmount = $paidAmount;
        return $this;
    }

    public function getReceiptNumber(): ?string
    {
        return $this->receiptNumber;
    }

    public function setReceiptNumber(?string $receiptNumber): static
    {
        $this->receiptNumber = $receiptNumber;
        return $this;
    }

    public function getReceiptDocument(): ?Media
    {
        return $this->receiptDocument;
    }

    public function setReceiptDocument(?Media $receiptDocument): static
    {
        $this->receiptDocument = $receiptDocument;
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

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function isOverdue(): bool
    {
        if ($this->status === 'paid') {
            return false;
        }
        
        return $this->dueDate < new \DateTime('today');
    }

    public function getDaysOverdue(): int
    {
        if (!$this->isOverdue()) {
            return 0;
        }
        
        $today = new \DateTime('today');
        $interval = $today->diff($this->dueDate);
        return $interval->days;
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function isPartial(): bool
    {
        return $this->status === 'partial';
    }

    public function getStatusBadgeClass(): string
    {
        return match ($this->status) {
            'pending' => $this->isOverdue() ? 'bg-danger' : 'bg-warning',
            'paid' => 'bg-success',
            'late' => 'bg-danger',
            'partial' => 'bg-info',
            'cancelled' => 'bg-secondary',
            default => 'bg-secondary',
        };
    }

    public function getStatusLabel(): string
    {
        return match ($this->status) {
            'pending' => $this->isOverdue() ? 'En retard' : 'En attente',
            'paid' => 'Payé',
            'late' => 'En retard',
            'partial' => 'Partiel',
            'cancelled' => 'Annulé',
            default => 'Inconnu',
        };
    }

    public function getRemainingAmount(): float
    {
        if ($this->paidAmount === null) {
            return $this->amount;
        }
        
        return max(0, $this->amount - $this->paidAmount);
    }
}