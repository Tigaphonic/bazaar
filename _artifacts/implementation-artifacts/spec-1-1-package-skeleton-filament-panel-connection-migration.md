---
title: 'Story 1.1: Package Skeleton, Filament Panel Connection & Migration'
type: 'feature'
created: '2026-09-14'
status: 'draft'
route: 'dispatch'
review_loop_iteration: 0
context: [
  '{project-root}/_artifacts/implementation-artifacts/epic-1-context.md',
  '{project-root}/_artifacts/planning-artifacts/architecture/architecture-Tigaphonic/bazaar-2026-09-12/ARCHITECTURE-SPINE.md'
]
---

<frozen-after-approval reason="human-owned intent — do not modify unless human renegotiates">

## Intent

**Problem:** The repo is still the unmodified `spatie/package-skeleton-laravel` scaffold — no Bazaar domain code, no Filament dependency, no way for a Developer to install Bazaar into a client project and get a working admin panel.

**Approach:** Turn the skeleton into a real Composer package: add Filament + the domain packages (permission, settings), stand up the domain-grouped `src/{Install,User,Settings}` layout, ship `bazaar:install` (wires Resources onto the client's existing panel, never creates a new one) and `bazaar:status` (AD-17 queue/scheduler heartbeat), and make `php artisan migrate` run clean (ULID PKs, engine-neutral SQL) for the User & Access + Global Settings domains. Red-phase ATDD tests already exist (7 files, `->skip()`-marked); this story is the green phase.

## Boundaries & Constraints

**Always:** Every new Model's PK is ULID (`HasUlids`), no exceptions (AD-18). Cross-domain calls only through a domain's own Service — `tests/ArchDomainBoundaryTest.php` enforces this the moment `src/{Domain}` dirs exist, don't weaken it. `config/bazaar.php` holds only deploy-time/structural values (incl. the `resources.*` override seam); no Staff-editable runtime value belongs there. `bazaar:install` never edits the client's own PanelProvider source file — panel connection is a self-registering hook via `Filament::serving()`, gated only by config, per the architecture's named extension seams (container-binding override, Events, `config('bazaar.resources.*')` swap).

**Never:** No new panel registration. No full Role/Permission CRUD UI (Story 1.3), Manage User UI (1.4), or Global Settings parameter fields (1.6/1.7) — this story only scaffolds the domains enough for nav groups to exist and migrations to run; deep UI is later stories' scope. No AuditTrail (1.5) or media pipeline (1.10) work here.

</frozen-after-approval>

## Open Questions

- **Kepemilikan tabel/model `User` (Staff)** — options: **A) Bazaar mendefinisikan tabel `users` miliknya sendiri**, terpisah dari apa pun yang mungkin sudah ada di proyek klien (ULID PK bersih sejak awal, guard auth sendiri, cocok dengan framing "Filament panel kosong" — panel belum punya resource sama sekali, dan paket ini secara efektif *menjadi* seluruh backend admin) / **B) Bazaar menempel ke model User existing klien** lewat container-binding swap (`config('bazaar.models.user')`, seam yang sudah disebut arsitektur; tidak ada tabel duplikat, tapi PK tabel `users` bawaan Laravel klien hampir pasti auto-increment bigint — melanggar AD-18 kecuali klien memigrasi ulang tabelnya sendiri, langkah invasif untuk "instalasi ke proyek existing" yang seharusnya cuma 1 command). Rekomendasi: **A** — menghindari konflik ULID dan tidak menyentuh skema klien sama sekali.

## Code Map

