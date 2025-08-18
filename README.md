# WP Document Revisions

[![CI](https://github.com/wp-document-revisions/wp-document-revisions/actions/workflows/ci.yml/badge.svg)](https://github.com/wp-document-revisions/wp-document-revisions/actions/workflows/ci.yml) [![Crowdin](https://d322cqt584bo4o.cloudfront.net/wordpress-document-revisions/localized.svg)](https://crowdin.com/project/wordpress-document-revisions) [![PRs Welcome](https://img.shields.io/badge/PRs-welcome-brightgreen.svg?style=flat-square)](http://makeapullrequest.com) [![Coverage Status](https://codecov.io/gh/wp-document-revisions/wp-document-revisions/branch/main/graphs/badge.svg?branch=main)](https://codecov.io/github/wp-document-revisions/wp-document-revisions?branch=main)

A powerful document management and version control plugin for WordPress that allows teams of any size to collaboratively edit files and manage their workflow.

## 🚀 Quick Start

**[Download from WordPress.org](https://wordpress.org/plugins/wp-document-revisions/)** | **[View Documentation](https://wp-document-revisions.github.io/wp-document-revisions/)**

## ✨ What is WP Document Revisions?

WP Document Revisions transforms WordPress into a complete document management system. It's three powerful tools in one:

1. **📁 Document Management System (DMS)** - Track, store, and organize files of any format
2. **👥 Collaboration Tool** - Enable teams to collaboratively draft, edit, and refine documents
3. **🔒 Secure File Hosting** - Publish and securely deliver files to teams, clients, or the public

## 🎯 Key Features

- **Any File Type** - Word docs, spreadsheets, PDFs, images, and more
- **Version Control** - Complete revision history with rollback capability
- **Secure Access** - Enterprise-grade security with granular permissions
- **Team Collaboration** - File check-out/check-in system prevents conflicts
- **Flexible Workflow** - Integrates with your existing processes
- **Permanent URLs** - Files get persistent links that always point to the latest version

## 📋 Requirements

- **WordPress:** 4.9 or higher
- **PHP:** 7.4 or higher
- **Tested up to:** WordPress 7.0

## 🔧 Installation

### From WordPress Admin (Recommended)

1. Go to **Plugins > Add New** in your WordPress admin
2. Search for "WP Document Revisions"
3. Click **Install Now** and then **Activate**

### Manual Installation

1. Download the latest release from [WordPress.org](https://wordpress.org/plugins/wp-document-revisions/)
2. Upload the plugin files to `/wp-content/plugins/wp-document-revisions/`
3. Activate the plugin through the **Plugins** menu in WordPress

### For Developers

```bash
git clone https://github.com/wp-document-revisions/wp-document-revisions.git
cd wp-document-revisions
composer install --no-dev
```

## 📚 Documentation

- **[Complete Documentation](https://wp-document-revisions.github.io/wp-document-revisions/)** - Full user guide
- **[Installation Guide](https://wp-document-revisions.github.io/wp-document-revisions/installation/)** - Detailed setup instructions
- **[FAQ](https://wp-document-revisions.github.io/wp-document-revisions/frequently-asked-questions/)** - Common questions answered
- **[Features Overview](https://wp-document-revisions.github.io/wp-document-revisions/features/)** - Complete feature list

## 🛠️ Development

WP Document Revisions now has both a traditional WordPress/PHP surface and a modern TypeScript/webpack build for block/editor functionality. This section describes how to work productively with both.

### 1. Environment

| Area      | Requirement                              |
| --------- | ---------------------------------------- |
| PHP       | 7.4+ (test matrix runs 7.2–8.3)          |
| WordPress | 4.9+ (tests run against legacy & latest) |
| Node.js   | 20.x (what CI uses)                      |
| Composer  | 2.x                                      |

### 2. One‑time Setup (Development)

```bash
git clone https://github.com/wp-document-revisions/wp-document-revisions.git
cd wp-document-revisions
composer install        # installs dev tools: PHPUnit, PHPCS, WPCS
npm install             # installs JS/TS toolchain
```

### 3. Everyday Commands

PHP / WordPress side:

```bash
./vendor/bin/phpcs                 # Coding standards (PHPCS / WPCS)
./vendor/bin/phpcbf                # Auto-fix coding standards where possible
./vendor/bin/phpunit --config=phpunit9.xml   # Main PHPUnit suite (modern WP)
./vendor/bin/phpunit --config=phpunit.xml    # Legacy (WP 4.9) config
```

JavaScript / TypeScript side:

```bash
npm run lint            # ESLint (with WordPress + TypeScript + Prettier)
npm run lint:fix        # ESLint with auto-fix
npm run format:check    # Prettier (non‑mutating) check (tabs per WP standard)
npm run format          # Prettier write
npm run type-check      # TypeScript noEmit project check
npm test                # Jest test suite
npm run build           # Production webpack build (outputs to dist/)
npm run dev             # Development build (unminified)
npm run watch           # Rebuild on file changes
npm run all             # lint → format (write) → type-check → test → build
```

### 4. Recommended Local Workflow

1. Create a feature branch.
2. Write/update code & tests.
3. Run `npm run all` for quick JS assurance (OR run the granular tasks you changed).
4. Run `./vendor/bin/phpunit` (and optionally the legacy config if touching backward‑compat areas).
5. Run `./vendor/bin/phpcs` / `npm run format:check` to ensure no drift.
6. Commit & push; CI mirrors these checks (tests + build now included).

### 5. Pre-commit Checklist

- [ ] PHP unit tests pass (modern + legacy if relevant)
- [ ] JS Jest tests pass
- [ ] `npm run lint` clean (only expected warnings, if any)
- [ ] `npm run format:check` no changes
- [ ] `./vendor/bin/phpcs` reports zero errors
- [ ] Built assets (webpack) still generate without warnings

### 6. Editor / Formatting Notes

Prettier is configured to use **tabs (width 4)** to align with WordPress coding standards. Avoid overriding local editor settings that convert tabs to spaces in `src/` or you will see large diffs.

### 6a. Git Hooks / Pre-commit

Husky installs a `pre-commit` hook that runs:

```
npm run precommit   # lint, prettier check (non-mutating), type-check, jest (bail on first failure)
```

If you only changed a few files and want a faster local iteration loop, you can run targeted checks manually (e.g. `eslint <file>` or `npm test -- <pattern>`). To bypass the hook in an emergency (not recommended):

```
git commit -m "msg" --no-verify
```

### 6b. EditorConfig

An `.editorconfig` is provided to help editors enforce tabs for code, spaces for YAML/JSON/Markdown, LF endings, and trimming trailing whitespace.

### 7. Building Documentation

```bash
ruby script/build-readme
```

### 8. Full Quality Gate (Single Command)

If you want an approximate local replica of what CI enforces for JS, run:

```bash
npm run all
```

Then separately run PHPCS & PHPUnit (since those are PHP-specific):

```bash
./vendor/bin/phpcs && ./vendor/bin/phpunit --config=phpunit9.xml
```

You can also run a combined cross‑stack QA script (JS + PHP) with coverage:

```bash
script/qa
```

### 9. Updating Dependencies

Use `composer update` / `npm update` sparingly and prefer focused upgrades. After upgrading:

1. Re-run all tests.
2. Rebuild blocks (`npm run build`).
3. Note any deprecations in the CHANGELOG / PR.

### 10. Troubleshooting (JS)

| Symptom                                   | Likely Cause                  | Fix                                                                         |
| ----------------------------------------- | ----------------------------- | --------------------------------------------------------------------------- |
| Massive Prettier tab errors               | Editor saved with spaces      | Re-run `npm run format` and reconfigure editor                              |
| TS version warning in ESLint              | Newer TS than parser supports | Pin TypeScript to supported range or update `@typescript-eslint/*` packages |
| Failing Jest tests referencing WP globals | Missing mocks                 | Add/update mocks in `tests/mocks/wordpress/`                                |
| Pre-commit hook is slow                   | Runs full suite               | Add lint-staged or run granular commands manually                           |

### 11. Node Version

CI currently uses Node `20.x`. The dev container shows Node `v22` installed; tooling is tested against Node 20. Use an `.nvmrc` (to be added) or `nvm use 20` for parity if you see unexpected differences.

For deeper platform details see `.github/copilot-instructions.md` inside the repo.

## 🌐 End-to-End (Playwright) Tests

Browser-level tests validate critical workflows (admin login, creating Documents, revision UI) using [Playwright](https://playwright.dev/) plus the Gutenberg `@wordpress/e2e-test-utils-playwright` helpers (available for future extension).

### Prerequisites

- Docker + docker compose
- Node.js 20.x (what CI uses)

### One-Time Setup

```bash
npm ci
npx playwright install
cp .env.example .env   # adjust values if needed
```

### Running Locally

```bash
npm run e2e:bootstrap   # starts docker compose & installs WP if needed
npm run e2e:test        # headless cross-browser tests
```

Headed / interactive mode:

```bash
npm run e2e:test:headed
```

Show last HTML report:

```bash
npm run e2e:report
```

### Environment Variables

Set in `.env` (see `.env.example`):

| Var              | Default                 | Purpose                        |
| ---------------- | ----------------------- | ------------------------------ |
| `WP_BASE_URL`    | `http://localhost:8088` | URL where docker WP is exposed |
| `WP_ADMIN_USER`  | `admin`                 | Admin user for tests           |
| `WP_ADMIN_PASS`  | `password`              | Admin password                 |
| `WP_ADMIN_EMAIL` | `admin@example.com`     | Admin email during install     |
| `WP_SITE_TITLE`  | `WPDR E2E`              | Site title on first install    |

### CI

GitHub Actions workflow `.github/workflows/e2e.yml` runs the Playwright suite on pushes & PRs to `main`, provisioning WordPress via docker compose, activating the plugin, then executing tests across Chromium / Firefox / WebKit. Artifacts include an HTML report (and traces on retry/failure in CI).

### Extending Tests

- Add new specs under `tests/e2e/*.spec.ts`
- Reuse or expand helpers in `tests/e2e/helpers/wp-utils.ts`
- Consider leveraging `@wordpress/e2e-test-utils-playwright` for editor interactions

If you add flows involving media uploads or alternative roles (e.g., author, editor) update the bootstrap script to create those users.

## 🤝 Contributing

We welcome contributions! Here's how you can help:

- **🐛 Report Issues** - [GitHub Issues](https://github.com/wp-document-revisions/wp-document-revisions/issues)
- **💬 Get Support** - [WordPress.org Forums](https://wordpress.org/support/plugin/wp-document-revisions/)
- **🌍 Translate** - [Crowdin Project](https://crowdin.com/project/wordpress-document-revisions)
- **💻 Code** - Fork and submit pull requests

See our [Contributing Guide](https://wp-document-revisions.github.io/wp-document-revisions/CONTRIBUTING/) for detailed information.

## 🔒 Security

Security is a top priority. To report security vulnerabilities, please email [ben@balter.com](mailto:ben@balter.com).

## 📄 License

This project is licensed under the GPL v3 License - see the [LICENSE](LICENSE) file for details.

## ⭐ Support the Project

If WP Document Revisions has been helpful for your team:

- ⭐ **Star this repository**
- 📝 **Leave a review** on [WordPress.org](https://wordpress.org/support/plugin/wp-document-revisions/reviews/)
- 🐦 **Share it** with your network
- 💰 **Sponsor development** via [GitHub Sponsors](https://github.com/sponsors/benbalter)

---

**Made with ❤️ by the WP Document Revisions community**
