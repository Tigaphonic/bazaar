---
stepsCompleted: ['step-01-preflight-and-context', 'step-02-generation-mode', 'step-03-test-strategy', 'step-04-generate-tests', 'step-04c-aggregate', 'step-05-validate-and-complete']
lastStep: 'step-05-validate-and-complete'
lastSaved: '2026-09-16'
workflowType: 'testarch-atdd'
storyId: '1.3'
storyKey: '1-3-manage-role-permission'
storyFile: '_artifacts/planning-artifacts/epics.md'
atddChecklistPath: '_artifacts/test-artifacts/atdd-checklist-1-3-manage-role-permission.md'
generatedTestFiles:
  - 'tests/Feature/User/RoleServiceTest.php'
  - 'tests/Feature/User/RolePermissionPropagationTest.php'
  - 'tests/Feature/User/NoHardcodedRoleTest.php'
  - 'tests/Feature/User/RoleResource/ListRolesTest.php'
  - 'tests/Feature/User/RoleResource/CreateRoleTest.php'
  - 'tests/Feature/User/RoleResource/EditRoleTest.php'
  - 'tests/Feature/User/RoleResource/DeleteRoleTest.php'
inputDocuments:
  - '_artifacts/planning-artifacts/epics.md (Story 1.3, baris 319–338)'
  - '_artifacts/implementation-artifacts/epic-1-context.md'
  - '_artifacts/planning-artifacts/architecture/architecture-Tigaphonic/bazaar-2026-09-12/ARCHITECTURE-SPINE.md (AD-5, AD-6, AD-18, AD-20)'
  - '_artifacts/planning-artifacts/ux-designs/ux-Tigaphonic/bazaar-2026-09-11/EXPERIENCE.md (§Navigation, §Maker-Checker & Approval Pattern, Flow 10)'
  - 'src/User/Filament/Resources/UserResource.php'
  - 'src/BazaarServiceProvider.php'
  - 'tests/TestCase.php'
  - 'tests/Feature/Install/PanelNavigationTest.php'
  - 'tests/Feature/Shell/Components/ModalTest.php'
  - 'tests/Feature/Shell/UserThemePreferenceTest.php'
  - 'vendor/filament/actions/src/DeleteAction.php'
  - 'vendor/filament/tables/src/Testing/TestsActions.php'
  - 'vendor/filament/forms/src/Testing/TestsForms.php'
  - '.claude/skills/bmad-testarch-atdd/resources/knowledge/test-quality.md'
  - '.claude/skills/bmad-testarch-atdd/resources/knowledge/confidence-gate.md'
  - '.claude/skills/bmad-testarch-atdd/resources/knowledge/playwright-utils-mandate.md'
---

# ATDD Checklist - Epic 1, Story 1.3: Manage Role & Permission

**Date:** 2026-09-16
**Author:** Binyo
**Primary Test Level:** Integration (Testbench + Filament/Livewire testing) — tidak ada Unit/E2E terpisah

---

## Story Summary

Staff berwenang membuat Role baru dan mencentang permission granular untuknya tanpa deploy kode, sehingga akses siapa-berwenang-apa bisa diatur langsung dari dashboard.

**As a** Staff berwenang
**I want** membuat Role baru dan mencentang permission granular untuknya tanpa deploy kode
**So that** saya bisa mengatur siapa berwenang melakukan apa tanpa menunggu rilis developer

---

## Acceptance Criteria

1. **AC1** — Given Staff berwenang membuka User & Access → Roles. When Staff membuat Role baru dan mencentang sejumlah permission (mis. "can approve publish Item", "can approve Return"). Then Role tersimpan dengan kumpulan permission granular tsb, tanpa perlu deploy kode.
2. **AC2** — Given sebuah Role sudah dipakai beberapa User. When Staff mengubah permission Role tsb. Then perubahan berlaku ke semua User pemegang Role itu secara instan.
3. **AC3** — Given "Approval Role" dirujuk di berbagai domain (publish Item, moderasi Review, approve Cancel/Retur, approve Refund). When Staff memeriksa konsep ini. Then sistem tidak memiliki role hardcoded bernama "Approval Role" — istilah ini merujuk ke Role manapun yang memegang permission terkait. And Role bisa dihapus dari dashboard (dengan modal konfirmasi karena aksi sulit dibalik).

---

## Story Integration Metadata

