# Translation Management Service (Backend)

## Overview

Laravel API for managing translations, tags, and locale exports. Includes a simple admin UI, JSON export, and token-based auth.

## Setup

### Local (PHP + MySQL)

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm install
npm run build
php artisan serve
```

Visit `http://localhost:8000`.

### Docker (minimal)

From the repo root:

```bash
docker compose up --build
docker compose exec app php artisan migrate --seed
```

Visit `http://localhost:8000`.

## Environment

Required:
- `APP_URL`
- `DB_*` (database connection)

Optional:
- `VITE_ASSET_URL` for CDN asset base (example: `https://cdn.example.com/`)

## API Docs (Swagger)

Generate docs:

```bash
php artisan l5-swagger:generate
```

Open Swagger UI:

```
http://localhost:8000/api/documentation
```

## Tests

```bash
php artisan test
```

## Design Notes

- **Sanctum tokens** keep the auth flow simple for API + admin UI use.
- **Export caching** uses file cache to avoid large payloads in DB cache.
- **Tag model** is a many-to-many relation for flexible tagging and filtering.
- **Vite assets** are configurable via `VITE_ASSET_URL` to support CDN hosting.
