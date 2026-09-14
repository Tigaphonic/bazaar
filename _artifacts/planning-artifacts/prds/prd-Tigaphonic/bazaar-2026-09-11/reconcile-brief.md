# Input Reconciliation — Brand Store Package Brief vs. PRD + Addendum

**Source input:** `_artifacts/business-draft/brandstore-package-brief.md`
**Checked against:** `_artifacts/planning-artifacts/prds/prd-Tigaphonic/bazaar-2026-09-11/prd.md` + `addendum.md`
**Date:** 2026-09-11

Scope note: per task instructions, deliberate documented changes are NOT flagged as gaps — this includes Condition dropping "Refurbished", Publish Gate becoming universal (not second-hand-only), OTP verification becoming universal for all checkouts (same generalization pattern as Publish Gate), Refund becoming request+approval with Min/Max% range instead of full-amount-only, and Return/Retur becoming one generic flow instead of per-condition/configurable policy (this resolves the brief's own Open Item #3). These are confirmed in the PRD/addendum as conscious decisions and are treated as covered below.

---

## 1. Latar Belakang & Tujuan (Background/Purpose)

**Covered.** PRD §1 Vision captures the core decision ("bangun package sendiri, general purpose... 1 fondasi domain yang sama") and even strengthens the competitive rationale by naming Lunar PHP specifically as the closest existing option and noting its headless/API gap — detail the brief didn't have.

**Partially covered (rationale trimmed, not a gap):** The brief's supporting arguments for why overriding a generic package is more expensive than building custom (override cost > build cost, especially when second-hand is only used by a few clients; and the specific "hybrid client" cost-of-stitching-two-systems argument) aren't restated in the PRD. This is narrative/rationale color rather than a decision or requirement, so it's reasonable for the PRD to compress it — noting it here only for completeness, not as an oversight worth fixing.

---

## 2. Visi Produk & Model Distribusi

**Covered:** Composer package distribution (not SaaS multi-tenant), one Laravel app/DB per client, feature combinations differing per client (new-only/second-only/hybrid) — all present in PRD §1 Vision.

**Gap found (minor):** The brief has an explicit, concrete schema-level decision: *"Tidak ada kolom `tenant_id` atau isolasi multi-tenant di level schema — karena tiap instalasi memang berdiri sendiri per client."* This specific instruction ("no tenant_id column, no schema-level tenant isolation") does not appear anywhere in the PRD or the addendum — confirmed via text search, the only related text is the narrative "bukan SaaS multi-tenant" in §1 Vision. Since the addendum is explicitly the home for schema-level detail (and other P1-P10-adjacent decisions were carried forward there), this concrete "don't add tenant_id" instruction looks like it fell through the cracks rather than being a deliberate omission — worth adding to the addendum so an architect doesn't reflexively add tenant scoping out of habit.

---

## 3. Prinsip Arsitektur Utama (P1–P10)

**Covered.** Addendum §A reproduces the full P1–P10 table "carried forward unchanged," verified line-by-line against the brief — matches closely, no material drift.

---

## 4. Struktur Package

**Covered.** Addendum §B reproduces the folder structure, updated to match the PRD's final domain/model names (e.g. `Cart`/`CartItem`, `CancelRequest`/`ReturRequest`, `AuditLog` under `User/`, `BlogCategory`/`PageCategory` under `Content/`). Adds an `Install/` folder for the `bazaar:install` Artisan command — a sensible additive elaboration, not a contradiction. Single-package distribution decision (P1) and rationale preserved.

---

## 5. Domain Module Breakdown (5.1–5.8)

### 5.1 Manajemen Katalog
**Covered.** Brand, Category (elaborated into explicit 2-level Main/Sub — the brief only said "taksonomi tetap" without specifying levels, so this is a compatible refinement, not a contradiction), Attribute Template, Item, Stock, Warehouse all present as FR-1–FR-6, plus new FR-30 (Item Visibility) as an additive elaboration.

### 5.2 Manajemen Order
**Covered.** Customer, Order, Shipment, Retur & Pembatalan, Promo, CS, Review Order all present (FR-7–FR-15, FR-31). Cart — brief's "belum diputuskan" open item — is resolved in FR-8 (no reservation until checkout, works for both Pooled and Serialized) and explicitly noted in addendum §D as "baru sesi ini." Promo staying in Order domain (not Finance) and CS/Review being added to Order domain are both preserved as decided.

### 5.3 Finance
**Covered.** Payment (FR-16) and Refund (FR-17) present. Refund's "no partial" → "request+approval within Min/Max% range" change is explicitly annotated in FR-17's Notes as a deliberate revision — correctly excluded from this gap list per task instructions.

### 5.4 Manajemen User
**Covered.** User (FR-18), Role & Permission (FR-19), Audit Trail (FR-20) — audit trail wording ("siapa/apa/kapan/before-after") matches almost verbatim.

### 5.5 Manajemen Konten
**Covered.** Hero Banner (FR-21), Blog/Page (FR-22), Featured Item (FR-23), SEO (FR-24). Brief's Open Item #4 (SEO as own entity vs. concern) is resolved: SEO becomes entity-attached fields, not a separate CRUD module — and the still-undetermined specific fields are correctly carried to PRD §8 Open Questions.

### 5.6 Reporting / Analytics
**Covered.** FR-25 + addendum §G. Specific report list/metrics correctly deferred to PRD §8 Open Questions, consistent with brief's own "detail metrik menyusul."

### 5.7 Notification
**Covered.** Template/Setup (FR-26), Log/Monitoring (FR-27), decentralized dispatch via Events preserved. New FR-32 (Staff In-App Notification) is an additive 4th concern, not present in brief but doesn't contradict anything.

### 5.8 Global Setting
**Covered.** FR-28 includes Timeout Timer, Payment Gateway config, Shipping config, Default Warehouse, General Store Info, plus new Min/Max Refund % (added because of the Refund policy change). List+Edit-only pattern (no ad-hoc parameter CRUD) explicitly preserved in FR-28 consequences.

---

## 6. Keputusan Desain Mendalam per Topik Kunci (6.1–6.7)

### 6.1 Model Inventory Terpadu (Item/Stock/Warehouse)
**Covered, thoroughly.** Addendum §C reproduces the 3-table model, the atomic reservation/commit/release mechanism, the Serialized-locked-at-1 behavior, the v1-vs-permanent 1-row-per-Item distinction (validation-layer for Pooled vs. permanent for Serialized), and Default Warehouse snapshot resolution — nearly verbatim against brief §6.1.

**Gap found (see also §7 below — same root cause):** The brief's finalized Item schema table (§6.1) includes a `grading (nullable, khusus Second)` column. Addendum §C's reproduction of this schema table **omits the `grading` field entirely** — the addendum's Item schema is `id, brand_id, sub_category_id, condition, inventory_strategy, attribute_snapshot (JSON), price, media, publish_status`. No `grading` field, and no note explaining a deliberate removal (unlike the Condition/Refurbished removal, which *is* explicitly annotated in addendum §E). Confirmed via full-text search: "grading" appears exactly once in the entire PRD+addendum, in PRD §1 Vision's narrative description of the problem space ("grading kondisi manual") — it is never turned into a glossary term, an FR, or a schema field. This looks like an oversight, not a decision.

### 6.2 Order — 3-Layer Status + Shipment
**Covered.** Addendum §D reproduces the Payment/Order/Shipping status value lists (PRD FR-10 adds the concrete minimum values, an elaboration), the Shipment-per-Warehouse model, and the guard-condition/state-machine (orchestrated vs. event-driven) pattern nearly verbatim. The brief's flagged-as-undesigned split-shipment allocation algorithm is correctly resolved as moot for v1 (Pooled restricted to 1 warehouse/Item ⇒ no cross-warehouse split-allocation needed) and captured in Non-Goals/Out of Scope.

### 6.3 Condition vs. Inventory Strategy
**Covered — with the deliberate, annotated change** (Condition loses "Refurbished," per addendum §E's explicit note) that's excluded from this gap list per task instructions.

### 6.4 Customer — Guest-First, Auth-Ready
**Covered.** Addendum §F reproduces this section essentially unchanged.

### 6.5 Reporting — Domain Read-Only
**Covered.** Addendum §G reproduces this unchanged.

### 6.6 Notification — 3 Concerns
**Covered**, plus an additive 4th concern (Staff In-App Notification, FR-32) layered on top in addendum §H.

### 6.7 Kenapa Single Package
**Covered.** Addendum §I reproduces this unchanged.

---

## 7. Pola Flow Referensi — Barang Second / Unique Item (§7)

Checked point-by-point:

1. **"1 unit fisik = 1 listing, stok selalu bernilai 1"** — Covered (Glossary "Inventory Strategy," FR-5).
   **Ambiguous/weaker:** the brief's accompanying clarification — *"kalau ada beberapa unit series/model sama, tetap jadi listing terpisah masing-masing — tidak digabung"* (multiple units of the same model stay separate listings, never merged into one listing with qty>1) — is not restated anywhere in PRD or addendum (confirmed via search: no hits for "digabung," "series," or "merge"). It's implied by "Serialized ⇒ qty always 1," but the explicit anti-merging clarification — which matters for anyone modeling the catalog UI — is weaker/absent versus the brief.

2. **Standardized, controlled grading (fixed scale, not free-text), used for badges/catalog filters, distinct from per-category descriptive condition/completeness attributes** — **MISSING.** As detailed in §6.1 above, "grading" as a concept/field does not exist anywhere in the PRD's FRs, Glossary, or the addendum's schema. The brief is explicit that grading (a fixed universal scale) is a *distinct mechanism* from the category-specific descriptive attributes that Attribute Template (FR-3) covers — so this isn't something that can be assumed folded into Attribute Template. This is a real requirement gap, not a scope-cut: the brief lists it as part of the "keputusan final" schema (§6.1) and as a validated pattern from a real case study (§7), and the PRD's own Vision (§1) cites "grading kondisi manual" as a defining characteristic that makes this problem space uncovered by generic packages — yet no FR operationalizes it.

3. **Publish gate (maker-checker)** — Covered, deliberately made universal (excluded from gap list per instructions).

4. **Guest checkout + OTP verification before order confirmed** — Covered, deliberately made universal (FR-9) in the same spirit as Publish Gate; treated as an intentional parallel generalization, not flagged.

5. **Atomic locking per unit at checkout** — Covered (P9, FR-9, FR-5 NFR).

6. **Manual/case-by-case Retur via CS channel, then admin approval** — Covered (FR-13, FR-15), made universal for New and Second — this is the resolution of the brief's own Open Item #3, documented as a decision (§6.2 Out of Scope: "revisit kalau ternyata klien New-heavy butuh self-service").

7. **Refund always full amount, no partial** — Deliberately changed (FR-17 Notes explicitly say so); excluded from this gap list per task instructions.

8. **Trust signal = actual photos + grading, no separate verification/certification module; noted as a conscious trade-off, revisit-per-client** — **Partially covered.** The "no verification module" half is well preserved, including the brief's own "revisit per client" nuance (PRD §6.2 has a `[NOTE FOR PM]` callout that quotes this almost verbatim). But the "grading" half of the trust-signal pairing is missing for the same reason as point 2 above — the trust mechanism as designed in the brief rests on photos *and* grading together, and only the photos half survives explicitly (via the `media` field).

---

## 8. Non-Goals / Di Luar Scope v1 (§8)

**Covered — all 6 brief non-goals present in PRD §5 / §6.2:**
- Member Login/Wishlist/Loyalty ✓
- Split-shipment auto-allocation across warehouses ✓ (consolidated with the multi-warehouse-per-Item non-goal into one PRD bullet — brief treats these as two related but separate non-goals; PRD's single bullet correctly captures that both are moot together since one depends on the other, so substance is preserved even though phrasing is condensed)
- Multi-currency / cross-border ✓
- Second-hand acquisition flow (buy-outright/trade-in/consignment) ✓ — near-verbatim match
- Authenticity verification/certification module ✓ (with the added `[NOTE FOR PM]` preserving the brief's "revisit per client" caveat)
- (Implicit) no partial refund — superseded by the deliberate Refund policy change, and PRD adds a *new*, more precise non-goal ("partial refund outside the Min/Max% range") reflecting that change.

PRD §5 also adds several new non-goals not in the brief (self-service return, automated refund-via-gateway, broadcast/WebSocket infra, storefront rendering) — additive, consistent with decisions made elsewhere in the PRD, not contradictions.

---

## 9. Open Items — Belum Diputuskan (§9)

All 4 open items from the brief are addressed:

1. **Cart for mixed Serialized+Pooled catalog** — Resolved (FR-8): no reservation at add-to-cart for either type; availability-check service on demand instead. Addendum §D marks this "baru sesi ini."
2. **Warehouse allocation algorithm for multi-location Pooled** — Explicitly deferred to v2+ (PRD §6.2 Out of Scope), consistent with the brief's own framing that this would be designed "pasca-v1."
3. **Return/Refund policy — configurable per client or generic?** — Resolved: v1 uses one generic flow for all; configurability deferred with an explicit revisit trigger noted (§6.2 Out of Scope).
4. **SEO — own entity or attached concern?** — Resolved: attached fields on existing entities (FR-24), not a separate module; specific fields correctly pushed to PRD §8 Open Questions.

No gaps here — this section of the brief is fully reconciled.

---

## 10. Rencana Lanjutan (§10)

**Covered / superseded.** The brief's suggested session order (Catalog → Order [incl. Cart & Return policy] → Payment/Shipping → User/RBAC → Content/Reporting/Notification/Settings) isn't restated as a roadmap in the PRD, but that's because the PRD's own §4 structure follows this exact order (4.1 Catalog, 4.2 Order, 4.3 Finance, 4.4 User & Access, 4.5–4.8 Content/Reporting/Notification/Settings) — the recommended process was followed, not dropped. Since the PRD now covers all domains completely, §10's original purpose (sequencing future breakout sessions) is moot. Not a gap.

---

## Summary of Findings

| # | Severity | Location | Finding |
|---|---|---|---|
| 1 | **Gap (missing)** | Brief §6.1 schema, §7 | "Grading" (standardized condition scale, fixed values, distinct from Attribute Template) is a named schema field and explicit reference-flow pattern in the brief, but appears nowhere in the PRD's FRs/Glossary or the addendum's Item schema — only a passing narrative mention in PRD §1 Vision. No annotation suggesting deliberate removal (unlike Condition/Refurbished, which is explicitly noted). |
| 2 | **Gap (minor)** | Brief §2 | Explicit "no `tenant_id` column / no schema-level tenant isolation" decision is not restated in PRD or addendum, though the general "not multi-tenant SaaS" framing survives narratively. |
| 3 | **Ambiguous/weaker** | Brief §7, point 1 | The clarification that multiple units of the same model/series remain separate listings ("tidak digabung") isn't restated — implied but not explicit. |
| 4 | **Ambiguous/weaker** | Brief §7, point 8 | Trust-signal design pairs photos + grading; only the photos half is explicit in PRD, tied to finding #1. |
