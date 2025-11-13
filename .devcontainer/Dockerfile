# Laravel ERP - Slim Developer Environment Dockerfile
# PHP 8.3 with PostgreSQL support and all project dependencies

FROM php:8.3-cli-alpine

LABEL maintainer="Laravel ERP Development Team"
LABEL description="Slim developer environment for Laravel ERP with PHP 8.3"

# Set environment variables
ENV COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_MEMORY_LIMIT=-1 \
    PHP_MEMORY_LIMIT=2G \
    LARAVEL_ENV=development

# Install system dependencies
RUN apk update && apk add --no-cache \
    # Build essentials
    build-base \
    autoconf \
    automake \
    libtool \
    linux-headers\
    # Git and version control
    git \
    openssh-client \
    # PostgreSQL client and development libraries
    postgresql-client \
    postgresql-dev \
    # System utilities
    curl \
    wget \
    zip \
    unzip \
    supervisor \
    # Required for PHP extensions
    icu-dev \
    libxml2-dev \
    openssl-dev \
    zlib-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libwebp-dev \
    oniguruma-dev \
    # Redis support
    redis \
    # Node.js and npm (for frontend tooling)
    nodejs \
    npm \
    # Text editors and development tools
    vim \
    nano \
    htop \
    && rm -rf /var/cache/apk/*

# Install PHP extensions
RUN docker-php-ext-configure gd \
    --with-freetype=/usr/include/ \
    --with-jpeg=/usr/include/ \
    --with-webp=/usr/include/

RUN docker-php-ext-install -j$(nproc) \
    # Core extensions
    pcntl \
    posix \
    sockets \
    # PostgreSQL support
    pgsql \
    pdo_pgsql \
    # Image processing
    gd \
    # JSON and XML
    json \
    simplexml \
    xml \
    xmlreader \
    xmlwriter \
    dom \
    # String operations
    mbstring \
    # Database
    pdo \
    mysqli \
    # Compression
    zip \
    # Caching
    opcache \
    # Other utilities
    iconv \
    intl \
    ctype \
    fileinfo

# Install PECL extensions
RUN pecl install redis && docker-php-ext-enable redis
RUN pecl install xdebug && docker-php-ext-enable xdebug

# Configure PHP for development
COPY --chown=www-data:www-data .devcontainer/docker/php.ini /usr/local/etc/php/conf.d/99-laravel-erp.ini

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Install Laravel Installer globally
RUN composer global require laravel/installer laravel/tinker

# Create application directory
WORKDIR /workspace

# Copy project files
COPY --chown=www-data:www-data . .

# Create necessary directories
RUN mkdir -p \
    storage/logs \
    storage/app \
    bootstrap/cache \
    node_modules

# Install Composer dependencies
RUN composer install --no-interaction --no-progress --optimize-autoloader --no-dev 2>/dev/null || composer install --no-interaction --no-progress

# Install npm dependencies (if package.json exists)
RUN if [ -f package.json ]; then npm ci --prefer-offline --no-audit; fi

# Set proper permissions
RUN chown -R www-data:www-data /workspace && \
    chmod -R 755 /workspace && \
    chmod -R 775 storage bootstrap/cache

# Expose ports
EXPOSE 8000 5432 6379

# Health check
HEALTHCHECK --interval=30s --timeout=10s --start-period=5s --retries=3 \
    CMD php -r "exit((int)!file_exists('/workspace/bootstrap/app.php'));"

# Default command
CMD ["php", "-S", "0.0.0.0:8000", "-t", "public"]
