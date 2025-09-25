<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250925162820 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create setting table for site configuration management';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE setting (
            id INT AUTO_INCREMENT NOT NULL, 
            setting_key VARCHAR(100) NOT NULL, 
            setting_value LONGTEXT DEFAULT NULL, 
            setting_type VARCHAR(20) NOT NULL DEFAULT "string", 
            setting_name VARCHAR(255) NOT NULL, 
            description LONGTEXT DEFAULT NULL, 
            category VARCHAR(50) NOT NULL DEFAULT "general", 
            is_public TINYINT(1) NOT NULL DEFAULT 0, 
            is_required TINYINT(1) NOT NULL DEFAULT 0, 
            default_value LONGTEXT DEFAULT NULL, 
            options JSON DEFAULT NULL, 
            created_at DATETIME NOT NULL COMMENT "(DC2Type:datetime_immutable)", 
            updated_at DATETIME NOT NULL COMMENT "(DC2Type:datetime_immutable)", 
            sort_order INT DEFAULT NULL, 
            UNIQUE INDEX UNIQ_IDENTIFIER_SETTING_KEY (setting_key), 
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        
        // Insert default settings
        $this->addSql("INSERT INTO setting (setting_key, setting_value, setting_type, setting_name, description, category, is_public, is_required, default_value, created_at, updated_at, sort_order) VALUES 
            ('site_name', 'EdgeLoan', 'string', 'Nom du site', 'Le nom principal du site web', 'general', 1, 1, 'EdgeLoan', NOW(), NOW(), 1),
            ('site_description', 'Solution de prêts en ligne moderne et sécurisée', 'text', 'Description du site', 'Description utilisée pour le SEO et les réseaux sociaux', 'general', 1, 1, '', NOW(), NOW(), 2),
            ('site_url', 'https://edgeloan.com', 'url', 'URL du site', 'URL complète du site web', 'general', 1, 1, '', NOW(), NOW(), 3),
            ('site_logo', '/assets/images/logo.png', 'file', 'Logo du site', 'Chemin vers le logo principal', 'branding', 1, 0, '/assets/images/logo.png', NOW(), NOW(), 1),
            ('site_favicon', '/favicon.ico', 'file', 'Favicon', 'Icône du site (favicon)', 'branding', 1, 0, '/favicon.ico', NOW(), NOW(), 2),
            ('contact_email', 'contact@edgeloan.com', 'email', 'Email de contact', 'Adresse email principale de contact', 'contact', 1, 1, '', NOW(), NOW(), 1),
            ('contact_phone', '+33 1 23 45 67 89', 'string', 'Téléphone de contact', 'Numéro de téléphone principal', 'contact', 1, 1, '', NOW(), NOW(), 2),
            ('contact_address', '123 Rue de la Finance, 75001 Paris, France', 'text', 'Adresse', 'Adresse postale complète', 'contact', 1, 1, '', NOW(), NOW(), 3),
            ('social_facebook', 'https://facebook.com/edgeloan', 'url', 'Facebook', 'URL de la page Facebook', 'social', 1, 0, '', NOW(), NOW(), 1),
            ('social_twitter', 'https://twitter.com/edgeloan', 'url', 'Twitter/X', 'URL du profil Twitter/X', 'social', 1, 0, '', NOW(), NOW(), 2),
            ('social_linkedin', 'https://linkedin.com/company/edgeloan', 'url', 'LinkedIn', 'URL de la page LinkedIn', 'social', 1, 0, '', NOW(), NOW(), 3),
            ('maintenance_mode', '0', 'boolean', 'Mode maintenance', 'Activer/désactiver le mode maintenance', 'system', 0, 0, '0', NOW(), NOW(), 1),
            ('max_loan_amount', '100000', 'integer', 'Montant maximum de prêt', 'Montant maximum autorisé pour un prêt', 'loan', 1, 1, '100000', NOW(), NOW(), 1),
            ('min_loan_amount', '1000', 'integer', 'Montant minimum de prêt', 'Montant minimum autorisé pour un prêt', 'loan', 1, 1, '1000', NOW(), NOW(), 2),
            ('theme_primary_color', '#007bff', 'color', 'Couleur principale', 'Couleur principale du thème', 'theme', 1, 0, '#007bff', NOW(), NOW(), 1),
            ('theme_secondary_color', '#6c757d', 'color', 'Couleur secondaire', 'Couleur secondaire du thème', 'theme', 1, 0, '#6c757d', NOW(), NOW(), 2)
        ");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE setting');
    }
}