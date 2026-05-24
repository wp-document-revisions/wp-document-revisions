# WP Document Revisions

[![CI](https://github.com/wp-document-revisions/wp-document-revisions/actions/workflows/ci.yml/badge.svg)](https://github.com/wp-document-revisions/wp-document-revisions/actions/workflows/ci.yml) [![Crowdin](https://d322cqt584bo4o.cloudfront.net/wordpress-document-revisions/localized.svg)](https://crowdin.com/project/wordpress-document-revisions) [![PRs Welcome](https://img.shields.io/badge/PRs-welcome-brightgreen.svg?style=flat-square)](http://makeapullrequest.com)

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

- **WordPress:** 5.9 or higher
- **PHP:** 8.0 or higher
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
- **[Block Editor Support](https://wp-document-revisions.github.io/wp-document-revisions/block-editor/)** - ⚠️ Experimental Gutenberg support (opt-in)

## 🛠️ Development

### Running Tests

```bash
composer install
./vendor/bin/phpunit
```

### JavaScript Tests

```bash
npm install
npm test                  # Run all tests
npm run test:watch        # Watch mode
npm run test:coverage     # Generate coverage report
```

See [tests/js/README.md](tests/js/README.md) for detailed JavaScript testing documentation.

### Code Standards

```bash
./vendor/bin/phpcs
```

### Building Documentation

```bash
ruby script/build-readme
```

## 🤝 Contributing

We welcome contributions! Here's how you can help:

- **🐛 Report Issues** - [GitHub Issues](https://github.com/wp-document-revisions/wp-document-revisions/issues)
- **💬 Get Support** - [WordPress.org Forums](https://wordpress.org/support/plugin/wp-document-revisions/)
- **🌍 Translate** - [Crowdin Project](https://crowdin.com/project/wordpress-document-revisions)
- **💻 Code** - Fork and submit pull requests

See our [Contributing Guide](https://wp-document-revisions.github.io/wp-document-revisions/CONTRIBUTING/) for detailed information.

## 🔒 Security

Security is a top priority. This project uses GitHub's CodeQL for automated security scanning of JavaScript code. To report security vulnerabilities, please email [ben@balter.com](mailto:ben@balter.com).

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
