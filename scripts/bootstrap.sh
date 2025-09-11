#!/usr/bin/env bash
set -euo pipefail

echo "==> Bootstrapping Laravel 11 + Vue (Breeze) stack"

if ! command -v docker &>/dev/null; then
  echo "Docker is required. Please install Docker and try again." >&2
  exit 1
fi

if ! command -v docker compose &>/dev/null; then
  echo "Docker Compose V2 is required (docker compose)." >&2
  exit 1
fi

# Copy env for compose
if [ ! -f .env ]; then
  cp .env.example .env
fi

echo "==> Building app image (php-fpm + supervisor)"
docker compose build app

echo "==> Starting core services (db, redis, meilisearch, minio, mailhog)"
docker compose up -d db redis meilisearch minio mailhog

echo "==> Creating Laravel 11 project (this may take a while)"
docker compose run --rm app bash -lc '
  if [ ! -f artisan ]; then \
    composer create-project laravel/laravel:^11.0 .; \
    php -r "file_exists('.env') || copy('.env.example', '.env');"; \
    php artisan key:generate; \
  else \
    echo "Laravel app already present, skipping create-project"; \
  fi'

echo "==> Installing core packages (permission, activitylog, medialibrary, query-builder, excel, sanctum, scout)"
docker compose run --rm app bash -lc '
  composer require \
    spatie/laravel-permission \
    spatie/laravel-activitylog \
    spatie/laravel-medialibrary \
    spatie/laravel-query-builder \
    maatwebsite/excel \
    laravel/sanctum \
    laravel/scout \
    meilisearch/meilisearch-php; \
  composer require --dev pestphp/pest pestphp/pest-plugin-laravel nunomaduro/collision laravel/pint; \
  php artisan vendor:publish --provider="Spatie\\Permission\\PermissionServiceProvider"; \
  php artisan migrate'

echo "==> Installing Breeze (Inertia + Vue 3 + Vite)"
docker compose run --rm app bash -lc '
  php artisan breeze:install vue; \
  php artisan storage:link'

echo "==> Installing NPM deps and building front-end"
docker compose run --rm node sh -lc '
  npm install; \
  npm run build'

echo "==> All set. Start the stack with: ./scripts/dev.sh"

