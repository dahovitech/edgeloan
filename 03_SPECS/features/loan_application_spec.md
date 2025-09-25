# Loan Application Processing Specification

## Overview

This specification defines the complete loan application processing system for EdgeLoan, including application submission, document management, review workflow, approval process, and status tracking.

## Business Requirements

### User Stories
- **As a customer**, I want to submit a loan application online so that I can apply for financing conveniently
- **As a customer**, I want to upload supporting documents so that my application can be processed
- **As a customer**, I want to track my application status so that I know the progress of my request
- **As a loan officer**, I want to review applications with all supporting documents so that I can make informed decisions
- **As a loan officer**, I want to request additional information so that I can complete the review process
- **As an administrator**, I want to track application metrics so that I can monitor processing efficiency
- **As a compliance officer**, I want to audit all application activities so that I can ensure regulatory compliance

### Functional Requirements

1. **Loan Application Management**
   - Multi-step application form with validation
   - Save and continue functionality (draft mode)
   - Application submission and confirmation
   - Application modification before submission
   - Application cancellation with reason

2. **Document Management**
   - Secure document upload with validation
   - Document categorization and organization
   - Document version control
   - Document preview and download
   - Required document checklist

3. **Review Workflow**
   - Automatic assignment to loan officers
   - Review queue management
   - Multi-level approval process
   - Review comments and feedback
   - Request for additional information

4. **Status Tracking & Notifications**
   - Real-time status updates
   - Email notifications for status changes
   - Customer portal for status viewing
   - Timeline view of application progress
   - Automated reminder notifications

5. **Decision Management**
   - Approval/rejection with reasons
   - Conditional approval with terms
   - Appeal process for rejections
   - Decision audit trail
   - Integration with external credit systems

## Technical Specifications

### Database Schema

#### LoanApplication Entity
```sql
CREATE TABLE loan_applications (
    id CHAR(36) PRIMARY KEY, -- UUID
    applicant_id CHAR(36) NOT NULL,
    reference_number VARCHAR(20) UNIQUE NOT NULL, -- Auto-generated
    amount DECIMAL(12,2) NOT NULL,
    purpose TEXT NOT NULL,
    loan_term_months INT NOT NULL,
    interest_rate DECIMAL(5,2) NULL, -- Set during approval
    monthly_payment DECIMAL(10,2) NULL, -- Calculated
    status ENUM('draft', 'submitted', 'under_review', 'additional_info_requested', 
                'approved', 'rejected', 'cancelled', 'completed') DEFAULT 'draft',
    priority ENUM('low', 'normal', 'high', 'urgent') DEFAULT 'normal',
    assigned_to CHAR(36) NULL, -- Loan officer
    submitted_at DATETIME NULL,
    reviewed_at DATETIME NULL,
    decision_date DATETIME NULL,
    decision_reason TEXT NULL,
    notes TEXT NULL,
    metadata JSON NULL, -- Additional flexible data
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    
    FOREIGN KEY (applicant_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
    
    INDEX idx_loan_apps_status (status),
    INDEX idx_loan_apps_applicant (applicant_id),
    INDEX idx_loan_apps_assigned (assigned_to),
    INDEX idx_loan_apps_created (created_at),
    INDEX idx_loan_apps_reference (reference_number),
    INDEX idx_loan_apps_amount (amount)
);
```

#### LoanDocument Entity
```sql
CREATE TABLE loan_documents (
    id CHAR(36) PRIMARY KEY,
    application_id CHAR(36) NOT NULL,
    document_type ENUM('identity', 'income_proof', 'bank_statement', 
                      'employment_verification', 'property_document', 
                      'other') NOT NULL,
    name VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    file_size INT NOT NULL,
    is_required BOOLEAN DEFAULT FALSE,
    is_verified BOOLEAN DEFAULT FALSE,
    verified_by CHAR(36) NULL,
    verified_at DATETIME NULL,
    version INT DEFAULT 1,
    replaced_by CHAR(36) NULL, -- For version control
    uploaded_by CHAR(36) NOT NULL,
    uploaded_at DATETIME NOT NULL,
    
    FOREIGN KEY (application_id) REFERENCES loan_applications(id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (verified_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (replaced_by) REFERENCES loan_documents(id) ON DELETE SET NULL,
    
    INDEX idx_loan_docs_app (application_id),
    INDEX idx_loan_docs_type (document_type),
    INDEX idx_loan_docs_required (is_required),
    INDEX idx_loan_docs_verified (is_verified)
);
```

