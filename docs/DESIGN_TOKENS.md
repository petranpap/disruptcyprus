# Design Tokens — "Editorial Pulse" (Disrupt Cyprus)

Source of truth for the visual language. Derived from `UI/editorial_pulse/DESIGN.md` and the four
Stitch exports in `UI/` (read-only). Where the exports contradict each other or fail accessibility,
this file records the resolution — **this file wins over `UI/`** once approved.

Implementation: Phase 4 turns this file into `web/src/styles/tokens.json` → a small Node script
generates `web/src/styles/theme.css` (Tailwind v4 `@theme` + CSS variables for light/dark). The same
CSS variables are reused by the Blade landing page. Token names keep the Material-3 names used by the
Stitch exports (`surface`, `on-surface`, `primary-container`…) so markup from `UI/` maps 1:1.

---

## 1. Color

All colors are CSS variables switched by `html.dark` (system preference by default, manual override
persisted). Tailwind classes reference the variables, never raw hex.

### 1.1 Brand

| Token | Value | Use |
|---|---|---|
| `brand-cyan` | `#00A5E6` | Logo mark, active tab indicator, progress bars, icons, check pills, large accents |
| `brand-crimson` | `#E21E49` | "Disrupt Original" badge, unread dot, trending #1 |
| `brand-ink` | `#0F172A` | Text/shadow tint reference |

### 1.2 Semantic colors — light / dark

| Token | Light | Dark | Notes |
|---|---|---|---|
| `background` | `#FAFAF8` | `#121316` | Light: DESIGN.md "unbleached paper" (exports mix `#faf8ff` and `#FAFAF8`; we pick `#FAFAF8`) |
| `surface` | `#FAFAF8` | `#121316` | App canvas |
| `surface-container-lowest` | `#FFFFFF` | `#1A1D24` | **Cards** |
| `surface-container-low` | `#F2F3FF` | `#1E222A` | Inputs, subtle fills |
| `surface-container` | `#EAEDFF` | `#232730` | Image placeholders, hover fills |
| `surface-container-high` | `#E2E7FF` | `#2A2F39` | Progress tracks, icon wells |
| `surface-container-highest` | `#DAE2FD` | `#323844` | Pressed states |
| `on-surface` | `#131B2E` | `#EEF0FF` | Primary text |
| `on-surface-variant` | `#3E4850` | `#B4BCC6` | Excerpts, secondary text |
| `outline` (meta text) | `#5F6A73` ⚠ | `#9AA4AD` ⚠ | Timestamps, bylines. Changed from `#6E7881` for AA (see §1.4) |
| `outline-variant` | `#BDC8D1` | `#3A404B` | Input borders, unselected rings |
| `hairline` | `rgba(15,23,42,.08)` | `rgba(255,255,255,.08)` | 0.5px dividers and card strokes |
| `primary` (text/links/active) | `#00658E` | `#00A5E6` | Tab labels, links, "View all" |
| `primary-action` (filled button bg) | `#0077B6` ⚠ | `#0077B6` ⚠ | White label. See §1.4 |
| `primary-action-hover` | `#00658E` | `#0089CF` | |
| `on-primary` | `#FFFFFF` | `#FFFFFF` | |
| `primary-fixed` (tint bg) | `#C7E7FF` | `rgba(0,165,230,.14)` | Category chip background |
| `on-primary-fixed-variant` | `#004C6C` | `#85CFFF` | Category chip text |
| `secondary` (crimson text) | `#BA0035` | `#FF5A7A` ⚠ | Kicker labels like "LATEST BRIEFING" |
| `secondary-container` (badge bg) | `#E21E49` | `#E21E49` | White label, bold |
| `secondary-fixed` (tint bg) | `#FFDADA` | `rgba(226,30,73,.16)` | "Disrupt Original" chip in reader |
| `error` | `#BA1A1A` | `#FFB4AB` | |
| `error-container` | `#FFDAD6` | `#93000A` | |
| `success` | `#1B7F4C` | `#5FD39A` | Not in design; added for toasts/"saved offline" |
| `scrim-hero` | `linear-gradient(180deg, transparent 40%, rgba(15,23,42,.92) 100%)` | `linear-gradient(to top, #121316, rgba(18,19,22,.5), transparent)` | Hero cards |
| `glass` | `rgba(250,250,248,.85)` + `blur(20px)` | `rgba(18,19,22,.85)` + `blur(20px)` | Sticky header / bottom nav ⚠ dark export used `#283044` (inverse-surface) — inconsistent with canvas, replaced |

