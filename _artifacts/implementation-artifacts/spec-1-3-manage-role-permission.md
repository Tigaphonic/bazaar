---
title: 'Story 1.3: Manage Role & Permission'
type: 'feature'
created: '2026-09-16'
status: 'done'
route: 'dispatch'
review_loop_iteration: 1
baseline_commit: '80633fc22e6ba1e8f535dc316ab4acd1ddad120f'
context: [
  '{project-root}/_artifacts/implementation-artifacts/epic-1-context.md',
  '{project-root}/_artifacts/planning-artifacts/architecture/architecture-Tigaphonic/bazaar-2026-09-12/ARCHITECTURE-SPINE.md',
  '{project-root}/_artifacts/planning-artifacts/ux-designs/ux-Tigaphonic/bazaar-2026-09-11/EXPERIENCE.md',
  '{project-root}/_artifacts/test-artifacts/atdd-checklist-1-3-manage-role-permission.md',
]
---

<frozen-after-approval reason="human-owned intent — do not modify unless human renegotiates">

## Intent

**Problem:** Bazaar belum punya cara Staff mengatur akses tanpa deploy kode: tidak ada Role/Permission dinamis, RoleResource, atau Service yang menjamin propagasi instan dan larangan role hardcoded "Approval Role".

**Approach:** Bangun `src/User/Services/RoleService.php` (create/update/delete Role di atas `spatie/laravel-permission`, `syncPermissions()` untuk replace-bukan-append) dan `src/User/Filament/Resources/RoleResource.php` + 3 Page (List/Create/Edit, plus DeleteAction terkonfirmasi) — membuat 17 test merah (7 file, `->skip()`, lihat `atddChecklistPath`) menjadi hijau tanpa mengubah assertion-nya.

## Boundaries & Constraints

**Always:**
- Presentation code (`RoleResource` + Pages) hanya boleh panggil `RoleService` — tidak pernah `Role::create()`/`$record->update()`/`$record->delete()` langsung (AD-5).
- `RoleService::update()` memakai `syncPermissions()` (bukan `givePermissionTo()` berulang) — replace, bukan append (AC2); ini juga otomatis memicu `PermissionRegistrar::forgetCachedPermissions()` bawaan spatie untuk propagasi instan.
- Payload form `permissions` adalah array of Permission `name` string, bukan id — sudah dikte oleh test yang frozen (`CreateRoleTest`/`EditRoleTest`).
- Migration `spatie/laravel-permission` (belum `->runsMigrations()`, terverifikasi `vendor/spatie/laravel-permission/src/PermissionServiceProvider.php:24-38`) dimuat apa adanya di `tests/TestCase.php` lewat `include` file vendor + panggil `->up()` — tidak pernah disalin ke `database/migrations/` milik Bazaar (AD-18).

**Never:**
- Tidak membuat `PermissionResource` terpisah — AC1 hanya "mencentang" permission yang sudah ada, tidak ada test yang membutuhkannya.
- Tidak menambah `->requiresConfirmation()` manual pada `DeleteAction` — sudah default bawaan Filament (`vendor/filament/actions/src/DeleteAction.php:37`).
- Tidak mengubah assertion di 7 file test yang sudah ada — hanya menghapus `->skip(...)`.
- Tidak menyentuh panel auth (`->login()`) atau migration/ServiceProvider Shell — di luar AC epics.md Story 1.3 (baris 319-338), yang sama sekali tidak menyebut login.

## I/O & Edge-Case Matrix

| Scenario | Input / State | Expected Output / Behavior | Error Handling |
|----------|--------------|---------------------------|----------------|
| Create dengan permission tercentang | `name` + `permissions` valid | Role tersimpan, `hasPermissionTo()` true hanya utk yang dicentang | N/A |
| Create tanpa permission | `permissions=[]` | Role tersimpan, 0 permission | N/A |
| Update mengganti set permission | Role punya permission A, update ke B | `hasPermissionTo(B)` true, `hasPermissionTo(A)` false | N/A |
| Propagasi instan ke pemegang Role | 2 User pegang Role sama, Role diupdate | Kedua User langsung reflect permission baru (`fresh()`) | N/A |
| Nama Role kosong | `name=''` | — | `assertHasFormErrors(['name' => 'required'])` |
| Nama Role duplikat | `name` sudah dipakai Role lain | — | `assertHasFormErrors(['name' => 'unique'])` |
| Delete di-mount tanpa konfirmasi | `mountTableAction('delete')` | Role belum terhapus | N/A |
| Delete dikonfirmasi | `callTableAction('delete')` | Role terhapus | N/A |
| `bazaar:install` tidak seed Role | fresh install | `Role::count()` === 0 | N/A |