#### LoanReview Entity
```sql
CREATE TABLE loan_reviews (
    id CHAR(36) PRIMARY KEY,
    application_id CHAR(36) NOT NULL,
    reviewer_id CHAR(36) NOT NULL,
    review_type ENUM('initial', 'additional_review', 'appeal', 'final') DEFAULT 'initial',
    status ENUM('pending', 'in_progress', 'completed') DEFAULT 'pending',
    decision ENUM('approved', 'rejected', 'needs_info', 'escalated') NULL,
    score INT NULL, -- Credit score or internal rating
    risk_level ENUM('low', 'medium', 'high', 'very_high') NULL,
    comments TEXT NULL,
    conditions TEXT NULL, -- Approval conditions
    requested_documents TEXT NULL, -- Additional docs needed
    started_at DATETIME NULL,
    completed_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    
    FOREIGN KEY (application_id) REFERENCES loan_applications(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewer_id) REFERENCES users(id) ON DELETE CASCADE,
    
    INDEX idx_loan_reviews_app (application_id),
    INDEX idx_loan_reviews_reviewer (reviewer_id),
    INDEX idx_loan_reviews_status (status),
    INDEX idx_loan_reviews_decision (decision)
);
```

#### ApplicationStatusHistory Entity
```sql
CREATE TABLE application_status_history (
    id CHAR(36) PRIMARY KEY,
    application_id CHAR(36) NOT NULL,
    previous_status VARCHAR(50) NULL,
    new_status VARCHAR(50) NOT NULL,
    changed_by CHAR(36) NOT NULL,
    reason TEXT NULL,
    automated BOOLEAN DEFAULT FALSE,
    changed_at DATETIME NOT NULL,
    
    FOREIGN KEY (application_id) REFERENCES loan_applications(id) ON DELETE CASCADE,
    FOREIGN KEY (changed_by) REFERENCES users(id) ON DELETE CASCADE,
    
    INDEX idx_status_history_app (application_id),
    INDEX idx_status_history_date (changed_at)
);
```

### Entity Implementations

