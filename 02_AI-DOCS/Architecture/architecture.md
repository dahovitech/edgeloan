# Architecture Specification for EdgeLoan

## System Overview

EdgeLoan is a modern, enterprise-grade loan management system built with Symfony 7 and PHP 8+. The architecture follows Domain-Driven Design principles with a focus on maintainability, scalability, and security.

## Technology Stack

### Backend
- **Framework:** Symfony 7.x
- **PHP Version:** 8.2+
- **Database:** MySQL 8.0 / SQLite (development)
- **ORM:** Doctrine ORM 3.x
- **Cache:** Symfony Cache Component
- **Queue:** Symfony Messenger
- **Validation:** Symfony Validator
- **Security:** Symfony Security Bundle

### Frontend
- **Template Engine:** Twig 3.x
- **CSS Framework:** Bootstrap 5.3
- **Icons:** Bootstrap Icons
- **JavaScript:** Vanilla ES6+ / Stimulus (optional)
- **Build Tool:** Webpack Encore
- **Asset Management:** Symfony AssetMapper

### Development & Deployment
- **Containerization:** Docker & Docker Compose
- **Testing:** PHPUnit 10+
- **Static Analysis:** PHPStan Level 8
- **Code Style:** PHP CS Fixer (PSR-12)
- **CI/CD:** GitHub Actions (recommended)

## Architectural Patterns

### Domain-Driven Design Structure

```
src/
├── Controller/           # HTTP controllers
│   ├── Admin/           # Administrative interface
│   ├── Api/             # REST API endpoints
│   └── Frontend/        # Public-facing controllers
├── Entity/              # Doctrine entities (domain models)
├── Repository/          # Data access layer
├── Service/             # Business logic services
├── Form/                # Symfony form types
├── Security/            # Authentication & authorization
├── EventListener/       # Event-driven components
├── Command/             # Console commands
├── DataFixtures/        # Database seeders
└── Twig/                # Custom Twig extensions
```

### Layered Architecture

1. **Presentation Layer** (Controllers, Forms, Templates)
2. **Application Layer** (Services, Event Listeners)
3. **Domain Layer** (Entities, Value Objects)
4. **Infrastructure Layer** (Repositories, External Services)

## Core Entities & Relationships

### User Management Domain

```php
// Core user entity with role-based access
User {
  id: UuidV4
  email: string (unique)
  password: string (hashed)
  firstName: string
  lastName: string
  roles: array<string>
  isActive: boolean
  profile: UserProfile (OneToOne)
  createdAt: DateTimeImmutable
  updatedAt: DateTimeImmutable
}

UserProfile {
  id: UuidV4
  user: User (OneToOne)
  phone: string?
  address: Address (Embedded)
  avatar: string?
  locale: string (default: 'fr')
  timezone: string (default: 'Europe/Paris')
}

Role {
  id: UuidV4
  name: string (unique)
  description: string?
  permissions: array<Permission> (ManyToMany)
  users: array<User> (ManyToMany)
}

Permission {
  id: UuidV4
  name: string (unique)
  resource: string
  action: string
  description: string?
}
```

### Loan Management Domain

```php
LoanApplication {
  id: UuidV4
  applicant: User (ManyToOne)
  amount: decimal
  purpose: string
  status: LoanStatus (enum)
  documents: array<Document> (OneToMany)
  reviewHistory: array<LoanReview> (OneToMany)
  submittedAt: DateTimeImmutable?
  approvedAt: DateTimeImmutable?
  rejectedAt: DateTimeImmutable?
  createdAt: DateTimeImmutable
  updatedAt: DateTimeImmutable
}

LoanStatus {
  DRAFT = 'draft'
  SUBMITTED = 'submitted'
  UNDER_REVIEW = 'under_review'
  APPROVED = 'approved'
  REJECTED = 'rejected'
  CANCELLED = 'cancelled'
}

LoanReview {
  id: UuidV4
  application: LoanApplication (ManyToOne)
  reviewer: User (ManyToOne)
  status: LoanStatus
  comments: text?
  reviewedAt: DateTimeImmutable
}

Document {
  id: UuidV4
  application: LoanApplication (ManyToOne)
  name: string
  originalName: string
  mimeType: string
  size: integer
  path: string
  uploadedBy: User (ManyToOne)
  uploadedAt: DateTimeImmutable
}
```

### System Configuration

