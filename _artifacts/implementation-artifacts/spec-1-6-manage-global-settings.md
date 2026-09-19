---
title: 'Story 1.6: Manage Global Settings'
type: 'feature'
created: '2026-09-19'
status: 'done'
route: 'dispatch'
review_loop_iteration: 0
baseline_commit: '0747e850f5abad4ce7370b031434dd299d3b3fc7'
context: [
  '{project-root}/_artifacts/implementation-artifacts/epic-1-context.md',
  '{project-root}/_artifacts/planning-artifacts/architecture/architecture-Tigaphonic/bazaar-2026-09-12/ARCHITECTURE-SPINE.md',
  '{project-root}/_artifacts/test-artifacts/atdd-checklist-1-6-manage-global-settings.md',
]
---

<frozen-after-approval reason="human-owned intent — do not modify unless human renegotiates">

## Intent

**Problem:** Staff belum bisa melihat/mengubah parameter operasional per instalasi (Timeout Timer, gateway, Store Info, Refund %, Robots.txt, Analytics, SEO Defaults); `GlobalSettings` masih halaman placeholder.

**Approach:** Simpan parameter fixed di `spatie/laravel-settings` (kredensial via encrypted property), buka lewat `SettingsService` (`get()`/`update()`) sebagai satu-satunya gateway, dan tampilkan lewat halaman Filament List+Edit `GlobalSettings` berformulir yang memanggil Service — membuat 2 file ATDD (skip) hijau, kecuali 2 skenario Warehouse yang menunggu Epic 3.

## Boundaries & Constraints

**Always:**
- Storage `spatie/laravel-settings` group `bazaar`, tabel `settings` bawaan dependency (AD-18/AD-21); kredensial (`payment_gateways`, `shipping_couriers`) terenkripsi, tidak pernah plaintext di DB.
- `SettingsService::update(array)` hanya menerima key yang terdefinisi (key asing diabaikan diam-diam), memvalidasi refund % (whole number, min < max, `InvalidSettingValueException`), lalu menyimpan. Hanya key yang dikirim berubah.
- `get()` dan `update()` melempar `AuthorizationException` bila ada user login tanpa permission `manage-settings`; tanpa user login (console/job/guest) lolos.
- Migration Bazaar baru membuat permission `manage-settings` (`findOrCreate`) bila tabel permission ada.
- Halaman `GlobalSettings`: nav group `Global Settings`, `canAccess()` = permission, form → `SettingsService::update()`, toast sukses, error validasi refund di field `refund_min_percent`, `getHeaderActions()` tanpa `create`, memuat `BazaarStatusWidget` (heartbeat scheduler/queue, AD-17).
- Audit Trail otomatis: listener event `SettingsSaved` milik dependency di `AuditTrailRecorder` (User domain), `log_name=bazaar`, `event=updated`, hanya diff berubah, nilai kredensial diganti `***`. Tanpa `activity()` di `SettingsService`.
- Semua label UI bilingual EN/ID (`resources/lang/{en,id}`), token DESIGN.md.

**Never:**
- Tidak ada route `/robots.txt`; tidak ada tambah/hapus parameter lewat UI.
- Tidak memakai `SettingsPage`/`HasSettingsForms` dari `filament/spatie-laravel-settings-plugin` (menyimpan langsung, melewati Service, validasi, dan audit — melanggar AD-5); plugin tidak di-install.
- Tidak membuat domain Warehouse/Catalog. Tidak mengubah assertion test ATDD — hanya unskip + isi body.

## I/O & Edge-Case Matrix

| Scenario | Input / State | Expected Output / Behavior | Error Handling |
|----------|--------------|---------------------------|----------------|
| Update parsial | `update(['store_name'=>'X'])` | Hanya `store_name` berubah | N/A |
| Key asing | `update(['made_up_key'=>1])` | Tidak tersimpan | Diabaikan |
| Refund tak valid | min ≥ max, atau desimal | Ditolak | `InvalidSettingValueException`; form: error di `refund_min_percent` |
| Kredensial | `server_key` diset | DB tanpa plaintext; `get()` mengembalikan plaintext | N/A |
| Tanpa permission | user login tanpa `manage-settings` | Service melempar; halaman 403 | `AuthorizationException` |
| Audit | `update()` mengubah nilai | 1 entri `bazaar`/`updated`, secret `***` | N/A |

</frozen-after-approval>

## Code Map