#### LoanApplication Entity
```php
<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Uid\UuidV4;

#[ORM\Entity(repositoryClass: LoanApplicationRepository::class)]
#[ORM\Table(name: 'loan_applications')]
#[ORM\HasLifecycleCallbacks]
class LoanApplication
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private ?UuidV4 $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $applicant = null;

    #[ORM\Column(type: 'string', length: 20, unique: true)]
    private ?string $referenceNumber = null;

    #[ORM\Column(type: 'decimal', precision: 12, scale: 2)]
    #[Assert\NotBlank(message: 'loan.amount.not_blank')]
    #[Assert\Range(
        min: 1000,
        max: 1000000,
        notInRangeMessage: 'loan.amount.range'
    )]
    private ?float $amount = null;

    #[ORM\Column(type: 'text')]
    #[Assert\NotBlank(message: 'loan.purpose.not_blank')]
    #[Assert\Length(
        max: 1000,
        maxMessage: 'loan.purpose.too_long'
    )]
    private ?string $purpose = null;

    #[ORM\Column(type: 'integer')]
    #[Assert\NotBlank(message: 'loan.term.not_blank')]
    #[Assert\Range(
        min: 6,
        max: 360,
        notInRangeMessage: 'loan.term.range'
    )]
    private ?int $loanTermMonths = null;

    #[ORM\Column(type: 'decimal', precision: 5, scale: 2, nullable: true)]
    private ?float $interestRate = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private ?float $monthlyPayment = null;

    #[ORM\Column(type: 'string', enumType: LoanStatus::class)]
    private LoanStatus $status = LoanStatus::DRAFT;

    #[ORM\Column(type: 'string', enumType: Priority::class)]
    private Priority $priority = Priority::NORMAL;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $assignedTo = null;

    #[ORM\OneToMany(targetEntity: LoanDocument::class, mappedBy: 'application', cascade: ['persist', 'remove'])]
    private Collection $documents;

    #[ORM\OneToMany(targetEntity: LoanReview::class, mappedBy: 'application', cascade: ['persist', 'remove'])]
    private Collection $reviews;

    #[ORM\OneToMany(targetEntity: ApplicationStatusHistory::class, mappedBy: 'application', cascade: ['persist', 'remove'])]
    private Collection $statusHistory;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $submittedAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $reviewedAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $decisionDate = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $decisionReason = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $metadata = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->documents = new ArrayCollection();
        $this->reviews = new ArrayCollection();
        $this->statusHistory = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    #[ORM\PrePersist]
    public function generateReferenceNumber(): void
    {
        if ($this->referenceNumber === null) {
            $this->referenceNumber = 'LA' . date('Y') . str_pad((string)random_int(1, 99999), 5, '0', STR_PAD_LEFT);
        }
    }

    #[ORM\PreUpdate]
    public function updateTimestamp(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    // Business logic methods
    public function submit(): void
    {
        if ($this->status !== LoanStatus::DRAFT) {
            throw new \InvalidArgumentException('Only draft applications can be submitted');
        }
        
        $this->status = LoanStatus::SUBMITTED;
        $this->submittedAt = new \DateTimeImmutable();
        $this->updateTimestamp();
    }

    public function assign(User $loanOfficer): void
    {
        $this->assignedTo = $loanOfficer;
        $this->updateTimestamp();
    }

    public function startReview(): void
    {
        if (!$this->canStartReview()) {
            throw new \InvalidArgumentException('Application cannot be reviewed in current state');
        }
        
        $this->status = LoanStatus::UNDER_REVIEW;
        $this->reviewedAt = new \DateTimeImmutable();
        $this->updateTimestamp();
    }

    public function approve(string $reason = null, array $conditions = []): void
    {
        $this->status = LoanStatus::APPROVED;
        $this->decisionDate = new \DateTimeImmutable();
        $this->decisionReason = $reason;
        
        if (!empty($conditions)) {
            $this->metadata = array_merge($this->metadata ?? [], ['conditions' => $conditions]);
        }
        
        $this->updateTimestamp();
    }

    public function reject(string $reason): void
    {
        $this->status = LoanStatus::REJECTED;
        $this->decisionDate = new \DateTimeImmutable();
        $this->decisionReason = $reason;
        $this->updateTimestamp();
    }

    public function requestAdditionalInformation(array $requestedDocuments = []): void
    {
        $this->status = LoanStatus::ADDITIONAL_INFO_REQUESTED;
        
        if (!empty($requestedDocuments)) {
            $this->metadata = array_merge(
                $this->metadata ?? [], 
                ['requested_documents' => $requestedDocuments]
            );
        }
        
        $this->updateTimestamp();
    }

    public function cancel(string $reason): void
    {
        $this->status = LoanStatus::CANCELLED;
        $this->decisionReason = $reason;
        $this->updateTimestamp();
    }

    // Helper methods
    public function canStartReview(): bool
    {
        return $this->status === LoanStatus::SUBMITTED && $this->assignedTo !== null;
    }

    public function canBeModified(): bool
    {
        return in_array($this->status, [LoanStatus::DRAFT, LoanStatus::ADDITIONAL_INFO_REQUESTED]);
    }

    public function hasRequiredDocuments(): bool
    {
        $requiredTypes = ['identity', 'income_proof', 'bank_statement'];
        $uploadedTypes = [];
        
        foreach ($this->documents as $document) {
            if ($document->isVerified()) {
                $uploadedTypes[] = $document->getDocumentType()->value;
            }
        }
        
        return empty(array_diff($requiredTypes, $uploadedTypes));
    }

    public function getProgressPercentage(): int
    {
        return match($this->status) {
            LoanStatus::DRAFT => 20,
            LoanStatus::SUBMITTED => 40,
            LoanStatus::UNDER_REVIEW => 60,
            LoanStatus::ADDITIONAL_INFO_REQUESTED => 50,
            LoanStatus::APPROVED, LoanStatus::REJECTED, LoanStatus::CANCELLED => 100,
            default => 0
        };
    }

    public function calculateMonthlyPayment(): float
    {
        if ($this->amount === null || $this->loanTermMonths === null || $this->interestRate === null) {
            return 0;
        }
        
        $monthlyRate = $this->interestRate / 100 / 12;
        $numPayments = $this->loanTermMonths;
        
        if ($monthlyRate == 0) {
            return $this->amount / $numPayments;
        }
        
        return $this->amount * ($monthlyRate * pow(1 + $monthlyRate, $numPayments)) / 
               (pow(1 + $monthlyRate, $numPayments) - 1);
    }

    // Getters and setters...
}
```

