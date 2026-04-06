# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Minimal Laravel 13 app with PostgreSQL, demonstrating database connectivity, migrations, and health/status endpoints. Designed as a recipe for the [Zerops](https://zerops.io) deployment platform.

## Commands

```bash
# Full local setup (install deps, generate key, migrate, build assets)
composer setup

# Development (starts PHP server, queue worker, log tail, and Vite HMR concurrently)
composer dev

# Run tests (clears config cache first, uses SQLite in-memory per phpunit.xml)
composer test

# Run a single test file
php artisan test --filter=ExampleTest

# Lint/format PHP
./vendor/bin/pint

# Build frontend assets
npm run build
```

## Architecture

- **Routes** are defined entirely in `routes/web.php` — three closure-based routes: `/` (welcome page with DB status), `/health` (JSON health check used by Zerops readiness/liveness probes), `/status` (detailed JSON status).
- **No controllers** — all logic lives in route closures given the minimal scope.
- **Database**: PostgreSQL in production/dev (Zerops), SQLite in-memory for tests. Default Laravel migrations (users, cache, jobs tables).
- **Frontend**: Vite + Tailwind CSS v4 via `@tailwindcss/vite` plugin. Entry points: `resources/css/app.css`, `resources/js/app.js`.
- **Middleware**: Reverse proxy trust configured in `bootstrap/app.php` (`trustProxies(at: '*')`) — required for Zerops L7 balancer.

## Deployment (Zerops)

Configured in `zerops.yaml` with two setups:
- **prod**: Optimized build (no dev deps, asset compilation, config/route/view caching at runtime).
- **dev**: Full source deployed for live editing via SSH, includes dev dependencies and Node runtime.

Key gotcha: No `.env` file in Zerops — environment variables are injected as OS env vars. Creating a `.env` with empty values shadows them.
