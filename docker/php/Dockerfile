FROM php:8.4-fpm-alpine
# Install system dependencies & build tools
RUN apk update && apk add --no-cache \
    git \
    curl \
    libpng-dev \
    libxml2-dev \
    zip \
    unzip \
    oniguruma-dev \
    icu-dev \
    libzip-dev \
    linux-headers \
    $PHPIZE_DEPS

# Install PHP core and PECL extensions required by Laravel & JWT
RUN docker-php-ext-install \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    intl \
    opcache \
    zip

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Create non-root user matching host permissions
RUN addgroup -g 1000 laravel && adduser -u 1000 -G laravel -D laravel

USER laravel

EXPOSE 9000

CMD ["php-fpm"]