#### Enums
```php
<?php

namespace App\Entity\Enum;

enum LoanStatus: string
{
    case DRAFT = 'draft';
    case SUBMITTED = 'submitted';
    case UNDER_REVIEW = 'under_review';
    case ADDITIONAL_INFO_REQUESTED = 'additional_info_requested';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case CANCELLED = 'cancelled';
    case COMPLETED = 'completed';

    public function getLabel(): string
    {
        return match($this) {
            self::DRAFT => 'loan.status.draft',
            self::SUBMITTED => 'loan.status.submitted',
            self::UNDER_REVIEW => 'loan.status.under_review',
            self::ADDITIONAL_INFO_REQUESTED => 'loan.status.additional_info_requested',
            self::APPROVED => 'loan.status.approved',
            self::REJECTED => 'loan.status.rejected',
            self::CANCELLED => 'loan.status.cancelled',
            self::COMPLETED => 'loan.status.completed',
        };
    }

    public function getColor(): string
    {
        return match($this) {
            self::DRAFT => 'secondary',
            self::SUBMITTED => 'info',
            self::UNDER_REVIEW => 'warning',
            self::ADDITIONAL_INFO_REQUESTED => 'warning',
            self::APPROVED => 'success',
            self::REJECTED => 'danger',
            self::CANCELLED => 'dark',
            self::COMPLETED => 'primary',
        };
    }
}

enum DocumentType: string
{
    case IDENTITY = 'identity';
    case INCOME_PROOF = 'income_proof';
    case BANK_STATEMENT = 'bank_statement';
    case EMPLOYMENT_VERIFICATION = 'employment_verification';
    case PROPERTY_DOCUMENT = 'property_document';
    case OTHER = 'other';

    public function getLabel(): string
    {
        return match($this) {
            self::IDENTITY => 'document.type.identity',
            self::INCOME_PROOF => 'document.type.income_proof',
            self::BANK_STATEMENT => 'document.type.bank_statement',
            self::EMPLOYMENT_VERIFICATION => 'document.type.employment_verification',
            self::PROPERTY_DOCUMENT => 'document.type.property_document',
            self::OTHER => 'document.type.other',
        };
    }

    public function isRequired(): bool
    {
        return in_array($this, [self::IDENTITY, self::INCOME_PROOF, self::BANK_STATEMENT]);
    }
}
```

### API Endpoints

#### Loan Application API
```
# Customer Endpoints
GET    /api/my/loan-applications          # List customer's applications
POST   /api/my/loan-applications          # Create new application
GET    /api/my/loan-applications/{id}     # Get application details
PUT    /api/my/loan-applications/{id}     # Update draft application
POST   /api/my/loan-applications/{id}/submit # Submit application
DELETE /api/my/loan-applications/{id}     # Cancel application

# Document Management
GET    /api/my/loan-applications/{id}/documents       # List documents
POST   /api/my/loan-applications/{id}/documents       # Upload document
GET    /api/my/loan-applications/{id}/documents/{docId} # Download document
DELETE /api/my/loan-applications/{id}/documents/{docId} # Delete document

# Status Tracking
GET    /api/my/loan-applications/{id}/status-history  # Get status history
GET    /api/my/loan-applications/{id}/timeline        # Get application timeline

# Administrative Endpoints
GET    /api/admin/loan-applications       # List all applications (paginated)
GET    /api/admin/loan-applications/{id}  # Get application details
POST   /api/admin/loan-applications/{id}/assign      # Assign to loan officer
POST   /api/admin/loan-applications/{id}/approve     # Approve application
POST   /api/admin/loan-applications/{id}/reject      # Reject application
POST   /api/admin/loan-applications/{id}/request-info # Request additional info

# Review Management
GET    /api/admin/loan-applications/{id}/reviews      # Get reviews
POST   /api/admin/loan-applications/{id}/reviews      # Add review
PUT    /api/admin/reviews/{reviewId}                  # Update review

# Analytics and Reporting
GET    /api/admin/loan-applications/metrics           # Application metrics
GET    /api/admin/loan-applications/export           # Export applications
```

