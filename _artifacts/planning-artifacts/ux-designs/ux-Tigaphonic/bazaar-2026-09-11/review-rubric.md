# Spine Pair Review — Tigaphonic/bazaar

## Overall verdict

This is a strong, largely clean contract: every `{path.to.token}` reference in both files resolves (verified by full extraction, zero broken refs), DESIGN.md's section order is exactly canonical, all 13 confirmed journeys from `.memlog.md` appear as complete Key Flows (named protagonist, numbered steps, climax, failure/edge path), and the entire named color-token system carries complete light/dark hex pairs. The real misses cluster tightly: the Attribute Reconcile Banner — a load-bearing, thrice-referenced component — has zero DESIGN.md visual spec; a handful of raw (non-token) hex literals used in frequently-hit surfaces (secondary button border, table divider, dropzone border, nav hover, stepper dot) have no dark-mode counterpart despite dark mode being a locked, first-class requirement; and there are several minor naming/traceability loose ends (Alert Banner and Staff Notification Bell behavioral/visual asymmetry, component-name drift between the two files, import citations living only at document level). None of these block a competent downstream consumer, but the Banner and dark-mode gaps are exactly the kind of thing that produces a stalled story or an invented answer during architecture/dev.

## 1. Flow coverage — strong

Checked: EXPERIENCE.md sources frontmatter (prd.md, addendum.md) plus `.memlog.md`'s list of 13 confirmed journeys against the 13 Key Flows. Mapped 1:1 in order (Sari item publish→Flow 1, Rara approvals→Flow 2, Sari reconcile→Flow 3, Dewi AWB→Flow 4, Dewi&Rara cancel/retur→Flow 5, Wulan refund→Flow 6, Tari CS→Flow 7, Yoga blog→Flow 8, Rian promo→Flow 9, Bagas role/audit→Flow 10, Fajar redirect/404→Flow 11, Dian notification→Flow 12, Nina reporting→Flow 13). Every flow has a named protagonist, numbered steps, an explicit **Climax** beat, and a failure/edge path. `*Realizes FR-…*` tags on each flow were checked against the PRD's FR numbering and all resolve correctly (e.g. Flow 1 → FR-1–FR-6 Catalog; Flow 5 → FR-13; Flow 13 → FR-25).

### Findings
- **low** Eight of the 13 flow protagonists (Wulan, Tari, Yoga, Rian, Bagas, Fajar, Dian, Nina) do not appear anywhere in the cited `sources:` (prd.md/addendum.md only name Andi, Sari, Budi, Dewi, Rara) — they were established as session decisions recorded in `.memlog.md`, which is not a frontmatter source (`EXPERIENCE.md` frontmatter, `.memlog.md` line 29). *Fix:* either add `.memlog.md` (or a decisions log) to `sources:`, or fold a one-line note into Key Flows' intro stating persona names beyond the PRD's four are session-confirmed, not PRD-verbatim.
- **low** FR-8 (Manage Cart) is never mentioned in EXPERIENCE.md — no IA row, no explicit "no staff surface" note (unlike FR-33–35 and FR-36–38, which each get an explicit callout) (`EXPERIENCE.md` Information Architecture, SEO & Discoverability section). *Fix:* add a one-line note next to Orders or in a scope-note paragraph confirming Cart has no admin surface by design (customer/portal-only), mirroring the FR-36/37/38 treatment.

## 2. Token completeness — adequate

Checked: every key in DESIGN.md's YAML frontmatter (32 color tokens with full light/dark hex pairs, 11 typography roles, 5 rounded steps, 17 spacing values, 19 named components) against design-md-spec.md's type rules, plus every `{path.to.token}` reference extracted from both files' prose via full-text grep (cross-checked, zero broken references found).

### Findings
- **high** Several raw (non-token) hex literals used in frequently-hit surfaces have no dark-mode counterpart anywhere, despite dark mode being a locked, first-class requirement: `button-secondary.border: '#d7dbdf'` (frontmatter, every secondary button), table row-divider `#f1f2f4` (every Data Table), Dropzone border `#c6ccd1` (every upload surface), nav-item hover `#f4f6f7`, and the Stepper's pending-step dot `#cfd5da` — all in contrast to the fully-paired named-token system, where every one of the 32 color tokens has a `-dark` sibling (`DESIGN.md` lines 191, 216, 313, 317-324, 374, 376). *Fix:* either promote these five to named tokens with `-dark` pairs, or add a short line to each affected Component entry stating the dark-mode value.
- **medium** The Avatar, Checkbox, Toggle Switch, Dropzone, Stepper, Quota Bar, Filter/Grade/Courier Chip, and Empty State components have no `components.*` frontmatter entry at all (prose-only), unlike structurally similar components (sidebar, card, stat-card, alert-banner) that do get a frontmatter object — an inconsistent line between "tokenized" and "prose-only" components with no stated rule for which gets which (`DESIGN.md` frontmatter `components:` block vs. Components section prose). *Fix:* either add frontmatter entries for consistency, or state the criterion (e.g. "components reused compositionally get tokens; leaf controls don't") once in the Components section intro.
- **low** `{typography.nav-item}` and `{typography.pill-text}` are defined in frontmatter but never cross-referenced by path at their actual point of use in body prose (Sidebar Nav item labels; Status Pill text) — `pill-text` is only reachable via an internal frontmatter reference (`components.pill.typography`), and `nav-item` is only mentioned by bare name once, in the Tabs entry, not at its own definition site (`DESIGN.md` lines 99-103, 330, 386). *Fix:* add the explicit `{typography.nav-item}` cite to the Sidebar Nav entry.

