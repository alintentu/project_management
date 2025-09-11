# Project Management MVP – Laravel 11 + Vue 3

Stack minimal viabil (MVP) pregătit pentru dezvoltare rapidă cu Docker.

## Ce include

- Backend: Laravel 11 (PHP 8.3, FPM)
- Frontend: Vue 3 + Vite (Breeze/Inertia)
- DB & Cache: PostgreSQL 16, Redis 7
- Căutare: Meilisearch
- Fișiere: MinIO (S3 compatibil)
- Mail: MailHog (dev)
- Web: Nginx
- Queue & Scheduler: Supervisor în containerul `app`
- CI: workflow GitHub Actions de bază

## Servicii (Docker Compose)

- `web` (nginx) – expus la `http://localhost:8088`
- `app` (php-fpm) – rulează și queue + scheduler
- `node` (vite dev server) – `http://localhost:5173`
- `db` (postgres) – port `5432`
- `redis` – port `6379`
- `meilisearch` – `http://localhost:7700`
- `minio` – API `:9000`, consola `http://localhost:9001`
- `mailhog` – UI la `http://localhost:8025`

## Bootstrap (prima rulare)

1) Prerechizite: Docker Desktop / Docker Engine + Docker Compose v2
2) Copiază variabilele implicite: `cp .env.example .env`
3) Rulează scriptul de bootstrap:

   - Linux/macOS: `bash scripts/bootstrap.sh`

Scriptul va:

- Porni Postgres/Redis/Meilisearch/MinIO/MailHog
- Crea proiectul Laravel 11 în directorul curent
- Instala pachetele cheie: spatie/permission, activitylog, medialibrary, query-builder, maatwebsite/excel, sanctum, scout (+ meilisearch)
- Instala Breeze (Inertia + Vue 3)
- Construi frontend-ul cu Vite

După bootstrap:

- Pornește stack-ul de dev: `bash scripts/dev.sh`
- Aplică migrările (dacă ai sărit în script): `docker compose run --rm app php artisan migrate`

## Configurări Laravel recomandate

- `.env` (Laravel) – setări pentru conexiuni (PG, Redis, Meili, S3/MinIO):
  - `DB_HOST=db`, `DB_PORT=5432`
  - `REDIS_HOST=redis`
  - `SCOUT_DRIVER=meilisearch`, `MEILISEARCH_HOST=http://meilisearch:7700`, `MEILISEARCH_KEY=${MEILI_MASTER_KEY}`
  - S3/MinIO: `FILESYSTEM_DISK=s3`, `AWS_ENDPOINT=http://minio:9000`, `AWS_USE_PATH_STYLE_ENDPOINT=true`, user/pass din `MINIO_*`

## Pachete Laravel utile (a se instala pe parcurs)

- RBAC: `spatie/laravel-permission`
- Audit trail: `spatie/laravel-activitylog`
- Media: `spatie/laravel-medialibrary`
- Query builder: `spatie/laravel-query-builder`
- Import/Export: `maatwebsite/excel`
- PDF: `barryvdh/laravel-dompdf` sau `spatie/browsershot`
- Auth SPA & mobile: `laravel/sanctum`
- Queue monitor: `laravel/horizon`
- Feature flags: `laravel/pennant`

## Frontend (Vue)

- State: Pinia, utilitare: VueUse, validare: vee-validate + yup
- UI: TailwindCSS + Headless UI / PrimeVue / Naive UI
- Charts/KPI: Chart.js sau ECharts
- PDF viewer: PDF.js; adnotări: tui-image-editor

## Roadmap funcțional (sugerat)

- Module: Projects, Sites, WBS/Tasks, RFIs, Documents, Timesheets, Finance
- Căutare: integrare Scout + Meilisearch pentru liste (proiecte, documente, RFIs)
- Realtime: Laravel Reverb (sau Pusher) pentru notificări live
- Observabilitate: Telescope (dev), Sentry (prod)
- Testare: Pest (unit/feature), Dusk/Playwright pentru e2e
- Securitate: rate limit, CSRF, headers (CSP), `composer audit`

## CI/CD (template inclus)

- Workflow: `.github/workflows/ci.yml` – rulează teste PHP și build frontend
- Extinde cu: lint (Pint, ESLint), static analysis (Larastan), build & push imagini

## Comenzi utile

- Pornește stack-ul: `bash scripts/dev.sh`
- Oprește stack-ul: `docker compose down`
- Teste: `bash scripts/test.sh`
- Artisan: `docker compose run --rm app php artisan <cmd>`
- NPM: `docker compose run --rm node npm <cmd>`

## Note

- Scripturile și Compose nu descarcă nimic acum; sunt pregătite pentru rulare locală când ai rețea.
- Pentru Reverb/Horizon poți adăuga mai târziu configurări/servicii dedicate.
