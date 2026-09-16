---
stepsCompleted: ['step-01-preflight-and-context', 'step-02-generation-mode', 'step-03-test-strategy', 'step-04-generate-tests', 'step-04c-aggregate', 'step-05-validate-and-complete']
lastStep: 'step-05-validate-and-complete'
lastSaved: '2026-09-16'
workflowType: 'testarch-atdd'
storyId: '1.4'
storyKey: '1-4-manage-user'
storyFile: '_artifacts/planning-artifacts/epics.md'
atddChecklistPath: '_artifacts/test-artifacts/atdd-checklist-1-4-manage-user.md'
generatedTestFiles:
  - 'tests/Feature/User/UserServiceTest.php'
  - 'tests/Feature/User/UserAccessRevocationTest.php'
  - 'tests/Feature/User/UserResource/ListUsersTest.php'
  - 'tests/Feature/User/UserResource/CreateUserTest.php'
  - 'tests/Feature/User/UserResource/EditUserTest.php'
  - 'tests/Feature/User/UserResource/DeactivateUserTest.php'
inputDocuments:
  - '_artifacts/planning-artifacts/epics.md (Story 1.4, baris 340–358)'
  - '_artifacts/implementation-artifacts/epic-1-context.md'
  - '_artifacts/planning-artifacts/architecture/architecture-Tigaphonic/bazaar-2026-09-12/ARCHITECTURE-SPINE.md (AD-5, AD-16, AD-18, AD-20)'
  - '_artifacts/planning-artifacts/ux-designs/ux-Tigaphonic/bazaar-2026-09-11/EXPERIENCE.md (§Navigation, §Component Patterns, §Maker-Checker & Approval Pattern, Flow 10)'
  - 'src/User/Filament/Resources/UserResource.php'
  - 'src/User/Filament/Resources/RoleResource.php'
  - 'src/User/Filament/Resources/RoleResource/Pages/{CreateRole,EditRole,ListRoles}.php'
  - 'src/User/Services/RoleService.php'
  - 'database/migrations/create_bazaar_user_preferences_table.php'
  - 'src/BazaarServiceProvider.php'
  - 'workbench/app/Models/User.php'
  - 'config/bazaar.php'
  - 'tests/TestCase.php'
  - 'tests/Feature/User/RoleResource/{CreateRoleTest,EditRoleTest,DeleteRoleTest,ListRolesTest}.php'
  - 'tests/Feature/User/{RoleServiceTest,RolePermissionPropagationTest}.php'
  - '_artifacts/test-artifacts/atdd-checklist-1-3-manage-role-permission.md'
  - 'vendor/filament/actions/src/DeleteAction.php'
  - 'vendor/filament/tables/src/Testing/TestsActions.php'
  - 'vendor/filament/forms/src/Testing/TestsForms.php'
  - '.claude/skills/bmad-testarch-atdd/resources/knowledge/test-quality.md'
  - '.claude/skills/bmad-testarch-atdd/resources/knowledge/confidence-gate.md'
  - '.claude/skills/bmad-testarch-atdd/resources/knowledge/playwright-utils-mandate.md'
---

# ATDD Checklist - Epic 1, Story 1.4: Manage User

**Date:** 2026-09-16
**Author:** Binyo
**Primary Test Level:** Integration (Testbench + Filament/Livewire testing) — tidak ada Unit/E2E terpisah

---

## Story Summary

Staff berwenang membuat, mengedit, dan menonaktifkan akun User internal (terpisah total dari Customer) serta menetapkan Role-nya, sehingga bisa mengelola siapa punya akses ke dashboard dan level aksesnya.

**As a** Staff berwenang
**I want** membuat, mengedit, dan menonaktifkan akun User internal serta menetapkan Role-nya
**So that** saya bisa mengelola siapa saja yang punya akses ke dashboard dan level aksesnya

---

## Acceptance Criteria

