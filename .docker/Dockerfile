# Multi-stage Dockerfile for Laravel application with FrankenPHP
# Unified FrankenPHP setup for both development and production

# ============================================
# DEVELOPMENT STAGE (FrankenPHP with full tools)
# ============================================
FROM dunglas/frankenphp:php8.3 AS development

# Install system dependencies and PHP extensions in single layer
RUN apt-get update && apt-get install -y \
    git \
    curl \
    unzip \
    libzip-dev \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    && docker-php-ext-install pdo_mysql zip bcmath mbstring exif pcntl \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Copy Composer from official image
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Copy composer files first for better caching
COPY composer.json composer.lock ./

# Install PHP dependencies
RUN composer install --prefer-dist --no-scripts --no-autoloader

# Copy application files
COPY . .

# Generate autoloader and run post-install scripts
RUN composer dump-autoload --optimize

# Set permissions
RUN chown -R www-data:www-data /app/storage /app/bootstrap/cache \
    && chmod -R 775 /app/storage /app/bootstrap/cache

# Configure FrankenPHP for development
ENV SERVER_NAME=":80"
ENV APP_ENV=local
ENV APP_DEBUG=true

# ============================================
# BUILDER STAGE (for production assets)
# ============================================
FROM development AS builder

# Install Node.js for building assets
RUN curl -fsSL https://deb.nodesource.com/setup_24.x | bash - \
    && apt-get install -y nodejs \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install Node dependencies and build assets
RUN npm ci --legacy-peer-deps && npm run build

# Reinstall Composer dependencies without dev packages for production
RUN composer install --no-dev --prefer-dist --optimize-autoloader --no-scripts

# ============================================
# PRODUCTION STAGE (FrankenPHP optimized)
# ============================================
FROM dunglas/frankenphp:php8.3-alpine AS production

# Install runtime dependencies and PHP extensions
RUN apk add --no-cache libzip libpng libxml2 oniguruma \
    && apk add --no-cache --virtual .build-deps libzip-dev libpng-dev libxml2-dev oniguruma-dev \
    && docker-php-ext-install pdo_mysql zip bcmath mbstring exif pcntl \
    && apk del .build-deps

WORKDIR /app

# Copy only necessary files from builder (excluding node_modules, tests, etc.)
COPY --from=builder /app/app /app/app
COPY --from=builder /app/bootstrap /app/bootstrap
COPY --from=builder /app/config /app/config
COPY --from=builder /app/database /app/database
COPY --from=builder /app/public /app/public
COPY --from=builder /app/resources/views /app/resources/views
COPY --from=builder /app/routes /app/routes
COPY --from=builder /app/storage /app/storage
COPY --from=builder /app/vendor /app/vendor
COPY --from=builder /app/artisan /app/artisan
COPY --from=builder /app/composer.json /app/composer.json

# Set permissions
RUN chown -R www-data:www-data /app/storage /app/bootstrap/cache \
    && chmod -R 775 /app/storage /app/bootstrap/cache

# Configure FrankenPHP for production
ENV SERVER_NAME=":80"
ENV APP_ENV=production
ENV APP_DEBUG=false

EXPOSE 80

# Start FrankenPHP server
CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile"]
