<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration for Loan Management System tables
 */
final class Version20250925142220 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add loan management system tables and extend User entity with loan-specific fields';
    }

    public function up(Schema $schema): void
    {
        // Extend users table with loan-specific fields
        $this->addSql('ALTER TABLE user ADD COLUMN phone VARCHAR(20) DEFAULT NULL');
        $this->addSql('ALTER TABLE user ADD COLUMN client_type VARCHAR(20) DEFAULT "individual"');
        $this->addSql('ALTER TABLE user ADD COLUMN monthly_income DECIMAL(10,2) DEFAULT NULL');
        $this->addSql('ALTER TABLE user ADD COLUMN monthly_charges DECIMAL(10,2) DEFAULT NULL');
        $this->addSql('ALTER TABLE user ADD COLUMN employment_status VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE user ADD COLUMN employer VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE user ADD COLUMN employment_start_date DATE DEFAULT NULL');
        $this->addSql('ALTER TABLE user ADD COLUMN business_name VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE user ADD COLUMN business_registration VARCHAR(20) DEFAULT NULL');
        $this->addSql('ALTER TABLE user ADD COLUMN business_years INTEGER DEFAULT NULL');
        $this->addSql('ALTER TABLE user ADD COLUMN annual_revenue DECIMAL(12,2) DEFAULT NULL');
        $this->addSql('ALTER TABLE user ADD COLUMN is_account_verified BOOLEAN DEFAULT FALSE');
        $this->addSql('ALTER TABLE user ADD COLUMN verified_at DATETIME DEFAULT NULL');

        // Create loan_applications table
        $this->addSql('CREATE TABLE loan_applications (
            id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
            customer_id INTEGER NOT NULL,
            application_number VARCHAR(20) NOT NULL,
            loan_type VARCHAR(50) DEFAULT "personal",
            requested_amount DECIMAL(10,2) NOT NULL,
            requested_duration INTEGER NOT NULL,
            interest_rate DECIMAL(5,3) DEFAULT NULL,
            monthly_payment DECIMAL(10,2) DEFAULT NULL,
            status VARCHAR(20) DEFAULT "pending",
            purpose TEXT DEFAULT NULL,
            credit_score INTEGER DEFAULT NULL,
            notes TEXT DEFAULT NULL,
            metadata TEXT DEFAULT "[]",
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            approved_at DATETIME DEFAULT NULL,
            UNIQUE(application_number),
            FOREIGN KEY (customer_id) REFERENCES user (id)
        )');

        // Create loan_contracts table
        $this->addSql('CREATE TABLE loan_contracts (
            id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
            loan_application_id INTEGER NOT NULL,
            contract_number VARCHAR(20) NOT NULL,
            contract_content TEXT NOT NULL,
            customer_signature TEXT DEFAULT NULL,
            signed_at DATETIME DEFAULT NULL,
            signed_from_ip VARCHAR(45) DEFAULT NULL,
            status VARCHAR(20) DEFAULT "draft",
            pdf_document_id INTEGER DEFAULT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            activated_at DATETIME DEFAULT NULL,
            metadata TEXT DEFAULT "[]",
            UNIQUE(contract_number),
            FOREIGN KEY (loan_application_id) REFERENCES loan_applications (id),
            FOREIGN KEY (pdf_document_id) REFERENCES media (id)
        )');

        // Create loan_documents table
        $this->addSql('CREATE TABLE loan_documents (
            id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
            loan_application_id INTEGER NOT NULL,
            media_id INTEGER NOT NULL,
            document_type VARCHAR(50) NOT NULL,
            description VARCHAR(255) DEFAULT NULL,
            is_required BOOLEAN DEFAULT TRUE,
            is_verified BOOLEAN DEFAULT FALSE,
            uploaded_at DATETIME NOT NULL,
            verified_at DATETIME DEFAULT NULL,
            verified_by VARCHAR(255) DEFAULT NULL,
            verification_notes TEXT DEFAULT NULL,
            FOREIGN KEY (loan_application_id) REFERENCES loan_applications (id),
            FOREIGN KEY (media_id) REFERENCES media (id)
        )');

        // Create loan_payments table
        $this->addSql('CREATE TABLE loan_payments (
            id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
            loan_application_id INTEGER NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            due_date DATE NOT NULL,
            paid_date DATE DEFAULT NULL,
            status VARCHAR(20) DEFAULT "pending",
            paid_amount DECIMAL(10,2) DEFAULT NULL,
            receipt_number VARCHAR(255) DEFAULT NULL,
            receipt_document_id INTEGER DEFAULT NULL,
            notes TEXT DEFAULT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            FOREIGN KEY (loan_application_id) REFERENCES loan_applications (id),
            FOREIGN KEY (receipt_document_id) REFERENCES media (id)
        )');

        // Create indexes for better performance
        $this->addSql('CREATE INDEX IDX_loan_applications_customer ON loan_applications (customer_id)');
        $this->addSql('CREATE INDEX IDX_loan_applications_status ON loan_applications (status)');
        $this->addSql('CREATE INDEX IDX_loan_contracts_application ON loan_contracts (loan_application_id)');
        $this->addSql('CREATE INDEX IDX_loan_documents_application ON loan_documents (loan_application_id)');
        $this->addSql('CREATE INDEX IDX_loan_payments_application ON loan_payments (loan_application_id)');
        $this->addSql('CREATE INDEX IDX_loan_payments_status ON loan_payments (status)');
        $this->addSql('CREATE INDEX IDX_loan_payments_due_date ON loan_payments (due_date)');
    }

    public function down(Schema $schema): void
    {
        // Drop loan tables
        $this->addSql('DROP TABLE IF EXISTS loan_payments');
        $this->addSql('DROP TABLE IF EXISTS loan_documents');
        $this->addSql('DROP TABLE IF EXISTS loan_contracts');
        $this->addSql('DROP TABLE IF EXISTS loan_applications');

        // Remove added columns from users table
        $this->addSql('ALTER TABLE user DROP COLUMN phone');
        $this->addSql('ALTER TABLE user DROP COLUMN client_type');
        $this->addSql('ALTER TABLE user DROP COLUMN monthly_income');
        $this->addSql('ALTER TABLE user DROP COLUMN monthly_charges');
        $this->addSql('ALTER TABLE user DROP COLUMN employment_status');
        $this->addSql('ALTER TABLE user DROP COLUMN employer');
        $this->addSql('ALTER TABLE user DROP COLUMN employment_start_date');
        $this->addSql('ALTER TABLE user DROP COLUMN business_name');
        $this->addSql('ALTER TABLE user DROP COLUMN business_registration');
        $this->addSql('ALTER TABLE user DROP COLUMN business_years');
        $this->addSql('ALTER TABLE user DROP COLUMN annual_revenue');
        $this->addSql('ALTER TABLE user DROP COLUMN is_account_verified');
        $this->addSql('ALTER TABLE user DROP COLUMN verified_at');
    }
}
