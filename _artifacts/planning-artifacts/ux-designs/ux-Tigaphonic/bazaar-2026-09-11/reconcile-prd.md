# Reconcile — PRD + addendum

Source: `prd.md` + `addendum.md` (`prds/prd-Tigaphonic/bazaar-2026-09-11/`), 9 domains / 40 FRs.

## Carried into EXPERIENCE.md

- All 8 in-scope domains' user-facing FRs → 33 IA surfaces + 5 global-chrome surfaces.
- 3-independent-status-layer Order model (Payment / Order / Shipping), full enum values for each.
- Maker-checker pattern across Item, Blog/Page, Review, Cancel/Retur→Return, Refund — modeled as one reusable Component Pattern with 2 shapes (2-state Draft→Published vs. 3-state Pending→Approved/Rejected).
- Terminology/glossary — mirrored verbatim (with the Retur→Return canonicalization resolved per the terminology decision).
- Timeout Timer family (OTP, payment deadline, review deadline, tokenized-link H+5 default, on-process window, auto-confirm) → Countdown/Deadline Indicator pattern.
- Two-track notification model (customer email via Notification Templates vs. staff in-app bell/badge) plus the newly-decided toast-per-action requirement.
- All 13 confirmed named-protagonist journeys, built from the PRD's implied flows.

## Explicitly out of scope (not designed, not silently dropped)

- **Customer-facing portal UI** — checkout, cart, guest OTP, tokenized order-access page, Review submission form. PRD itself excludes portal UI from Bazaar's scope; this UX session only designs how staff observe/act on portal-originated events, never the portal screens themselves.
- **Package Installation domain (FR-33, FR-34, FR-35)** — 100% CLI/developer-facing (`composer require`, `artisan` commands), no dashboard screen exists to design. Confirmed excluded by the user (Journey #14, Andi/Developer, was cut from the 14-journey draft).
- **FR-36 (Media Upload Optimization), FR-37 (Sitemap Data Feed), FR-38 (Structured Data Feed)** — silent/automatic or backend-only feeds with no staff action and no staff-facing screen. Noted inline in EXPERIENCE.md rather than given a surface.
- **Non-goals carried from the PRD, not revisited here:** multi-currency support, Member Login/Wishlist/Loyalty (schema is auth-ready but deferred to v2+), and a certification/verification module for second-hand trust (PRD's own `[NOTE FOR PM]` flag for revisit on high-value categories — a product-scope decision, not a UX one, so this session does not design for it).

## Notes for downstream (architecture / stories)

- The "Approval Role" being a dynamic permission bundle rather than a fixed role has real UI implications (who a given Approvals Inbox row is even visible to) — EXPERIENCE.md specifies permission-gated visibility, but the actual permission-check logic is an architecture concern.
- Bazaar's per-client feature toggling (New-only / Second-only / Hybrid inventory mix) implies conditional field visibility (e.g. Grading only when Second-condition is active) — EXPERIENCE.md notes this at the Item form level; how the toggle itself is configured/exposed was not detailed in the PRD and was not invented here.