1. **AC1** — Given Staff berwenang membuka User & Access → Users. When Staff membuat User baru dan menetapkan minimal 1 Role (dari Story 1.3). Then User tersimpan sebagai akun internal, terpisah total dari skema Customer.
2. **AC2** — Given seorang User yang sudah tidak aktif bekerja. When Staff menonaktifkan akun User tsb. Then akses User tsb dicabut seketika, tapi jejak historis aksinya di Audit Trail (Story 1.5) tetap tercatat atas nama User tsb, tidak terhapus.
3. **AC3** — Given Staff mencoba membuat User tanpa Role. When Staff menyimpan form. Then sistem menolak — User wajib punya minimal 1 Role.

Story statement juga menyebut "mengedit" secara eksplisit (di luar tiga AC literal di atas) — diperlakukan sama seperti Story 1.3 memperlakukan Edit Role di bawah AC2: dicakup di sini sebagai bagian scope, dengan invarian AC3 ("wajib minimal 1 Role") berlaku juga saat edit, bukan cuma saat create.

---

## Story Integration Metadata

- **Story ID:** `1.4`
- **Story Key:** `1-4-manage-user`
- **Story File:** `_artifacts/planning-artifacts/epics.md` (Story 1.4 section, baris 340–358) — belum ada file story individual terpisah (`create-story` belum dijalankan), sama seperti Story 1.1/1.2/1.3
- **Checklist Path:** `_artifacts/test-artifacts/atdd-checklist-1-4-manage-user.md`
- **Generated Test Files:** lihat bagian "Red-Phase Test Scaffolds Created" di bawah

Story ini belum melalui `create-story` BMM — begitu file story individual dibuat, mirror path checklist & test files ini ke `Dev Notes`-nya (pola sama seperti Story 1.1/1.2/1.3).

---

## Stack Deviation Notice

Sama seperti Story 1.1/1.2/1.3: knowledge base skill ini (Playwright Utils, Pact.js) berorientasi JS/TS + browser-first dan sebagian besar tidak relevan di sini.

- **Tidak dipakai:** seluruh fragment Pact.js (tidak ada microservices/contract boundary di story ini — UserResource murni internal Filament CRUD)
- **Tidak dipakai:** `playwright-utils-mandate` dan seluruh fragment turunannya — sama alasan seperti Story 1.3 (paket `@seontechnologies/playwright-utils` belum jadi dependency nyata, gate relevansinya no-op). Tidak ada spec Playwright baru dibuat untuk story ini.
- **Dipakai (diterjemahkan ke idiom Pest PHP):** `test-quality.md` dan `confidence-gate.md`
- **Test level:** Testbench `TestCase` + Pest v4, plus Filament/Livewire testing helpers (`Livewire::test()` + macro `TestsForms`/`TestsActions`/`TestsRecords`/`TestsColumns`) — pola identik Story 1.3, diterapkan ke `UserResource` alih-alih `RoleResource`.

---

## Generation Mode

**Mode: AI Generation** — AC standar (CRUD User + assign Role + deactivate-dengan-konfirmasi), tidak ada interaksi UI yang butuh verifikasi browser nyata. Sama seperti Story 1.3: form Create/Edit, checkbox Role, dan modal konfirmasi deactivate semuanya adalah state Livewire yang bisa diverifikasi penuh lewat `Livewire::test()` + macro testing Filament bawaan. Keputusan: **nol file Playwright baru untuk story ini** (deviasi eksplisit, disurface di sini, bukan disembunyikan).

---

## Test Strategy

Level backend+fullstack: **Integration** (Testbench + Filament/Livewire testing) untuk hampir semua skenario; tidak ada Unit terisolasi dan tidak ada E2E/browser (lihat Generation Mode).