The rest of the M3 palette from `DESIGN.md` (`tertiary*`, `inverse-*`, `*-fixed-dim`) is carried
over unchanged for light mode and available as tokens, but the screens barely use it.

### 1.3 Industry colors

Each industry has a `color` field (admin-editable). Used **only** as a 3px dot/left stripe on tiles and
chips, never as text color, so contrast is not affected. Seeds use a 12-hue rotation derived from the
brand cyan/crimson axis.

### 1.4 Accessibility deviations from the exports (need approval)

Measured WCAG 2.2 contrast ratios:

| Pair in the export | Ratio | Fix | New ratio |
|---|---|---|---|
| White on `#00A5E6` (every filled button) | **2.79** ✗ | Filled buttons use `#0077B6` | 4.87 ✓ |
| `#00A5E6` text on light canvas | **2.65** ✗ | Light-mode text/links use `#00658E`; cyan stays for non-text accents | 6.13 ✓ |
| `#6E7881` meta on dark card `#1A1D24` | **3.75** ✗ | `#9AA4AD` | 6.66 ✓ |
| `#6E7881` meta on light canvas | 4.27 ✗ (10–11px text) | `#5F6A73` | 5.25 ✓ |
| `#E21E49` crimson text on dark card | **3.63** ✗ | `#FF5A7A` | 5.62 ✓ |
| White on `#E21E49` badge | 4.65 ✓ | keep | — |

Brand cyan keeps its role wherever it isn't small text: logo, tab underline, progress bars, icons,
selection rings and check pills.

---

## 2. Typography

### 2.1 Families and Greek coverage

Greek is the **default** locale, so every family must render Greek. Checked against the Google Fonts
CSS API:

| Role | Design font | Greek? | Resolution |
|---|---|---|---|
| Headlines | Playfair Display | **No** (latin, latin-ext, cyrillic, vietnamese only) | Stack `"Playfair Display", "Noto Serif Display", serif`. The browser picks Noto Serif Display glyph-by-glyph for Greek. It has similar high-contrast didone proportions. Same weights (600/700, italic 600) |
| Article body | Source Serif 4 | Yes | Keep |
| UI / meta | Inter | Yes (+ greek-ext) | Keep |

- **Self-hosted** via `@fontsource-variable/*` (no Google Fonts CDN): GDPR-safe (no visitor IP sent
  to Google), cacheable by the service worker, and `unicode-range` subsets so Greek pages load only
  Greek glyphs.
- `font-display: swap`; preload Inter (latin + greek) and the Playfair 700 latin subset only.
- Greek uppercase: kicker/label text is uppercase. Greek uppercase must drop accents (ΤΕΧΝΟΛΟΓΙΑ, not
  ΤΕΧΝΟΛΟΓΊΑ). We set `lang` on `<html>` and on content blocks (whose language can differ from the UI),
  and use an `upper()` helper that strips tonos. Browser `text-transform` handling of this varies.

### 2.2 Scale (from DESIGN.md, verified against the exports)

