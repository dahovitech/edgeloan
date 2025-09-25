# Roo Task Workflow for EdgeLoan

## Overview

This document defines the interaction protocol between AI agents and the Roo Orchestrator for systematic task management and execution within the EdgeLoan project.

## Workflow Phases

### Phase 1: Task Initialization

#### AI Agent Request
```json
{
  "action": "initialize_project_tracking",
  "project": {
    "name": "EdgeLoan",
    "type": "Full-Stack Web Application", 
    "objective": "Advanced loan management system with multi-language support",
    "technology_stack": {
      "frontend": "Symfony Twig, Bootstrap 5, JavaScript",
      "backend": "PHP 8+, Symfony 7",
      "database": "SQLite/MySQL (Doctrine ORM)",
      "deployment": "Docker, Docker Compose"
    }
  },
  "context_sources": [
    "/project_session_state.json",
    "/02_AI-DOCS/Documentation/",
    "/03_SPECS/"
  ]
}
```

#### Roo Response
```json
{
  "status": "project_initialized",
  "project_id": "edgeloan-2025",
  "tracking_enabled": true,
  "next_action": "epic_creation"
}
```

### Phase 2: Epic Creation

#### Features List for Epic Generation
```json
{
  "features": [
    {
      "id": "F-001",
      "name": "Advanced User Management",
      "description": "Enhanced user management with role-based permissions, profile management, and audit trails",
      "priority": "high",
      "business_value": "Core security and user experience foundation"
    },
    {
      "id": "F-002", 
      "name": "Loan Application Processing",
      "description": "Complete loan application workflow with document upload, review process, and approval system",
      "priority": "critical",
      "business_value": "Primary business functionality"
    },
    {
      "id": "F-003",
      "name": "Document Management System",
      "description": "Secure document storage, versioning, and access control for loan-related documents",
      "priority": "high",
      "business_value": "Regulatory compliance and organization"
    },
    {
      "id": "F-004",
      "name": "Multi-Language Enhancement", 
      "description": "Complete internationalization with dynamic language switching and cultural adaptations",
      "priority": "high",
      "business_value": "Global market accessibility"
    },
    {
      "id": "F-005",
      "name": "Administrative Dashboard",
      "description": "Comprehensive admin interface with analytics, reporting, and system management",
      "priority": "medium",
      "business_value": "Operational efficiency"
    },
    {
      "id": "F-006",
      "name": "API Integration Layer",
      "description": "RESTful API for third-party integrations and mobile applications",
      "priority": "medium", 
      "business_value": "Extensibility and integration capabilities"
    }
  ]
}
```

#### Epic Creation Request
```json
{
  "action": "create_epics",
  "project_id": "edgeloan-2025",
  "features": "[features_list_above]",
  "guidelines": {
    "max_epic_duration": "3 weeks",
    "min_tasks_per_epic": 5,
    "max_tasks_per_epic": 15,
    "quality_standards": [
      "Enterprise-level code quality",
      "Complete test coverage",
      "Multi-language support",
      "Accessibility compliance",
      "Security best practices"
    ]
  }
}
```

### Phase 3: Task Breakdown

#### Task Creation Request for Epic
```json
{
  "action": "create_tasks_for_epic",
  "epic_id": "E-002", 
  "epic_title": "Loan Application Processing",
  "breakdown_requirements": {
    "max_task_duration": "4 hours",
    "include_design_requirements": true,
    "include_testing_requirements": true,
    "reference_documents": [
      "/02_AI-DOCS/Conventions/design_conventions.md",
      "/02_AI-DOCS/Documentation/AI_Coding_Agent_Optimization.md",
      "/03_SPECS/features/loan_application_spec.md"
    ]
  },
  "technical_context": {
    "existing_entities": ["User", "Setting", "Language", "Media"],
    "existing_forms": ["UserType", "LanguageType"],
    "existing_controllers": ["AdminController", "UserController"],
    "database_schema": "Current schema with users, settings, languages, media tables"
  }
}
```

