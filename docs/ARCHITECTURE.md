# Architecture — Disrupt Cyprus

Status: **Phase 0 draft**. Items marked ❓ are waiting for product-owner confirmation.

## 1. System overview

```
                 ┌─────────────────────────── app.disruptcyprus.com ───────────────────────────┐
 Browser / PWA ──┤  /            → static React PWA (Vite build, served by Nginx)               │
                 │  /api/v1/*    → Laravel (PHP-FPM)        same origin → no CORS,              │
                 │  /sanctum/*   → Laravel                  first-party httpOnly cookies        │
                 └──────────────────────────────────────────────────────────────────────────────┘
                 ┌──────────────────────────── disruptcyprus.com ───────────────────────────────┐
 Search engines, │  /                 → Blade landing (GR default, /en)                          │
 social previews,│  /a/{slug}, /e/{slug}, /d/{slug} → Blade share pages (OG tags, full text)   │
 editors         │  /admin            → Filament                                                │
                 │  /privacy, /terms, /sitemap.xml                                               │
                 └──────────────────────────────────────────────────────────────────────────────┘
 Laravel ── MariaDB 10.11 (data, Scout database engine, cache, queue, sessions)
         └─ queue worker (systemd): notifications, web push, digests, exports
         └─ scheduler (cron, Asia/Nicosia): digest drafts, event reminders, scheduled publishing
```

One Laravel codebase serves both hosts. The PWA is a separate Vite project deployed as static files.

### 1.1 Auth decision (confirms the brief, with one refinement)

- **Sanctum SPA cookie auth**, as proposed. Refinement: serve the API **under the PWA's own origin**
  (`app.disruptcyprus.com/api`, Nginx path routing) instead of `api.` on a sibling subdomain. This gives:
  - no CORS preflights, and the session/XSRF cookies are host-only and first-party;
  - no exposure to iOS Safari ITP / third-party-cookie rules in installed PWAs;
  - the same setup in development: the Vite dev server proxies `/api` and `/sanctum` to Nginx.
- Session cookies: `HttpOnly`, `Secure`, `SameSite=Lax`, 30-day remember-me. CSRF via `/sanctum/csrf-cookie` + `X-XSRF-TOKEN`.
- `POST /api/v1/auth/token` (+ `DELETE`) issues/revokes personal access tokens for a future native app.
  It sits behind the same rate limits and is unused by the PWA.
- Google: Socialite redirect flow on `app.` (`/api/v1/auth/social/google/redirect` → Google →
  callback → session established → redirect to `/onboarding` or `/`). Apple later.
- Filament on `disruptcyprus.com/admin` keeps its own web session (separate host cookie). Editors log in separately there.

### 1.2 SEO / sharing decision

- **Blade share pages** at `disruptcyprus.com/a/{slug}` (articles), `/e/{slug}` (events), `/d/{slug}`
  (digests). They render the full article in a light Editorial Pulse layout with OG/Twitter tags,
  `hreflang` (el/en), JSON-LD `NewsArticle` / `Event`, and an "Open in app" CTA →
  `app.disruptcyprus.com/articles/{slug}`.
- The PWA's share button shares the **Blade URL**, so link previews work everywhere. No SSR in the SPA.
- `sitemap.xml` lists the share pages and the landing page.

### 1.3 Slugs

One slug per article/event/digest (not per locale). It is generated from the EN title, or from the
transliterated Greek title if only Greek exists, and stays stable once published. URLs then stay the
same across languages and routing stays simple; the page language follows the user's settings.

## 2. Information architecture

- Top tabs: **For you | News | Startups | Research | Investors | Events**. Guests see "Trending" in place of "For you".
- Under each section tab: horizontally scrolling **industry chips**. "All" comes first, then the user's followed industries,
  then "+ More" (opens a sheet with all 33). Selection is kept in the URL (`?industry=fintech,ai`).
