# Laravel Minimal Recipe App

<!-- #ZEROPS_EXTRACT_START:intro# -->

A minimal Laravel application with a PostgreSQL connection,
demonstrating database connectivity, migrations, and a health endpoint.
Used within [Laravel Minimal recipe](https://app.zerops.io/recipes/laravel-minimal) for [Zerops](https://zerops.io) platform.

<!-- #ZEROPS_EXTRACT_END:intro# -->

[![Deploy on Zerops](https://github.com/zeropsio/recipe-shared-assets/blob/main/deploy-button/light/deploy-button.svg)](https://app.zerops.io/recipes/laravel-minimal?environment=small-production)

![Laravel cover](https://github.com/zeropsio/recipe-shared-assets/blob/main/covers/svg/cover-laravel.svg)

## Integration Guide

<!-- #ZEROPS_EXTRACT_START:integration-guide# -->

### 1. Adding `zerops.yaml`

The main configuration file — place at repository root. It tells Zerops how to build, deploy and run your app.

```yaml
zerops:
  # Production — optimized build, compiled assets, framework caches.
  - setup: prod
    build:
      # Multi-base build: PHP for Composer, Node for Vite asset
      # compilation. Both runtimes are fully available on PATH
      # during the build — no manual install needed.
      base:
        - php@8.4
        - nodejs@22
      buildCommands:
        # Production Composer install — no dev packages, classmap
        # optimized for faster autoloading in production.
        - composer install --no-dev --optimize-autoloader
        # Vite compiles Tailwind CSS and JS into content-hashed
        # bundles in public/build/. These static assets are all
        # the runtime container needs from the Node side.
        - npm install
        - npm run build
      deployFiles:
        # List each directory explicitly — deploying ./ would
        # ship node_modules, .env.example, and other build-only
        # artifacts the runtime container doesn't need.
        - app
        - bootstrap
        - config
        - database
        - public
        - resources/views
        - routes
        - storage
        - vendor
        - artisan
        - composer.json
      # Cache vendor/ and node_modules/ between builds so
      # Composer and npm skip redundant network fetches.
      cache:
        - vendor
        - node_modules

    # Readiness check gates the traffic switch — new containers
    # must answer HTTP 200 before the L7 balancer routes to them.
    # This enables zero-downtime deploys.
    deploy:
      readinessCheck:
        httpGet:
          port: 80
          path: /health

    run:
      # php-nginx serves via Nginx + PHP-FPM — no explicit start
      # command needed; the base image handles both processes.
      base: php-nginx@8.4
      # Nginx serves static files from public/ and proxies PHP
      # requests to FPM. Laravel expects this as its web root.
      documentRoot: public
      # Config, route, and view caches MUST be built at runtime.
      # Build runs at /build/source/ but the app serves from
      # /var/www/ — caching during build bakes wrong paths.
      # Migrations run exactly once per deploy via zsc execOnce,
      # regardless of how many containers start in parallel.
      initCommands:
        - zsc execOnce ${appVersionId} --retryUntilSuccessful -- php artisan migrate --force
        - php artisan config:cache
        - php artisan route:cache
        - php artisan view:cache
      # Health check restarts unresponsive containers after the
      # 5-minute retry window expires — keeps production alive.
      healthCheck:
        httpGet:
          port: 80
          path: /health
      envVariables:
        APP_NAME: "Laravel Zerops"
        # Production mode — stack traces hidden, error pages
        # generic, optimizations enabled.
        APP_ENV: production
        APP_DEBUG: "false"
        # APP_URL drives absolute URL generation for redirects,
        # signed URLs, mail links, and CSRF origin validation.
        # zeropsSubdomain is the platform-injected HTTPS URL.
        APP_URL: ${zeropsSubdomain}
        # Stderr logging sends output to Zerops runtime log
        # viewer — no log files to manage or rotate.
        LOG_CHANNEL: stderr
        LOG_LEVEL: warning
        # Cross-service references resolve at deploy time.
        # Pattern: ${hostname_varname} maps to the db service's
        # auto-generated credentials.
        DB_CONNECTION: pgsql
        DB_HOST: ${db_hostname}
        DB_PORT: ${db_port}
        DB_DATABASE: ${db_dbName}
        DB_USERNAME: ${db_user}
        DB_PASSWORD: ${db_password}
        # Database-backed sessions work out of the box with the
        # sessions migration Laravel ships by default.
        SESSION_DRIVER: database
        CACHE_STORE: database

  # Dev — full source deployed for live editing via SSHFS.
  # PHP-FPM serves requests immediately; edit files in /var/www
  # and changes take effect on the next request — no restart.
  - setup: dev
    build:
      # Same multi-base as prod — both PHP and Node available
      # during the build so Composer and npm can run.
      base:
        - php@8.4
        - nodejs@22
      buildCommands:
        # Full Composer install with dev packages — testing and
        # debugging tools available over SSH.
        - composer install
        # Pre-populate node_modules so the developer can run
        # npm run dev (Vite HMR) immediately after SSH-ing in
        # without waiting for another install.
        - npm install
      # Deploy the entire working directory — source files,
      # vendor/, node_modules/, and zerops.yaml so zcli push
      # works from the dev container.
      deployFiles:
        - ./
      cache:
        - vendor
        - node_modules

    run:
      base: php-nginx@8.4
      documentRoot: public
      # Install Node on the runtime container so the developer
      # can run Vite dev server (npm run dev) over SSH. This
      # runs once and is cached into the runtime image — not
      # re-executed on every container restart.
      prepareCommands:
        - sudo -E zsc install nodejs@22
      # Migration runs once per deploy — DB is ready when the
      # SSH session starts. No cache warming in dev — we want
      # config changes to take effect immediately.
      initCommands:
        - zsc execOnce ${appVersionId} --retryUntilSuccessful -- php artisan migrate --force
      envVariables:
        APP_NAME: "Laravel Zerops"
        # Dev mode — detailed error pages with stack traces,
        # no config caching, verbose logging for debugging.
        APP_ENV: local
        APP_DEBUG: "true"
        APP_URL: ${zeropsSubdomain}
        # Debug-level stderr logging surfaces all framework
        # events in the Zerops log viewer.
        LOG_CHANNEL: stderr
        LOG_LEVEL: debug
        # Same DB wiring as prod — only mode flags differ.
        DB_CONNECTION: pgsql
        DB_HOST: ${db_hostname}
        DB_PORT: ${db_port}
        DB_DATABASE: ${db_dbName}
        DB_USERNAME: ${db_user}
        DB_PASSWORD: ${db_password}
        SESSION_DRIVER: database
        CACHE_STORE: database
```

### 2. Trust the reverse proxy

Zerops terminates SSL at its L7 balancer and forwards requests via reverse proxy. Without trusting the proxy, Laravel rejects CSRF tokens and generates `http://` URLs instead of `https://`. In `bootstrap/app.php`:

```php
->withMiddleware(function (Middleware $middleware): void {
    $middleware->trustProxies(at: '*');
})
```

<!-- #ZEROPS_EXTRACT_END:integration-guide# -->

<!-- #ZEROPS_EXTRACT_START:knowledge-base# -->

### Gotchas

- **No `.env` file** — Zerops injects environment variables as OS env vars. Creating a `.env` file with empty values shadows the OS vars, causing `env()` to return `null` for every key that appears in `.env` even if the platform has a value set.
- **Cache commands in `initCommands`, not `buildCommands`** — `config:cache`, `route:cache`, and `view:cache` bake absolute paths into their cached files. The build container runs at `/build/source/` while the runtime serves from `/var/www/`. Caching during build produces paths like `/build/source/storage/...` that crash at runtime with "directory not found."
- **`APP_KEY` is project-level** — Laravel's encryption key must be shared across all services that read the same database (sessions, encrypted columns). Set it once at project level in Zerops; do not add it per-service or in `zerops.yaml envVariables`.
- **PDO PostgreSQL extension** — The `php-nginx` base image includes `pdo_pgsql` out of the box. No `prepareCommands` or `apk add` needed for PostgreSQL connectivity.

<!-- #ZEROPS_EXTRACT_END:knowledge-base# -->
