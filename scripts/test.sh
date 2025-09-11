#!/usr/bin/env bash
set -euo pipefail

echo "==> Running PHP unit/feature tests (Pest)"
docker compose run --rm app bash -lc '
  php artisan test --colors=always'

