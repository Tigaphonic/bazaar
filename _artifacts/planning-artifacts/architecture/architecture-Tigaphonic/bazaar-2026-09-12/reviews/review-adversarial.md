---
name: 'Bazaar — Adversarial Architecture Review'
type: architecture-review
lens: adversarial
target: _artifacts/planning-artifacts/architecture/architecture-Tigaphonic/bazaar-2026-09-12/ARCHITECTURE-SPINE.md
created: '2026-09-12'
---

# Adversarial Review — Bazaar Architecture Spine

## Method

For each finding below I name two concrete "units one level down" — two developers or AI
agents, each independently implementing a different epic/story, each following every AD in
the spine to the letter — and show a plausible pair of implementations that would still be
mutually incompatible. Each is a hole the spine should close with a new or tightened AD, not
a style nitpick.

Severity scale: CRITICAL (silent data corruption / financial or inventory integrity failure
in production), HIGH (a whole capability silently degrades or an audit/compliance guarantee
breaks), MEDIUM (feature works but two teams build incompatible contracts requiring rework
to reconcile), LOW-MEDIUM (naming/documentation drift likely to cause a wrong implementation
choice but cheap to fix once noticed).

---

## Finding 1 — Transaction-number counter mechanism is deferred per-entity, but AD-18's
own no-collision guarantee depends on every entity using a concurrency-safe one
**Severity: CRITICAL**

- **AD in question:** AD-18 (identifiers), Deferred note "Transaction-number generation
  algorithm."
- **Unit A:** the developer/agent implementing the Order epic (FR-7–15), who builds
  `ORD-YYYYMMDD-NNNNNN` generation for `Order` using a dedicated `transaction_counters`
  table with an atomic `UPDATE ... SET seq = seq + 1 WHERE key = ? RETURNING seq` (or
  equivalent per-engine atomic increment), matching AD-8's "atomic UPDATE, never
  read-then-write" discipline.
- **Unit B:** the developer/agent implementing the Payment epic (FR-16-17), who also needs
  a transaction number for `Payment` (AD-18 explicitly lists Payment among the entities that
  need one) and, reading the Deferred note's own suggested menu of options ("per-day
  sequence, global sequence, **Settings-backed counter**"), picks the Settings-backed
  counter because `spatie/laravel-settings` is already the established mechanism for
  "runtime, staff-editable operational parameters" (AD-21) and looks like the path of least
  resistance for "just another persisted counter value."
- **The clash:** `spatie/laravel-settings` reads/writes a settings property as a normal
  Eloquent update (typically cached), not through AD-8's atomic guarded-`UPDATE` pattern.
  Under concurrent checkout (exactly the scenario AD-18 says must never collide — "never
  colliding under concurrent checkout (AD-8 discipline)"), two simultaneous Payment
  creations can both read the same counter value before either write lands, producing two
  `PAY-...` records with the identical human-readable number. Order's counter (built by Unit
  A on a real atomic table) is safe; Payment's counter (built by Unit B, equally spec-
  compliant — nothing in the spine forbids the Settings-backed option, the Deferred note
  offers it as one of three legitimate choices) is not. The spine's own Rule for AD-18 is
  violated in production for one entity type but not another, and nothing in this document
  would have caught it at review time because "implementation detail" was explicitly
  waved through to story level.
- **Why this belongs in the spine now, not Deferred:** AD-18 makes a hard collision-safety
  claim ("never colliding... AD-8 discipline") that only holds if the concrete mechanism is
  itself AD-8-compliant. Leaving the mechanism choice open while implicitly endorsing an
  option (Settings-backed) that cannot satisfy the AD it's supposed to serve is a
  self-contradicting deferral.
- **Fix:** Add an AD (or tighten AD-18) that fixes the counter mechanism itself — e.g. "one
  shared `transaction_counters` table keyed by (prefix, date), mutated only through an
  atomic increment matching AD-8's guarded-UPDATE pattern; `spatie/laravel-settings` is
  never used for a monotonic counter." Apply it once, shared by every entity in AD-18's
  list, not reinvented per domain.