```php
Setting {
  id: UuidV4
  name: string (unique)
  value: text
  type: SettingType (enum)
  category: string
  isPublic: boolean
  description: string?
  createdAt: DateTimeImmutable
  updatedAt: DateTimeImmutable
}

Language {
  id: UuidV4
  code: string (unique, ISO 639-1)
  name: string
  nativeName: string
  isActive: boolean
  isDefault: boolean
  flag: string? (emoji or path)
}

Media {
  id: UuidV4
  name: string
  originalName: string
  mimeType: string
  size: integer
  path: string
  category: string?
  alt: string?
  uploadedBy: User (ManyToOne)
  createdAt: DateTimeImmutable
}
```

## Security Architecture

### Authentication Strategy

1. **Form-based Authentication** for admin interface
2. **API Token Authentication** for REST API
3. **Remember Me** functionality for user convenience
4. **Account Lockout** protection against brute force

### Authorization Model

```php
// Role hierarchy
ROLE_SUPER_ADMIN:
  - Full system access
  - User management
  - System configuration

ROLE_ADMIN:
  - Loan management
  - User management (limited)
  - Reports and analytics

ROLE_LOAN_OFFICER:
  - Loan review and approval
  - Document management
  - Customer communication

ROLE_USER:
  - Submit loan applications
  - View own applications
  - Upload documents

ROLE_GUEST:
  - View public content
  - Register for account
```

### Security Measures

- **CSRF Protection** on all forms
- **XSS Prevention** through Twig auto-escaping
- **SQL Injection Prevention** via Doctrine ORM
- **File Upload Validation** with type and size limits
- **Rate Limiting** for API endpoints
- **HTTPS Enforcement** in production
- **Secure Headers** (CSP, HSTS, etc.)

## Database Design

### Schema Optimization

```sql
-- Indexes for performance
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_active ON users(is_active);
CREATE INDEX idx_loan_applications_status ON loan_applications(status);
CREATE INDEX idx_loan_applications_user ON loan_applications(applicant_id);
CREATE INDEX idx_loan_applications_created ON loan_applications(created_at);
CREATE INDEX idx_documents_application ON documents(application_id);
CREATE INDEX idx_settings_category ON settings(category);
CREATE INDEX idx_settings_public ON settings(is_public);

-- Full-text search indexes
CREATE FULLTEXT INDEX idx_loan_applications_search 
ON loan_applications(purpose, comments);
```

### Data Migration Strategy

1. **Version-controlled migrations** using Doctrine migrations
2. **Rollback capability** for all schema changes
3. **Data seeding** through Fixtures for development
4. **Environment-specific** configurations

## API Architecture

### RESTful API Design

```
GET    /api/loan-applications           # List applications
POST   /api/loan-applications           # Create application
GET    /api/loan-applications/{id}      # Get specific application
PUT    /api/loan-applications/{id}      # Update application
DELETE /api/loan-applications/{id}      # Delete application

POST   /api/loan-applications/{id}/documents  # Upload document
GET    /api/loan-applications/{id}/documents  # List documents

POST   /api/loan-applications/{id}/review     # Add review
GET    /api/loan-applications/{id}/reviews    # Get review history

POST   /api/auth/login                 # Authenticate
POST   /api/auth/refresh               # Refresh token
POST   /api/auth/logout                # Logout
```

### API Response Format

```json
{
  "data": {
    "id": "uuid",
    "type": "loan-application",
    "attributes": {
      "amount": 50000,
      "purpose": "Home renovation",
      "status": "under_review"
    },
    "relationships": {
      "applicant": {
        "data": { "type": "user", "id": "user-uuid" }
      }
    },
    "links": {
      "self": "/api/loan-applications/uuid"
    }
  },
  "included": [],
  "meta": {
    "timestamp": "2025-09-25T20:29:04Z",
    "version": "1.0"
  }
}
```

## Internationalization Architecture

### Translation Strategy

1. **Hierarchical Translation Keys**
   ```yaml
   # admin.fr.yaml
   loan:
     application:
       title: "Demande de prêt"
       status:
         draft: "Brouillon"
         submitted: "Soumise"
   ```

2. **Domain-Specific Translation Files**
   - `admin.{locale}.yaml` - Administrative interface
   - `messages.{locale}.yaml` - General application messages
   - `validators.{locale}.yaml` - Validation messages
   - `security.{locale}.yaml` - Authentication/authorization

3. **Fallback Strategy**
   - Primary: User's preferred language
   - Secondary: System default (French)
   - Tertiary: English (universal fallback)

### Locale Management

```php
// Automatic locale detection
class LocaleListener
{
    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        
        // 1. Check URL parameter
        if ($locale = $request->query->get('_locale')) {
            $request->setLocale($locale);
            return;
        }
        
        // 2. Check session
        if ($locale = $request->getSession()->get('_locale')) {
            $request->setLocale($locale);
            return;
        }
        
        // 3. Check user preference
        if ($user = $this->getUser()) {
            $request->setLocale($user->getPreferredLocale());
            return;
        }
        
        // 4. Check Accept-Language header
        $request->setLocale(
            $request->getPreferredLanguage(['fr', 'en', 'de', 'es'])
        );
    }
}
```