| ID | AC | Prioritas | Level | Skenario |
|---|---|---|---|---|
| TS-1 | AC1 | P0 | Integration | `UserService::create()` menyimpan User pada model host, dengan Role ter-assign |
| TS-2 | edit | P0 | Integration | `UserService::update()` mengganti (bukan menambah) set Role User |
| TS-3 | AC2 | P0 | Integration | `UserService::deactivate()` mencabut akses (`isActive()` → false) seketika |
| TS-4 | AC2 | P0 | Integration | `UserService::deactivate()` tidak pernah menghapus record User |
| TS-5 | AC2 | P0 | Integration | Deactivate tidak meninggalkan state ter-cache basi — `isActive()` via `fresh()` langsung reflect |
| TS-6 | AC2 | P1 | Integration | Nama & email User yang dinonaktifkan tetap utuh (prasyarat Audit Trail Story 1.5 bisa merujuk namanya nanti) |
| TS-7 | AC1 | P2 | Integration | `ListUsers` menampilkan kolom `name`, `email`, dan status (`is_active`) |
| TS-8 | AC1 | P0 | Integration | Form `CreateUser` (Livewire) membuat User dengan Role tercentang, tanpa error |
| TS-9 | AC3 | P0 | Integration | `CreateUser` menolak form tanpa Role tercentang sama sekali |
| TS-10 | AC1 | P1 | Integration | `CreateUser` menolak email duplikat (validasi form) |
| TS-11 | edit | P1 | Integration | Form `EditUser` pre-fill nama+email+Role existing |
| TS-12 | edit | P1 | Integration | `EditUser` menyimpan perubahan set Role untuk User existing |
| TS-13 | AC3 | P0 | Integration | `EditUser` menolak menghapus seluruh Role User existing (invarian "minimal 1 Role" berlaku juga saat edit) |
| TS-14 | AC2 | P0 | Integration | Tabel `UserResource` punya action `deactivate` untuk tiap baris User aktif |
| TS-15 | AC2 | P0 | Integration | Deactivate **tidak langsung mengeksekusi** saat action di-mount (bukti modal konfirmasi) |
| TS-16 | AC2 | P0 | Integration | Deactivate benar-benar menonaktifkan User setelah action dikonfirmasi/dipanggil |
| TS-17 | AC2 | P2 | Integration | Action `deactivate` disembunyikan untuk User yang sudah nonaktif (cegah double-deactivate membingungkan) |

**Red phase confirmation:** seluruh 17 test (TS-1..TS-17, satu test fisik per TS) dipastikan gagal/ter-skip sekarang — `Tigaphonic\Bazaar\User\Services\UserService` dan `Tigaphonic\Bazaar\User\Filament\Resources\UserResource\Pages\{CreateUser,EditUser}` belum ada; `UserResource` yang sudah ada (skeleton Story 1.1, hanya `ListUsers` + kolom `name`/`email`) belum punya kolom status maupun action `deactivate`. Diverifikasi lewat run nyata (`vendor/bin/pest`) — lihat Test Execution Evidence.

### Confidence Gate