- **Story ID:** `1.3`
- **Story Key:** `1-3-manage-role-permission`
- **Story File:** `_artifacts/planning-artifacts/epics.md` (Story 1.3 section, baris 319–338) — belum ada file story individual terpisah (`create-story` belum dijalankan), sama seperti Story 1.1/1.2
- **Checklist Path:** `_artifacts/test-artifacts/atdd-checklist-1-3-manage-role-permission.md`
- **Generated Test Files:** lihat bagian "Red-Phase Test Scaffolds Created" di bawah

Story ini belum melalui `create-story` BMM — begitu file story individual dibuat, mirror path checklist & test files ini ke `Dev Notes`-nya (pola sama seperti Story 1.1/1.2).

---

## Stack Deviation Notice

Sama seperti Story 1.1/1.2: knowledge base skill ini (Playwright Utils, Pact.js) berorientasi JS/TS + browser-first dan sebagian besar tidak relevan di sini.

- **Tidak dipakai:** seluruh fragment Pact.js (tidak ada microservices/contract boundary di story ini — RoleResource murni internal Filament CRUD)
- **Tidak dipakai:** `playwright-utils-mandate` dan seluruh fragment turunannya — `tea_use_playwright_utils: true` di config, tapi mandate itu sendiri mensyaratkan `@seontechnologies/playwright-utils` benar-benar terpasang di `package.json` (lihat `playwright-utils-mandate.md` §Scope); config.yaml sendiri sudah mencatat paket itu belum jadi dependency nyata → gate relevansinya no-op. Tidak ada spec Playwright baru dibuat untuk story ini sama sekali (lihat alasan di §Generation Mode di bawah), jadi pertanyaan vanilla-vs-utils tidak muncul.
- **Dipakai (diterjemahkan ke idiom Pest PHP):** `test-quality.md` dan `confidence-gate.md` — sama seperti sebelumnya
- **Test level:** Testbench `TestCase` + Pest v4, plus Filament/Livewire testing helpers (`Livewire::test()` + macro `TestsForms`/`TestsActions`/`TestsRecords`/`TestsColumns` dari `filament/forms`, `filament/actions`, `filament/tables`) — pola baru dibanding Story 1.1/1.2 karena ini CRUD Resource pertama yang diuji di package ini

---

## Generation Mode

**Mode: AI Generation** — AC standar (CRUD Role + propagasi permission + delete-dengan-konfirmasi), tidak ada interaksi UI yang butuh verifikasi browser nyata. Berbeda dengan Story 1.2 (yang butuh Playwright untuk membuktikan swap tema instan tanpa reload — sesuatu yang cuma browser sungguhan bisa buktikan), **tidak ada satu pun AC Story 1.3 yang butuh bukti browser nyata**: form Create/Edit, checkbox permission, dan modal konfirmasi delete semuanya adalah state Livewire yang bisa diverifikasi penuh lewat `Livewire::test()` + macro testing Filament bawaan (`assertTableActionMounted`, `assertFormSet`, dst. — diverifikasi langsung ke source `vendor/filament/*/src/Testing/`). Keputusan: **nol file Playwright baru untuk story ini** (deviasi eksplisit, disurface di sini, bukan disembunyikan).

---

## Test Strategy

Level backend+fullstack: **Integration** (Testbench + Filament/Livewire testing) untuk hampir semua skenario; tidak ada Unit terisolasi (tidak ada logika murni tanpa DB) dan tidak ada E2E/browser (lihat Generation Mode).

| ID | AC | Prioritas | Level | Skenario |
|---|---|---|---|---|
| TS-1 | AC1 | P0 | Integration | `RoleService::create()` menyimpan Role dengan permission yang dicentang |
| TS-2 | AC1 | P0 | Integration | Form `CreateRole` (Livewire) membuat Role dengan permission tercentang, tanpa error |
| TS-3 | AC1 | P1 | Integration | `CreateRole` menolak nama Role kosong / duplikat (validasi form) |
| TS-4 | AC1 | P2 | Integration | `RoleService::create()` menerima nol permission tercentang (Role read-only) |
| TS-5 | AC2 | P0 | Integration | `RoleService::update()` mengganti (bukan menambah) set permission Role |
| TS-6 | AC2 | P0 | Integration | Perubahan permission Role via `RoleService::update()` berlaku instan ke semua User pemegang Role (cache spatie/laravel-permission ter-invalidate otomatis) |
| TS-7 | AC2 | P1 | Integration | Form `EditRole` (Livewire) pre-fill nama+permission existing, dan menyimpan perubahan |
| TS-8 | AC3 | P0 | Integration | Tabel `RoleResource` punya action `delete` yang **tidak langsung mengeksekusi** saat di-mount (bukti modal konfirmasi) |
| TS-9 | AC3 | P0 | Integration | Delete Role benar-benar menghapus record setelah action dikonfirmasi/dipanggil |
| TS-10 | AC3 | P1 | Integration | Dua Role berbeda nama yang sama-sama pegang 1 permission sama-sama meloloskan gate — tidak ada kasus khusus untuk nama "Approval Role" |
| TS-11 | AC3 | P1 | Integration | `bazaar:install` tidak pernah men-seed Role apa pun (apalagi bernama "Approval Role") |
| TS-12 | AC1 | P2 | Integration | `RoleResource` terdaftar di navigation group "User & Access", dan `ListRoles` menampilkan kolom `name` |