### User Interface Specifications

#### Customer Application Portal

1. **Application Form**
   - Multi-step wizard with progress indicator
   - Real-time validation and feedback
   - Auto-save functionality for drafts
   - Amount calculator with payment preview
   - Purpose selection with predefined options

2. **Document Upload**
   - Drag-and-drop interface
   - Document type categorization
   - Required document checklist
   - Progress indicators for uploads
   - Document preview capability

3. **Application Dashboard**
   - Application status overview
   - Timeline view of progress
   - Message center for communications
   - Document management section
   - Action buttons based on status

#### Loan Officer Interface

1. **Review Queue**
   - Prioritized application list
   - Filter and search capabilities
   - Assignment management
   - Workload distribution view
   - Quick action buttons

2. **Application Review**
   - Complete application overview
   - Document viewer with annotations
   - Decision making interface
   - Comments and feedback system
   - Credit score integration

3. **Decision Management**
   - Approval workflow with conditions
   - Rejection reason selection
   - Additional information requests
   - Decision history tracking
   - Escalation procedures

### Workflow Automation

#### Automatic Processes

1. **Application Assignment**
   - Round-robin assignment to available officers
   - Workload balancing algorithms
   - Specialty-based assignment rules
   - Priority-based queue management

2. **Notification System**
   - Status change notifications
   - Reminder emails for pending actions
   - Escalation notifications for overdue reviews
   - Customer communication automation

3. **Document Processing**
   - Automatic virus scanning
   - OCR for text extraction
   - Document type classification
   - Duplicate detection

### Integration Points

#### External Services

1. **Credit Bureau Integration**
   - Credit score retrieval
   - Credit history verification
   - Risk assessment data
   - Fraud detection services

2. **Document Verification**
   - Identity document validation
   - Income verification services
   - Employment verification
   - Bank statement analysis

3. **Communication Services**
   - Email service integration
   - SMS notification service
   - Document delivery service
   - Customer portal integration

### Security and Compliance

#### Data Protection

1. **Encryption**
   - Documents encrypted at rest
   - Secure transmission protocols
   - Database field-level encryption
   - Key management system

2. **Access Control**
   - Role-based document access
   - Audit trail for all actions
   - IP-based access restrictions
   - Multi-factor authentication

3. **Compliance**
   - GDPR data protection compliance
   - Financial services regulations
   - Data retention policies
   - Privacy by design principles

### Performance and Scalability

#### Optimization Strategies

1. **Database Optimization**
   - Query optimization with proper indexing
   - Pagination for large result sets
   - Connection pooling
   - Read replicas for reporting

2. **File Storage**
   - Cloud storage integration
   - CDN for document delivery
   - Automatic compression
   - Archival strategies

3. **Caching**
   - Application-level caching
   - Database query caching
   - Static asset caching
   - API response caching

### Testing Strategy

#### Test Coverage

1. **Unit Tests**
   - Entity business logic
   - Service layer methods
   - Form validation rules
   - Calculation accuracy

2. **Integration Tests**
   - API endpoint functionality
   - Database operations
   - File upload processes
   - External service integration

3. **End-to-End Tests**
   - Complete application workflow
   - Multi-user scenarios
   - Cross-browser compatibility
   - Mobile responsiveness

4. **Performance Tests**
   - Load testing for high volume
   - Stress testing for peak usage
   - File upload performance
   - Database query performance

This specification provides a comprehensive framework for implementing a robust, secure, and user-friendly loan application processing system that meets both business requirements and regulatory compliance standards while ensuring excellent performance and user experience.
