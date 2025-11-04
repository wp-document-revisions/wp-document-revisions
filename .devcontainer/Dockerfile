FROM mcr.microsoft.com/devcontainers/php:1-8.2-bookworm

# Install MariaDB client, subversion (for WordPress tests), and other useful tools
RUN apt-get update && export DEBIAN_FRONTEND=noninteractive \
    && apt-get install -y \
        mariadb-client \
        subversion \
        less \
        git \
        unzip \
        wget \
    && apt-get clean -y && rm -rf /var/lib/apt/lists/*

# Install PHP extensions needed for WordPress
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Install WP-CLI
RUN curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar \
    && chmod +x wp-cli.phar \
    && mv wp-cli.phar /usr/local/bin/wp

# Install Composer (if not already installed)
RUN if ! command -v composer &> /dev/null; then \
        curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer; \
    fi

# Set up shell conveniences for vscode user
RUN echo 'alias ll="ls -la"' >> /home/vscode/.bashrc \
    && echo 'alias phpcs="vendor/bin/phpcs"' >> /home/vscode/.bashrc \
    && echo 'alias phpcbf="vendor/bin/phpcbf"' >> /home/vscode/.bashrc \
    && echo 'alias phpunit="vendor/bin/phpunit"' >> /home/vscode/.bashrc

