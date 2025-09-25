<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Enhanced Role Management System Migration
 * Creates Role, Permission entities and their relationships
 */
final class Version20250925220000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Creates enhanced role management system with granular permissions';
    }

    public function up(Schema $schema): void
    {
        // Create Permission table
        $this->addSql('CREATE TABLE permission (
            id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
            code VARCHAR(100) NOT NULL,
            name VARCHAR(100) NOT NULL,
            description VARCHAR(255) DEFAULT NULL,
            category VARCHAR(50) NOT NULL,
            is_system_permission BOOLEAN NOT NULL DEFAULT 0,
            is_active BOOLEAN NOT NULL DEFAULT 1,
            priority INTEGER NOT NULL DEFAULT 0,
            resource VARCHAR(50) DEFAULT NULL,
            action VARCHAR(50) DEFAULT NULL,
            translations JSON DEFAULT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL
        )');

        // Create unique index for permission code
        $this->addSql('CREATE UNIQUE INDEX UNIQ_IDENTIFIER_CODE ON permission (code)');

        // Create Role table
        $this->addSql('CREATE TABLE role (
            id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
            code VARCHAR(50) NOT NULL,
            name VARCHAR(100) NOT NULL,
            description VARCHAR(255) DEFAULT NULL,
            is_system_role BOOLEAN NOT NULL DEFAULT 0,
            is_active BOOLEAN NOT NULL DEFAULT 1,
            priority INTEGER NOT NULL DEFAULT 0,
            translations JSON DEFAULT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL
        )');

        // Create unique index for role code
        $this->addSql('CREATE UNIQUE INDEX UNIQ_IDENTIFIER_CODE ON role (code)');

        // Create role_permissions junction table
        $this->addSql('CREATE TABLE role_permissions (
            role_id INTEGER NOT NULL,
            permission_id INTEGER NOT NULL,
            PRIMARY KEY(role_id, permission_id),
            CONSTRAINT FK_5D6255E2D60322AC FOREIGN KEY (role_id) REFERENCES role (id) ON DELETE CASCADE,
            CONSTRAINT FK_5D6255E2FED90CCA FOREIGN KEY (permission_id) REFERENCES permission (id) ON DELETE CASCADE
        )');

        // Create indexes for junction table
        $this->addSql('CREATE INDEX IDX_5D6255E2D60322AC ON role_permissions (role_id)');
        $this->addSql('CREATE INDEX IDX_5D6255E2FED90CCA ON role_permissions (permission_id)');

        // Create user_roles junction table
        $this->addSql('CREATE TABLE user_roles (
            user_id INTEGER NOT NULL,
            role_id INTEGER NOT NULL,
            PRIMARY KEY(user_id, role_id),
            CONSTRAINT FK_54FCD59FA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE,
            CONSTRAINT FK_54FCD59FD60322AC FOREIGN KEY (role_id) REFERENCES role (id) ON DELETE CASCADE
        )');

        // Create indexes for user_roles junction table
        $this->addSql('CREATE INDEX IDX_54FCD59FA76ED395 ON user_roles (user_id)');
        $this->addSql('CREATE INDEX IDX_54FCD59FD60322AC ON user_roles (role_id)');
    }

    public function down(Schema $schema): void
    {
        // Drop junction tables first
        $this->addSql('DROP TABLE user_roles');
        $this->addSql('DROP TABLE role_permissions');
        
        // Drop main tables
        $this->addSql('DROP TABLE role');
        $this->addSql('DROP TABLE permission');
    }

    public function postUp(Schema $schema): void
    {
        // Insert default permissions
        $this->insertDefaultPermissions();
        
        // Insert default roles
        $this->insertDefaultRoles();
        
        // Assign permissions to roles
        $this->assignPermissionsToRoles();
    }

    private function insertDefaultPermissions(): void
    {
        $permissions = [
            // User Management
            ['USER_MANAGEMENT_CREATE', 'Créer des utilisateurs', 'USER_MANAGEMENT', 'Autorisation de créer de nouveaux utilisateurs', 'USER', 'CREATE'],
            ['USER_MANAGEMENT_READ', 'Consulter les utilisateurs', 'USER_MANAGEMENT', 'Autorisation de consulter les informations utilisateurs', 'USER', 'READ'],
            ['USER_MANAGEMENT_UPDATE', 'Modifier les utilisateurs', 'USER_MANAGEMENT', 'Autorisation de modifier les informations utilisateurs', 'USER', 'UPDATE'],
            ['USER_MANAGEMENT_DELETE', 'Supprimer des utilisateurs', 'USER_MANAGEMENT', 'Autorisation de supprimer des utilisateurs', 'USER', 'DELETE'],
            ['USER_MANAGEMENT_LIST', 'Lister les utilisateurs', 'USER_MANAGEMENT', 'Autorisation de voir la liste des utilisateurs', 'USER', 'LIST'],
            
            // Loan Management
            ['LOAN_MANAGEMENT_CREATE', 'Créer des demandes de prêt', 'LOAN_MANAGEMENT', 'Autorisation de créer des demandes de prêt', 'LOAN', 'CREATE'],
            ['LOAN_MANAGEMENT_READ', 'Consulter les prêts', 'LOAN_MANAGEMENT', 'Autorisation de consulter les informations de prêt', 'LOAN', 'READ'],
            ['LOAN_MANAGEMENT_UPDATE', 'Modifier les prêts', 'LOAN_MANAGEMENT', 'Autorisation de modifier les informations de prêt', 'LOAN', 'UPDATE'],
            ['LOAN_MANAGEMENT_DELETE', 'Supprimer des prêts', 'LOAN_MANAGEMENT', 'Autorisation de supprimer des prêts', 'LOAN', 'DELETE'],
            ['LOAN_MANAGEMENT_APPROVE', 'Approuver des prêts', 'LOAN_MANAGEMENT', 'Autorisation d\'approuver des demandes de prêt', 'LOAN', 'APPROVE'],
            ['LOAN_MANAGEMENT_REJECT', 'Rejeter des prêts', 'LOAN_MANAGEMENT', 'Autorisation de rejeter des demandes de prêt', 'LOAN', 'REJECT'],
            
            // Document Management
            ['DOCUMENT_MANAGEMENT_CREATE', 'Créer des documents', 'DOCUMENT_MANAGEMENT', 'Autorisation de créer/télécharger des documents', 'DOCUMENT', 'CREATE'],
            ['DOCUMENT_MANAGEMENT_READ', 'Consulter les documents', 'DOCUMENT_MANAGEMENT', 'Autorisation de consulter les documents', 'DOCUMENT', 'READ'],
            ['DOCUMENT_MANAGEMENT_UPDATE', 'Modifier les documents', 'DOCUMENT_MANAGEMENT', 'Autorisation de modifier les documents', 'DOCUMENT', 'UPDATE'],
            ['DOCUMENT_MANAGEMENT_DELETE', 'Supprimer des documents', 'DOCUMENT_MANAGEMENT', 'Autorisation de supprimer des documents', 'DOCUMENT', 'DELETE'],
            
            // System Administration
            ['SYSTEM_ADMINISTRATION_ROLES', 'Gérer les rôles', 'SYSTEM_ADMINISTRATION', 'Autorisation de gérer les rôles et permissions', 'ROLE', 'UPDATE'],
            ['SYSTEM_ADMINISTRATION_SETTINGS', 'Gérer les paramètres', 'SYSTEM_ADMINISTRATION', 'Autorisation de modifier les paramètres système', 'SETTING', 'UPDATE'],
            ['SYSTEM_ADMINISTRATION_AUDIT', 'Consulter l\'audit', 'SYSTEM_ADMINISTRATION', 'Autorisation de consulter les logs d\'audit', 'AUDIT', 'READ'],
            
            // Reporting
            ['REPORTING_VIEW', 'Consulter les rapports', 'REPORTING', 'Autorisation de consulter les rapports', 'REPORT', 'READ'],
            ['REPORTING_EXPORT', 'Exporter les rapports', 'REPORTING', 'Autorisation d\'exporter les rapports', 'REPORT', 'EXPORT'],
            
            // Financial
            ['FINANCIAL_VIEW', 'Consulter les finances', 'FINANCIAL', 'Autorisation de consulter les informations financières', 'FINANCIAL', 'READ'],
            ['FINANCIAL_MANAGE', 'Gérer les finances', 'FINANCIAL', 'Autorisation de gérer les informations financières', 'FINANCIAL', 'UPDATE']
        ];

        $datetime = (new \DateTimeImmutable())->format('Y-m-d H:i:s');

        foreach ($permissions as $permission) {
            $this->addSql("INSERT INTO permission (code, name, category, description, resource, action, is_system_permission, is_active, priority, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, 1, 1, 50, ?, ?)", 
                [$permission[0], $permission[1], $permission[2], $permission[3], $permission[4], $permission[5], $datetime, $datetime]);
        }
    }

    private function insertDefaultRoles(): void
    {
        $roles = [
            ['ROLE_SUPER_ADMIN', 'Super Administrateur', 'Accès complet à toutes les fonctionnalités du système', true, 100],
            ['ROLE_ADMIN', 'Administrateur', 'Accès administratif avec restrictions', true, 90],
            ['ROLE_LOAN_OFFICER', 'Responsable des Prêts', 'Gestion des demandes de prêt et approbations', false, 70],
            ['ROLE_USER_MANAGER', 'Gestionnaire d\'Utilisateurs', 'Gestion des comptes utilisateurs', false, 60],
            ['ROLE_FINANCIAL_ANALYST', 'Analyste Financier', 'Analyse et reporting financier', false, 50],
            ['ROLE_CUSTOMER', 'Client', 'Accès client standard', false, 10],
        ];

        $datetime = (new \DateTimeImmutable())->format('Y-m-d H:i:s');

        foreach ($roles as $role) {
            $this->addSql("INSERT INTO role (code, name, description, is_system_role, is_active, priority, created_at, updated_at) VALUES (?, ?, ?, ?, 1, ?, ?, ?)", 
                [$role[0], $role[1], $role[2], $role[3], $role[4], $datetime, $datetime]);
        }
    }

    private function assignPermissionsToRoles(): void
    {
        // Super Admin gets all permissions
        $this->addSql("
            INSERT INTO role_permissions (role_id, permission_id)
            SELECT r.id, p.id 
            FROM role r, permission p 
            WHERE r.code = 'ROLE_SUPER_ADMIN' AND p.is_active = 1
        ");

        // Admin gets most permissions except super admin specific ones
        $adminPermissions = [
            'USER_MANAGEMENT_CREATE', 'USER_MANAGEMENT_READ', 'USER_MANAGEMENT_UPDATE', 'USER_MANAGEMENT_LIST',
            'LOAN_MANAGEMENT_CREATE', 'LOAN_MANAGEMENT_READ', 'LOAN_MANAGEMENT_UPDATE', 'LOAN_MANAGEMENT_APPROVE', 'LOAN_MANAGEMENT_REJECT',
            'DOCUMENT_MANAGEMENT_CREATE', 'DOCUMENT_MANAGEMENT_READ', 'DOCUMENT_MANAGEMENT_UPDATE', 'DOCUMENT_MANAGEMENT_DELETE',
            'REPORTING_VIEW', 'REPORTING_EXPORT',
            'FINANCIAL_VIEW'
        ];
        
        foreach ($adminPermissions as $permissionCode) {
            $this->addSql("
                INSERT INTO role_permissions (role_id, permission_id)
                SELECT r.id, p.id 
                FROM role r, permission p 
                WHERE r.code = 'ROLE_ADMIN' AND p.code = ?
            ", [$permissionCode]);
        }

        // Loan Officer permissions
        $loanOfficerPermissions = [
            'LOAN_MANAGEMENT_CREATE', 'LOAN_MANAGEMENT_READ', 'LOAN_MANAGEMENT_UPDATE', 'LOAN_MANAGEMENT_APPROVE', 'LOAN_MANAGEMENT_REJECT',
            'DOCUMENT_MANAGEMENT_READ', 'DOCUMENT_MANAGEMENT_CREATE',
            'USER_MANAGEMENT_READ',
            'REPORTING_VIEW'
        ];
        
        foreach ($loanOfficerPermissions as $permissionCode) {
            $this->addSql("
                INSERT INTO role_permissions (role_id, permission_id)
                SELECT r.id, p.id 
                FROM role r, permission p 
                WHERE r.code = 'ROLE_LOAN_OFFICER' AND p.code = ?
            ", [$permissionCode]);
        }

        // User Manager permissions
        $userManagerPermissions = [
            'USER_MANAGEMENT_CREATE', 'USER_MANAGEMENT_READ', 'USER_MANAGEMENT_UPDATE', 'USER_MANAGEMENT_LIST',
            'REPORTING_VIEW'
        ];
        
        foreach ($userManagerPermissions as $permissionCode) {
            $this->addSql("
                INSERT INTO role_permissions (role_id, permission_id)
                SELECT r.id, p.id 
                FROM role r, permission p 
                WHERE r.code = 'ROLE_USER_MANAGER' AND p.code = ?
            ", [$permissionCode]);
        }

        // Financial Analyst permissions
        $financialAnalystPermissions = [
            'FINANCIAL_VIEW', 'REPORTING_VIEW', 'REPORTING_EXPORT',
            'LOAN_MANAGEMENT_READ'
        ];
        
        foreach ($financialAnalystPermissions as $permissionCode) {
            $this->addSql("
                INSERT INTO role_permissions (role_id, permission_id)
                SELECT r.id, p.id 
                FROM role r, permission p 
                WHERE r.code = 'ROLE_FINANCIAL_ANALYST' AND p.code = ?
            ", [$permissionCode]);
        }

        // Customer gets basic permissions
        $customerPermissions = [
            'LOAN_MANAGEMENT_CREATE', 'LOAN_MANAGEMENT_READ',
            'DOCUMENT_MANAGEMENT_CREATE', 'DOCUMENT_MANAGEMENT_READ'
        ];
        
        foreach ($customerPermissions as $permissionCode) {
            $this->addSql("
                INSERT INTO role_permissions (role_id, permission_id)
                SELECT r.id, p.id 
                FROM role r, permission p 
                WHERE r.code = 'ROLE_CUSTOMER' AND p.code = ?
            ", [$permissionCode]);
        }
    }
}