- `composer.json` — add `filament/filament` (`require`, resolve to `^5.8`-compatible), `spatie/laravel-permission`, `spatie/laravel-settings`, `filament/spatie-laravel-settings-plugin`; narrow `illuminate/contracts` to the exact string `'^12.0||^13.0'` (PackageManifestTest asserts this literally).
- `src/BazaarServiceProvider.php` — currently bare `PackageServiceProvider` (`hasConfigFile`, `hasViews`, `hasMigration('create_bazaar_table')`, `hasCommand(BazaarCommand::class)`). Replace the single stub migration with the real migrations dir; register both new commands; in `boot()` add a `Filament::serving()` hook resolving the target panel (`config('bazaar.panel')` override, else `Filament::getDefaultPanel()`) and calling `->resources(...)`/`->navigationGroups(['User & Access', 'Global Settings'])` from `config('bazaar.resources.*')`; register the AD-17 heartbeat via `$schedule->call(...)->everyMinute()` (scheduler tick) + a matching queued job (queue tick), both writing to `Cache`.
- `config/bazaar.php` — currently `[]`. Add `resources` (per-domain Resource-class list, the override seam) and `panel` (nullable panel-id override).
- `database/migrations/create_bazaar_table.php.stub` — delete (unused generic placeholder); replace with real `create_users_table` migration (ULID PK) + spatie/laravel-permission's published migration edited so `roles`/`permissions` PKs are ULID too (AD-18 has no exceptions).
- `src/Commands/BazaarCommand.php`, `src/Bazaar.php`, `src/Facades/Bazaar.php` — generic skeleton stubs, no real purpose; delete, replaced by `src/Install/Commands/{BazaarInstallCommand,BazaarStatusCommand}.php`.
- `src/User/Models/User.php` — new: `HasUlids`, `HasRoles` (spatie/laravel-permission), implements `FilamentUser`.
- `src/User/Filament/Resources/UserResource.php` — new: minimal (list only — full CRUD is Story 1.4), nav group `User & Access`.
- `src/Settings/` — new: one placeholder `spatie/laravel-settings` `Settings` class + `filament/spatie-laravel-settings-plugin` page registered under nav group `Global Settings` (no parameter fields yet — Story 1.6/1.7).
- `tests/TestCase.php` — `getEnvironmentSetUp` has migrations commented out and no Filament panel; needs a workbench Filament `PanelProvider` fixture (standard orchestra/testbench pattern) registered as a package provider, plus real migration loading, so `Filament::getDefaultPanel()` resolves in tests.
- `tests/{Unit/PackageManifestTest,Unit/PackageStructureTest,Feature/Install/*,ArchDomainBoundaryTest}.php` — remove each `->skip(...)` once its AC is satisfied; this is the literal red→green flip. Do not change assertions.

## Tasks & Acceptance

**Execution:**
- [ ] `composer.json` -- add/narrow the 4 dependency lines above, then `composer update` -- AC1
- [ ] `config/bazaar.php` -- add `resources` + `panel` keys -- extension seam for AC2/AC4
- [ ] `src/User/Models/User.php` + migration -- ULID Staff table, `HasRoles`, `FilamentUser` -- AC2/AC3/AC4
- [ ] `src/User/Filament/Resources/UserResource.php` -- minimal list resource, nav group `User & Access` -- AC2/AC4
- [ ] `src/Settings/...` + `filament/spatie-laravel-settings-plugin` wiring -- nav group `Global Settings` -- AC2/AC4
- [ ] spatie/laravel-permission migration published + edited to ULID PKs; `config/permission.php` model-key type set to match -- AC3
- [ ] `src/Install/Commands/BazaarInstallCommand.php` (`bazaar:install`) -- publishes config/migrations/views, seeds default Settings row -- AC2
- [ ] `src/Install/Commands/BazaarStatusCommand.php` (`bazaar:status`) -- reports scheduler + queue heartbeat staleness from `Cache` -- AC4
- [ ] `src/BazaarServiceProvider.php` -- `Filament::serving()` resource-registration hook + heartbeat schedule -- AC2/AC4
- [ ] `tests/TestCase.php` + `workbench/app/Providers/Filament/TestPanelProvider.php` -- bootable test panel + real migrations -- prerequisite for all Feature tests
- [ ] delete `src/Commands/BazaarCommand.php`, `src/Bazaar.php`, `src/Facades/Bazaar.php`, `database/migrations/create_bazaar_table.php.stub` -- dead skeleton code
- [ ] remove `->skip(...)` from all 7 existing test files once each behavior is green

**Acceptance Criteria:**
- Given a clean Testbench app with an empty default Filament panel, when `composer require` + `bazaar:install` run, then Bazaar's Resources appear on that same panel and no new panel is registered
- Given the package installed, when `php artisan migrate` runs, then it succeeds with ULID PKs and no MySQL/Postgres-specific SQL in any migration file
- Given install is complete, when the panel loads, then `User & Access` and `Global Settings` nav groups are visible and `bazaar:status` reports both heartbeats
- Given any `src/{Domain}` exists, when `tests/ArchDomainBoundaryTest.php` runs, then no domain's Models/Actions are reachable outside their own Services

## Implementation Notes

## Spec Change Log

## Review Triage Log

## Verification

**Commands:**
- `composer update` -- expected: resolves cleanly, `filament/filament` ^5.8-compatible, `illuminate/contracts` locked to `^12.0||^13.0`
- `vendor/bin/pest` -- expected: all tests pass, zero `->skip()` remaining in the 7 target files
- `vendor/bin/pest --filter=ArchDomainBoundaryTest` -- expected: passes once `src/{Install,User,Settings}` exist
