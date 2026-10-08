# API — Disrupt Cyprus v1

Base path: `/api/v1`. In production the PWA calls it **same-origin** (`https://app.disruptcyprus.com/api/v1`).
Status: Phases 1–2. Generated OpenAPI 3.1 spec: `docs/openapi.json` (re-export with
`php artisan scramble:export --path=storage/app/private/openapi.json`); interactive docs at `http://localhost:8080/docs/api` (local only).

## Conventions

| Topic | Rule |
|---|---|
| Language | `Accept-Language: el` or `en` selects translated fields and messages. Anything else falls back to `el`. Responses carry `Content-Language` and `Vary: Accept-Language` |
| Envelope | Single resources and lists: `{ "data": … }`. Lists are cursor-paginated: `meta.next_cursor` → pass back as `?cursor=` |
| Errors | `{ "message": string, "code": string, "errors"?: { field: [messages] } }` |
| Error codes | `validation_failed` 422 · `unauthenticated` 401 · `forbidden` 403 · `not_found` 404 · `csrf_mismatch` 419 · `too_many_requests` 429 · `http_error` (other 4xx) · `server_error` 500 |
| Rate limits | 120 req/min per user or IP. Login: 5/min per email+IP and 20/min per IP. Register/forgot/reset: 10/min per IP. Data export: 1/hour per user |
| Caching | Taxonomy: `public, max-age=300`. Content GETs: guests `public, max-age=60`, signed-in `private, no-cache`. All send `ETag` (send `If-None-Match` → 304) and `Vary: Accept-Language, Cookie, Authorization` |
| Content language | Each item is rendered in the UI language if available, else in another of the reader's `content_locales` (guests: both). Cards carry `locale` and `is_fallback`. Lists hide items not readable in any accepted language; opening an item directly always works |
| Optional auth | Public endpoints also recognise a signed-in reader (cookie or bearer) to fill `is_bookmarked` and apply content languages |
| Times | ISO 8601, UTC |

## Authentication

### PWA (cookie session, Sanctum SPA)
1. `GET /sanctum/csrf-cookie` sets the `XSRF-TOKEN` cookie.
2. Send every non-GET request with `X-XSRF-TOKEN: <cookie value>`, `credentials: 'include'`, and `Accept: application/json`.
3. `POST /api/v1/auth/login` → session cookie (httpOnly). `POST /api/v1/auth/logout` ends it.

Only origins listed in `SANCTUM_STATEFUL_DOMAINS` get session auth.

### Native clients (future)
`POST /api/v1/auth/token` → `{ token }`, then send `Authorization: Bearer <token>`. `DELETE /api/v1/auth/token` revokes it.

### Google
Open `GET /api/v1/auth/social/google/redirect` in the browser (a top-level navigation, not fetch). After Google, the callback
signs the user in and redirects to `FRONTEND_URL/onboarding` (new user, or consent/onboarding missing) or to `FRONTEND_URL/`.
On failure it redirects to `FRONTEND_URL/sign-in?error=social_failed`.

## Endpoints (Phase 1)

| Method | Path | Auth | Body / query | Response |
|---|---|---|---|---|
| GET | `/sections` | — | — | `data: Section[]` |
| GET | `/industries` | — | — | `data: Industry[]` (active only, ordered) |
| POST | `/auth/register` | — | `name, email, password, consent: true, locale?, content_locales?, timezone?` | 201 `data: User`. SPA requests are signed in. Sends a verification email |
| POST | `/auth/login` | — (SPA) | `email, password, remember?` | `data: User` |
| POST | `/auth/logout` | ✓ | — | 204 |
| POST | `/auth/token` | — | `email, password, device_name` | 201 `{ token, user }` |
| DELETE | `/auth/token` | ✓ bearer | — | 204 |
| POST | `/auth/forgot-password` | — | `email` | 200 (same answer whether or not the account exists). Mail links to `FRONTEND_URL/reset-password?token=…&email=…` |
| POST | `/auth/reset-password` | — | `token, email, password, password_confirmation` | 200. Revokes API tokens |
| POST | `/auth/email/verification-notification` | ✓ | — | 202 |
| GET | `/auth/email/verify/{id}/{hash}` | signed URL | — | 302 → `FRONTEND_URL/?email_verified=1` |
| GET | `/auth/social/google/redirect` | — | — | 302 → Google |
| GET | `/auth/social/google/callback` | — | — | 302 → PWA |
| GET | `/me` | ✓ | — | `data: User` |
| PATCH | `/me` | ✓ | `name?, email? (+current_password if the account has one), locale?, content_locales?, timezone?, consent?: true, onboarding_completed?: bool` | `data: User`. A changed email becomes unverified and a new verification mail is sent |
| DELETE | `/me` | ✓ | `password` (or `confirmation: "DELETE"` for social-only accounts) | 204. Anonymizes and soft-deletes the account, removes tokens, bookmarks, follows, preferences, notifications and avatar |
| POST | `/me/avatar` | ✓ | multipart `avatar` (jpeg/png/webp, ≤5 MB, ≥64px) | `data: User` |
| DELETE | `/me/avatar` | ✓ | — | `data: User` |
| PUT | `/me/password` | ✓ | `current_password` (unless social-only), `password, password_confirmation` | 200. Revokes other API tokens |
| POST | `/me/export` | ✓ | — | 202. Queued; mail + in-app notification with a signed download link (48h) |
| GET | `/me/export/{user}/{file}` | signed URL | — | JSON file download |
| GET | `/me/industries` | ✓ | — | `data: { industry_ids: int[], notify_ids: int[] }` |
| PUT | `/me/industries` | ✓ | `industry_ids: int[]` (may be empty), `notify_ids?: int[]` (subset of `industry_ids`) | same as GET |
| GET | `/me/notification-preferences` | ✓ | — | `data: Preferences` |
| PUT | `/me/notification-preferences` | ✓ | any subset of the fields below | `data: Preferences` |

