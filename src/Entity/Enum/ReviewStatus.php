<?php

namespace App\Entity\Enum;

enum ReviewStatus: string
{
    case PENDING = 'pending';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';

    public function getLabel(): string
    {
        return match($this) {
            self::PENDING => 'review.status.pending',
            self::IN_PROGRESS => 'review.status.in_progress',
            self::COMPLETED => 'review.status.completed',
        };
    }

    public function getIcon(): string
    {
        return match($this) {
            self::PENDING => 'bi-clock',
            self::IN_PROGRESS => 'bi-hourglass-split',
            self::COMPLETED => 'bi-check-circle',
        };
    }

    public function getBadgeClass(): string
    {
        return match($this) {
            self::PENDING => 'bg-warning',
            self::IN_PROGRESS => 'bg-primary',
            self::COMPLETED => 'bg-success',
        };
    }

    public function getDescription(): string
    {
        return match($this) {
            self::PENDING => 'review.status.description.pending',
            self::IN_PROGRESS => 'review.status.description.in_progress',
            self::COMPLETED => 'review.status.description.completed',
        };
    }

    public static function getSelectableOptions(): array
    {
        return [
            'review.status.pending' => self::PENDING->value,
            'review.status.in_progress' => self::IN_PROGRESS->value,
            'review.status.completed' => self::COMPLETED->value,
        ];
    }

    public function isActive(): bool
    {
        return in_array($this, [self::PENDING, self::IN_PROGRESS]);
    }

    public function isCompleted(): bool
    {
        return $this === self::COMPLETED;
    }

    public function canBeStarted(): bool
    {
        return $this === self::PENDING;
    }

    public function canBeCompleted(): bool
    {
        return $this === self::IN_PROGRESS;
    }
}