**Red phase confirmation:** seluruh 17 test (rincian di TS-1..TS-12, beberapa TS berisi >1 test fisik) dipastikan gagal sekarang — `Tigaphonic\Bazaar\User\Services\RoleService`, `Tigaphonic\Bazaar\User\Filament\Resources\RoleResource` (+ `Pages\ListRoles`/`CreateRole`/`EditRole`), dan `Spatie\Permission\Models\Role`/`Permission` semuanya belum ada (paket `spatie/laravel-permission` belum di-`composer require`); `Workbench\App\Models\User` belum memakai trait `Spatie\Permission\Traits\HasRoles`. Diverifikasi lewat run nyata (`vendor/bin/pest`) — lihat Test Execution Evidence.

### Confidence Gate

```
Confidence: 7
Rationale: AC dikutip literal dari epics.md Story 1.3 (baris 319–338); domain target (src/User,
  navigationGroup 'User & Access') dikutip dari UserResource.php yang sudah ada + EXPERIENCE.md
  ("Roles & Permissions | Sidebar -> User & Access -> Roles"); keharusan Service-layer (RoleService,
  bukan akses Model langsung dari Resource) dikutip literal dari AD-5 ("Presentation code ... may
  only call a Service"); model backing (Spatie\Permission\Models\Role/Permission, bukan model custom
  Bazaar) dikutip dari epic-1-context.md ("dibangun di atas spatie/laravel-permission") + AD-18
  (tabel dependency tetap native, tidak pernah disubclass/dimodel-ulang oleh Bazaar sejauh ini di
  package); default `requiresConfirmation()` DeleteAction diverifikasi langsung dari source
  vendor/filament/actions/src/DeleteAction.php baris 37; seluruh API testing Filament (fillForm,
  assertHasFormErrors, mountTableAction, assertTableActionMounted, callTableAction,
  assertCanSeeTableRecords, assertTableColumnExists) diverifikasi langsung dari
  vendor/filament/{forms,tables}/src/Testing/*.php, bukan ditebak dari dokumentasi versi lama.
Unknowns:
  - Apakah Bazaar akan membuat PermissionResource terpisah untuk mengelola katalog Permission itu
    sendiri. AC1 hanya bicara "mencentang" permission yang sudah ada, bukan "membuat" permission baru
    lewat UI -- diasumsikan TIDAK perlu PermissionResource untuk story ini (permission dideklarasikan
    developer per-domain seiring epic berjalan, bukan staff-authored). Confidence pada asumsi ini
    sedang (permission catalog penuh baru terbentuk lintas epic berikutnya) -- displasi di sini
    dengan penuh transparansi, bukan disembunyikan; test yang ditulis tidak bergantung pada asumsi
    ini secara ketat (semuanya men-seed Permission via factory langsung di setiap test, tidak
    mengandalkan katalog tetap).
  - Struktur payload form `permissions` (array of Permission `name` string via CheckboxList) adalah
    pilihan paling idiomatis untuk Filament + spatie/laravel-permission, tapi belum diverifikasi ke
    kode nyata (RoleResource belum ada) -- kalau dev memilih array of Permission ID alih-alih name
    saat implementasi, form-field test (TS-2/TS-3/TS-7) perlu disesuaikan session GREEN.
```
Confidence 7 ≥ 7 → lanjut generate scaffold, kedua Unknown di atas disurface eksplisit di Implementation Checklist di bawah, bukan ditebak diam-diam.

---

## Generation Note (deviation from JS/subagent orchestration)

