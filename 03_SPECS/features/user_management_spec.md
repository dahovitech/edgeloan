# User Management Specification

## Overview

This specification defines the enhanced user management system for EdgeLoan, including role-based access control, user profiles, authentication, and administrative interfaces.

## Business Requirements

### User Stories
- **As an administrator**, I want to manage user accounts so that I can control system access and maintain security
- **As an administrator**, I want to assign roles and permissions so that users have appropriate access levels
- **As a user**, I want to manage my profile information so that my data stays current
- **As a user**, I want to change my password so that I can maintain account security
- **As an administrator**, I want to view user activity logs so that I can monitor system usage and security

### Functional Requirements

1. **User Account Management**
   - Create, read, update, and delete user accounts
   - User profile management with personal information
   - Password management with strength requirements
   - Account activation/deactivation
   - User search and filtering capabilities

2. **Role-Based Access Control**
   - Define custom roles with specific permissions
   - Assign multiple roles to users
   - Permission inheritance and hierarchy
   - Real-time permission checking
   - Role-based UI element visibility

3. **Authentication & Security**
   - Secure login with email/password
   - Password strength validation
   - Account lockout after failed attempts
   - Session management and timeout
   - "Remember Me" functionality

4. **User Interface**
   - Administrative user management dashboard
   - User profile editing interface
   - Role and permission management interface
   - Responsive design for all screen sizes
   - Multi-language support

## Technical Specifications

### Database Schema

#### User Entity
```sql
CREATE TABLE users (
    id CHAR(36) PRIMARY KEY, -- UUID
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL, -- bcrypt hashed
    first_name VARCHAR(255) NOT NULL,
    last_name VARCHAR(255) NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    is_verified BOOLEAN DEFAULT FALSE,
    failed_login_attempts INT DEFAULT 0,
    locked_until DATETIME NULL,
    last_login_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    INDEX idx_users_email (email),
    INDEX idx_users_active (is_active),
    INDEX idx_users_created (created_at)
);
```

