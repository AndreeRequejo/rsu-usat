# =============================================================
# ETAPA 1: Builder - Construir assets y dependencias
# =============================================================
FROM php:8.4-fpm-alpine AS builder

RUN apk add --no-cache \
        build-base \
        libzip-dev \
        zip \
        unzip \
        git \
        curl \
        mysql-client \
        nodejs \
        npm \
        libpng-dev \
        libjpeg-turbo-dev \
        freetype-dev \
        oniguruma-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql zip bcmath gd mbstring exif

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

COPY composer.json composer.lock ./
COPY package.json package-lock.json ./

RUN composer install --no-interaction --no-plugins --no-scripts --no-dev --prefer-dist --optimize-autoloader

RUN npm ci

COPY . .

RUN npm run build

RUN rm -rf node_modules tests

# =============================================================
# ETAPA 2: Imagen Final - Solo lo necesario para producción
# =============================================================
FROM php:8.4-fpm-alpine

RUN apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS \
        libzip-dev \
        libpng-dev \
        libjpeg-turbo-dev \
        freetype-dev \
        oniguruma-dev \
    && apk add --no-cache \
        mysql-client \
        libzip \
        libpng \
        libjpeg-turbo \
        freetype \
        oniguruma \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql zip bcmath gd mbstring exif \
    && apk del .build-deps

WORKDIR /var/www

# Copiar código y vendor desde el builder
COPY --from=builder /var/www /var/www

# Crear directorios y fijar permisos como root antes de arrancar
RUN mkdir -p storage/framework/{cache,sessions,views,testing} \
        storage/logs \
        bootstrap/cache \
    && chown -R www-data:www-data /var/www \
    && chmod -R 775 /var/www/storage /var/www/bootstrap/cache

COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

HEALTHCHECK --interval=10s --timeout=5s --start-period=30s --retries=5 \
    CMD php-fpm -t || exit 1

EXPOSE 9000

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm"]