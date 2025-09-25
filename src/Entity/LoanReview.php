<?php

namespace App\Entity;

use App\Entity\Enum\ReviewDecision;
use App\Entity\Enum\ReviewStatus;
use App\Entity\Enum\RiskLevel;
use App\Repository\LoanReviewRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\UuidV4;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: LoanReviewRepository::class)]
#[ORM\Table(name: 'loan_reviews')]
#[ORM\HasLifecycleCallbacks]
class LoanReview
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private ?UuidV4 $id = null;

    #[ORM\ManyToOne(targetEntity: LoanApplication::class, inversedBy: 'reviews')]
    #[ORM\JoinColumn(nullable: false)]
    private LoanApplication $application;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $reviewer;

    #[ORM\Column(length: 50)]
    #[Assert\Choice(choices: ['initial', 'additional_review', 'appeal', 'final'])]
    private string $reviewType = 'initial';

    #[ORM\Column(type: 'string', enumType: ReviewStatus::class)]
    private ReviewStatus $status = ReviewStatus::PENDING;

    #[ORM\Column(type: 'string', enumType: ReviewDecision::class, nullable: true)]
    private ?ReviewDecision $decision = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    #[Assert\Range(min: 300, max: 850)]
    private ?int $score = null;

    #[ORM\Column(type: 'string', enumType: RiskLevel::class, nullable: true)]
    private ?RiskLevel $riskLevel = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $comments = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $conditions = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $requestedDocuments = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $startedAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $completedAt = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
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

    public function getReviewer(): User
    {
        return $this->reviewer;
    }

    public function setReviewer(User $reviewer): static
    {
        $this->reviewer = $reviewer;
        return $this;
    }

    public function getReviewType(): string
    {
        return $this->reviewType;
    }

    public function setReviewType(string $reviewType): static
    {
        $this->reviewType = $reviewType;
        return $this;
    }

    public function getStatus(): ReviewStatus
    {
        return $this->status;
    }

    public function setStatus(ReviewStatus $status): static
    {
        $this->status = $status;
        
        // Auto-set started/completed timestamps
        if ($status === ReviewStatus::IN_PROGRESS && $this->startedAt === null) {
            $this->startedAt = new \DateTimeImmutable();
        }
        
        if ($status === ReviewStatus::COMPLETED && $this->completedAt === null) {
            $this->completedAt = new \DateTimeImmutable();
        }
        
        return $this;
    }

    public function getDecision(): ?ReviewDecision
    {
        return $this->decision;
    }

    public function setDecision(?ReviewDecision $decision): static
    {
        $this->decision = $decision;
        return $this;
    }

    public function getScore(): ?int
    {
        return $this->score;
    }

    public function setScore(?int $score): static
    {
        $this->score = $score;
        return $this;
    }

    public function getRiskLevel(): ?RiskLevel
    {
        return $this->riskLevel;
    }

    public function setRiskLevel(?RiskLevel $riskLevel): static
    {
        $this->riskLevel = $riskLevel;
        return $this;
    }

    public function getComments(): ?string
    {
        return $this->comments;
    }

    public function setComments(?string $comments): static
    {
        $this->comments = $comments;
        return $this;
    }

    public function getConditions(): ?string
    {
        return $this->conditions;
    }

    public function setConditions(?string $conditions): static
    {
        $this->conditions = $conditions;
        return $this;
    }

    public function getRequestedDocuments(): ?string
    {
        return $this->requestedDocuments;
    }

    public function setRequestedDocuments(?string $requestedDocuments): static
    {
        $this->requestedDocuments = $requestedDocuments;
        return $this;
    }

    public function getStartedAt(): ?\DateTimeImmutable
    {
        return $this->startedAt;
    }

    public function setStartedAt(?\DateTimeImmutable $startedAt): static
    {
        $this->startedAt = $startedAt;
        return $this;
    }

    public function getCompletedAt(): ?\DateTimeImmutable
    {
        return $this->completedAt;
    }

    public function setCompletedAt(?\DateTimeImmutable $completedAt): static
    {
        $this->completedAt = $completedAt;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    // Helper methods
    public function isCompleted(): bool
    {
        return $this->status === ReviewStatus::COMPLETED;
    }

    public function getDurationInMinutes(): ?int
    {
        if ($this->startedAt === null) {
            return null;
        }

        $endTime = $this->completedAt ?? new \DateTimeImmutable();
        return (int) $this->startedAt->diff($endTime)->format('%i');
    }

    public function getDurationInDays(): ?int
    {
        if ($this->startedAt === null) {
            return null;
        }

        $endTime = $this->completedAt ?? new \DateTimeImmutable();
        return (int) $this->startedAt->diff($endTime)->format('%a');
    }

    public function getDecisionBadgeClass(): string
    {
        return $this->decision?->getBadgeClass() ?? 'bg-secondary';
    }

    public function getRiskBadgeClass(): string
    {
        return $this->riskLevel?->getBadgeClass() ?? 'bg-secondary';
    }

    public function getStatusBadgeClass(): string
    {
        return $this->status->getBadgeClass();
    }
}