Sama seperti Story 1.1: Step 4/4C skill ini dirancang untuk dual-subagent (Worker A: API/TypeScript, Worker B: E2E/Playwright) yang menulis JSON ke `/tmp`. Proyek ini PHP/Pest, dan story ini murni backend+Filament-Livewire tanpa kebutuhan browser nyata (lihat §Generation Mode) — scaffold ditulis langsung (sequential, satu track, tanpa subagent, tanpa file JSON perantara), sesuai deviasi yang sudah disetujui sejak Step 1/1.1.

## Red-Phase Test Scaffolds Created (17 skipped)

### `tests/Feature/User/RoleServiceTest.php` (4 tests)

- ✅ `it creates a Role with the given granular permissions, no deploy required` — SKIPPED — **Verifies:** AC1
- ✅ `it creates a Role with zero permissions when none are checked` — SKIPPED — **Verifies:** AC1 (edge case)
- ✅ `it replaces a Role's permission set on update rather than appending to it` — SKIPPED — **Verifies:** AC2
- ✅ `it deletes a Role` — SKIPPED — **Verifies:** AC3

### `tests/Feature/User/RolePermissionPropagationTest.php` (2 tests)

- ✅ `it propagates a Role permission change instantly to every User holding that Role` — SKIPPED — **Verifies:** AC2
- ✅ `it never hardcodes "Approval Role" -- any Role holding the relevant permission gates the same action` — SKIPPED — **Verifies:** AC3

### `tests/Feature/User/NoHardcodedRoleTest.php` (1 test)

- ✅ `it seeds no Role at all after bazaar:install, let alone one hardcoded as "Approval Role"` — SKIPPED — **Verifies:** AC3

### `tests/Feature/User/RoleResource/ListRolesTest.php` (2 tests)

- ✅ `it lists every Role with its name column` — SKIPPED — **Verifies:** AC1
- ✅ `it registers the Roles resource under the User & Access navigation group` — SKIPPED — **Verifies:** AC1

### `tests/Feature/User/RoleResource/CreateRoleTest.php` (3 tests)

- ✅ `it creates a Role with checked permissions via the dashboard form` — SKIPPED — **Verifies:** AC1
- ✅ `it rejects a Role name left blank` — SKIPPED — **Verifies:** AC1 (edge case)
- ✅ `it rejects a duplicate Role name` — SKIPPED — **Verifies:** AC1 (edge case)

### `tests/Feature/User/RoleResource/EditRoleTest.php` (2 tests)

- ✅ `it pre-fills the form with the Role's current name and checked permissions` — SKIPPED — **Verifies:** AC2
- ✅ `it saves an updated permission set for an existing Role` — SKIPPED — **Verifies:** AC2

### `tests/Feature/User/RoleResource/DeleteRoleTest.php` (3 tests)

- ✅ `it exposes a delete table action for each Role row` — SKIPPED — **Verifies:** AC3
- ✅ `it does not delete the Role merely by mounting the delete confirmation modal` — SKIPPED — **Verifies:** AC3 (modal konfirmasi)
- ✅ `it deletes the Role once the delete action is confirmed` — SKIPPED — **Verifies:** AC3

---

## Data Factories / Fixtures / Mocks

Tidak ada factory Eloquent baru dibuat — `Spatie\Permission\Models\Permission::create(['name' => ...])` dipanggil langsung per-test (vendor model, tidak butuh factory package ini). `Workbench\App\Models\User::create()` dipakai untuk simulasi host User (sudah dipakai `HasFactory` tapi test-test di sini membuatnya inline langsung, sejalan dengan pola `UserThemePreferenceTest.php`).

---

## Implementation Checklist

### Prasyarat lingkungan (sebelum test manapun bisa diaktifkan)

- [ ] `composer require spatie/laravel-permission`
- [ ] Publish/load migration bawaan `spatie/laravel-permission` (tabel `roles`, `permissions`, `model_has_roles`, `model_has_permissions`, `role_has_permissions`) — **apa adanya**, jangan disalin ke `database/migrations/` milik Bazaar, jangan dipaksa ULID (AD-18)
- [ ] Tambahkan `use Spatie\Permission\Traits\HasRoles;` ke `workbench/app/Models/User.php` — mensimulasikan langkah instalasi manual yang didokumentasikan Bazaar untuk User model milik *host* (bukan sesuatu yang Bazaar suntikkan otomatis ke kelas yang tidak ia miliki — AD-16 tidak menyediakan seam untuk "menempelkan trait ke class asing", jadi ini didokumentasikan sebagai langkah instalasi manual, persis seperti Filament sendiri mensyaratkan `implements FilamentUser`)
- [ ] Putuskan (developer, saat GREEN): payload form `permissions` di CheckboxList pakai `Permission::name` (asumsi test saat ini) atau `Permission::id` — lihat Unknown #2 di Confidence Gate