## 3. Component coverage — thin

Checked: every component name in DESIGN.md.Components (22 entries) against EXPERIENCE.md.Component Patterns (15 rows), in both directions.

### Findings
- **high** **Attribute Reconcile Banner** has a full behavioral row in EXPERIENCE.md.Component Patterns, is central to Key Flow 3, and is named explicitly in the Maker-Checker section — but has zero DESIGN.md visual spec (no row, no component token, no anatomy for the collapsed-summary/expanded-two-column-diff layout it's described as having) (`EXPERIENCE.md` Component Patterns row "Attribute Reconcile Banner"; `DESIGN.md` Components section, absent). *Fix:* add a DESIGN.md Components row (it can reuse `{colors.warn-bg}`/`{colors.warn-ring}` already defined; needs anatomy/spacing for the two-column diff).
- **medium** **Alert Banner** has a full DESIGN.md visual spec (3 variants, explicit use cases) but no dedicated EXPERIENCE.md.Component Patterns row — its behavior surfaces only incidentally, in one State Patterns line ("Permission-denied module") — so persistence/dismissibility/stacking rules for the other two variants (danger, warn) are never stated behaviorally (`DESIGN.md` Components "Alert Banner"; `EXPERIENCE.md` State Patterns row "Permission-denied module" is the only mention). *Fix:* add an Alert Banner row to Component Patterns covering all three variants' triggers and lifecycle.
- **medium** **Staff Notification Bell** has a full EXPERIENCE.md behavioral row (dropdown, mark-as-read, deep-link, permission-scoped dispatch) but DESIGN.md gives it no dedicated visual anatomy — only a passing mention under Topbar ("icon-button cluster... with optional `{colors.notification-dot}` badge") and the notification-dot color story; the dropdown panel's own layout (item row anatomy, empty state, panel width/shadow) is unspecified (`EXPERIENCE.md` Component Patterns "Staff Notification Bell"; `DESIGN.md` Components "Topbar"). *Fix:* add a Staff Notification Bell (or "Notification Dropdown") row to DESIGN.md.Components.
- **low** Component names drift between the two files for the same thing: "Toast" (EXPERIENCE.md) vs. "Toast Notification" (DESIGN.md); "Order Status Stepper" (EXPERIENCE.md) vs. "Stepper" (DESIGN.md); "Approval Action" (EXPERIENCE.md, the button) vs. "Approval / maker-checker state" (DESIGN.md, the pill labeling rule — arguably a different thing entirely, since the Approve/Reject button itself is only specced generically via Button primary/danger) (`EXPERIENCE.md` lines 140, 146; `DESIGN.md` lines 362, 376, 390). *Fix:* align names verbatim across both files.

## 4. State coverage — adequate

Checked: EXPERIENCE.md's State Patterns table (10 rows) walked against the ~30 IA surfaces for empty/cold-load/focus/error/offline/permission-denied applicability.

### Findings
- **medium** Cold-load is specified only for Dashboard ("Skeleton stat cards matching the 3-up grid"); there is no generalized loading-state pattern for the many Data-Table-driven list surfaces (Items, Orders, Payments, Audit Trail, etc.) that make up most of the IA — a downstream consumer has no committed treatment for "table is fetching its first page" (`EXPERIENCE.md` State Patterns, row "Cold dashboard load"). *Fix:* add a generic "Any list, first load" row (e.g. skeleton rows matching `{spacing.row-padding-y}` rhythm) alongside the Dashboard-specific one.
- **low** Reporting (FR-25) promises CSV/XLSX/PDF export and scheduled reports, but no state pattern covers export-generation failure or scheduled-report delivery failure — Notification Log has an explicit Sent/Failed pattern for a different domain, but Reporting's export/schedule path has none (`EXPERIENCE.md` Information Architecture "Reporting Dashboard"; State Patterns table, no matching row). *Fix:* add a row, or note explicitly that export/schedule failure is out of scope for this spine.
- **low** No offline state is defined anywhere; likely a defensible omission for a desktop, always-connected internal tool (unlike the mobile example spines), but it is never stated as an intentional exclusion the way other omissions in this document are (`EXPERIENCE.md`, no offline row). *Fix (optional):* one line confirming offline is out of scope, for parity with how other exclusions are handled elsewhere in the document.

## 5. Visual reference coverage — adequate

Checked: all 6 files in `imports/` (admin-dashboard, admin-finance-cs, admin-katalog, admin-order, admin-pengaturan-sistem, admin-promo — no `mockups/`/`wireframes/` yet, as expected) against every citation in both spines.

### Findings
- **medium** All six imports are cited by name, but only at two document-level locations — DESIGN.md's Brand & Style "Sourced from" line and EXPERIENCE.md's Inspiration & Anti-patterns section — never inline next to the specific IA rows or components each domain-specific mockup actually illustrates (e.g. `admin-order.html` near "Order detail," `admin-katalog.html` near "Item detail/edit," `admin-pengaturan-sistem.html` near "Global Settings"), unlike the example spines' "→ Composition reference: `mockups/…`" pattern placed directly in Information Architecture (`EXPERIENCE.md` Information Architecture section, no inline import links; compare `experience-example-mobile.md` line 28). *Fix:* add inline "→ see `imports/admin-X.html`" pointers next to the relevant domain sections, even though all six share one system — it still helps a story-writer verify a specific screen.
- No orphans: all six import files are referenced by name somewhere in the pair; spines-win-on-conflict is stated once in each file (DESIGN.md Brand & Style; EXPERIENCE.md frontmatter blockquote).

## 6. Bloat & overspecification — strong

Checked DESIGN.md prose for restated pixel specs, source restatement, and decorative narrative; checked EXPERIENCE.md prose for editorial voice it shouldn't carry.

### Findings
- No findings. DESIGN.md's editorial voice ("quietly competent back-office software") is appropriately scoped to Brand & Style and stays tied to real decisions (density, single-hue discipline); EXPERIENCE.md's Key Flow "Climax" lines read as narrative but match the required shape (same pattern as both example spines) and each ties to a concrete system fact, not decoration. FR citations stay terse (`(FR-1)` inline) rather than restating requirement text. No section reads as unreachable by a downstream consumer.

## 7. Inheritance discipline — adequate

Checked `sources:` resolution, FR/glossary-term verbatim use, and component-name identity across both files (cross-referencing Component coverage §3 rather than re-litigating it here).

### Findings
- **low** See Flow coverage finding 1 — eight persona names are not traceable to the stated `sources:` frontmatter, only to `.memlog.md`.
- **low** See Component coverage finding 4 — Toast/Toast Notification, Stepper/Order Status Stepper naming drift is technically an inheritance-discipline defect (component names not identical across all sections in both files), not just cosmetic.
- The Retur→Return terminology change is a **positive** example of disciplined inheritance: it deviates from the PRD's literal glossary term but is explicitly flagged and justified in its own "Terminology resolved" paragraph (`EXPERIENCE.md` Order Status Model section), traced back to a logged user decision in `.memlog.md` — this is how a deliberate deviation should be handled, and every other glossary term (Attribute Template, Condition, Inventory Strategy, Grading, Timeout Timer, Shipment, Approval Role, etc.) is used verbatim and consistently across both spines and the PRD.

## 8. Shape fit — strong

Checked DESIGN.md's 8 body sections against the canonical order; checked EXPERIENCE.md against the required-default section set and the two invented sections' justification.

### Findings
- No findings. DESIGN.md's sections appear in exact canonical order (Brand & Style → Colors → Typography → Layout & Spacing → Elevation & Depth → Shapes → Components → Do's and Don'ts). EXPERIENCE.md carries every required default (Foundation, IA, Voice and Tone, Component Patterns, State Patterns, Interaction Primitives, Accessibility Floor, Key Flows) plus both required-when-applicable sections (Inspiration & Anti-patterns — sources/memlog show a real reference product and real rejects; Responsive & Platform — breakpoints exist). The two invented sections (Maker-Checker & Approval Pattern; Order Status Model) earn their place: both are heavily cross-referenced from multiple Component Patterns rows and from 6+ Key Flows, and consolidating them once avoids repeating the same cross-domain logic in every flow that touches it.

## Mechanical notes

- **Zero broken `{path.to.token}` references** — full extraction and cross-check of every `{colors.*}`, `{typography.*}`, `{spacing.*}`, `{rounded.*}`, `{components.*}` reference in both files against DESIGN.md's frontmatter found no unresolved paths.
- **Frontmatter completeness**: DESIGN.md has `name`, `description`, `status`, and all five token blocks. EXPERIENCE.md has `name`, `status`, `sources`, `updated` — no `description` key, consistent with the example spine convention (neither example EXPERIENCE.md has one either), so not a gap.
- **Name inconsistencies** (component-name drift, not fixed elsewhere in this doc): "Toast" vs. "Toast Notification"; "Stepper" vs. "Order Status Stepper"; "Approval / maker-checker state" vs. "Approval Action" (see Component coverage finding 4).
- No Mermaid diagrams present in either file — not applicable.
- `sources:` paths in EXPERIENCE.md frontmatter both resolve to real files (`prd.md`, `addendum.md`), confirmed by direct read.
