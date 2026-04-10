FROM mcr.microsoft.com/devcontainers/php:2-8

# Remove stale Yarn apt repo whose GPG key causes feature installs to fail.
RUN rm -f /etc/apt/sources.list.d/yarn.list /etc/apt/keyrings/yarn.gpg