- Sub-tabs: News → **Latest | Daily | Monthly**. Events → **Upcoming | This week | This month**, with a list/calendar toggle on "This month".
  - "Daily"/"Monthly" show the latest published News digest of that cadence (with a "previous editions" link).
  - "This week"/"This month" show the published Weekly/Monthly Events digest for the current period. If none is
    published yet, they fall back to a plain date-range query, so the tab is never empty.
- Bottom nav: **Home | Explore | Saved | Profile** (matches the home export; DESIGN.md's 3-tab text is outdated).
- Header: logo, search (→ `/explore?focus=search`), bell with unread badge (→ `/notifications`).

## 3. Screens found in `UI/`

The folder is named `UI/` (uppercase), not `ui/`. It is treated as read-only.

| # | Folder / file | Screen | Mode |
|---|---|---|---|
| 1 | `UI/editorial_pulse/DESIGN.md` | Design system "Editorial Pulse" (tokens + prose) | — |
| 2 | `UI/disrupt_cyprus_welcome/` (`code.html`, `screen.png`) | Welcome / auth entry | Light |
| 3 | `UI/onboarding_topic_selection/` | Onboarding step 2 — topic tiles (12 generic topics) | Light |
| 4 | `UI/home_feed_for_you_dark_mode/` | Home "For You" feed | Dark |
| 5 | `UI/article_detail_disrupt_original/` | Article reader (Disrupt Original) | Light |
| 6 | `UI/fb_img_1791010307996.jpg/screen.png` | Brand logo, 200×200 raster (folder name ends in `.jpg`, contains a PNG) | — |

No light-mode home and no dark-mode reader/welcome/onboarding exist. Their counterpart modes are derived from tokens.

## 4. Conflicts between `UI/` and the brief

| # | In `UI/` | Brief | Proposed resolution |
|---|---|---|---|
| C1 | Top tabs: Technology, Startups, Economy, Politics, Culture | 5 sections + For you | Replace labels, keep the tab component exactly |
| C2 | 12 generic topic tiles (h-48 image tiles) | 33 industries | Keep the tile design and make tiles shorter (h-32, 3:2). Add a search/filter field above the grid and group tiles into 5 clusters (Finance & Investment, Deep Tech, Digital & Software, Sectors, Society & Gov) so 33 tiles remain scannable. The counter shows "N of 33" |
| C3 | Card meta shows external sources ("Cyprus Mail · 3h ago", "InBusiness") | No external content | Meta = author name · relative time. "Disrupt Original" in crimson when `is_original` |
| C4 | Reader shows **Applauds (1.4k)** and **Comments (48)** | Not in brief | ✅ Dropped. Bar = **Share + Save** |
| C5 | Welcome shows an **Apple** button | Apple Sign-In later | Hide Apple. Google becomes a full-width button. The slot comes back later |
| C6 | Welcome copy: "news and stories shaping Cyprus… unfiltered independent journalism", "Ad-Free" | Startup/innovation focus | ✅ Rewritten (copy below). "Ad-Free" removed |
| C7 | Brand-cyan buttons with white text (2.79:1) and other low-contrast pairs | Accessibility target | ✅ Adjusted tokens approved, see DESIGN_TOKENS §1.4 |
| C8 | Playfair Display headlines | Greek default locale | Playfair has no Greek. Greek glyphs fall back to Noto Serif Display (DESIGN_TOKENS §2.1) |
| C9 | DESIGN.md says 3-tab bottom nav (Today, Discover, Saved) and a floating audio mini-player | Brief: 4-tab nav | 4 tabs per the home export. The mini-player appears only while "Listen" is playing and sits above the bottom nav |
| C10 | Onboarding is "Step 2 of 3" with a "Skip" action | Min 3 industries | "Skip" is shown but leads to a confirm sheet ("Your feed will show trending stories"). Skipping is allowed; For you then falls back to trending |
| C11 | Wordmark in Playfair (exports) vs geometric sans (real logo) | — | Use the exports' version until an SVG logo exists |

### 4.1 Welcome copy (C6)

Greek uses the informal "εσύ", as is usual for consumer tech apps.

| Element | EN | GR |
|---|---|---|
| Edition line | CYPRUS EDITION | ΕΚΔΟΣΗ ΚΥΠΡΟΥ |
| Tagline | STARTUPS • VENTURES • RESEARCH | STARTUPS • ΕΠΕΝΔΥΣΕΙΣ • ΕΡΕΥΝΑ |
| Badge | Personalized • Daily briefing • Events calendar | Εξατομικευμένο • Καθημερινή ενημέρωση • Ημερολόγιο εκδηλώσεων |
| Headline | Cyprus startups and innovation, curated for you. | Startups και καινοτομία στην Κύπρο, επιλεγμένα για εσένα. |
| Subtitle | Funding rounds, founders, research and events from Cyprus and the Eastern Mediterranean, filtered by the industries you follow. | Χρηματοδοτήσεις, ιδρυτές, έρευνα και εκδηλώσεις από την Κύπρο και την Ανατολική Μεσόγειο, με βάση τους κλάδους που ακολουθείς. |
| Preview kicker | DAILY BRIEFING | ΗΜΕΡΗΣΙΑ ΕΝΗΜΕΡΩΣΗ |
| Primary CTA | Create an account | Δημιουργία λογαριασμού |
| Secondary CTA | Sign in | Σύνδεση |
| Social | Continue with Google | Συνέχεια με Google |
| Guest | Continue as guest | Συνέχεια ως επισκέπτης |
| Legal | By signing up, you agree to our Terms & Privacy Policy | Με την εγγραφή σου αποδέχεσαι τους Όρους Χρήσης και την Πολιτική Απορρήτου |

## 5. Route → screen → API map

`G` = works for guests, `A` = requires auth (guests get a sign-up sheet on protected actions).

| Route | Screen | Design source | Access | API endpoints |
|---|---|---|---|---|
| `/welcome` | Welcome | welcome ✓ | G | `GET /feed/trending?limit=1` (preview card) |
| `/sign-in` | Sign in | **new** (welcome style) | G | `GET /sanctum/csrf-cookie`, `POST /auth/login`, `GET /auth/social/google/redirect` |
| `/sign-up` | Sign up (+ GDPR consent) | **new** | G | `POST /auth/register` |
| `/forgot-password` | Forgot password | **new** | G | `POST /auth/forgot-password` |
| `/reset-password` | Reset password | **new** | G | `POST /auth/reset-password` |
| `/onboarding/account` | Step 1 — account (name, UI & content language) | **new** (step header from onboarding) | A | `PATCH /me` |
| `/onboarding/industries` | Step 2 — industries | onboarding ✓ (adapted, C2) | A | `GET /industries`, `PUT /me/industries` |
| `/onboarding/notifications` | Step 3 — notifications, digests, install hint | **new** | A | `GET /push/public-key`, `POST /push/subscriptions`, `PUT /me/notification-preferences` |
| `/` | Home — For you (guest: Trending) | home ✓ | G/A | `GET /feed/for-you` (A) or `GET /feed/trending`, `GET /notifications/unread-count`, `POST/DELETE /bookmarks` |
| `/news` | News: Latest / Daily / Monthly | home ✓ + **new** sub-tabs | G | `GET /sections/news/articles?industry[]`, `GET /digests/latest?kind=news&cadence=daily\|monthly` |
| `/startups`, `/research`, `/investors` | Section feed | home ✓ | G | `GET /sections/{slug}/articles?industry[]&cursor` |
| `/events` | Events: Upcoming / This week / This month + calendar | **new** | G | `GET /events?range=…&industry[]&city&online`, `GET /events/calendar?month=`, `GET /digests/latest?kind=events&cadence=weekly\|monthly` |
| `/digests/:slug` | Digest page | **new** | G | `GET /digests/{slug}` |
| `/articles/:slug` | Article reader | article ✓ | G | `GET /articles/{slug}`, `POST /articles/{id}/view`, `GET /articles/{slug}/related`, bookmarks |
| `/events/:slug` | Event detail | **new** | G | `GET /events/{slug}`, `GET /events/{slug}/ics`, bookmarks |
| `/explore` | Explore: industries grid + search | **new** | G | `GET /industries`, `GET /search?q=`, `GET /feed/trending` |
| `/explore/:industry` | Industry feed (articles + events) | **new** | G | `GET /industries/{slug}/feed` |
| `/saved` | Saved: Articles / Events (+ offline badge) | **new** | A | `GET /bookmarks?type=article\|event` |
| `/notifications` | Inbox | **new** | A | `GET /notifications`, `POST /notifications/{id}/read`, `POST /notifications/read-all` |
| `/profile` | Profile hub | **new** | A (guest: sign-up CTA) | `GET /me`, `GET /me/industries` |
| `/settings/industries` | Followed industries + per-industry push | **new** (reuses tiles/chips) | A | `GET/PUT /me/industries` |
| `/settings/notifications` | Digest + reminder prefs, delivery time, push on/off for this device | **new** | A | `GET/PUT /me/notification-preferences`, push subscription endpoints |
| `/settings/language` | UI language, content languages | **new** | G (local) / A (synced) | `PATCH /me` |
| `/settings/appearance` | System / Light / Dark | **new** | G (local) | — |
| `/settings/account` | Name, email, avatar, password | **new** | A | `PATCH /me`, `PUT /me/password` |
| `/settings/privacy` | Data export, delete account, policies | **new** | A | `GET /me/export`, `DELETE /me` |
| `/offline` | Offline fallback (SW navigation fallback) | **new** | G | — (cached saved items from IndexedDB) |
| `/dev/components` | Component gallery (dev builds only) | — | dev | MSW fixtures |
| global | Install banner / iOS sheet, update toast, sign-up sheet, empty/error/skeleton states | **new** | — | — |

## 6. Screens missing from `UI/` — design plan

All of them are built only from the tokens and the components in DESIGN_TOKENS §9. No new visual primitives unless listed.

| Screen / state | Composition |
|---|---|
| Sign in / Sign up / Forgot / Reset | Welcome layout (ambient cyan/crimson blur, logo, serif headline). Form fields: `surface-container-lowest`, 12px radius, hairline border, cyan focus ring. Sign-up adds a consent checkbox linking Terms/Privacy (required) and a password strength hint |
| Onboarding step 1 | Step header (1 of 3) + name, UI language segmented control (ΕΛ / EN), content-language checkboxes |
| Onboarding step 3 | Step header (3 of 3). Toggle list rows for the 4 digests and event reminders. Delivery-time picker. "Enable notifications" card that triggers the browser permission prompt on tap. On iOS outside standalone mode: an inline card "Install the app to get notifications" with the Share → Add to Home Screen steps |
| Section sub-tabs | Secondary segmented control (pill, `surface-container-low` track, white active pill) below the industry chips |
| Events list | Rows grouped by day: sticky day header (label-md uppercase). Event card with a **date chip** (56px square, 12px radius, month label-sm crimson + day headline-md), title headline-sm, time · city/"Online" meta, industry kicker, bookmark |
| Calendar view | Month grid (7 cols, 44px cells), dots under days with events (cyan; crimson if one is saved), tapping a day filters the list below. Prev/next month header in headline-md |
| Event detail | Hero image (reader hero style), badges, serif title, info card with rows (calendar icon: when + timezone; pin: venue + "Open in Maps"; globe: online link; ticket: price). Sticky bottom dock: **Register** (primary) + add-to-calendar (.ics) + save + share |
| Digest page | Masthead: kicker ("DAILY BRIEFING" / "ΗΜΕΡΗΣΙΑ ΕΝΗΜΕΡΩΣΗ") in crimson, date range, serif title, intro in body-md. Numbered item list (trending-rank numerals) using compact cards. Editor notes shown as an italic Source Serif callout under an item. Monthly News is grouped by section headers. "Highlighted for you" puts items from followed industries first, with a cyan "For you" chip |
| Notifications inbox | List rows: type icon in a tinted 40px circle, title (label-lg), body (body-sm, 2 lines), relative time. Unread rows get a cyan dot and a `surface-container-low` fill. "Mark all read" in the header. Grouped Today / Earlier |
| Saved | Tabs Articles / Events, compact cards. Downloaded-for-offline badge (check-circle icon, `success`) |
| Profile | Avatar + name + email, followed-industries chip row, settings list rows (icon, label, chevron) with 0.5px inset dividers per DESIGN.md "Dividers & Lists" |
| Settings sub-pages | Same list-row pattern. Toggles: 44×26 switch, cyan when on. Danger zone (delete account) with an `error` outline button + confirm sheet requiring the password or the typed word |
| Explore | Search field (pill, 44px) at the top. Industries grid as compact tiles (2 cols, 96px tall, image + scrim + name) or chip mode. Search results grouped Articles / Events / Industries |
| Install prompt | Android/desktop: bottom sheet card (logo, "Add Disrupt Cyprus to your home screen", Install / Not now), shown after 3 article reads or the first save, never in the first session minute, snoozed 14 days on dismiss. iOS: sheet with illustrated steps (Share icon → "Add to Home Screen") |
| Update prompt | Snackbar above the bottom nav: "New version available — Refresh" |
| Empty states | Centered Material icon in a 64px tinted circle, headline-sm, body-sm, one action. Saved-empty, no-results, no-events-this-week, notifications-empty, offline |
| Error state | Same layout, `error` icon, "Try again" |
| Offline | Cloud-off icon, "You're offline", list of saved articles available offline |
| Skeletons | Shapes match each card variant exactly, using `surface-container`/`surface-container-high` shimmer |
| Guest gate | Bottom sheet: "Create a free account to save stories and personalize your feed" + Sign up / Sign in |

These are written specs for now and get built in code (and shown on `/dev/components`) in Phases 4–5.
Optional: they could also be generated in Stitch first for visual sign-off.

## 7. Backend structure (Laravel 13, Filament 5)

```
backend/app/
  Enums/            ArticleStatus, DigestKind, DigestCadence, ContentLocale …
  Models/           User, Section, Industry, Author, Article, Event, Digest, DigestItem, Bookmark, PushSubscription, NotificationPreference …
  Http/Controllers/Api/V1/   thin controllers
  Http/Requests/    Form Requests (all validation)
  Http/Resources/   API Resources (locale-aware, available_locales, fallback flag)
  Policies/
  Services/         FeedService, TrendingService, DigestGenerator, ReadingTimeCalculator, HtmlSanitizer, IcsBuilder, DataExporter
  Notifications/    DigestPublished, FeaturedArticleInIndustry, EventReminder, ManualCampaign (channel-agnostic: database + webpush)
  Console/Commands/ digests:generate {kind} {cadence} {--date=}, reminders:events
  Filament/         Resources, Widgets, Pages
```

- **Localization**: `spatie/laravel-translatable` JSON columns. A middleware sets the app locale from
  `Accept-Language` (`en` | `el`, default `el`). Resources return the requested locale, falling back to the other locale
  when the user's `content_locales` allow it, plus `locale` and `is_fallback` per item.
- **Search**: Scout database engine over a denormalized `search_text` column per article/event (both
  locales, HTML stripped, `utf8mb4_unicode_ci` collation, FULLTEXT index). Searching translatable JSON columns directly
  would be case- and accent-sensitive, so "κυπρος" would not match "Κύπρος". Phase 2 tests verify this.
- **Feed ranking** (`FeedService`): `score = recencyDecay(age, half-life 36h) × (1 + 0.25·featured + 0.15·original) × industryMatch`.
  Events only if `starts_at ≤ now + 14d`. Section diversity is enforced by interleaving (no more than 3 consecutive items from one section).
  Trending = views + 3 × bookmarks in the last 48h, from an `article_daily_stats`/`view_events` rollup.
  The view counter is throttled per session/IP hash and article.
- **Digests**: `DigestGenerator` is idempotent on `(kind, cadence, period_start)`. A draft counts as "edited" once an
  editor has touched it (`edited_at` set by Filament saves), and an edited draft is never regenerated automatically.
  Periods are computed in Asia/Nicosia (DST-safe) and stored in UTC.

## 8. Web app structure (React 19, Vite 8, TS strict)

```
web/src/
  app/          router, providers (QueryClient, i18n, theme), layouts (AppShell, AuthLayout, OnboardingLayout)
  api/          fetch client (credentials: 'include', XSRF, Accept-Language), zod schemas, query keys
  features/     auth, onboarding, feed, sections, articles, events, digests, explore, saved, notifications, settings, install, push
  components/   design-system primitives (Button, Card variants, Tabs, Chips, Sheet, Skeleton, Icon …)
  i18n/         en.json, el.json
  lib/          dates (Intl, Asia/Nicosia aware), greekUpper, tts (SpeechProvider interface + WebSpeechProvider)
  sw/           service worker (injectManifest: Workbox routes + push + notificationclick)
  styles/       tokens.json → theme.css (generated)
```

- Offline saved items: when an item is bookmarked, the SW stores its JSON (`/articles/{slug}`) and hero image in
  dedicated caches. The Saved screen also reads an IndexedDB index so it works with no network.
- State: TanStack Query for server state; Zustand for theme, reader font size, install-prompt engagement, and the guest's
  local UI language.

## 9. Assumptions (proceeding unless you say otherwise)

1. Production hosts: `app.disruptcyprus.com` (PWA + API) and `disruptcyprus.com` (landing, share pages, admin). ✅ confirmed.
2. Latest stable versions as of today: **Laravel 13**, **Filament 5**, PHP 8.4, React 19.3, Vite 8, React Router 8, TanStack Query 5, Tailwind 4.3, vite-plugin-pwa 2. If a key package isn't compatible yet, I'll pin and note it.
3. Default UI language is Greek when the browser isn't `en*`. `users.locale` defaults to `el`.
4. **Digest timing**: Daily News draft generated at **06:00** for the window `[yesterday 06:00, today 06:00)`; Weekly Events Sunday **18:00** for Mon–Sun of the coming week;
   Monthly drafts on the 1st at **06:00**. Generation runs at the same times for everyone; editor publishing is manual.
5. **Digest notification timing**: when an editor publishes, each user is notified at their `delivery_time` (in their timezone) that day.
   If that time has already passed, they are notified immediately. Monthly digests use the same rule.
6. "This week" = Monday–Sunday in Asia/Nicosia. "This month" = calendar month.
7. A digest's items are articles only (News) or events only (Events). An article/event can appear in several digests.
8. Event reminders go to users who saved the event, if `event_reminders` is on, ~24h before (an hourly job picks events
   starting in [23h, 24h)). Events saved less than 24h before the start get no reminder.
9. Featured-article push: sent when an article is first published with `is_featured`, to followers of its industries with `notify = true`.
   At most one push per user per article, and at most 3 such pushes per user per day.
10. View counts are anonymous (hashed IP + UA + day), so no consent is needed. Only strictly necessary cookies are used (session, XSRF), so there is no cookie banner. Analytics are out of scope.
11. Account deletion is a hard delete of personal data (user row anonymized/soft-deleted then purged after 30 days by a scheduled job, plus immediate removal of push subscriptions, bookmarks, socials, avatar, tokens).
12. Research attachments are PDFs ≤ 20 MB, public.
13. Demo images: seeders ship a small local set of royalty-free placeholder images (no network calls during seeding).
14. Package manager: npm (pnpm isn't installed).
15. TTS: `speechSynthesis` exists in most browsers, but Greek voices are only reliably present on Android Chrome, iOS/macOS Safari and Windows (Edge). The button is hidden when no voice matches the content language.
