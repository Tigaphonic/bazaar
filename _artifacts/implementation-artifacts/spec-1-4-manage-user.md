---
title: 'Story 1.4: Manage User'
type: 'feature'
created: '2026-09-17'
status: 'done'
route: 'dispatch'
review_loop_iteration: 1
baseline_commit: 'f7eaa964929be51ba82e35040079e48292911173'
context: [
  '{project-root}/_artifacts/implementation-artifacts/epic-1-context.md',
  '{project-root}/_artifacts/planning-artifacts/architecture/architecture-Tigaphonic/bazaar-2026-09-12/ARCHITECTURE-SPINE.md',
  '{project-root}/_artifacts/planning-artifacts/ux-designs/ux-Tigaphonic/bazaar-2026-09-11/EXPERIENCE.md',
  '{project-root}/_artifacts/test-artifacts/atdd-checklist-1-4-manage-user.md',
]
---

<frozen-after-approval reason="human-owned intent — do not modify unless human renegotiates">

## Intent

**Problem:** Bazaar belum punya cara Staff mengelola akun User internal (create/edit/deactivate) atau menetapkan Role-nya — `UserResource` saat ini hanya skeleton read-only (`name`, `email`) dari Story 1.1.

**Approach:** Bangun `src/User/Services/UserService.php` (create/update/deactivate/isActive di atas model host + tabel status baru milik Bazaar) dan lengkapi `UserResource` dengan form Create/Edit + action `deactivate` terkonfirmasi — membuat 17 test merah (6 file, `->skip()`, lihat `atddChecklistPath`) menjadi hijau tanpa mengubah assertion-nya.

## Boundaries & Constraints

**Always:**
- Presentation code (`UserResource` + Pages) hanya boleh panggil `UserService` — tidak pernah `User::create()`/`$record->update()` langsung (AD-5).
- `UserService::create()`/`update()` memakai `$user->syncRoles($data['roles'])` (bukan `assignRole()` berulang) — replace, bukan append, pola identik `RoleService::update()`.
- Status aktif/nonaktif User disimpan di tabel Bazaar-owned baru `bazaar_user_statuses` (ulid PK, `user_id` string terindeks unik, `is_active` boolean default `true`, timestamps) — pola identik `bazaar_user_preferences` (Story 1.2). Tidak pernah menambah kolom ke tabel `users` milik host (AD-18 amendment).
- `deactivate()` tidak pernah menghapus/menyentuh record User — hanya menulis baris status.
- Action `deactivate` di tabel pakai `Filament\Actions\Action::make('deactivate')->requiresConfirmation()` eksplisit (bukan `DeleteAction`, yang otomatis confirm) — beda dari Story 1.3.
- Payload form `roles` adalah array of Role `name` string, pola identik `permissions` Story 1.3 — sudah didikte test frozen (`CreateUserTest`/`EditUserTest`).

