# Reconcile — imports/ (6 reference admin-dashboard mockups)

Source: `admin-dashboard.html`, `admin-finance-cs.html`, `admin-katalog.html`, `admin-order.html`, `admin-pengaturan-sistem.html`, `admin-promo.html`. All 6 share one fully consistent design system (identical CSS tokens/components/icon sprite) — reconciled here as a single unit rather than per-file, since there is no cross-file divergence to arbitrate.

## Carried into DESIGN.md (extracted as-is)

- Full light color token set: primary `#00609e` family, neutrals, 6 semantic pill families (success/warn/info/danger/neutral/done), plus `live-dot`/`notification-dot`/`quota-warn`.
- Typography: Poppins (headings/nav/labels) + Mulish (body), 14px base, compact 11–26px type ramp.
- Radius scale (7/8/12px + full-round), two-tier soft shadow system.
- Shell layout: 250px sidebar + 62px topbar, spacing rhythm, grid patterns (stat-grid, grid-2, form-grid, detail-grid).
- Components: Sidebar Nav, Topbar, Card, Stat Card, Data Table (compact/dense), Buttons (primary/secondary/ghost/danger), Form Input, Filter/Grade/Courier Chips, Checkbox, Toggle Switch, Dropzone, Stepper (order timeline), Quota Bar, Alert Banner (danger/info variants — `warn` variant added net-new, see DESIGN.md).
- Domain UI patterns that directly informed EXPERIENCE.md: the 3-layer Payment/Order/Shipping status model, maker-checker approval queue pattern, Grading as fixed-scale chips, dynamic Attribute Template block, settlement/margin breakdown table, CS interaction log with channel icons, "List+Edit only" settings scope banner, promo quota-bar-with-atomic-redemption note.

## Deliberately dropped / not carried

- **"ArmyWatch" brand identity and voice** — placeholder brand name, tagline, and any watch-resale-specific copy. Dropped per locked decision: Bazaar is a general-purpose installable package, not one client's brand; DESIGN.md is brand-neutral.
- **Persona names Bara (Owner) / Dimas (Ops) / Nadia (Finance&CS)** shown in the reference's sidebar footers — not adopted. The UX session's 13 confirmed journeys use PRD-anchored names instead (Sari, Rara, Dewi, Wulan, Tari, Yoga, Rian, Bagas, Fajar, Dian, Nina) — the coincidental overlap with the reference's "Sari" was noted but is not a dependency.
- **Hardcoded "Owner-only" approval lock** on the refund/publish approve button — dropped. PRD defines Approval Role as a dynamic permission bundle, not a fixed role; EXPERIENCE.md specifies generic, permission-gated approval UI instead.
- **Decorative browser-chrome device frame** (traffic-light dots, fake URL bar, 1440px presentation wrapper) — presentation-only artifact of how the mockups were delivered, not part of the real product chrome. Not carried.
- **Unit ID prefix convention (`AW-0231`)** and watch-specific Attribute Template field examples (Kondisi Case / Kaca-Dial / Tali / Mekanisme) — these are illustrative values from the reference's fictional watch-resale scenario, not literal Bazaar field names (Bazaar's Attribute Template is dynamic per real Sub-Category, defined by each install). **Open item, not resolved in this UX session:** Bazaar's actual auto-generated Item ID/SKU format is unspecified in the PRD — flagged for a PM/architecture decision, not fabricated here.
- **Non-token one-off colors** used only decoratively in the mockup chrome (traffic-light dot colors) — not promoted to DESIGN.md tokens, irrelevant to the real product.

## Gaps the reference didn't cover (designed net-new in DESIGN.md, not sourced — see DESIGN.md for detail)

Dark mode, responsive breakpoints, Modal/Dialog, Tabs, Pagination, Toast Notification, real Empty State content, Countdown/Deadline Indicator, Alert Banner `warn` variant.
