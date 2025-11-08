# GitHub Configuration for Laravel ERP

This directory contains GitHub-specific configuration files and customizations for the Laravel ERP project.

## Contents

### `copilot-instructions.md`

Comprehensive instructions for GitHub Copilot to generate code that follows this project's:

- **Architecture patterns** (Contract-driven, Domain-driven, Event-driven)
- **Code standards** (PHP 8.2+, Laravel 12+, PSR-12)
- **Project structure** (Domain organization, naming conventions)
- **Development practices** (Repository pattern, Action pattern, Service layer)
- **API conventions** (JSON:API spec, versioning, authentication)
- **Testing standards** (Feature tests, Unit tests)
- **Security guidelines** (Authorization, input validation, audit logging)

**Usage:** This file is automatically read by GitHub Copilot in VS Code and applied to all coding assistance provided in this workspace.

## Best Practices

When working with GitHub Copilot in this project:

1. **Trust the Instructions** - Copilot has been configured with project-specific patterns
2. **Review Generated Code** - Always verify that generated code follows the architectural patterns
3. **Test Everything** - Generate corresponding tests for all new code
4. **Follow Conventions** - Stick to the naming conventions and file structure
5. **Use Contracts** - Always define interfaces before implementations

## Additional Resources

- [Product Requirements Document](/docs/prd/PRD.md) - Full project specifications
- [Phase 1 MVP](/docs/prd/PHASE-1-MVP.md) - Initial implementation details
- [Module Development Guide](/docs/prd/MODULE-DEVELOPMENT.md) - Step-by-step module creation
- [Implementation Checklist](/docs/prd/IMPLEMENTATION-CHECKLIST.md) - Task tracking

## Future Additions

This directory may include:

- **Workflows** (`.github/workflows/`) - GitHub Actions CI/CD pipelines
- **Issue Templates** (`.github/ISSUE_TEMPLATE/`) - Standardized issue formats
- **Pull Request Templates** (`.github/PULL_REQUEST_TEMPLATE.md`) - PR guidelines
- **Code Owners** (`.github/CODEOWNERS`) - Automatic PR review assignments
- **Security Policy** (`.github/SECURITY.md`) - Vulnerability reporting process
- **Contributing Guidelines** (`.github/CONTRIBUTING.md`) - Contribution process

---

**Maintained By:** Laravel ERP Development Team  
**Last Updated:** November 8, 2025
