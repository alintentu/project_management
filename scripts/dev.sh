#!/usr/bin/env bash
set -euo pipefail

echo "==> Starting full dev stack (web, app, node, db, redis, meilisearch, minio, mailhog)"
docker compose up -d web app node db redis meilisearch minio mailhog

echo "Web:       http://localhost:8088"
echo "Vite:      http://localhost:5173"
echo "Mailhog:   http://localhost:8025"
echo "Meili:     http://localhost:7701"
echo "MinIO:     http://localhost:9001 (console)"
