<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250925223403 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE languages (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, code VARCHAR(10) NOT NULL, name VARCHAR(100) NOT NULL, native_name VARCHAR(100) NOT NULL, is_active BOOLEAN NOT NULL, is_default BOOLEAN NOT NULL, sort_order INTEGER NOT NULL)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_A0D1537977153098 ON languages (code)');
        $this->addSql('CREATE TABLE loan_applications (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, applicant_id INTEGER NOT NULL, reviewed_by_id INTEGER DEFAULT NULL, uuid BLOB NOT NULL --(DC2Type:uuid)
        , application_number VARCHAR(20) NOT NULL, loan_type VARCHAR(50) NOT NULL, amount NUMERIC(12, 2) NOT NULL, purpose VARCHAR(500) DEFAULT NULL, status VARCHAR(30) NOT NULL, requested_duration INTEGER NOT NULL, interest_rate NUMERIC(5, 3) DEFAULT NULL, monthly_payment NUMERIC(10, 2) DEFAULT NULL, credit_score INTEGER DEFAULT NULL, notes CLOB DEFAULT NULL, review_notes CLOB DEFAULT NULL, metadata CLOB NOT NULL --(DC2Type:json)
        , translations CLOB DEFAULT NULL --(DC2Type:json)
        , created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , updated_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , submitted_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        , reviewed_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        , approved_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        , activated_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        , risk_assessment CLOB DEFAULT NULL --(DC2Type:json)
        , risk_score NUMERIC(5, 2) DEFAULT NULL, risk_level VARCHAR(20) DEFAULT NULL, CONSTRAINT FK_86DC222697139001 FOREIGN KEY (applicant_id) REFERENCES user (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_86DC2226FC6B21F1 FOREIGN KEY (reviewed_by_id) REFERENCES user (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_86DC2226D17F50A6 ON loan_applications (uuid)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_86DC222626A31391 ON loan_applications (application_number)');
        $this->addSql('CREATE INDEX IDX_86DC222697139001 ON loan_applications (applicant_id)');
        $this->addSql('CREATE INDEX IDX_86DC2226FC6B21F1 ON loan_applications (reviewed_by_id)');
        $this->addSql('CREATE TABLE loan_contracts (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, loan_application_id INTEGER NOT NULL, pdf_document_id INTEGER DEFAULT NULL, contract_number VARCHAR(20) NOT NULL, contract_content CLOB NOT NULL, customer_signature CLOB DEFAULT NULL, signed_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        , signed_from_ip VARCHAR(45) DEFAULT NULL, status VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , updated_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , activated_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        , metadata CLOB NOT NULL --(DC2Type:json)
        , CONSTRAINT FK_480395E998D09CC8 FOREIGN KEY (loan_application_id) REFERENCES loan_applications (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_480395E9CBFD05C FOREIGN KEY (pdf_document_id) REFERENCES media (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_480395E9AAD0FA19 ON loan_contracts (contract_number)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_480395E998D09CC8 ON loan_contracts (loan_application_id)');
        $this->addSql('CREATE INDEX IDX_480395E9CBFD05C ON loan_contracts (pdf_document_id)');
        $this->addSql('CREATE TABLE loan_documents (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, loan_application_id INTEGER NOT NULL, media_id INTEGER NOT NULL, document_type VARCHAR(50) NOT NULL, description VARCHAR(255) DEFAULT NULL, is_required BOOLEAN NOT NULL, is_verified BOOLEAN NOT NULL, uploaded_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , verified_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        , verified_by VARCHAR(255) DEFAULT NULL, verification_notes CLOB DEFAULT NULL, CONSTRAINT FK_E3E34E1298D09CC8 FOREIGN KEY (loan_application_id) REFERENCES loan_applications (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_E3E34E12EA9FDD75 FOREIGN KEY (media_id) REFERENCES media (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_E3E34E1298D09CC8 ON loan_documents (loan_application_id)');
        $this->addSql('CREATE INDEX IDX_E3E34E12EA9FDD75 ON loan_documents (media_id)');
        $this->addSql('CREATE TABLE loan_payments (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, loan_application_id INTEGER NOT NULL, receipt_document_id INTEGER DEFAULT NULL, amount NUMERIC(10, 2) NOT NULL, due_date DATE NOT NULL, paid_date DATE DEFAULT NULL, status VARCHAR(20) NOT NULL, paid_amount NUMERIC(10, 2) DEFAULT NULL, receipt_number VARCHAR(255) DEFAULT NULL, notes CLOB DEFAULT NULL, created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , updated_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , CONSTRAINT FK_3294619998D09CC8 FOREIGN KEY (loan_application_id) REFERENCES loan_applications (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_32946199F41D6079 FOREIGN KEY (receipt_document_id) REFERENCES media (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_3294619998D09CC8 ON loan_payments (loan_application_id)');
        $this->addSql('CREATE INDEX IDX_32946199F41D6079 ON loan_payments (receipt_document_id)');
        $this->addSql('CREATE TABLE media (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, file_name VARCHAR(255) DEFAULT NULL, original_name VARCHAR(255) DEFAULT NULL, alt VARCHAR(255) DEFAULT NULL, extension VARCHAR(255) DEFAULT NULL, mime_type VARCHAR(100) DEFAULT NULL, size INTEGER DEFAULT NULL, path VARCHAR(500) DEFAULT NULL, created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        )');
        $this->addSql('CREATE TABLE permission (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, code VARCHAR(100) NOT NULL, name VARCHAR(100) NOT NULL, description VARCHAR(255) DEFAULT NULL, category VARCHAR(50) NOT NULL, is_system_permission BOOLEAN NOT NULL, is_active BOOLEAN NOT NULL, priority INTEGER NOT NULL, created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , updated_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , translations CLOB DEFAULT NULL --(DC2Type:json)
        , resource VARCHAR(50) DEFAULT NULL, "action" VARCHAR(50) DEFAULT NULL)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_PERMISSION_CODE ON permission (code)');
        $this->addSql('CREATE TABLE role (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, code VARCHAR(50) NOT NULL, name VARCHAR(100) NOT NULL, description VARCHAR(255) DEFAULT NULL, is_system_role BOOLEAN NOT NULL, is_active BOOLEAN NOT NULL, priority INTEGER NOT NULL, created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , updated_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , translations CLOB DEFAULT NULL --(DC2Type:json)
        )');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_ROLE_CODE ON role (code)');
        $this->addSql('CREATE TABLE role_permissions (role_id INTEGER NOT NULL, permission_id INTEGER NOT NULL, PRIMARY KEY(role_id, permission_id), CONSTRAINT FK_1FBA94E6D60322AC FOREIGN KEY (role_id) REFERENCES role (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_1FBA94E6FED90CCA FOREIGN KEY (permission_id) REFERENCES permission (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_1FBA94E6D60322AC ON role_permissions (role_id)');
        $this->addSql('CREATE INDEX IDX_1FBA94E6FED90CCA ON role_permissions (permission_id)');
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
        $this->addSql('CREATE TABLE setting (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, setting_key VARCHAR(100) NOT NULL, setting_value CLOB DEFAULT NULL, setting_type VARCHAR(20) NOT NULL, setting_name VARCHAR(255) NOT NULL, description CLOB DEFAULT NULL, category VARCHAR(50) NOT NULL, is_public BOOLEAN NOT NULL, is_required BOOLEAN NOT NULL, default_value CLOB DEFAULT NULL, options CLOB DEFAULT NULL --(DC2Type:json)
        , created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , updated_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , sort_order INTEGER DEFAULT NULL)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_IDENTIFIER_SETTING_KEY ON setting (setting_key)');
        $this->addSql('CREATE TABLE user (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles CLOB NOT NULL --(DC2Type:json)
        , password VARCHAR(255) NOT NULL, first_name VARCHAR(100) NOT NULL, last_name VARCHAR(100) NOT NULL, is_active BOOLEAN NOT NULL, created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , updated_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , last_login_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        , phone VARCHAR(20) DEFAULT NULL, client_type VARCHAR(20) NOT NULL, monthly_income NUMERIC(10, 2) DEFAULT NULL, monthly_charges NUMERIC(10, 2) DEFAULT NULL, employment_status VARCHAR(50) DEFAULT NULL, employer VARCHAR(100) DEFAULT NULL, employment_start_date DATE DEFAULT NULL, business_name VARCHAR(100) DEFAULT NULL, business_registration VARCHAR(20) DEFAULT NULL, business_years INTEGER DEFAULT NULL, annual_revenue NUMERIC(12, 2) DEFAULT NULL, is_account_verified BOOLEAN NOT NULL, verified_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        , profile_image VARCHAR(255) DEFAULT NULL, preferred_language VARCHAR(10) DEFAULT NULL, notification_preferences CLOB DEFAULT NULL --(DC2Type:json)
        , privacy_settings CLOB DEFAULT NULL --(DC2Type:json)
        , display_preferences CLOB DEFAULT NULL --(DC2Type:json)
        , is_two_factor_enabled BOOLEAN NOT NULL)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL ON user (email)');
        $this->addSql('CREATE TABLE user_roles (user_id INTEGER NOT NULL, role_id INTEGER NOT NULL, PRIMARY KEY(user_id, role_id), CONSTRAINT FK_54FCD59FA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_54FCD59FD60322AC FOREIGN KEY (role_id) REFERENCES role (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_54FCD59FA76ED395 ON user_roles (user_id)');
        $this->addSql('CREATE INDEX IDX_54FCD59FD60322AC ON user_roles (role_id)');
        $this->addSql('CREATE TABLE messenger_messages (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, body CLOB NOT NULL, headers CLOB NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , available_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , delivered_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        )');
        $this->addSql('CREATE INDEX IDX_75EA56E0FB7336F0 ON messenger_messages (queue_name)');
        $this->addSql('CREATE INDEX IDX_75EA56E0E3BD61CE ON messenger_messages (available_at)');
        $this->addSql('CREATE INDEX IDX_75EA56E016BA31DB ON messenger_messages (delivered_at)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE languages');
        $this->addSql('DROP TABLE loan_applications');
        $this->addSql('DROP TABLE loan_contracts');
        $this->addSql('DROP TABLE loan_documents');
        $this->addSql('DROP TABLE loan_payments');
        $this->addSql('DROP TABLE media');
        $this->addSql('DROP TABLE permission');
        $this->addSql('DROP TABLE role');
        $this->addSql('DROP TABLE role_permissions');
        $this->addSql('DROP TABLE service_translations');
        $this->addSql('DROP TABLE services');
        $this->addSql('DROP TABLE setting');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE user_roles');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
