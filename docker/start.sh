#!/bin/sh
# Container entrypoint: prepare Laravel with the runtime environment, then serve.
set -e

# Render injects PORT at runtime; FrankenPHP's Caddyfile listens on SERVER_NAME.
# A bare ":<port>" means plain HTTP, since Render terminates TLS in front of us.
export SERVER_NAME=":${PORT:-8080}"

# Caches must be built here, not at image build time: env vars only exist now.
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

php artisan migrate --force

exec frankenphp run --config /etc/frankenphp/Caddyfile --adapter caddyfile
