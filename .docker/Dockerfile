# =============================================================================
# Highly Optimized Multi-Stage Dockerfile for Laravel 12 + FrankenPHP
# Optimizations: Build speed, Security, Disk space, Runtime performance
# =============================================================================

# -----------------------------------------------------------------------------
# BASE: Common dependencies for all stages
# -----------------------------------------------------------------------------
FROM dunglas/frankenphp:php8.4-alpine AS base

# Install only runtime dependencies
RUN apk add --no-cache \
    libzip \
    libpng \
    libxml2 \
    oniguruma \
    icu-libs \
    && rm -rf /var/cache/apk/*

# Install PHP extensions (one-time build)
RUN apk add --no-cache --virtual .build-deps \
    libzip-dev \
    libpng-dev \
    libxml2-dev \
    oniguruma-dev \
    icu-dev \
    linux-headers \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        zip \
        bcmath \
        mbstring \
        exif \
        pcntl \
        intl \
        opcache \
    && apk del .build-deps \
    && rm -rf /tmp/* /var/cache/apk/*

WORKDIR /app

# -----------------------------------------------------------------------------
# COMPOSER DEPENDENCIES: Cached layer for PHP dependencies
# -----------------------------------------------------------------------------
FROM base AS composer-deps

COPY --from=composer:2.9 /usr/bin/composer /usr/bin/composer

COPY composer.json composer.lock ./

# Install dependencies with optimal flags and cache mounting
RUN --mount=type=cache,target=/root/.composer,sharing=locked \
    composer install \
        --no-dev \
        --no-scripts \
        --no-autoloader \
        --no-interaction \
        --no-progress \
        --prefer-dist \
        --optimize-autoloader

# -----------------------------------------------------------------------------
# NODE BUILD: Build frontend assets
# -----------------------------------------------------------------------------
FROM node:24-alpine AS node-builder

WORKDIR /app

RUN npm install -g npm@latest

COPY package.json package-lock.json ./

# Install node dependencies with cache
RUN --mount=type=cache,target=/root/.npm,sharing=locked \
    npm ci --legacy-peer-deps --no-audit --prefer-offline

# Copy only necessary source files for building
COPY resources ./resources
COPY public ./public
COPY vite.config.js postcss.config.js tailwind.config.js jsconfig.json ./

# Build production assets
RUN npm run build && \
    rm -rf node_modules

# -----------------------------------------------------------------------------
# PRODUCTION: Minimal runtime image
# -----------------------------------------------------------------------------
FROM base AS production

# Copy Composer dependencies
COPY --from=composer-deps --chown=www-data:www-data /app/vendor /app/vendor

# Copy application code (excluding what's in .dockerignore)
COPY --chown=www-data:www-data ./app /app/app
COPY --chown=www-data:www-data ./bootstrap /app/bootstrap
COPY --chown=www-data:www-data ./config /app/config
COPY --chown=www-data:www-data ./database/migrations /app/database/migrations
COPY --chown=www-data:www-data ./database/factories /app/database/factories
COPY --chown=www-data:www-data ./database/seeders /app/database/seeders
COPY --chown=www-data:www-data ./public /app/public
COPY --chown=www-data:www-data ./resources/views /app/resources/views
COPY --chown=www-data:www-data ./routes /app/routes
COPY --chown=www-data:www-data ./storage /app/storage
COPY --chown=www-data:www-data ./artisan /app/artisan
COPY --chown=www-data:www-data ./composer.json ./composer.lock /app/

# Copy built frontend assets
COPY --from=node-builder --chown=www-data:www-data /app/public/build /app/public/build

# Generate optimized autoloader
RUN composer dump-autoload --optimize --classmap-authoritative --no-dev

# Create necessary directories and set permissions (security hardened)
RUN mkdir -p storage/framework/{sessions,views,cache} \
    storage/logs \
    bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache \
    && find storage -type f -exec chmod 664 {} \; \
    && find storage -type d -exec chmod 775 {} \;

# Production PHP configuration
COPY .docker/php.production.ini /usr/local/etc/php/conf.d/zzz-custom.ini

# Production Caddyfile
COPY .docker/Caddyfile.production /etc/caddy/Caddyfile

# Switch to non-root user (security)
USER www-data

# Production environment defaults
ENV APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stack \
    LOG_LEVEL=error \
    SERVER_NAME=":80"

# Health check (optimized)
HEALTHCHECK --interval=30s --timeout=3s --start-period=40s --retries=3 \
    CMD ["frankenphp", "php-cli", "/app/artisan", "health:ping"]

EXPOSE 80 443

# Start FrankenPHP
CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile"]

# -----------------------------------------------------------------------------
# DEVELOPMENT: Full tooling for local development
# -----------------------------------------------------------------------------
FROM dunglas/frankenphp:php8.4 AS development

# Install dev dependencies and tools
RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    curl \
    unzip \
    libzip-dev \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libicu-dev \
    vim \
    && curl -fsSL https://deb.nodesource.com/setup_24.x | bash - \
    && apt-get install -y --no-install-recommends nodejs \
    && npm install -g npm@latest \
    && docker-php-ext-install -j$(nproc) pdo_mysql zip bcmath mbstring exif pcntl intl opcache \
    && pecl install xdebug-3.4.0 \
    && docker-php-ext-enable xdebug \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

# Install Composer
COPY --from=composer:2.9 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Development PHP configuration
COPY .docker/php.development.ini /usr/local/etc/php/conf.d/zzz-custom.ini

# Development Caddyfile
COPY .docker/Caddyfile /etc/frankenphp/Caddyfile

# Development environment defaults
ENV APP_ENV=local \
    APP_DEBUG=true \
    LOG_LEVEL=debug \
    SERVER_NAME=":80"

# Don't switch user in dev for easier file permissions
EXPOSE 80 443

CMD ["frankenphp", "run", "--config", "/etc/frankenphp/Caddyfile"]
