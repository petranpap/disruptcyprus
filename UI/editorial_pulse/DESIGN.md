---
name: Editorial Pulse
colors:
  surface: '#faf8ff'
  surface-dim: '#d2d9f4'
  surface-bright: '#faf8ff'
  surface-container-lowest: '#ffffff'
  surface-container-low: '#f2f3ff'
  surface-container: '#eaedff'
  surface-container-high: '#e2e7ff'
  surface-container-highest: '#dae2fd'
  on-surface: '#131b2e'
  on-surface-variant: '#3e4850'
  inverse-surface: '#283044'
  inverse-on-surface: '#eef0ff'
  outline: '#6e7881'
  outline-variant: '#bdc8d1'
  surface-tint: '#00658e'
  primary: '#00658e'
  on-primary: '#ffffff'
  primary-container: '#00a5e6'
  on-primary-container: '#00374f'
  inverse-primary: '#85cfff'
  secondary: '#ba0035'
  on-secondary: '#ffffff'
  secondary-container: '#e21e49'
  on-secondary-container: '#fffbff'
  tertiary: '#006398'
  on-tertiary: '#ffffff'
  tertiary-container: '#40a2e7'
  on-tertiary-container: '#003655'
  error: '#ba1a1a'
  on-error: '#ffffff'
  error-container: '#ffdad6'
  on-error-container: '#93000a'
  primary-fixed: '#c7e7ff'
  primary-fixed-dim: '#85cfff'
  on-primary-fixed: '#001e2e'
  on-primary-fixed-variant: '#004c6c'
  secondary-fixed: '#ffdada'
  secondary-fixed-dim: '#ffb3b6'
  on-secondary-fixed: '#40000c'
  on-secondary-fixed-variant: '#920028'
  tertiary-fixed: '#cce5ff'
  tertiary-fixed-dim: '#93ccff'
  on-tertiary-fixed: '#001d31'
  on-tertiary-fixed-variant: '#004b73'
  background: '#faf8ff'
  on-background: '#131b2e'
  surface-variant: '#dae2fd'
typography:
  headline-hero:
    fontFamily: Playfair Display
    fontSize: 36px
    fontWeight: '700'
    lineHeight: 44px
    letterSpacing: -0.02em
  headline-hero-mobile:
    fontFamily: Playfair Display
    fontSize: 28px
    fontWeight: '700'
    lineHeight: 34px
    letterSpacing: -0.015em
  headline-lg:
    fontFamily: Playfair Display
    fontSize: 24px
    fontWeight: '700'
    lineHeight: 30px
    letterSpacing: -0.01em
  headline-md:
    fontFamily: Playfair Display
    fontSize: 20px
    fontWeight: '600'
    lineHeight: 26px
  headline-sm:
    fontFamily: Playfair Display
    fontSize: 18px
    fontWeight: '600'
    lineHeight: 24px
  body-lg:
    fontFamily: Source Serif 4
    fontSize: 18px
    fontWeight: '400'
    lineHeight: 28px
  body-md:
    fontFamily: Source Serif 4
    fontSize: 16px
    fontWeight: '400'
    lineHeight: 25px
  body-sm:
    fontFamily: Inter
    fontSize: 14px
    fontWeight: '400'
    lineHeight: 20px
  label-lg:
    fontFamily: Inter
    fontSize: 13px
    fontWeight: '600'
    lineHeight: 18px
    letterSpacing: 0.02em
  label-md:
    fontFamily: Inter
    fontSize: 11px
    fontWeight: '600'
    lineHeight: 16px
    letterSpacing: 0.06em
  label-sm:
    fontFamily: Inter
    fontSize: 10px
    fontWeight: '500'
    lineHeight: 14px
    letterSpacing: 0.04em
rounded:
  sm: 0.25rem
  DEFAULT: 0.5rem
  md: 0.75rem
  lg: 1rem
  xl: 1.5rem
  full: 9999px
spacing:
  gutter: 1rem
  gutter-sm: 0.75rem
  margin: 1rem
  margin-tablet: 1.5rem
  space-xs: 0.25rem
  space-sm: 0.5rem
  space-md: 1rem
  space-lg: 1.5rem
  space-xl: 2rem
---

## Brand & Style

This design system drives a forward-looking digital publication experience centered on Mediterranean innovation, startup culture, tech ventures, and deep-dive analysis. The brand personality blends Mediterranean warmth and bold journalistic integrity with sleek mobile-first utility. The UI channels the clarity of curated digital newsstands like Apple News and Flipboard while preserving high-craft print editorial principles.

The experience evokes confidence, intellectual curiosity, and sharp focus. The design direction unites modern editorial minimalism with vibrant digital accents: generous margins, warm off-white newsprint canvases, razor-sharp hairline dividers, rich typographic hierarchy, and tactfully applied saturated accents extracted from the brand identity.

## Colors