| Token | Family | Size / line-height | Weight | Tracking | Used for |
|---|---|---|---|---|---|
| `headline-hero` | Playfair | 36 / 44 | 700 | -0.02em | ≥640px hero & reader title |
| `headline-hero-mobile` | Playfair | 28 / 34 | 700 | -0.015em | Hero card, reader title, page titles |
| `headline-lg` | Playfair | 24 / 30 | 700 | -0.01em | Secondary hero, "Related Stories" |
| `headline-md` | Playfair | 20 / 26 | 600 | 0 | Section headers ("Trending Today"), wordmark, pull quotes (italic) |
| `headline-sm` | Playfair | 18 / 24 | 600 | 0 | Card titles, tile titles |
| `body-lg` | Source Serif 4 | 18 / 28 (reader: line-height 1.75) | 400 | 0 | Article body |
| `body-md` | Source Serif 4 | 16 / 25 | 400 | 0 | Excerpts, subtitles |
| `body-sm` | Inter | 14 / 20 | 400 | 0 | UI copy, form text |
| `label-lg` | Inter | 13 / 18 | 600 | 0.02em | Buttons, tabs, author name |
| `label-md` | Inter | 11 / 16 | 600 | 0.06em | Badges (uppercase), chips, links |
| `label-sm` | Inter | 10 / 14 | 500 | 0.04em | Meta, bottom-nav labels, kickers |

Reader font-size control ("A+"): 3 steps for the body: 18px / 20px (line-height 1.85) / 22px. Persisted per device.
Drop cap: Playfair 700, 3.75rem, line-height 0.8, color `primary`, first paragraph only, disabled when
the paragraph starts with a non-letter.

---

## 3. Spacing & layout

| Token | Value | Notes |
|---|---|---|
| `space-xs` | 4px | |
| `space-sm` | 8px | Meta ↔ title gap |
| `space-md` / `margin` / `gutter` | 16px | Phone side margins, card gaps |
| `gutter-sm` | 12px | Grid gaps (tiles use 14px) |
| `space-lg` / `margin-tablet` | 24px | |
| `space-xl` | 32px | Between feed sections |

- 8px vertical rhythm.
- **Responsive website, app feel on phones** (decided 2026-10-07, replaces the 480px app column):
  - `< 1024px` (phones, tablets): app layout — glass header with a scrolling section-tab row, content full width
    with 16px gutters, fixed bottom navigation.
  - `≥ 1024px` (`lg`): website layout — top bar with logo, inline section navigation, search field (≥1280px, icon below),
    language, saved, notifications and account/sign-in buttons; content centred in `max-w-content` (1200px, 32px gutters);
    site footer (sections, company links, language); no bottom navigation.
  - Content widths: `content` 1200px (feeds; Phase 5 adds a 320px right sidebar on `lg`), `narrow` 768px (onboarding, forms,
    settings), `reading` 720px (article reader). Auth screens on desktop: split view (brand panel + centred form).
  - Sheets become centred modals on `lg`.
- Safe areas: `viewport-fit=cover`; header gets `padding-top: env(safe-area-inset-top)`, bottom nav and
  floating docks get `padding-bottom: max(8px, env(safe-area-inset-bottom))`.
- Tap targets ≥ 44×44px (the export's 32px back button and 18px bookmark icons get padded hit areas, with unchanged visuals).

## 4. Radii

The exports disagree with DESIGN.md's `rounded` table (the Tailwind config in the exports redefines
`lg`/`xl`). The values below are what the screens **render**, as semantic tokens:

| Token | Value | Used for |
|---|---|---|
| `radius-card` | 16px | Story cards, topic tiles, trending cards, compact rows |
| `radius-control` | 12px | Buttons, inputs, thumbnails, inline image cards |
| `radius-hero-bottom` | 20px | Reader hero image bottom corners (top: 12px) |
| `radius-tag` | 4px | Square-ish tags on images / trending category tag |
| `radius-pill` | 9999px | Badges, chips, audio pill, avatars |
| `radius-logo` | 8px (sm) / 12px (lg) | "#" squircle |

## 5. Elevation & effects

