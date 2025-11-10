# GitHub Configuration for Laravel ERP

This directory contains GitHub-specific configuration files and customizations for the Laravel ERP project.

---

## File Structure

```
.github/
├── README.md                     # This file
├── copilot-instructions.md       # GitHub Copilot instructions (VSCode + GitHub.com)
├── agents/
│   └── laravel-erp-expert.md    # Custom agent for specialized tasks
└── prompts/                      # Reusable prompt templates
```

---

## Core Files

### 1. `copilot-instructions.md`

**Purpose:** Comprehensive instructions for GitHub Copilot to generate code following project conventions.

**Used By:**
- GitHub Copilot in VS Code (workspace-wide)
- GitHub Copilot on GitHub.com (PR reviews, code suggestions)

**Contains:**
- Architecture patterns (Contract-driven, Domain-driven, Event-driven)
- Code standards (PHP 8.2+, Laravel 12+, PSR-12)
- Project structure (Domain organization, naming conventions)
- Development practices (Repository, Action, Service patterns)
- Mandatory tool integration (Scout, Pest, Pint, Pulse)
- API conventions (REST, versioning, authentication)
- Testing standards (Pest v4+ syntax)
- Security guidelines (Authorization, validation, audit logging)

**Key Features:**
- ✅ Concise and focused on project-specific requirements
- ✅ Clear separation between mandatory and optional requirements
- ✅ Quick reference sections for common tasks
- ✅ Prominent links to CODING_GUIDELINES.md

### 2. `agents/laravel-erp-expert.md`

**Purpose:** Custom agent specializing in Laravel ERP development for complex or domain-specific tasks.

**Used By:**
- GitHub Copilot Agent mode (invoked explicitly)
- Advanced development scenarios requiring specialized knowledge

**Contains:**
- Role definition and responsibilities
- Step-by-step development workflow
- Pattern implementations with examples
- Common mistakes and how to avoid them
- Pre-task checklist and validation steps

**Key Features:**
- ✅ References `copilot-instructions.md` to avoid duplication
- ✅ Provides actionable workflow steps
- ✅ Focuses on "how to implement" rather than "what to implement"
- ✅ Emphasizes reading CODING_GUIDELINES.md first

### 3. Relationship Between Files

```
┌─────────────────────────────────────────────────┐
│ copilot-instructions.md                         │
│ • Comprehensive project conventions             │
│ • Used automatically by Copilot                 │
│ • Quick reference for all developers           │
└─────────────────┬───────────────────────────────┘
                  │
                  │ references
                  ▼
┌─────────────────────────────────────────────────┐
│ agents/laravel-erp-expert.md                   │
│ • Specialized workflow guidance                 │
│ • Invoked explicitly for complex tasks         │
│ • Step-by-step implementation patterns         │
└─────────────────┬───────────────────────────────┘
                  │
                  │ both reference
                  ▼
┌─────────────────────────────────────────────────┐
│ ../CODING_GUIDELINES.md                         │
│ • Detailed coding standards                     │
│ • Common mistakes and solutions                │
│ • Code review checklist                        │
└─────────────────────────────────────────────────┘
```

---

## How to Use

### For General Development (VS Code)

1. **GitHub Copilot automatically reads** `copilot-instructions.md`
2. **Before coding, read** `CODING_GUIDELINES.md` in repository root
3. **Write code** following the patterns and conventions
4. **Use Copilot suggestions** - they follow project standards

### For Complex Tasks (Agent Mode)

1. **Invoke the custom agent** via GitHub Copilot Agent mode
2. **Provide context** about the task and requirements
3. **The agent will:**
   - Read `copilot-instructions.md` for conventions
   - Read `CODING_GUIDELINES.md` for standards
   - Follow the step-by-step workflow
   - Generate code following all patterns

### For Code Reviews

GitHub Copilot on GitHub.com uses `copilot-instructions.md` to:
- Review PR code against project standards
- Suggest improvements aligned with conventions
- Identify deviations from architectural patterns

---

## Best Practices

### When Using GitHub Copilot

1. **📖 Read CODING_GUIDELINES.md first** - Contains critical standards
2. **✅ Trust the instructions** - Copilot is configured with project patterns
3. **🔍 Review generated code** - Verify architectural pattern compliance
4. **🧪 Generate tests** - All code must have Pest tests
5. **📝 Follow conventions** - Naming, structure, and patterns
6. **🔒 Use contracts** - Define interfaces before implementations

### When Copilot Gets It Wrong

If Copilot suggests code that doesn't follow project standards:

1. **Check the instructions** - Ensure `copilot-instructions.md` is up to date
2. **Provide feedback** - Correct the suggestion with proper pattern
3. **Update guidelines** - Add to CODING_GUIDELINES.md if it's a new pattern
4. **Use the agent** - Invoke `laravel-erp-expert` for complex scenarios

---

## Maintenance

### Updating Instructions

When project conventions change:

1. **Update `copilot-instructions.md`** for general conventions
2. **Update `agents/laravel-erp-expert.md`** for workflow changes
3. **Update `CODING_GUIDELINES.md`** for new coding standards
4. **Increment version numbers** in all files
5. **Test with Copilot** to ensure suggestions follow new patterns

### Version Control

- **Current Version:** 2.0.0
- **Last Updated:** November 10, 2025
- **Version Format:** MAJOR.MINOR.PATCH (Semantic Versioning)

**Version History:**
- `2.0.0` (2025-11-10) - Major restructure for clarity and consistency
- `1.0.0` (2025-11-08) - Initial comprehensive instructions

---

## Additional Resources

### Project Documentation

- **[../CODING_GUIDELINES.md](../CODING_GUIDELINES.md)** - Detailed coding standards
- **[/docs/prd/PRD.md](/docs/prd/PRD.md)** - Product requirements
- **[/docs/prd/PHASE-1-MVP.md](/docs/prd/PHASE-1-MVP.md)** - MVP specifications
- **[/docs/prd/MODULE-DEVELOPMENT.md](/docs/prd/MODULE-DEVELOPMENT.md)** - Module creation guide

### External Resources

- **[PSR-12 Standard](https://www.php-fig.org/psr/psr-12/)** - PHP coding style
- **[Laravel Documentation](https://laravel.com/docs)** - Framework reference
- **[Pest Documentation](https://pestphp.com)** - Testing framework
- **[Laravel Scout](https://laravel.com/docs/scout)** - Search integration

---

## Future Additions

Planned enhancements to this directory:

- [ ] **Workflows** (`.github/workflows/`) - CI/CD pipelines
- [ ] **Issue Templates** (`.github/ISSUE_TEMPLATE/`) - Standardized formats
- [ ] **PR Templates** (`.github/PULL_REQUEST_TEMPLATE.md`) - Review guidelines
- [ ] **Code Owners** (`.github/CODEOWNERS`) - Automatic assignments
- [ ] **Security Policy** (`.github/SECURITY.md`) - Vulnerability reporting
- [ ] **Contributing Guide** (`.github/CONTRIBUTING.md`) - Contribution process

---

## Questions or Suggestions

If you have questions about these instructions or suggestions for improvements:

1. **Open an issue** in the repository with label `documentation`
2. **Discuss in team chat** for quick clarifications
3. **Submit a PR** with proposed changes to instruction files

---

**Maintained By:** Laravel ERP Development Team  
**Version:** 2.0.0  
**Last Updated:** November 10, 2025
