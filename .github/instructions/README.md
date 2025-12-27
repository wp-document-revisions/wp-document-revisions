# Path-Specific Instructions

This directory contains path-specific instructions that provide focused guidance for different areas of the codebase.

## How Path-Specific Instructions Work

When GitHub Copilot works on files matching the `applies_to` paths, it automatically receives context-specific guidance for that area of the codebase.

## Available Path-Specific Instructions

### 📚 docs.instructions.md

**Applies to:**
- `docs/` directory
- `README.md`
- `readme.txt`

**Purpose:** Guidelines for documentation updates, maintaining consistent style, and ensuring documentation accuracy.

### 🧪 tests.instructions.md

**Applies to:**
- `tests/` directory

**Purpose:** Testing standards, PHPUnit setup, test writing patterns, and validation requirements.

### 💻 includes.instructions.md

**Applies to:**
- `includes/` directory
- `wp-document-revisions.php`

**Purpose:** WordPress plugin code standards, security guidelines, coding patterns, and validation requirements.

## Benefits

Path-specific instructions provide:

1. **Context-aware guidance** - Relevant instructions based on which files you're working with
2. **Focused standards** - Specific requirements for different code areas
3. **Specialized workflows** - Different processes for docs, tests, and code
4. **Reduced confusion** - Only see instructions relevant to your current task

## Relationship to Other Instructions

- **Repository-wide instructions**: [../copilot-instructions.md](../copilot-instructions.md)
- **Architectural documentation**: [../../.copilot-instructions.md](../../.copilot-instructions.md)
- **Custom agents**: [../agents/README.md](../agents/README.md)

## Adding New Path-Specific Instructions

To add instructions for a new path:

1. Create a new `[name].instructions.md` file in this directory
2. Add YAML frontmatter with `applies_to` paths:
   ```yaml
   ---
   applies_to:
     - path/to/directory/
     - specific-file.php
   ---
   ```
3. Write focused instructions for that path
4. Update this README with the new instructions

## Learn More

- [GitHub Copilot Documentation](https://docs.github.com/en/copilot)
- [Path-Specific Instructions Guide](https://docs.github.com/en/copilot/how-tos/configure-custom-instructions)
