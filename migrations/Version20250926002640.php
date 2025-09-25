<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration to update LoanDocument entity to use DocumentType enum
 */
final class Version20250926002640 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Update LoanDocument entity to use DocumentType enum and fix related constraints';
    }

    public function up(Schema $schema): void
    {
        // Update document_type column to support the enum values
        $this->addSql('CREATE TEMPORARY TABLE __temp__loan_documents AS SELECT id, loan_application_id, media_id, document_type, description, is_required, is_verified, uploaded_at, verified_at, verified_by, verification_notes FROM loan_documents');
        $this->addSql('DROP TABLE loan_documents');
        $this->addSql('CREATE TABLE loan_documents (
            id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, 
            loan_application_id CHAR(36) NOT NULL, 
            media_id INTEGER NOT NULL, 
            document_type VARCHAR(50) NOT NULL, 
            description VARCHAR(255) DEFAULT NULL, 
            is_required BOOLEAN NOT NULL, 
            is_verified BOOLEAN NOT NULL, 
            uploaded_at DATETIME NOT NULL --(DC2Type:datetime_immutable), 
            verified_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable), 
            verified_by VARCHAR(255) DEFAULT NULL, 
            verification_notes CLOB DEFAULT NULL, 
            CONSTRAINT FK_A48BF1CAB61C5C43 FOREIGN KEY (loan_application_id) REFERENCES loan_applications (uuid) NOT DEFERRABLE INITIALLY IMMEDIATE, 
            CONSTRAINT FK_A48BF1CAEA9FDD75 FOREIGN KEY (media_id) REFERENCES media (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        )');
        $this->addSql('INSERT INTO loan_documents (id, loan_application_id, media_id, document_type, description, is_required, is_verified, uploaded_at, verified_at, verified_by, verification_notes) SELECT id, loan_application_id, media_id, document_type, description, is_required, is_verified, uploaded_at, verified_at, verified_by, verification_notes FROM __temp__loan_documents');
        $this->addSql('DROP TABLE __temp__loan_documents');
        $this->addSql('CREATE INDEX IDX_A48BF1CAB61C5C43 ON loan_documents (loan_application_id)');
        $this->addSql('CREATE INDEX IDX_A48BF1CAEA9FDD75 ON loan_documents (media_id)');
        
        // Update any legacy document type values to match the enum
        $this->addSql('UPDATE loan_documents SET document_type = "employment_verification" WHERE document_type = "employment_proof"');
        $this->addSql('UPDATE loan_documents SET document_type = "business_document" WHERE document_type = "business_registration"');
    }

    public function down(Schema $schema): void
    {
        // Revert the changes if needed
        $this->addSql('CREATE TEMPORARY TABLE __temp__loan_documents AS SELECT id, loan_application_id, media_id, document_type, description, is_required, is_verified, uploaded_at, verified_at, verified_by, verification_notes FROM loan_documents');
        $this->addSql('DROP TABLE loan_documents');
        $this->addSql('CREATE TABLE loan_documents (
            id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, 
            loan_application_id CHAR(36) NOT NULL, 
            media_id INTEGER NOT NULL, 
            document_type VARCHAR(50) NOT NULL, 
            description VARCHAR(255) DEFAULT NULL, 
            is_required BOOLEAN NOT NULL, 
            is_verified BOOLEAN NOT NULL, 
            uploaded_at DATETIME NOT NULL --(DC2Type:datetime_immutable), 
            verified_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable), 
            verified_by VARCHAR(255) DEFAULT NULL, 
            verification_notes CLOB DEFAULT NULL, 
            CONSTRAINT FK_A48BF1CAB61C5C43 FOREIGN KEY (loan_application_id) REFERENCES loan_applications (uuid) NOT DEFERRABLE INITIALLY IMMEDIATE, 
            CONSTRAINT FK_A48BF1CAEA9FDD75 FOREIGN KEY (media_id) REFERENCES media (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        )');
        $this->addSql('INSERT INTO loan_documents (id, loan_application_id, media_id, document_type, description, is_required, is_verified, uploaded_at, verified_at, verified_by, verification_notes) SELECT id, loan_application_id, media_id, document_type, description, is_required, is_verified, uploaded_at, verified_at, verified_by, verification_notes FROM __temp__loan_documents');
        $this->addSql('DROP TABLE __temp__loan_documents');
        $this->addSql('CREATE INDEX IDX_A48BF1CAB61C5C43 ON loan_documents (loan_application_id)');
        $this->addSql('CREATE INDEX IDX_A48BF1CAEA9FDD75 ON loan_documents (media_id)');
        
        // Revert document type values
        $this->addSql('UPDATE loan_documents SET document_type = "employment_proof" WHERE document_type = "employment_verification"');
        $this->addSql('UPDATE loan_documents SET document_type = "business_registration" WHERE document_type = "business_document"');
    }
}