## Endpoints (Phase 2 — content)

| Method | Path | Auth | Query / body | Response |
|---|---|---|---|---|
| GET | `/feed/for-you` | ✓ | `cursor?` | `data: (ArticleCard\|EventCard)[]`, `meta.next_cursor`, `meta.fallback` (`"trending"` when the reader follows no industries) |
| GET | `/feed/trending` | — | — | Up to 10 `ArticleCard`, order = rank. Score = views + 3 × saves in the last 48h, topped up with the most-read articles of the last 14 days |
| GET | `/sections/{slug}/articles` | — | `industry?` (`a,b` or `industry[]=`), `cursor?` | `ArticleCard[]`, newest first. 404 for `events` |
| GET | `/industries/{slug}/feed` | — | `cursor?` | Articles newest first; first page has up to 3 upcoming events woven in at positions 2, 5, 8. `meta.industry` |
| GET | `/articles/{slug}` | — | — | `Article` (sanitized `body`, author, attachment, `share_url`) |
| GET | `/articles/{slug}/related` | — | — | Up to 6 `ArticleCard`, most shared industries first |
| POST | `/articles/{id}/view` | — | — | 204. Counted once per viewer per 30 min (session, or hashed IP+UA per day); 60/min per IP |
| GET | `/events` | — | `range?=upcoming\|week\|month`, `industry?`, `city?`, `online?`, `cursor?` | `EventCard[]` by start time. `upcoming` includes running events; `week`/`month` are Nicosia calendar periods. `meta.digest` = published Weekly/Monthly Events digest for the period (`{slug,title,intro}` or null) |
| GET | `/events/calendar` | — | `month?=YYYY-MM` | `{ month, timezone, days: [{ date, events: EventCard[] }] }`. Multi-day events appear on each day (max 14) |
| GET | `/events/{slug}` | — | — | `Event` (+ `registration_url`, `online_url`, `address`, `ics_url`, `share_url`) |
| GET | `/events/{slug}/ics` | — | — | `text/calendar` file (UTC times, RFC 5545 folding) |
| GET | `/digests` | — | `kind?=news\|events`, `cadence?=daily\|weekly\|monthly`, `cursor?` | `DigestCard[]` (published only, newest period first, `items_count`, `cover_url`) |
| GET | `/digests/latest` | — | `kind`, `cadence` (required) | `Digest`. 404 if none published |
| GET | `/digests/{slug}` | — | — | `Digest` with `items: [{ id, position, editor_note, is_highlighted, item: ArticleCard\|EventCard }]` in editor order. Unpublished/unreadable items are dropped; `is_highlighted` = item in an industry the reader follows |
| GET | `/search` | — | `q` (2–100 chars) | `{ articles: ArticleCard[≤10], events: EventCard[≤5] (upcoming first), industries: Industry[] }`. 30/min |
| GET | `/bookmarks` | ✓ | `type?=article\|event`, `cursor?` | Cards + `bookmarked_at`, newest first; unpublished items hidden |
| POST | `/bookmarks` | ✓ | `{ type, id }` | 201 (new) or 200 (already saved). 404 if not published |
| DELETE | `/bookmarks` | ✓ | `{ type, id }` | 204 (idempotent) |

## Endpoints (Phase 6 — notifications)

