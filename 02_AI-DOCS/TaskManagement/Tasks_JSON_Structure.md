# Tasks JSON Structure for EdgeLoan

## Overview

This document defines the complete JSON schema for task management in the EdgeLoan project, compatible with the Agentic Coding Framework and Roo Orchestrator.

## Complete Schema Structure

```json
{
  "meta": {
    "project": {
      "name": "EdgeLoan",
      "version": "2.0.0",
      "type": "Full-Stack Web Application",
      "objective": "Advanced loan management system with multi-language support and modern UI/UX",
      "technology_stack": {
        "frontend": "Symfony Twig, Bootstrap 5, JavaScript",
        "backend": "PHP 8+, Symfony 7", 
        "database": "SQLite/MySQL (Doctrine ORM)",
        "deployment": "Docker, Docker Compose",
        "testing": "PHPUnit, Symfony Test Framework"
      },
      "quality_standards": {
        "code_coverage_target": 85,
        "static_analysis_level": 8,
        "accessibility_compliance": "WCAG AA",
        "performance_target": "<2s page load",
        "security_standard": "OWASP Top 10 compliance"
      },
      "languages_supported": ["fr", "en", "de", "es"],
      "default_language": "fr"
    },
    "workflow": {
      "current_phase": "implementation",
      "last_updated": "2025-09-25T20:29:04Z",
      "total_estimated_hours": 180,
      "hours_completed": 45,
      "completion_percentage": 25
    },
    "documentation_references": {
      "coding_standards": "/02_AI-DOCS/Conventions/coding_conventions.md",
      "design_guidelines": "/02_AI-DOCS/Conventions/design_conventions.md", 
      "ai_optimization": "/02_AI-DOCS/Documentation/AI_Coding_Agent_Optimization.md",
      "specifications_index": "/03_SPECS/documentation_index.md"
    }
  },
  "epics": [
    {
      "id": "E-001",
      "title": "Advanced User Management System",
      "description": "Enhanced user management with role-based permissions, profile management, audit trails, and security features",
      "status": "in_progress",
      "priority": "high",
      "business_value": "Core security foundation and user experience enhancement",
      "user_stories": [
        "As an administrator, I want to manage user roles and permissions so that I can control system access",
        "As a user, I want to update my profile information so that my data stays current",
        "As an administrator, I want to view user activity logs so that I can monitor system usage"
      ],
      "acceptance_criteria": [
        "Role-based access control fully implemented",
        "User profile management with validation",
        "Audit trail system operational",
        "Multi-language support for all user-facing text",
        "Security best practices implemented"
      ],
      "features": [
        "Enhanced role management with granular permissions",
        "User profile editing with image upload",
        "Activity logging and audit trails",
        "Password policy enforcement",
        "Account lockout and security measures"
      ],
      "estimated_effort": "2 weeks",
      "actual_effort": null,
      "dependencies": [],
      "risks": [
        "Security implementation complexity - Mitigated by security review at each milestone",
        "Role permission matrix complexity - Mitigated by incremental implementation"
      ],
      "tasks": ["T-001-001", "T-001-002", "T-001-003", "T-001-004", "T-001-005"],
      "created_at": "2025-09-25T20:29:04Z",
      "updated_at": "2025-09-25T20:29:04Z"
    }
  ],
  "tasks": [
    {
      "id": "T-001-001",
      "title": "Implement Enhanced Role Management System",
      "description": "Create a flexible role management system with granular permissions, allowing administrators to define custom roles and assign specific permissions to users",
      "type": "feature",
      "status": "pending",
      "priority": "high",
      "epicId": "E-001",
      "assignedTo": "AI_Coding_Agent",
      "estimatedHours": 6,
      "actualHours": 0,
      "dependencies": [],
      "tags": ["symfony", "security", "backend", "doctrine"],
      "acceptanceCriteria": [
        "Role entity created with proper Doctrine mapping",
        "Permission entity created with relationship to roles",
        "RoleRepository with methods for role management",
        "Admin interface for role creation and editing",
        "Role assignment interface for users",
        "Permission checking middleware implemented",
        "Multi-language support for role names and descriptions",
        "Database migration created and tested",
        "Comprehensive unit and integration tests"
      ],
      "technicalDetails": {
        "files": [
          "/src/Entity/Role.php",
          "/src/Entity/Permission.php", 
          "/src/Repository/RoleRepository.php",
          "/src/Controller/Admin/RoleController.php",
          "/src/Form/RoleType.php",
          "/templates/admin/role/",
          "/migrations/Version_role_management.php"
        ],
        "specifications": [
          "/03_SPECS/features/role_management_spec.md",
          "/02_AI-DOCS/Conventions/coding_conventions.md"
        ],
        "designReferences": [
          "/02_AI-DOCS/Conventions/design_conventions.md"
        ],
        "existing_integrations": [
          "Symfony Security Component",
          "Doctrine ORM",
          "Bootstrap 5 UI Framework"
        ]
      },
      "testStrategy": {
        "unitTests": "Test Role and Permission entities, repository methods, form validation",
        "integrationTests": "Test role assignment workflow, permission checking, admin interface",
        "securityTests": "Test unauthorized access prevention, privilege escalation protection",
        "manualTests": "Test role management UI across different screen sizes and languages"
      },
      "definition_of_done": [
        "Code implemented according to Symfony best practices",
        "All unit tests written and passing (minimum 90% coverage)",
        "Integration tests written and passing",
        "Security tests written and passing",
        "Code reviewed and approved",
        "Documentation updated (PHPDoc and user docs)",
        "Multi-language support implemented and tested",
        "Accessibility requirements met (WCAG AA)",
        "Performance benchmarks met (<2s response time)",
        "Database migration tested in development environment"
      ],
      "createdAt": "2025-09-25T20:29:04Z",
      "updatedAt": "2025-09-25T20:29:04Z",
      "completedAt": null,
      "notes": [],
      "timeTracking": {
        "started_at": null,
        "paused_duration": 0,
        "last_activity": null
      }
    },
    {
      "id": "T-001-002",
      "title": "Create User Profile Management Interface",
      "description": "Build a comprehensive user profile management system allowing users to update their personal information, change passwords, upload profile pictures, and manage account preferences",
      "type": "feature",
      "status": "pending",
      "priority": "medium",
      "epicId": "E-001", 
      "assignedTo": "AI_Coding_Agent",
      "estimatedHours": 4.5,
      "actualHours": 0,
      "dependencies": ["T-001-001"],
      "tags": ["symfony", "frontend", "twig", "forms", "file-upload"],
      "acceptanceCriteria": [
        "User profile form with validation implemented",
        "Profile image upload with file validation",
        "Password change functionality with security checks",
        "Account preferences management",
        "Responsive design across all breakpoints",
        "Multi-language form labels and validation messages",
        "File upload security measures implemented",
        "Form CSRF protection enabled",
        "Success and error message handling"
      ],
      "technicalDetails": {
        "files": [
          "/src/Controller/ProfileController.php",
          "/src/Form/ProfileType.php",
          "/src/Form/PasswordChangeType.php",
          "/templates/profile/",
          "/public/css/profile-styles.css",
          "/public/js/profile-interactions.js"
        ],
        "specifications": [
          "/03_SPECS/features/user_profile_spec.md"
        ],
        "designReferences": [
          "/02_AI-DOCS/Conventions/design_conventions.md",
          "/assets/mockups/profile-page-design.png"
        ],
        "security_considerations": [
          "File upload validation (type, size, security)",
          "Password strength requirements",
          "CSRF token validation",
          "Input sanitization and validation"
        ]
      },
      "testStrategy": {
        "unitTests": "Test form validation, file upload validation, password change logic",
        "integrationTests": "Test complete profile update workflow, image upload process",
        "securityTests": "Test file upload security, password validation, CSRF protection",
        "accessibilityTests": "Test form accessibility, keyboard navigation, screen reader compatibility",
        "manualTests": "Test UI responsiveness, multi-language display, user experience flow"
      },
      "definition_of_done": [
        "Profile management interface implemented",
        "All form validations working correctly",
        "File upload functionality secure and functional", 
        "Responsive design verified on all breakpoints",
        "Multi-language support implemented and tested",
        "Security measures implemented and tested",
        "Accessibility compliance verified (WCAG AA)",
        "Unit tests written and passing (minimum 85% coverage)",
        "Integration tests written and passing",
        "User documentation updated"
      ],
      "createdAt": "2025-09-25T20:29:04Z",
      "updatedAt": "2025-09-25T20:29:04Z",
      "completedAt": null,
      "notes": [],
      "timeTracking": {
        "started_at": null,
        "paused_duration": 0,
        "last_activity": null
      }
    }
  ],
  "task_templates": {
    "feature_task": {
      "type": "feature",
      "required_fields": ["title", "description", "acceptanceCriteria", "technicalDetails", "testStrategy", "definition_of_done"],
      "default_estimatedHours": 4,
      "default_priority": "medium",
      "required_tags": ["symfony"]
    },
    "bug_fix_task": {
      "type": "bug",
      "required_fields": ["title", "description", "reproduction_steps", "expected_behavior", "actual_behavior", "fix_strategy"],
      "default_estimatedHours": 2,
      "default_priority": "high",
      "required_tags": ["bug"]
    },
    "refactor_task": {
      "type": "refactor",
      "required_fields": ["title", "description", "current_state", "target_state", "benefits", "risks"],
      "default_estimatedHours": 3,
      "default_priority": "low",
      "required_tags": ["refactor"]
    },
    "documentation_task": {
      "type": "documentation",
      "required_fields": ["title", "description", "target_audience", "content_outline"],
      "default_estimatedHours": 1.5,
      "default_priority": "low",
      "required_tags": ["documentation"]
    },
    "test_task": {
      "type": "test",
      "required_fields": ["title", "description", "test_scope", "test_scenarios", "coverage_target"],
      "default_estimatedHours": 2.5,
      "default_priority": "medium",
      "required_tags": ["testing"]
    }
  },
  "validation_rules": {
    "task_id_pattern": "^T-\\d{3}-\\d{3}$",
    "epic_id_pattern": "^E-\\d{3}$",
    "status_values": ["pending", "in_progress", "review", "testing", "completed", "blocked", "cancelled"],
    "priority_values": ["critical", "high", "medium", "low"],
    "type_values": ["feature", "bug", "refactor", "documentation", "test", "maintenance"],
    "max_title_length": 100,
    "max_description_length": 1000,
    "max_estimated_hours": 8,
    "required_coverage_minimum": 80
  },
  "workflow_states": {
    "pending": {
      "description": "Task is defined but not yet started",
      "allowed_transitions": ["in_progress", "cancelled"]
    },
    "in_progress": {
      "description": "Task is actively being worked on",
      "allowed_transitions": ["review", "blocked", "cancelled"]
    },
    "review": {
      "description": "Task implementation is complete, awaiting review",
      "allowed_transitions": ["testing", "in_progress", "completed"]
    },
    "testing": {
      "description": "Task is in testing phase",
      "allowed_transitions": ["completed", "in_progress"]
    },
    "completed": {
      "description": "Task is fully completed and verified",
      "allowed_transitions": []
    },
    "blocked": {
      "description": "Task cannot proceed due to external dependency",
      "allowed_transitions": ["in_progress", "cancelled"]
    },
    "cancelled": {
      "description": "Task has been cancelled and will not be completed",
      "allowed_transitions": []
    }
  },
  "metrics": {
    "velocity": {
      "current_sprint_points": 0,
      "average_velocity": 0,
      "velocity_trend": "stable"
    },
    "quality": {
      "average_coverage": 0,
      "defect_rate": 0,
      "rework_percentage": 0
    },
    "predictability": {
      "estimation_accuracy": 0,
      "on_time_delivery": 0,
      "scope_creep_factor": 0
    }
  },
  "generated_at": "2025-09-25T20:29:04Z",
  "schema_version": "1.0.0"
}
```

