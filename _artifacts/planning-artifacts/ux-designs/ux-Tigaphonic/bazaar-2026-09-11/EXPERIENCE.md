---
name: Bazaar
status: final
sources:
  - _artifacts/planning-artifacts/prds/prd-Tigaphonic/bazaar-2026-09-11/prd.md
  - _artifacts/planning-artifacts/prds/prd-Tigaphonic/bazaar-2026-09-11/addendum.md
updated: 2026-09-12
---

# Bazaar — Experience Spine

> Filament-based staff/admin dashboard for a headless eCommerce Brand Store package. 9 domains (8 in scope for this spine — Package Installation is CLI-only), 40 FRs. Desktop-first responsive web, bilingual EN/ID, dual theme, compact density. Paired with `DESIGN.md` (Bazaar's default admin visual identity) — this spine is the behavior; DESIGN.md wins on any visual-token conflict, this document wins on any behavioral conflict with the imported reference mockups in `imports/`.

**Sections:** Foundation → Information Architecture → Voice and Tone → Component Patterns → Maker-Checker & Approval Pattern → Order Status Model (3-Layer) → State Patterns → Interaction Primitives → Accessibility Floor → Responsive & Platform → Inspiration & Anti-patterns → Key Flows (Catalog & Fulfillment 1–5, Finance & CS 6–7, Content & Marketing 8–9, Admin/SEO/Ops 10–12, Executive 13).

## Foundation

Bazaar's experience layer is the **Filament-based staff/admin dashboard** that ships with every Bazaar package install — one per client project (no multi-tenant SaaS shell, no `tenant_id`, no workspace switcher). It is an internal, permission-gated operations tool for **all Bazaar staff roles, including the Brand Store owner** — not a consumer product. The customer-facing portal is a separate, out-of-scope surface built by each client's own frontend team (monolith Blade/Livewire via the Service Layer, or headless via the optional API Layer); this spine never designs a portal screen — it designs how staff observe and act on portal-originated events (a new Order, a submitted Review, a reported 404) from the dashboard side.

No named third-party UI kit is inherited wholesale. Filament is the underlying admin framework, but per the locked decision to theme-override Filament's defaults, every visual token cited in this document (`{colors.*}`, `{typography.*}`, `{spacing.*}`, `{rounded.*}`, `{components.*}`) resolves to `DESIGN.md` — the mockups generated from that token set are the visual source of truth, not Filament's stock panel theme.

**Form factor:** web, desktop-first/primary (≥1280px is the design target); responsive down to mobile web is a supported nice-to-have, not a primary target (see Responsive & Platform). **Bilingual:** EN/ID with a language switcher is a hard requirement — every string in this spine, every microcopy example, and every component behavior must work translated, with no fixed-width container assumed to fit only one language. **Theme:** light and dark are both first-class (dark is net-new per DESIGN.md, not a defaulted inversion). **Density:** compact is a deliberate, locked preference — dense tables, tight rows, more information per screen over generous whitespace, throughout this document.

## Information Architecture

The shell is constant: a domain-grouped sidebar (`{components.sidebar}`) plus global chrome in the topbar (`{components.topbar}`) — search, staff notification bell, language/theme controls, user menu. Modal stacks one level deep, never two (a second action opens a new view instead of nesting). Surfaces below are grouped by the 9 PRD domains; **Package Installation is CLI-only** (`composer require` + `php artisan bazaar:install` + `php artisan migrate`, run once by the developer, not staff) and has no dashboard surface — excluded from this spine per the confirmed scope decision.

**Global chrome** (not domain-local; always available):

→ Composition reference: `imports/admin-dashboard.html` illustrates the shell (sidebar/topbar), stat-card Dashboard, and Recent Activity feed. Spine wins on conflict.

| Surface | Reached from | Purpose |
|---|---|---|
| Dashboard (Home) | Login / sidebar brand mark | Landing snapshot — KPI stat cards (today's revenue, orders, pending approvals) + quick links into each domain's queue |
| Approvals Inbox | Sidebar (badge count) / bell notification click-through | Cross-domain queue of every Item, Blog/Page, Review, Cancel/Retur, and Refund Request awaiting the viewer's approval permission — see Maker-Checker & Approval Pattern. → `mockups/approvals-inbox.html` |
| Staff Notification Center | Topbar bell icon | Persistent, role-targeted, actionable-event inbox (distinct from toast — see Component Patterns) |
| Global Search | Topbar search input | Cross-domain lookup: Order by ID/customer, Item by name/SKU, Customer by email |
| Settings & Profile menu | Topbar user chip | Own profile, theme toggle, language switcher (EN/ID), logout |

### Catalog

→ Composition reference: `imports/admin-katalog.html` illustrates Items, Item detail/edit, and Grading/Attribute-Template treatment. Spine wins on conflict.

| Surface | Reached from | Purpose |
|---|---|---|
| Brands | Sidebar → Catalog → Brands | Create/edit/archive Brand master data (FR-1) |
| Categories | Sidebar → Catalog → Categories | Manage the fixed 2-level Main/Sub-Category hierarchy, slug, SEO fields (FR-2, FR-24) |
| Attribute Templates | Category detail (a Sub-Category) → "Attribute Template" tab | Define dynamic text/number/dropdown/checklist fields per Sub-Category, no deploy (FR-3) |
| Items | Sidebar → Catalog → Items | List/filter Items by Draft/Published, Condition, Inventory Strategy, visibility label (FR-4, FR-30) |
| Item detail/edit | Items row → detail | Full Item form — Brand, Sub-Category, Condition, Inventory Strategy, Grading (Second only), media, SEO, slug, Attribute Reconcile banner (FR-3, FR-4, FR-24). → `mockups/item-reconcile.html` (reconcile banner, both states) |
| Stock | Item detail → "Stock" tab | View/adjust the Stock ledger per Item × Warehouse (FR-5) |
| Warehouses | Sidebar → Catalog → Warehouses | Create/edit Warehouse, set Default Warehouse pointer (FR-6) |

### Order

→ Composition reference: `imports/admin-order.html` illustrates the Order detail 3-layer status pills, Shipment cards, and Stepper. `imports/admin-promo.html` illustrates Promos/Promo detail. Spine wins on conflict.

| Surface | Reached from | Purpose |
|---|---|---|
| Customers | Sidebar → Order → Customers | View/edit Customer records, see per-Customer Order history (FR-7) |
| Orders | Sidebar → Order → Orders | List filterable independently by Payment / Order / Shipping status (FR-9, FR-10) |
| Order detail | Orders row → detail | 3 status pill groups, Shipment cards + AWB actions, CS History tab, Cancel/Retur actions, Review moderation entry point, tokenized-link expiry indicator (FR-10, FR-11, FR-12, FR-13, FR-15). → `mockups/order-detail-dark.html` (dark-theme proof-of-concept) |
| Cancel/Return Requests | Order detail action, or Sidebar → Order → Cancel/Return Requests | File and track Cancel/Return Requests across all Orders, Pending/Approved/Rejected (FR-13) |
| Promos | Sidebar → Order → Promos | Create/edit Promo — discount type, quota, per-customer limit, active window (FR-14) |
| Promo detail | Promos row → detail | Quota Bar, redemption stats, link to Coupon Performance report (FR-14, FR-25) |

Cart (FR-8) has **no staff-facing surface**, by design: it is a pre-checkout, portal-only construct (never reserves stock) that staff have no reason to view or edit from the dashboard side — mirroring the FR-36/FR-37/FR-38 treatment below.

### Finance

→ Composition reference: `imports/admin-finance-cs.html` illustrates Payments (settlement/margin breakdown) and Refund Requests, plus the CS History pattern reused on Order detail. Spine wins on conflict.

| Surface | Reached from | Purpose |
|---|---|---|
| Payments | Sidebar → Finance → Payments | List and reconcile Payment transactions per gateway (FR-16) |
| Refund Requests | Sidebar → Finance → Refunds, or Order detail | Submit/track Refund Requests, nominal bounded by Min–Max Refund % (FR-17) |

### User & Access

| Surface | Reached from | Purpose |
|---|---|---|
| Users | Sidebar → User & Access → Users | Create/edit/deactivate internal User accounts, assign Role(s) (FR-18) |
| Roles & Permissions | Sidebar → User & Access → Roles | Create Role, check granular permissions, no deploy needed (FR-19) |
| Audit Trail | Sidebar → User & Access → Audit Trail | Read-only, filterable (User / entity / date range) log of every mutation (FR-20) |

### Content

| Surface | Reached from | Purpose |
|---|---|---|
| Hero Banners | Sidebar → Content → Hero Banners | Create/order Hero Banners with an active-date schedule (FR-21) |
| Blog | Sidebar → Content → Blog | Create/edit/publish Blog entries (Markdown) + Blog Categories, same maker-checker gate as Item (FR-22). → `mockups/content-editor.html` |
| Pages | Sidebar → Content → Pages | Create/edit/publish Page entries (Markdown) + Page Categories, same gate as Blog (FR-22) |
| Featured Items | Sidebar → Content → Featured Items | Pick Published Items to feature, set display order (FR-23) |

SEO metadata (Meta Title/Description, Canonical URL, OG fields — FR-24) is **not a separate surface**: it is a field group embedded directly in the Item, Category, Blog, and Page edit forms.

### Reporting

| Surface | Reached from | Purpose |
|---|---|---|
| Reporting Dashboard | Sidebar → Reporting | 5 read-only report categories (Sales & Revenue, Customer Analytics, Inventory, Marketing & Promo, Operational & Support, plus SEO Health) with date-range filter, CSV/XLSX/PDF export, scheduled report (FR-25) |

### Notification

| Surface | Reached from | Purpose |
|---|---|---|
| Notification Templates | Sidebar → Notification → Templates | Edit subject/body/placeholder variables per customer-facing trigger point, no deploy (FR-26) |
| Notification Log | Sidebar → Notification → Log | Sent/Failed delivery history + manual resend (FR-27) |

Staff In-App Notification (FR-32) is **not a domain-local surface** — it is the global Staff Notification Center chrome listed above; there is no template-editing screen for it, because its event list and copy are fixed by the PRD, not staff-authored.

### Global Settings

→ Composition reference: `imports/admin-pengaturan-sistem.html` illustrates the List+Edit-only settings-row pattern and the locked/auto-synced field treatment. Spine wins on conflict.

| Surface | Reached from | Purpose |
|---|---|---|
| Global Settings | Sidebar → Global Settings | List+Edit fixed parameters: Timeout Timers, Payment Gateway + active methods, Shipping config, Default Warehouse, Store Info, Min/Max Refund %, Robots.txt content, Analytics codes (FR-28) |
| SEO Defaults | Global Settings → "SEO Defaults" section | Site-wide fallback meta title template, description, default OG image (FR-29) |

### SEO & Discoverability

| Surface | Reached from | Purpose |
|---|---|---|
| Redirect Manager | Sidebar → SEO & Discoverability → Redirects | Register old path/slug → new target (Bazaar entity or free URL) + reason (FR-39) |

Broken Link (404) tracking (FR-40) surfaces inside **Reporting → SEO Health**, not as its own SEO domain screen — it's a read-only report over passively-collected data, not something Staff manage. Three FRs in this domain have **no staff-facing surface at all**, by design: FR-36 (media optimization) runs silently inside every Dropzone upload across domains; FR-37 (Sitemap Data Feed) and FR-38 (Structured Data Feed) are pure Service/API data feeds the portal pulls — Bazaar never renders sitemap.xml, robots.txt, or JSON-LD itself, consistent with the headless vision (§1 PRD). This is intentional scope, not a gap.

## Voice and Tone

Microcopy only. Brand voice and visual posture live in `DESIGN.md` ("quietly competent back-office software"). Every example below must hold in both EN and ID.

| Do | Don't |
|---|---|
| "Item disimpan sebagai Draft." / "Item saved as Draft." | "Yeay, Item kamu berhasil dibuat! 🎉" |
| "3 permintaan menunggu approval Anda." / "3 requests awaiting your approval." | "You have pending stuff to review!" |
| "Menunggu Approval" (generic, permission-based) | "Menunggu approval Owner" / any hardcoded role name in UI copy |
| "AWB berhasil dibuat." / "AWB created successfully." — state what happened, plainly | "✓ Success! Your shipment is on its way to greatness" |
| "Tautan aktif sampai 16 Sep, 14:00." / "Link active until Sep 16, 14:00." — plain deadline, no urgency theater | "⏰ Hurry! Link expires soon!" |
| Short, complete sentences; literal, translatable phrasing | Idioms that don't translate 1:1 ("ready to roll," "in the driver's seat") |

## Component Patterns

Behavioral. Visual specs live in DESIGN.md → Components.

| Component | Use | Behavioral rules |
|---|---|---|
| Sidebar Nav | Global | Queue-count badges show live Draft/Pending counts, permission-scoped — never shows a count for an action the viewer can't take (visual spec: DESIGN.md → Sidebar Nav) |
| Data Table | Every list surface | Row click anywhere opens detail; a row needing attention (Disputed payment, failed AWB) must be visually flagged per DESIGN.md's row-tint rule; the single most common row action (for example, Approve) is always a visible icon button, never buried in a menu |
| Status Pill | Every status field | One pill per status value; text is the source of truth, color is reinforcement only, never color-only meaning; Order's 3 layers always render as 3 separate pills, never merged (see Order Status Model) |
| Approval Action | Approvals Inbox, and inline on Item/Blog/Page/Review/Cancel/Retur/Refund detail | Renders only if the viewer's Role holds the specific approve-permission for that item type — hidden, not just disabled, for everyone else; label is always generic ("Approve"/"Setujui") |
| Attribute Reconcile Banner | Item edit form, when `attribute_snapshot` differs from the Sub-Category's current Attribute Template | Inline, collapsible banner (`{colors.warn-bg}`/`{colors.warn-ring}`) at the top of the form — **not a modal**, see Maker-Checker & Approval Pattern for why. Collapsed state summarizes the diff count; expanded state lists Added/Removed fields in two columns; a single "Selaraskan"/"Reconcile" action runs the merge synchronously and fires a toast |
| Alert Banner | Permission-denied module notice (`info`), Attribute Reconcile (`warn` — its own dedicated row above), account-level Disputed/Chargeback flag (`danger`) | Persistent until its underlying condition resolves — never auto-dismisses like a toast; at most one Alert Banner visible per surface at a time; `danger` and `warn` variants may be dismissed by staff after reading; `info` scope-notices (for example, "Modul ini hanya List+Edit") cannot be dismissed since the constraint they describe never changes |
| Modal / Dialog | Short single-step confirmations only (confirm Reject, confirm Deactivate User) | `{components.modal}`; one level deep, never nested; never used for the Attribute Reconcile diff — that content needs to stay visible alongside the live form, not trapped behind an overlay |
| Toast | Every state-changing action, no exceptions (locked decision) | Never the *only* confirmation — the underlying pill/badge/count must also visibly change (visual spec: DESIGN.md → Toast) |
| Staff Notification Bell | Topbar, global | `{colors.notification-dot}` unread badge; opening the dropdown marks visible items read; each item deep-links to its source record; dispatched only to Roles holding the relevant permission, never broadcast |
| Tabs | Order detail (Timeline / Shipments / CS History), Item detail (Details / Stock / SEO), Settings (grouped sections) | `{components.tabs}`; content-only switch, no reload; deep-linkable so a bell notification can land Staff on the exact tab |
| Pagination | Every list Data Table | `{components.pagination}`; no infinite scroll — a predictable row count matters for an audit-adjacent tool |
| Filter / Grade / Courier Chip | Item list (Condition/Grading), Order list (courier) | Multi-select toggle chips above the table; selection persists in the URL query so a filtered view is bookmarkable |
| Quota Bar | Promo detail | Always paired with a "used / total" numeric label — the near-capacity fill color is reinforcement, never the sole signal (visual spec: DESIGN.md → Quota Bar) |
| Order Status Stepper | Order detail | Timeline of Shipping Status transitions with timestamps; visually inert — actions live in the Shipment card, never on the stepper itself (visual spec: DESIGN.md → Order Status Stepper) |
| Dropzone | Item, Hero Banner, Blog/Page Feature Image, Store Info logo/favicon | Every upload runs the same silent compress+WebP pipeline (FR-36) across all domains — one mechanism, not a per-domain feature; hint line always states the minimum requirement |
| Empty State | Any genuinely empty collection | Icon + headline naming what's missing + one primary action where actionable; a **filtered-to-nothing** result gets its own shorter message ("Tidak ada hasil untuk filter ini") distinct from true emptiness |
| Countdown / Deadline Indicator | Order detail (tokenized-link expiry, Review deadline), Refund window where relevant | Plain-language remaining time ("Tautan aktif sampai 16 Sep, 14:00"), recalculated on page load — not a live-ticking JS clock, since staff visits are infrequent relative to deadline granularity |

## Maker-Checker & Approval Pattern

This recurs across 5 places — Item publish, Blog/Page publish, Review moderation, Cancel/Retur approval, Refund approval — and takes exactly **two shapes**, both surfaced generically (never hardcoded to a role name, per the locked decision):

**Shape 1 — 2-state Draft → Published** (Item FR-4, Blog/Page FR-22): there is no separate backend "Pending" status — `Draft` covers both "Staff is still editing" and "awaiting first publish." The Approvals Inbox treats every Draft the viewer can approve as a pending row. **Approve** → Published. There is **no formal Reject**: the PRD deliberately leaves incomplete/incorrect Drafts to off-system coordination (chat) between maker and approver; the entity simply stays Draft until the maker revises and the approver approves again.

**Shape 2 — 3-state Pending → Approved / Rejected** (Cancel Request, Return Request, Refund Request — all FR-13/FR-17; Review — FR-12): an explicit Pending status is recorded at submission, a notification fires to the Role holding the relevant approve-permission, and the outcome is a visible terminal state on the Request record itself — never silent. Reject leaves the underlying Order/Payment untouched.

| Instance | Shape | Approve consequence | Reject consequence |
|---|---|---|---|
| Item publish | 1 | Draft → Published, visible on portal | *(none — off-system coordination)* |
| Blog/Page publish | 1 | Draft → Published | *(none — off-system coordination)* |
| Review moderation | 2 | Review visible on portal | Review stays hidden, Request marked Rejected |
| Cancel Request | 2 | Order Status → Cancelled, reservation released | Order continues unchanged |
| Return Request | 2 | Branches into AWB Voided / Returned to Warehouse — see Order Status Model | Order Status reverts to prior state, continues unchanged |
| Refund Request | 2 | Payment Status → Refunded (manual bank transfer follows, off-system) | Payment Status unchanged, no transfer |

A rule spanning both shapes: the Approve/Reject control is **permission-gated at render time**, not merely disabled — a Staff member without the relevant permission never sees the control. The single cross-domain **Approvals Inbox** (Key Flow 2) aggregates both shapes into one queue, typed by icon+label per row, so Staff can tell a Draft-Item row from a Pending-Refund row without opening it. → `mockups/approvals-inbox.html`.

## Order Status Model (3-Layer)

Order carries **3 independent status layers**, each with its own enum and its own trigger — never conflated into one composite "status." Order detail always renders 3 distinct pill groups side by side; the Orders list shows all 3 as separate, independently filterable columns, never folded into one "Status" column.

| Layer | Values (pill family) | Typical trigger |
|---|---|---|
| **Payment Status** | Pending (`warn`) → Paid (`success`) / Expired (`danger`) / Denied (`danger`) / Cancelled (`neutral`) / Refunded (`done`) / Disputed (`danger` + alert icon) | Payment gateway callback; Expired via the payment-timeout Timeout Timer (FR-31) |
| **Order Status** | Processing (`info`) → Cancelled (`danger`) / Return Requested (`warn`) / Returned (`done`) / Completed (`success`) | Mix of Staff action (approve Cancel/Retur) and automatic transitions (FR-31 payment timeout, FR-12 Review-deadline auto-complete) |
| **Shipping Status** | Not Shipped (`neutral`) → Shipped (`info`) → Delivered (`success`) / AWB Voided (`danger`) / Returned to Warehouse (`done`) | Shipping vendor webhook/poll sync, or Staff's Create-AWB / manual-entry / "Retur Selesai" action |

Independence is a first-class fact staff rely on: Payment = Paid while Shipping = Not Shipped is normal mid-flow, not an inconsistent state to flag.

**Terminology resolved** (decision log): the request entity's canonical name is the English **Return Request** — matching the Order Status enum value it produces, **Return Requested** — since backend state and field names use full English throughout. "Retur" only survives as Indonesian-locale *display* copy (for example, the Indonesian translation of "Return Request" is "Permintaan Retur," and the Indonesian action label for closing one out is "Retur Selesai"); it is never the canonical identifier. Every other entity/field name in this spine follows the same convention: English canonical identifier, bilingual EN/ID display copy layered on top — not a mixed-language literal baked into either.

## State Patterns

Behavioral. Visual contrast/tokens live in `DESIGN.md`.

| State | Surface | Treatment |
|---|---|---|
| Cold dashboard load | Dashboard (Home) | Skeleton stat cards matching the 3-up grid; resolves as each metric query returns |
| Cold list load | Any Data Table surface (Items, Orders, Payments, Audit Trail, …) | Skeleton rows matching `{spacing.row-padding-y}` rhythm (not a spinner) — the table shape is visible before data is, so the layout doesn't jump on resolve |
| Empty collection | Any list (Items, Orders, Warehouses, …) | Empty State component: icon + "Belum ada {entity}" / "No {entity} yet" + primary action where actionable |
| Filtered to nothing | Any filtered list | Shorter, distinct message ("Tidak ada hasil untuk filter ini") — never the same copy as a truly empty collection |
| Permission-denied module | Sidebar / direct URL | Surface hidden from sidebar entirely for Roles without List permission; a Role with view-only access to a List+Edit-only module (for example, Global Settings) sees an inline `info` Alert Banner ("Modul ini hanya List+Edit") rather than a blocked-screen |
| AWB vendor call failure | Order detail → Shipment card | Clear failure state + Retry button (idempotent — no duplicate AWB on repeat clicks); persistent failure exposes the manual courier/AWB entry fields as fallback |
| Save/network failure | Any form | Toast (danger variant): "Gagal menyimpan, coba lagi." / "Couldn't save, try again." Form values retained, no data loss |
| Tokenized order link expired (staff view) | Order detail | Staff sees an inline note on the Order that the customer-facing tokenized link has expired (per the configured Timeout Timer) — informational only, does not block any Staff action |
| Disabled / auto-synced field | Item (auto Warehouse), Shipment (vendor-filled AWB), Global Settings (system-computed values) | `{colors.soft}` fill + `{colors.bluegray}` text, `opacity:.4–.45` on any accompanying disabled control — visually distinct as "system-set," never just a plain grayed input indistinguishable from a bug |
| Timeout Timer nearing/passed deadline | Order detail (payment window, review window, tokenized-link window) | Plain-language deadline shown pre-expiry (Countdown component); once passed, the automatic consequence (auto-cancel, auto-complete, link closes) is what actually changes state — the UI never shows a "deadline passed, action needed" prompt for something the system already resolved on its own |
| Focus (form fields, table rows) | Every surface | Native focus ring, visible at AA contrast against surface — no custom focus chrome, no removed outline |
| Export/scheduled-report failure | Reporting Dashboard | Out of scope for this spine: the PRD specifies CSV/XLSX/PDF export and scheduled email delivery but no failure-path detail to design against — flagged here rather than invented — revisit once the PRD commits to one |
| Offline | Any surface | Out of scope, deliberately: an internal, always-connected desktop tool has no defined offline mode, unlike the mobile example spines this document's format is drawn from |

## Interaction Primitives

- Click to act everywhere; no hover-only affordance (the tablet breakpoint may be touch-driven).
- Row click opens detail; the single most common secondary action is always a visible icon button, never hidden behind a menu.
- Approve/Reject fire immediately on click + toast — no extra "are you sure" modal, because the maker-checker pattern already *is* the confirmation step. A second confirm modal is reserved for genuinely hard-to-reverse actions only: Deactivate User, Delete Brand/Category.
- Reordering (Featured Item display order, Hero Banner display order) uses explicit "move up / move down" row-action icon buttons, not drag-and-drop — keeps reordering keyboard-operable without building a drag-and-drop accessibility layer this internal tool doesn't need.
- Toast fires on every state-changing action, no exceptions (locked decision) — distinct from the persistent bell/badge Staff Notification Center; see Component Patterns.
- Language switcher (EN/ID) and theme toggle (light/dark) live in the topbar user menu; apply instantly, no reload, persist per-User.
- One primary-color (`{colors.primary}`) action per view region — a screen with both Approve and Reject shows Approve as primary, Reject as `{components.button-danger}` (white-fill/red-border, never red-fill); never two primary buttons in the same region.
- **Banned:** infinite scroll (pagination only), drag-and-drop reordering, decorative loading animation beyond a standard skeleton/spinner, nested modals, color-only status meaning.

## Accessibility Floor

Behavioral. Visual contrast lives in `DESIGN.md` (directional AA targets, not an audited guarantee). Bazaar's confirmed floor is **internal-tool standard**: clear contrast + basic keyboard operability for core flows — not a formal WCAG compliance commitment.

- Tab order matches visual/reading order on every surface; forms, tables, and approval actions are fully reachable and triggerable via keyboard (Tab + Enter/Space) without a mouse.
- Every icon-only control (row-action icons, topbar bell/search/user icons) carries an accessible name — never icon-only with no label for assistive tech.
- Status is never color-only: every pill, quota bar, and banner carries a text label alongside its semantic color, so a colorblind or non-visual user gets the same information.
- Modal/Dialog traps focus while open; `Esc` closes it; focus returns to the triggering control on close. One level deep only.
- Disabled/auto-synced fields are marked `aria-disabled`/`readonly` in addition to their visual treatment — not visual-only.
- Toast is reachable and dismissible via keyboard within its ~4s window; it is never the sole channel for a state change (the underlying UI also updates — see Component Patterns).
- Dense tables keep a visible focus outline on every interactive cell/row despite the tight row rhythm — density never removes focus visibility.

## Responsive & Platform

Desktop-first/primary; mobile web is a supported nice-to-have, not a redesigned surface. Breakpoints are defined in DESIGN.md → Layout & Spacing — this section states the behavioral consequence at each.

| Breakpoint | Behavioral consequence |
|---|---|
| Desktop (≥1280px) | Full experience as specified throughout this document — unchanged. |
| Tablet (768–1279px) | Layout mechanics per DESIGN.md → Layout & Spacing; behaviorally, Approvals Inbox and Order detail (the two most touch-likely surfaces) must remain fully operable at this width. |
| Mobile (<768px) | Layout mechanics per DESIGN.md → Layout & Spacing. No flow in this spine is mobile-redesigned beyond that layout mechanic — the same 13 Key Flows apply, just reflowed. |

## Inspiration & Anti-patterns

- **Lifted from the six imported reference mockups** (`imports/admin-dashboard.html`, `admin-finance-cs.html`, `admin-katalog.html`, `admin-order.html`, `admin-pengaturan-sistem.html`, `admin-promo.html`): the overall back-office shape — sidebar + stat-card dashboard + dense data tables — is the right silhouette for this staff audience, and is kept as the IA/Component baseline above.
- **Rejected — hardcoded "Owner-only" approval gate** (reference mockups' `tombol approve terkunci — Owner-only`): Approval Role is a dynamic permission bundle (FR-19), not a fixed role — see Maker-Checker & Approval Pattern; the UI never names a role.
- **Rejected — the reference's placeholder "ArmyWatch" brand voice and persona-specific copy**: dropped for the brand-neutral tone in Voice and Tone, consistent with `DESIGN.md`.
- **Rejected — inventing a formal "Rejected" status** for Item/Blog/Page publish, or a "Retur Ditolak" status for physical-condition disputes on returns: the PRD deliberately leaves these to off-system human coordination rather than modeling every disagreement as a state (see Maker-Checker & Approval Pattern, Shape 1, and Key Flow 5's failure path) — this spine does not add a Reject button where the PRD explicitly has none.

## Key Flows

*Protagonist names not in the PRD (Wulan, Tari, Yoga, Rian, Bagas, Fajar, Dian, Nina) are session-confirmed in `.memlog.md`, extending the PRD's own naming convention to its remaining implied roles.*

### Catalog & Fulfillment (Flows 1–5)

#### Flow 1 — Sari publishes a new Item (Sari, Staff Catalog)

*Realizes FR-1, FR-2, FR-3, FR-4, FR-5, FR-6.*

1. Sari opens Catalog → Items → Add Item.
2. Picks Brand and Sub-Category — the Sub-Category's active Attribute Template fields load automatically (FR-3); she sets Condition and Inventory Strategy (system suggests New→Pooled or Second→Serialized, she can override the pairing).
3. Condition = Second on this one, so Grading becomes a required field.
4. She uploads photos to the Dropzone; leaves alt text blank — system auto-fills it with the Item name.
5. She leaves Warehouse unset on Stock; the system silently resolves it to the current Default Warehouse and snapshots that choice on the Stock row.
6. She saves. Toast: "Item disimpan sebagai Draft." The Item appears in the Items list tagged Draft (`neutral`).
7. **Climax:** her new Item is fully in-system and correctly filed — brand, category, attributes, stock, warehouse — but invisible to any customer until someone with Approval Role acts on it. Maker-checker held, without her having to know or care who that someone is.

Failure/edge: there's no formal "Rejected" status — if Rara finds the data incomplete, she and Sari coordinate outside Bazaar (chat); the Item just stays Draft until Sari revises and Rara approves again.

#### Flow 2 — Rara works the Approvals Inbox (Rara, Approval Role)

*Realizes FR-4, FR-12, FR-13, FR-17, FR-22.*

1. Rara sees a badge count on the sidebar's Approvals Inbox item; bell notifications have already arrived for each new pending item.
2. Opens Approvals Inbox — one cross-domain queue, typed rows (Item, Blog/Page, Review, Cancel/Retur, Refund), each showing submitted-by/at and a compact preview.
3. Opens a pending Item row (Sari's, from Flow 1) in review context, sees it's complete, clicks Approve. Toast confirms; the Item's pill flips Draft (`neutral`) → Published (`success`).
4. Moves to a pending Review: reads the text and rating inline, Approves — it goes live on the portal; the underlying Order was already Completed regardless of this decision, since approval only gates *visibility*, not Order state.
5. **Climax:** the inbox count drops to zero for her — one screen let her clear five different domains' worth of gated work without learning five different approve buttons.

Failure path: she opens a pending Item and the price field is missing. There's no in-system Reject — she leaves it Draft and messages Sari outside Bazaar; it simply reappears in her queue once Sari fixes and re-saves it.

#### Flow 3 — Sari reconciles an Attribute Template diff (Sari, Staff Catalog)

*Realizes FR-3.*

1. Sari reopens an older Second-hand Item just to update its price. Its Sub-Category's Attribute Template has since gained a field ("Warna") and lost one ("Ukuran Kardus") since this Item's snapshot was last touched.
2. An inline Attribute Reconcile Banner appears at the top of the form: "Template atribut berubah — 1 field baru, 1 field dihapus."
3. She expands it: two columns — Added ("Warna," empty) / Removed ("Ukuran Kardus," still present in this Item's data).
4. She clicks Reconcile. The system adds the empty Warna field to the snapshot and drops Ukuran Kardus from it; fields present in both are untouched — her existing values stay intact.
5. Toast: "Atribut diselaraskan." The banner collapses since the snapshot now matches the current template.
6. She fills in Warna, updates the price, and saves.
7. **Climax:** the Item's attribute panel now mirrors the live template exactly, with the one new required field flagged — and none of her unrelated data was touched or lost along the way; the diff never blocked the price edit she actually came to make.

Failure/edge: she ignores the banner and saves without reconciling — allowed; the snapshot stays stale, and the same banner reappears next time anyone opens this Item, since reconciliation is manual/staff-triggered only, never automatic.

#### Flow 4 — Dewi fulfills an Order and registers AWB (Dewi, Staff Order)

*Realizes FR-10, FR-11, FR-31.*

1. Dewi gets an in-app notification: a new Order is Processing. She opens Order detail: Payment = Pending (`warn`), Order = Processing (`info`), Shipping = Not Shipped (`neutral`) — 3 distinct pills.
2. Branch — payment resolves: **(a)** customer doesn't pay within the Timeout Timer window → Order auto-Cancelled, no approval needed, she just sees the result when she next looks; **(b)** customer pays → she gets a notification, Payment flips to Paid (`success`).
3. She packs off-system, then clicks "Buat AWB" on each Shipment card — the Order spans 2 Warehouses, so there are 2 independent Shipment cards, each with its own action button.
4. Branch — vendor call succeeds: courier + AWB number auto-fill from the vendor response, she can print the surat jalan; Shipping flips Not Shipped → Shipped (`info`). Branch — vendor call fails: a clear failure state with Retry (idempotent — no duplicate AWB); if it keeps failing, she registers manually at the vendor's own site and types the courier + AWB number into the Shipment's manual-entry fields.
5. Shipping status continues via webhook/manual refresh through Delivered; a manual refresh/override button exists as fallback for stalled sync.
6. **Climax:** the moment Shipping flips to Delivered, the customer's tokenized-link email has already gone out automatically — she never typed a single status by hand across the whole cycle, and both Shipment cards on the split Order carry independently correct AWB state.

Failure/edge: clicking "Buat AWB" twice on an already-registered Shipment is a no-op — idempotent, no duplicate AWB, no confusing second toast.

#### Flow 5 — Dewi and Rara handle a 3-branch Cancel/Retur (Dewi, Rara)

*Realizes FR-13.*

1. A customer calls CS wanting to cancel or return. Dewi opens the Order and checks Shipping Status to know which of the 3 branches applies.
2. **Branch A — no AWB yet:** she files a Cancel Request + reason. Order Status stays Processing while it's Pending; Rara approves from the Approvals Inbox → Order Status flips to Cancelled (`danger`), reservation released. Reject → Order continues unchanged.
3. **Branch B — AWB exists, not yet physically shipped:** filed as a **Return Request** instead (AWB already exists). Order Status flips immediately to Return Requested (`warn`) while Pending. Rara approves → Dewi manually voids the AWB at the vendor, goods return to warehouse, she logs photo + condition notes, clicks "Retur Selesai"/"Mark Return Complete" → Shipping → AWB Voided (`danger`), Order → Returned (`done`); the Serialized Item republishes / the Pooled Item's Stock is credited back.
4. **Branch C — already physically shipped:** same Return Request + Return Requested Pending state; on approval Dewi waits for the physical parcel, logs photo + notes, clicks "Retur Selesai"/"Mark Return Complete" → Shipping → **Returned to Warehouse** (`done`, not AWB Voided — the parcel genuinely traveled) → Order → Returned, same as Branch B.
5. **Climax:** three visually distinct end-states (Cancelled vs. Returned+AWB-Voided vs. Returned+Returned-to-Warehouse) all come out of one shared Request form and one shared approval step — Dewi never picks a different UI for New vs. Second-condition goods, and Rara never faces a "which kind of return is this" decision.

Failure/edge: the returned goods don't match the description — handled entirely off-system (no "Return Rejected" state exists); Dewi still runs the same "Retur Selesai"/"Mark Return Complete" action and records the discrepancy in her notes, because the system doesn't branch on it.

### Finance & CS (Flows 6–7)

#### Flow 6 — Wulan processes a Refund (Wulan, Staff Finance)

*Realizes FR-17.*

1. Wulan opens an Order that's Returned (or Cancelled with Payment previously Paid) — only there does a "Ajukan Refund" action appear.
2. Opens the Refund Request form: the nominal field is pre-filled with the Maximum Refund % of the Order total — always valid the instant the form opens.
3. She can type any Rupiah figure, but the valid Min–Max range shows inline; anything outside it is rejected on submit.
4. She lowers it slightly per an agreement with the customer, submits. Toast confirms; status is Pending Approval, notification fires to the Refund-approve Role.
5. An approver Approves → Payment Status flips to Refunded (`done`); a note reminds Wulan the actual bank transfer is manual, off-system — Bazaar never calls a gateway refund API and never verifies the transfer happened.
6. **Climax:** the moment Payment Status flips to Refunded, that's the system's entire responsibility discharged — the wire transfer itself is her next step, off-screen, and Bazaar doesn't pretend to track further than that.

Failure path: approver Rejects → Payment Status untouched, no transfer implied, and Wulan sees Rejected sitting next to her original Request with no other side effect.

#### Flow 7 — Tari logs a CS interaction and a straightforward retur (Tari, Staff CS)

*Realizes FR-15 (feeds FR-13).*

1. A customer calls in; Tari adds a CS Interaction Log entry on the Order — channel, note, auto-stamped with her name and time.
2. She logs 2–3 more entries across the call as it becomes clear the customer wants a return (AWB already exists, so it's a Return, not a Cancel).
3. Before filing anything, she scrolls the CS History tab — her paper trail for the decision she's about to make.
4. She files the Return Request per Flow 5's Branch B/C, referencing the call context in the reason field.
5. **Climax:** everything she needs — the conversation history and the return trigger — lives on the same Order detail screen, in adjacent tabs; she never cross-references a separate CS tool.

Failure/edge: the customer calls back before approval resolves — she adds another log entry; the Return Request's Pending status is unaffected, log entries are a record, not a state input.

### Content & Marketing (Flows 8–9)

#### Flow 8 — Yoga publishes a Blog post (Yoga, Staff Content)

*Realizes FR-22, FR-24.*

1. Yoga opens Content → Blog → New Entry, picks a Blog Category, writes the body in the Markdown editor.
2. Uploads the mandatory Feature Image; leaves alt text blank — it auto-fills with the entry title.
3. Fills SEO fields; leaves OG Title blank and sees (per the documented fallback chain) it will use Meta Title at publish time if that's filled, or the entry title if not.
4. Sets the Slug (pre-filled from title, editable). Saves — Draft, same maker-checker gate as Item.
5. An Approval Role reviewer picks it up from the Approvals Inbox (filtered to Content), approves.
6. **Climax:** the entry flips to Published through the identical inbox shape the Catalog team already uses — nobody had to explain a Content-specific approval workflow to Yoga.

Failure/edge: he forgets the Feature Image — save is blocked with an inline requirement message; there's no silent Draft-with-missing-image state.

#### Flow 9 — Rian creates and monitors a Promo (Rian, Staff Marketing)

*Realizes FR-14, FR-25.*

1. Rian opens Order → Promos → New Promo: discount type (percentage or fixed), total quota, per-customer redemption limit, active date range.
2. Saves — the Promo goes live at its start date automatically; Promo is not in the maker-checker list — no approval gate.
3. Days later he opens the Promo detail: Quota Bar shows used/total, fill in `{colors.primary}`.
4. As redemptions climb near capacity, the fill shifts to `{colors.quota-warn}` — a louder, distinct amber he notices without reading the numbers.
5. He cross-checks Reporting → Marketing & Promo → Coupon Performance: usage count, total sales generated, discount cost, custom date range, exports CSV for his manager.
6. **Climax:** the quota-bar color alone told him this code is about to exhaust before he opened any report — the report is where he confirms it was worth the cost, not where he first learns it's running low.

Failure/edge: 2 customers redeem the last unit of quota at the same instant — atomic redemption guarantees only one succeeds; the second customer's checkout fails validation, no double-spend for Rian to clean up.

### Admin, SEO & Ops (Flows 10–12)

#### Flow 10 — Bagas manages a Role and reviews the Audit Trail (Bagas, Staff Admin/IT)

*Realizes FR-18, FR-19, FR-20.*

1. Bagas opens User & Access → Roles, creates "Supervisor Retur," checks permission boxes ("can approve Retur," "can approve Refund") — no deploy.
2. Opens Users, assigns the new Role to an existing staff account (a User can hold more than one Role, at least one required).
3. That staff member's Approvals Inbox immediately starts surfacing Retur/Refund items — Bagas never touched any approval-specific UI, the permission change propagated to every Role holder instantly.
4. Later, investigating a stock discrepancy, he opens Audit Trail, filters by entity and date range, reads a chronological before/after log — who, what, when — read-only throughout.
5. He deactivates a departing staff member's account: access revokes immediately, but every past Audit Trail entry still names them against their historical actions.
6. **Climax:** he reshaped who can approve what without touching code or waiting on a deploy, and when he needed to reconstruct what happened to a record, the trail was already there — complete, untouchable.

Failure/edge: he looks for an edit or delete control on an Audit Trail row — there isn't one, by design; the read-only floor is absolute.

#### Flow 11 — Fajar manages a Redirect and reviews Broken Links (Fajar, Staff SEO)

*Realizes FR-39, FR-40.*

1. An old Category gets renamed; Fajar opens SEO & Discoverability → Redirect Manager, registers the old slug, a target (an existing Bazaar entity, or a free-form external URL), and an optional reason.
2. He saves — Bazaar stores the mapping but executes no HTTP redirect itself (the portal syncs and executes it on its own schedule); the list shows the mapping as registered with a note that live redirect happens portal-side.
3. Separately, he checks Reporting → SEO Health → Broken Link (404): a table of portal-reported 404 URLs with counts and last-seen timestamps — entirely passively populated, Bazaar never crawls on its own.
4. He spots a spike of 404s against the same old Category path he just redirected — cross-references, confirms his new mapping covers it.
5. **Climax:** two different surfaces — a Manager he edits, a Report he reads — give him both the fix and the evidence the fix was needed, without either one pretending to control what the portal does at request-time.

Failure/edge: he maps to an already-discontinued Brand entity — the reference is still selectable (Bazaar doesn't validate destination liveness); the system doesn't warn further, consistent with this domain's read/write-only, no-execution posture.

#### Flow 12 — Dian edits a Notification Template and audits the Log (Dian, Staff Support/Ops)

*Realizes FR-26, FR-27, FR-32 (by contrast).*

1. Dian opens Notification → Templates, edits "Order Confirmed" subject/body, inserting placeholder tokens like `{{customer_name}}`, `{{order_id}}` — no deploy needed.
2. Saves; toast confirms. The next time that trigger fires — dispatched from the Order domain's own Laravel Event, not from this screen — the new copy is what customers see.
3. She opens Notification → Log, filters to Failed sends from the last 24 hours, finds one failed Order Confirmed email, clicks "Kirim Ulang" (Resend).
4. **Climax:** she edited the words the customer sees and fixed a delivery failure entirely from two Notification screens, never touching the code that decides *when* an email fires — that stays decentralized in each domain's own Events, exactly as expected.

Failure/edge: she looks for the same template-editing experience under Staff In-App Notification and doesn't find one — in-app notifications aren't customer email, they're role-targeted alerts wired to a fixed PRD-defined event list (new Order, Payment Paid, AWB failure, approval-needed, new Review), not staff-authored copy.

### Executive (Flow 13)

#### Flow 13 — Nina (Owner) runs her morning Reporting check (Nina, Brand Store Owner)

*Realizes FR-25, touching Catalog/Order/Finance/Content read-only.*

1. Nina logs in, lands on Dashboard (Home) — stat cards for the day's headline numbers, same visual language every Role sees, but hers surfaces every domain's stat, not a subset.
2. Opens Reporting → Sales & Revenue → Sales Summary for "Kemarin" (Yesterday), then Sales by Product/SKU to see which Item drove revenue.
3. Checks Inventory → Low Stock Alert and Dead Stock — flags 2 Items sitting unsold >90 days, capital tied up.
4. Checks Customer Analytics → Abandoned Cart rate, then Operational & Support → Fulfillment SLA and Return & Refund Report to gauge the team's turnaround.
5. Sets up a weekly Scheduled Report (CSV, Sales Summary) to her own and her ops manager's email, so she doesn't need to open the dashboard every morning going forward.
6. **Climax:** in one sitting, inside one read-only domain, she forms a complete picture of the business's health without opening Catalog, Order, or Finance individually — Reporting's one-way dependency on every other domain is exactly what lets her do this without stepping on anyone's workflow.

Failure/edge: she tries to act on what she sees — clicking a Low Stock Item to fix its Stock number directly from the report — but the report link only routes her out to the real Catalog surface to make that edit; Reporting itself never lets her mutate data from inside it.
