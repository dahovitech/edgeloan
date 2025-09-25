# Documentation Index for EdgeLoan

## Project Overview

**EdgeLoan** is a modern, enterprise-grade loan management system built with Symfony 7 and designed following the Agentic Coding Framework methodology. This documentation provides comprehensive guidance for AI agents and developers working on the project.

## Framework Documentation

### Core Framework Files
- [`01_AI-RUN/00_Getting_Started.md`](../01_AI-RUN/00_Getting_Started.md) - Project initialization and overview
- [`01_AI-RUN/01_AutoPilot.md`](../01_AI-RUN/01_AutoPilot.md) - AI orchestration and workflow management
- [`project_session_state.json`](../project_session_state.json) - Current project state and progress tracking

### AI Agent Optimization
- [`02_AI-DOCS/Documentation/AI_Coding_Agent_Optimization.md`](../02_AI-DOCS/Documentation/AI_Coding_Agent_Optimization.md) - Comprehensive coding guidelines and standards
- [`02_AI-DOCS/Documentation/AI_Design_Agent_Optimization.md`](../02_AI-DOCS/Documentation/AI_Design_Agent_Optimization.md) - Design system and UI/UX guidelines
- [`02_AI-DOCS/Documentation/AI_Task_Management_Optimization.md`](../02_AI-DOCS/Documentation/AI_Task_Management_Optimization.md) - Task management and workflow optimization

### Task Management
- [`02_AI-DOCS/TaskManagement/Roo_Task_Workflow.md`](../02_AI-DOCS/TaskManagement/Roo_Task_Workflow.md) - Roo orchestrator interaction protocols
- [`02_AI-DOCS/TaskManagement/Tasks_JSON_Structure.md`](../02_AI-DOCS/TaskManagement/Tasks_JSON_Structure.md) - Complete task management schema
- [`tasks/tasks.json`](../tasks/tasks.json) - Active task definitions and progress

## Technical Documentation

### Architecture & Design
- [`02_AI-DOCS/Architecture/architecture.md`](../02_AI-DOCS/Architecture/architecture.md) - System architecture and technical specifications
- [`02_AI-DOCS/Conventions/coding_conventions.md`](../02_AI-DOCS/Conventions/coding_conventions.md) - PHP, Symfony, and JavaScript coding standards
- [`02_AI-DOCS/Conventions/design_conventions.md`](../02_AI-DOCS/Conventions/design_conventions.md) - UI/UX design system and component library

### Feature Specifications
- [`03_SPECS/features/user_management_spec.md`](features/user_management_spec.md) - User management system specifications
- [`03_SPECS/features/loan_application_spec.md`](features/loan_application_spec.md) - Loan application workflow specifications
- [`03_SPECS/features/document_management_spec.md`](features/document_management_spec.md) - Document handling specifications
- [`03_SPECS/features/multi_language_spec.md`](features/multi_language_spec.md) - Internationalization specifications
- [`03_SPECS/features/admin_dashboard_spec.md`](features/admin_dashboard_spec.md) - Administrative interface specifications
- [`03_SPECS/features/api_integration_spec.md`](features/api_integration_spec.md) - REST API specifications

## Implementation Guidelines

### Development Workflow
1. **Start with Documentation** - Always reference relevant specifications before coding
2. **Follow Conventions** - Adhere to established coding and design conventions
3. **Test-Driven Approach** - Write tests alongside implementation
4. **Multi-Language Support** - Ensure all features support FR, EN, DE, ES
5. **Accessibility First** - Meet WCAG AA compliance standards
6. **Security by Design** - Implement security measures from the start

### Quality Standards
- **Code Coverage:** Minimum 85% for new features
- **Static Analysis:** PHPStan Level 8 compliance
- **Performance:** Page load times under 2 seconds
- **Accessibility:** WCAG AA compliance
- **Security:** OWASP Top 10 compliance
- **Documentation:** Comprehensive inline and user documentation

## Current Project Status

### Completed Features ✅
- Multi-language support infrastructure (FR, EN, DE, ES)
- User management with role-based access control
- Form internationalization system
- Database foundation with proper indexing
- Administrative interface framework
- Setting management system
- Media management capabilities
- Code quality assurance pipeline

