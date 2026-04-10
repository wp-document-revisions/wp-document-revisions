# WP Document Revisions Dev Container

This directory contains the development container configuration for WP Document Revisions. The dev container uses [`wp-env`](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-env/) to provide a complete WordPress development environment.

## What's Included

### Environment
- **PHP 8.3** — Development container with Composer
- **Node.js 20** — JavaScript runtime and package management
- **Docker** — Host Docker socket shared via docker-outside-of-docker for wp-env
- **GitHub CLI** — `gh` for issues, PRs, and workflows

### WordPress (via wp-env)
- **WordPress** (latest) with WP_DEBUG and SCRIPT_DEBUG enabled
- **MySQL** database managed automatically
- Plugin mounted and activated via `.wp-env.json`
- mu-plugins from `tests/e2e/mu-plugins/` mapped automatically

### VS Code Extensions
The dev container automatically installs helpful extensions for PHP, WordPress, JavaScript, and code quality.

## Getting Started

### Option 1: GitHub Codespaces (Recommended)

1. Go to the repository on GitHub
2. Click **Code** → **Codespaces** → **Create codespace on main**
3. Wait for the environment to build (~3-5 min first time)
4. Start developing — WordPress is already running with the plugin activated

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
4. Starts WordPress via `npx wp-env start`

### Accessing WordPress

- **WordPress:** http://localhost:8888 (or the forwarded port in Codespaces)
- **Admin:** http://localhost:8888/wp-admin
- **Login:** `admin` / `password`
- **Test instance:** http://localhost:8889

## Development Workflow

### Running Tests

```bash
# PHP unit tests (requires test framework setup first)
# Option 1: Use wp-env's test database
npx wp-env run tests-cli wp db create 2>/dev/null; bash script/install-wp-tests wordpress_test root '' localhost latest true
bin/phpunit --config=phpunit9.xml

# Option 2: Use local MySQL (if available)
bash script/install-wp-tests wordpress_test root '' localhost latest true
bin/phpunit --config=phpunit9.xml

# JavaScript tests
npm test

# E2E tests (wp-env already running)
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

### Managing WordPress

```bash
# wp-env wraps WP-CLI
npx wp-env run cli wp plugin list
npx wp-env run cli wp post create --post_type=document --post_title="Test"

# Stop/start/restart
npx wp-env stop
npx wp-env start
npx wp-env destroy  # full reset
```

## Troubleshooting

### Container won't start
- Ensure Docker is running
- Try rebuilding: `Dev Containers: Rebuild Container`

### WordPress not accessible
- Check wp-env status: `npx wp-env start` (idempotent)
- Verify port forwarding in the Ports panel
- Check Docker containers: `docker ps`

### wp-env issues
- Full reset: `npx wp-env destroy && npx wp-env start`
- Logs: `npx wp-env logs`

### Dependencies not installed
- Dependencies install via `updateContentCommand` (automatic)
- Manual: `composer install && npm install && npm run build:blocks`

### After Codespace wakes from sleep
- wp-env auto-restarts via `postStartCommand` — wait a moment for containers to start
- If WordPress is unresponsive: `npx wp-env start`

## Architecture

```
.devcontainer/
├── devcontainer.json    # Main configuration (build, features, lifecycle)
├── Dockerfile           # Strips stale Yarn apt repo from PHP base image
└── README.md            # This file

.wp-env.json             # wp-env configuration (plugin mapping, mu-plugins)
```

### Lifecycle

1. **`updateContentCommand`** — Runs on create and when source changes: installs Composer/npm deps, builds blocks
2. **`waitFor: updateContentCommand`** — Terminal opens only after deps are ready
3. **`postCreateCommand`** — Runs once on first create: starts WordPress via wp-env (with retry)
4. **`postStartCommand`** — Runs on every start (including Codespace wake from sleep): ensures wp-env is running