#### UserProfile Entity
```sql
CREATE TABLE user_profiles (
    id CHAR(36) PRIMARY KEY, -- UUID
    user_id CHAR(36) UNIQUE NOT NULL,
    phone VARCHAR(20) NULL,
    address_line_1 VARCHAR(255) NULL,
    address_line_2 VARCHAR(255) NULL,
    city VARCHAR(100) NULL,
    postal_code VARCHAR(20) NULL,
    country VARCHAR(2) NULL, -- ISO country code
    avatar_path VARCHAR(500) NULL,
    locale VARCHAR(5) DEFAULT 'fr',
    timezone VARCHAR(50) DEFAULT 'Europe/Paris',
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

#### Role Entity
```sql
CREATE TABLE roles (
    id CHAR(36) PRIMARY KEY, -- UUID
    name VARCHAR(100) UNIQUE NOT NULL,
    description TEXT NULL,
    is_system BOOLEAN DEFAULT FALSE, -- System roles cannot be deleted
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    INDEX idx_roles_name (name)
);
```

#### Permission Entity
```sql
CREATE TABLE permissions (
    id CHAR(36) PRIMARY KEY, -- UUID
    name VARCHAR(100) UNIQUE NOT NULL,
    resource VARCHAR(100) NOT NULL, -- e.g., 'user', 'loan', 'document'
    action VARCHAR(50) NOT NULL, -- e.g., 'create', 'read', 'update', 'delete'
    description TEXT NULL,
    created_at DATETIME NOT NULL,
    UNIQUE KEY uk_permission_resource_action (resource, action),
    INDEX idx_permissions_resource (resource)
);
```

#### User-Role Relationship
```sql
CREATE TABLE user_roles (
    user_id CHAR(36) NOT NULL,
    role_id CHAR(36) NOT NULL,
    assigned_at DATETIME NOT NULL,
    assigned_by CHAR(36) NULL, -- User who assigned the role
    PRIMARY KEY (user_id, role_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_by) REFERENCES users(id) ON DELETE SET NULL
);
```

#### Role-Permission Relationship
```sql
CREATE TABLE role_permissions (
    role_id CHAR(36) NOT NULL,
    permission_id CHAR(36) NOT NULL,
    granted_at DATETIME NOT NULL,
    granted_by CHAR(36) NULL,
    PRIMARY KEY (role_id, permission_id),
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE,
    FOREIGN KEY (granted_by) REFERENCES users(id) ON DELETE SET NULL
);
```

### Entity Implementations

#### User Entity
```php
<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Uid\UuidV4;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'users')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private ?UuidV4 $id = null;

    #[ORM\Column(type: 'string', length: 255, unique: true)]
    #[Assert\NotBlank(message: 'user.email.not_blank')]
    #[Assert\Email(message: 'user.email.invalid')]
    private ?string $email = null;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $password = null;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank(message: 'user.first_name.not_blank')]
    #[Assert\Length(max: 255, maxMessage: 'user.first_name.too_long')]
    private ?string $firstName = null;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank(message: 'user.last_name.not_blank')]
    #[Assert\Length(max: 255, maxMessage: 'user.last_name.too_long')]
    private ?string $lastName = null;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $isActive = true;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $isVerified = false;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $failedLoginAttempts = 0;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $lockedUntil = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $lastLoginAt = null;

    #[ORM\OneToOne(targetEntity: UserProfile::class, mappedBy: 'user', cascade: ['persist', 'remove'])]
    private ?UserProfile $profile = null;

    #[ORM\ManyToMany(targetEntity: Role::class, inversedBy: 'users')]
    #[ORM\JoinTable(name: 'user_roles')]
    private Collection $roles;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->roles = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    // UserInterface implementation
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    public function getRoles(): array
    {
        $roles = [];
        foreach ($this->roles as $role) {
            $roles[] = 'ROLE_' . strtoupper($role->getName());
        }
        
        // Guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
    }

    // Security methods
    public function isAccountLocked(): bool
    {
        return $this->lockedUntil !== null && $this->lockedUntil > new \DateTimeImmutable();
    }

    public function incrementFailedLoginAttempts(): void
    {
        $this->failedLoginAttempts++;
        $this->updateTimestamp();
        
        // Lock account after 5 failed attempts for 30 minutes
        if ($this->failedLoginAttempts >= 5) {
            $this->lockedUntil = new \DateTimeImmutable('+30 minutes');
        }
    }

    public function resetFailedLoginAttempts(): void
    {
        $this->failedLoginAttempts = 0;
        $this->lockedUntil = null;
        $this->lastLoginAt = new \DateTimeImmutable();
        $this->updateTimestamp();
    }

    // Getters and setters...
    
    private function updateTimestamp(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function __toString(): string
    {
        return $this->getFullName();
    }

    public function getFullName(): string
    {
        return trim($this->firstName . ' ' . $this->lastName);
    }
}
```

### API Endpoints

#### User Management API
```
GET    /api/users                    # List users with pagination and filters
POST   /api/users                    # Create new user
GET    /api/users/{id}               # Get user details
PUT    /api/users/{id}               # Update user
DELETE /api/users/{id}               # Delete user (soft delete)
POST   /api/users/{id}/activate      # Activate user account
POST   /api/users/{id}/deactivate    # Deactivate user account
POST   /api/users/{id}/reset-password # Send password reset email

GET    /api/users/{id}/roles         # Get user roles
POST   /api/users/{id}/roles         # Assign role to user
DELETE /api/users/{id}/roles/{roleId} # Remove role from user

GET    /api/users/{id}/permissions   # Get effective permissions
GET    /api/users/{id}/profile       # Get user profile
PUT    /api/users/{id}/profile       # Update user profile
```

#### Role Management API
```
GET    /api/roles                    # List roles
POST   /api/roles                    # Create role
GET    /api/roles/{id}               # Get role details
PUT    /api/roles/{id}               # Update role
DELETE /api/roles/{id}               # Delete role

GET    /api/roles/{id}/permissions   # Get role permissions
POST   /api/roles/{id}/permissions   # Grant permission to role
DELETE /api/roles/{id}/permissions/{permissionId} # Revoke permission

