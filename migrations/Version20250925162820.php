<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration pour créer la table de paramètres système
 * Cette migration crée uniquement la structure de la table.
 * Les données par défaut sont gérées par SettingFixtures.
 */
final class Version20250925162820 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create setting table for site configuration management';
    }

    public function up(Schema $schema): void
    {
        // Create setting table - SQLite compatible
        $this->addSql('CREATE TABLE setting (
            id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, 
            setting_key VARCHAR(100) NOT NULL, 
            setting_value TEXT DEFAULT NULL, 
            setting_type VARCHAR(20) NOT NULL DEFAULT "string", 
            setting_name VARCHAR(255) NOT NULL, 
            description TEXT DEFAULT NULL, 
            category VARCHAR(50) NOT NULL DEFAULT "general", 
            is_public BOOLEAN NOT NULL DEFAULT 0, 
            is_required BOOLEAN NOT NULL DEFAULT 0, 
            default_value TEXT DEFAULT NULL, 
            options TEXT DEFAULT NULL, 
            created_at DATETIME NOT NULL, 
            updated_at DATETIME NOT NULL, 
            sort_order INTEGER DEFAULT NULL
        )');
        
        $this->addSql('CREATE UNIQUE INDEX UNIQ_IDENTIFIER_SETTING_KEY ON setting (setting_key)');
        $this->addSql('CREATE INDEX IDX_SETTING_CATEGORY ON setting (category)');
        $this->addSql('CREATE INDEX IDX_SETTING_PUBLIC ON setting (is_public)');
    }

    public function down(Schema $schema): void
    {
        // Sauvegarder les données importantes avant suppression
        $this->addSql('-- ATTENTION: Cette migration supprime définitivement toutes les données de paramètres');
        $this->addSql('DROP INDEX UNIQ_IDENTIFIER_SETTING_KEY');
        $this->addSql('DROP INDEX IDX_SETTING_CATEGORY');
        $this->addSql('DROP INDEX IDX_SETTING_PUBLIC');
        $this->addSql('DROP TABLE setting');
    }
}