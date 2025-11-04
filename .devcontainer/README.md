# WP Document Revisions Dev Container

This directory contains the development container configuration for WP Document Revisions. The dev container provides a complete, consistent development environment with all necessary tools pre-configured.

## What's Included

### Services
- **PHP 8.2** - Running in the main development container
- **WordPress (latest)** - Full WordPress installation
- **MariaDB 10.4** - Database server

### Development Tools
- **Composer** - PHP dependency management
- **Node.js 20** - JavaScript runtime and package management
- **WP-CLI** - WordPress command-line interface
- **PHPUnit** - PHP testing framework
- **PHPCS** - PHP CodeSniffer for code standards
- **Git** - Version control

### VS Code Extensions
The dev container automatically installs helpful extensions:
- **Intelephense** - PHP language server
- **PHP Debug** - Xdebug integration
- **PHPCS** - Code standards checking
- **PHPUnit** - Test runner integration
- **WordPress Toolbox** - WordPress-specific tools
- **ESLint & Prettier** - JavaScript formatting
- **GitLens** - Enhanced Git integration
- **Docker** - Container management
- **EditorConfig** - Consistent coding styles
- **Code Spell Checker** - Spelling assistance

## Getting Started

### Prerequisites
- [Visual Studio Code](https://code.visualstudio.com/)
- [Docker Desktop](https://www.docker.com/products/docker-desktop) or [Docker Engine](https://docs.docker.com/engine/install/)
- [Dev Containers extension](https://marketplace.visualstudio.com/items?itemName=ms-vscode-remote.remote-containers) for VS Code

### Opening the Dev Container

1. **Clone the repository** (if you haven't already):
   ```bash
   git clone https://github.com/wp-document-revisions/wp-document-revisions.git
   cd wp-document-revisions
   ```

2. **Open in VS Code**:
   ```bash
   code .
   ```

3. **Reopen in Container**:
   - Press `F1` or `Ctrl+Shift+P` (Windows/Linux) / `Cmd+Shift+P` (Mac)
   - Type "Dev Containers: Reopen in Container"
   - Select the command and wait for the container to build and start

4. **First-time setup**:
   - The container will automatically run `setup.sh` which:
     - Installs PHP dependencies via Composer
     - Installs Node.js dependencies via npm
     - Sets up the WordPress test environment
     - Creates a test database
     - Links the plugin to the WordPress installation
   - This may take 3-5 minutes on first run

### Accessing WordPress

Once the container is running:

1. **Access WordPress** at http://localhost (or the forwarded port shown in VS Code)
2. **Complete WordPress installation** with your preferred settings
3. **Activate the plugin**:
   - Go to Plugins → Installed Plugins
   - Find "WP Document Revisions"
   - Click "Activate"

### Database Access

The dev container includes MariaDB with the following credentials:

- **Host**: `db` (from within container) or `localhost:3306` (from host)
- **Development Database**: `wordpress`
- **Test Database**: `wordpress_test`
- **Username**: `wordpress`
- **Password**: `wordpress`
- **Root Password**: `mariadb`

## Development Workflow

### Running Tests

**PHP Unit Tests**:
```bash
bin/phpunit --config=phpunit9.xml
```

**JavaScript Tests**:
```bash
npm test                  # Run all tests
npm run test:watch        # Watch mode
npm run test:coverage     # Generate coverage report
```

### Code Quality

**Check code standards**:
```bash
bin/phpcs --standard=phpcs.ruleset.xml -p -s --colors *.php */**.php
```

**Auto-fix code style**:
```bash
bin/phpcbf --standard=phpcs.ruleset.xml *.php */**.php
```

### Using WP-CLI

The dev container includes WP-CLI for WordPress management:

```bash
# Check WordPress version
wp core version

# List plugins
wp plugin list

# Activate plugin
wp plugin activate wp-document-revisions

# Create test content
wp post create --post_type=document --post_title="Test Document"
```

## Troubleshooting

### Container won't start
- Ensure Docker is running
- Check that ports 80 and 3306 are not in use by other applications
- Try rebuilding the container: `Dev Containers: Rebuild Container`

### WordPress not accessible
- Check if the wordpress service is running: `docker ps`
- Verify port forwarding in VS Code's "Ports" panel
- Wait a few moments after container startup for WordPress to initialize

### Database connection issues
- Ensure the database service is healthy: `docker ps`
- Check database credentials in `docker-compose.yml`
- Verify the test database exists: `mysql -h db -u wordpress -pwordpress -e "SHOW DATABASES;"`

### Plugin not showing in WordPress
- Check that the symlink was created: `ls -la /var/www/html/wp-content/plugins/`
- Manually create link if needed:
  ```bash
  ln -s /workspaces/wp-document-revisions /var/www/html/wp-content/plugins/wp-document-revisions
  ```

### Dependencies not installed
- Manually run setup script: `bash .devcontainer/setup.sh`
- Or install individually:
  ```bash
  composer install --optimize-autoloader --prefer-dist
  npm install
  ```

## Customization

### Adding VS Code Extensions
Edit `.devcontainer/devcontainer.json` and add extension IDs to the `extensions` array:
```json
"extensions": [
    "your.extension-id"
]
```

### Changing PHP Version
Edit `.devcontainer/Dockerfile` and change the base image:
```dockerfile
FROM mcr.microsoft.com/devcontainers/php:1-8.3-bookworm
```

### Adding Development Tools
Edit `.devcontainer/Dockerfile` and add to the `apt-get install` section:
```dockerfile
RUN apt-get update && export DEBIAN_FRONTEND=noninteractive \
    && apt-get install -y \
        your-package-here \
    && apt-get clean -y && rm -rf /var/lib/apt/lists/*
```

## Architecture

### File Structure
```
.devcontainer/
├── devcontainer.json    # Main configuration
├── docker-compose.yml   # Service definitions
├── Dockerfile           # Custom container image
├── setup.sh            # Post-creation setup script
└── README.md           # This file
```

### Network Configuration
The dev container uses a custom network mode where the `app` service (your development environment) shares the network with the `wordpress` service. This allows seamless communication between all services.

### Volume Mounts
- `/workspaces` - Contains all workspace repositories
- `/var/www/html` - WordPress installation (wordpress-data volume)
- `/var/lib/mysql` - Database storage (mariadb-data volume)

## Additional Resources

- [Dev Containers Documentation](https://containers.dev/)
- [VS Code Dev Containers](https://code.visualstudio.com/docs/devcontainers/containers)
- [WP Document Revisions Documentation](https://wp-document-revisions.github.io/wp-document-revisions/)
- [WordPress Developer Resources](https://developer.wordpress.org/)
- [WP-CLI Documentation](https://wp-cli.org/)

## Contributing

If you have suggestions for improving the dev container configuration, please:
1. Open an issue on GitHub
2. Submit a pull request with your improvements
3. Include documentation for any changes

---

Happy coding! 🚀
