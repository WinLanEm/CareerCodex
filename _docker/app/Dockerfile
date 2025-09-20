FROM php:8.2-fpm

RUN apt-get update && apt-get install -y \
    apt-utils \
    libpq-dev \
    libpng-dev \
    libjpeg-dev \
    libzip-dev \
    libbson-dev \
    libssl-dev \
    zip unzip \
    git \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && docker-php-ext-configure gd --with-jpeg \
    && docker-php-ext-install pdo_mysql pdo_pgsql bcmath gd zip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

# Настройка PHP
COPY ./_docker/app/php.ini /usr/local/etc/php/conf.d/php.ini

# Установка Composer
ENV COMPOSER_ALLOW_SUPERUSER=1
RUN curl -sS https://getcomposer.org/installer | php -- \
    --filename=composer \
    --install-dir=/usr/local/bin

# Рабочая директория
WORKDIR /var/www

# Установка зависимостей Composer
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-scripts

# Копирование исходного кода
COPY . .

# Настройка прав
RUN mkdir -p /var/www/storage/app/public \
    /var/www/tmp/db \
    /var/www/tmp/redis \
    && chown -R www-data:www-data /var/www \
    && chmod -R 775 /var/www/storage /var/www/tmp

# Установка пользователя и установка npm-зависимостей
USER 1000


