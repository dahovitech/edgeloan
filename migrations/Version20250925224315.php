<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250925224315 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__permission AS SELECT id, code, name, description, category, is_system_permission, is_active, priority, created_at, updated_at, translations, resource, "action" FROM permission');
        $this->addSql('DROP TABLE permission');
        $this->addSql('CREATE TABLE permission (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, code VARCHAR(100) NOT NULL, name VARCHAR(100) NOT NULL, description VARCHAR(255) DEFAULT NULL, category VARCHAR(50) NOT NULL, is_system_permission BOOLEAN NOT NULL, is_active BOOLEAN NOT NULL, priority INTEGER NOT NULL, created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , updated_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , translations CLOB DEFAULT NULL --(DC2Type:json)
        , resource VARCHAR(50) DEFAULT NULL, "action" VARCHAR(50) DEFAULT NULL)');
        $this->addSql('INSERT INTO permission (id, code, name, description, category, is_system_permission, is_active, priority, created_at, updated_at, translations, resource, "action") SELECT id, code, name, description, category, is_system_permission, is_active, priority, created_at, updated_at, translations, resource, "action" FROM __temp__permission');
        $this->addSql('DROP TABLE __temp__permission');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_PERMISSION_CODE ON permission (code)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__role AS SELECT id, code, name, description, is_system_role, is_active, priority, created_at, updated_at, translations FROM role');
        $this->addSql('DROP TABLE role');
        $this->addSql('CREATE TABLE role (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, code VARCHAR(50) NOT NULL, name VARCHAR(100) NOT NULL, description VARCHAR(255) DEFAULT NULL, is_system_role BOOLEAN NOT NULL, is_active BOOLEAN NOT NULL, priority INTEGER NOT NULL, created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , updated_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , translations CLOB DEFAULT NULL --(DC2Type:json)
        )');
        $this->addSql('INSERT INTO role (id, code, name, description, is_system_role, is_active, priority, created_at, updated_at, translations) SELECT id, code, name, description, is_system_role, is_active, priority, created_at, updated_at, translations FROM __temp__role');
        $this->addSql('DROP TABLE __temp__role');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_ROLE_CODE ON role (code)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__permission AS SELECT id, code, name, description, category, is_system_permission, is_active, priority, created_at, updated_at, translations, resource, "action" FROM permission');
        $this->addSql('DROP TABLE permission');
        $this->addSql('CREATE TABLE permission (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, code VARCHAR(100) NOT NULL, name VARCHAR(100) NOT NULL, description VARCHAR(255) DEFAULT NULL, category VARCHAR(50) NOT NULL, is_system_permission BOOLEAN NOT NULL, is_active BOOLEAN NOT NULL, priority INTEGER NOT NULL, created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , updated_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , translations CLOB DEFAULT NULL --(DC2Type:json)
        , resource VARCHAR(50) DEFAULT NULL, "action" VARCHAR(50) DEFAULT NULL)');
        $this->addSql('INSERT INTO permission (id, code, name, description, category, is_system_permission, is_active, priority, created_at, updated_at, translations, resource, "action") SELECT id, code, name, description, category, is_system_permission, is_active, priority, created_at, updated_at, translations, resource, "action" FROM __temp__permission');
        $this->addSql('DROP TABLE __temp__permission');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_PERMISSION_CODE ON permission (code)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__role AS SELECT id, code, name, description, is_system_role, is_active, priority, created_at, updated_at, translations FROM role');
        $this->addSql('DROP TABLE role');
        $this->addSql('CREATE TABLE role (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, code VARCHAR(50) NOT NULL, name VARCHAR(100) NOT NULL, description VARCHAR(255) DEFAULT NULL, is_system_role BOOLEAN NOT NULL, is_active BOOLEAN NOT NULL, priority INTEGER NOT NULL, created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , updated_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , translations CLOB DEFAULT NULL --(DC2Type:json)
        )');
        $this->addSql('INSERT INTO role (id, code, name, description, is_system_role, is_active, priority, created_at, updated_at, translations) SELECT id, code, name, description, is_system_role, is_active, priority, created_at, updated_at, translations FROM __temp__role');
        $this->addSql('DROP TABLE __temp__role');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_ROLE_CODE ON role (code)');
    }
}