### Test: `RoleServiceTest` (AC1, AC2, AC3)

- [ ] Buat `src/User/Services/RoleService.php`: `create(array $data): Role`, `update(Role $role, array $data): Role`, `delete(Role $role): void` — `$data` berbentuk `['name' => string, 'permissions' => string[]]`; gunakan `$role->syncPermissions($data['permissions'])` (bukan `givePermissionTo` berulang) supaya update benar-benar *mengganti*, bukan menambah
- [ ] Jalankan: `vendor/bin/pest --filter=RoleServiceTest`

### Test: `RolePermissionPropagationTest` (AC2, AC3)

- [ ] Tidak ada kode tambahan di luar `RoleService` di atas — `syncPermissions()` sudah memanggil `PermissionRegistrar::forgetCachedPermissions()` bawaan spatie secara otomatis; test ini murni pembuktian, bukan pemicu kode baru
- [ ] Pastikan `workbench/app/Models/User.php` sudah pakai `HasRoles` (prasyarat lingkungan di atas)
- [ ] Jalankan: `vendor/bin/pest --filter=RolePermissionPropagationTest`

### Test: `NoHardcodedRoleTest` (AC3)

- [ ] Pastikan tidak ada seeder/migration Bazaar yang membuat baris `Role` apa pun saat `bazaar:install`
- [ ] Jalankan: `vendor/bin/pest --filter=NoHardcodedRoleTest`

### Test: `RoleResource/ListRolesTest` (AC1)

- [ ] Buat `src/User/Filament/Resources/RoleResource.php`: `getModel()` → `Spatie\Permission\Models\Role::class`; `$navigationGroup = 'User & Access'`; kolom tabel `name` (+ opsional badge jumlah permission)
- [ ] Buat `src/User/Filament/Resources/RoleResource/Pages/ListRoles.php`
- [ ] Daftarkan `RoleResource::class` di `config('bazaar.resources')`
- [ ] Jalankan: `vendor/bin/pest --filter=ListRolesTest`

### Test: `RoleResource/CreateRoleTest` (AC1)

- [ ] Buat `src/User/Filament/Resources/RoleResource/Pages/CreateRole.php`
- [ ] Form: `TextInput::make('name')->required()->unique(table: 'roles')`, `CheckboxList::make('permissions')->options(Permission::pluck('name', 'name'))`
- [ ] `create()` memanggil `app(RoleService::class)->create($data)` — presentation code hanya boleh panggil Service (AD-5), tidak boleh manipulasi `Role`/`Permission` langsung di halaman
- [ ] Jalankan: `vendor/bin/pest --filter=CreateRoleTest`

### Test: `RoleResource/EditRoleTest` (AC2)

- [ ] Buat `src/User/Filament/Resources/RoleResource/Pages/EditRole.php` (form sama dengan Create, plus pre-fill `permissions` dari `$record->permissions->pluck('name')`)
- [ ] `save()`/mutate memanggil `app(RoleService::class)->update($record, $data)`
- [ ] Jalankan: `vendor/bin/pest --filter=EditRoleTest`

### Test: `RoleResource/DeleteRoleTest` (AC3)

- [ ] Tambahkan `Filament\Actions\DeleteAction::make()` ke `table()`-nya `RoleResource` — **tidak perlu** memanggil `->requiresConfirmation()` manual, itu sudah default bawaan Filament; `delete()`-nya diarahkan lewat `app(RoleService::class)->delete($record)` (bukan `$record->delete()` langsung, tetap taat AD-5)
- [ ] Jalankan: `vendor/bin/pest --filter=DeleteRoleTest`

---

## Running Tests

```bash
# Jalankan semua test story ini
vendor/bin/pest tests/Feature/User

# Jalankan 1 file spesifik
vendor/bin/pest --filter=RoleServiceTest

# Jalankan seluruh suite package (pastikan tidak regresi)
composer test
```

---

## Red-Green-Refactor Workflow

### RED Phase (Complete) ✅

