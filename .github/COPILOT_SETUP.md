# GitHub Copilot Configuration

This document provides an overview of the GitHub Copilot configuration for the WP Document Revisions repository.

## Overview

This repository is fully configured to work with GitHub Copilot coding agent, following [GitHub's best practices for repository instructions](https://docs.github.com/en/copilot/how-tos/configure-custom-instructions/add-repository-instructions).

## Configuration Files

### 📋 Repository Instructions

1. **`.github/copilot-instructions.md`** (Primary)
   - Practical development workflow instructions
   - Build, test, and lint commands
   - Setup procedures and troubleshooting
   - Used by Copilot as the main reference

2. **`.copilot-instructions.md`** (Secondary)
   - Comprehensive architectural documentation
   - Feature information and design patterns
   - WordPress-specific conventions
   - Detailed technical reference

### 🤖 Custom Agents

Location: `.github/agents/`

Specialized agents for focused tasks:

- **documentation-expert.md** - Documentation and README maintenance
- **wordpress-php-expert.md** - WordPress plugin PHP development
- **testing-expert.md** - PHPUnit testing
- **security-expert.md** - Security audits and reviews

Usage: `@documentation-expert: Update the installation guide`

See [agents/README.md](agents/README.md) for full details.

### 📁 Path-Specific Instructions

Location: `.github/instructions/`

Context-aware guidance for different code areas:

- **docs.instructions.md** - Applies to: `docs/`, `README.md`, `readme.txt`
- **tests.instructions.md** - Applies to: `tests/`
- **includes.instructions.md** - Applies to: `includes/`, `wp-document-revisions.php`

These are automatically applied when working with files in the specified paths.

### ⚙️ Automated Setup

**`.github/workflows/copilot-setup-steps.yml`**

GitHub Actions workflow that automates the development environment setup:
- Installs PHP and MySQL
- Installs Composer dependencies
- Sets up WordPress test environment
- Verifies all tools are properly installed

Can be run manually via workflow_dispatch or called from other workflows.

## Quick Reference

### For New Contributors

1. Read `.github/copilot-instructions.md` for practical setup
2. Read `.copilot-instructions.md` for architectural context
3. Use custom agents for specialized tasks
4. Follow path-specific instructions automatically applied by Copilot

### For Maintainers

When updating Copilot configuration:

1. Keep instructions synchronized across both main files
2. Update custom agents when adding new specialized areas
3. Add path-specific instructions for new code sections
4. Test changes by using Copilot with the updated instructions

## Best Practices

### Using Custom Agents

```
@documentation-expert: Update the FAQ with new troubleshooting steps
@wordpress-php-expert: Add a filter to the document upload process
@testing-expert: Write tests for the new REST API endpoint
@security-expert: Review the file upload security implementation
```

### Working with Different Code Areas

- **Documentation** (`docs/`) - Copilot automatically applies docs.instructions.md
- **Tests** (`tests/`) - Copilot automatically applies tests.instructions.md
- **Plugin Code** (`includes/`) - Copilot automatically applies includes.instructions.md

### Development Workflow

1. Start with automated setup: Run copilot-setup-steps.yml
2. Use appropriate custom agent for your task
3. Follow path-specific instructions (automatically applied)
4. Run validation: PHPCS, PHPUnit, security checks
5. Review and commit changes

## File Structure

```
.
├── .copilot-instructions.md              # Architectural documentation
├── .github/
│   ├── copilot-instructions.md           # Main practical instructions
│   ├── COPILOT_SETUP.md                  # This file
│   ├── agents/                           # Custom specialized agents
│   │   ├── README.md
│   │   ├── documentation-expert.md
│   │   ├── wordpress-php-expert.md
│   │   ├── testing-expert.md
│   │   └── security-expert.md
│   ├── instructions/                     # Path-specific instructions
│   │   ├── README.md
│   │   ├── docs.instructions.md
│   │   ├── tests.instructions.md
│   │   └── includes.instructions.md
│   └── workflows/
│       └── copilot-setup-steps.yml       # Automated environment setup
```

## Benefits

This comprehensive Copilot configuration provides:

1. **Context-Aware Assistance** - Different guidance for different code areas
2. **Specialized Expertise** - Custom agents for specific tasks
3. **Consistent Standards** - Enforces WordPress coding standards
4. **Security Focus** - Special attention to file upload security
5. **Easy Onboarding** - Clear instructions for new contributors
6. **Automated Setup** - Quick environment configuration

## Maintenance

### Updating Instructions

When the project changes:

1. Update `.github/copilot-instructions.md` for workflow changes
2. Update `.copilot-instructions.md` for architectural changes
3. Update custom agents if new specialized areas emerge
4. Add/update path-specific instructions for new code sections
5. Keep copilot-setup-steps.yml current with dependencies

### Validation

To verify the configuration:

1. Test with Copilot on various tasks
2. Ensure instructions are clear and actionable
3. Verify custom agents work correctly
4. Check path-specific instructions apply properly
5. Validate automated setup workflow succeeds

## Learn More

- [GitHub Copilot Documentation](https://docs.github.com/en/copilot)
- [Repository Custom Instructions](https://docs.github.com/en/copilot/how-tos/configure-custom-instructions/add-repository-instructions)
- [Custom Agents Configuration](https://docs.github.com/en/copilot/reference/custom-agents-configuration)
- [WordPress Coding Standards](https://make.wordpress.org/core/handbook/best-practices/coding-standards/)

## Support

For questions about the Copilot configuration:
- Open an issue in the repository
- Refer to the documentation files listed above
- Check the GitHub Copilot documentation

---

**Last Updated**: 2025-11-04
