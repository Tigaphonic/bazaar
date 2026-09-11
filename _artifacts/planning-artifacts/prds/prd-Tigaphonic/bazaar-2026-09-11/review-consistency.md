# Consistency Review — PRD: Bazaar (prd.md + addendum.md)
Reviewed: prd.md, addendum.md (bazaar-2026-09-11)

Scope: cross-reference integrity, contradictions, Glossary/terminology drift, FR numbering integrity, UJ→FR cross-references.

**Totals: 17 findings — 2 Critical, 4 High, 7 Medium, 4 Low.** (Category 4, FR numbering, has zero findings — verified clean.)

---

## 1. Broken or wrong cross-references

### 1.1 — FR-30 cites the wrong FR for the Cart availability-check service
**Severity:** High
**Location:** prd.md §4.1 FR-30 (Consequences, last bullet), cross-checked against §4.2 FR-8 and addendum.md §C

**Quote (FR-30):**
> "Status visibilitas ini tersedia lewat availability-check service yang sama dengan Cart (§4.2 FR-9), supaya portal bisa menampilkan label yang konsisten."

FR-9 is "Checkout & Order Creation" (OTP verification + re-validation at checkout), which has nothing to do with the on-demand availability-check service. The actual availability-check service is defined in **FR-8** ("Manage Cart"):
> "Bazaar menyediakan availability-check service/endpoint on-demand yang bisa dipanggil portal kapan saja untuk menampilkan status ketersediaan tiap Item di Cart." (FR-8)

addendum.md §C independently confirms FR-8 is the right target:
> "Item Visibility (§4.1 FR-30, baru sesi ini): query availability-check (dipakai Cart §4.2 FR-8 dan portal listing) mengevaluasi..."

So the addendum and FR-8 agree with each other, and FR-30 in the PRD body disagrees with both — a clean broken cross-reference, not just an ambiguous one.

**Suggested fix:** Change "(§4.2 FR-9)" to "(§4.2 FR-8)" in FR-30.

---

### 1.2 — FR-31 cites FR-10 for a mechanism actually specified in FR-9
**Severity:** Medium
**Location:** prd.md §4.2 FR-31 (Consequences, 2nd bullet)

**Quote:**
> "Kalau Payment tidak selesai sampai batas waktu terlewati, sistem otomatis mengubah Order Status menjadi Cancelled dan melepas reservasi Stock/unit Serialized yang sempat dibuat saat Order dibuat (§4.2 FR-10)."

The claim being cited — that a reservation is "made when the Order is created" — is FR-9's content: "Item yang lolos validasi direservasi secara atomic pada momen pembuatan Order." FR-10 ("Order Status Lifecycle") only describes the three independent status layers; it never mentions reservation at all. FR-10 does own "Order Status becomes Cancelled" as a lifecycle concept, so the citation isn't nonsensical, but as worded it sits right after "reservasi ... yang sempat dibuat saat Order dibuat," which is squarely FR-9 territory.

**Suggested fix:** Either cite FR-9 (for the reservation-at-creation fact) in addition to/instead of FR-10, or split the sentence so each clause cites its actual source FR.

---

### 1.3 — §6.1 MVP Scope's "(FR-1–FR-29)" range is stale relative to the 8 named domains
**Severity:** Medium
**Location:** prd.md §6.1 MVP Scope, In Scope, bullet 1

**Quote:**
> "8 domain module dengan Filament admin UI penuh (FR-1–FR-29): Catalog, Order, Finance, User & Access, Content, Reporting, Notification, Global Settings."

FR-30 (Catalog), FR-31 (Order), and FR-32 (Notification) all live inside three of these exact 8 domains (§4.1, §4.2, §4.7) but fall outside the stated "FR-1–FR-29" range. The parenthetical reads as an exhaustive FR range for the 8 domains, but it undercounts by at least 3 (FR-32 in particular is a genuine Staff-facing admin-dashboard UI feature, not a background mechanism like FR-30/FR-31, making its omission harder to justify as intentional).

**Suggested fix:** Either drop the numeric range (just name the 8 domains) or update it to reflect all FRs actually inside those domains (FR-1–FR-29 plus FR-30, FR-31, FR-32).

---

### 1.4 — FR-3 Notes points to addendum content that doesn't exist there
**Severity:** Low
**Location:** prd.md §4.1 FR-3, Notes

**Quote:**
> "Snapshot atribut per Item (bukan referensi hidup ke Attribute Template) adalah keputusan arsitektur; detail implementasi → addendum."