---

## Finding 2 — Stock-reservation release on cancel/timeout has no fixed call site, so the
timeout path and the manual-cancel path can silently diverge
**Severity: CRITICAL**

- **AD in question:** AD-8 ("release on cancel/timeout"), AD-12 (state transitions via
  `spatie/laravel-model-states`, guard-checked; scheduled jobs re-check state before
  acting).
- **Unit A:** the developer/agent implementing the Customer-initiated Cancel Request story
  (part of Order FR-7–15), who builds a `CancelOrderAction` (AD-5's Service→Action layering)
  that, after validating and transitioning `OrderStatus`, explicitly calls
  `Catalog\Services\StockService::release($orderId)` inline inside the Action — a fully
  AD-5/AD-8-compliant implementation.
- **Unit B:** the developer/agent implementing the payment-timeout auto-cancel job (AD-17's
  queue/scheduler envelope), who reads AD-12's instruction that "every scheduled/delayed job
  re-checks current state as a guard condition before acting" and implements the job to call
  `OrderStatus::transitionTo(Cancelled)` directly (the state class itself, via
  `spatie/laravel-model-states`), assuming the release-of-stock behavior lives as a
  transition side-effect on the state class (a normal spatie/laravel-model-states pattern),
  not realizing Unit A never put it there — Unit A put it in the Action, one layer up from
  where Unit B's job enters.
- **The clash:** both are AD-8/AD-12-compliant reads of the spine, but the spine never says
  *which layer* owns "call Catalog's Service to release." If stock-release logic lives
  inside `CancelOrderAction` (Unit A's placement) rather than inside the `OrderStatus` state
  class's transition hook, Unit B's timeout job — which transitions the state directly
  without going through the Action — cancels the Order but never releases the reserved
  stock. The unit stays permanently reserved (`quantity_reserved` never decremented) even
  though the Order is Cancelled, silently starving future availability (FR-30) for that
  Item until a manual data fix.
- **Fix:** Add a rule pinning stock-release to exactly one place — most robustly, the
  `OrderStatus` state class's `onTransitionTo(Cancelled)`/timeout-guard hook, with
  `CancelOrderAction` and the timeout job both required to go *through* the state
  transition (never bypass it) so there is exactly one code path that touches
  `Catalog\Services\StockService::release`.

---

## Finding 3 — Reporting's scheduled-report delivery has no drawn dependency on
Notification, so two teams will build two different "send this to people" mechanisms
**Severity: HIGH**

- **AD in question:** AD-28 (ReportSchedule), AD-13 (Reporting is one-directional
  *read-only*), AD-14 (Notification owns delivery history), Structural Seed dependency
  diagram.
- **Unit A:** the developer/agent implementing AD-28's `ReportSchedule` + queued generation,
  who needs to actually deliver the finished CSV/XLSX/PDF to `recipients`. The Structural
  Seed dependency diagram draws **no edge from Reporting to Notification** — only
  `Reporting -.reads.-> {Order, Catalog, Payment, Content, Notification, Seo}` (read-only,
  per AD-13's letter: "Reporting may read any domain's Service"). Taken literally, sending
  an email is a *write* capability, so Unit A concludes Reporting must not call into
  Notification's Service at all, and instead builds its own `Mail::to($recipients)->send(new
  ReportReadyMail(...))` directly inside Reporting's `ExportGenerator`.
- **Unit B:** the developer/agent implementing Notification (FR-26/27), who reads AD-14's
  "Notification domain owns... Notification Log (delivery history, resend)" as the single
  place every outbound customer/staff communication is logged, and builds the Staff-facing
  delivery-history screen (FR-27) purely off `NotificationLog` rows.
- **The clash:** Unit A's report emails never create a `NotificationLog` row (Unit A never
  called Notification's Service — the diagram gave no dependency arrow authorizing it, and
  AD-13's own wording scopes Reporting to reads). Unit B's FR-27 delivery-history view is now
  permanently blind to every scheduled-report delivery: Staff can't see whether a report
  actually sent, can't resend a failed one through the standard mechanism, and any future
  change to outbound-email branding via `NotificationTemplate`'s admin UI has zero effect on
  report emails, because they never went through that template mechanism either.
- **Fix:** Either (a) explicitly carve out an exception in AD-13/AD-28 stating Reporting
  *does* have one write dependency — on Notification's dispatch Service, specifically for
  delivering report output — and add that edge to the Structural Seed diagram; or (b) if
  report delivery is deliberately meant to bypass Notification, say so explicitly in AD-28
  and accept that FR-27's audit trail scope explicitly excludes report deliveries. Silence
  is what creates the divergent-build risk here, not either resolution on its own.

---

## Finding 4 — Return/Refund approval has no assigned owner for restocking, so Catalog and
Order/Payment can each assume the other handles it
**Severity: HIGH**

- **AD in question:** AD-8 ("release on cancel/timeout, decrement... on payment
  settlement" — no return/refund leg is listed), AD-9, AD-6 (cross-domain only via Service).
- **Unit A:** the developer/agent implementing Catalog's Stock service, who reads AD-8's
  enumerated transition list — "reserve on checkout-start, release on cancel/timeout,
  decrement alongside `quantity_on_hand` on payment settlement" — as an exhaustive
  specification of every stock-mutation trigger, and therefore does not build any
  `restock()` entry point at all, since nothing calls for one.
- **Unit B:** the developer/agent implementing `ReturnRequest`/`RefundRequest` approval
  (Order/Payment epics, AD-29's Approvals Inbox owning domains), who assumes physical
  restocking after an approved return is self-evidently part of "approve this return" and
  expects to call `Catalog\Services\StockService::restock($itemId, $warehouseId, $qty)` —
  but per Unit A's build, that method doesn't exist, or (worse, in a slightly different pair
  of builds) Unit A *did* anticipate it generically while Unit B independently decided
  restocking is a manual Staff action performed later through a separate "adjust stock"
  screen, so Unit B's `ReturnRequest`-approval Action never calls Catalog at all.
- **The clash:** depending on which half of the pair ships, you get either a runtime error
  (missing method) or — more dangerously — a silent integrity drift: approved returns never
  put inventory back into sellable stock (permanent under-count, lost sales) or, if a third
  variant has *both* an automatic hook and a manual Staff "adjust stock" habit that Staff
  keep using out of trained habit, a double-restock (over-count, oversells later). None of
  this is a hypothetical: the spine's AD-8 enumeration is the only place stock-mutation
  triggers are fixed, and it has a gap exactly at the return/refund leg.
- **Fix:** Extend AD-8 (or add a new AD) that explicitly assigns the restock trigger: e.g.
  "ReturnRequest approval, once physical receipt is confirmed, calls Catalog's Service to
  increment `quantity_on_hand` via the same atomic path; this is the only restock trigger —
  no separate manual 'adjust stock' UI exists for this flow."

---

## Finding 5 — "Checkout-start" (the moment stock is reserved) is not pinned relative to
the OTP gate, so two builds can differ on when a race window opens
**Severity: MEDIUM-HIGH**

- **AD in question:** AD-8 ("reserve on checkout-start"), AD-27 (OTP is a transient gate
  inside `CheckoutAction`, before Order is confirmed to "diproses").
- **Unit A:** the developer/agent building the Cart→Checkout transition (the page/step
  where the customer commits to buying and is about to be sent an OTP), who reads
  "checkout-start" as "the moment the customer begins the checkout flow" and reserves stock
  the instant the checkout page/API call is invoked, *before* the OTP code is even sent —
  reasoning that otherwise a customer could pass the OTP wait window (during which they
  could reasonably take a minute or two) only to find the item sold out from under them,
  which looks like a foreseeable, and bad, UX gap AD-27 doesn't otherwise mitigate.
- **Unit B:** the developer/agent building `CheckoutAction` per AD-27's literal text — "every
  checkout... passes an OTP step, owned by Order's `CheckoutAction`, before an Order is
  confirmed" — and reads "checkout-start" as the point `CheckoutAction` actually runs to
  completion and creates the `Order` row, i.e. reservation happens *after* OTP success, since
  nothing is reserved for an attempt that might never produce a real Order.
- **The clash:** these are two different race-condition profiles for the exact same feature.
  Under Unit A's build, an abandoned/failed OTP attempt leaves stock reserved with no Order
  yet to eventually time out against (AD-8's "release on cancel/timeout" release path is
  keyed to Order-level cancel/timeout state — if no Order exists yet, what releases it, and
  after how long?). Under Unit B's build, two concurrent customers can both pass OTP for the
  last unit of a low-stock/serialized Item, and only one CheckoutAction's reservation call
  wins the atomic guard — the other fails *after* the customer already received and entered a
  valid OTP, a materially worse failure UX than failing before OTP. Neither is wrong per the
  letter of AD-8/AD-27; they produce different systems.
- **Fix:** Pin "checkout-start" to one explicit point (recommend: reservation happens when
  the checkout attempt begins, pre-OTP, using a short-lived reservation tied to the checkout
  attempt's own token/expiry — not to the not-yet-created Order — with an explicit expiry
  independent of Order-level timeout for the pre-Order window).

---

## Finding 6 — Multi-warehouse Shipment splitting (AD-12) assumes a per-warehouse stock
attribution that AD-9's "single Stock row" reading of Pooled items doesn't provide
**Severity: MEDIUM**

- **AD in question:** AD-9 ("Pooled's '1 Stock row per Item' limit is enforced at the
  Service validation layer... intentionally soft... loosened later when multi-warehouse
  Pooled stock is needed"), AD-12 ("`Shipment` is a distinct entity, one Order : many
  Shipment (one per Warehouse involved)").
- **Unit A:** the developer/agent implementing Catalog's Stock/reservation Service, who
  reads AD-9 as confirming that in v1, a Pooled Item has exactly one Stock row (i.e.
  effectively one warehouse's worth of pooled inventory), and therefore never builds a
  "which warehouse is this reservation against" concept into the reservation API for Pooled
  items — the Service signature is `reserve(itemId, qty)`, no warehouse parameter needed.
- **Unit B:** the developer/agent implementing Order's Shipment-creation/splitting logic
  (needed for AD-12's "one Shipment per Warehouse involved" rule and the ERD's
  `WAREHOUSE ||--o{ SHIPMENT`), who must group an Order's line items by warehouse to create
  the right number of Shipments, and needs Catalog to answer "which warehouse fulfilled this
  specific reserved unit" for every line — including Pooled lines — to do that grouping for
  a mixed Pooled+Serialized Order.
- **The clash:** Unit A's Service contract has nothing to give Unit B: for Pooled lines there
  is no per-reservation warehouse attribution to query, because AD-9 was read (correctly, by
  the letter) as saying pooled stock is single-warehouse in v1. Unit B either (a) hardcodes
  an assumed "default warehouse" for every Pooled line, silently wrong the moment a second
  Pooled-stock warehouse is ever configured (which AD-9 explicitly says is meant to be
  possible to "loosen... later" — without a migration, implying it could land while Order's
  code still hardcodes single-warehouse assumptions with no signal to revisit), or (b)
  independently invents its own warehouse-attribution field on the reservation records,
  duplicating/contradicting whatever Catalog eventually builds when multi-warehouse Pooled
  lands.
- **Fix:** Either state plainly that v1 Shipment-splitting-by-warehouse only ever applies to
  Serialized lines (Pooled lines always ship from one implicit warehouse, so Order's
  splitting logic can hardcode that today), or require Catalog's reservation Service to
  return a warehouse attribution for every reservation (Pooled included) from day one so
  Order's splitting logic never needs special-casing when multi-warehouse Pooled lands.

---

## Finding 7 — Approvals Inbox has no shared contract, so four owning domains can each
answer "can this Staff member act on this item" a different way
**Severity: MEDIUM**

- **AD in question:** AD-29 ("filtered to what the viewing Staff's Role actually holds
  permission to act on... each via that domain's own Service").
- **Unit A:** the developer/agent implementing Catalog's pending-Item approval Service, who
  gates visibility with a plain `spatie/laravel-permission` role check
  (`$staff->hasPermissionTo('approve-items')`), a single boolean with no per-record scoping,
  and exposes it as `ItemService::pendingFor(User $staff): Collection`.
- **Unit B:** the developer/agent implementing Content's pending-Blog/Page approval Service,
  who — because Content editors are commonly scoped per category in the PRD's Staff-role
  model — builds a record-scoped Policy (`$staff->can('approve', $blogPost)`, checked
  per-row against the post's category), and exposes it as
  `ContentService::pendingApprovals(User $staff): LengthAwarePaginator` (paginated, a
  different return shape).
- **The clash:** the Approvals Inbox (User domain, AD-29) has to call both. Nothing in AD-29
  fixes a common method name, return shape (eager/lazy Collection vs paginator), or
  permission-check granularity (blanket role vs per-record Policy) the four owning domains
  must expose. The Inbox page ends up with bespoke per-domain glue code to normalize four
  different shapes into one merged list — exactly the "duplicated per-domain... instead of
  one governed aggregation" outcome AD-29 says it exists to prevent — and, worse, a Staff
  member could see Catalog's items filtered only by blanket role while Content's items are
  filtered by category-scoped Policy, an inconsistency Staff will notice and distrust.
- **Fix:** Add to AD-29 an explicit `Approvable`-style contract every owning domain's
  Service must implement (fixed method signature and return shape, e.g.
  `pendingFor(User $staff): Collection<PendingApprovalItem>` where `PendingApprovalItem` is a
  small DTO), and mandate the permission check be record-scoped (a Policy per approvable
  model) uniformly, not a mix of blanket-role and per-record checks.

---

## Finding 8 — `StaffNotification`'s tab-anchor has no fixed shape or resolution
mechanism, so every domain that raises one can invent a different convention
**Severity: MEDIUM**

- **AD in question:** AD-14 ("Every `StaffNotification` carries a polymorphic reference...
  plus an optional tab-anchor, so the dashboard bell can deep-link straight to the exact
  record and tab").
- **Unit A:** the developer/agent implementing AD-17's health-check alert (Settings domain),
  who sets `tab_anchor` to a literal Filament tab *label* string (`"Queue Health"`) matching
  what's displayed in the UI, since that's the most legible value to hand-author for one
  hardcoded alert type.
- **Unit B:** the developer/agent implementing AD-29's Approvals Inbox notifications (User
  domain, fed by Catalog/Content/Order/Payment), who — needing a machine-resolvable value
  across four different owning Resources' tab structures — sets `tab_anchor` to a Filament
  tab *key* (`"pending_review"`), a snake_case identifier, because that's what Filament's own
  tab API actually expects programmatically.
- **The clash:** the shared bell-notification renderer (built once, presumably in User or
  Notification domain) has to turn `{entity_type, entity_id, tab_anchor}` into a working
  deep link for *every* domain that raises a `StaffNotification`. If it's built against
  Unit B's convention (tab keys), Unit A's human-readable label string won't resolve to a
  real Filament tab and the deep-link silently lands on the record's default tab instead of
  "Queue Health" — a quiet UX regression that's easy to ship because both builds are
  individually AD-14-compliant ("carries... an optional tab-anchor" says nothing about its
  value space).
- **Fix:** Fix the tab-anchor's value space explicitly in AD-14 (e.g. "the tab-anchor is
  always the Filament Resource's tab *key*, never a display label") so every domain raising
  a `StaffNotification` encodes it the same way the shared renderer expects.

---

## Finding 9 — Cross-domain Event payloads have no fixed shape, so a snapshot-sensitive
notification can be built to re-fetch live (and therefore drifted) data
**Severity: MEDIUM**

- **AD in question:** AD-6 (Events for reverse-direction notification), AD-7 (snapshot
  principle for Order/Payment/Shipping/Promo), AD-17 (queue-based async side effects).
- **Unit A:** the developer/agent implementing Order's `OrderCompleted`/`PaymentSettled`
  dispatch, who — following the common, lightweight Laravel convention and nothing in the
  spine forbidding it — dispatches a slim event carrying only the Order's ULID
  (`OrderCompleted(string $orderId)`), on the reasoning that listeners can always refetch
  what they need.
- **Unit B:** the developer/agent implementing the Notification listener for the
  "Order Completed" template trigger (FR-26), who — per AD-17's requirement that retryable
  side effects be event-driven/queued — builds a queued listener that, when it actually
  executes (possibly minutes later, after a queue backlog or a retried job), calls
  `Order::find($orderId)` fresh to populate the template's merge fields (order total, item
  list, price).
- **The clash:** AD-7's snapshot principle exists precisely so a transaction's recorded
  meaning never silently changes after the fact — but it's stated as binding "Order, Payment,
  Shipping, Promo" (the *transaction record* itself), not the Notification payload derived
  from it later. If, between event dispatch and delayed queue execution, the Order's total
  is adjusted (e.g. a partial refund processed in the interim, itself a legitimate Order
  domain mutation), Unit B's notification will render the post-adjustment total under a
  subject line saying "your order is complete" — factually correct about the Order's current
  state, but not a snapshot of what was true when the event fired, an inconsistency neither
  Unit A nor Unit B individually breaks a Rule to create.
- **Fix:** Add a rule (extending AD-6 or AD-7) that Events feeding Notification's queued
  listeners must carry the rendering-relevant data as a snapshot payload on the event itself,
  not merely an id for the listener to re-resolve live — otherwise state Notification
  templates the case where "live-at-send-time" is the deliberately intended behavior.

---

## Finding 10 — Three different names for the same audit mechanism appear in the spine
itself
**Severity: LOW-MEDIUM**

- **AD in question:** AD-22 (prose: "`AuditTrail` via `spatie/laravel-activitylog`"),
  Structural Seed (`User/ Models: User, Role, Permission, AuditLog`), Core-entity ERD
  (`USER ||--o{ AUDIT_LOG : causes`). The underlying package's own default model is
  `Activity` — a fourth name, unmentioned but real once installed.
- **Unit A:** whoever scaffolds the User domain module from the Structural Seed section
  verbatim creates a model class literally named `AuditLog`.
- **Unit B:** whoever writes the FR-20 audit-history Filament Resource, working from AD-22's
  prose, queries/binds against a class named `AuditTrail`.
- **The clash:** these are two different class names for a single concept the spine itself
  never definitively picks. One of the two builds is "wrong" relative to whatever the other
  already scaffolded, and neither is a misreading — both are literal, faithful readings of
  a different section of the same document. (A third real risk: whichever one actually just
  uses `spatie/laravel-activitylog`'s stock `Activity` model without subclassing hits a
  fifth possible name mismatch against both.)
- **Fix:** Pick one name (recommend `AuditTrail`, matching AD-22's own Rule prose, as a thin
  subclass/config of the package's `Activity` model) and make Structural Seed/ERD consistent
  with it.

---

## Finding 11 — Cart's identity before a Customer exists is unaddressed; the ERD omits it
entirely
**Severity: LOW-MEDIUM**

- **AD in question:** AD-26 (guest-first Customer; "guest checkout is create-or-find by
  email"). Note the core-entity ERD lists no Cart/CartItem relationship at all — Cart only
  appears in the Structural Seed module list.
- **Unit A:** the developer/agent building the pre-checkout Cart/CartItem add-to-cart flow,
  who — since no Customer/email exists yet at browse time — keys `Cart` to an anonymous
  session/device token, with `customer_id` nullable and populated only later.
- **Unit B:** the developer/agent building `CheckoutAction` (AD-27), who — reading AD-26's
  "guest checkout is create-or-find by email" as the complete guest-identity story — assumes
  `Cart` is always resolvable directly from whatever `Customer` record checkout just
  created/found, with no separate anonymous-identity reconciliation step in mind.
- **The clash:** nothing pins how Unit B's `CheckoutAction` locates *which* anonymous Cart
  belongs to the customer now identified by email — the two stories' assumptions about the
  Cart's pre-checkout key (session token vs. an eventually-populated `customer_id`) were
  never required to agree, and the ERD gives no relationship to settle it by inspection.
- **Fix:** Add the Cart↔Customer relationship to the core-entity ERD and state explicitly
  how an anonymous Cart is reconciled to the Customer record `CheckoutAction` creates/finds
  (e.g. Cart is keyed by a signed anonymous token carried in a cookie/local-storage value,
  and `CheckoutAction` attaches `customer_id` to that same Cart row at OTP-success time).

---

## Summary Table

| # | Finding | Severity | ADs touched |
| --- | --- | --- | --- |
| 1 | Transaction-number counter mechanism deferred, but one legitimate option (Settings-backed) can't satisfy AD-18's own no-collision guarantee | CRITICAL | AD-18, Deferred |
| 2 | Stock-release on cancel/timeout has no fixed call site (Action vs. state-transition hook) | CRITICAL | AD-8, AD-12 |
| 3 | Reporting's scheduled-report delivery has no drawn dependency on Notification | HIGH | AD-13, AD-28, AD-14 |
| 4 | Return/Refund approval has no assigned restock trigger | HIGH | AD-8, AD-9 |
| 5 | "Checkout-start" reservation point not pinned relative to OTP gate | MEDIUM-HIGH | AD-8, AD-27 |
| 6 | Multi-warehouse Shipment splitting assumes a per-warehouse stock attribution AD-9 doesn't guarantee for Pooled | MEDIUM | AD-9, AD-12 |
| 7 | Approvals Inbox has no shared Approvable contract across 4 owning domains | MEDIUM | AD-29 |
| 8 | StaffNotification tab-anchor has no fixed value space | MEDIUM | AD-14 |
| 9 | Cross-domain Event payloads have no fixed shape; snapshot principle doesn't reach delayed Notification rendering | MEDIUM | AD-6, AD-7, AD-17 |
| 10 | Three names for one audit mechanism (AuditTrail / AuditLog / Activity) | LOW-MEDIUM | AD-22 |
| 11 | Cart↔Customer pre-checkout identity binding is unaddressed; ERD omits the relationship | LOW-MEDIUM | AD-26 |

## Overall Verdict

The spine is unusually disciplined about *domain* boundaries (AD-5/AD-6) and about a handful
of concurrency-critical mechanisms (AD-8, AD-18), but it is exactly at the *seams between*
disciplined ADs — where one AD's enumeration ends and another's begins, or where a
Deferred item's "implementation detail" secretly needed to satisfy an earlier AD's hard
guarantee — that two equally-compliant builders would diverge; two findings (transaction
numbers, stock-release call site) are concrete enough to cause real data corruption in
production if built by two different people/agents exactly as the spine currently allows.
