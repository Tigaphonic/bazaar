# Review — ARCHITECTURE-SPINE.md (Bazaar, 2026-09-12)

Reviewed against the 7-point good-spine checklist, cross-referenced with:
- PRD: `_artifacts/planning-artifacts/prds/prd-Tigaphonic/bazaar-2026-09-11/prd.md` (FR-1–FR-40)
- Addendum: `_artifacts/planning-artifacts/prds/prd-Tigaphonic/bazaar-2026-09-11/addendum.md`
- Brownfield scaffold: `/Users/mastin/devphp-valet/package-bazaar/composer.json` (unmodified `spatie/package-skeleton-laravel`)
- Live Packagist/Laravel-docs data (fetched 2026-09-12) for every pinned dependency version

## Overall verdict

The spine is substantially strong — genuinely-verified tech stack, near-complete FR/domain coverage, and several exemplary, concrete ADs (AD-8/AD-9 atomic reservation, AD-17 heartbeat, AD-29 Approvals Inbox). But it has at least one internal self-contradiction that would actively misdirect two independently-built stories to different modules (RefundRequest's owning domain), one PRD-vs-spine contradiction (FR-24 SEO metadata modeling), and one real cross-cutting seam (FR-36 media-optimization pipeline) left with no architectural home at all. It is not ready to hand to epic/story writers as-is; it needs a fix-up pass before those three land, plus a handful of minor cleanups.

---

## 1. Does it fix the real divergence points for the level below, missing none?

Mostly yes — 29 ADs cover package boundary, admin lock-in, headless boundary, gateway contracts, layering, cross-domain call discipline, snapshot principle, atomic reservation, inventory model, tenancy, order/shipment state machine, reporting direction, notification's 4 concerns, extension seams, ops heartbeat, identifiers, money representation, auth realms, config-vs-settings, logging, DB portability, release discipline, attribute-template snapshot, guest-first customer, OTP-as-non-auth, reporting exports, and the approvals inbox. That is a genuinely thorough sweep of the PRD's stated mechanisms.

However, several real divergence points are **not** fixed:

- **RefundRequest's owning domain is stated two different ways in the same document** (see §2 finding below) — this is exactly the "two different people/agents diverge" scenario the spine exists to prevent, and here the spine itself is the source of the ambiguity.
- **FR-36's media-optimization pipeline** (one mechanism, hooked at every upload point across Catalog/Content/Settings) has no AD, no Binds entry, and no line in the Structural Seed — despite being an explicit cross-domain uniformity requirement in the PRD. Two developers building Catalog's Item upload and Content's Blog upload independently have nothing telling them to share an implementation.
- **Payment/Shipping webhook ingress** (AD-11 requires Bazaar to receive Midtrans/RajaOngkir callbacks) is never located structurally relative to the *optional* `Http/Api` module — see §7.
- **SEO metadata's actual home** (inline columns per FR-24, vs. a separate `SeoMeta` model per Structural Seed) is unresolved — see §6.

## 2. Is every AD's Rule enforceable and coherent (Binds/Prevents/Rule)?

Most ADs pass this bar cleanly — AD-8, AD-9, AD-12, AD-17, AD-18, AD-21, AD-22, AD-29 in particular give concrete, checkable rules. Two problems found:

- **AD-29 vs. the Capability→Architecture Map vs. Structural Seed disagree on where `RefundRequest` lives.**
  - AD-29's Binds line: *"User (hosts the surface), Catalog, Content, Order, **Payment** (owning domains of the approvable items)"*, and its Rule text: *"pending RefundRequest (**Payment**)"*.
  - Capability→Architecture Map: `Finance (FR-16, FR-17) | src/Payment | ...` — FR-17 is the Refund/RefundRequest FR, mapped to `src/Payment`.
  - Structural Seed's actual module listing: `Order/ Models: Customer, Order, Cart, CartItem, Shipment, CancelRequest, ReturnRequest, RefundRequest, Promo, CsInteraction, Review` — **RefundRequest is placed under `src/Order`, not `src/Payment`.**
  - This structural-seed line is carried over verbatim from the addendum's §B folder structure (which predates AD-29 and the Capability Map, both of which correctly follow the PRD's own §4.3 Finance grouping for Refund/RefundRequest). The addendum even claims its folder structure was "diperbarui mengikuti domain final PRD" (updated to match the final PRD domains) — but this particular line wasn't reconciled. **This is a genuine bug, not just an ambiguity**, and it directly affects where the RefundRequest model, migration, and Service method would be built. Fix: correct the Structural Seed to place `RefundRequest` under `src/Payment` (aligning with AD-29 / Capability Map / PRD §4.3), or explicitly justify keeping it in Order and update AD-29 + the Capability Map to match.

