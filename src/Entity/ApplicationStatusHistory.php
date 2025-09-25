<?php

namespace App\Entity;

use App\Repository\ApplicationStatusHistoryRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\UuidV4;

#[ORM\Entity(repositoryClass: ApplicationStatusHistoryRepository::class)]
#[ORM\Table(name: 'application_status_history')]
#[ORM\HasLifecycleCallbacks]
class ApplicationStatusHistory
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private ?UuidV4 $id = null;

    #[ORM\ManyToOne(targetEntity: LoanApplication::class, inversedBy: 'statusHistory')]
    #[ORM\JoinColumn(nullable: false)]
    private LoanApplication $application;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $previousStatus = null;

    #[ORM\Column(length: 50)]
    private string $newStatus;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $changedBy;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $reason = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $automated = false;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $changedAt;

    public function __construct()
    {
        $this->changedAt = new \DateTimeImmutable();
    }

    public function getId(): ?UuidV4
    {
        return $this->id;
    }

    public function getApplication(): LoanApplication
    {
        return $this->application;
    }

    public function setApplication(LoanApplication $application): static
    {
        $this->application = $application;
        return $this;
    }

    public function getPreviousStatus(): ?string
    {
        return $this->previousStatus;
    }

    public function setPreviousStatus(?string $previousStatus): static
    {
        $this->previousStatus = $previousStatus;
        return $this;
    }

    public function getNewStatus(): string
    {
        return $this->newStatus;
    }

    public function setNewStatus(string $newStatus): static
    {
        $this->newStatus = $newStatus;
        return $this;
    }

    public function getChangedBy(): User
    {
        return $this->changedBy;
    }

    public function setChangedBy(User $changedBy): static
    {
        $this->changedBy = $changedBy;
        return $this;
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function setReason(?string $reason): static
    {
        $this->reason = $reason;
        return $this;
    }

    public function isAutomated(): bool
    {
        return $this->automated;
    }

    public function setAutomated(bool $automated): static
    {
        $this->automated = $automated;
        return $this;
    }

    public function getChangedAt(): \DateTimeImmutable
    {
        return $this->changedAt;
    }

    public function setChangedAt(\DateTimeImmutable $changedAt): static
    {
        $this->changedAt = $changedAt;
        return $this;
    }

    // Helper methods
    public function getStatusChange(): string
    {
        $previous = $this->previousStatus ? ucfirst($this->previousStatus) : 'N/A';
        $new = ucfirst($this->newStatus);
        return $previous . ' → ' . $new;
    }

    public function getFormattedDate(): string
    {
        return $this->changedAt->format('d/m/Y H:i');
    }

    public function getChangerName(): string
    {
        return $this->changedBy->getDisplayName();
    }

    public function getStatusBadgeClass(): string
    {
        return match($this->newStatus) {
            'approved' => 'bg-success',
            'rejected', 'cancelled' => 'bg-danger',
            'under_review', 'submitted' => 'bg-primary',
            'additional_info_requested' => 'bg-warning',
            'draft' => 'bg-secondary',
            default => 'bg-info',
        };
    }

    public function getChangeIcon(): string
    {
        if ($this->automated) {
            return 'bi-robot';
        }

        return match($this->newStatus) {
            'approved' => 'bi-check-circle',
            'rejected' => 'bi-x-circle',
            'cancelled' => 'bi-dash-circle',
            'under_review' => 'bi-eye',
            'submitted' => 'bi-send',
            'additional_info_requested' => 'bi-question-circle',
            'draft' => 'bi-pencil',
            default => 'bi-arrow-right-circle',
        };
    }
}