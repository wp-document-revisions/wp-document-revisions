# WP Document Revisions Dev Container

This directory contains the development container configuration for WP Document Revisions. The dev container provides a complete, consistent development environment with all necessary tools pre-configured.

## What's Included

### Services
- **PHP 8.3** — Main development container
- **WordPress 6.8** — Full WordPress installation with plugin auto-activated
- **MariaDB 10.11** — Database server with health checks

### Development Tools
- **Composer** — PHP dependency management
- **Node.js 20** — JavaScript runtime and package management
- **WP-CLI** — WordPress command-line interface
- **PHPUnit** — PHP testing framework
- **PHPCS/PHPCBF** — PHP CodeSniffer for code standards
- **PHPStan** — Static analysis
- **GitHub CLI** — `gh` for issues, PRs, and workflows
- **Git** — Version control

### VS Code Extensions
The dev container automatically installs helpful extensions:
- **Intelephense** — PHP language server
- **PHP Debug** — Xdebug integration
- **PHPCS** — Code standards checking in-editor
- **PHPStan** — Static analysis in-editor
- **PHPUnit** — Test runner integration
- **WordPress Toolbox** — WordPress-specific tools
- **ESLint** — JavaScript linting
- **GitLens** — Enhanced Git integration
- **Code Spell Checker** — Spelling assistance
- **GitHub Copilot** — AI-assisted coding

## Getting Started

### Option 1: GitHub Codespaces (Recommended)

1. Go to the repository on GitHub
2. Click **Code** → **Codespaces** → **Create codespace on main**
3. Wait for the environment to build (~3-5 min first time)
4. Start developing — WordPress is already running and the plugin is activated

### Option 2: Local Dev Container

**Prerequisites:**
- [Visual Studio Code](https://code.visualstudio.com/)
- [Docker Desktop](https://www.docker.com/products/docker-desktop) or [Docker Engine](https://docs.docker.com/engine/install/)
- [Dev Containers extension](https://marketplace.visualstudio.com/items?itemName=ms-vscode-remote.remote-containers)

**Steps:**
1. Clone the repository and open in VS Code
2. Press `F1` → **Dev Containers: Reopen in Container**
3. Wait for the container to build and setup to complete (~3-5 min first time)

### What Happens Automatically

The container setup:
1. Installs PHP dependencies via Composer
2. Installs Node.js dependencies via npm
3. Builds Gutenberg blocks (`npm run build:blocks`)
4. Installs WordPress and creates an admin account
5. Activates the WP Document Revisions plugin
6. Sets up the PHPUnit test database and framework

### Accessing WordPress

- **WordPress:** http://localhost (or the forwarded port shown in VS Code/Codespaces)
- **Admin:** http://localhost/wp-admin
- **Login:** `admin` / `password`

### Database Access

| Setting | Value |
|---------|-------|
| Host | `db` (from container) or `localhost:3306` (from host) |
| WordPress DB | `wordpress` |
| Test DB | `wordpress_test` |
| Username | `wordpress` |
| Password | `wordpress` |
| Root Password | `mariadb` |

> **Note:** These are development-only credentials. Never use simple passwords in production.

## Development Workflow

### Running Tests

```bash
# PHP unit tests
bin/phpunit --config=phpunit9.xml

# JavaScript tests
npm test

# E2E tests (requires wp-env)
npm run test:e2e
```

### Code Quality

```bash
# Check code standards
bin/phpcs --standard=phpcs.ruleset.xml -p -s --colors *.php */*.php

# Auto-fix code style
bin/phpcbf --standard=phpcs.ruleset.xml *.php */*.php

# Static analysis
bin/phpstan analyse
```

### Building Blocks

```bash
# Build Gutenberg blocks (production)
npm run build:blocks

# Watch mode (rebuilds on changes)
npx wp-scripts start --webpack-src-dir=src/blocks --output-path=build/blocks
```

### Using WP-CLI

```bash
wp --path=/var/www/html plugin list
wp --path=/var/www/html post create --post_type=document --post_title="Test Document"
```

## Troubleshooting

### Container won't start
- Ensure Docker is running
- Check that ports 80 and 3306 are not in use
- Try rebuilding: `Dev Containers: Rebuild Container`

### WordPress not accessible
- Check if services are running: `docker ps`
- Verify port forwarding in the Ports panel
- Wait a few moments after container startup

### Database connection issues
- The `db` service has a health check — it must be healthy before WordPress starts
- Verify: `MYSQL_PWD=wordpress mysql -h db -u wordpress -e "SHOW DATABASES;"`

### Plugin not showing
- Check symlink: `ls -la /var/www/html/wp-content/plugins/wp-document-revisions`
- Re-run setup: `bash .devcontainer/setup.sh`

### Dependencies not installed
- Dependencies install via `updateContentCommand` (automatic)
- Manual: `composer install && npm install && npm run build:blocks`

## Architecture

```
.devcontainer/
├── devcontainer.json    # Main configuration and lifecycle commands
├── docker-compose.yml   # Service definitions (app, wordpress, db)
├── Dockerfile           # Custom PHP 8.3 dev container image
├── setup.sh             # One-time WordPress install and plugin activation
└── README.md            # This file
```

### Lifecycle

1. **`updateContentCommand`** — Runs on create and when source changes: installs Composer/npm deps, builds blocks
2. **`postCreateCommand`** — Runs once: WordPress install, plugin activation, test DB setup