- **AD-5/AD-6's layering and cross-domain rules are stated as absolute ("never", "always") but nothing in the spine ties them to enforcement.** The brownfield `composer.json` already has `pestphp/pest-plugin-arch` as a dev dependency — i.e., the tooling to write automated architecture tests (e.g., "no domain may `use` another domain's `Models\*` outside its own Service") already exists in the scaffold. The spine doesn't mandate any such test, leaving AD-5/AD-6 — bound to *all* domains and the single most load-bearing rule in the whole document — enforced by code-review discipline alone. For a document whose stated purpose is preventing divergence between independently-built units, this is a meaningful checklist-item-2 gap: the Rule is clear, but nothing makes it actually self-enforcing.

Minor: AD-15–24 and AD-27–29 omit the `[ADOPTED]` status tag that AD-1–14, 25, 26 carry. Not a substance issue, but inconsistent enough (in a `status: draft` document) that a reader could wonder if the untagged ones are less final than the tagged ones.

## 3. Could anything under "Deferred" let two independently-built units diverge in a way that matters?

Most Deferred items are appropriately low-risk (PG CI matrix, queue driver choice, per-story dependency graph, Sanctum token rotation, Member Login v2 flow, FR-29 SEO defaults — all genuinely implementation/epic-level or explicitly PRD-flagged as unresolved).

One item is under-specified in a way that matters:

- **"Transaction-number generation algorithm" is deferred entirely to "implementation detail"** (per-day sequence / global sequence / Settings-backed counter — pick one), but AD-18 requires this mechanism for **six different transactional entities spanning at least three domains** (Order, Payment, RefundRequest, Shipment, CancelRequest, ReturnRequest), each of which must be "atomic... never colliding under concurrent checkout." Deferring the algorithm is fine; deferring *whether it's one shared generator or six independent ones* is not — AD-18 itself frames the risk as concurrency collision, which is exactly what would happen if two domains built their own counters with different atomicity guarantees. The Deferred entry should at minimum fix "one shared counter service/trait, reused by every domain that needs a transaction number," leaving only the counter algorithm itself as implementation detail.

## 4. Is named tech verified-current, not stale/hallucinated?

This is a strength of the spine. Checked every pinned version live against Packagist/Laravel docs (today is 2026-09-12):