```
Confidence: 7
Rationale: AC dikutip literal dari epics.md Story 1.4 (baris 340-358); domain target
  (src/User, navigationGroup 'User & Access') dikutip dari UserResource.php yang sudah
  ada (komentarnya sendiri menyatakan "Full CRUD is Story 1.4's scope"); keharusan
  Service-layer (UserService, bukan akses Model langsung dari Resource) dikutip
  literal dari AD-5, pola identik RoleService (Story 1.3); model backing (host app's
  own authenticatable model via config('bazaar.models.user') ?? config('auth.
  providers.users.model')) dikutip literal dari UserResource::getModel() yang sudah
  ada + AD-18 amendment ("Bazaar never owns its own Staff/users table"); default
  requiresConfirmation() TIDAK otomatis di sini (beda dari Story 1.3's DeleteAction)
  karena `deactivate` adalah Action kustom, bukan DeleteAction bawaan Filament --
  diverifikasi dari vendor/filament/actions/src/Action.php (base class) yang tidak
  memanggil requiresConfirmation() di setUp()-nya sendiri, hanya DeleteAction
  subclass yang melakukan itu; seluruh API testing Filament (fillForm,
  assertHasFormErrors, mountTableAction, assertTableActionMounted,
  assertTableActionHidden, callTableAction, assertCanSeeTableRecords,
  assertTableColumnExists) diverifikasi langsung dari
  vendor/filament/{forms,tables}/src/Testing/*.php.
Unknowns:
  - Mekanisme penyimpanan status aktif/nonaktif User. AD-18 amendment melarang Bazaar
    menambah kolom ke tabel `users` milik host. Test-test di scaffold ini SENGAJA
    tidak mengasumsikan mekanisme penyimpanan apa pun -- semua interaksi lewat API
    publik UserService (create/update/deactivate/isActive()), bukan lewat model
    penyimpanan internal manapun. Rekomendasi non-mengikat untuk dev di GREEN: tabel
    Bazaar-owned baru (ULID PK, kolom `user_id` string biasa -- pola identik
    `bazaar_user_preferences`, database/migrations/create_bazaar_user_preferences_
    table.php), BUKAN kolom baru di tabel `users`. Confidence pada arah ini tinggi
    (satu-satunya pola yang sudah dipakai & disetujui di package ini untuk "data
    per-User yang bukan milik host"), tapi nama tabel/kolom persis adalah keputusan
    dev, tidak dikunci test manapun.
  - "Akses dicabut seketika" (AC2) tidak bisa diverifikasi lewat login/session HTTP
    sungguhan pada level story ini -- BazaarServiceProvider sendiri berkomentar "no
    ->login() panel auth exists yet" (belum ada panel auth Filament sama sekali).
    Test-test di sini membuktikan pencabutan akses di level data
    (`UserService::isActive()`), seam yang sama yang akan dipakai implementasi
    `FilamentUser::canAccessPanel()` di masa depan begitu panel auth ada -- bukan
    bukti end-to-end penuh. Displasi eksplisit di sini, bukan disembunyikan.
  - "Terpisah total dari skema Customer" (AC1) tidak diuji lewat pengecekan tabel
    `customers` -- domain Customer belum ada sama sekali di codebase (Epic 4,
    backlog). Keterpisahan realm sudah dijamin arsitektural oleh AD-20 (no shared
    table) semata-mata dengan memakai model host sendiri; tidak ada assertion baru
    yang bisa/perlu ditambahkan untuk klausa ini di story ini.
```
Confidence 7 ≥ 7 → lanjut generate scaffold, ketiga Unknown di atas disurface eksplisit di Implementation Checklist di bawah, bukan ditebak diam-diam.

---

## Generation Note (deviation from JS/subagent orchestration)

Sama seperti Story 1.1/1.2/1.3: Step 4/4C skill ini dirancang untuk dual-subagent (Worker A: API/TypeScript, Worker B: E2E/Playwright) yang menulis JSON ke `/tmp`. Proyek ini PHP/Pest, dan story ini murni backend+Filament-Livewire tanpa kebutuhan browser nyata (lihat §Generation Mode) — scaffold ditulis langsung (sequential, satu track, tanpa subagent, tanpa file JSON perantara), sesuai deviasi yang sudah disetujui sejak Step 1/1.1.

## Red-Phase Test Scaffolds Created (17 skipped)

### `tests/Feature/User/UserServiceTest.php` (4 tests)

- ✅ `it creates a User with the given Roles, stored on the host's own authenticatable model` — SKIPPED — **Verifies:** AC1
- ✅ `it replaces a User's Role assignment on update rather than appending to it` — SKIPPED — **Verifies:** edit
- ✅ `it deactivates a User, revoking access immediately` — SKIPPED — **Verifies:** AC2
- ✅ `it does not delete the User record when deactivating` — SKIPPED — **Verifies:** AC2 (edge)

### `tests/Feature/User/UserAccessRevocationTest.php` (2 tests)

- ✅ `it revokes access immediately when a User is deactivated, with no stale cached state` — SKIPPED — **Verifies:** AC2
- ✅ `it preserves the deactivated User's name and email untouched, so future Audit Trail entries can still resolve to them` — SKIPPED — **Verifies:** AC2

### `tests/Feature/User/UserResource/ListUsersTest.php` (1 test)

- ✅ `it lists every User with name, email, and status columns` — SKIPPED — **Verifies:** AC1

### `tests/Feature/User/UserResource/CreateUserTest.php` (3 tests)

