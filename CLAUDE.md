# laravel-minimal-app

Laravel 13 starter with PostgreSQL + Vite — no Jetstream/Breeze; health + status endpoints; runtime-time artisan cache.

## Zerops service facts

- HTTP port: `80` (document root `public/`)
- Siblings: `db` (PostgreSQL) — env: `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`
- Runtime base: `php-nginx@8.4` (build on `php@8.4` + `nodejs@22`)

## Zerops dev (hybrid)

Runtime (`php-nginx`) auto-serves PHP changes immediately — edit `.blade.php` / `.php` and they take effect on the next request.

**The `dev` setup intentionally skips `npm run build` in `buildCommands`** to keep the HMR workflow (`npm run dev`) fast. Consequence: `public/build/manifest.json` does NOT exist after a fresh deploy, so any view using `@vite(...)` throws HTTP 500 `Vite manifest not found` on the first request.

**After every `zerops_deploy` on this service and BEFORE running `zerops_verify`**, do ONE of:

- **One-shot build** — `ssh appdev 'cd /var/www && npm run build'`. Creates the manifest; survives until the next deploy rsyncs the build tree back in.
- **Long-running dev server (HMR)** — `ssh appdev 'cd /var/www && nohup npm run dev > /tmp/vite.log 2>&1 &'`. Drops `public/build/hot`; Laravel's `@vite` helper routes asset URLs to the dev server. Containers restart on every `zerops_deploy`, so rerun after each redeploy.

**Do NOT add `npm run build` to dev `buildCommands`** — it adds ~20–30 s to every push and defeats the HMR-first design.

**All platform operations (deploy, env / scaling / storage / domains) go through the Zerops development workflow via `zcp` MCP tools. Don't shell out to `zcli`.**

## Notes

- No `.env` file in the container — platform injects env as OS vars; a local `.env` would shadow them.
- Artisan caches (`config:cache`, `route:cache`, `view:cache`) run in prod `initCommands`, not `buildCommands` — build path (`/build/source`) differs from runtime path (`/var/www`).
- Dev setup installs Node via `prepareCommands` (`sudo -E zsc install nodejs@22`) — cached into the runtime image, not re-run on restart.
