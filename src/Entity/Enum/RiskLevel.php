<?php

namespace App\Entity\Enum;

enum RiskLevel: string
{
    case LOW = 'low';
    case MEDIUM = 'medium';
    case HIGH = 'high';
    case VERY_HIGH = 'very_high';

    public function getLabel(): string
    {
        return match($this) {
            self::LOW => 'risk.level.low',
            self::MEDIUM => 'risk.level.medium',
            self::HIGH => 'risk.level.high',
            self::VERY_HIGH => 'risk.level.very_high',
        };
    }

    public function getIcon(): string
    {
        return match($this) {
            self::LOW => 'bi-shield-check',
            self::MEDIUM => 'bi-shield-exclamation',
            self::HIGH => 'bi-shield-x',
            self::VERY_HIGH => 'bi-exclamation-triangle',
        };
    }

    public function getBadgeClass(): string
    {
        return match($this) {
            self::LOW => 'bg-success',
            self::MEDIUM => 'bg-warning',
            self::HIGH => 'bg-danger',
            self::VERY_HIGH => 'bg-dark',
        };
    }

    public function getTextClass(): string
    {
        return match($this) {
            self::LOW => 'text-success',
            self::MEDIUM => 'text-warning',
            self::HIGH => 'text-danger',
            self::VERY_HIGH => 'text-dark',
        };
    }

    public function getDescription(): string
    {
        return match($this) {
            self::LOW => 'risk.level.description.low',
            self::MEDIUM => 'risk.level.description.medium',
            self::HIGH => 'risk.level.description.high',
            self::VERY_HIGH => 'risk.level.description.very_high',
        };
    }

    public static function getSelectableOptions(): array
    {
        return [
            'risk.level.low' => self::LOW->value,
            'risk.level.medium' => self::MEDIUM->value,
            'risk.level.high' => self::HIGH->value,
            'risk.level.very_high' => self::VERY_HIGH->value,
        ];
    }

    public function getNumericValue(): int
    {
        return match($this) {
            self::LOW => 1,
            self::MEDIUM => 2,
            self::HIGH => 3,
            self::VERY_HIGH => 4,
        };
    }

    public function isAcceptable(): bool
    {
        return in_array($this, [self::LOW, self::MEDIUM]);
    }

    public function requiresEscalation(): bool
    {
        return $this === self::VERY_HIGH;
    }

    public function allowsAutoApproval(): bool
    {
        return $this === self::LOW;
    }

    public static function fromScore(int $score): self
    {
        return match(true) {
            $score >= 750 => self::LOW,
            $score >= 650 => self::MEDIUM,
            $score >= 550 => self::HIGH,
            default => self::VERY_HIGH,
        };
    }
}