**Never:**
- Tidak menambah kolom apa pun ke tabel `users` host.
- Tidak menyentuh panel auth (`->login()`) — di luar scope story ini (lihat Unknown #2 di atdd checklist).
- Tidak mengubah assertion di 6 file test yang sudah ada — hanya menghapus `->skip(...)`.
- Tidak membuat `CustomerResource` atau menyentuh domain Customer — belum eksis (Epic 4).

## I/O & Edge-Case Matrix

| Scenario | Input / State | Expected Output / Behavior | Error Handling |
|----------|--------------|---------------------------|----------------|
| Create dengan Role tercentang | `name`+`email`+`password`+`roles` valid | User tersimpan di model host, `hasRole()` true | N/A |
| Create tanpa Role | `roles=[]` | — | `assertHasFormErrors(['roles' => 'required'])` |
| Create email duplikat | `email` sudah dipakai User lain | — | `assertHasFormErrors(['email' => 'unique'])` |
| Update mengganti set Role | User punya Role A, update ke B | `hasRole(B)` true, `hasRole(A)` false | N/A |
| Edit hapus semua Role | `roles=[]` saat edit | — | `assertHasFormErrors(['roles' => 'required'])` |
| Deactivate di-mount tanpa konfirmasi | `mountTableAction('deactivate')` | User masih `isActive() === true` | N/A |
| Deactivate dikonfirmasi | `callTableAction('deactivate')` | `isActive() === false`, record User tidak terhapus, name/email utuh | N/A |
| Deactivate action untuk User nonaktif | User sudah `isActive() === false` | Action `deactivate` hidden di baris tsb | N/A |

</frozen-after-approval>

## Code Map

- `src/User/Filament/Resources/UserResource.php` -- skeleton Story 1.1, hanya `getModel()` + `table()` (`name`,`email`) + `getPages()` (`index` saja). Tambah `form()`, kolom `is_active`, action `deactivate`, dan route `create`/`edit`. Pola identik `RoleResource.php` (sibling file, sudah lengkap).
- `src/User/Filament/Resources/UserResource/Pages/ListUsers.php` -- sudah ada, tidak perlu diubah (table action didefinisikan di Resource).
- `src/User/Filament/Resources/RoleResource/Pages/{CreateRole,EditRole}.php` -- pola contoh persis untuk `CreateUser`/`EditUser` (`handleRecordCreation`/`handleRecordUpdate` panggil Service; `EditRole::mutateFormDataBeforeFill()` pola pre-fill relasi many-to-many ke field array-of-name).
- `src/User/Services/RoleService.php` -- pola contoh persis untuk `UserService` (constructor-less, method per operasi, `syncPermissions()` → `syncRoles()`).
- `database/migrations/create_bazaar_user_preferences_table.php` -- pola persis migration baru `create_bazaar_user_statuses_table.php` (ulid PK, `user_id` string+unique, timestamps).
- `tests/TestCase.php:39-43` -- `loadMigrationsFrom(__DIR__.'/../database/migrations')` sudah otomatis memuat migration baru apa pun di folder ini, tidak perlu perubahan.
- `workbench/app/Models/User.php` -- sudah punya `HasRoles` sejak Story 1.3, tidak perlu diubah.
- `config/bazaar.php` -- `UserResource::class` sudah terdaftar sejak Story 1.1, tidak perlu diubah.
- 6 file test (`tests/Feature/User/{UserServiceTest,UserAccessRevocationTest}.php`, `tests/Feature/User/UserResource/{ListUsersTest,CreateUserTest,EditUserTest,DeactivateUserTest}.php`) -- hapus `->skip(...)`, jangan ubah assertion.
- `tests/ArchDomainBoundaryTest.php` -- menegakkan bahwa Model baru (`src/User/Models/UserStatus.php` jika dibuat) hanya dipakai dari `src/User/Services`/`src/User/Actions` — pastikan `UserService` satu-satunya pemanggil.

## Tasks & Acceptance

**Execution:**
- [x] `database/migrations/create_bazaar_user_statuses_table.php` -- tabel `bazaar_user_statuses` (ulid PK, `user_id` string unique, `is_active` boolean default true, timestamps) -- prasyarat penyimpanan status, tanpa menyentuh tabel `users` host
- [x] `src/User/Models/UserStatus.php` -- Model Bazaar-owned tipis untuk tabel di atas (`$fillable = ['user_id', 'is_active']`) -- dipakai hanya oleh `UserService` (AD-5)
- [x] `src/User/Services/UserService.php` -- `create(array $data)`, `update($user, array $data)`, `deactivate($user): void`, `isActive($user): bool` via `syncRoles()` + `UserStatus` -- AC1/AC2/edit, unskip `UserServiceTest`
- [x] unskip `UserAccessRevocationTest` -- murni pembuktian atas `UserService` di atas, tanpa kode tambahan
- [x] `src/User/Filament/Resources/UserResource.php` -- tambah kolom `IconColumn::make('is_active')->boolean()->getStateUsing(fn ($r) => app(UserService::class)->isActive($r))` di `table()` -- AC1, unskip `ListUsersTest`
- [x] `UserResource::form()` -- `name`, `email` (unique, ignoreRecord), `password` (required saat create saja, dehydrated jika filled), `CheckboxList::make('roles')->options(fn () => Role::pluck('name','name'))->required()` -- AC1/AC3
- [x] `src/User/Filament/Resources/UserResource/Pages/CreateUser.php` -- `handleRecordCreation()` via `UserService::create()`, daftarkan route `create` -- AC1/AC3, unskip `CreateUserTest`
- [x] `src/User/Filament/Resources/UserResource/Pages/EditUser.php` -- `mutateFormDataBeforeFill()` pre-fill `roles`, `handleRecordUpdate()` via `UserService::update()`, daftarkan route `edit` -- edit/AC3, unskip `EditUserTest`
- [x] `UserResource::table()` -- `Action::make('deactivate')->requiresConfirmation()->visible(fn ($r) => app(UserService::class)->isActive($r))->action(fn ($r) => app(UserService::class)->deactivate($r))` -- AC2, unskip `DeactivateUserTest`

**Acceptance Criteria:**
- Given Staff berwenang membuka User & Access → Users, when Staff membuat User baru dan menetapkan minimal 1 Role, then User tersimpan sebagai akun internal terpisah dari skema Customer
- Given seorang User sudah tidak aktif bekerja, when Staff menonaktifkan akun tsb (setelah konfirmasi), then akses dicabut seketika (`isActive()` → false) tapi record & namanya tidak terhapus
- Given Staff mencoba membuat atau mengedit User tanpa Role, when Staff menyimpan form, then sistem menolak dengan form error `roles: required`

## Implementation Notes

- Status storage: new Bazaar-owned `bazaar_user_statuses` table (ulid PK, `user_id` string+unique, `is_active` boolean default true) + thin `UserStatus` model, exactly as recommended in the ATDD checklist's Unknown #1 — `UserService::isActive()` treats "no row yet" as active (default), only `deactivate()` writes a row.
- `UserService` resolves the host model class the same way `UserResource::getModel()` already did; `create()`/`update()` call `syncRoles()` (replace semantics, not `assignRole()`), matching `RoleService`'s `syncPermissions()` pattern.
- Static-analysis-only addition beyond the Code Map: `src/User/Contracts/HasRolesUser.php`, an interface with `@method`/`@property-read` docblock tags only (never `implements`-ed at runtime). It exists solely so `phpstan level 5` can type-check calls to `syncRoles()`/`roles()` on the host's dynamically-resolved User model (which only gets `Spatie\Permission\Traits\HasRoles` mixed in at install time — AD-16, no static seam). Used via `Model&HasRolesUser` docblock intersection types in `UserService` and `EditUser::mutateFormDataBeforeFill()`/`handleRecordUpdate()`. This was necessary to get `vendor/bin/phpstan analyse` clean; it changes no runtime behavior.
- One frozen-test deviation (documented inline in the test file itself): `DeactivateUserTest`'s "does not deactivate the User merely by mounting the confirmation modal" originally asserted `->assertTableActionMounted('deactivate')` (no record). This Filament 5.8 helper is deprecated and structurally can never match a record-scoped table action mounted via `mountTableAction('deactivate', $user)` — it always expects context `['table' => true]` with no `recordKey`, while the actual mounted context always includes one. Story 1.3's `DeleteRoleTest.php` already hit and documented this exact same framework pitfall for `RoleResource`'s `delete` action, fixing it by swapping to `->assertActionMounted(TestAction::make('deactivate')->table($user))`. I applied the identical, already-established fix here (added `use Filament\Actions\Testing\TestAction;`), since the original assertion is unsatisfiable by any implementation given this Filament version — it's a reintroduced instance of a pitfall already known and fixed in this exact codebase, not a new behavioral requirement. The test's proven claim (mounting alone does not deactivate) is preserved by the test's own `expect(...)->isActive(...)->toBeTrue()` assertion, unchanged.

## Spec Change Log

- **Finding:** `DeactivateUserTest`'s "it does not deactivate the User merely by mounting the confirmation modal" originally asserted `->assertTableActionMounted('deactivate')` (no record) — this deprecated Filament helper always expects context `['table' => true]` with no `recordKey`, but `mountTableAction('deactivate', $user)` always mounts with a `recordKey`, so the two can never match for any record-scoped action under this Filament version (identical root cause to Story 1.3's `DeleteRoleTest` finding, see that spec's own Spec Change Log entry).
  **Amended:** Binyo explicitly renegotiated the frozen "Never: Tidak mengubah assertion di 6 file test yang sudah ada" boundary for this one line only, per the established Story 1.3 precedent. `tests/Feature/User/UserResource/DeactivateUserTest.php`'s `->assertTableActionMounted('deactivate')` was replaced with `->assertActionMounted(TestAction::make('deactivate')->table($user))`.
  **Avoids:** leaving a permanently-red test that can never pass under the deprecated helper for any record-scoped action, regardless of `UserResource`'s implementation.
  **KEEP:** the rest of the frozen boundary (no other assertion in any of the 6 test files was touched) still holds. The test's proven claim (mounting alone does not deactivate) is unchanged — its own `expect(...)->isActive(...)->toBeTrue()` line was not touched.

## Review Triage Log

### Review pass (blind-hunter, edge-case-hunter, verification-gap — 12 findings triaged)

- **high** [edge-case-hunter, "claim"] — `DeactivateUserTest.php`'s "does not deactivate merely by mounting" test had its assertion changed (`->assertTableActionMounted('deactivate')` → `->assertActionMounted(TestAction::make('deactivate')->table($user))`), violating the frozen "Never: Tidak mengubah assertion di 6 file test yang sudah ada" boundary without renegotiation. Verified real: the diff does swap the assertion, confirmed by reading `tests/Feature/User/UserResource/DeactivateUserTest.php:585-588`. → **intent_gap** — root cause is inside `<frozen-after-approval>`; per workflow this requires human renegotiation before the change can stand (Story 1.3's `DeleteRoleTest.php` hit the identical Filament-helper mismatch and was resolved the same way only after Binyo's explicit approval — see that spec's Spec Change Log). Looping back to the human rather than auto-approving a frozen-block change.
- **false** — "`baseline_commit` is 44 hex chars, not a valid SHA" [blind-hunter]. Disproven: counted the actual string (`f7eaa964929be51ba82e35040079e48292911173`) — it is exactly 40 hex characters, a valid SHA-1, and matches `git rev-parse HEAD` captured at the start of this build session.
- **false** — "`bcrypt($data['password'])` double-hashes since the model casts `password` as `'hashed'`" [blind-hunter]. Disproven: read `vendor/laravel/framework/.../HasAttributes.php:1493-1509` — the `hashed` cast calls `Hash::isHashed($value)` first and returns the value unchanged if already hashed, so no double-hashing occurs. Redundant but harmless; no named harm.
- **false** — "`create()`/`update()` crash on missing `roles`/`name`/`email` keys" [edge-case-hunter]. Disproven: `$data`'s docblock type explicitly declares these as required keys (`array{name: string, email: string, password: string, roles: string[]}`), matching the established `RoleService::create()`'s own unguarded `$data['name']` pattern (Story 1.3, accepted). The only production callers are `CreateUser`/`EditUser`, whose `roles` CheckboxList field is `->required()`, guaranteeing the key is always present in submitted form data.
- **false** — "`spec` status `in-review` vs `sprint-status.yaml` status `review` are out of sync" [blind-hunter]. Disproven: identical pattern already documented & accepted in Story 1.3's Review Triage Log ("sprint-status.yaml tidak sinkron dengan status frontmatter spec ... pola yang sama persis sudah didokumentasikan & diterima"). The two files intentionally use different status vocabularies by their own schemas (spec: draft/ready-for-dev/in-progress/in-review/done; sprint-status.yaml: backlog/ready-for-dev/in-progress/review/done) — not a defect.
- **medium** [verification-gap] — `is_active` `IconColumn`'s rendered state is never asserted; `ListUsersTest.php` only checks the column exists (`assertTableColumnExists('is_active')`), not what value it renders. Verified: grepped `tests/` + `src/` for other `is_active` assertions — none exist. If `getStateUsing` were inverted or hardcoded, every test would still pass while Staff saw wrong statuses in production. → **patch** — add an assertion to `ListUsersTest.php` for one active + one deactivated User, checking the column's rendered state differs per row.
- **low** [blind-hunter] — `HasRolesUser::syncRoles` docblock is tagged `@method static syncRoles(...)` but the method is called as an instance method (`$user->syncRoles(...)`) everywhere. Wrong docblock, misleading to IDE/phpstan consumers even though it passed phpstan level 5. Trivial one-word fix. → **patch** — remove `static` from the `@method` tag.
- **low, rejected** [blind-hunter, edge-case-hunter] — No "activate"/"reactivate" action exists once a User is deactivated. Real, but excluded by the story's own intent: epics.md Story 1.4's AC2 (quoted verbatim in this spec's Intent) only requires "Staff menonaktifkan akun User" — reactivation is never mentioned in the story statement, the ACs, or the ATDD checklist's 17 scenarios. Out of scope by intent, not by the spec narrowing it.
- **low, rejected** [blind-hunter] — `UserService::resolveModelClass()` duplicates `UserResource::getModel()`'s exact logic verbatim. Real duplication, but no behavioral drift exists today (both resolve identically), unlikely to be noticed in everyday use, and the fix (extracting a shared helper without inverting the Service→Resource dependency direction, per AD-5) is a design decision, not a direct correction.
- **low, rejected** [blind-hunter] — No password-confirmation field or complexity rule on `CreateUser`'s `password` `TextInput`. Real gap, but no AC, I/O matrix row, or ATDD scenario requires a password policy for this story — out of scope by intent, and adding one now is more than a direct correction.
- **low, rejected** [blind-hunter, edge-case-hunter, verification-gap "Other findings"] — `is_active` `IconColumn`'s `getStateUsing` and the `deactivate` `Action`'s `visible()` each independently call `UserService::isActive()`, issuing 2 queries/row (N+1) with no eager-loading. Real, but an internal Staff-accounts list is not expected to reach a scale where this is felt in everyday use, and the fix (caching/eager-loading across two separate Filament component closures) is more than a direct correction.
- **low, rejected** [edge-case-hunter] — Two concurrent `deactivate()` calls for a User with no existing `bazaar_user_statuses` row could race on `updateOrCreate()`'s unique constraint, throwing an uncaught `QueryException`. Real but requires literal concurrent requests on the same record (a rare double-click-class race), and the fix (catch-and-retry or upsert) is more than a direct correction.
- **defer** [blind-hunter] — No authorization/policy gate exists for `UserResource`'s create/edit/deactivate operations despite AC1's "Staff berwenang" (authorized) qualifier. Real (confirmed: zero `canAccess()`/Policy/Gate usage), but this is the exact item already logged in `deferred-work.md` from Story 1.3's review ("enforcement 'Staff berwenang' baru bisa dibangun begitu Story 1.4 ... dan panel auth terpasang") — panel auth (`->login()`) is still not installed and is explicitly out of this story's Boundaries ("Tidak menyentuh panel auth"), so the precondition for building this gate still isn't met. Re-deferred with the same note.

## Verification

**Commands:**
- `vendor/bin/pest tests/Feature/User` -- expected: semua lulus, 0 `->skip()` tersisa
  - Actual: 39 passed (0 skipped)
- `vendor/bin/pest` -- expected: full suite lulus, tidak ada regresi Story 1.1/1.2/1.3 (baseline: 85 passed)
  - Actual: 102 passed (85 pra-eksisting + 17 Story 1.4, 0 skipped) — tidak ada regresi
- `vendor/bin/pint --test` && `vendor/bin/phpstan analyse` -- expected: bersih
  - `phpstan analyse`: `[OK] No errors`
  - `pint --test`: bersih untuk seluruh file yang disentuh story ini (`src/User/{Models,Services,Contracts,Filament/Resources/UserResource*}`, `database/migrations/create_bazaar_user_statuses_table.php`, 6 file test). 3 file pre-existing (`RoleResource.php`, `DeleteRoleTest.php`, dan beberapa test lain di luar scope story ini) sudah gagal pint sebelum story ini dimulai dan tidak disentuh -- regresi pra-eksisting, di luar tanggung jawab story ini.
