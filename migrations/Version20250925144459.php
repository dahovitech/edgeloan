<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250925144459 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE service_translations (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, service_id INTEGER NOT NULL, language_id INTEGER NOT NULL, title VARCHAR(255) NOT NULL, description CLOB DEFAULT NULL, meta_title VARCHAR(500) DEFAULT NULL, meta_description CLOB DEFAULT NULL, created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , updated_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , CONSTRAINT FK_191BAF62ED5CA9E6 FOREIGN KEY (service_id) REFERENCES services (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_191BAF6282F1BAF4 FOREIGN KEY (language_id) REFERENCES languages (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_191BAF62ED5CA9E6 ON service_translations (service_id)');
        $this->addSql('CREATE INDEX IDX_191BAF6282F1BAF4 ON service_translations (language_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_SERVICE_LANGUAGE ON service_translations (service_id, language_id)');
        $this->addSql('CREATE TABLE services (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, image_id INTEGER DEFAULT NULL, slug VARCHAR(255) NOT NULL, is_active BOOLEAN NOT NULL, sort_order INTEGER NOT NULL, created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , updated_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , CONSTRAINT FK_7332E1693DA5256D FOREIGN KEY (image_id) REFERENCES media (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_7332E169989D9B62 ON services (slug)');
        $this->addSql('CREATE INDEX IDX_7332E1693DA5256D ON services (image_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__loan_applications AS SELECT id, customer_id, application_number, loan_type, requested_amount, requested_duration, interest_rate, monthly_payment, status, purpose, credit_score, notes, metadata, created_at, updated_at, approved_at FROM loan_applications');
        $this->addSql('DROP TABLE loan_applications');
        $this->addSql('CREATE TABLE loan_applications (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, customer_id INTEGER NOT NULL, application_number VARCHAR(20) NOT NULL, loan_type VARCHAR(50) NOT NULL, requested_amount NUMERIC(10, 2) NOT NULL, requested_duration INTEGER NOT NULL, interest_rate NUMERIC(5, 3) DEFAULT NULL, monthly_payment NUMERIC(10, 2) DEFAULT NULL, status VARCHAR(20) NOT NULL, purpose CLOB DEFAULT NULL, credit_score INTEGER DEFAULT NULL, notes CLOB DEFAULT NULL, metadata CLOB NOT NULL --(DC2Type:json)
        , created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , updated_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , approved_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        , FOREIGN KEY (customer_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO loan_applications (id, customer_id, application_number, loan_type, requested_amount, requested_duration, interest_rate, monthly_payment, status, purpose, credit_score, notes, metadata, created_at, updated_at, approved_at) SELECT id, customer_id, application_number, loan_type, requested_amount, requested_duration, interest_rate, monthly_payment, status, purpose, credit_score, notes, metadata, created_at, updated_at, approved_at FROM __temp__loan_applications');
        $this->addSql('DROP TABLE __temp__loan_applications');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_86DC222626A31391 ON loan_applications (application_number)');
        $this->addSql('CREATE INDEX IDX_86DC22269395C3F3 ON loan_applications (customer_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__loan_contracts AS SELECT id, loan_application_id, pdf_document_id, contract_number, contract_content, customer_signature, signed_at, signed_from_ip, status, created_at, updated_at, activated_at, metadata FROM loan_contracts');
        $this->addSql('DROP TABLE loan_contracts');
        $this->addSql('CREATE TABLE loan_contracts (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, loan_application_id INTEGER NOT NULL, pdf_document_id INTEGER DEFAULT NULL, contract_number VARCHAR(20) NOT NULL, contract_content CLOB NOT NULL, customer_signature CLOB DEFAULT NULL, signed_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        , signed_from_ip VARCHAR(45) DEFAULT NULL, status VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , updated_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , activated_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        , metadata CLOB NOT NULL --(DC2Type:json)
        , FOREIGN KEY (loan_application_id) REFERENCES loan_applications (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE, FOREIGN KEY (pdf_document_id) REFERENCES media (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO loan_contracts (id, loan_application_id, pdf_document_id, contract_number, contract_content, customer_signature, signed_at, signed_from_ip, status, created_at, updated_at, activated_at, metadata) SELECT id, loan_application_id, pdf_document_id, contract_number, contract_content, customer_signature, signed_at, signed_from_ip, status, created_at, updated_at, activated_at, metadata FROM __temp__loan_contracts');
        $this->addSql('DROP TABLE __temp__loan_contracts');
        $this->addSql('CREATE INDEX IDX_480395E9CBFD05C ON loan_contracts (pdf_document_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_480395E9AAD0FA19 ON loan_contracts (contract_number)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_480395E998D09CC8 ON loan_contracts (loan_application_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__loan_documents AS SELECT id, loan_application_id, media_id, document_type, description, is_required, is_verified, uploaded_at, verified_at, verified_by, verification_notes FROM loan_documents');
        $this->addSql('DROP TABLE loan_documents');
        $this->addSql('CREATE TABLE loan_documents (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, loan_application_id INTEGER NOT NULL, media_id INTEGER NOT NULL, document_type VARCHAR(50) NOT NULL, description VARCHAR(255) DEFAULT NULL, is_required BOOLEAN NOT NULL, is_verified BOOLEAN NOT NULL, uploaded_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , verified_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        , verified_by VARCHAR(255) DEFAULT NULL, verification_notes CLOB DEFAULT NULL, FOREIGN KEY (loan_application_id) REFERENCES loan_applications (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE, FOREIGN KEY (media_id) REFERENCES media (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO loan_documents (id, loan_application_id, media_id, document_type, description, is_required, is_verified, uploaded_at, verified_at, verified_by, verification_notes) SELECT id, loan_application_id, media_id, document_type, description, is_required, is_verified, uploaded_at, verified_at, verified_by, verification_notes FROM __temp__loan_documents');
        $this->addSql('DROP TABLE __temp__loan_documents');
        $this->addSql('CREATE INDEX IDX_E3E34E12EA9FDD75 ON loan_documents (media_id)');
        $this->addSql('CREATE INDEX IDX_E3E34E1298D09CC8 ON loan_documents (loan_application_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__loan_payments AS SELECT id, loan_application_id, receipt_document_id, amount, due_date, paid_date, status, paid_amount, receipt_number, notes, created_at, updated_at FROM loan_payments');
        $this->addSql('DROP TABLE loan_payments');
        $this->addSql('CREATE TABLE loan_payments (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, loan_application_id INTEGER NOT NULL, receipt_document_id INTEGER DEFAULT NULL, amount NUMERIC(10, 2) NOT NULL, due_date DATE NOT NULL, paid_date DATE DEFAULT NULL, status VARCHAR(20) NOT NULL, paid_amount NUMERIC(10, 2) DEFAULT NULL, receipt_number VARCHAR(255) DEFAULT NULL, notes CLOB DEFAULT NULL, created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , updated_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , FOREIGN KEY (loan_application_id) REFERENCES loan_applications (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE, FOREIGN KEY (receipt_document_id) REFERENCES media (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO loan_payments (id, loan_application_id, receipt_document_id, amount, due_date, paid_date, status, paid_amount, receipt_number, notes, created_at, updated_at) SELECT id, loan_application_id, receipt_document_id, amount, due_date, paid_date, status, paid_amount, receipt_number, notes, created_at, updated_at FROM __temp__loan_payments');
        $this->addSql('DROP TABLE __temp__loan_payments');
        $this->addSql('CREATE INDEX IDX_32946199F41D6079 ON loan_payments (receipt_document_id)');
        $this->addSql('CREATE INDEX IDX_3294619998D09CC8 ON loan_payments (loan_application_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__media AS SELECT id FROM media');
        $this->addSql('DROP TABLE media');
        $this->addSql('CREATE TABLE media (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, file_name VARCHAR(255) DEFAULT NULL, alt VARCHAR(255) DEFAULT NULL, extension VARCHAR(255) DEFAULT NULL)');
        $this->addSql('INSERT INTO media (id) SELECT id FROM __temp__media');
        $this->addSql('DROP TABLE __temp__media');
        $this->addSql('CREATE TEMPORARY TABLE __temp__user AS SELECT id, email, roles, password, first_name, last_name, is_active, created_at, updated_at, last_login_at, phone, client_type, monthly_income, monthly_charges, employment_status, employer, employment_start_date, business_name, business_registration, business_years, annual_revenue, is_account_verified, verified_at FROM user');
        $this->addSql('DROP TABLE user');
        $this->addSql('CREATE TABLE user (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles CLOB NOT NULL --(DC2Type:json)
        , password VARCHAR(255) NOT NULL, first_name VARCHAR(100) NOT NULL, last_name VARCHAR(100) NOT NULL, is_active BOOLEAN NOT NULL, created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , updated_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , last_login_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        , phone VARCHAR(20) DEFAULT NULL, client_type VARCHAR(20) NOT NULL, monthly_income NUMERIC(10, 2) DEFAULT NULL, monthly_charges NUMERIC(10, 2) DEFAULT NULL, employment_status VARCHAR(50) DEFAULT NULL, employer VARCHAR(100) DEFAULT NULL, employment_start_date DATE DEFAULT NULL, business_name VARCHAR(100) DEFAULT NULL, business_registration VARCHAR(20) DEFAULT NULL, business_years INTEGER DEFAULT NULL, annual_revenue NUMERIC(12, 2) DEFAULT NULL, is_account_verified BOOLEAN NOT NULL, verified_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        )');
        $this->addSql('INSERT INTO user (id, email, roles, password, first_name, last_name, is_active, created_at, updated_at, last_login_at, phone, client_type, monthly_income, monthly_charges, employment_status, employer, employment_start_date, business_name, business_registration, business_years, annual_revenue, is_account_verified, verified_at) SELECT id, email, roles, password, first_name, last_name, is_active, created_at, updated_at, last_login_at, phone, client_type, monthly_income, monthly_charges, employment_status, employer, employment_start_date, business_name, business_registration, business_years, annual_revenue, is_account_verified, verified_at FROM __temp__user');
        $this->addSql('DROP TABLE __temp__user');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL ON user (email)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE service_translations');
        $this->addSql('DROP TABLE services');
        $this->addSql('CREATE TEMPORARY TABLE __temp__loan_applications AS SELECT id, customer_id, application_number, loan_type, requested_amount, requested_duration, interest_rate, monthly_payment, status, purpose, credit_score, notes, metadata, created_at, updated_at, approved_at FROM loan_applications');
        $this->addSql('DROP TABLE loan_applications');
        $this->addSql('CREATE TABLE loan_applications (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, customer_id INTEGER NOT NULL, application_number VARCHAR(20) NOT NULL, loan_type VARCHAR(50) DEFAULT \'"personal"\', requested_amount NUMERIC(10, 2) NOT NULL, requested_duration INTEGER NOT NULL, interest_rate NUMERIC(5, 3) DEFAULT NULL, monthly_payment NUMERIC(10, 2) DEFAULT NULL, status VARCHAR(20) DEFAULT \'"pending"\', purpose CLOB DEFAULT NULL, credit_score INTEGER DEFAULT NULL, notes CLOB DEFAULT NULL, metadata CLOB DEFAULT \'"[]"\', created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, approved_at DATETIME DEFAULT NULL, CONSTRAINT FK_86DC22269395C3F3 FOREIGN KEY (customer_id) REFERENCES user (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO loan_applications (id, customer_id, application_number, loan_type, requested_amount, requested_duration, interest_rate, monthly_payment, status, purpose, credit_score, notes, metadata, created_at, updated_at, approved_at) SELECT id, customer_id, application_number, loan_type, requested_amount, requested_duration, interest_rate, monthly_payment, status, purpose, credit_score, notes, metadata, created_at, updated_at, approved_at FROM __temp__loan_applications');
        $this->addSql('DROP TABLE __temp__loan_applications');
        $this->addSql('CREATE INDEX IDX_loan_applications_status ON loan_applications (status)');
        $this->addSql('CREATE INDEX IDX_loan_applications_customer ON loan_applications (customer_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__loan_contracts AS SELECT id, loan_application_id, pdf_document_id, contract_number, contract_content, customer_signature, signed_at, signed_from_ip, status, created_at, updated_at, activated_at, metadata FROM loan_contracts');
        $this->addSql('DROP TABLE loan_contracts');
        $this->addSql('CREATE TABLE loan_contracts (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, loan_application_id INTEGER NOT NULL, pdf_document_id INTEGER DEFAULT NULL, contract_number VARCHAR(20) NOT NULL, contract_content CLOB NOT NULL, customer_signature CLOB DEFAULT NULL, signed_at DATETIME DEFAULT NULL, signed_from_ip VARCHAR(45) DEFAULT NULL, status VARCHAR(20) DEFAULT \'"draft"\', created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, activated_at DATETIME DEFAULT NULL, metadata CLOB DEFAULT \'"[]"\', CONSTRAINT FK_480395E998D09CC8 FOREIGN KEY (loan_application_id) REFERENCES loan_applications (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_480395E9CBFD05C FOREIGN KEY (pdf_document_id) REFERENCES media (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO loan_contracts (id, loan_application_id, pdf_document_id, contract_number, contract_content, customer_signature, signed_at, signed_from_ip, status, created_at, updated_at, activated_at, metadata) SELECT id, loan_application_id, pdf_document_id, contract_number, contract_content, customer_signature, signed_at, signed_from_ip, status, created_at, updated_at, activated_at, metadata FROM __temp__loan_contracts');
        $this->addSql('DROP TABLE __temp__loan_contracts');
        $this->addSql('CREATE INDEX IDX_480395E9CBFD05C ON loan_contracts (pdf_document_id)');
        $this->addSql('CREATE INDEX IDX_loan_contracts_application ON loan_contracts (loan_application_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__loan_documents AS SELECT id, loan_application_id, media_id, document_type, description, is_required, is_verified, uploaded_at, verified_at, verified_by, verification_notes FROM loan_documents');
        $this->addSql('DROP TABLE loan_documents');
        $this->addSql('CREATE TABLE loan_documents (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, loan_application_id INTEGER NOT NULL, media_id INTEGER NOT NULL, document_type VARCHAR(50) NOT NULL, description VARCHAR(255) DEFAULT NULL, is_required BOOLEAN DEFAULT TRUE, is_verified BOOLEAN DEFAULT FALSE, uploaded_at DATETIME NOT NULL, verified_at DATETIME DEFAULT NULL, verified_by VARCHAR(255) DEFAULT NULL, verification_notes CLOB DEFAULT NULL, CONSTRAINT FK_E3E34E1298D09CC8 FOREIGN KEY (loan_application_id) REFERENCES loan_applications (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_E3E34E12EA9FDD75 FOREIGN KEY (media_id) REFERENCES media (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO loan_documents (id, loan_application_id, media_id, document_type, description, is_required, is_verified, uploaded_at, verified_at, verified_by, verification_notes) SELECT id, loan_application_id, media_id, document_type, description, is_required, is_verified, uploaded_at, verified_at, verified_by, verification_notes FROM __temp__loan_documents');
        $this->addSql('DROP TABLE __temp__loan_documents');
        $this->addSql('CREATE INDEX IDX_E3E34E12EA9FDD75 ON loan_documents (media_id)');
        $this->addSql('CREATE INDEX IDX_loan_documents_application ON loan_documents (loan_application_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__loan_payments AS SELECT id, loan_application_id, receipt_document_id, amount, due_date, paid_date, status, paid_amount, receipt_number, notes, created_at, updated_at FROM loan_payments');
        $this->addSql('DROP TABLE loan_payments');
        $this->addSql('CREATE TABLE loan_payments (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, loan_application_id INTEGER NOT NULL, receipt_document_id INTEGER DEFAULT NULL, amount NUMERIC(10, 2) NOT NULL, due_date DATE NOT NULL, paid_date DATE DEFAULT NULL, status VARCHAR(20) DEFAULT \'"pending"\', paid_amount NUMERIC(10, 2) DEFAULT NULL, receipt_number VARCHAR(255) DEFAULT NULL, notes CLOB DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, CONSTRAINT FK_3294619998D09CC8 FOREIGN KEY (loan_application_id) REFERENCES loan_applications (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_32946199F41D6079 FOREIGN KEY (receipt_document_id) REFERENCES media (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO loan_payments (id, loan_application_id, receipt_document_id, amount, due_date, paid_date, status, paid_amount, receipt_number, notes, created_at, updated_at) SELECT id, loan_application_id, receipt_document_id, amount, due_date, paid_date, status, paid_amount, receipt_number, notes, created_at, updated_at FROM __temp__loan_payments');
        $this->addSql('DROP TABLE __temp__loan_payments');
        $this->addSql('CREATE INDEX IDX_32946199F41D6079 ON loan_payments (receipt_document_id)');
        $this->addSql('CREATE INDEX IDX_loan_payments_due_date ON loan_payments (due_date)');
        $this->addSql('CREATE INDEX IDX_loan_payments_status ON loan_payments (status)');
        $this->addSql('CREATE INDEX IDX_loan_payments_application ON loan_payments (loan_application_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__media AS SELECT id FROM media');
        $this->addSql('DROP TABLE media');
        $this->addSql('CREATE TABLE media (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, filename VARCHAR(255) NOT NULL, original_name VARCHAR(255) NOT NULL, mime_type VARCHAR(100) NOT NULL, size INTEGER NOT NULL, path VARCHAR(500) NOT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, updated_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL)');
        $this->addSql('INSERT INTO media (id) SELECT id FROM __temp__media');
        $this->addSql('DROP TABLE __temp__media');
        $this->addSql('CREATE TEMPORARY TABLE __temp__user AS SELECT id, email, roles, password, first_name, last_name, is_active, created_at, updated_at, last_login_at, phone, client_type, monthly_income, monthly_charges, employment_status, employer, employment_start_date, business_name, business_registration, business_years, annual_revenue, is_account_verified, verified_at FROM user');
        $this->addSql('DROP TABLE user');
        $this->addSql('CREATE TABLE user (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles CLOB NOT NULL, password VARCHAR(255) NOT NULL, first_name VARCHAR(100) NOT NULL, last_name VARCHAR(100) NOT NULL, is_active BOOLEAN DEFAULT 1 NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, last_login_at DATETIME DEFAULT NULL, phone VARCHAR(20) DEFAULT NULL, client_type VARCHAR(20) DEFAULT \'"individual"\', monthly_income NUMERIC(10, 2) DEFAULT NULL, monthly_charges NUMERIC(10, 2) DEFAULT NULL, employment_status VARCHAR(50) DEFAULT NULL, employer VARCHAR(100) DEFAULT NULL, employment_start_date DATE DEFAULT NULL, business_name VARCHAR(100) DEFAULT NULL, business_registration VARCHAR(20) DEFAULT NULL, business_years INTEGER DEFAULT NULL, annual_revenue NUMERIC(12, 2) DEFAULT NULL, is_account_verified BOOLEAN DEFAULT FALSE, verified_at DATETIME DEFAULT NULL)');
        $this->addSql('INSERT INTO user (id, email, roles, password, first_name, last_name, is_active, created_at, updated_at, last_login_at, phone, client_type, monthly_income, monthly_charges, employment_status, employer, employment_start_date, business_name, business_registration, business_years, annual_revenue, is_account_verified, verified_at) SELECT id, email, roles, password, first_name, last_name, is_active, created_at, updated_at, last_login_at, phone, client_type, monthly_income, monthly_charges, employment_status, employer, employment_start_date, business_name, business_registration, business_years, annual_revenue, is_account_verified, verified_at FROM __temp__user');
        $this->addSql('DROP TABLE __temp__user');
    }
}
