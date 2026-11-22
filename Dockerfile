# syntax=docker/dockerfile:1

# ---------------------------------------------------------------------------
# Base: FrankenPHP (Caddy + PHP 8.4) with the extensions the app needs.
# ---------------------------------------------------------------------------
FROM dunglas/frankenphp:1-php8.4-alpine AS base

RUN install-php-extensions pdo_pgsql intl bcmath zip opcache pcntl \
    && apk add --no-cache libcap

WORKDIR /app

# ---------------------------------------------------------------------------
# Build: Composer dependencies + Vite assets.
# Node lives next to PHP here because the Wayfinder Vite plugin runs
# `php artisan wayfinder:generate` to create the typed route helpers.
# ---------------------------------------------------------------------------
FROM base AS build

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY --from=node:22-alpine /usr/local/bin/node /usr/local/bin/node
COPY --from=node:22-alpine /usr/local/lib/node_modules /usr/local/lib/node_modules
RUN apk add --no-cache libstdc++ \
    && ln -s /usr/local/lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction --no-progress

COPY package.json package-lock.json .npmrc ./
RUN npm ci --no-audit --no-fund

COPY . .

RUN composer dump-autoload --optimize --classmap-authoritative --no-dev \
    && php artisan package:discover --ansi \
    && npm run build \
    && rm -rf node_modules

# ---------------------------------------------------------------------------
# Runtime: lean image, non-root user, listens on $PORT (Render sets it).
# ---------------------------------------------------------------------------
FROM base AS runtime

ENV APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr \
    PORT=8080

RUN cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY docker/php.ini "$PHP_INI_DIR/conf.d/zz-app.ini"

RUN adduser -D -u 1000 app \
    # Allow binding to ports without root, and let Caddy write its own state.
    && setcap -r /usr/local/bin/frankenphp \
    && mkdir -p /data/caddy /config/caddy \
    && chown -R app:app /data/caddy /config/caddy

COPY --from=build --chown=app:app /app /app
COPY --chmod=755 docker/start.sh /usr/local/bin/start

USER app

EXPOSE 8080

CMD ["start"]
