# -------- Stage 1: Composer --------
FROM composer:2 AS composer

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-progress

# -------- Stage 2: PHP-FPM --------
FROM php:8.3-fpm-alpine

# نصب اکستنشن‌های مورد نیاز (در صورت نیاز بیشتر اضافه کن)
RUN apk add --no-cache \
    libzip-dev \
    oniguruma-dev \
    bash \
    && docker-php-ext-install pdo pdo_mysql opcache

WORKDIR /var/www

# کپی فایل‌های پروژه
COPY . .

# کپی vendor از stage قبلی
COPY --from=composer /app/vendor /var/www/vendor

# تنظیم پرمیشن
RUN chown -R www-data:www-data /var/www

# -------- Stage 3: Caddy --------
FROM caddy:2-alpine

WORKDIR /var/www

# کپی فایل‌های PHP از stage قبل
COPY --from=1 /var/www /var/www

# کپی Caddyfile
COPY Caddyfile /etc/caddy/Caddyfile

# پورت
EXPOSE 80

# اجرای Caddy
CMD ["caddy", "run", "--config", "/etc/caddy/Caddyfile"]
