<?php

namespace App\Entity\Enum;

enum DocumentType: string
{
    case IDENTITY = 'identity';
    case INCOME_PROOF = 'income_proof';
    case BANK_STATEMENT = 'bank_statement';
    case EMPLOYMENT_VERIFICATION = 'employment_verification';
    case PROPERTY_DOCUMENT = 'property_document';
    case BUSINESS_DOCUMENT = 'business_document';
    case TAX_RETURN = 'tax_return';
    case COLLATERAL_DOCUMENT = 'collateral_document';
    case OTHER = 'other';

    public function getLabel(): string
    {
        return match($this) {
            self::IDENTITY => 'document.type.identity',
            self::INCOME_PROOF => 'document.type.income_proof',
            self::BANK_STATEMENT => 'document.type.bank_statement',
            self::EMPLOYMENT_VERIFICATION => 'document.type.employment_verification',
            self::PROPERTY_DOCUMENT => 'document.type.property_document',
            self::BUSINESS_DOCUMENT => 'document.type.business_document',
            self::TAX_RETURN => 'document.type.tax_return',
            self::COLLATERAL_DOCUMENT => 'document.type.collateral_document',
            self::OTHER => 'document.type.other',
        };
    }

    public function getIcon(): string
    {
        return match($this) {
            self::IDENTITY => 'bi-person-badge',
            self::INCOME_PROOF => 'bi-cash-stack',
            self::BANK_STATEMENT => 'bi-bank',
            self::EMPLOYMENT_VERIFICATION => 'bi-briefcase',
            self::PROPERTY_DOCUMENT => 'bi-house',
            self::BUSINESS_DOCUMENT => 'bi-building',
            self::TAX_RETURN => 'bi-receipt',
            self::COLLATERAL_DOCUMENT => 'bi-shield-check',
            self::OTHER => 'bi-file-earmark',
        };
    }

    public function getDescription(): string
    {
        return match($this) {
            self::IDENTITY => 'document.description.identity',
            self::INCOME_PROOF => 'document.description.income_proof',
            self::BANK_STATEMENT => 'document.description.bank_statement',
            self::EMPLOYMENT_VERIFICATION => 'document.description.employment_verification',
            self::PROPERTY_DOCUMENT => 'document.description.property_document',
            self::BUSINESS_DOCUMENT => 'document.description.business_document',
            self::TAX_RETURN => 'document.description.tax_return',
            self::COLLATERAL_DOCUMENT => 'document.description.collateral_document',
            self::OTHER => 'document.description.other',
        };
    }

    public function isRequired(): bool
    {
        return in_array($this, [
            self::IDENTITY,
            self::INCOME_PROOF,
            self::BANK_STATEMENT
        ]);
    }

    public function getAcceptedMimeTypes(): array
    {
        return [
            'application/pdf',
            'image/jpeg',
            'image/png',
            'image/gif',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        ];
    }

    public function getMaxFileSize(): int
    {
        // Return size in bytes (5MB for most, 10MB for property/business documents)
        return match($this) {
            self::PROPERTY_DOCUMENT, self::BUSINESS_DOCUMENT, self::COLLATERAL_DOCUMENT => 10 * 1024 * 1024,
            default => 5 * 1024 * 1024,
        };
    }

    public static function getSelectableOptions(): array
    {
        return [
            'document.type.identity' => self::IDENTITY->value,
            'document.type.income_proof' => self::INCOME_PROOF->value,
            'document.type.bank_statement' => self::BANK_STATEMENT->value,
            'document.type.employment_verification' => self::EMPLOYMENT_VERIFICATION->value,
            'document.type.property_document' => self::PROPERTY_DOCUMENT->value,
            'document.type.business_document' => self::BUSINESS_DOCUMENT->value,
            'document.type.tax_return' => self::TAX_RETURN->value,
            'document.type.collateral_document' => self::COLLATERAL_DOCUMENT->value,
            'document.type.other' => self::OTHER->value,
        ];
    }

    public static function getRequiredTypes(): array
    {
        return [
            self::IDENTITY,
            self::INCOME_PROOF,
            self::BANK_STATEMENT,
        ];
    }
}