The color palette is derived directly from the brand insignia:
- **Primary (`#00A5E6` / `#0284C7`)**: The signature Mediterranean cyan blue, representing clarity, technology, and active highlights. Used for primary interactive touchpoints, reading progress indicators, and active navigation states.
- **Secondary (`#E11D48`)**: A crimson coral red conveying urgency, breaking scoops, live coverage badges, and curated spotlight tags.
- **Neutral Canvas (`#FAFAF8` light mode / `#121316` dark mode)**: A textured, unbleached digital paper tone that reduces glare and honors tactile print magazines.
- **Text & Structure (`#0F172A`)**: Deep slate charcoal, offering softer contrast than harsh pure black (`#000000`) for comfortable sustained reading.

Tints at 8% and 12% opacity provide gentle category tag backgrounds without overpowering adjacent photography or editorial titling.

## Typography

The typographic balance bridges high-literary tradition and digital ergonomics:
- **Headlines (`Playfair Display`)**: Employs classical transitional contrast for lead features, cover stories, and card titles. Use tight tracking on larger scales to echo luxury magazine covers.
- **Article Body (`Source Serif 4`)**: Chosen for long-form reading comfort, rhythmic proportions, and clear optical characteristics at mobile scale.
- **Metadata & UI (`Inter`)**: Neutral, highly legible sans-serif for timestamps, bylines, category chips, navigation controls, and live tickers. Category badges are set in uppercase with slight tracking (`0.06em`) for immediate visual hierarchy.

## Layout & Spacing

Designed primarily for mobile portrait viewports with a 4-column fluid mobile grid adapting to an 8-column layout on small tablets:
- **Safe Margins**: Outer canvas padding stays strictly at `1rem` (16px) on phones to maximize screen space for editorial spreads, widening to `1.5rem` on larger portrait displays.
- **Rhythm & Flow**: Story cards, headline blocks, and media modules adhere to an 8px vertical rhythm. Related metadata sits `0.5rem` from titles, while full editorial sections separate via `2rem`.
- **Breakpoints**:
  - `mobile` (< 640px): 4-column flow, single-story hero cards, edge-padded horizontal carousels.
  - `tablet` (640px – 1024px): 8-column split view (main story 5 columns, secondary feed 3 columns).

## Elevation & Depth

Visual hierarchy emphasizes editorial flat layers with ambient light diffusion:
- **Surface Elevation**: Cards sit on pure `#FFFFFF` over the subtly tinted `#FAFAF8` base canvas. In dark mode, `#1A1D24` surfaces contrast against the `#121316` background.
- **Shadows**: Minimal and delicate. Standard cards use an ambient drop shadow tinted with deep slate: `0 4px 20px -2px rgba(15, 23, 42, 0.05)`. Floating action bars and audio players use `0 8px 30px -4px rgba(15, 23, 42, 0.12)`.
- **Borders & Dividers**: Editorial separation relies on ultra-fine `0.5px` hairline rules in `rgba(15, 23, 42, 0.08)` (or `rgba(255, 255, 255, 0.08)` in dark mode) instead of heavy dropped shadows.
- **Frosted Translucency**: Sticky top headers and bottom navigation bars incorporate an iOS-style frosted glass treatment (`backdrop-filter: blur(20px)` with `85%` alpha surface).

## Shapes

The interface balances sharp architectural editorial layouts with warm, touch-friendly rounded elements:
- **Story Cards**: Default curvature is `0.875rem` to `1rem` (14px–16px), giving hero image containers a finished, app-native tactile feel.
- **Category Pills & Badges**: Fully pill-shaped (`9999px`) to create distinction against the squared geometry of full-bleed images and story cards.
- **Action Controls**: Buttons and interactive chips feature `0.75rem` (12px) corners.

## Components

- **Story & Magazine Cards**:
  - *Hero Cover Card*: Full-bleed 16:9 or 4:5 image with a gradient scrim (`linear-gradient(180deg, transparent 40%, rgba(15, 23, 42, 0.92) 100%)`). White serif title overlaid at bottom, secondary coral badge at top left.
  - *Standard Feed Card*: 16px corner radius, crisp white container, 0.5px hairline stroke, 80x80px rounded square thumbnail on the right, serif headline and metadata stack on the left.
- **Category Chips & Badges**:
  - Soft tinted background using 10% opacity fills of the primary blue or coral red, matching 100% saturated text.
  - `padding: 4px 10px`, font size `11px`, bold uppercase with tracked letter spacing.
- **Action Buttons**:
  - *Primary*: Vibrant Cyan (`#00A5E6`), white bold text, pill or 12px rounded geometry, subtle active press scale (`0.98`).
  - *Secondary / Outline*: 1px hairline border using `rgba(15, 23, 42, 0.15)` with `#0F172A` text.
- **Navigation & Audio Dock**:
  - Bottom bar with frosted translucent finish, 3-tab simplicity (Today, Discover, Saved).
  - Floating mini-player for audio articles with a 0.5px top border and subtle cyan playback progress line.
- **Dividers & Lists**:
  - Clean list rows with 0.5px inset dividers, terminating 16px before screen edges.