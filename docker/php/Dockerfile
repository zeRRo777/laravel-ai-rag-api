FROM php:8.4-fpm AS base

WORKDIR /var/www/html

COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/

RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libpq-dev \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

RUN install-php-extensions pdo_pgsql zip redis @composer mbstring exif pcntl bcmath gd opcache intl

# === ЭТАП ДЛЯ ЛОКАЛЬНОЙ РАЗРАБОТКИ ===
FROM base AS development

RUN install-php-extensions xdebug

COPY ./docker/php/php.ini-development $PHP_INI_DIR/conf.d/99-custom.ini

RUN useradd -G www-data,root -u 1000 -d /home/devuser devuser \
    && mkdir -p /home/devuser/.composer \
    && chown -R devuser:devuser /home/devuser

USER devuser

CMD ["php-fpm"]


# === ЭТАПЫ ДЛЯ ПРОДАКШЕНА  ===
FROM base AS builder

COPY composer.json composer.lock ./

RUN composer install --no-dev --no-interaction --no-scripts --no-autoloader

COPY . .

RUN composer dump-autoload --optimize --classmap-authoritative

FROM base AS production

COPY ./docker/php/php.ini-production $PHP_INI_DIR/conf.d/99-custom.ini

USER www-data

COPY --chown=www-data:www-data --from=builder /var/www/html /var/www/html

CMD [ "php-fpm" ]