GET    /api/permissions              # List available permissions
```

### Security Considerations

1. **Password Security**
   - Minimum 8 characters with complexity requirements
   - bcrypt hashing with cost factor 12+
   - Password history to prevent reuse
   - Secure password reset process

2. **Session Security**
   - Secure session cookies (httpOnly, secure, sameSite)
   - Session timeout after inactivity
   - Session regeneration after login
   - Logout invalidates session

3. **Access Control**
   - Permission-based access control at controller level
   - Voter classes for complex authorization logic
   - Rate limiting for authentication endpoints
   - CSRF protection on all forms

4. **Data Protection**
   - Input validation and sanitization
   - SQL injection prevention via ORM
   - XSS prevention through Twig escaping
   - Personal data encryption where required

### User Interface Specifications

#### Admin User Management Dashboard
- User list with search, filtering, and sorting
- Bulk operations (activate/deactivate, assign roles)
- User creation and editing forms
- Role and permission assignment interface
- Activity log and audit trail
- Responsive design for mobile access

#### User Profile Management
- Personal information editing form
- Avatar upload with image cropping
- Password change form with strength indicator
- Account preferences and settings
- Two-factor authentication setup (future)
- Privacy and security settings

### Multi-Language Support

#### Translation Keys Structure
```yaml
# admin.fr.yaml
user:
  title: "Utilisateurs"
  create: "Créer un utilisateur"
  edit: "Modifier l'utilisateur"
  delete: "Supprimer l'utilisateur"
  profile: "Profil utilisateur"
  roles: "Rôles"
  permissions: "Permissions"
  
  form:
    email:
      label: "Adresse e-mail"
      placeholder: "Saisir l'adresse e-mail"
      help: "Adresse e-mail unique pour la connexion"
    first_name:
      label: "Prénom"
      placeholder: "Saisir le prénom"
    last_name:
      label: "Nom de famille"
      placeholder: "Saisir le nom de famille"
    password:
      label: "Mot de passe"
      placeholder: "Saisir le mot de passe"
      help: "Minimum 8 caractères avec majuscules, minuscules et chiffres"
      
  status:
    active: "Actif"
    inactive: "Inactif"
    locked: "Verrouillé"
    pending: "En attente"
    
  messages:
    created: "Utilisateur créé avec succès"
    updated: "Utilisateur modifié avec succès"
    deleted: "Utilisateur supprimé avec succès"
    activated: "Compte activé avec succès"
    deactivated: "Compte désactivé avec succès"
```

### Testing Requirements

#### Unit Tests
- Entity validation and business logic
- Repository query methods
- Service layer functionality
- Form validation and processing
- Security voter logic

#### Integration Tests
- Authentication workflow
- Role assignment and permission checking
- API endpoint functionality
- Database operations and migrations
- Multi-language content rendering

#### Security Tests
- Authentication bypass attempts
- Privilege escalation prevention
- Input validation and sanitization
- Session management security
- Password security requirements

### Performance Considerations

1. **Database Optimization**
   - Proper indexing on frequently queried columns
   - Efficient joins for role and permission queries
   - Pagination for large user lists
   - Query caching for permission checks

2. **Caching Strategy**
   - User permission cache with TTL
   - Role hierarchy cache
   - Translation cache for multi-language support
   - Session-based caching for user data

3. **Frontend Optimization**
   - Lazy loading of user lists
   - Client-side form validation
   - Progressive enhancement
   - Optimized asset delivery

### Compliance and Audit

1. **GDPR Compliance**
   - Data retention policies
   - Right to be forgotten implementation
   - Consent management
   - Data portability features

2. **Audit Trail**
   - User creation, modification, deletion
   - Role and permission changes
   - Login attempts and failures
   - Administrative actions

3. **Security Monitoring**
   - Failed login attempt monitoring
   - Privilege escalation detection
   - Unusual access pattern alerts
   - Regular security audits

This specification provides a comprehensive foundation for implementing a robust, secure, and user-friendly user management system that meets enterprise-level requirements while maintaining excellent user experience across all supported languages.
