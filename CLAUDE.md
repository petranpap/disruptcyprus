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
| OpenAPI export | `podman compose exec app php artisan scramble:export --path=storage/app/private/openapi.json && cp backend/storage/app/private/openapi.json docs/` |
| Generate a digest draft | `podman compose exec app php artisan digests:generate news daily [--date=YYYY-MM-DD] [--refresh]` |
| Create/promote staff | `podman compose exec app php artisan admin:user you@example.com [--role=editor]` |
| VAPID keys (once per environment) | `podman compose exec app php artisan webpush:vapid` (writes `.env`; never commit) |
| Run the scheduler once | `podman compose exec app php artisan schedule:run` (the `scheduler` container runs `schedule:work`) |
| Admin panel | http://localhost:8080/admin (demo: admin@ / editor@disruptcyprus.test, password `password`) |
| Failed jobs | `podman compose exec app php artisan queue:failed` / `queue:retry all` |
| Web dev server | `podman compose up -d node` → http://localhost:5173 (Vite proxies /api, /sanctum, /storage to nginx) |
| Web one-off commands | `podman compose run --rm -T node npm run <script>` (Node 20, same as production) |
| Web checks | scripts: `typecheck`, `lint`, `test` (Vitest), `format`, `build` (tokens + tsc + vite), `tokens`, `icons` |
| Component gallery | http://localhost:5173/dev/components (dev builds only) · `?theme=dark|light` forces a theme on any page |

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
- Admin (Filament 5): resources live in `app/Filament/Resources/<Plural>/{Resource, Schemas, Tables, Pages}`. Translatable fields are
  named `field.el` / `field.en` inside `Translatable::tabs()`; spatie reads/writes the per-locale array directly. Articles/events use
  the virtual `industry_ids` + `primary_industry_id` fields (`IndustryFields`, `SavesEditorialContent`). All labels via `lang/{en,el}/admin.php`.
