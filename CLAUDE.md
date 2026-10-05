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
docker-compose.yml   local dev: php-fpm, nginx, mysql, redis, mailpit
```

## Commands

Filled in as each phase lands. `—` = not available yet.

| Task | Backend | Web |
|---|---|---|
| Start dev stack | `docker compose up -d` (Phase 1) | `npm --prefix web run dev` (Phase 4) |
| Install deps | `docker compose exec app composer install` | `npm --prefix web ci` |
| Migrate + seed | `docker compose exec app php artisan migrate --seed` | — |
| Fresh DB | `docker compose exec app php artisan migrate:fresh --seed` | — |
| Tests | `docker compose exec app php artisan test` (Pest) | `npm --prefix web test` (Vitest), `npm --prefix web run e2e` (Playwright) |
| Lint / format | `vendor/bin/pint`, `vendor/bin/phpstan analyse` | `npm --prefix web run lint`, `npm --prefix web run typecheck` |
| Generate digests manually | `php artisan digests:generate news daily --date=YYYY-MM-DD` | — |
| Generate design theme | — | `npm --prefix web run tokens` |

Local machine note: `docker` is podman (podman-docker shim). A compose provider (`podman-compose` or
`docker-compose`) must be installed for `docker compose` to work.

## Conventions

**Backend**
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
| 2026-10-05 | Search: Scout database engine over denormalized `search_text` (ai_ci collation, FULLTEXT) | Accent/case-insensitive Greek search; JSON columns can't do it |
| 2026-10-05 | Greek headline fallback: Noto Serif Display | Playfair Display lacks Greek glyphs |
| 2026-10-05 | Bottom nav = 4 tabs (Home, Explore, Saved, Profile) | Matches home export + brief; DESIGN.md text outdated |
| 2026-10-05 | Accessible color adjustments approved (filled buttons `#0077B6`, see DESIGN_TOKENS §1.4) | WCAG AA |
| 2026-10-05 | Reader: no Applauds/Comments; bottom bar = Share + Save | Out of scope for MVP (moderation, GDPR) |
| 2026-10-05 | Welcome: Apple button hidden until Apple Sign-In exists; copy rewritten for startup audience (ARCHITECTURE §4 C6); no "Ad-Free" claim | Product owner |
| 2026-10-05 | Logo: Stitch Playfair wordmark until an SVG logo is supplied | Product owner will provide SVG |
| 2026-10-05 | Domains: `app.disruptcyprus.com` (PWA + API), `disruptcyprus.com` (landing, share pages, admin) | Confirmed |
| 2026-10-05 | Everything runs in containers (podman + podman-compose locally; compose file stays Docker-compatible) | Product owner |

## Phase status

- [x] Phase 0 — Discovery & plan (docs/ARCHITECTURE.md, docs/DESIGN_TOKENS.md)
- [ ] Phase 1 — Backend foundation
- [ ] Phase 2 — Content API
- [ ] Phase 3 — Admin panel + digests + scheduler
- [ ] Phase 4 — Web foundation
- [ ] Phase 5 — Web features
- [ ] Phase 6 — Notifications
- [ ] Phase 7 — Landing, share pages, GDPR, CI, Lighthouse, deployment, visual QA