#### Roo Task Generation Response
```json
{
  "epic_id": "E-002",
  "tasks_created": 12,
  "estimated_total_hours": 45,
  "critical_dependencies": [
    "T-001-003 (User Authentication) must complete before T-002-001",
    "T-002-005 (Database Schema) must complete before T-002-007"
  ],
  "design_requirements_included": true,
  "test_coverage_planned": "85%"
}
```

### Phase 4: Task Assignment and Execution

#### Next Task Request
```json
{
  "action": "get_next_task",
  "agent_type": "coding_agent",
  "agent_capabilities": [
    "symfony_development",
    "php_8_plus",
    "doctrine_orm", 
    "twig_templates",
    "bootstrap_5",
    "multi_language_support"
  ],
  "available_time": 4,
  "exclude_tasks": ["T-002-010"] // Currently blocked tasks
}
```

#### Roo Task Assignment
```json
{
  "task_assigned": {
    "id": "T-002-003",
    "title": "Implement Loan Application Entity with Validation",
    "priority": "high",
    "estimated_hours": 3.5,
    "dependencies_ready": true,
    "context_files": [
      "/src/Entity/User.php",
      "/src/Entity/Setting.php",
      "/02_AI-DOCS/Conventions/coding_conventions.md",
      "/03_SPECS/features/loan_application_spec.md"
    ],
    "acceptance_criteria": [
      "LoanApplication entity created with proper Doctrine annotations",
      "All required fields defined with appropriate validation constraints",
      "Relationships with User entity established",
      "Multi-language field support implemented",
      "Repository class created with basic query methods",
      "Unit tests written with 90%+ coverage",
      "Migration generated and tested"
    ],
    "technical_specifications": {
      "file_location": "/src/Entity/LoanApplication.php",
      "required_fields": [
        "id (auto-generated)",
        "user (relationship to User entity)",
        "amount (decimal, validated)",
        "purpose (string, translatable)",
        "status (enum: draft, submitted, under_review, approved, rejected)",
        "submitted_at (datetime, nullable)",
        "reviewed_at (datetime, nullable)",
        "created_at (datetime, auto-set)",
        "updated_at (datetime, auto-update)"
      ],
      "validation_rules": [
        "amount: positive number, max 1000000",
        "purpose: not blank, max 500 characters", 
        "status: valid enum value"
      ]
    },
    "design_requirements": "N/A - Entity class",
    "test_requirements": {
      "unit_tests": "Test entity creation, validation, relationships",
      "integration_tests": "Test database persistence and queries"
    }
  }
}
```

### Phase 5: Progress Reporting

#### Task Progress Update
```json
{
  "action": "update_task_progress",
  "task_id": "T-002-003",
  "status": "in_progress",
  "progress_percentage": 60,
  "hours_spent": 2.1,
  "work_completed": [
    "LoanApplication entity class created",
    "Basic properties and annotations added",
    "User relationship established"
  ],
  "next_steps": [
    "Add validation constraints",
    "Create repository class",
    "Write unit tests"
  ],
  "issues_encountered": [],
  "estimated_completion": "1.4 hours remaining"
}
```

#### Task Completion Report
```json
{
  "action": "complete_task", 
  "task_id": "T-002-003",
  "completion_status": "completed",
  "total_hours_spent": 3.2,
  "acceptance_criteria_met": {
    "LoanApplication entity created": true,
    "Required fields defined with validation": true,
    "User relationship established": true,
    "Multi-language support implemented": true,
    "Repository class created": true,
    "Unit tests written (90%+ coverage)": true,
    "Migration generated and tested": true
  },
  "deliverables": [
    "/src/Entity/LoanApplication.php",
    "/src/Repository/LoanApplicationRepository.php",
    "/tests/Unit/Entity/LoanApplicationTest.php",
    "/migrations/Version20250925203000.php"
  ],
  "quality_checks_passed": {
    "static_analysis": true,
    "code_style": true,
    "unit_tests": true,
    "test_coverage": "92%"
  },
  "notes": "Entity implementation includes additional helper methods for status management and date formatting for multi-language display."
}
```

