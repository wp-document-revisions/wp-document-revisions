# Custom Agents for WP Document Revisions

This directory contains custom agent definitions for GitHub Copilot. These agents are specialized assistants that provide focused expertise for specific types of tasks.

## Available Agents

### 📚 documentation-expert

**Purpose**: Creating and updating documentation

**Use when:**
- Writing or updating user documentation
- Maintaining README files
- Documenting features and APIs
- Creating usage examples

**Expertise:**
- WordPress plugin documentation standards
- Markdown formatting
- User-friendly technical writing
- Consistency across documentation files

### 💻 wordpress-php-expert

**Purpose**: WordPress plugin PHP development

**Use when:**
- Modifying plugin core functionality
- Implementing WordPress hooks and filters
- Working with WordPress database
- Adding new features to the plugin

**Expertise:**
- WordPress Coding Standards (WPCS)
- PHP best practices
- WordPress plugin architecture
- Security and performance optimization

### 🧪 testing-expert

**Purpose**: PHPUnit testing for WordPress

**Use when:**
- Writing new tests
- Fixing failing tests
- Improving test coverage
- Setting up test environments

**Expertise:**
- PHPUnit with WordPress test framework
- Test-driven development (TDD)
- WordPress-specific testing patterns
- Test environment configuration

### 🔒 security-expert

**Purpose**: Security review and auditing

**Use when:**
- Reviewing code for security issues
- Auditing file upload functionality
- Checking access control implementation
- Validating input sanitization

**Expertise:**
- WordPress security best practices
- File upload security
- OWASP vulnerabilities
- Secure coding patterns

## How to Use Custom Agents

When working with GitHub Copilot, mention the agent by name in your request:

```
@documentation-expert: Update the installation guide with new setup steps
```

```
@wordpress-php-expert: Add a new filter to the document upload process
```

```
@testing-expert: Write tests for the new document revision feature
```

```
@security-expert: Review the file upload code for security issues
```

## Agent Configuration

Each agent is configured with:

- **name**: Unique identifier for the agent
- **description**: What the agent specializes in
- **tools**: Which tools the agent can use
- **instructions**: Detailed guidance and constraints

## Best Practices

1. **Choose the right agent** for your task to get the most relevant expertise
2. **Be specific** in your requests to get better results
3. **Review agent suggestions** before implementing
4. **Combine agents** for complex tasks that span multiple areas
5. **Provide context** about what you're trying to accomplish

## Contributing

When adding new agents:

1. Create a new `.md` file in this directory
2. Follow the YAML frontmatter format
3. Provide clear instructions and constraints
4. List relevant tools the agent should use
5. Update this README with the new agent

## Learn More

- [GitHub Copilot Custom Agents Documentation](https://docs.github.com/en/copilot/reference/custom-agents-configuration)
- [WP Document Revisions Main Instructions](../copilot-instructions.md)
- [Architectural Documentation](../../.copilot-instructions.md)