- ✅ `it creates a User with assigned Roles via the dashboard form` — SKIPPED — **Verifies:** AC1
- ✅ `it rejects creating a User with no Role selected` — SKIPPED — **Verifies:** AC3
- ✅ `it rejects a duplicate email` — SKIPPED — **Verifies:** AC1 (edge case)

### `tests/Feature/User/UserResource/EditUserTest.php` (3 tests)

- ✅ `it pre-fills the form with the User's current name, email, and Roles` — SKIPPED — **Verifies:** edit
- ✅ `it saves an updated Role assignment for an existing User` — SKIPPED — **Verifies:** edit
- ✅ `it rejects removing every Role from an existing User via edit` — SKIPPED — **Verifies:** AC3

### `tests/Feature/User/UserResource/DeactivateUserTest.php` (4 tests)

- ✅ `it exposes a deactivate table action for each active User row` — SKIPPED — **Verifies:** AC2
- ✅ `it does not deactivate the User merely by mounting the confirmation modal` — SKIPPED — **Verifies:** AC2 (modal konfirmasi)
- ✅ `it deactivates the User once the action is confirmed` — SKIPPED — **Verifies:** AC2
- ✅ `it hides the deactivate action once the User is already deactivated` — SKIPPED — **Verifies:** AC2 (edge)

---

## Data Factories / Fixtures / Mocks

Tidak ada factory Eloquent baru dibuat — `Workbench\App\Models\User::create()` dipakai inline per-test (pola identik `RolePermissionPropagationTest.php`), `Spatie\Permission\Models\Role::create()` dipakai langsung untuk Role prasyarat.

---

## Implementation Checklist

### Prasyarat lingkungan (sebelum test manapun bisa diaktifkan)

- [ ] Tidak ada prasyarat instalasi baru — `spatie/laravel-permission` dan `HasRoles` di `workbench/app/Models/User.php` sudah terpasang sejak Story 1.3
- [ ] Putuskan (developer, saat GREEN): mekanisme penyimpanan status aktif/nonaktif User — lihat Unknown #1 di Confidence Gate (rekomendasi: tabel Bazaar-owned baru, pola `bazaar_user_preferences`, BUKAN kolom di tabel `users`)

### Test: `UserServiceTest` (AC1, AC2, edit)