## Field Descriptions

### Meta Section
- **project**: Core project information and standards
- **workflow**: Current workflow state and progress tracking
- **documentation_references**: Links to key documentation files

### Epic Structure
- **id**: Unique epic identifier (E-XXX format)
- **title**: Clear, concise epic title
- **description**: Detailed epic description and scope
- **user_stories**: Business value from user perspective
- **features**: List of specific features included in epic
- **risks**: Identified risks and mitigation strategies

### Task Structure
- **id**: Unique task identifier (T-XXX-XXX format)
- **technicalDetails**: Implementation-specific information
- **testStrategy**: Testing approach and requirements
- **definition_of_done**: Specific completion criteria
- **timeTracking**: Time management and tracking data

### Templates and Validation
- **task_templates**: Reusable templates for different task types
- **validation_rules**: Schema validation and constraints
- **workflow_states**: State machine for task progression

## Usage Guidelines

### Creating New Tasks
1. Use appropriate task template based on task type
2. Ensure all required fields are populated
3. Validate against schema rules
4. Include comprehensive acceptance criteria
5. Reference relevant documentation and specifications

### Task Dependencies
- Use task IDs to define dependencies
- Ensure dependency chain is logical and necessary
- Avoid circular dependencies
- Consider parallel execution opportunities

### Quality Assurance
- Every task must include test strategy
- Definition of done must be specific and measurable
- Technical details must reference relevant specifications
- Multi-language requirements must be explicitly addressed

### Progress Tracking
- Update task status as work progresses
- Record actual hours for estimation improvement
- Add notes for significant decisions or issues
- Maintain timeTracking for accurate metrics

This JSON structure provides comprehensive task management capabilities while ensuring alignment with the Agentic Coding Framework methodology and EdgeLoan project requirements.