## Performance Architecture

### Caching Strategy

1. **Application Cache**
   - Doctrine Query Result Cache
   - Metadata Cache
   - Translation Cache

2. **HTTP Cache**
   - ESI (Edge Side Includes) for fragments
   - Cache headers for static assets
   - Conditional requests (ETag, Last-Modified)

3. **Session Management**
   - Redis/Memcached for production
   - File system for development

### Database Optimization

```php
// Optimized repository queries
class LoanApplicationRepository extends ServiceEntityRepository
{
    public function findWithPagination(int $page, int $limit): array
    {
        return $this->createQueryBuilder('la')
            ->select('la', 'u', 'up')  // Fetch associations
            ->leftJoin('la.applicant', 'u')
            ->leftJoin('u.profile', 'up')
            ->orderBy('la.createdAt', 'DESC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->useQueryCache(true)  // Enable query cache
            ->getResult();
    }
}
```

## Monitoring & Observability

### Logging Strategy

```php
// Structured logging
class LoanApplicationService
{
    public function __construct(
        private LoggerInterface $logger
    ) {}
    
    public function submitApplication(LoanApplication $application): void
    {
        $this->logger->info('Loan application submitted', [
            'application_id' => $application->getId()->toString(),
            'applicant_id' => $application->getApplicant()->getId()->toString(),
            'amount' => $application->getAmount(),
            'purpose' => $application->getPurpose(),
            'ip_address' => $this->requestStack->getCurrentRequest()?->getClientIp()
        ]);
    }
}
```

### Health Checks

```php
// System health monitoring
class HealthCheckController extends AbstractController
{
    #[Route('/health', name: 'health_check')]
    public function check(): JsonResponse
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
            'filesystem' => $this->checkFilesystem(),
            'memory' => $this->checkMemoryUsage()
        ];
        
        $healthy = array_reduce($checks, fn($carry, $check) => $carry && $check['status'] === 'ok', true);
        
        return new JsonResponse([
            'status' => $healthy ? 'healthy' : 'unhealthy',
            'checks' => $checks,
            'timestamp' => new \DateTimeImmutable()
        ], $healthy ? 200 : 503);
    }
}
```

## Deployment Architecture

### Container Strategy

```dockerfile
# Multi-stage Dockerfile
FROM php:8.2-fpm-alpine AS base

# Production stage
FROM base AS production
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /var/www
COPY . .
RUN composer install --no-dev --optimize-autoloader
RUN php bin/console cache:warmup

# Development stage  
FROM base AS development
RUN apk add --no-cache git
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /var/www
COPY . .
RUN composer install
```

### Environment Configuration

```yaml
# docker-compose.prod.yml
version: '3.8'
services:
  app:
    image: edgeloan:latest
    environment:
      APP_ENV: prod
      DATABASE_URL: mysql://user:pass@db:3306/edgeloan
      REDIS_URL: redis://redis:6379
    depends_on:
      - db
      - redis
  
  nginx:
    image: nginx:alpine
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - ./docker/nginx/nginx.conf:/etc/nginx/nginx.conf
      - ./docker/nginx/ssl:/etc/nginx/ssl
  
  db:
    image: mysql:8.0
    environment:
      MYSQL_ROOT_PASSWORD: ${DB_ROOT_PASSWORD}
      MYSQL_DATABASE: edgeloan
    volumes:
      - mysql_data:/var/lib/mysql
  
  redis:
    image: redis:alpine
    command: redis-server --appendonly yes
    volumes:
      - redis_data:/data
```

## Quality Assurance

### Testing Strategy

1. **Unit Tests** (80%+ coverage)
2. **Integration Tests** (API endpoints, database)
3. **Functional Tests** (user workflows)
4. **Security Tests** (authentication, authorization)
5. **Performance Tests** (load testing, profiling)

### Code Quality Tools

```yaml
# .github/workflows/ci.yml
name: CI/CD Pipeline
on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: 8.2
      
      - name: Install dependencies
        run: composer install --prefer-dist
      
      - name: Run PHPStan
        run: vendor/bin/phpstan analyse
      
      - name: Run PHP CS Fixer
        run: vendor/bin/php-cs-fixer fix --dry-run --diff
      
      - name: Run PHPUnit
        run: vendor/bin/phpunit --coverage-clover coverage.xml
      
      - name: Security Check
        run: symfony security:check
```

This architecture provides a solid foundation for the EdgeLoan application, ensuring scalability, maintainability, security, and performance while following industry best practices and Symfony conventions.
