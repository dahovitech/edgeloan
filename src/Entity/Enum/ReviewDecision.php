<?php

namespace App\Entity\Enum;

enum ReviewDecision: string
{
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case NEEDS_INFO = 'needs_info';
    case ESCALATED = 'escalated';

    public function getLabel(): string
    {
        return match($this) {
            self::APPROVED => 'review.decision.approved',
            self::REJECTED => 'review.decision.rejected',
            self::NEEDS_INFO => 'review.decision.needs_info',
            self::ESCALATED => 'review.decision.escalated',
        };
    }

    public function getIcon(): string
    {
        return match($this) {
            self::APPROVED => 'bi-check-circle',
            self::REJECTED => 'bi-x-circle',
            self::NEEDS_INFO => 'bi-question-circle',
            self::ESCALATED => 'bi-arrow-up-circle',
        };
    }

    public function getBadgeClass(): string
    {
        return match($this) {
            self::APPROVED => 'bg-success',
            self::REJECTED => 'bg-danger',
            self::NEEDS_INFO => 'bg-warning',
            self::ESCALATED => 'bg-info',
        };
    }

    public function getDescription(): string
    {
        return match($this) {
            self::APPROVED => 'review.decision.description.approved',
            self::REJECTED => 'review.decision.description.rejected',
            self::NEEDS_INFO => 'review.decision.description.needs_info',
            self::ESCALATED => 'review.decision.description.escalated',
        };
    }

    public static function getSelectableOptions(): array
    {
        return [
            'review.decision.approved' => self::APPROVED->value,
            'review.decision.rejected' => self::REJECTED->value,
            'review.decision.needs_info' => self::NEEDS_INFO->value,
            'review.decision.escalated' => self::ESCALATED->value,
        ];
    }

    public function isPositive(): bool
    {
        return $this === self::APPROVED;
    }

    public function isNegative(): bool
    {
        return $this === self::REJECTED;
    }

    public function requiresAction(): bool
    {
        return in_array($this, [self::NEEDS_INFO, self::ESCALATED]);
    }
}