</frozen-after-approval>

## Code Map

- `composer.json` -- `spatie/laravel-permission` sudah ditambahkan (`composer require`, terkunci `^8.3`); auto-discovered Testbench (`vendor/orchestra/testbench-core/laravel/bootstrap/cache/packages.php` sudah memuat `Spatie\Permission\PermissionServiceProvider`) — `config('permission.*')` otomatis termuat, tidak perlu didaftarkan manual di `tests/TestCase.php::getPackageProviders()`.
- `tests/TestCase.php` -- di `setUp()`, setelah `loadMigrationsFrom` yang sudah ada: `include`+jalankan `->up()` pada instance yang dikembalikan `vendor/spatie/laravel-permission/database/migrations/create_permission_tables.php.stub` (pola identik test suite spatie/laravel-permission sendiri).
- `workbench/app/Models/User.php` -- tambah `use Spatie\Permission\Traits\HasRoles;` (langkah instalasi manual, AD-16 tidak punya seam untuk menempel trait ke class asing).
- `src/User/Services/RoleService.php` -- baru: `create(array $data): Role`, `update(Role $role, array $data): Role`, `delete(Role $role): void`.
- `src/User/Filament/Resources/RoleResource.php` -- baru, pola identik `UserResource.php` (`getModel()` → `Role::class`, `$navigationGroup = 'User & Access'` -- grup ini sudah teregistrasi di `BazaarServiceProvider::packageRegistered()`, tidak perlu diubah). `table()`: `TextColumn::make('name')` + `DeleteAction::make()->action(fn (Role $r) => app(RoleService::class)->delete($r))`. `form(Schema $schema)`: `TextInput::make('name')->required()->unique(table: 'roles')` + `CheckboxList::make('permissions')->options(fn () => Permission::pluck('name', 'name'))`.
- `src/User/Filament/Resources/RoleResource/Pages/{ListRoles,CreateRole,EditRole}.php` -- baru, pola identik `UserResource/Pages/ListUsers.php`. `CreateRole::handleRecordCreation()` dan `EditRole::handleRecordUpdate()` dioverride untuk panggil `RoleService` (bukan default Model-langsung).
- `config/bazaar.php` -- tambah `RoleResource::class` ke array `'resources'` (pola sama seperti `UserResource::class`).
- 7 file test (`tests/Feature/User/{RoleServiceTest,RolePermissionPropagationTest,NoHardcodedRoleTest}.php`, `tests/Feature/User/RoleResource/{ListRolesTest,CreateRoleTest,EditRoleTest,DeleteRoleTest}.php`) -- hapus `->skip(...)`, jangan ubah assertion.

## Tasks & Acceptance

**Execution:**
- [x] `tests/TestCase.php` -- load migration `spatie/laravel-permission` apa adanya -- prasyarat semua test
- [x] `workbench/app/Models/User.php` -- tambah trait `HasRoles` -- prasyarat AC2 propagation test
- [x] `src/User/Services/RoleService.php` -- create/update/delete via `syncPermissions()` -- AC1/AC2/AC3, unskip `RoleServiceTest`
- [x] unskip `RolePermissionPropagationTest`, `NoHardcodedRoleTest` -- murni pembuktian atas `RoleService` di atas
- [x] `src/User/Filament/Resources/RoleResource.php` + `Pages/ListRoles.php` -- daftar Role, kolom `name`, nav group -- AC1, unskip `ListRolesTest`
- [x] `config/bazaar.php` -- daftarkan `RoleResource::class`
- [x] `Pages/CreateRole.php` -- form + `handleRecordCreation` via Service -- AC1, unskip `CreateRoleTest`
- [x] `Pages/EditRole.php` -- pre-fill + `handleRecordUpdate` via Service -- AC2, unskip `EditRoleTest`
- [x] `DeleteAction` di `table()` `RoleResource` via Service -- AC3, unskip `DeleteRoleTest` -- 3/3 test lulus (1 assertion diganti ke API non-deprecated, human-approved, lihat Spec Change Log)