### In Progress 🔄
- Enhanced user management system
- Loan application processing workflow
- Document management system
- Comprehensive admin dashboard
- API integration layer

### Planned Features 📋
- Advanced reporting and analytics
- Email notification system
- Mobile-responsive enhancements
- Third-party integrations
- Performance optimization
- Advanced security features

## Technology Stack

### Backend
- **Framework:** Symfony 7.x
- **PHP:** 8.2+
- **Database:** MySQL 8.0 / SQLite (development)
- **ORM:** Doctrine 3.x
- **Testing:** PHPUnit 10+
- **Static Analysis:** PHPStan Level 8

### Frontend
- **Templates:** Twig 3.x
- **CSS Framework:** Bootstrap 5.3
- **Icons:** Bootstrap Icons
- **JavaScript:** Vanilla ES6+ / Stimulus
- **Build Tools:** Webpack Encore

### DevOps
- **Containerization:** Docker & Docker Compose
- **Version Control:** Git
- **CI/CD:** GitHub Actions
- **Code Quality:** PHP CS Fixer (PSR-12)

## Getting Started for AI Agents

### Essential Reading Order
1. [`01_AI-RUN/00_Getting_Started.md`](../01_AI-RUN/00_Getting_Started.md) - Project context
2. [`02_AI-DOCS/Documentation/AI_Coding_Agent_Optimization.md`](../02_AI-DOCS/Documentation/AI_Coding_Agent_Optimization.md) - Coding standards
3. [`02_AI-DOCS/Architecture/architecture.md`](../02_AI-DOCS/Architecture/architecture.md) - Technical architecture
4. [`02_AI-DOCS/TaskManagement/Roo_Task_Workflow.md`](../02_AI-DOCS/TaskManagement/Roo_Task_Workflow.md) - Task workflow
5. Relevant feature specifications in `03_SPECS/features/`

### Quick Reference
- **Project State:** [`project_session_state.json`](../project_session_state.json)
- **Active Tasks:** [`tasks/tasks.json`](../tasks/tasks.json)
- **Coding Standards:** [`02_AI-DOCS/Conventions/coding_conventions.md`](../02_AI-DOCS/Conventions/coding_conventions.md)
- **Design Guidelines:** [`02_AI-DOCS/Conventions/design_conventions.md`](../02_AI-DOCS/Conventions/design_conventions.md)

## File Organization

```
/
├── 01_AI-RUN/              # AI orchestration and workflow
├── 02_AI-DOCS/             # Technical documentation and guidelines
│   ├── Architecture/       # System architecture documentation
│   ├── Conventions/        # Coding and design standards
│   ├── Documentation/      # AI agent optimization guides
│   └── TaskManagement/     # Task workflow and structure
├── 03_SPECS/               # Feature specifications
│   └── features/           # Individual feature specifications
├── tasks/                  # Task management
│   └── tasks.json         # Active task definitions
├── src/                   # Symfony application source
├── templates/             # Twig templates
├── translations/          # Multi-language files
├── tests/                 # Test suites
└── project_session_state.json  # Project state tracking
```

## Support and Maintenance

### Documentation Updates
This documentation is living and should be updated as the project evolves. Key principles:
- Keep documentation current with code changes
- Update task progress in `project_session_state.json`
- Maintain specification accuracy in `03_SPECS/`
- Document architectural decisions and rationale

### Quality Assurance
- Regular documentation reviews
- Link validation and accuracy checks
- Consistency across all documentation files
- Accessibility of documentation format

## Contributing Guidelines

### For AI Agents
- Always reference this documentation index before starting work
- Update relevant documentation when implementing features
- Follow established conventions and patterns
- Maintain high quality standards throughout development

### For Human Developers
- Review AI-generated code against these specifications
- Provide feedback on documentation accuracy
- Ensure business requirements alignment
- Validate user experience and accessibility

---

**Last Updated:** 2025-09-25  
**Documentation Version:** 1.0.0  
**Project Phase:** Implementation  

For questions or clarifications regarding this documentation, refer to the specific files listed above or consult the project state file for current progress and context.
