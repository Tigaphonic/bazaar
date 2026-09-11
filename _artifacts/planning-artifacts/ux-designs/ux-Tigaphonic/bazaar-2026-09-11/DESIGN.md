---
name: Bazaar
description: Default visual design system for Bazaar's Filament-based staff/admin dashboard — a compact, desktop-first, dual-theme brand-neutral admin skin that installs shipping the package and that individual client installs can theme-override.
status: final
sources:
  - imports/admin-dashboard.html
  - imports/admin-finance-cs.html
  - imports/admin-katalog.html
  - imports/admin-order.html
  - imports/admin-pengaturan-sistem.html
  - imports/admin-promo.html
updated: 2026-09-12
colors:
  # ---- Light theme — extracted verbatim from imports/*.html :root tokens
  # (identical across all 6 reference mockups; kept as CSS-var-name-matching
  # kebab keys for direct traceability back to source).
  primary: '#00609e'
  primary-700: '#00527f'
  primary-50: '#eef6fb'
  primary-100: '#d9ecf7'
  bluegray: '#5a6a82'
  charcoal: '#333333'
  soft: '#f7f7f7'
  border: '#e3e6ea'
  white: '#ffffff'
  ink: '#22262b'
  muted: '#5c6875'
  success-bg: '#e8f5ec'
  success-text: '#1e7d3c'
  success-ring: '#bfe3cb'
  warn-bg: '#fdf3e2'
  warn-text: '#a15c00'
  warn-ring: '#f3d9a3'
  info-bg: '#e8f1fb'
  info-text: '#00609e'
  info-ring: '#bcd9ef'
  danger-bg: '#fbeaea'
  danger-text: '#b0292b'
  danger-ring: '#f0c2c3'
  neutral-bg: '#eef0f2'
  neutral-text: '#5b6472'
  neutral-ring: '#dbdfe4'
  done-bg: '#eef1f5'
  done-text: '#3d4655'
  done-ring: '#d7dce3'
  live-dot: '#2fae5c'
  notification-dot: '#e6544f'
  quota-warn: '#d98a1f'
  hairline: '#f1f2f4'
  border-emphasis: '#d7dbdf'
  surface-hover: '#f4f6f7'
  border-dashed: '#c6ccd1'
  dot-pending: '#cfd5da'
  # ---- Dark theme — DESIGNED NET-NEW (reference mockups are light-only).
  # Same hue families as the light tokens above, lightened/desaturated per
  # standard dark-UI practice for contrast and eye comfort — not inversions.
  primary-dark: '#3d94c9'
  primary-700-dark: '#7ec2e8'
  primary-50-dark: '#0d2436'
  primary-100-dark: '#123049'
  bluegray-dark: '#93a1b1'
  charcoal-dark: '#eef1f4'
  soft-dark: '#10151b'
  border-dark: '#262f39'
  white-dark: '#181f27'
  ink-dark: '#e7ebef'
  muted-dark: '#7e8b99'
  success-bg-dark: '#123423'
  success-text-dark: '#4fcf82'
  success-ring-dark: '#1f5c3b'
  warn-bg-dark: '#3a2c10'
  warn-text-dark: '#f0b459'
  warn-ring-dark: '#5c4318'
  info-bg-dark: '#12283b'
  info-text-dark: '#5fb3e8'
  info-ring-dark: '#1f4460'
  danger-bg-dark: '#3a1616'
  danger-text-dark: '#f0797b'
  danger-ring-dark: '#5c2426'
  neutral-bg-dark: '#232a32'
  neutral-text-dark: '#a7b0ba'
  neutral-ring-dark: '#333c46'
  done-bg-dark: '#232933'
  done-text-dark: '#aab3c0'
  done-ring-dark: '#333c48'
  live-dot-dark: '#3ecb74'
  notification-dot-dark: '#f0797b'
  quota-warn-dark: '#e3a94a'
  hairline-dark: '#20262d'
  border-emphasis-dark: '#333d47'
  surface-hover-dark: '#1c232b'
  border-dashed-dark: '#3a4650'
  dot-pending-dark: '#3d4750'
