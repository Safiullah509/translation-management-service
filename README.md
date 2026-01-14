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

-   `APP_URL`
-   `DB_*` (database connection)

Optional:

-   `VITE_ASSET_URL` for CDN asset base (example: `https://cdn.example.com/`)

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

### Coverage

```bash
composer test:coverage
```

### Performance tests

Performance assertions are opt-in to avoid flaky CI. Enable them with:

```bash
PERF_TESTS=1 php artisan test
```

Optional dataset seeding for scale checks:

```bash
php artisan translations:perf --count=100000
```

## Design Notes

-   **Sanctum tokens** keep the auth flow simple for API + admin UI use.
-   **Export freshness** avoids response caching to guarantee up-to-date translations.
-   **Tag model** is a many-to-many relation for flexible tagging and filtering.
-   **Vite assets** are configurable via `VITE_ASSET_URL` to support CDN hosting.

## Query Profiling Notes

-   Add indexes for `translations.key`, `translations.content`, `translations.locale_id`, and `tag_translation.tag_id` to speed up search and tag filters.
-   For MySQL production, consider replacing `translations.content` index with a `FULLTEXT` index for better `LIKE`/search performance.
-   Use `EXPLAIN` on `translations` and `translations/search` queries when tuning large datasets.
