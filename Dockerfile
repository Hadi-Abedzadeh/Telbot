# Composer
FROM jfrog.farabixo.tech/docker-io/library/composer:2 AS vendor
WORKDIR /app

# Copy Composer files
COPY composer.json composer.lock ./

# Configure JFrog proxy as the default composer repo
RUN composer config -g repos.packagist composer https://jfrog.farabixo.tech/artifactory/api-github/

# Install dependencies via Artifactory proxy
RUN composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction \
    --prefer-dist \
    --ignore-platform-reqs \
    --no-scripts


# Build Application
FROM jfrog.farabixo.tech/general/dunglas/frankenphp:1-php8.3-bookworm-mongo-based
WORKDIR /app

# Copy source and vendor from previous stage
COPY . .
COPY --from=vendor /app/vendor /app/vendor

# Copy Laravel .env into image (must exist in build context)
COPY .env /app/.env
RUN chmod 640 /app/.env || true

# Configure npm to use JFrog Artifactory proxy (npm-new)
RUN if [ -f package.json ]; then \
    echo "registry=https://jfrog.farabixo.tech/artifactory/api/npm/npm-new/" > /root/.npmrc && \
    npm config set registry "https://jfrog.farabixo.tech/artifactory/api/npm/npm-new/" && \
    npm install --no-fund --no-audit && npm run build; \
    fi

# اطمینان از وجود پوشه‌های لازم + symlink storage + دسترسی‌ها + پاکسازی کش
RUN set -eux; \
    mkdir -p storage/framework/{cache,sessions,views} bootstrap/cache; \
    rm -rf public/storage; \
    ln -s /app/storage/app/public /app/public/storage; \
    chown -R www-data:www-data /app/storage /app/bootstrap/cache; \
    chmod -R ug+rwX /app/storage /app/bootstrap/cache; \
    php artisan storage:link; \
    php artisan optimize:clear

# Environment variables for production
ENV APP_ENV=stage \
    APP_DEBUG=false
EXPOSE 8080

# Start Laravel on port 8080
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8080"]
