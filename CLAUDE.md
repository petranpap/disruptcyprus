# CLAUDE.md — Disrupt Cyprus

Bilingual (EL/EN) personalized startup & innovation news platform for Cyprus / East Med.
Installable React PWA + Laravel API + Filament admin + Blade landing/share pages.
All content is written in-house by editors. **No RSS import, no external aggregation. Never build it.**

## Hard rules

- `UI/` (uppercase; the brief calls it `ui/`) is the design reference and is **read-only**. Never modify, move or rename it.
- When a requirement conflicts with `UI/`, raise it with the product owner before deciding. Log the outcome under Decisions.
- Never commit secrets. `.env` files stay local; keep `.env.example` complete.
- Work in phases (see brief). Stop and report after each phase; wait for "continue".
- Small logical commits, imperative messages (`feat(api): add bookmarks endpoints`). Boring, documented solutions; no unnecessary packages.
- No hardcoded UI strings in the web app: everything goes in `web/src/i18n/{en,el}.json`. Code comments in English.
- Use design tokens only (no raw hex in components). Source: `docs/DESIGN_TOKENS.md`.

## Repo layout

```
CLAUDE.md            this file
UI/                  Stitch design exports (read-only)
backend/             Laravel 13 + Filament 5 + Blade landing/share pages
web/                 React 19 + Vite 8 PWA (TypeScript strict)
docs/                ARCHITECTURE.md, API.md, DESIGN_TOKENS.md, DEPLOYMENT.md
docker-compose.yml   local dev only: php-fpm 8.4, nginx, mariadb 10.11, mailpit
deploy.sh            production deploy (run on the server)
```

## Commands

All backend commands run in containers. Prefix: `podman compose exec app …` (set `PODMAN_COMPOSE_WARNING_LOGS=false`
to silence the provider banner; use `exec -T` in non-interactive scripts).

| Task | Command |
|---|---|
| Start / stop stack | `podman compose up -d` / `podman compose down` |
| Rebuild PHP image | `podman compose build app` (compiles with `-j2`; the machine has little free RAM) |
| Composer | `podman compose exec app composer require …`, then `podman compose restart queue scheduler` |
| Migrate + seed | `podman compose exec app php artisan migrate --seed` |
| Fresh DB with demo content | `podman compose exec app php artisan migrate:fresh --seed` |
| Tests (Pest, MariaDB `disrupt_testing`) | `podman compose exec app php artisan test --compact` |
| Code style | `podman compose exec app vendor/bin/pint` |
| Static analysis (level 6) | `podman compose exec app vendor/bin/phpstan analyse --memory-limit=1G` |
| Failed jobs | `podman compose exec app php artisan queue:failed` / `queue:retry all` |
| Web (Phase 4) | `npm --prefix web run dev` · `npm --prefix web test` · `npm --prefix web run lint` |

Ports (host network, 127.0.0.1): nginx 8080, php-fpm 9000, MariaDB 3307 (a host MySQL owns 3306), Mailpit 1025/8025.

Production (see docs/DEPLOYMENT.md): shared Ubuntu 24.04 server, Apache 2.4 + PHP-FPM 8.4 pool, MariaDB 10.11, Node 20,
no Redis, no Docker. Deploy = `git push`, then `./deploy.sh` on the server as the `disrupt` user.
**Local must mirror production**: never use features that need Redis, MySQL-only syntax/collations (`utf8mb4_0900_*`),
or PHP extensions the server lacks (no redis, no bcmath).

Environment quirks on this machine:
- `docker` is podman (podman-docker). `podman-compose` is installed in `~/.local/bin` (pip --user).
- Rootless bridge networking (pasta) fails with "Failed to remount /: Permission denied", so every service uses `network_mode: host`.
- One-off containers: `podman run --rm --network=host --userns=keep-id -v ./backend:/var/www/html:z localhost/disrupt-php:dev …`.
- `php artisan tinker <file>` hangs (interactive). For ad-hoc scripts, bootstrap the app in a PHP file under `backend/storage/app/private/` and run it with `php`.

## Conventions

**Backend**
- Laravel 13 idioms: model config via attributes (`#[Fillable]`, `#[Hidden]`), `casts()` method, `bootstrap/app.php` for middleware/exceptions.
- Morph map is enforced (`article`, `event`, `digest`, `user`, `industry`, `section`, `author`); never store class names.
- Thin controllers → Form Requests (validation) → Services (logic) → API Resources (output). Policies for every model.
- API prefix `/api/v1`, `Accept-Language: el|en` (default `el`). Error shape `{message, errors?, code}`. Cursor pagination everywhere.
- Translatable fields via `spatie/laravel-translatable`. A locale is "available" only if title + body exist for it.
- Times stored in UTC. Business periods (digests, "this week") computed in `Asia/Nicosia`.
- Feed ranking lives only in `App\Services\FeedService` and is unit-tested.
- Editor HTML is sanitized on output (allow-list), never trusted.
- Every endpoint gets a Pest feature test. Pint + Larastan must stay clean.