### Phase 6: Complexity Analysis and Roadmap

#### Analysis Request
```json
{
  "action": "analyze_complexity",
  "scope": "project", // or "epic" or "feature"
  "analysis_type": "full", // includes time estimation, risk assessment, dependency analysis
  "considerations": [
    "Multi-language requirements impact",
    "Security and compliance requirements",
    "Integration complexity",
    "Testing overhead",
    "Deployment complexity"
  ]
}
```

#### Complexity Analysis Response
```json
{
  "complexity_analysis": {
    "overall_complexity": "medium-high",
    "total_estimated_hours": 180,
    "estimated_duration": "6-8 weeks",
    "risk_factors": [
      {
        "risk": "Multi-language complexity",
        "impact": "medium",
        "mitigation": "Standardized translation workflow and validation"
      },
      {
        "risk": "Security requirements", 
        "impact": "high",
        "mitigation": "Security review at each milestone"
      }
    ],
    "critical_path": [
      "E-001 (User Management)",
      "E-002 (Loan Processing)", 
      "E-003 (Document Management)"
    ],
    "resource_recommendations": {
      "coding_agents": 2,
      "design_agents": 1,
      "testing_focus": "Security and multi-language scenarios"
    }
  },
  "implementation_roadmap": {
    "phase_1": {
      "duration": "2 weeks",
      "epics": ["E-001"],
      "milestone": "Enhanced user management complete"
    },
    "phase_2": {
      "duration": "3 weeks", 
      "epics": ["E-002", "E-003"],
      "milestone": "Core loan processing functionality"
    },
    "phase_3": {
      "duration": "2 weeks",
      "epics": ["E-004", "E-005"],
      "milestone": "Complete system with admin interface"
    },
    "phase_4": {
      "duration": "1 week",
      "epics": ["E-006"],
      "milestone": "API layer and final integration"
    }
  }
}
```

## Quality Assurance Integration

### Pre-Task Validation
- Dependency verification
- Context file availability check
- Resource allocation confirmation
- Specification completeness review

### During-Task Monitoring
- Progress tracking against estimates
- Quality gate checkpoints
- Risk factor monitoring
- Dependency impact analysis

### Post-Task Validation
- Acceptance criteria verification
- Quality metrics assessment
- Documentation completeness check
- Impact analysis for dependent tasks

## Error Handling and Recovery

### Task Blocking Scenarios
```json
{
  "action": "report_task_blocked",
  "task_id": "T-002-007",
  "blocking_reason": "Missing dependency: T-002-003 not completed",
  "suggested_alternatives": [
    "T-002-004: UI mockups (no dependencies)",
    "T-002-008: Documentation update (no dependencies)"
  ],
  "escalation_required": false
}
```

### Task Failure Recovery
```json
{
  "action": "handle_task_failure",
  "task_id": "T-002-005",
  "failure_reason": "Technical complexity exceeded estimate",
  "recovery_strategy": "task_breakdown",
  "new_subtasks": [
    "T-002-005a: Database schema design",
    "T-002-005b: Migration implementation", 
    "T-002-005c: Testing and validation"
  ],
  "revised_estimate": 6.5 // hours
}
```

## Success Metrics

### Task-Level Metrics
- Completion time vs. estimate accuracy
- First-pass acceptance rate
- Defect detection rate
- Rework frequency

### Epic-Level Metrics  
- Epic delivery on schedule
- Feature completeness score
- Quality gate pass rate
- Business value realization

### Project-Level Metrics
- Overall velocity trend
- Predictability index
- Technical debt accumulation
- User satisfaction scores

The Roo Task Workflow ensures systematic, high-quality development while maintaining visibility, predictability, and continuous improvement throughout the EdgeLoan project lifecycle.