- ✅ 17 test scaffold ditulis dengan `->skip('alasan AC')` di 7 file
- ✅ Tidak ada fixture/factory baru diperlukan (vendor model `Permission`/`Role` dipakai langsung, `Workbench\App\Models\User::create()` inline)
- ✅ Implementation Checklist dibuat per file test
- ✅ Verifikasi terjalankan nyata: `vendor/bin/pest` → 17 skipped (baru) + 63 passed (pra-eksisting, tidak ada regresi), exit bersih (lihat Test Execution Evidence)

### GREEN Phase (Dev — Story 1.3 implementation)

1. Selesaikan Prasyarat lingkungan (composer require, trait `HasRoles` di workbench User, migrasi spatie ter-load)
2. Pilih 1 test dari Implementation Checklist, hapus `->skip(...)`, konfirmasi GAGAL (bukan error PHP fatal)
3. Implementasikan kode minimal agar test itu lulus
4. Jalankan test, pastikan hijau, lanjut ke test berikutnya
5. Urutan disarankan: `RoleService` dulu (RoleServiceTest → RolePermissionPropagationTest → NoHardcodedRoleTest), baru `RoleResource` (ListRoles → CreateRole → EditRole → DeleteRole)

### REFACTOR Phase

Setelah semua test AC1–AC3 hijau: `vendor/bin/pint` + `vendor/bin/phpstan analyse`, pastikan `composer test` tetap hijau, dan `ArchDomainBoundaryTest` (self-activating) tetap lulus begitu `src/User/Services/RoleService.php` ada.

---

## Next Steps

1. Story 1.3 belum punya file story individual — begitu `create-story` dijalankan, mirror path checklist ini + 7 file test ke `Dev Notes`-nya
2. Selesaikan prasyarat lingkungan sebelum mengaktifkan test manapun (lihat Implementation Checklist)
3. Aktifkan 1 scaffold per task, red → green, urutan RoleService dulu baru RoleResource
4. Setelah semua lulus, refactor + `composer test` hijau
5. Update `sprint-status.yaml`: `1-3-manage-role-permission` → `in-progress` lalu `done`

---

## Knowledge Base References Applied

- **test-quality.md** — Definition of Done generik diterjemahkan ke idiom Pest PHP (deterministic, explicit assertions, tidak ada assertion tersembunyi, satu concern per file)
- **confidence-gate.md** — confidence declaration (7/10) sebelum menulis scaffold, dengan 2 Unknown disurface eksplisit (PermissionResource scope, bentuk payload form `permissions`)
- **Tidak dipakai:** seluruh fragment Playwright Utils / Pact.js — lihat "Stack Deviation Notice" dan "Generation Mode" di atas

---

## Test Execution Evidence

### Initial Scaffold Verification (RED-phase inert-check)

**Command:** `vendor/bin/pest`

**Results:**

```
Tests:    17 skipped, 63 passed (116 assertions)
Duration: 3.39s
```

Rincian 17 skipped tersebar di 7 file baru (`RoleServiceTest` 4, `RolePermissionPropagationTest` 2, `NoHardcodedRoleTest` 1, `RoleResource/ListRolesTest` 2, `RoleResource/CreateRoleTest` 3, `RoleResource/EditRoleTest` 2, `RoleResource/DeleteRoleTest` 3). 63 passed adalah seluruh suite pra-eksisting Story 1.1/1.2 — tidak ada satupun yang terganggu/regresi oleh penambahan file-file ini. Exit bersih, tidak ada fatal/error (aman untuk CI).

**Summary:**

- Total tests: 80 (63 pra-eksisting + 17 scaffold baru)
- Skipped: 17 (semua scaffold baru, red-phase, sesuai desain)
- Pra-eksisting: 63 passed (tidak terganggu)
- Status: ✅ Red-phase scaffolds terverifikasi inert

---

## Notes

- File story individual (`create-story`) belum dijalankan — checklist ini merujuk `epics.md` langsung, sama seperti Story 1.1/1.2.
- **Unknown terbuka (perlu keputusan sebelum/selama GREEN):** (1) apakah perlu `PermissionResource` terpisah untuk mengelola katalog Permission itu sendiri — saat ini diasumsikan tidak; (2) bentuk payload form `permissions` (name vs id) — lihat Confidence Gate di atas.
- Tidak ada AD baru diperlukan untuk story ini — sepenuhnya tercakup AD-5 (Service layering), AD-6 (cross-domain), AD-18 (dependency-owned tables/models tetap native).

---

**Generated by Murat (Master Test Architect, `bmad-testarch-atdd`)** — 2026-09-16