typography:
  # fontFamily fallback stacks are the same for every heading / body role
  # (see Typography section below) — omitted per-token here to stay terse.
  heading-display:
    fontFamily: 'Poppins'
    fontSize: 21px
    fontWeight: '700'
    lineHeight: '1.3'
  heading-section:
    fontFamily: 'Poppins'
    fontSize: 14.5px
    fontWeight: '700'
    lineHeight: '1.3'
  heading-stat:
    fontFamily: 'Poppins'
    fontSize: 26px
    fontWeight: '700'
    lineHeight: '1.2'
  label:
    fontFamily: 'Poppins'
    fontSize: 12px
    fontWeight: '700'
    lineHeight: '1.4'
  nav-item:
    fontFamily: 'Poppins'
    fontSize: 13.5px
    fontWeight: '500'
    lineHeight: '1.4'
  body:
    fontFamily: 'Mulish'
    fontSize: 14px
    fontWeight: '400'
    lineHeight: '1.5'
  body-sm:
    fontFamily: 'Mulish'
    fontSize: 13px
    fontWeight: '400'
    lineHeight: '1.5'
  caption:
    fontFamily: 'Mulish'
    fontSize: 12px
    fontWeight: '400'
    lineHeight: '1.4'
  overline:
    fontFamily: 'Mulish'
    fontSize: 11px
    fontWeight: '700'
    lineHeight: '1.3'
    letterSpacing: 0.02em
  overline-group:
    fontFamily: 'Poppins'
    fontSize: 12px
    fontWeight: '700'
    lineHeight: '1.3'
    letterSpacing: 0.04em
  pill-text:
    fontFamily: 'Mulish'
    fontSize: 11px
    fontWeight: '700'
    lineHeight: '1.2'
rounded:
  sm: 7px
  md: 8px
  lg: 12px
  full: 9999px
  DEFAULT: 8px
spacing:
  '1': 2px
  '2': 4px
  '3': 6px
  '4': 8px
  '5': 10px
  '6': 12px
  '7': 14px
  '8': 16px
  '9': 18px
  '10': 20px
  '11': 22px
  '12': 28px
  gutter: 16px
  card-padding: 18px
  row-padding-x: 14px
  row-padding-y: 11px
  content-padding: '26px 28px 40px'
  sidebar-width: 250px
  topbar-height: 62px
components:
  sidebar:
    width: '{spacing.sidebar-width}'
    background: '{colors.white}'
    background-dark: '{colors.white-dark}'
    border-right: '{colors.border}'
  topbar:
    height: '{spacing.topbar-height}'
    background: '{colors.white}'
    border-bottom: '{colors.border}'
  card:
    background: '{colors.white}'
    border: '{colors.border}'
    radius: '{rounded.lg}'
    padding: '{spacing.card-padding}'
    shadow: '0 1px 2px rgba(16,24,40,.04), 0 2px 6px rgba(16,24,40,.06)'
  stat-card:
    radius: '{rounded.lg}'
    icon-radius: 10px
    number-typography: '{typography.heading-stat}'
  button-primary:
    background: '{colors.primary}'
    foreground: '{colors.white}'
    radius: '{rounded.md}'
    padding: '9px 15px'
    typography: '{typography.body-sm}'
  button-secondary:
    background: '{colors.white}'
    foreground: '{colors.charcoal}'
    border: '{colors.border-emphasis}'
    radius: '{rounded.md}'
  button-danger:
    background: '{colors.white}'
    foreground: '{colors.danger-text}'
    border: '{colors.danger-ring}'
    radius: '{rounded.md}'
  button-ghost:
    background: 'transparent'
    foreground: '{colors.bluegray}'
  input:
    background: '{colors.white}'
    border: '{colors.border}'
    radius: '{rounded.md}'
    padding: '9px 11px'
    typography: '{typography.body-sm}'
  pill:
    radius: '{rounded.full}'
    padding: '3px 9px'
    typography: '{typography.pill-text}'
    border-width: 1px
  table:
    header-typography: '{typography.overline}'
    row-padding-y: '{spacing.row-padding-y}'
    row-padding-x: '{spacing.row-padding-x}'
    row-divider: '{colors.hairline}'
  avatar:
    radius: '{rounded.full}'
    background: '{colors.primary-100}'
    foreground: '{colors.primary-700}'
  toggle-switch:
    track-off: '{colors.border}'
    track-on: '{colors.primary}'
    knob: '{colors.white}'
    radius: '{rounded.full}'
  # ---- Net-new (not in reference), same token system
  modal:
    background: '{colors.white}'
    radius: '{rounded.lg}'
    overlay: 'rgba(16,24,40,.45)'
    shadow: '0 24px 60px rgba(16,24,40,.22)'
  toast:
    radius: '{rounded.md}'
    shadow: '0 1px 2px rgba(16,24,40,.04), 0 2px 6px rgba(16,24,40,.06)'
  tabs:
    radius: '{rounded.md}'
    active-indicator: '{colors.primary}'
  pagination:
    radius: '{rounded.md}'
    active-background: '{colors.primary-50}'
    active-foreground: '{colors.primary-700}'
  alert-banner:
    radius: '{rounded.md}'
    variants:
      danger: '{colors.danger-bg}/{colors.danger-ring}/{colors.danger-text}'
      warn: '{colors.warn-bg}/{colors.warn-ring}/{colors.warn-text}'
      info: '{colors.info-bg}/{colors.info-ring}/{colors.info-text}'
  countdown-indicator:
    typography: '{typography.caption}'
    radius: '{rounded.full}'
    normal: '{colors.bluegray}'
    urgent: '{colors.warn-text}'
    expired: '{colors.danger-text}'
  attribute-reconcile-banner:
    background: '{colors.warn-bg}'
    border: '{colors.warn-ring}'
    radius: '{rounded.md}'
    diff-columns: 2
  notification-dropdown:
    width: 340px
    radius: '{rounded.lg}'
    shadow: '0 1px 2px rgba(16,24,40,.04), 0 2px 6px rgba(16,24,40,.06)'
    row-divider: '{colors.hairline}'
