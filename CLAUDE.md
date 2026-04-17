# laravel-minimal-app

Laravel 13 starter with PostgreSQL + Vite — no Jetstream/Breeze; health + status endpoints; runtime-time artisan cache.

## Zerops service facts

- HTTP port: `80` (document root `public/`)
- Siblings: `db` (PostgreSQL) — env: `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`
- Runtime base: `php-nginx@8.4` (build on `php@8.4` + `nodejs@22`)

## Zerops dev (hybrid)

Runtime (`php-nginx`) auto-serves PHP changes immediately — edit `.blade.php` / `.php` and they take effect on the next request.

**Vite dev server is NOT auto-started.** For frontend asset HMR, the agent must start it manually:

- Vite dev command: `npm run dev`
- Build frontend assets (instead of HMR): `npm run build`

**All platform operations (start/stop of Vite, deploy, env / scaling / storage / domains) go through the Zerops development workflow via `zcp` MCP tools. Don't shell out to `zcli`.**

## Notes

- No `.env` file in the container — platform injects env as OS vars; a local `.env` would shadow them.
- Artisan caches (`config:cache`, `route:cache`, `view:cache`) run in prod `initCommands`, not `buildCommands` — build path (`/build/source`) differs from runtime path (`/var/www`).
- Dev setup installs Node via `prepareCommands` (`sudo -E zsc install nodejs@22`) — cached into the runtime image, not re-run on restart.