- `src/Settings/Filament/Pages/GlobalSettings.php` + `resources/views/settings/global-settings.blade.php` -- placeholder; ganti form nyata.
- `src/Settings/Services/SettingsService.php`, `Settings/Support/BazaarSettings.php` (Settings class), `Settings/Exceptions/InvalidSettingValueException.php` -- baru. Settings class bukan di `Models/` agar lolos `ArchDomainBoundaryTest`.
- `src/Install/Filament/Widgets/BazaarStatusWidget.php` -- baru; baca cache key `bazaar:heartbeat:*` seperti `BazaarStatusCommand`.
- `src/User/Support/AuditTrailRecorder.php` -- tambah handler `SettingsSaved`; daftar di `BazaarServiceProvider::registerAuditTrail()`.
- `database/migrations/` -- migration `settings` (dari vendor, `spatie/laravel-settings` publish) + `create_bazaar_manage_settings_permission`; daftarkan di `hasMigrations()`; `tests/TestCase.php` sudah memuat `database/migrations`.
- `config/bazaar.php` -- `pages` sudah memuat `GlobalSettings`; daftar Settings class di `config/settings.php` vendor via provider.
- `tests/Feature/Settings/*` -- 24 skenario skip; AC6 (2 test Warehouse) tetap skip.

## Tasks & Acceptance

**Execution:**
- [x] `composer.json` -- require `spatie/laravel-settings ^3.9` -- AD-21 (catatan: `package:purge-skeleton` menghapus `.env` testbench; tulis ulang `APP_KEY`)
- [x] `database/migrations/*` -- tabel `settings` + permission `manage-settings`; `BazaarServiceProvider` -- migrasi, `settings.settings` config, listener audit
- [x] `src/Settings/Support/BazaarSettings.php` -- 22 properti fixed + default; `encrypted()` untuk kredensial
- [x] `src/Settings/Services/SettingsService.php` + exception -- get/update/otorisasi/validasi
- [x] `src/User/Support/AuditTrailRecorder.php` -- audit `SettingsSaved` + redaksi secret
- [x] `src/Install/Filament/Widgets/BazaarStatusWidget.php` -- heartbeat
- [x] `src/Settings/Filament/Pages/GlobalSettings.php` + view + lang -- form per section (Timeout, Payment, Shipping, Warehouse, Store, Refund, Robots, Analytics, SEO)
- [x] `tests/Feature/Settings/*` -- unskip, isi body; tambah test audit redaksi + partial update

**Acceptance Criteria:**
- Given Staff berwenang membuka Global Settings, then hanya parameter fixed tampil, tanpa aksi tambah/hapus.
- Given Timeout Timer disimpan, then `get()` berikutnya mengembalikan nilai baru.
- Given gateway diaktifkan dengan daftar metode, then hanya metode yang di-enable tersimpan di `payment_gateways[].enabled_methods`.
- Given save apa pun, then entri Audit Trail tercatat tanpa nilai kredensial.

### Review Findings
- [x] [Review][Patch] Integer field casting in Livewire form untested [GlobalSettingsPageTest.php]
- [x] [Review][Patch] Timeout values validation missing test [GlobalSettingsServiceTest.php]
- [x] [Review][Patch] Role resource permission adoption missing UI test [RoleResource.php:54]
- [x] [Review][Patch] Settings config registration untested [BazaarServiceProvider.php:61]
- [x] [Review][Patch] BazaarStatusWidget cache read unsafe [BazaarStatusWidget.php:40]
- [x] [Review][Patch] GlobalSettings bilingual rule broken (PAYMENT_METHODS hardcoded) [GlobalSettings.php]
- [x] [Review][Patch] RoleService::ensureAdminRole mixes guards and crashes syncPermissions [RoleService.php]
- [x] [Review][Patch] seed_bazaar_settings_defaults lacks rollback (down method) [seed_bazaar_settings_defaults.php]
- [x] [Review][Patch] DomainMigrationTest hardcodes settings row count [DomainMigrationTest.php]
- [x] [Review][Patch] AuditTrailRecorder hardcodes model exclusion [AuditTrailRecorder.php]
- [x] [Review][Patch] seed_bazaar_manage_settings_permission hardcodes string [seed_bazaar_manage_settings_permission.php]
- [x] [Review][Patch] Wrong dependency event used for Audit Trail (SavingSettings vs SettingsSaved) [BazaarServiceProvider.php:169]
- [x] [Review][Patch] User model lacks HasRoles trait check [UserService.php:264]
- [x] [Review][Defer] SettingsService::update() array validation weak — deferred: Sudah dicatat di Spec Triage Log untuk API Layer (1.9).
- [x] [Review][Defer] RoleService::ensureShippedPermissions lacks guard — deferred: Sudah dicatat di Spec Triage Log terkait deferred-work panel-auth.

**Rejected Findings:**
- `AuditTrailRecorder array comparison flawed`: Reject (low). Array Livewire order stabil; over-engineering recursive diff.
- `SettingsService::assertValidType ignores union types`: Reject (false). BazaarSettings properties nullable (`?type`) adalah NamedType, bukan UnionType, sehingga `getName()` aman.

## Implementation Notes