**Acceptance Criteria:**
- Given Staff berwenang membuka User & Access → Roles, when Staff membuat Role baru dan mencentang permission, then Role tersimpan dengan permission granular tsb tanpa deploy kode
- Given sebuah Role dipakai beberapa User, when Staff mengubah permission Role tsb, then perubahan berlaku ke semua User pemegang Role itu secara instan
- Given "Approval Role" dirujuk di berbagai domain, when Staff memeriksa konsep ini, then sistem tidak punya role hardcoded bernama itu — dan Role bisa dihapus dari dashboard dengan modal konfirmasi

## Implementation Notes

- `tests/TestCase.php` needed `protected $enablesPackageDiscoveries = true;` in addition to the `include`+`->up()` migration step — Testbench's own `Orchestra\Testbench\TestCase` defaults `$enablesPackageDiscoveries` to `false`, which makes `InteractsWithWorkbench::ignorePackageDiscoveriesFromUsingWorkbench()` return `['*']` and reject every vendor package's auto-discovered provider (verified via `vendor/orchestra/testbench-core/src/{TestCase.php,Concerns/InteractsWithWorkbench.php}`) — so `Spatie\Permission\PermissionServiceProvider` never booted and `config('permission.*')` stayed empty (confirmed empirically: a debug dump showed `config('permission')` was `null` before this flag, fully populated after). The Code Map's claim that this config is auto-loaded without any TestCase change was correct in spirit but incomplete — this one extra flag was still required. Laravel's `Application::register()` dedupes providers by class name, so this has no effect on the 4 providers already explicit in `getPackageProviders()`.
- `RoleService::create()` calls `Role::query()->create([...])` rather than the static `Role::create([...])` spatie ships — the latter is typed `@return RoleContract|Role` in `vendor/spatie/laravel-permission/src/Models/Role.php:51`, which PHPStan correctly refuses to narrow to this Service's declared `: Role` return type. Going through `Role::query()->create()` (Eloquent's own generically-typed builder) keeps the return type concretely `Role` without a cast/assert/ignore. This intentionally forgoes spatie's own `RoleAlreadyExists`-throwing duplicate pre-check inside its static `create()` — safe here because `RoleResource`'s own form validation (`->unique(table: 'roles')`) already guards duplicate names before this Service is ever reached (AC1's "Nama Role duplikat" case, `CreateRoleTest`).

## Spec Change Log

- **Finding:** `DeleteRoleTest`'s "it does not delete the Role merely by mounting the delete confirmation modal" failed against a correct `RoleResource` — root cause is a Filament v5.8.1 testing-helper mismatch, not application code (see Review Triage Log entry below for full evidence).
  **Amended:** Binyo explicitly renegotiated the frozen "Never: Tidak mengubah assertion di 7 file test yang sudah ada" boundary for this one line only. `tests/Feature/User/RoleResource/DeleteRoleTest.php`'s `->assertTableActionMounted('delete')` was replaced with `->assertActionMounted(TestAction::make('delete')->table($role))` — independently verified in a throwaway scratch test to prove the identical behavior (mount-without-confirm ≠ delete) before applying.
  **Avoids:** leaving a permanently-red test that can never pass under the deprecated helper for any record-scoped action, regardless of `RoleResource`'s implementation.
  **KEEP:** the rest of the frozen boundary (no other assertion in any of the 7 test files was touched) still holds.

## Review Triage Log
- `patch`: RoleResource::table() uses wrong Actions namespace (Filament\Actions instead of Filament\Tables\Actions) causing potential crash.
- `patch`: ListRolesTest.php is missing expect($panel->getResources())->toContain(RoleResource::class) which was incorrectly reverted, leaving unused import.
- `patch`: BazaarInstallCommand silently skips vendor:publish if command missing, rather than returning failure.
- `patch`: BazaarInstallCommandTest misses asserting vendor:publish actually executes.
- `patch`: DeleteRoleTest lacks notification verification (->assertNotified()).


- **medium, fixed** — `DeleteRoleTest` "it does not delete the Role merely by mounting the delete confirmation modal" failed against correct `RoleResource` code. Root cause: `Filament\Tables\Testing\TestsActions::assertTableActionMounted(string|array $actions)` takes no record parameter, so `parseNestedTableActions($actions)` (called with `$record = null`) builds an expected context of `['table' => true]` — but the preceding `->mountTableAction('delete', $role)` call unconditionally stores `['table' => true, 'recordKey' => '1']` (`vendor/filament/tables/src/Testing/TestsActions.php:332-343` + `391-419`). This mismatch is inherent to testing *any* row-scoped Filament table action with this deprecated helper once a record is passed to `mountTableAction()` — not specific to `RoleResource`. Confirmed independently (not just from the implementation subagent's report): reproduced the exact failing-assertion diff myself, read the vendor source for both `assertTableActionMounted()` and the base `assertActionMounted()`/`TestAction::table()`, and verified the fix in an isolated throwaway test file before applying it to the real spec. → **patch**, applied per Spec Change Log above.

### Review pass (blind-hunter, edge-case-hunter, verification-gap — 15 findings triaged)

- **medium** [verification-gap] — `config/bazaar.php`'s `'resources'` array registers `RoleResource::class`, but no test asserts that specific class is present — `BazaarInstallCommandTest.php`'s only related assertion checks the resources list is merely non-empty (already satisfied by pre-existing `UserResource::class`), and all 4 `RoleResource` test files exercise the Livewire page classes directly, never the panel's registered resource list. Verified: `grep -rn "getResources" tests/` and `grep -rln "RoleResource" tests/` confirm no test would catch `RoleResource::class` being dropped or misspelled in the config array — a real regression there would silently remove the AC1 "User & Access → Roles" entry point from the actual panel with zero test failures. → **patch, fixed** — assertion added to `ListRolesTest.php`, verified: `Filament::getDefaultPanel()->getResources()` now checked to contain `RoleResource::class`.
- **medium** [verification-gap "Other findings"] — `RoleResource::table()`'s `DeleteAction::make()->action(function (Role $record): void { app(RoleService::class)->delete($record); })` fully replaces Filament's default action closure, which normally wraps the delete in `$this->process()` and calls `$this->success()`/`$this->failure()` to drive the post-action notification. Verified against `vendor/filament/actions/src/DeleteAction.php` (default closure) and confirmed `Filament\Actions\Concerns\CanCustomizeProcess::using()` exists specifically to swap what `process()` calls while leaving the wrapping success/failure/confirmation logic intact — Staff currently get no toast after deleting a Role, and any exception from `RoleService::delete()` bubbles uncaught instead of a graceful failure notification. This also violates epic-1-context.md's UX rule "Toast (wajib tiap aksi state-changing...)". → **patch, fixed** — `RoleResource::table()` now uses `DeleteAction::make()->using(fn (Role $record) => app(RoleService::class)->delete($record))`, verified against the current file.
- **false** [blind-hunter] — "`sprint-status.yaml` (`in-progress`) tidak sinkron dengan status frontmatter spec (`in-review`)." Ditolak: pola yang sama persis sudah didokumentasikan & diterima di Review Triage Log Story 1.2 — `step-05-present.md` men-sinkronkan `sprint-status.yaml` ke status review setelah step ini selesai; state in-flight yang diharapkan, bukan defect.
- **false** [blind-hunter] — "no `EditAction`/click-through wired, Staff cannot reach Edit from the Roles list." Disproven by `vendor/filament/filament/src/Resources/Pages/ListRecords.php:161-192`: `makeTable()`'s default `recordUrl` closure falls back to `$resource::hasPage('edit')` + `$resource::canEdit($record)` and resolves `getResourceUrl('edit', ...)` even with zero registered table actions. `RoleResource::getPages()` registers the `edit` route, so row-click-to-edit works out of the box — matches EXPERIENCE.md's own documented pattern ("klik baris buka detail").
- **defer** [blind-hunter] — "No authorization/policy gate exists for `RoleResource`." Real (confirmed: zero `canAccess()`/Policy/Gate usage anywhere in `src/` or `config/`), but pre-existing across the *entire* codebase — `UserResource` (Story 1.1) has none either, no panel `->login()` exists yet (per spec-1-2's own Implementation Notes), and no Policy infrastructure has been introduced by any story so far. Not caused or exposed specifically by this diff.
- **false** [blind-hunter] — "`RoleService::create()` bypassing spatie's static `Role::create()` duplicate-name guard lets any other caller silently create duplicate role names." The migration's own `$table->unique(['name', 'guard_name'])` constraint (verified in `vendor/spatie/laravel-permission/database/migrations/create_permission_tables.php.stub`) still prevents an actual duplicate row regardless of caller — only the exception type differs (raw `QueryException` vs. spatie's friendlier `RoleAlreadyExists`), not silent duplicate creation.
- **low, rejected** [blind-hunter] — `CheckboxList::make('permissions')` not scoped to `guard_name` is real for generic Filament+spatie usage, but AD-20 establishes there is no multi-guard scenario for the Staff/User realm in this architecture (Customer never uses Laravel's auth guard system at all). Unlikely to ever be met, and the fix (guard-aware filtering/grouping) is more than a direct correction.
- **low** [blind-hunter] — `CheckboxList::make('permissions')` has no `->searchable()`; foreseeable friction as the permission catalogue grows across Stories 1.4-1.6, but today's catalogue is tiny. Fix is a trivial one-line addition, so it doesn't meet the reject bar. → **patch, fixed** — `->searchable()` verified present on the `CheckboxList` in the current file.
- **defer** [blind-hunter] — "No bilingual (EN/ID) labels on `RoleResource`'s new UI surface (`name` field, `permissions` CheckboxList, delete-confirmation copy) despite Story 1.2 establishing bilingual Shell." Real, but matches the unmodified `UserResource` (Story 1.1), which also has zero bilingual labels — Story 1.2's bilingual work is scoped to Shell's own chrome copy (topbar/sidebar/component kit), and no per-Resource form-label i18n convention has been established by any prior story. Pre-existing gap, not introduced or exposed specifically by this diff.
- **defer** [blind-hunter] — "Deleting a Role held by Users has no audit trail." Real, but epic-1-context.md's Cross-Story Dependencies explicitly scope this to Story 1.5 ("auto-capture-nya harus berfungsi tanpa instrumentasi manual di semua epic berikutnya") — this story is required to need zero manual audit instrumentation, by the epic's own design.
- **low, rejected** [blind-hunter] — "Deleting a Role in use shows no in-use warning beyond the generic confirmation modal." AC3 only requires "modal konfirmasi karena aksi sulit dibalik", which is present; no in-use-specific warning is specified anywhere in epics.md/EXPERIENCE.md/the ATDD checklist. Fix (querying holder count, new copy) is more than a direct correction.
- **low** [blind-hunter] — Role `name` `TextInput` has no `->trim()`; "Supervisor Retur" vs "Supervisor Retur " (trailing space) look identical to Staff but aren't caught by the unique check. Trivial one-line fix. → **patch, fixed** — `->trim()` verified present on the `name` `TextInput` in the current file.
- **low, rejected** [edge-case-hunter] — `RoleService::create()`/`update()`'s `syncPermissions()` throws uncaught `PermissionDoesNotExist` if a permission name vanishes between form-render and submit (TOCTOU). No UI anywhere in the codebase can delete a Permission (no `PermissionResource` exists, by this story's own Confidence Gate), so this window is unreachable today except via out-of-band DB/tinker access — matches the same not-currently-reachable pattern already accepted in Story 1.2's Review Triage Log.
- **high** [edge-case-hunter] — `RoleResource` reads/writes `roles`/`permissions` tables that only get created in `tests/TestCase.php`'s test-only migration load; nothing in `BazaarInstallCommand` or README tells a real host app to publish+migrate spatie/laravel-permission's own migration. First real visit to the Roles page after `composer require tigaphonic/bazaar && php artisan bazaar:install` throws a missing-table SQL error — directly contradicts Epic 1's own "Composer + 1 perintah Artisan" installation promise. Verified: `README.md`'s Installation section and `BazaarInstallCommand::handle()` mention nothing about it. → **patch** (guarded `vendor:publish` of the migration tag + an info reminder to run `migrate`, mirroring the existing guarded `filament:assets` call already in this command; plus a README note) — kept to a publish-and-inform step rather than an unconditional `migrate` call, since running migrate unconditionally inside `bazaar:install` would apply *any* pending host-app migration, a materially bigger side effect than this fix warrants. → **fixed** — `BazaarInstallCommand::handle()` now guards + calls `vendor:publish --provider=Spatie\Permission\PermissionServiceProvider::class --tag=laravel-permission-migrations` plus an info reminder to run `migrate`; `README.md` documents the extra step. Verified against the current files, not just the report.
- **false** [edge-case-hunter, "claim"] — "A reader trusting the spec's 'tanpa mengubah assertion' claim would skip auditing the one test whose assertion changed." Disproven: the `## Spec Change Log` entry above (written during step-03, before this review pass ran) already transparently records that exact assertion change, its evidence, and Binyo's explicit approval — nothing is hidden from a reader who reads the spec's own change log, which is the designated place for exactly this kind of disclosure.

## Verification

**Commands:**
- `vendor/bin/pest tests/Feature/User` -- expected: semua lulus, 0 `->skip()` tersisa
- `vendor/bin/pest` -- expected: full suite lulus, tidak ada regresi Story 1.1/1.2 (baseline: 63 passed)
- `vendor/bin/pint --test` && `vendor/bin/phpstan analyse` -- expected: bersih

**Manual checks (if no CLI):**
- Setelah `composer require`, jalankan `composer run build` (`testbench workbench:build`) jika `vendor/bin/pest` gagal dengan `MissingAppKeyException` -- `post-autoload-dump` men-purge skeleton workbench tanpa membangunnya ulang (quirk lingkungan pra-existing, bukan defect story ini).

**Verification run (independently re-executed, bukan sekadar dipercaya dari laporan subagent):**
- `vendor/bin/pest` -- 80 passed, 0 skipped, 0 failed (baseline 63 + 17 story ini, tidak ada regresi).
- `vendor/bin/phpstan analyse` -- no errors.
- `vendor/bin/pint --test` -- clean pada seluruh file yang disentuh/dibuat story ini.
- Matrix Test Audit: seluruh 9 baris I/O & Edge-Case Matrix punya test yang jalan dan lulus.

**Review pass verification (setelah 5 patch diterapkan):**
- `vendor/bin/pest` -- 81 passed, 0 skipped, 0 failed (+1 test baru: registrasi `RoleResource` di panel).
- `vendor/bin/phpstan analyse` -- no errors.
- `vendor/bin/pint --test` (file yang disentuh story ini) -- clean; kegagalan repo-wide yang tersisa 100% pre-existing di file Story 1.1/1.2, tidak tersentuh diff ini.

### Review Findings

- [x] [Review][Decision] Constraint Contradiction: Unrenegotiated Test Modification — The diff adds a new test block (`expect($panel->getResources())->toContain(RoleResource::class)`) to `ListRolesTest.php`, violating the frozen constraint "Never: Tidak mengubah assertion di 7 file test yang sudah ada" without formal renegotiation.
- [x] [Review][Patch] Missing BazaarInstallCommand test for vendor:publish [tests/Feature/Install/BazaarInstallCommandTest.php:5]
- [x] [Review][Patch] BazaarInstallCommand ignores vendor:publish failure [src/Install/Commands/BazaarInstallCommand.php:32]
- [x] [Review][Patch] Hardcoded table name in RoleResource::unique [src/User/Filament/Resources/RoleResource.php:44]
- [x] [Review][Patch] Table column improvements (searchable, sortable, permissions_count) [src/User/Filament/Resources/RoleResource.php]
- [x] [Review][Patch] Missing EditAction in RoleResource table [src/User/Filament/Resources/RoleResource.php]
- [x] [Review][Patch] Missing Role name trimming test [tests/Feature/User/RoleResource/CreateRoleTest.php]
- [x] [Review][Patch] Missing test verifying DeleteAction uses RoleService [tests/Feature/User/RoleResource/DeleteRoleTest.php:41]
- [x] [Review][Patch] Missing test for RoleService update with empty permissions [tests/Feature/User/RoleServiceTest.php]
- [x] [Review][Patch] Missing test for RoleService name update [tests/Feature/User/RoleServiceTest.php]
- [x] [Review][Patch] TestCase.php migration lacks hasTable check [tests/TestCase.php:52]
- [x] [Review][Defer] Missing Authorization Gate (canAccess) [src/User/Filament/Resources/RoleResource.php] — deferred: pre-existing, logged in deferred-work.md (awaiting Story 1.4 for proper enforcement).

#### Rejected Findings

- `false`: vendor:publish tag is 'permission-migrations' — Spatie package tools automatically prepends the package name, so 'laravel-permission-migrations' is correct.
- `false`: RoleResource.php lacks ignoreRecord parameter in unique — Filament automatically ignores the current record when attached to a standard Edit page.
- `false`: mutateFormDataBeforeFill uses all() loading related models instead of toArray() — Spatie's `$role->permissions` uses a highly optimized cache, whereas the query builder bypasses the cache.
- `low`: CheckboxList renders permissions as a flat list — Not a defect, cosmetic improvement not required by the spec.