- Admin tests use `Livewire::test()` (no pest-plugin-livewire) after `Filament::setCurrentPanel('admin')`.
- Status/published_at are normalized in the models (`NormalizesPublication`); never set `scheduled` by hand elsewhere.
- Tests touching FULLTEXT search go in `tests/Search` (truncation, not transactions: InnoDB FTS only sees committed rows).
- Test helpers: `reader()`, `newArticle()`, `newEvent()`, `section()` (`event()` is a Laravel helper — don't shadow it).

**Web**
- React 19 + React Router 7 (data router, `createBrowserRouter`), TanStack Query 5, Zustand 5, react-hook-form + zod 4, i18next, Tailwind 4 (CSS-first).
- Design tokens: edit `web/src/styles/tokens.json`, then `npm run tokens` regenerates `src/styles/theme.css` (never edit it by hand). Tailwind's
  default palette is removed: only token colours exist (`bg-surface-container-lowest`, `text-on-surface-variant`, …).
- API calls only through `src/api/client.ts` (CSRF cookie, `Accept-Language`, error envelope → `ApiError`) and zod-validated hooks in `src/api/*`.
- After Prettier runs, multi-line code no longer matches single-line search strings: verify scripted edits actually applied (grep), as several silently missed in Phase 5.
- Cards save themselves via `useBookmark()` (optimistic + guest gate); feeds use `FeedStream` (design rhythm) inside `FeedLayout` (desktop sidebar).
- Never copy server data into state from an effect: load in a parent, initialise the form's state from props (see onboarding steps).
- Constants/helpers shared across components live in non-component modules (fast refresh).
- Tests: Vitest + RTL with MSW (`src/test/server.ts` in-memory backend, `renderApp(path)` mounts the real routes). Tests run in English.
- Feature folders under `web/src/features/*`. Server state = TanStack Query; client state = small Zustand stores.
- Forms: react-hook-form + zod. API responses are validated with zod schemas in `web/src/api`.
- Icons: Material Symbols as SVG components (`@material-symbols/svg-400`); no icon font.
- Fonts self-hosted (`@fontsource-variable/*`). Headline stack: `"Playfair Display", "Noto Serif Display", serif` (Playfair has no Greek).
- Greek uppercase labels go through the `greekUpper()` helper (strips tonos).
- Responsive website with an app feel on phones: < `lg` (1024px) = app layout (tab row + bottom nav); ≥ `lg` = website
  (top-bar navigation, `Container` up to 1200px, footer, no bottom nav). One header component rearranges via CSS — never
  render separate mobile/desktop copies of navigation. Use `Screen` + `Container` (`content` | `narrow` | `reading`).
  44px minimum tap targets, safe-area insets.

## Decisions log

| Date | Decision | Why |
|---|---|---|
| 2026-10-05 | Sanctum SPA cookies, API served same-origin under `app.<domain>/api` | No CORS, first-party cookies, iOS PWA safe. Token endpoint kept for native |
| 2026-10-05 | SEO via Blade share pages `/a/{slug}`, `/e/{slug}`, `/d/{slug}` on the main domain; PWA shares those URLs | Simplest path to OG previews + indexing without SSR |
| 2026-10-05 | One slug per item (not per locale) | Stable share URLs, simple routing |
| 2026-10-06 | Search: `SearchService` on MariaDB FULLTEXT (`search_text`, boolean prefix mode) + whole-word REGEXP for terms < 3 chars; Scout not used | Accent/case-insensitive Greek, prefixes, "AI"; Scout's database engine can't do these |
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
| 2026-10-06 | Feed ranking: recency half-life 36h, +25% featured, +15% original, +10% per extra followed industry (max 3), events within 14 days (half-life 96h), max 3 consecutive items per section; cursor pins ranking time | Deterministic, testable pages |
| 2026-10-06 | Trending = views + 3 × saves in 48h (hourly `content_view_stats` buckets), cached 5 min | Brief |
| 2026-10-06 | All datetimes normalized to UTC before storage (`StoresUtcTimestamps`) | Eloquent stores a Carbon's wall-clock time otherwise |
| 2026-10-06 | Editor HTML sanitized on output with symfony/html-sanitizer (allow-list), cached per article/locale/updated_at | Brief: never trust stored HTML |
| 2026-10-06 | Admin roles: editors manage articles, events, digests, authors, push campaigns; only admins delete content and manage users, industries, sections. Section slugs are fixed | Brief; slugs are app routes |
| 2026-10-06 | Digest drafts: Daily News 06:00, Weekly Events Sunday 18:00 (next Mon–Sun), Monthly News 1st 06:00 (previous month, top 5 per section), Monthly Events 1st 06:05 — all Asia/Nicosia | Brief; generator idempotent, never overwrites edited or published |
| 2026-10-06 | Creating a digest in the admin runs the generator; any admin save marks a draft edited; "Regenerate draft" overwrites after confirmation | One code path, no duplicates |
| 2026-10-06 | "Publish and notify" and push campaigns fire `DigestPublished` / `SendPushCampaign`; delivery is a logged stub until Phase 6. Campaigns: 3 sends/hour per staff member | Brief |
| 2026-10-06 | `admin:user` command for staff accounts (Filament's make:filament-user creates readers) | Panel access is role-based |
| 2026-10-07 | Node 20 in a `node` compose service (same major as production); Vite dev on 5173 | Local mirrors production |
| 2026-10-07 | Cache only arrays/scalars (rendered resource payloads per locale), never Eloquent objects | Laravel 13 `serializable_classes = false` breaks unserialize; caught by screenshot QA |
| 2026-10-07 | Relative time: English narrow ("2h ago", as in the designs), Greek long ("πριν από 2 ώρες") | Greek short form abbreviates to "ώ." |
| 2026-10-07 | Logo mark drawn as SVG (bold "#") shared by app icon and header; wordmark stays Playfair until the SVG logo arrives | Thin font glyph was illegible at 28px |
| 2026-10-07 | Route-level code splitting for welcome/auth/onboarding; precache only Latin + Greek font subsets | Main bundle 605→252 KB, precache 1.27 MB→0.88 MB |
| 2026-10-07 | Desktop is a real website (top-bar nav, 1200px container, footer, split auth screens); app layout below 1024px | Product owner: "a website with PWA, not an app on desktop" (replaces the 480px column) |
| 2026-10-07 | Service worker: network-first for feeds/lists/details (cache fallback offline), stale-while-revalidate only for industries/sections, saved articles in `saved-content-v1`; sign-out/deletion clears personal caches | SWR would show stale bookmark state after a toggle; offline still instant from cache |
| 2026-10-07 | Bookmarks are optimistic across every cached query (`patchCards`) with rollback; guests get a sign-up sheet | Instant feedback everywhere a card appears |
| 2026-10-07 | First-time guests are sent to Welcome only from `/`; deep links (shared articles/events) open directly | Shared links must land on the content |
| 2026-10-07 | Reader "Listen" uses Web Speech behind `SpeechProvider`; hidden when the device has no voice for the story language | Brief; swappable for a TTS service later |
| 2026-10-07 | Notifications: one `AppNotification` payload `{type,title,body,url}` → database (inbox) always + Web Push when the reader has a device (`laravel-notification-channels/webpush`). Rendered in the reader's UI language | One code path; inbox works without push |
| 2026-10-07 | Digest notifications at each reader's `delivery_time` in their timezone; if already passed: now between 07:00–22:00 local, else next morning. Delayed jobs re-check that the content is still published (`shouldSend`) | Brief + no midnight pushes |
| 2026-10-07 | Idempotency via `notification_dispatches` (unique user + key): one digest/featured alert/reminder per reader; featured alerts ≤ 3 per reader per 24 h | Retries and republishing never double-notify |
| 2026-10-07 | Event reminders: hourly, events starting in (now+1h, now+24h] | Covers events saved on the day; never at the door |
| 2026-10-07 | Filament panel uses database transactions; featured-article listener is after-commit + 60 s delay | Listener must see the industries pivot saved after the record |
| 2026-10-07 | Push endpoints restricted to known push services (FCM, Mozilla, Apple, Windows) | The server POSTs to the endpoint: no SSRF |
| 2026-10-07 | Sign-out unsubscribes push on that device (no prompt); sign-in re-attaches an existing subscription to the new account | Shared devices never get another reader's notifications |
| 2026-10-07 | Install/push prompts: one per visit, never in the first 60 s; push offer after the first save, install banner after 3 reads or first save, snoozed 14 days; iOS gets Home Screen instructions | Brief; non-intrusive |
| 2026-10-05 | Everything runs in containers (podman + podman-compose locally; compose file stays Docker-compatible) | Product owner |

## Phase status

- [x] Phase 0 — Discovery & plan (docs/ARCHITECTURE.md, docs/DESIGN_TOKENS.md)
- [x] Phase 1 — Backend foundation (schema, seeders, auth, account, taxonomy, preferences)
- [x] Phase 2 — Content API (feeds, sections, articles, events, digests, search, bookmarks, OpenAPI)
- [x] Phase 3 — Admin panel (Filament 5), digest generation + scheduler, push campaigns UI (delivery stubbed)
- [x] Phase 4 — Web foundation (shell, tokens, i18n, API client, auth, onboarding, component gallery, PWA base)
- [x] Phase 5 — Web features (feeds, sections, reader, events + calendar, digests, explore/search, saved + offline, settings, guest mode)
- [x] Phase 6 — Notifications (inbox, Web Push, digest timing, featured alerts, event reminders, campaigns, install prompt)
- [ ] Phase 7 — Landing, share pages, GDPR, CI, Lighthouse, deployment, visual QA
