<?php

namespace App\Entity\Enum;

enum LoanStatus: string
{
    case DRAFT = 'draft';
    case SUBMITTED = 'submitted';
    case UNDER_REVIEW = 'under_review';
    case ADDITIONAL_INFO_REQUIRED = 'additional_info_required';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case CANCELLED = 'cancelled';
    case ACTIVE = 'active';
    case COMPLETED = 'completed';
    case DEFAULTED = 'defaulted';

    public function getLabel(): string
    {
        return match($this) {
            self::DRAFT => 'loan.status.draft',
            self::SUBMITTED => 'loan.status.submitted',
            self::UNDER_REVIEW => 'loan.status.under_review',
            self::ADDITIONAL_INFO_REQUIRED => 'loan.status.additional_info_required',
            self::APPROVED => 'loan.status.approved',
            self::REJECTED => 'loan.status.rejected',
            self::CANCELLED => 'loan.status.cancelled',
            self::ACTIVE => 'loan.status.active',
            self::COMPLETED => 'loan.status.completed',
            self::DEFAULTED => 'loan.status.defaulted',
        };
    }

    public function getColor(): string
    {
        return match($this) {
            self::DRAFT => 'secondary',
            self::SUBMITTED => 'info',
            self::UNDER_REVIEW => 'warning',
            self::ADDITIONAL_INFO_REQUIRED => 'warning',
            self::APPROVED => 'success',
            self::REJECTED => 'danger',
            self::CANCELLED => 'dark',
            self::ACTIVE => 'primary',
            self::COMPLETED => 'success',
            self::DEFAULTED => 'danger',
        };
    }

    public function getIcon(): string
    {
        return match($this) {
            self::DRAFT => 'bi-pencil-square',
            self::SUBMITTED => 'bi-send',
            self::UNDER_REVIEW => 'bi-hourglass-split',
            self::ADDITIONAL_INFO_REQUIRED => 'bi-info-circle',
            self::APPROVED => 'bi-check-circle',
            self::REJECTED => 'bi-x-circle',
            self::CANCELLED => 'bi-ban',
            self::ACTIVE => 'bi-arrow-repeat',
            self::COMPLETED => 'bi-check-all',
            self::DEFAULTED => 'bi-exclamation-triangle',
        };
    }

    public function getProgressPercentage(): int
    {
        return match($this) {
            self::DRAFT => 20,
            self::SUBMITTED => 40,
            self::UNDER_REVIEW => 60,
            self::ADDITIONAL_INFO_REQUIRED => 50,
            self::APPROVED => 80,
            self::REJECTED, self::CANCELLED => 100,
            self::ACTIVE => 90,
            self::COMPLETED => 100,
            self::DEFAULTED => 100,
        };
    }

    public function canTransitionTo(self $newStatus): bool
    {
        return match($this) {
            self::DRAFT => in_array($newStatus, [self::SUBMITTED, self::CANCELLED]),
            self::SUBMITTED => in_array($newStatus, [self::UNDER_REVIEW, self::CANCELLED]),
            self::UNDER_REVIEW => in_array($newStatus, [self::APPROVED, self::REJECTED, self::ADDITIONAL_INFO_REQUIRED]),
            self::ADDITIONAL_INFO_REQUIRED => in_array($newStatus, [self::UNDER_REVIEW, self::CANCELLED]),
            self::APPROVED => in_array($newStatus, [self::ACTIVE, self::CANCELLED]),
            self::ACTIVE => in_array($newStatus, [self::COMPLETED, self::DEFAULTED, self::CANCELLED]),
            self::REJECTED, self::CANCELLED, self::COMPLETED, self::DEFAULTED => false,
        };
    }

    public static function getSelectableOptions(): array
    {
        return [
            'loan.status.draft' => self::DRAFT->value,
            'loan.status.submitted' => self::SUBMITTED->value,
            'loan.status.under_review' => self::UNDER_REVIEW->value,
            'loan.status.additional_info_required' => self::ADDITIONAL_INFO_REQUIRED->value,
            'loan.status.approved' => self::APPROVED->value,
            'loan.status.rejected' => self::REJECTED->value,
            'loan.status.cancelled' => self::CANCELLED->value,
            'loan.status.active' => self::ACTIVE->value,
            'loan.status.completed' => self::COMPLETED->value,
            'loan.status.defaulted' => self::DEFAULTED->value,
        ];
    }
}