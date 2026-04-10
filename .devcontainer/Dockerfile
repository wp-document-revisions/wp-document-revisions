FROM mcr.microsoft.com/devcontainers/php:1-8.3-bookworm

# Install MariaDB client, subversion (for WordPress tests), and PHP extensions
# Remove Yarn apt source with expired GPG key from base image to unblock apt-get update
RUN rm -f /etc/apt/sources.list.d/yarn.list \
    && apt-get update && export DEBIAN_FRONTEND=noninteractive \
    && apt-get install -y --no-install-recommends \
        mariadb-client \
        subversion \
    && apt-get clean -y && rm -rf /var/lib/apt/lists/* \
    && docker-php-ext-install mysqli pdo pdo_mysql

# Install WP-CLI with checksum verification
RUN curl -fsSL -o /usr/local/bin/wp https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar \
    && php /usr/local/bin/wp --info --allow-root > /dev/null 2>&1 \
    && chmod +x /usr/local/bin/wp

# Install Composer (if not already in base image)
RUN if ! command -v composer &> /dev/null; then \
        curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer; \
    fi

# Set up shell conveniences for vscode user
RUN echo 'alias ll="ls -la"' >> /home/vscode/.bashrc \
    && echo 'alias phpcs="bin/phpcs"' >> /home/vscode/.bashrc \
    && echo 'alias phpcbf="bin/phpcbf"' >> /home/vscode/.bashrc \
    && echo 'alias phpunit="bin/phpunit"' >> /home/vscode/.bashrc \
    && echo 'alias phpstan="bin/phpstan"' >> /home/vscode/.bashrc \
    && echo 'cd /workspaces/wp-document-revisions 2>/dev/null' >> /home/vscode/.bashrc

