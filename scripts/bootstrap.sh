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
  set -euo pipefail; \
  if [ ! -f artisan ]; then \
    TMP_DIR=/tmp/app_$(date +%s); \
    mkdir -p "$TMP_DIR"; \
    composer create-project laravel/laravel:^11.0 "$TMP_DIR"; \
    # Preserve infra files, then copy Laravel app over, then restore infra
    mkdir -p /tmp/infra_backup; \
    for p in docker docker-compose.yml scripts .github README.md .gitignore; do \
      [ -e "$p" ] && mv "$p" /tmp/infra_backup/; \
    done; \
    cp -a "$TMP_DIR"/. .; \
    for p in docker docker-compose.yml scripts .github README.md .gitignore; do \
      [ -e "/tmp/infra_backup/$p" ] && rm -rf "$p" && mv "/tmp/infra_backup/$p" .; \
    done; \
    # Create .env from example if missing and ensure Docker defaults
    [ -f .env ] || cp .env.example .env; \
    {
      echo ""; \
      echo "# Docker Compose defaults"; \
      echo "DB_CONNECTION=pgsql"; \
      echo "DB_HOST=db"; \
      echo "DB_PORT=5432"; \
      echo "DB_DATABASE=project_management"; \
      echo "DB_USERNAME=postgres"; \
      echo "DB_PASSWORD=postgres"; \
      echo "REDIS_HOST=redis"; \
      echo "REDIS_PORT=6379"; \
      echo "REDIS_PASSWORD=null"; \
      echo "SCOUT_DRIVER=meilisearch"; \
      echo "MEILISEARCH_HOST=http://meilisearch:7700"; \
      echo "MEILISEARCH_KEY=\${MEILI_MASTER_KEY}"; \
      echo "APP_URL=http://localhost:8088"; \
    } >> .env; \
    php artisan key:generate; \
  else \
    echo "Laravel app already present, skipping create-project"; \
  fi'

echo "==> Installing core packages (permission, activitylog, medialibrary, query-builder, excel, sanctum, scout)"
docker compose run --rm app bash -lc '
  set -e; \
  composer require \
    spatie/laravel-permission \
    spatie/laravel-activitylog \
    spatie/laravel-medialibrary \
    spatie/laravel-query-builder \
    maatwebsite/excel \
    laravel/sanctum \
    laravel/scout \
    meilisearch/meilisearch-php; \
  composer require --dev nunomaduro/collision laravel/pint; \
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
