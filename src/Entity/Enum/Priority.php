<?php

namespace App\Entity\Enum;

enum Priority: string
{
    case LOW = 'low';
    case NORMAL = 'normal';
    case HIGH = 'high';
    case URGENT = 'urgent';

    public function getLabel(): string
    {
        return match($this) {
            self::LOW => 'priority.low',
            self::NORMAL => 'priority.normal',
            self::HIGH => 'priority.high',
            self::URGENT => 'priority.urgent',
        };
    }

    public function getColor(): string
    {
        return match($this) {
            self::LOW => 'success',
            self::NORMAL => 'info',
            self::HIGH => 'warning',
            self::URGENT => 'danger',
        };
    }

    public function getIcon(): string
    {
        return match($this) {
            self::LOW => 'bi-arrow-down',
            self::NORMAL => 'bi-dash',
            self::HIGH => 'bi-arrow-up',
            self::URGENT => 'bi-exclamation-triangle',
        };
    }

    public function getWeight(): int
    {
        return match($this) {
            self::LOW => 1,
            self::NORMAL => 2,
            self::HIGH => 3,
            self::URGENT => 4,
        };
    }

    public static function getSelectableOptions(): array
    {
        return [
            'priority.low' => self::LOW->value,
            'priority.normal' => self::NORMAL->value,
            'priority.high' => self::HIGH->value,
            'priority.urgent' => self::URGENT->value,
        ];
    }
}