| Token | Light | Dark |
|---|---|---|
| `shadow-card` | `0 4px 20px -2px rgba(15,23,42,.05)` | `0 4px 20px -2px rgba(0,0,0,.30)` |
| `shadow-float` | `0 8px 30px -4px rgba(15,23,42,.12)` | `0 8px 30px -4px rgba(0,0,0,.50)` |
| `shadow-dock-top` | `0 -8px 30px -4px rgba(15,23,42,.12)` | `0 -8px 30px -4px rgba(0,0,0,.5)` |
| `stroke-card` | 0.5px `hairline` | 1px `rgba(255,255,255,.05)` (hover `.10`) |
| `glow-tab` | none | `0 0 8px rgba(0,165,230,.6)` under active tab (from export) |

## 6. Motion

- Press: `scale(0.98)` (cards 0.99, icon buttons 0.95), 150ms ease-out.
- Image hover zoom 1.05 over 300–500ms (pointer devices only).
- Skeleton shimmer 1.2s. Pull-to-refresh indicator matches the export's "Updated just now" pill.
- `prefers-reduced-motion: reduce` disables zoom, shimmer, smooth scroll and the spinner rotation.

## 7. Iconography

- **Material Symbols Outlined** (the set used throughout `UI/`), weight 400, optical size 24, `FILL 0`
  by default, `FILL 1` for active/selected (active nav item, saved bookmark, verified mark, play).
- Shipped as tree-shaken **SVG components** (`@material-symbols/svg-400`), not the ~3 MB variable icon font.
- Sizes: 24 (nav), 22 (header actions), 20 (inline actions), 16–18 (meta), 14 (chip icons).
- Brand marks (Google "G") as inline SVG, original colors.

## 8. Brand mark

- Logo (`UI/fb_img_1791010307996.jpg/screen.png`, 200×200 raster): italic "#" in a cyan rounded square,
  wordmark "Disrupt" crimson + "Cyprus" cyan in a **geometric sans**, not Playfair.
- The Stitch exports instead draw the wordmark in Playfair Display. Until we get a vector logo, the app
  uses the Stitch treatment (Playfair wordmark, "#" squircle) to stay consistent with the screens. If an
  SVG logo is provided, it replaces both.

## 9. Component inventory (from `UI/`)

| Component | Source screen | Key specs |
|---|---|---|
| `AppHeader` (glass) | home | Logo left; search + bell (crimson unread dot) right |
| `SectionTabs` | home | Horizontal scroll, label-lg, active = `primary` + 2.5px cyan underline (glow in dark) |
| `IndustryChips` | **new** | Pill chips, label-md, tint bg when active, sits under tabs |
| `HeroCard` | home | 4:3 (16:9 ≥640), scrim, crimson/cyan badge top-left, hero-mobile title, 2-line excerpt, meta + bookmark row |
| `StandardCard` | home | 16:10 image with tag overlay, headline-sm 2 lines, meta + bookmark |
| `CompactCard` | home | 80×80 thumb left, kicker, 2-line title, meta + bookmark |
| `TrendingCard` + carousel | home | 260px wide, italic `#n` rank (1 crimson, 2 cyan, 3+ outline), tag, "12.4k Reads" |
| `SectionHeader` | home | headline-md + "View all ›" link |
| `Badge` (Original / category) | home, article | Pill, uppercase label-md |
| `TopicTile` | onboarding | Image tile, scrim, kicker + headline-sm, check pill, 2px cyan ring when selected |
| `StepHeader` + `BottomDock` | onboarding | Step label + progress track; counter + primary CTA |
| `ReaderHeader` | article | Back, brand mark, A+, share, bookmark; 2.5px reading-progress bar |
| `AuthorByline` | article | 44px avatar, name + verified, title · date · read time |
| `ListenPill` | article | Play circle + "Listen N min" |
| `PullQuote`, `InlineFigure`, `DropCap` | article | Rich-text renderers for editor HTML |
| `RelatedCarousel` | article | 288px cards, kicker, title, read time → |
| `BottomNav` (glass) | home | Home / Explore / Saved / Profile; active FILL 1 + `primary` |
| `Button` primary / outline / social | welcome | 12px radius, py-3.5, press scale |
| `PreviewCard` | welcome | Glassy mini story row |