---

## Brand & Style

Bazaar is a headless, installable Laravel/Filament package, not a single client's product — so this is Bazaar's own **default admin design system**: a neutral, professional operations-tool aesthetic that any Brand Store install gets out of the box, and that individual clients can theme-override later through Filament's standard theming mechanism (custom CSS/panel theme swapping the token values below, same structure). Nothing in this document should read as belonging to one brand.

The posture is *quietly competent back-office software*: a deep, trustworthy blue as the single chromatic anchor, calm neutral grays for structure, and a small, disciplined set of semantic colors that carry state (paid vs. pending vs. disputed) so staff can scan a screen without reading every cell. Density is a deliberate choice, not a compromise — staff live in tables and queues all day, and the compact 13–14px rhythm keeps more of the operational picture on screen at once, deliberately over generous whitespace.

**Sourced from** (visual tone reference, one consistent design system verified across all six — identical tokens, components, and icon sprite; this document wins on any conflict with them going forward):
`imports/admin-dashboard.html`, `imports/admin-finance-cs.html`, `imports/admin-katalog.html`, `imports/admin-order.html`, `imports/admin-pengaturan-sistem.html`, `imports/admin-promo.html`.

Two things the reference mockups carried that are deliberately **dropped** here: the placeholder "ArmyWatch" brand name/persona voice (Bazaar is brand-neutral by design), and the hardcoded "Owner-only" approval gate (Bazaar's maker-checker is a dynamic permission bundle — see Do's and Don'ts).

Bilingual EN/ID is a hard requirement at the interaction layer (EXPERIENCE.md), and it has a visual consequence here: no chrome element is sized to fit only one language's string length.

## Colors

**Primary — `{colors.primary}` (`#00609e`) light / `{colors.primary-dark}` (`#3d94c9`) dark.** The one chromatic brand color. Used for the active nav rail indicator, primary buttons, links, and focus rings. Its light-mode "deep" variant `{colors.primary-700}` (`#00527f`) is used for text sitting on `{colors.primary-50}` tint (for example, active nav label); in dark mode this relationship inverts in *luminance* while preserving the hue family — `{colors.primary-700-dark}` (`#7ec2e8`) is lighter than `{colors.primary-dark}`, because it sits as text on a very dark tint fill rather than a light one. Never used decoratively — no gradients, no secondary brand hue.

**Neutrals — structure, not decoration.** `{colors.white}` / `{colors.white-dark}` is the *raised surface* role (sidebar, topbar, cards). `{colors.soft}` / `{colors.soft-dark}` is the *base canvas* role (page background behind cards). `{colors.border}` / `{colors.border-dark}` are hairlines between structural regions. `{colors.charcoal}` / `{colors.charcoal-dark}` is heading/primary-emphasis text; `{colors.ink}` / `{colors.ink-dark}` is default body text; `{colors.bluegray}` / `{colors.bluegray-dark}` and `{colors.muted}` / `{colors.muted-dark}` are secondary/meta text (breadcrumbs, sub-labels, placeholder text) — two very close grays kept distinct in source for legacy reasons; treat them as interchangeable "secondary text."

**Semantic pairs (bg / text / ring), six families — `success`, `warn`, `info`, `danger`, `neutral`, `done`.** Each is a tinted background + saturated text + a slightly-darker-than-bg ring for the pill border, used identically across status pills, alert banners, and stat-card icon chips. This is the vocabulary every status enum in the product (Payment Status, Order Status, Shipping Status, approval state) maps onto — see Components → Status Pills for the exact mapping. Dark-mode pairs invert the bg/text luminance relationship (dark, low-saturation bg; light, saturated text) rather than literally inverting hue, per standard dark-theme practice — this keeps a `danger` pill legibly red in both themes without ever reading as a flat color swap.

**Small extras**, each with a distinct role from the six semantic families above: `{colors.live-dot}` (`#2fae5c`) marks the sidebar "Live · Brand Store" environment indicator; `{colors.notification-dot}` (`#e6544f`) is the unread-notification badge on the bell icon; `{colors.quota-warn}` (`#d98a1f`) is the promo quota-bar fill once a voucher is near exhaustion (distinct from, and louder than, `{colors.warn-text}`).

**Accessibility floor.** Bazaar targets the internal-tool standard confirmed for this product (clear contrast, basic keyboard support), not a formal WCAG audit — but "clear contrast" is still a concrete target: body text, headings, and pill text against their surfaces are chosen to clear WCAG 2.1 AA's 4.5:1 (normal text) / 3:1 (large text, 18px+/bold 14px+) ratios as *directional* guidance in both themes, not a certified/audited guarantee. The dark-mode semantic pairs above were lightened specifically to hold this floor against the dark canvas rather than being a literal color inversion. → `mockups/order-detail-dark.html` is a full dark-theme proof-of-concept exercising nearly every `-dark` token on one real screen.

Avoid: introducing a second chromatic brand hue, using semantic colors decoratively (a `success` green button that isn't confirming a positive state), or overriding `primary` per-install without going through Filament's theming path.

## Typography

Two families, extracted verbatim from the reference: **Poppins** for all headings, nav labels, buttons, and data emphasis (`font-family:'Poppins','Segoe UI',system-ui,-apple-system,sans-serif`), **Mulish** for body copy, table cells, and hint text (`font-family:'Mulish','Segoe UI',system-ui,-apple-system,sans-serif`). Base document size is 14px at 1.5 line-height; the type ramp above it is compact and mostly compressed into a 11–14.5px band, with `{typography.heading-stat}` (26px) as the one deliberately large moment reserved for dashboard KPI numbers.

Weight carries most of the hierarchy at this size, not size itself: `{typography.label}` and `{typography.heading-section}` are both 700-weight Poppins at very close sizes to body text — the jump from `{typography.body}` to a heading is felt as "bolder and Poppins," not "bigger."

`{typography.overline}` (11px, uppercase, `letterSpacing: 0.02em`) is reserved for table column headers; `{typography.overline-group}` (12px, uppercase, `letterSpacing: 0.04em`) is reserved for settings-page group labels ("Timer", "Shipping") — don't use overline styling for anything else — it reads as a structural marker.

**Bilingual constraint (EN/ID):** Indonesian strings run meaningfully longer than their English equivalents for the same concept ("Menunggu Approval" vs. "Pending Approval" is close, but many labels are not). No button, pill, or nav item may be a fixed pixel width — every one of them wraps with `white-space:nowrap` plus horizontal padding that grows with content, exactly as the reference already does (`.btn`, `.pill` both use `white-space:nowrap` with padding, never a fixed `width` except where explicitly `justify-content:center` for a full-bleed action button).

## Layout & Spacing

**Shell.** `{spacing.sidebar-width}` (250px) fixed sidebar + fluid content region, `{spacing.topbar-height}` (62px) topbar, `{spacing.content-padding}` (26px top / 28px sides / 40px bottom) content inset. This shell is constant across every admin screen.

**Spacing scale.** A tight, mostly-even 2px-stepped scale (`{spacing.1}`…`{spacing.12}`, 2px→28px) rather than a loose 4/8 system — this is what produces the compact density: `{spacing.card-padding}` (18px) inside every card, `{spacing.gutter}` (16px) between cards/grid columns, `{spacing.row-padding-y}`/`{spacing.row-padding-x}` (11px/14px) inside table rows. **This density is intentional and should not be "opened up"** — staff explicitly prioritize more information per screen over larger touch/breathing room.

**Grid patterns**, all extracted from the reference: a 3-column stat-card grid on Dashboard, a 2-column `grid-2` for paired queue cards, a 2-column `form-grid` for data-entry forms (full-width fields use a `span-2` override), and a 1.4fr/1fr `detail-grid` for order-detail (timeline + side panel).

**Responsive breakpoints — designed net-new.** The reference is fixed-width desktop only (1440px canvas, zero media queries), reflecting the confirmed desktop-first/primary scope. Bazaar adds three breakpoints as a nice-to-have layer, not a primary target:
- **Desktop (≥1280px)** — the design as specified above, unchanged.
- **Tablet (768–1279px)** — sidebar collapses to a 64px icon-only rail (labels in tooltip on hover/focus); stat-grid and grid-2 drop to 2 and 1 columns respectively; table `overflow-x:auto` (already present in reference) becomes the primary mitigation for wide tables rather than reflow.
- **Mobile (<768px)** — sidebar becomes an off-canvas drawer triggered from the topbar; all multi-column grids collapse to 1 column; `admin-table` rows reflow into stacked card rows (label/value pairs) instead of horizontal scroll; interactive tap targets (buttons, row actions, pills that are also controls) get a mobile-only minimum height of 40px even though visual density elsewhere stays compact — this is the one deliberate density exception, for touch accuracy, not preference.

## Elevation & Depth

Two levels only, both extracted from the reference — Bazaar does not use elevation as a general hierarchy device (structure comes from `{colors.border}` hairlines and `{colors.soft}`/`{colors.white}` tone contrast first).

- **Resting (`{components.card.shadow}`)** — `0 1px 2px rgba(16,24,40,.04), 0 2px 6px rgba(16,24,40,.06)`. Applied to every card, stat-card, and dropdown-like surface. Barely-there; it reads as "this is a distinct surface," not "this is important."
- **Overlay (`{components.modal.shadow}`)** — `0 24px 60px rgba(16,24,40,.22)`, paired with a `{components.modal.overlay}` scrim (`rgba(16,24,40,.45)`) behind it. Reserved for the single elevated layer in the system: modal/dialog. This shadow value is lifted directly from the reference's device-frame chrome (the mockup's own "floating browser window" treatment) — repurposed here as the real overlay depth level since the reference had no in-app modal to source it from.

No hover-elevation, no shadow-on-focus — interactive states are communicated by background-tint change (`.nav-item:hover{background:{colors.surface-hover}}`) and border-color change, never by lifting an element.

## Shapes

A 3-step radius scale plus full-round, extracted from the reference's two named CSS variables (`--r-lg:8px`, `--r-xl:12px`) and the smaller ad hoc values it uses for icon-sized containers:

- `{rounded.sm}` (7px) — the smallest interactive squares: 28×28 row-action icon buttons.
- `{rounded.md}` (8px) — the default: buttons, inputs, table thumbnails, filter chips, sidebar nav items. This is the reference's `--r-lg`.
- `{rounded.lg}` (12px) — cards, stat-cards, panels, modals. This is the reference's `--r-xl`.
- `{rounded.full}` (9999px) — pills, avatars, toggle-switch tracks, courier/grade chips, notification dots.

One noted nuance kept from the reference rather than forced onto the 4-step scale: icon badges sized to their icon footprint (the 32px sidebar brand badge at 9px radius, the 36px stat-card icon chip at 10px radius) round slightly tighter than `{rounded.md}` — a proportional micro-adjustment, not a fifth scale step. New components should round to the nearest scale step; only icon-sized square badges get this treatment, and only because the reference consistently does it across all six files.

## Components

Reference-extracted components keep their source anatomy; components marked **Net-new** were designed for this document in the same token language (radius scale, shadow levels, semantic color roles) because the reference mockups don't show them. Not every component gets a `components.*` frontmatter object: compositional components reused across many surfaces (shell chrome, cards, buttons, inputs, pills, and anything a Key Flow names by its token path) do; single-anatomy leaf controls don't need a parallel frontmatter object to be equally binding — those are tabled together under Leaf Controls below rather than each getting its own paragraph.

**Sidebar Nav** — `{components.sidebar}`. Brand mark + env indicator (`{colors.live-dot}` dot + "Live · Brand Store" label) at top, scrollable nav item list in `{typography.nav-item}`, user footer (avatar + name + role) pinned at bottom. Active item: `{colors.primary-50}` fill, `{colors.primary-700}` text/icon, 3px `{colors.primary}` bar on the left edge. Items may carry a count badge (`{colors.warn-bg}`/`{colors.warn-text}` pill) for queue counts.

**Topbar** — `{components.topbar}`. Search input (icon-left, `{colors.soft}` fill) flex-grows on the left; icon-button cluster (each with optional `{colors.notification-dot}` badge) + user chip (avatar + name + chevron) on the right.

**Staff Notification Bell / Notification Dropdown** — `{components.notification-dropdown}`. Bell icon-button in the Topbar cluster, `{colors.notification-dot}` badge when unread items exist. Click opens a 340px-wide dropdown panel anchored top-right: `{rounded.lg}`, resting shadow, `{colors.hairline}` row dividers. Each row: type icon + one-line summary + relative timestamp, unread rows get a `{colors.info-bg}` tint until opened. Empty state: single centered `{typography.caption}` line, no icon (the panel itself is small enough that a full Empty State treatment would overpower it). Footer link "Lihat semua" is plain text, not a button.

**Card** — `{components.card}`. The base content container everywhere: white surface, resting shadow, `{rounded.lg}`, `{spacing.card-padding}` padding. A `section-head` (title + optional subtitle + optional trailing action/pill) is the standard card-top pattern.

**Stat Card** — `{components.stat-card}`. Icon chip (36×36, 10px radius, tinted by semantic role) + trailing delta label, then a large `{typography.heading-stat}` number, then a `{typography.caption}` description line. Used in 3-up grids on landing/dashboard-style screens.

**Data Table** — `{components.table}`. `{typography.overline}` column headers, `{typography.body-sm}` cells, `{colors.hairline}` row dividers (lighter than the structural `{colors.border}`), `{spacing.row-padding-y}`/`{spacing.row-padding-x}` cell padding. Row states: a full-row tint (`{colors.success-bg}` or `{colors.danger-bg}`) for rows needing attention (for example, a disputed order), never a border-only highlight. Row actions are a right-aligned cluster of `{rounded.sm}` icon buttons.

**Status Pills** — `{components.pill}`. Full-round, bordered (ring color), 11px bold text, always `white-space:nowrap`. One pill per status value; never combine two statuses in one pill. Exact enum → semantic-family mapping, kept consistent with how the reference already colors its own status values:

| Status field | Value | Pill family |
|---|---|---|
| **Payment Status** | Pending | `warn` |
| | Paid | `success` |
| | Expired | `danger` |
| | Denied | `danger` |
| | Cancelled | `neutral` |
| | Refunded | `done` |
| | Disputed | `danger` (+ alert icon, per reference) |
| **Order Status** | Processing | `info` |
| | Cancelled | `danger` |
| | Return Requested | `warn` |
| | Returned | `done` |
| | Completed | `success` |
| **Shipping Status** | Not Shipped | `neutral` |
| | Shipped | `info` |
| | Delivered | `success` |
| | AWB Voided | `danger` |
| | Returned to Warehouse | `done` |

**Approval & Maker-Checker Pill Labeling** — the Approve/Reject control itself is a plain `{components.button-primary}` / `{components.button-danger}` pair (see Button below); only the *status pill* labeling rule is specified here, and it uses the same pill component but **must never hardcode a role name** — Approval Role is a dynamic permission bundle (PRD), not a fixed "Owner" role. Correct: "Menunggu Approval" / "Pending Approval" (`warn`), "Disetujui" / "Approved" (`success`), "Ditolak" / "Rejected" (`danger`). Incorrect (reference did this, dropped here): "Menunggu approval Bara" / "approve-terkunci, Owner-only."

**Button** — `{components.button-primary}` / `-secondary` / `-ghost` / `-danger`. Poppins 500, `{rounded.md}`, `9px 15px` padding (`btn-sm` variant: `6–7px 11–13px`). Primary is the single `{colors.primary}`-filled action per view region; secondary is the default; ghost is for low-emphasis dismissal ("Batalkan"/"Cancel"); danger is white-fill/red-border/red-text, never red-fill (the reference never fills a button with `{colors.danger-text}`, keeping destructive actions from visually competing with status-danger pills). Disabled state: `opacity:.4–.45`, cursor unavailable — used for example, on a locked "Edit" button for an auto-synced setting.

**Form Input / Select** — `{components.input}`. `{rounded.md}`, `{colors.border}` border, `{typography.body-sm}`. Disabled/read-only fields (auto-generated Unit ID, auto-synced timeout) get `{colors.soft}` fill + `{colors.bluegray}` text to visually distinguish "system-set" from "editable."

**Alert Banner** — `{components.alert-banner}`, full-width, `{rounded.md}`, leading alert icon, three semantic variants: `danger` (`{colors.danger-bg}`/`{colors.danger-ring}`/`{colors.danger-text}`) reserved for account-level urgent state (Disputed/Chargeback); `warn` (`{colors.warn-bg}`/`{colors.warn-ring}`/`{colors.warn-text}`) for a state that needs attention but isn't yet urgent (for example, an Attribute Template diff awaiting reconcile); `info` (`{colors.info-bg}`/`{colors.info-ring}`/`{colors.info-text}`) for a non-urgent scoped notice (for example, "this module is List+Edit only").

**Attribute Reconcile Banner** — `{components.attribute-reconcile-banner}`. A specialized `warn`-variant Alert Banner with two states. Collapsed: single line, "Template atribut berubah — N field baru, M field dihapus," `{rounded.md}`, `{colors.warn-bg}`/`{colors.warn-ring}` fill/border, positioned at the top of the Item form, above the fold. Expanded (click to toggle): two `{typography.body-sm}` columns side by side under the summary line — "Ditambahkan" (Added, left) and "Dihapus" (Removed, right) — each field name as a plain list item, no pill/badge needed since the column heading itself carries the meaning. A single `{components.button-primary}` "Selaraskan"/"Reconcile" sits at the banner's bottom-right, full-width on mobile. Never a modal — the point is staying visible alongside the live form (see EXPERIENCE.md → Component Patterns for why). → `mockups/item-reconcile.html` (both collapsed and expanded states).

**Countdown / Deadline Indicator** — *Net-new.* `{components.countdown-indicator}`: inline `{typography.caption}` text paired with a small full-round dot, no card of its own — sits inline within whatever surface names the deadline (Order detail, Refund form, tokenized-link status). Three states by time-remaining, not by a fixed threshold the UI hardcodes — each screen defines what "urgent" means for its own deadline: normal (`{colors.bluegray}` text + dot), urgent/near-expiry (`{colors.warn-text}` text + dot), expired (`{colors.danger-text}` text + dot, paired with an explanatory line rather than a bare timestamp — for example, "Link kedaluwarsa 2 hari lalu" / "Link expired 2 days ago"). Never a numeric-only countdown with no state color — the color is load-bearing, not decorative.

**Modal / Dialog** — *Net-new.* `{components.modal}`: centered surface, `{rounded.lg}`, overlay scrim + overlay-depth shadow (see Elevation). Header (title + close icon-button) / body (scrollable if tall) / footer (right-aligned ghost + primary/danger button pair, same button component as the rest of the app). One modal open at a time; nested modals are not supported — a second action opens a new view instead.

**Toast** — `{components.toast}`. *Net-new, required on every user action per decision log.* Bottom-right stack, `{rounded.md}`, resting-depth shadow, `{spacing.card-padding}`-adjacent compact padding. Leading icon + semantic-family color bar (success/warn/danger/info) on the left edge, message in `{typography.body-sm}`, auto-dismiss ~4s with a manual close icon. Example content: "AWB berhasil dibuat" / "AWB created successfully." Distinct from the bell+badge in-app notification pattern (EXPERIENCE.md) — toast is transient/per-action, the bell is a persistent inbox.

**Empty State** — *Net-new; reference only had a one-line `.empty-note` placeholder.* Centered within the card/table region: a simple line-icon (reusing the existing sprite, for example, `#icon-inbox` or `#icon-box`), a `{typography.heading-section}` headline naming what's missing ("Belum ada order" / "No orders yet"), a `{typography.caption}` supporting line, and — where the empty state is actionable — a single primary button (for example, "Tambah Unit" / "Add Unit"). Never just the reference's bare muted one-liner for a genuinely empty (not just filtered) collection.

### Leaf Controls

Single-anatomy controls, each fully specified by one row — no separate rationale needed beyond what's in the table.

| Component | Anatomy | Key states / notes |
|---|---|---|
| Checkbox | 17×17px, 5px radius, `{colors.border}` border unchecked | `{colors.primary}` fill + white check icon when on |
| Toggle Switch | `{components.toggle-switch}`, 38×22px full-round track, 18px knob | Off = `{colors.border}` track; on = `{colors.primary}` track, knob slides right. Used for binary settings like promo stacking |
| Filter / Grade / Courier Chip | Pill-shaped, `1–1.5px` border | Unselected = `{colors.border}` border / `{colors.bluegray}` text; selected = `{colors.primary}` border / `{colors.primary-50}` fill / `{colors.primary-700}` text — same contract across all three variants, they differ only in what they filter or tag |
| Dropzone | Dashed 1.5px `{colors.border-dashed}` border, `{colors.soft}` fill, `{rounded.md}`, centered icon + title + hint | Always paired with a hint line stating the minimum requirement (for example, "0 foto diunggah — minimal 1 foto wajib") |
| Quota Bar | 90×6px full-round track (`{colors.border}`) | Fill `{colors.primary}` normally → `{colors.quota-warn}` near/at capacity; always paired with a "used / total" text label above it |
| Order Status Stepper | Vertical dot-and-line, `{colors.primary}` filled dot for completed steps, `{colors.dot-pending}` dot for the pending step, 1px `{colors.border}` connecting line, `{typography.body-sm}` bold label + `{typography.caption}` timestamp | Visually inert — never carries its own action; Shipment actions live on the Shipment card (see EXPERIENCE.md → Component Patterns) |
| Tabs | *Net-new.* `{components.tabs}`: horizontal row, Poppins 500 13.5px labels (same weight/size family as `nav-item`), 2px `{colors.primary}` underline sliding under the active tab | Inactive tabs in `{colors.bluegray}`; sits directly under a card's `section-head` (for example, "Timeline / Shipments / CS History") |
| Pagination | *Net-new.* `{components.pagination}`: numbered page buttons, `{rounded.md}` | Current page = `{colors.primary-50}` fill / `{colors.primary-700}` text; others = ghost/transparent `{colors.bluegray}` text; prev/next as icon buttons at either end; sits bottom-right of any paginating `{components.table}` |

## Do's and Don'ts

| Do | Don't |
|---|---|
| Keep the compact 13–14px / dense-row rhythm everywhere | Loosen spacing "for breathing room" — density is a deliberate choice |
| Use generic approval labels ("Pending Approval") | Hardcode a role name into approval/maker-checker UI ("Owner-only") |
| Size every button/pill/nav-label to its content, `white-space:nowrap` | Fix pixel widths on text-bearing chrome — it will clip longer ID or EN strings |
| Use the six semantic pill families (`success/warn/info/danger/neutral/done`) for every status enum | Invent a one-off color for a new status value |
| Reserve `{colors.primary}` fill for the single primary action per region | Fill more than one button per view region with primary color |
| Use resting shadow for cards, overlay shadow only for modal/dialog | Add hover-elevation or shadow-on-focus to rows, buttons, or nav items |
| Round icon-sized square badges slightly tighter than `{rounded.md}` (as the reference does) | Introduce a new radius value outside the sm/md/lg/full scale for anything else |
| Fire a toast on every state-changing action | Rely on the bell/badge alone for action confirmation |
| Theme-override via Filament's panel theming mechanism per client install | Fork or hand-edit this token set per client — overrides flow through the theme layer |