addendum.md has no section describing the Attribute Template snapshot mechanism (sections A–I cover Principles, Folder Structure, Inventory Model, Order/Status, Condition vs Inventory Strategy, Customer, Reporting, Notification, Why Single Package — none of them mention Attribute Template snapshot/versioning implementation). The pointer is a dangling promise.

**Suggested fix:** Either add a short subsection to the addendum covering Attribute Template snapshot mechanics, or remove/soften the "→ addendum" pointer in FR-3.

---

## 2. Contradictions

### 2.1 — FR-17's pre-filled Refund default ("full amount") can violate its own Min/Max % constraint
**Severity:** Critical
**Location:** prd.md §4.3 FR-17 (Consequences, bullets 2–3), echoed in §3 Glossary "Refund"

**Quotes:**
> "Field nominal Refund Request menerima input Rupiah bebas (bukan pilihan preset), pre-filled dengan **full amount** (Payment original) sebagai nilai awal yang bisa diubah Finance."

> "Nominal yang disimpan — baik **nilai awal (full)** maupun hasil ubahan Finance — **wajib berada di antara Minimum Refund % dan Maximum Refund % dari total harga Order** (mis. total Rp100.000, Minimum 20% = Rp20.000, **Maximum 80% = Rp80.000** → nominal yang bisa disubmit hanya Rp20.000–Rp80.000)."

The text explicitly claims the pre-filled default is "full amount" (100%), and in the very same bullet's own worked example, Maximum Refund % is 80%. By definition, a 100%-of-payment default value is outside an Rp20.000–Rp80.000 (20–80%) valid range — the form would load with an already-invalid value. This looks like a leftover from the pre-reversal "no partial refund / full amount only" policy (see the FR-17 Notes: "Ini mencabut keputusan 'no partial refund' yang sebelumnya dikunci di sesi ini... direvisi atas permintaan eksplisit user") that wasn't fully reconciled with the new Min/Max % guardrail. The Glossary "Refund" entry repeats the same unreconciled claim: "Default nominal adalah full amount, tapi Finance bisa mengajukan nominal lain dalam batas persentase Minimum/Maximum Refund..."

**Suggested fix:** Decide and state explicitly what the pre-filled value actually is when full amount exceeds Maximum % — e.g., "pre-filled dengan **min(full amount, Maximum Refund %)**" or "pre-filled dengan Maximum Refund % dari total Order, bukan full amount, saat Maximum % < 100%." Update both FR-17 and the Glossary "Refund" entry together.

---

### 2.2 — "Return Requested" Order Status is declared but never actually set by any FR or UJ
**Severity:** High
**Location:** prd.md §3 Glossary "Order", §4.2 FR-10 Notes, vs. §4.2 FR-13 and §2.3 UJ-4

**Quotes:**
> "Order Status minimal mencakup: Processing, Cancelled, **Return Requested**, Returned, Completed." (Glossary "Order")

> "Order Status minimal mencakup Processing, Cancelled, **Return Requested**, Returned, Completed..." (FR-10 Notes)

FR-13 — the FR that actually specifies the Cancel/Retur Request flow in detail — describes only these Order Status outcomes: **Cancelled** (case A, approved), no-change (rejected), and **Returned** (cases B/C, after "Retur Selesai"). At no point does FR-13 state that Order Status transitions to **Return Requested** when a Retur Request is filed and pending approval (the state where this value would obviously apply). UJ-4's three branches also only name Cancelled/Returned/AWB Voided/Returned to Warehouse as outcomes — "Return Requested" never appears in either the FR or its illustrating journey, despite being declared a required minimum status value twice.

**Suggested fix:** Add an explicit transition to FR-13 (e.g., "Saat Retur Request dicatat dan menunggu approval, Order Status → Return Requested") so the declared status value is actually wired into the state machine, or remove "Return Requested" from the Glossary/FR-10 minimum list if it isn't meant to be used.

---

### 2.3 — FR-12's fixed "H+5" tokenized-page expiry breaks the otherwise-universal Timeout Timer pattern
**Severity:** Low
**Location:** prd.md §4.2 FR-12 (Consequences, last bullet), vs. addendum.md §D

**Quote:**
> "Halaman tokenized otomatis tertutup/tidak bisa diakses lagi H+5 (5 hari) setelah perubahan status Order terakhir..."

Every other time-based parameter in the PRD (OTP duration, payment timeout, Review submission deadline, On-Process window) is explicitly routed through the configurable "Timeout Timer" in Global Settings (§4.8 FR-28). This one value is stated as a hardcoded constant in both the PRD and addendum.md §D ("Expiry H+5 dihitung dari timestamp perubahan Order Status terakhir"), with no note explaining why it's exempt from the Global Settings pattern.

