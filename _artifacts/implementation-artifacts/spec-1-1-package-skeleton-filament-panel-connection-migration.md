---
title: 'Story 1.1: Package Skeleton, Filament Panel Connection & Migration'
type: 'feature'
created: '2026-09-14'
status: 'done'
route: 'dispatch'
review_loop_iteration: 0
baseline_commit: '114c26da64bd374d63aab56b67a19cabf2d46443'
context: [
  '{project-root}/_artifacts/implementation-artifacts/epic-1-context.md',
  '{project-root}/_artifacts/planning-artifacts/architecture/architecture-Tigaphonic/bazaar-2026-09-12/ARCHITECTURE-SPINE.md'
]
---

<frozen-after-approval reason="human-owned intent — do not modify unless human renegotiates">

## Intent

**Problem:** The repo is still the unmodified `spatie/package-skeleton-laravel` scaffold — no Bazaar domain code, no Filament dependency, no way for a Developer to install Bazaar into a client project and get a working admin panel.

**Approach:** Turn the skeleton into a real Composer package: add Filament, stand up the domain-grouped `src/{Install,User,Settings}` layout, ship `bazaar:install` (wires Resources onto the client's existing panel, never creates a new one) and `bazaar:status` (AD-17 queue/scheduler heartbeat), and make `php artisan migrate` run clean and engine-neutral. Role/Permission (`spatie/laravel-permission`, Story 1.3) and real Global Settings parameters (`spatie/laravel-settings`, Story 1.6/1.7) are explicitly out of scope — this story only needs the User & Access and Global Settings nav groups to exist and be non-empty. Red-phase ATDD tests already exist (7 files, `->skip()`-marked); this story is the green phase.

## Boundaries & Constraints

**Always:** Every Model whose migration Bazaar itself authors uses a ULID primary key, no exception (AD-18, amended 2026-09-14). Bazaar never creates its own Staff/`users` table and never hand-edits a vendor migration to force ULID — decided 2026-09-14 (Binyo + Winston): the User & Access domain's backing model is the host app's own authenticatable model, resolved via `config('bazaar.models.user', config('auth.providers.users.model'))`, keeping that table's native schema untouched. Cross-domain calls only through a domain's own Service — `tests/ArchDomainBoundaryTest.php` enforces this the moment `src/{Domain}` dirs exist, don't weaken it. `config/bazaar.php` holds only deploy-time/structural values (incl. the `resources.*` and `models.user` seams); no Staff-editable runtime value belongs there. `bazaar:install` never edits the client's own PanelProvider source file — panel connection is a self-registering hook via `Filament::serving()`, gated only by config, per the architecture's named extension seams.

**Never:** No new panel registration. No new `users`/Staff table or `spatie/laravel-permission` dependency (Story 1.3's job). No `spatie/laravel-settings` dependency or real Settings parameter fields (Story 1.6/1.7's job) — Global Settings gets a placeholder nav entry only. No full Role/Permission CRUD UI, Manage User UI, AuditTrail (1.5), or media pipeline (1.10) work here.

</frozen-after-approval>

## Code Map

- `composer.json` — add `filament/filament` (`require`, resolve to `^5.8`-compatible) only; narrow `illuminate/contracts` to the exact string `'^12.0||^13.0'` (PackageManifestTest asserts this literally). No other new runtime dependency this story.
- `src/BazaarServiceProvider.php` — currently bare `PackageServiceProvider` (`hasConfigFile`, `hasViews`, `hasMigration('create_bazaar_table')`, `hasCommand(BazaarCommand::class)`). Drop the stub migration reference (this story ships none); register the two new commands; in `boot()` add a `Filament::serving()` hook resolving the target panel (`config('bazaar.panel')` override, else `Filament::getDefaultPanel()`) and calling `->resources(...)`/`->pages(...)` + `->navigationGroups(['User & Access', 'Global Settings'])` from `config('bazaar.resources.*')`; register the AD-17 heartbeat via `$schedule->call(...)->everyMinute()` (scheduler tick) + a matching queued job (queue tick), both writing to `Cache`.
- `config/bazaar.php` — currently `[]`. Add `resources` (per-domain Resource/Page-class list, the override seam), `panel` (nullable panel-id override), `models.user` (nullable, defaults to `auth.providers.users.model`).
- `database/migrations/create_bazaar_table.php.stub` — delete (unused generic placeholder). No replacement migration this story — directory is legitimately empty; `php artisan migrate` has nothing of Bazaar's own to run yet.
- `src/Commands/BazaarCommand.php`, `src/Bazaar.php`, `src/Facades/Bazaar.php` — generic skeleton stubs, no real purpose; delete, replaced by `src/Install/Commands/{BazaarInstallCommand,BazaarStatusCommand}.php`.
- `src/User/Filament/Resources/UserResource.php` — new: `getModel()` returns `config('bazaar.models.user')`; minimal list-only table (full CRUD is Story 1.4), nav group `User & Access`.
- `src/Settings/Filament/Pages/GlobalSettings.php` — new: minimal placeholder Filament `Page` (no `spatie/laravel-settings` dependency yet), nav group `Global Settings`, satisfies "domain tampil & berfungsi" until Story 1.6 replaces it with the real List+Edit page.
- `tests/TestCase.php` — `getEnvironmentSetUp` has no Filament panel; needs a workbench Filament `PanelProvider` fixture (standard orchestra/testbench pattern) registered as a package provider, so `Filament::getDefaultPanel()` resolves in tests. Real migrations (Laravel's own `users` table via Testbench's default) load automatically — no change needed for Bazaar's own (empty) migrations dir.
- `tests/{Unit/PackageManifestTest,Unit/PackageStructureTest,Feature/Install/*,ArchDomainBoundaryTest}.php` — remove each `->skip(...)` once its AC is satisfied; this is the literal red→green flip. Do not change assertions (already updated for the AD-18 amendment — see `DomainMigrationTest.php`).

## Tasks & Acceptance

**Execution:**
- [x] `composer.json` -- add `filament/filament`, narrow `illuminate/contracts` to `'^12.0||^13.0'`, then `composer update` -- AC1
- [x] `config/bazaar.php` -- add `resources`, `pages`, `panel`, `models.user` keys -- extension seam for AC2/AC4
- [x] `src/User/Filament/Resources/UserResource.php` -- minimal list resource over `config('bazaar.models.user')`, nav group `User & Access` -- AC2/AC4
- [x] `src/Settings/Filament/Pages/GlobalSettings.php` -- minimal placeholder page, nav group `Global Settings` -- AC2/AC4
- [x] `src/Install/Commands/BazaarInstallCommand.php` (`bazaar:install`) -- confirms panel connection -- AC2
- [x] `src/Install/Commands/BazaarStatusCommand.php` (`bazaar:status`) -- reports scheduler + queue heartbeat staleness from `Cache` -- AC4
- [x] `src/BazaarServiceProvider.php` -- panel resource-registration hook (see Implementation Notes for the `Filament::serving()` → `afterResolving(PanelRegistry::class, ...)` deviation) + heartbeat schedule -- AC2/AC4
- [x] `tests/TestCase.php` + `workbench/app/Providers/Filament/TestPanelProvider.php` -- bootable test panel -- prerequisite for all Feature tests
- [x] delete `src/Commands/BazaarCommand.php`, `src/Bazaar.php`, `src/Facades/Bazaar.php`, `database/migrations/create_bazaar_table.php.stub` -- dead skeleton code
- [x] remove `->skip(...)` from all 7 existing test files once each behavior is green

**Acceptance Criteria:**
- Given a clean Testbench app with an empty default Filament panel, when `composer require` + `bazaar:install` run, then Bazaar's Resources/Pages appear on that same panel and no new panel is registered
- Given the package installed, when `php artisan migrate` runs, then it succeeds and no migration file Bazaar itself ships uses a non-ULID PK or engine-specific SQL
- Given install is complete, when the panel loads, then `User & Access` and `Global Settings` nav groups are visible and `bazaar:status` reports both heartbeats
- Given any `src/{Domain}` exists, when `tests/ArchDomainBoundaryTest.php` runs, then no domain's Models/Actions are reachable outside their own Services

## Implementation Notes

- **Deviation from the frozen block's `Filament::serving()` mechanism:** `ServingFilament` is dispatched only by an HTTP middleware (`Filament\Http\Middleware\DispatchServingFilamentEvent`) — verified by reading `vendor/filament/filament/src`. It never fires for Artisan-command/CLI execution, so it could not make the ATDD tests (which call `$this->artisan('bazaar:install')` directly, no HTTP request) pass. Used instead: `$this->app->afterResolving(PanelRegistry::class, ...)`, registered from `packageRegistered()` (register phase, not boot) so it queues before Filament's own `boot()` eagerly forces `PanelRegistry` resolution. This achieves the exact same intent — panel connection with zero client PanelProvider edits, gated only by `config('bazaar.resources'/'pages'/'panel')` — through a mechanism that works in both HTTP and CLI contexts. *(Corrected post-review — see Review Triage Log: `Panel::resources()`/`pages()` actually **append**, not reset; the hook now calls them once with `(array) config(...)`, no defensive re-merge needed.)*
- `bazaar:install` does not call `vendor:publish` (spec's Code Map suggested "publishes config/views"). No AC or test requires it, and Laravel/`spatie/laravel-package-tools` already auto-load the package's config and views without publishing; publishing is a client opt-in for customization, not something the install command needs to force. Command instead confirms which panel it connected to.
- `bazaar:status` and the heartbeat writers use `Cache` (`bazaar:heartbeat:scheduler_last_tick`, `bazaar:heartbeat:queue_last_processed`) — no new table, consistent with AD-21 (no new Staff-editable-adjacent storage needed for this story).
- Test fixture: `tests/TestCase.php` uses `Orchestra\Testbench\Concerns\WithWorkbench` + a `workbench/app/Providers/Filament/TestPanelProvider.php` (id `admin`, `->default()`, empty — simulating AC2's Given clause) and `$this->loadMigrationsFrom(Orchestra\Testbench\default_migration_path())` for Testbench's own bundled `users`/`sessions` migrations, simulating the host app's pre-existing Laravel core tables. `testbench.yaml` (gitignored, per the original skeleton's own `.gitignore`) is a local CLI-exploration convenience only — `TestPanelProvider` is registered explicitly in `getPackageProviders()`, not relied on via yaml auto-discovery, since that path did not register the provider reliably in this Testbench version.
- Verified pre-review: `vendor/bin/pest` (22 passed, 0 skipped, exit 0), `vendor/bin/pint --test` (clean after one auto-fix pass — import-style only, no assertions touched), `vendor/bin/phpstan analyse` (no errors), `composer validate` + `composer install --dry-run` (lock file in sync).
- **Post-review patches applied directly** (the step-03 implementation subagent had stalled/failed earlier in this run and was not cleanly re-engageable; see Review Triage Log for the 8 findings routed to patch):
  - New `src/Install/Support/PanelResolver.php` — single guarded panel-resolution helper (clear `RuntimeException` instead of a bare `TypeError`/uncaught `NoDefaultPanelSetException`), used by both `BazaarServiceProvider` and `BazaarInstallCommand`.
  - `BazaarServiceProvider`: dropped the redundant/wrong-premise resource-pages re-merge; now `resources((array) config('bazaar.resources'))->pages((array) config('bazaar.pages'))` — also fixes the null-config-unpack fatal.
  - `UserResource::getModel()`: throws a clear `RuntimeException` instead of a `TypeError` if both `bazaar.models.user` and `auth.providers.users.model` are unset.
  - `composer.json`: removed the orphaned `extra.laravel.aliases.Bazaar` entry (pointed at the deleted facade class); refreshed `composer.lock`'s content-hash.
  - New tests closing the 4 verification-gap coverage holes: `tests/Unit/UserResourceModelTest.php` (getModel fallback + override), a `pages()`-registration assertion added to `BazaarInstallCommandTest.php`, stale/healthy assertions added to `BazaarStatusCommandTest.php`, and new `tests/Feature/Install/HeartbeatSchedulingTest.php` (exercises the AD-17 heartbeat write side: `schedule:run` and `RecordQueueHeartbeat::handle()`).
- Verified post-patch: `vendor/bin/pest` (29 passed, 0 skipped, exit 0), `vendor/bin/pint` (clean), `vendor/bin/phpstan analyse` (no errors), plus an ad hoc probe (written and deleted) confirming the panel-not-found guard throws the intended `RuntimeException` rather than a fatal.
- Nothing left incomplete for this story's ACs. Risk carried forward to Story 1.3/1.4/1.6: `UserResource`/`GlobalSettings` are intentionally minimal placeholders per the frozen "Never" list.

## Spec Change Log

- 2026-09-14 — Open Question ("User model ownership") resolved by an AD-18 architecture amendment (Winston + Binyo): Bazaar attaches to the host's own `users` table via `config('bazaar.models.user')` instead of owning a separate ULID Staff table. Consequence surfaced during the resolution: `spatie/laravel-permission` and `spatie/laravel-settings` are not actually needed for this story's ACs (no test exercises Role/Permission or real Settings parameters) — dropped from this story's Code Map/Tasks entirely and deferred to Stories 1.3 and 1.6/1.7, narrowing footprint and avoiding a vendor-migration ULID conflict. KEEP: the rest of the original Code Map (Filament dependency, `bazaar:install`/`bazaar:status` design, `Filament::serving()` registration hook, dead-skeleton cleanup, workbench test fixture) — unaffected, carried forward as-is.

## Review Triage Log

- **medium** — `targetPanel()` (BazaarServiceProvider) and the duplicate resolution inline in `BazaarInstallCommand` return/use a `?Panel` (`PanelRegistry::get()`/`Filament::getPanel()` are both nullable, verified in `vendor/filament/filament/src/PanelRegistry.php` and `FilamentManager.php:372`) as if non-null — a typo'd `config('bazaar.panel')` throws an opaque `TypeError` instead of a clear error. [blind-hunter, edge-case-hunter x3] → **patch**: extract one shared, guarded panel-resolution helper used by both call sites.
- **medium** — `packageRegistered()`'s defensive `[...$panel->getResources(), ...config('bazaar.resources', [])]` merge is based on a wrong premise (Implementation Notes claims `resources()`/`pages()` "reset... rather than merging"); the installed Filament source (`Panel/Concerns/HasComponents.php`) shows both methods **append**. The re-merge is at best redundant and at worst double-registers a real client's pre-existing resources/pages (invisible here only because the test fixture panel starts empty). Also, `config('bazaar.resources', [])`'s default only applies when the key is *missing*, not when a client's own published config explicitly sets it to `null` — that unpacks `null` and fatals. [verification-gap "Other findings", edge-case-hunter x2] → **patch**: drop the redundant re-merge, call `resources()`/`pages()` once with `(array) config(...)`, and correct the Implementation Notes claim.
- **low-medium** — `UserResource::getModel()` fatals with an opaque `TypeError` if both `bazaar.models.user` and Laravel's own `auth.providers.users.model` are null (unusual but not impossible). [edge-case-hunter] → **patch**: guard with a clear exception message.
- **medium** — `composer.json`'s `extra.laravel.aliases.Bazaar` still points at `Tigaphonic\Bazaar\Facades\Bazaar`, which this same diff deletes — an orphaned auto-discovery alias to a nonexistent class. [edge-case-hunter, deletion check] → **patch**: remove the now-dead `aliases` block.
- **medium** — `UserResource::getModel()`'s config-fallback chain (the concrete mechanism of this story's AD-18 resolution) has zero test coverage; existing tests only check that a class-string is registered, never that it resolves to the right model. [verification-gap, pre-verified] → **patch**: add a unit test for the default and the override case.
- **medium** — Panel `pages()` registration (`GlobalSettings`) is never asserted anywhere — only `resources()` is checked, and the "Global Settings" nav-group label would still appear via the separate unconditional `navigationGroups()` call even if the page never attached. [verification-gap, pre-verified] → **patch**: assert `getPages()` contains a Bazaar page after install.
- **medium** — `bazaar:status`'s stale-vs-healthy branch is never exercised; both existing tests hit only the "no heartbeat recorded yet" branch (nothing seeds `Cache` first), so an inverted or broken staleness comparison would ship undetected. [verification-gap, pre-verified] → **patch**: seed `Cache` with a fresh and a stale timestamp and assert both outputs.
- **medium** — The AD-17 heartbeat *write* side (`$schedule->call(...)`, `$schedule->job(new RecordQueueHeartbeat)`) is never run by any test — grep for `Schedule::`/`RecordQueueHeartbeat`/`Bus::fake` under `tests/` returns nothing, so the whole mechanism could be inert in a real deployment while the suite stays green. [verification-gap, pre-verified] → **patch**: add a test that runs the schedule/dispatches the job and asserts the cache keys populate.
- **false** — "`navigationGroups()` overwrites instead of merging, unlike `resources()`/`pages()`." Refuted: `Panel::navigationGroups()` (`Panel/Concerns/HasNavigation.php`) does `$this->navigationGroups = [...$this->navigationGroups, ...$groups]` — merges, does not reset. [blind-hunter]
- **false** — "Dropping Laravel 11 support is undocumented as a consequence." Refuted: the exact `'^12.0||^13.0'` constraint is sourced from `ARCHITECTURE-SPINE.md`'s own Technical Decisions ("Stack floor... illuminate/contracts ^12.0||^13.0"), which the spec's `context:` already points at, and was already the literal, pre-existing `PackageManifestTest` assertion before this story touched it — not a new undocumented side effect of this diff. [blind-hunter]
- **false** — "Frozen-block content rewritten with no visible reopening marker." Refuted: the rewrite is the Open-Question resolution the workflow itself sanctions ("write the answer into the frozen block, delete the entry") — done after a live architecture consultation (Winston, AD-18 amendment) and an explicit human "approve" at the step-02 checkpoint before status moved to `ready-for-dev`. Not visible from the diff alone, but real. [blind-hunter]
- **false** — "Deleting the `Bazaar` class/facade... breaking change with no deprecation path." Refuted: this package has never been tagged/released (`minimum-stability: dev`, empty `CHANGELOG.md`); the deleted class was the unmodified `class Bazaar {}` skeleton stub with zero consumers. (The one real bug this pointed at — the orphaned composer.json alias — is triaged separately above.) [blind-hunter]
- **low, rejected** — "ULID/engine-neutral migration ACs are vacuously true this story." True but not a defect: already explicitly documented in the test file's own comments, the Implementation Notes, and the Spec Change Log; by design, re-exercised the moment any future story adds a real migration. [blind-hunter]
- **low, rejected** — "`config('bazaar.models.user')` fallback duplicated rather than centralized." One call site exists today (Rule of Three); extracting a helper now would be premature abstraction for a single caller. Revisit if Story 1.3/1.4 need the same resolution. [blind-hunter]
- **low, rejected** — "Blade view uses `__()` with no translation infrastructure." `__()` gracefully falls back to the literal string when no translation exists (Laravel default) — harmless no-op today, forward-compatible with Story 1.2's bilingual work already named in the epic context. [blind-hunter]
- **low, rejected** — "`sprint-status.yaml` says `in-progress` while the spec says `in-review`." Expected in-flight state, not a defect: `step-05-present.md` syncs `sprint-status.yaml` to `review` after this step completes. [blind-hunter]

## Verification

**Commands:**
- `composer update` -- expected: resolves cleanly, `filament/filament` ^5.8-compatible, `illuminate/contracts` locked to `^12.0||^13.0`
- `vendor/bin/pest` -- expected: all tests pass, zero `->skip()` remaining in the 7 target files
- `vendor/bin/pest --filter=ArchDomainBoundaryTest` -- expected: passes once `src/{Install,User,Settings}` exist
