<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration to create loan review and status history tables
 */
final class Version20250926004540 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create loan review and application status history tables for workflow management';
    }

    public function up(Schema $schema): void
    {
        // Create loan_reviews table
        $this->addSql('CREATE TABLE loan_reviews (
            id CHAR(36) NOT NULL PRIMARY KEY,
            application_id CHAR(36) NOT NULL,
            reviewer_id INTEGER NOT NULL,
            review_type VARCHAR(50) NOT NULL DEFAULT "initial",
            status VARCHAR(50) NOT NULL DEFAULT "pending",
            decision VARCHAR(50) DEFAULT NULL,
            score INTEGER DEFAULT NULL,
            risk_level VARCHAR(50) DEFAULT NULL,
            comments TEXT DEFAULT NULL,
            conditions TEXT DEFAULT NULL,
            requested_documents TEXT DEFAULT NULL,
            started_at DATETIME DEFAULT NULL,
            completed_at DATETIME DEFAULT NULL,
            created_at DATETIME NOT NULL,
            CONSTRAINT FK_LOAN_REVIEWS_APPLICATION FOREIGN KEY (application_id) REFERENCES loan_applications (uuid) ON DELETE CASCADE,
            CONSTRAINT FK_LOAN_REVIEWS_REVIEWER FOREIGN KEY (reviewer_id) REFERENCES user (id) ON DELETE CASCADE
        )');

        // Create indexes for loan_reviews
        $this->addSql('CREATE INDEX IDX_LOAN_REVIEWS_APPLICATION ON loan_reviews (application_id)');
        $this->addSql('CREATE INDEX IDX_LOAN_REVIEWS_REVIEWER ON loan_reviews (reviewer_id)');
        $this->addSql('CREATE INDEX IDX_LOAN_REVIEWS_STATUS ON loan_reviews (status)');
        $this->addSql('CREATE INDEX IDX_LOAN_REVIEWS_DECISION ON loan_reviews (decision)');
        $this->addSql('CREATE INDEX IDX_LOAN_REVIEWS_CREATED ON loan_reviews (created_at)');

        // Create application_status_history table
        $this->addSql('CREATE TABLE application_status_history (
            id CHAR(36) NOT NULL PRIMARY KEY,
            application_id CHAR(36) NOT NULL,
            previous_status VARCHAR(50) DEFAULT NULL,
            new_status VARCHAR(50) NOT NULL,
            changed_by INTEGER NOT NULL,
            reason TEXT DEFAULT NULL,
            automated BOOLEAN NOT NULL DEFAULT 0,
            changed_at DATETIME NOT NULL,
            CONSTRAINT FK_STATUS_HISTORY_APPLICATION FOREIGN KEY (application_id) REFERENCES loan_applications (uuid) ON DELETE CASCADE,
            CONSTRAINT FK_STATUS_HISTORY_CHANGED_BY FOREIGN KEY (changed_by) REFERENCES user (id) ON DELETE CASCADE
        )');

        // Create indexes for application_status_history
        $this->addSql('CREATE INDEX IDX_STATUS_HISTORY_APPLICATION ON application_status_history (application_id)');
        $this->addSql('CREATE INDEX IDX_STATUS_HISTORY_CHANGED_BY ON application_status_history (changed_by)');
        $this->addSql('CREATE INDEX IDX_STATUS_HISTORY_STATUS ON application_status_history (new_status)');
        $this->addSql('CREATE INDEX IDX_STATUS_HISTORY_DATE ON application_status_history (changed_at)');
        $this->addSql('CREATE INDEX IDX_STATUS_HISTORY_AUTOMATED ON application_status_history (automated)');

        // Add assignedTo field to loan_applications if it doesn't exist
        $this->addSql('ALTER TABLE loan_applications ADD COLUMN assigned_to INTEGER DEFAULT NULL');
        $this->addSql('CREATE INDEX IDX_LOAN_APPS_ASSIGNED_TO ON loan_applications (assigned_to)');
    }

    public function down(Schema $schema): void
    {
        // Drop the tables in reverse order
        $this->addSql('DROP TABLE IF EXISTS application_status_history');
        $this->addSql('DROP TABLE IF EXISTS loan_reviews');
        
        // Remove assignedTo field from loan_applications
        $this->addSql('DROP INDEX IF EXISTS IDX_LOAN_APPS_ASSIGNED_TO');
        $this->addSql('ALTER TABLE loan_applications DROP COLUMN IF EXISTS assigned_to');
    }
}