**Web**
- Feature folders under `web/src/features/*`. Server state = TanStack Query; client state = small Zustand stores.
- Forms: react-hook-form + zod. API responses are validated with zod schemas in `web/src/api`.
- Icons: Material Symbols as SVG components (`@material-symbols/svg-400`); no icon font.
- Fonts self-hosted (`@fontsource-variable/*`). Headline stack: `"Playfair Display", "Noto Serif Display", serif` (Playfair has no Greek).
- Greek uppercase labels go through the `greekUpper()` helper (strips tonos).
- Mobile-first. ≥768px: centered 480px app column. 44px minimum tap targets, safe-area insets.

## Decisions log

| Date | Decision | Why |
|---|---|---|
| 2026-10-05 | Sanctum SPA cookies, API served same-origin under `app.<domain>/api` | No CORS, first-party cookies, iOS PWA safe. Token endpoint kept for native |
| 2026-10-05 | SEO via Blade share pages `/a/{slug}`, `/e/{slug}`, `/d/{slug}` on the main domain; PWA shares those URLs | Simplest path to OG previews + indexing without SSR |
| 2026-10-05 | One slug per item (not per locale) | Stable share URLs, simple routing |
| 2026-10-05 | Search: Scout database engine over denormalized `search_text` (`utf8mb4_unicode_ci`, FULLTEXT) | Accent/case-insensitive Greek search; JSON columns can't do it |
| 2026-10-05 | Greek headline fallback: Noto Serif Display | Playfair Display lacks Greek glyphs |
| 2026-10-05 | Bottom nav = 4 tabs (Home, Explore, Saved, Profile) | Matches home export + brief; DESIGN.md text outdated |
| 2026-10-05 | Accessible color adjustments approved (filled buttons `#0077B6`, see DESIGN_TOKENS §1.4) | WCAG AA |
| 2026-10-05 | Reader: no Applauds/Comments; bottom bar = Share + Save | Out of scope for MVP (moderation, GDPR) |
| 2026-10-05 | Welcome: Apple button hidden until Apple Sign-In exists; copy rewritten for startup audience (ARCHITECTURE §4 C6); no "Ad-Free" claim | Product owner |
| 2026-10-05 | Logo: Stitch Playfair wordmark until an SVG logo is supplied | Product owner will provide SVG |
| 2026-10-05 | Domains: `app.disruptcyprus.com` (PWA + API), `disruptcyprus.com` (landing, share pages, admin) | Confirmed |
| 2026-10-05 | `POST /me/export` (not GET) and separate `POST /me/avatar` | Side-effect-free GETs; PHP can't parse multipart PATCH |
| 2026-10-05 | Digests opt-in by default; event reminders on by default | GDPR-friendly; reminders only concern items the user saved |
| 2026-10-05 | `push_subscriptions` table deferred to Phase 6 | Comes with the web-push package migration |
| 2026-10-05 | Tests run on MariaDB 10.11 (`disrupt_testing`), not SQLite | JSON columns, FULLTEXT and collations must behave like production |
| 2026-10-05 | Production = existing shared server (Apache, PHP-FPM 8.4, MariaDB 10.11), deployed with git + `deploy.sh`; Docker only for local dev, pinned to the same versions | Product owner; server already hosts other sites |
| 2026-10-05 | No Redis: cache, queue and sessions use the database | Not installed on the shared server; volume is small |
| 2026-10-05 | Collation `utf8mb4_unicode_ci` (verified accent/case-insensitive for Greek on MariaDB) | `utf8mb4_0900_ai_ci` is MySQL-only |
| 2026-10-05 | Account deletion = anonymize + soft delete immediately (hard purge job in Phase 7) | GDPR erasure without breaking FKs |
| 2026-10-05 | Everything runs in containers (podman + podman-compose locally; compose file stays Docker-compatible) | Product owner |

## Phase status

- [x] Phase 0 — Discovery & plan (docs/ARCHITECTURE.md, docs/DESIGN_TOKENS.md)
- [x] Phase 1 — Backend foundation (schema, seeders, auth, account, taxonomy, preferences)
- [ ] Phase 2 — Content API
- [ ] Phase 3 — Admin panel + digests + scheduler
- [ ] Phase 4 — Web foundation
- [ ] Phase 5 — Web features
- [ ] Phase 6 — Notifications
- [ ] Phase 7 — Landing, share pages, GDPR, CI, Lighthouse, deployment, visual QA