| Method | Path | Auth | Query / body | Response |
|---|---|---|---|---|
| GET | `/notifications` | ✓ | `cursor?` | `InboxNotification[]`, newest first; `meta.unread_count` |
| GET | `/notifications/unread-count` | ✓ | — | `data: { count }` (the bell badge polls this every 60 s and on push) |
| POST | `/notifications/{id}/read` | ✓ | — | 204. 404 for another reader's notification |
| POST | `/notifications/read-all` | ✓ | — | 204 |
| GET | `/push/public-key` | — | — | `data: { public_key }` (VAPID, not a secret) |
| POST | `/push/subscriptions` | ✓ | `{ endpoint, keys: { p256dh, auth }, content_encoding?: aes128gcm\|aesgcm }` | 201 (new device) or 200. The endpoint must be HTTPS on a known push service (FCM, Mozilla, Apple, Windows), since the server POSTs to it. A browser re-subscribed by another account moves to that account |
| DELETE | `/push/subscriptions` | ✓ | `{ endpoint }` | 204 (idempotent). Called on sign-out, so shared devices stop receiving the previous reader's pushes |

```jsonc
// InboxNotification: the same payload feeds the inbox and Web Push, already in the reader's UI language
{ "id": "uuid", "type": "digest_published|featured_article|event_reminder|campaign|data_export",
  "title": "Ημερήσιες Ειδήσεις: νέο τεύχος", "body": "Κορυφαίο θέμα: …", "url": "/digests/daily-news-2026-10-07",
  "read_at": null, "created_at": "…" }
// Web Push payload: { title, body, icon, badge, tag, lang, data: { url, type, campaign_id? } }, TTL 24 h (reminders 23 h)
```

### Content resources

```jsonc
// ArticleCard
{ "type": "article", "id": 1, "slug": "…", "title": "…", "excerpt": "…",
  "section": { "slug": "news", "name": "Ειδήσεις" },
  "primary_industry": { "slug": "fintech", "name": "FinTech", "color": "#00A5E6" },
  "industries": [{ "slug": "fintech", "name": "FinTech", "color": "#00A5E6", "is_primary": true }],
  "author": { "id": 1, "name": "Elena Vassiliou", "is_verified": true },
  "is_original": true, "is_featured": true, "published_at": "…", "reading_time_minutes": 4, "reads": 12400,
  "image": { "thumb": "…320²", "card": "…800×500", "hero": "…≤1600" },   // WebP; null when no image
  "locale": "el", "is_fallback": false, "is_bookmarked": false }

// EventCard
{ "type": "event", "id": 4, "slug": "…", "title": "…", "excerpt": "…", "starts_at": "…", "ends_at": "…",
  "timezone": "Asia/Nicosia", "is_online": false, "city": "Larnaca", "location_name": "…", "price_info": "Δωρεάν",
  "primary_industry": {…}, "industries": […], "is_featured": true, "image": {…}, "locale": "el", "is_fallback": false, "is_bookmarked": true }
```

### Search behaviour
MariaDB FULLTEXT over a `search_text` column holding both languages (`utf8mb4_unicode_ci`): accent- and case-insensitive
(`κυπρος` finds `Κύπρος`), every word must match, prefixes match (`καινοτ` → `καινοτομία`). Words under 3 characters
(e.g. `AI`, below MariaDB's minimum token size) are matched as whole words. Industries match by name in either language,
slug words or acronym (`AI` → Artificial Intelligence).

### Deviations from the brief

- **`POST /me/export`** instead of `GET`: it starts a background job, and a GET with side effects can be triggered by prefetching and link scanners.
- **Avatar** has its own `POST /me/avatar`, because PHP does not parse multipart bodies on `PATCH /me`.
- **No Laravel Scout.** Its database engine only does `LIKE` or plain FULLTEXT and can't handle prefixes or short words like "AI".
  `SearchService` queries MariaDB directly. Scout becomes worthwhile only with a dedicated engine (e.g. Meilisearch) later.
- **Industry filters use slugs** (`industry=fintech,ai`), so filters are readable in shareable URLs.

## Resources

```jsonc
// User (own account)
{ "id": 1, "name": "Anna", "email": "anna@example.com", "email_verified": true, "avatar_url": null,
  "role": "reader", "locale": "el", "content_locales": ["el","en"], "timezone": "Asia/Nicosia",
  "has_password": true, "needs_consent": false, "onboarded": true, "created_at": "2026-10-05T07:39:50+00:00" }

// Section
{ "id": 1, "slug": "news", "name": "Ειδήσεις", "has_articles": true, "sort_order": 10 }

// Industry — group ∈ finance_investment | deep_tech | digital_software | sectors | society_gov
{ "id": 1, "slug": "fintech", "name": "FinTech", "group": "finance_investment", "color": "#00A5E6", "image_url": null, "sort_order": 10 }

// Preferences (digests are opt-in)
{ "digest_news_daily": false, "digest_news_monthly": false, "digest_events_weekly": false,
  "digest_events_monthly": false, "event_reminders": true, "delivery_time": "08:00" }
```

In-app notifications use the shape `{ type, title, body, url }` in the `data` column (inbox endpoints arrive in Phase 6).
