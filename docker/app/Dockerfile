FROM php:8.3-fpm-bullseye

# Install system dependencies and PHP extensions
RUN set -eux; \
    apt-get update; \
    apt-get install -y --no-install-recommends \
      git unzip libzip-dev libpq-dev libicu-dev libxml2-dev \
      libpng-dev libjpeg-dev libfreetype6-dev libwebp-dev \
      libonig-dev libssl-dev supervisor ca-certificates curl; \
    docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp; \
    docker-php-ext-install -j"$(nproc)" gd exif intl pcntl bcmath zip opcache pdo_pgsql; \
    pecl install redis; \
    docker-php-ext-enable redis; \
    rm -rf /var/lib/apt/lists/*

# Composer
ENV COMPOSER_HOME=/tmp/composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# PHP config
COPY php.ini /usr/local/etc/php/conf.d/99-project.ini

# Supervisor config
COPY supervisord.conf /etc/supervisor/supervisord.conf

WORKDIR /var/www/html

EXPOSE 9000

CMD ["supervisord", "-c", "/etc/supervisor/supervisord.conf"]

