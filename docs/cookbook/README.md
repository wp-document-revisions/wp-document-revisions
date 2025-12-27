# WP Document Revisions Cookbook

Welcome to the WP Document Revisions Cookbook! This directory contains recipes and guides for extending and integrating WP Document Revisions with other plugins and workflows.

## What's in the Cookbook?

The cookbook provides practical examples, integration guides, and code snippets that demonstrate how to:

- Extend WP Document Revisions functionality
- Integrate with third-party plugins
- Customize workflows and behavior
- Implement advanced use cases

Each recipe is self-contained and includes:
- Overview of the use case
- Step-by-step implementation instructions
- Complete, working code examples
- Configuration guidance
- Troubleshooting tips

## Available Recipes

### Plugin Integrations

- **[PublishPress Revisions Integration](publishpress-revisions-integration.md)** - Enable scheduling of document revision publications with workflow management

## Contributing Your Own Recipes

Have you created a useful integration or customization? We'd love to include it in the cookbook!

### Recipe Guidelines

When contributing a recipe, please include:

1. **Clear Use Case**: Explain what problem the recipe solves
2. **Prerequisites**: List required plugins, PHP version, etc.
3. **Complete Code**: Provide fully working, tested code
4. **Installation Instructions**: Step-by-step setup guide
5. **Configuration**: Any settings that need adjustment
6. **Usage Examples**: Show how to use the integration
7. **Troubleshooting**: Common issues and solutions
8. **Credits**: Acknowledge contributors

### Code Standards

All code in cookbook recipes should:

- Follow [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/)
- Include proper PHPDoc comments
- Use WordPress functions over native PHP where appropriate
- Implement proper security (sanitization, nonces, capability checks)
- Be compatible with the minimum WordPress/PHP versions supported by WPDR

### Submitting a Recipe

1. Fork the repository
2. Create your recipe in the `docs/cookbook/` directory
3. Follow the format of existing recipes
4. Test your code thoroughly
5. Submit a pull request with:
   - Your recipe file
   - Update to this README adding your recipe to the list
   - Clear description of what the recipe provides

## Support

For questions about cookbook recipes:

- Check the specific recipe's troubleshooting section
- Open an issue on [GitHub](https://github.com/wp-document-revisions/wp-document-revisions/issues)
- Tag your issue with "cookbook" for faster response

## License

All cookbook content and code examples are provided under the GPL v3 license, consistent with WP Document Revisions.
