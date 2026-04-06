# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Minimal Laravel 13 app with PostgreSQL, demonstrating database connectivity, migrations, and health/status endpoints. Designed as a recipe for the [Zerops](https://zerops.io) deployment platform.

## Development Environment

The app runs on Zerops by default, even in dev — Nginx + PHP-FPM serve requests automatically (no `php artisan serve` needed). PHP changes take effect on the next request without restart.

### Zerops dev (primary workflow)

```bash
# Start Vite dev server for frontend hot-reload (PHP is already running via FPM)
npm run dev

# Run tests (uses SQLite in-memory per phpunit.xml)
composer test

# Run a single test
php artisan test --filter=ExampleTest

# Lint/format PHP
./vendor/bin/pint

# Build frontend assets for production
npm run build
```

### Purely local development

```bash
# Full local setup (install deps, generate key, migrate, build assets)
composer setup

# Start PHP server, queue worker, log tail, and Vite HMR concurrently
composer dev

# Tests and lint — same as above
composer test
./vendor/bin/pint
```

## Architecture

- **Routes** are defined entirely in `routes/web.php` — three closure-based routes: `/` (welcome page with DB status), `/health` (JSON health check used by Zerops readiness/liveness probes), `/status` (detailed JSON status).
- **No controllers** — all logic lives in route closures given the minimal scope.
- **Database**: PostgreSQL in production/dev (Zerops), SQLite in-memory for tests. Default Laravel migrations (users, cache, jobs tables).
- **Frontend**: Vite + Tailwind CSS v4 via `@tailwindcss/vite` plugin. Entry points: `resources/css/app.css`, `resources/js/app.js`.
- **Middleware**: Reverse proxy trust configured in `bootstrap/app.php` (`trustProxies(at: '*')`) — required for Zerops L7 balancer.

## Deployment (Zerops)

Configured in `zerops.yaml` with two setups:
- **prod**: Optimized build (no dev deps, asset compilation, config/route/view caching at runtime). Readiness/health checks hit `/health`.
- **dev**: Full source deployed for live editing via SSH, includes dev dependencies and Node runtime.

Key gotchas:
- **No `.env` file** — Zerops injects env vars at OS level. Creating a `.env` with empty values shadows them, causing `env()` to return `null`.
- **Cache commands at runtime only** — `config:cache`, `route:cache`, `view:cache` bake absolute paths. Build runs at `/build/source/` but runtime serves from `/var/www/`, so caching during build breaks paths.