| Package | Spine says | Verified live | Status |
|---|---|---|---|
| PHP | ^8.4 | matches composer.json exactly | ✓ |
| Laravel / illuminate/contracts | ^12.0\|\|^13.0 | Laravel 13.31.0 is current; 12/13 both live | ✓ |
| Filament | ^5.7 | v5.8.1 current, no v6 planned in 2026 | ✓ (constraint resolves fine) |
| spatie/laravel-permission | ^8.3 | 8.3.0 current, **confirmed requires illuminate ^12\|\|^13** | ✓ — the spine's Deferred claim ("adopting laravel-permission narrows the floor to ^12.0") is independently verified exactly correct |
| spatie/laravel-model-states | ^2.14 | 2.14.2 current | ✓ |
| spatie/laravel-medialibrary | ^11.23 | 11.23.6 current | ✓ |
| spatie/laravel-activitylog | ^5.0 (5.1 once on L13) | 5.1.0 confirmed to require illuminate ^13.0 | ✓ — nuance is accurate |
| spatie/laravel-settings | ^3.9 | 3.9.0 current | ✓ |
| filament/spatie-laravel-settings-plugin | latest compatible | v5.6.7, requires filament ^5.6.7 — full v5 support | ✓ |
| laravel/sanctum | latest compatible | v4.3.3 current | ✓ |
| maatwebsite/excel | ^4.0 | 4.0.2 current, requires PHP ^8.3 (compatible with spine's PHP ^8.4) | ✓ |
| barryvdh/laravel-dompdf | ^3.1 | v3.1.2 current | ✓ |

No stale or hallucinated version found. This checklist item passes cleanly.

## 5. Does it ratify the brownfield scaffold rather than contradict it?

Passes. Cross-checked against the actual `composer.json`:
- Package name `tigaphonic/bazaar`, namespace `Tigaphonic\Bazaar\`, PSR-4 root `src/` — all match the spine's AD-1 and Structural Seed exactly.
- PHP `^8.4` and `spatie/laravel-package-tools` `^1.16` match the spine's Stack table verbatim (explicitly marked "existing skeleton").
- The one real deviation — raising the Laravel floor from the skeleton's `^11.0` to `^12.0` (forced by `spatie/laravel-permission` ^8.3, verified in §4 above) — is explicitly called out with rationale in Deferred, not silently introduced. This is the correct way to handle a brownfield deviation.
- Pest/Testbench/Pint/Larastan are left "unchanged," appropriately not re-litigated.

No contradiction found. (Only a soft note: the skeleton's own `Bazaar` Facade isn't mentioned anywhere in the spine — harmless, since nothing requires a decision about it, but worth a one-line acknowledgment if the Facade is meant to stay unused under the Service-layer discipline.)

## 6. Does it cover the PRD/addendum's full capability set (9 domains, FR-1–FR-40)?

Essentially complete — every FR from 1 to 40 appears in the Capability→Architecture Map. Two problems:

- **FR-24 (entity-level SEO metadata) contradicts the Structural Seed.** FR-24's own text is explicit: SEO fields are *"bukan CRUD/module terpisah"* (not a separate CRUD/module) — they're inline fields added directly to Item/Category/Blog/Page's own forms. Yet the Structural Seed lists a standalone **`SeoMeta`** model under `src/Content`, while Catalog (which owns Item and Category — two of the four entities FR-24 applies to) lists no SeoMeta reference at all, and the dependency diagram has no `Catalog → Content` edge. This leaves genuinely unresolved: are Item/Category's SEO fields plain columns on those models (matching FR-24), or does Catalog quietly depend on Content's `SeoMeta` (contradicting FR-24, and creating an undiagrammed cross-domain dependency that would also need to justify itself against AD-6)? This is carried over unreconciled from the addendum's older §B folder listing, same pattern as the RefundRequest issue in §2.

- **FR-32 (Staff In-App Notification) appears in two Capability Map rows** — both `Order (FR-7–FR-15, FR-31, FR-32)` and `Notification (FR-26, FR-27, FR-32)`. Harmless in substance (many domains legitimately fire the events behind FR-32), but every other row in that table implies one FR → one governing module, so this reads as a copy-paste artifact rather than an intentional multi-owner note.

## 7. Is every "initiative"-altitude structural dimension decided, deferred, or an open question — especially the operational/environmental envelope?

Partially. The spine does have a real "Deployment & operational envelope" section, and it's honest and well-scoped for a package with "no infrastructure of its own": it fixes the PHP/Laravel/DB floor, requires a running queue worker + scheduler (self-checked via AD-17, not assumed), and fixes the release mechanism as Composer/Packagist semver (AD-24) with no centrally-controlled rollout. That's a legitimate, complete answer for those specific sub-dimensions.

Two sub-dimensions of the operational envelope are left completely silent, not even deferred:

- **Where do the Midtrans/RajaOngkir webhook and callback endpoints live, structurally?** AD-11 requires Bazaar to "receive/process the gateway callback/webhook" server-to-server — this must work for **every** install, including pure Service-Layer/monolith clients (FR-34) who never enable the optional API Layer (FR-35, `config('bazaar.api.enabled')`). But the only HTTP-facing module in the Structural Seed, `Http/Api`, is explicitly gated as "optional, config-registered" (AD-3/AD-20), and the dependency diagram draws no edge from any always-on route layer to Payment or Shipping. Whether the webhook route is always registered regardless of `bazaar.api.enabled`, or lives somewhere outside `Http/Api` entirely, is never decided — yet it's a hard requirement for every install that takes real payments, not an edge case.

- **Abuse-resistance / rate-limiting for the FR-40 broken-link endpoint was explicitly handed to architecture by the addendum** ("kalau volume 404 tinggi... itu detail implementasi (rate-limit, agregasi per-URL) untuk arsitektur, bukan keputusan produk" — addendum §K) and the spine never picks it up, not even as a Deferred line. Given this is an unauthenticated, portal-initiated write endpoint, this is a small but real gap in the operational envelope that was specifically flagged for this document to resolve.

Everything else the initiative altitude should own — data model, domain dependency direction, auth-realm split, money/identifier representation, the four logging mechanisms, config-vs-settings split, DB portability — is genuinely decided. The gap is narrow but concrete.

---

## Summary of findings by severity

**Major (fix before epics/stories are written from this spine):**
1. RefundRequest's owning domain contradicts itself between AD-29/Capability Map (`Payment`) and Structural Seed (`Order`).
2. FR-24 ("SEO metadata is not a separate module") contradicts the Structural Seed's standalone `SeoMeta` model under Content, with no resolution for how Catalog's Item/Category get their SEO fields.
3. FR-36's cross-domain media-optimization pipeline has no AD, no Binds entry, and no line anywhere in the Structural Seed despite being an explicit uniform-mechanism requirement spanning three domains.

**Moderate:**
4. Payment/Shipping webhook/callback ingress point is never located relative to the optional `Http/Api` module — a hard requirement for every paying install, not an edge case.
5. Transaction-number generation (AD-18) is deferred without fixing that it must be one shared mechanism across the 6 entities/3 domains that need it — only the algorithm should have been left open.
6. AD-5/AD-6 layering and cross-domain-access rules have no tie to the arch-testing tooling (`pestphp/pest-plugin-arch`) already present in the brownfield scaffold — the spine's most-repeated rule is enforced by convention only.

**Minor:**
7. FR-40's rate-limiting/abuse-resistance question was explicitly deferred to architecture by the addendum and never addressed.
8. Inconsistent `[ADOPTED]` tagging across ADs (1–14, 25, 26 tagged; 15–24, 27–29 not).
9. FR-32 listed under two different Capability Map rows (Order and Notification) with no explanation of the dual ownership.

**Passing cleanly:** tech-version currency (checklist #4) and brownfield ratification (checklist #5) are both solid, verified against live sources — no changes needed there.