**Suggested fix:** Either fold "tokenized page expiry" into the FR-28 Timeout Timer list explicitly, or add a one-line rationale for why it's intentionally fixed (e.g., security/consistency reasons) so it doesn't read as an oversight.

---

## 3. Terminology / Glossary drift

### 3.1 — "Review" is a first-class domain entity with no Glossary entry
**Severity:** Medium
**Location:** §3 Glossary vs. §4.2 FR-12, §4.4 FR-19, §4.7 FR-32; addendum.md §B

FR-12 is built entirely around Review (form, moderation, approve/reject), FR-19 names "moderasi Review" as one of four Approval-Role-gated actions, and FR-32 lists "Review baru butuh moderasi" as an in-app notification trigger. addendum.md §B confirms `Review` is a real model (`Order/ Models: ... Review`). Yet §3 Glossary — which the doc's own header claims is where "FR, UJ, dan SM memakai istilah Glossary verbatim" — has no entry for "Review" at all.

**Suggested fix:** Add a "Review" entry to §3 Glossary (customer product/order review, submitted via tokenized link within a configurable deadline, moderated by Approval Role before showing on the portal).

---

### 3.2 — "Timeout Timer" used as a named concept, never defined in Glossary
**Severity:** Medium
**Location:** §3 Glossary vs. §4.2 FR-12, §4.2 FR-31, §4.8 FR-28, §2.3 UJ-3

"Timeout Timer" is capitalized and used as a specific configuration primitive across FR-12, FR-28 ("Mencakup minimal: Timeout Timer (durasi OTP, batas waktu pembayaran, batas waktu submit Review, window On-Process, auto-confirm, dst.)"), FR-31, and UJ-3's step 3 ("Timeout Timer"). It is never defined in §3 Glossary.

**Suggested fix:** Add a "Timeout Timer" Glossary entry describing it as the family of configurable time-window parameters in Global Settings (OTP duration, payment deadline, Review deadline, On-Process window, etc.).

---

### 3.3 — Notification domain nouns absent from Glossary
**Severity:** Medium
**Location:** §3 Glossary vs. §4.7 FR-26, FR-27, FR-32; addendum.md §B, §H

FR-26 (Manage Notification Template), FR-27 (Notification Log & Monitoring), and FR-32 (Staff In-App Notification) are entirely built around "Notification Template," "Notification Log," and "Staff In-App Notification" as named entities. addendum.md §B confirms real models: `Notification/ Models: NotificationTemplate, NotificationLog, StaffNotification`. None of these three nouns appear in §3 Glossary, even though the entire §4.7 domain is defined by them.

