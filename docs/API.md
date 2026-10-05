# API — Disrupt Cyprus v1

Base path: `/api/v1`. In production the PWA calls it **same-origin** (`https://app.disruptcyprus.com/api/v1`).
Status: Phase 1 endpoints. Content endpoints arrive in Phase 2, along with generated OpenAPI docs.

## Conventions

| Topic | Rule |
|---|---|
| Language | `Accept-Language: el` or `en` selects translated fields and messages. Anything else falls back to `el`. Responses carry `Content-Language` and `Vary: Accept-Language` |
| Envelope | Single resources and lists: `{ "data": … }`. Lists are cursor-paginated from Phase 2 |
| Errors | `{ "message": string, "code": string, "errors"?: { field: [messages] } }` |
| Error codes | `validation_failed` 422 · `unauthenticated` 401 · `forbidden` 403 · `not_found` 404 · `csrf_mismatch` 419 · `too_many_requests` 429 · `http_error` (other 4xx) · `server_error` 500 |
| Rate limits | 120 req/min per user or IP. Login: 5/min per email+IP and 20/min per IP. Register/forgot/reset: 10/min per IP. Data export: 1/hour per user |
| Caching | Public taxonomy GETs: `Cache-Control: public, max-age=300` + `ETag` (send `If-None-Match` to get a 304) |
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

### Deviations from the brief

- **`POST /me/export`** instead of `GET`: it starts a background job, and a GET with side effects can be triggered by prefetching and link scanners.
- **Avatar** has its own `POST /me/avatar`, because PHP does not parse multipart bodies on `PATCH /me`.

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