- [ ] Buat `src/User/Services/UserService.php`: `create(array $data)`, `update($user, array $data)`, `deactivate($user): void`, `isActive($user): bool` — `$data` berbentuk `['name' => string, 'email' => string, 'password' => string, 'roles' => string[]]`; resolusi model host sama seperti `UserResource::getModel()` (`config('bazaar.models.user') ?? config('auth.providers.users.model')`)
- [ ] `create()`/`update()` memakai `$user->syncRoles($data['roles'])` (bukan `assignRole` berulang) — pola identik `RoleService::update()`'s `syncPermissions()`, supaya update benar-benar *mengganti* set Role
- [ ] `deactivate()`/`isActive()` tidak boleh menyentuh/menghapus record User itu sendiri — simpan status di storage terpisah (lihat Unknown #1)
- [ ] Jalankan: `vendor/bin/pest --filter=UserServiceTest`

### Test: `UserAccessRevocationTest` (AC2)

- [ ] Tidak ada kode tambahan di luar `UserService` di atas — test ini murni pembuktian instant-effect + integritas data, bukan pemicu kode baru
- [ ] Jalankan: `vendor/bin/pest --filter=UserAccessRevocationTest`

### Test: `UserResource/ListUsersTest` (AC1)

- [ ] Tambah kolom status ke `UserResource::table()` yang sudah ada — `IconColumn::make('is_active')->boolean()->getStateUsing(fn ($record) => app(UserService::class)->isActive($record))` (atau setara)
- [ ] Jalankan: `vendor/bin/pest --filter=ListUsersTest`

### Test: `UserResource/CreateUserTest` (AC1, AC3)

- [ ] Definisikan `UserResource::form()` (belum ada sama sekali saat ini): `TextInput::make('name')->required()`, `TextInput::make('email')->required()->email()->unique(table: fn () => (new (UserResource::getModel()))->getTable(), ignoreRecord: true)`, `TextInput::make('password')->password()->required(fn (string $context) => $context === 'create')->dehydrated(fn ($state) => filled($state))`, `CheckboxList::make('roles')->options(fn () => Role::pluck('name', 'name'))->required()` — pola field array-of-name identik `RoleResource`'s `permissions` (Unknown #2 Story 1.3 sudah settle ke arah ini, dipakai konsisten di sini)
- [ ] Buat `src/User/Filament/Resources/UserResource/Pages/CreateUser.php`, daftarkan route `create` di `UserResource::getPages()`
- [ ] `create()` memanggil `app(UserService::class)->create($data)` — presentation code hanya boleh panggil Service (AD-5)
- [ ] Jalankan: `vendor/bin/pest --filter=CreateUserTest`

### Test: `UserResource/EditUserTest` (edit, AC3)

- [ ] Buat `src/User/Filament/Resources/UserResource/Pages/EditUser.php` (form sama dengan Create minus `password` required, plus pre-fill `roles` dari `$record->roles->pluck('name')`), daftarkan route `edit`
- [ ] `save()`/mutate memanggil `app(UserService::class)->update($record, $data)`
- [ ] `roles` tetap `->required()` di form Edit yang sama (bukan hanya Create) — ini yang membuat TS-13 lulus otomatis begitu field-nya didefinisikan sekali dengan benar
- [ ] Jalankan: `vendor/bin/pest --filter=EditUserTest`

### Test: `UserResource/DeactivateUserTest` (AC2)

- [ ] Tambahkan `Filament\Actions\Action::make('deactivate')->requiresConfirmation()->visible(fn ($record) => app(UserService::class)->isActive($record))->action(fn ($record) => app(UserService::class)->deactivate($record))` ke `table()`-nya `UserResource` — **beda dari Story 1.3's `DeleteAction`**: `Action` dasar tidak otomatis meminta konfirmasi, `->requiresConfirmation()` wajib eksplisit di sini
- [ ] Jalankan: `vendor/bin/pest --filter=DeactivateUserTest`

---

## Running Tests

```bash
# Jalankan semua test story ini
vendor/bin/pest tests/Feature/User

# Jalankan 1 file spesifik
vendor/bin/pest --filter=UserServiceTest

# Jalankan seluruh suite package (pastikan tidak regresi)
composer test
```

---

## Red-Green-Refactor Workflow

### RED Phase (Complete) ✅

- ✅ 17 test scaffold ditulis dengan `->skip('alasan AC')` di 6 file
- ✅ Tidak ada fixture/factory baru diperlukan
- ✅ Implementation Checklist dibuat per file test
- ✅ Verifikasi terjalankan nyata: `vendor/bin/pest` → 17 skipped (baru) + 85 passed (pra-eksisting, tidak ada regresi), exit bersih (lihat Test Execution Evidence)

### GREEN Phase (Dev — Story 1.4 implementation)

1. Putuskan mekanisme penyimpanan status aktif/nonaktif (Unknown #1, prasyarat lingkungan)
2. Pilih 1 test dari Implementation Checklist, hapus `->skip(...)`, konfirmasi GAGAL (bukan error PHP fatal)
3. Implementasikan kode minimal agar test itu lulus
4. Jalankan test, pastikan hijau, lanjut ke test berikutnya
5. Urutan disarankan: `UserService` dulu (UserServiceTest → UserAccessRevocationTest), baru `UserResource` (ListUsers → CreateUser → EditUser → DeactivateUser)

### REFACTOR Phase

Setelah semua test AC1–AC3 hijau: `vendor/bin/pint` + `vendor/bin/phpstan analyse`, pastikan `composer test` tetap hijau, dan `ArchDomainBoundaryTest` (self-activating) tetap lulus begitu `src/User/Services/UserService.php` ada.

---

## Next Steps

1. Story 1.4 belum punya file story individual — begitu `create-story` dijalankan, mirror path checklist ini + 6 file test ke `Dev Notes`-nya
2. Putuskan mekanisme penyimpanan status aktif/nonaktif sebelum mengaktifkan test manapun (lihat Implementation Checklist)
3. Aktifkan 1 scaffold per task, red → green, urutan UserService dulu baru UserResource
4. Setelah semua lulus, refactor + `composer test` hijau
5. Update `sprint-status.yaml`: `1-4-manage-user` → `in-progress` lalu `done`

---

## Knowledge Base References Applied

- **test-quality.md** — Definition of Done generik diterjemahkan ke idiom Pest PHP (deterministic, explicit assertions, tidak ada assertion tersembunyi, satu concern per file)
- **confidence-gate.md** — confidence declaration (7/10) sebelum menulis scaffold, dengan 3 Unknown disurface eksplisit (storage mekanisme status, batas verifikasi akses tanpa panel auth, keterpisahan skema Customer yang belum eksis)
- **Tidak dipakai:** seluruh fragment Playwright Utils / Pact.js — lihat "Stack Deviation Notice" dan "Generation Mode" di atas

---

## Test Execution Evidence

### Initial Scaffold Verification (RED-phase inert-check)

**Command:** `vendor/bin/pest`

**Results:**

```
Tests:    17 skipped, 85 passed (180 assertions)
Duration: 5.97s
```

**Command (scoped):** `vendor/bin/pest tests/Feature/User --compact`

**Results:**

```
Tests:    17 skipped, 22 passed (64 assertions)
Duration: 2.53s
```

Rincian 17 skipped tersebar di 6 file baru (`UserServiceTest` 4, `UserAccessRevocationTest` 2, `UserResource/ListUsersTest` 1, `UserResource/CreateUserTest` 3, `UserResource/EditUserTest` 3, `UserResource/DeactivateUserTest` 4). 85 passed adalah seluruh suite pra-eksisting Story 1.1/1.2/1.3 — tidak ada satupun yang terganggu/regresi oleh penambahan file-file ini. Exit bersih, tidak ada fatal/error (aman untuk CI).

**Summary:**

- Total tests: 102 (85 pra-eksisting + 17 scaffold baru)
- Skipped: 17 (semua scaffold baru, red-phase, sesuai desain)
- Pra-eksisting: 85 passed (tidak terganggu)
- Status: ✅ Red-phase scaffolds terverifikasi inert

---

## Notes

- File story individual (`create-story`) belum dijalankan — checklist ini merujuk `epics.md` langsung, sama seperti Story 1.1/1.2/1.3.
- **Unknown terbuka (perlu keputusan sebelum/selama GREEN):** (1) mekanisme penyimpanan status aktif/nonaktif User — rekomendasi tabel Bazaar-owned baru, pola `bazaar_user_preferences`; (2) "akses dicabut seketika" hanya diverifikasi di level data (`UserService::isActive()`) karena belum ada panel auth (`->login()`) sama sekali di package ini; (3) "terpisah dari skema Customer" tidak diuji langsung karena domain Customer belum eksis (Epic 4).
- Tidak ada AD baru diperlukan untuk story ini — sepenuhnya tercakup AD-5 (Service layering), AD-16 (tidak ada seam untuk menempelkan interface/trait ke class asing di luar langkah instalasi manual, sama seperti `HasRoles` Story 1.3), AD-18 amendment (Bazaar tidak pernah memiliki tabel `users` host), AD-20 (User/Customer realm terpisah).
- `ListUsersTest` sengaja hanya 1 test (bukan 2 seperti `ListRolesTest` Story 1.3): `UserResource`/`ListUsers` sudah eksis sejak Story 1.1 dengan `navigationGroup = 'User & Access'` sudah benar dan lulus — menambah test navigation-group di sini hanya akan mengulang assertion yang sudah `PASS` hari ini, bukan skenario red yang sah. Bagian yang benar-benar red (kolom status) sudah dicakup di satu test yang ada.

---

**Generated by Murat (Master Test Architect, `bmad-testarch-atdd`)** — 2026-09-16