- Data migrations (`seed_bazaar_settings_defaults`, `seed_bazaar_manage_settings_permission`) tinggal di `database/data-migrations/`, dimuat `loadMigrationsFrom` di `packageBooted()`. `DomainMigrationTest` (Story 1.1) mewajibkan semua file `database/migrations/` memakai `->ulid(` — tabel `settings` milik dependency juga dipublish `bazaar:install` (pola sama dengan permission), bukan disalin ke package.
- `spatie/laravel-settings` menolak `save()` bila ada properti tanpa baris tersimpan (nilai default kelas tidak cukup) — semua 22 parameter di-seed sekali. Docblock generik `array<...>` pada properti Settings memicu `CouldNotResolveDocblockType`; dihapus.
- Repository dependency membuat baris lewat model Eloquent `SettingsProperty` (seed migration); model itu dikecualikan dari recorder global agar audit hanya lewat `handleSettingsSaving()` (kredensial `***`).
- 2 test Warehouse memakai `test()->skip()` yang error di Pest 4 (`Call to undefined method TestCase::skip()`, penyebab 29 gagal di baseline); diganti `$this->markTestSkipped()` dengan teks yang sama.
- Store logo/favicon/OG image = input teks path/URL sampai pipeline media (Story 1.10).
- `composer require` menjalankan `package:purge-skeleton` → `.env` testbench hilang; `APP_KEY` dipulihkan lokal (di luar git).
- phpstan: 3 error lama `view()` di `BazaarServiceProvider` tetap, tanpa error baru. Full suite: 196 passed, 2 skipped (Warehouse, Epic 3).

## Spec Change Log

## Review Triage Log

| Finding | Verdict | Route | Evidence |
|---|---|---|---|
| Widget health state (missing/stale/healthy) & render tanpa test | medium | patch | `BazaarStatusWidgetTest` ditambahkan (3 test). |
| Jalur `migrate` (data-migrations, permission, 22 baris default) tak diuji lewat provider | medium | patch | Test ditambahkan di `DomainMigrationTest`. |
| `BazaarSettings` tidak didaftarkan ke `settings.settings` (Code Map/task) | low | patch | Didaftarkan di `packageRegistered()`. |
| Repeater membuang `provider` saat save | false | reject | Test `keeps the provider identity...` hijau; state repeater mempertahankan key. |
| Kredensial terdekripsi dikirim ke Livewire `$data` | medium | defer | Nyata; perlu keputusan produk (field kosong = tak berubah). |
| Bentuk baris array gateway/kurir & subset `enabled_methods` tak divalidasi di Service | medium | defer | Hanya UI yang membatasi; valid saat API Layer (1.9) membuka Service. |
| Panel guard non-default → `auth()->user()` null → gate lolos | medium | defer | Sudah terkait deferred-work panel-auth; belum bisa diverifikasi. |
| Permission/default seed dilewati bila tabel belum ada | low | reject | Migration terpublish bertimestamp selalu lebih dulu; kegagalan tabel hilang bersuara keras. |
| `bazaar:install` dua kali menggandakan migration settings | false | reject | Nama file stub tetap; `vendor:publish` tidak menimpa/menduplikasi. |
| Heartbeat non-Carbon / masa depan | low | reject | Penulis heartbeat internal selalu `now()`; logika sama dengan `bazaar:status`. |
| Race dua Staff menyimpan bersamaan | low | reject | Perbaikan menambah locking; tidak realistis di v1. |
| Label metode/kurir & nav group berbahasa Inggris | low | reject | Nama merek/istilah teknis; nav group dikunci test ATDD. |
| Tanpa validasi panjang/URL string bebas | low | reject | Fix menambah aturan tanpa dasar requirement. |
| Audit menutup seluruh array gateway sebagai `***` | low | reject | Disengaja: kredensial tidak boleh terekam. |
| Warehouse FK tanpa validasi | medium | defer | Sudah di deferred-work (Epic 3). |
| Docs README/upgrade note | low | reject | Di luar scope story; README belum ada. |

## Design Notes

- `payment_gateways`: `[{provider, active, server_key, client_key, enabled_methods: string[]}]`; `shipping_couriers`: `[{provider, active, api_key, enabled_couriers: string[]}]`. Bentuk final ditetapkan di sini (checklist menunda ke green-phase).
- `default_warehouse_id`: `?string`, tanpa validasi FK; input form disabled dengan hint "tersedia setelah modul Warehouse" sampai Epic 3.
- Gate `get()` dicatat ke `deferred-work.md`: domain lain yang dipicu Staff tanpa `manage-settings` butuh jalur baca internal.

## Verification

**Commands:**
- `vendor/bin/pest` -- expected: hijau; hanya 2 skip Warehouse tersisa di tests/Feature/Settings
- `vendor/bin/phpstan analyse` -- expected: tanpa error baru
- `vendor/bin/pint --test` -- expected: file baru/diubah bersih
