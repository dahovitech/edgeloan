<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * User Profile Enhancement Migration
 * Adds profile management fields to User entity
 */
final class Version20250925221500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds profile management fields (profile image, preferences, language settings) to User entity';
    }

    public function up(Schema $schema): void
    {
        // Add new columns to user table
        $this->addSql('ALTER TABLE user ADD COLUMN profile_image VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE user ADD COLUMN preferred_language VARCHAR(10) DEFAULT NULL');
        $this->addSql('ALTER TABLE user ADD COLUMN notification_preferences JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE user ADD COLUMN privacy_settings JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE user ADD COLUMN display_preferences JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE user ADD COLUMN is_two_factor_enabled BOOLEAN NOT NULL DEFAULT 0');
    }

    public function down(Schema $schema): void
    {
        // Remove added columns
        $this->addSql('ALTER TABLE user DROP COLUMN profile_image');
        $this->addSql('ALTER TABLE user DROP COLUMN preferred_language');
        $this->addSql('ALTER TABLE user DROP COLUMN notification_preferences');
        $this->addSql('ALTER TABLE user DROP COLUMN privacy_settings');
        $this->addSql('ALTER TABLE user DROP COLUMN display_preferences');
        $this->addSql('ALTER TABLE user DROP COLUMN is_two_factor_enabled');
    }

    public function postUp(Schema $schema): void
    {
        // Set default values for existing users
        $this->addSql("
            UPDATE user 
            SET 
                preferred_language = 'fr',
                notification_preferences = '{\"email_notifications\": true, \"sms_notifications\": false, \"loan_status_updates\": true, \"marketing_communications\": false, \"security_alerts\": true}',
                privacy_settings = '{\"profile_visibility\": \"private\", \"show_email\": false, \"show_phone\": false, \"show_employment_details\": false, \"data_processing_consent\": true}',
                display_preferences = '{\"theme\": \"light\", \"items_per_page\": 10, \"show_tooltips\": true, \"compact_view\": false}',
                is_two_factor_enabled = 0
            WHERE preferred_language IS NULL
        ");
    }
}