**Suggested fix:** Add Glossary entries for Notification Template, Notification Log, and Staff In-App Notification (or a single combined "Notification" entry that distinguishes the three, mirroring addendum §H's "3 (+1) concern" framing).

---

### 3.4 — Content domain nouns (Hero Banner, Blog, Page, Featured Item) absent from Glossary
**Severity:** Medium
**Location:** §3 Glossary vs. §4.5 FR-21, FR-22, FR-23; addendum.md §B

§3 Glossary defines "Blog Category" and "Page Category" (the taxonomies) but never defines "Blog," "Page," "Hero Banner," or "Featured Item" themselves — the actual entities FR-21/FR-22/FR-23 are about. addendum.md §B confirms all four are real models (`Content/ Models: HeroBanner, Blog, Page, BlogCategory, PageCategory, FeaturedItem, SeoMeta`).

**Suggested fix:** Add Glossary entries for Hero Banner, Blog, Page, and Featured Item alongside the existing Blog Category / Page Category entries.

---

### 3.5 — Glossary "Approval Role" definition is narrower than FR-19's own enumeration
**Severity:** Low
**Location:** §3 Glossary "Approval Role" vs. §4.4 FR-19

**Quotes:**
> "**Approval Role** — role berwenang mem-publish Item Draft & approve Return/Cancellation/Refund (maker-checker)." (Glossary)

> "'Approval Role' yang dipakai di seluruh PRD ini (publish Item §4.1 FR-4, **moderasi Review §4.2 FR-12**, approve Return/Cancellation §4.2 FR-13, approve Refund §4.3 FR-17) merujuk ke Role apa pun yang memegang permission terkait..." (FR-19)

FR-19 explicitly lists four Approval-Role-gated actions, including Review moderation; the Glossary definition lists only three, omitting Review moderation.

**Suggested fix:** Update the Glossary "Approval Role" entry to include Review moderation, matching FR-19's list exactly.

---

### 3.6 — "Return" (Glossary heading, English) vs. "Retur Request" (operative term, Indonesian) — bilingual inconsistency
**Severity:** Low
**Location:** §3 Glossary "Return / Cancellation" vs. "Retur Request"; §4.2 FR-13 title vs. body

The Glossary has a combined entry titled "**Return** / **Cancellation**" (English), but the actual working entity used throughout FR-13 and UJ-4 is "**Retur** Request" (Indonesian transliteration), and FR-13's own section title is "Return & Cancellation" (English) while its body consistently says "Retur Request." This is likely intentional bilingual style (Indonesian prose, English status-enum labels) rather than a bug, but it's worth a PM pass to confirm "Retur" and "Return" are meant to be treated as the same term everywhere, since a reader skimming for "Return Request" (matching the section title) would not find that exact string anywhere in the FR body.

**Suggested fix:** Either standardize on one spelling for the request entity name, or add a one-line note in the Glossary clarifying that "Retur" (ID) and "Return" (EN heading) refer to the same concept.

---

## 4. FR numbering integrity

**No findings.** Verified FR-1 through FR-35 each appear exactly once, as a section header, with no duplicates and no gaps:

- §4.1 Catalog: FR-1, FR-2, FR-3, FR-4, FR-5, FR-6, FR-30 (7)
- §4.2 Order: FR-7, FR-8, FR-9, FR-10, FR-31, FR-11, FR-12, FR-13, FR-14, FR-15 (10)
- §4.3 Finance: FR-16, FR-17 (2)
- §4.4 User & Access: FR-18, FR-19, FR-20 (3)
- §4.5 Content: FR-21, FR-22, FR-23, FR-24 (4)
- §4.6 Reporting: FR-25 (1)
- §4.7 Notification: FR-26, FR-27, FR-32 (3)
- §4.8 Global Settings: FR-28, FR-29 (2)
- §4.9 Package Installation & Integration: FR-33, FR-34, FR-35 (3)

Total = 35, covering FR-1..FR-35 with no gaps or duplicates. The late-added FRs (FR-30–FR-32 interspersed in §4.1/§4.2/§4.7, FR-33–FR-35 in a new §4.9) appearing out of strict numeric order relative to their neighbors is consistent with the documented intentional non-renumbering policy — not flagged as an issue.

---

## 5. UJ cross-references

### 5.1 — UJ-4 "Realizes: FR-15" is almost certainly wrong — should be FR-13
**Severity:** Critical
**Location:** prd.md §2.3 UJ-4, Realizes line, vs. its own Path content and §4.2 FR-13/FR-15

**Quote (UJ-4 Realizes line):**
> "Realizes: FR-15."

UJ-4's entire Path — Cancel Request (case A), Retur Request with AWB void (case B), Retur Request with physical return (case C), approval/rejection branching, resulting Order/Shipping Status values (Cancelled, AWB Voided, Returned, Returned to Warehouse) — is verbatim the content of **FR-13 "Return & Cancellation"**. FR-13's own three lettered cases (A/B/C) map 1:1 onto UJ-4's three lettered branches, down to identical wording ("Cancel Request," "Retur Request," "Retur Selesai," "AWB Voided," "Returned to Warehouse"). FR-15 ("CS Interaction Log") is about logging communication channel/notes/timestamps in Order detail — a concept UJ-4's Path never once describes (no step records "channel, catatan, waktu" of a CS interaction). Citing FR-15 as the *sole* realized FR for UJ-4, while FR-13 (the FR UJ-4 is actually demonstrating) is omitted entirely, is very likely a session-editing artifact — quite possibly the mirror image of finding 5.4 below, where FR-13 shows up in UJ-3's list instead.

**Suggested fix:** Change UJ-4's Realizes line to at minimum include FR-13, e.g. "Realizes: FR-13, FR-15" (keep FR-15 only if CS-log usage is meant to be implied by "lewat CS" in the persona context).

---

### 5.2 — UJ-3 "Realizes" list includes FR-14 (Manage Promo) with no textual connection
**Severity:** High
**Location:** prd.md §2.3 UJ-3, Realizes line

**Quote:**
> "Realizes: FR-10, FR-11, FR-13, FR-14, FR-28, FR-30, FR-31, FR-32."

UJ-3's Path (8 steps: notification → payment → AWB/shipment → delivery → Review) never mentions Promo, voucher, discount, or redemption in any step. FR-14 ("Manage Promo") is entirely about voucher/discount quota and atomic redemption at checkout — a mechanism that, per FR-9, happens during Cart→Order checkout, which is *before* UJ-3's stated Entry State ("Budi baru selesai checkout"). There is no plausible textual anchor for FR-14 inside UJ-3.

**Suggested fix:** Remove FR-14 from UJ-3's Realizes list (it more plausibly belongs, if anywhere, to a checkout-focused journey covering FR-9), or add a Promo-related step to UJ-3's Path if Promo application is meant to be shown there.

---

### 5.3 — UJ-3 "Realizes" list omits FR-12, despite steps 7–8 being FR-12's content verbatim
**Severity:** High
**Location:** prd.md §2.3 UJ-3, Path steps 7–8, vs. §4.2 FR-12

**Quote (UJ-3 steps 7–8):**
> "7. Saat Delivered, Budi dapat email 'barang diterima, cek kondisi' + link tokenized + form Review + batas waktu submit sebelum auto-close.
> 8. Cabang Review — **Diisi dalam waktu:** Order → Selesai; Dewi dapat notif in-app moderasi Review; Dewi approve/reject tayang. **Tidak diisi:** Order auto-Selesai setelah lewat waktu, tanpa notif moderasi ke Dewi."

This is FR-12 ("Order Detail Access & Review") almost word-for-word: tokenized link, Delivered-triggered email, Review form, submission deadline, Completed-on-submit vs. Completed-without-Review-on-timeout, and the moderation-notification distinction. Despite this, FR-12 does not appear in UJ-3's Realizes list ("FR-10, FR-11, FR-13, FR-14, FR-28, FR-30, FR-31, FR-32") — the single FR most directly dramatized by the journey's final two steps is missing.

**Suggested fix:** Add FR-12 to UJ-3's Realizes list.

---

### 5.4 — UJ-3 includes FR-13 while UJ-4 (its natural home) is missing it — likely a list-assignment mix-up
**Severity:** Medium
**Location:** prd.md §2.3 UJ-3 Realizes vs. §2.3 UJ-4 Realizes (see also finding 5.1)

UJ-3's only cancellation-adjacent content is the automatic payment-timeout cancellation in step 3, which is specifically and separately **FR-31** (already correctly listed). The manual, CS-driven, maker-checker Cancel/Retur Request flow that FR-13 actually specifies is not depicted anywhere in UJ-3 — it is, however, depicted in full in UJ-4 (see finding 5.1), which is missing FR-13 from its own Realizes list. Taken together with 5.1, this strongly suggests FR-13 was meant to be listed under UJ-4, not UJ-3, and the two lists diverged during live editing.

**Suggested fix:** Resolve jointly with 5.1 — move FR-13 out of UJ-3's Realizes list and into UJ-4's.

---

## Summary table

| # | Category | Location | Severity |
|---|---|---|---|
| 1.1 | Broken cross-ref | §4.1 FR-30 → "(§4.2 FR-9)" | High |
| 1.2 | Broken cross-ref | §4.2 FR-31 → "(§4.2 FR-10)" | Medium |
| 1.3 | Broken cross-ref | §6.1 "(FR-1–FR-29)" | Medium |
| 1.4 | Broken cross-ref | §4.1 FR-3 Notes → addendum | Low |
| 2.1 | Contradiction | §4.3 FR-17 default vs. Max % | Critical |
| 2.2 | Contradiction | "Return Requested" never set | High |
| 2.3 | Contradiction | §4.2 FR-12 fixed H+5 | Low |
| 3.1 | Glossary drift | "Review" undefined | Medium |
| 3.2 | Glossary drift | "Timeout Timer" undefined | Medium |
| 3.3 | Glossary drift | Notification nouns undefined | Medium |
| 3.4 | Glossary drift | Content nouns undefined | Medium |
| 3.5 | Glossary drift | "Approval Role" incomplete | Low |
| 3.6 | Glossary drift | Return/Retur bilingual | Low |
| 4 | FR numbering | — | None (clean) |
| 5.1 | UJ cross-ref | UJ-4 Realizes: FR-15 only | Critical |
| 5.2 | UJ cross-ref | UJ-3 Realizes includes FR-14 | High |
| 5.3 | UJ cross-ref | UJ-3 Realizes omits FR-12 | High |
| 5.4 | UJ cross-ref | UJ-3 has FR-13, UJ-4 doesn't | Medium |
