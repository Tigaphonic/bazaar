---
stepsCompleted: ['step-01-preflight-and-context', 'step-02-generation-mode', 'step-03-test-strategy', 'step-04-generate-tests', 'step-04c-aggregate', 'step-05-validate-and-complete']
lastStep: 'step-05-validate-and-complete'
lastSaved: '2026-09-13'
workflowType: 'testarch-atdd'
storyId: '1.1'
storyKey: '1-1-package-skeleton-filament-panel-connection-migration'
storyFile: '_artifacts/planning-artifacts/epics.md'
atddChecklistPath: '_artifacts/test-artifacts/atdd-checklist-1-1-package-skeleton-filament-panel-connection-migration.md'
generatedTestFiles:
  - 'tests/Unit/PackageManifestTest.php'
  - 'tests/Unit/PackageStructureTest.php'
  - 'tests/Feature/Install/BazaarInstallCommandTest.php'
  - 'tests/Feature/Install/DomainMigrationTest.php'
  - 'tests/Feature/Install/BazaarStatusCommandTest.php'
  - 'tests/Feature/Install/PanelNavigationTest.php'
  - 'tests/ArchDomainBoundaryTest.php'
inputDocuments:
  - '_artifacts/planning-artifacts/epics.md'
  - 'tests/TestCase.php'
  - 'tests/Pest.php'
  - 'tests/ExampleTest.php'
  - 'tests/ArchTest.php'
  - 'composer.json'
  - '.claude/skills/bmad-testarch-atdd/resources/knowledge/test-quality.md'
  - '.claude/skills/bmad-testarch-atdd/resources/knowledge/confidence-gate.md'
---

# ATDD Checklist - Epic 1, Story 1.1: Package Skeleton, Filament Panel Connection & Migration

**Date:** 2026-09-13
**Author:** Binyo
**Primary Test Level:** Integration (Testbench feature tests) + Architecture tests (Pest Arch)

---

## Story Summary

Developer memasang Bazaar via Composer ke proyek Laravel/Filament klien yang sudah ada, menyambungkannya lewat 1 perintah Artisan (`bazaar:install`) tanpa membuat panel baru, menjalankan migrasi domain dasar (User & Access + Global Settings), dan mendapat panel admin yang langsung berfungsi dengan arch-test boundary enforcement dan heartbeat command.

**As a** Developer Tigaphonic
**I want** memasang Bazaar via Composer dan menghubungkannya ke Filament panel klien yang sudah ada lewat 1 perintah Artisan
**So that** saya mendapat backend admin Brand Store lengkap tanpa membangun ulang dari nol

---

## Acceptance Criteria

1. **AC1** — `composer require tigaphonic/bazaar` terpasang tanpa error, sesuai tech stack Architecture Spine (PHP ^8.4, Laravel ^12/^13, Filament ^5.8)
2. **AC2** — `php artisan bazaar:install` menyambungkan seluruh Resource/Page Filament Bazaar ke panel existing (tidak ada panel baru dibuat) dan menyediakan struktur domain-grouped `src/{Domain}/`
3. **AC3** — `php artisan migrate` berjalan bersih untuk skema yang sudah ada (domain User & Access + Global Settings di epic ini), kompatibel MySQL 8.x maupun PostgreSQL 15+ tanpa SQL spesifik-engine
4. **AC4** — setelah instalasi selesai: domain dasar (User & Access, Global Settings) tampil & berfungsi di panel; automated architecture test (`pestphp/pest-plugin-arch`) terpasang & lulus (tidak ada Model/Action domain diakses dari luar domain kecuali lewat Service-nya); `bazaar:status` Artisan command tersedia untuk cek heartbeat queue worker + scheduler (AD-17)

---

## Story Integration Metadata

- **Story ID:** `1.1`
- **Story Key:** `1-1-package-skeleton-filament-panel-connection-migration`
- **Story File:** `_artifacts/planning-artifacts/epics.md` (Story 1.1 section, baris 268–294) — belum ada file story individual terpisah (`create-story` belum dijalankan)
- **Checklist Path:** `_artifacts/test-artifacts/atdd-checklist-1-1-package-skeleton-filament-panel-connection-migration.md`
- **Generated Test Files:** lihat bagian "Red-Phase Test Scaffolds Created" di bawah

Story ini belum melalui `create-story` BMM — begitu file story individual dibuat, mirror path checklist & test files ini ke `Dev Notes`-nya.

---

## Stack Deviation Notice

Knowledge base skill ini (Playwright Utils, Pact.js) berorientasi JS/TS penuh dan tidak diterapkan di sini — Story 1.1 murni PHP (Composer install, Artisan command, migration, Filament panel registration), tanpa HTTP/browser surface. Atas persetujuan Binyo:

- **Tidak dipakai:** `playwright-utils-mandate`, `overview`, `api-request`, `auth-session`, `recurse`, dan seluruh fragment Pact.js
- **Dipakai (diterjemahkan ke idiom Pest PHP):** `test-quality.md` (deterministic, explicit assertions, no hidden asserts, ≤1000 baris, structure/naming per file) dan `confidence-gate.md` (confidence declaration sebelum scaffold ditulis)
- **Test level:** Testbench `TestCase` (extends `Orchestra\Testbench\TestCase`) + Pest v4 syntax, mengikuti konvensi `tests/Pest.php` yang sudah ada

---

## Generation Mode

**Mode: AI Generation** — `{detected_stack}` = `backend`, jadi tidak ada perekaman browser. AC dianalisis langsung dari `epics.md` §Story 1.1 + Architecture Spine AD-1/AD-5/AD-17/AD-23, dan test PHP/Pest ditulis langsung berdasarkan itu (setara "analisis OpenAPI/source code" untuk backend murni — di sini setara analisis kontrak Artisan command + skema migrasi).

---

## Test Strategy

Level selection per panduan backend: **Unit** untuk logika murni & manifest, **Integration** (Testbench) untuk command/migration/DB, **Architecture** (`pest-plugin-arch`) untuk boundary enforcement. Tidak ada E2E (tidak ada browser).

| ID | AC | Prioritas | Level | Skenario |
|---|---|---|---|---|
| TS-1 | AC2 | P0 | Integration | `bazaar:install` berjalan tanpa error |
| TS-2 | AC2 | P0 | Integration | `bazaar:install` mendaftarkan Resource/Page Bazaar ke panel existing, tidak membuat panel baru |
| TS-3 | AC3 | P0 | Integration | `artisan migrate` sukses untuk domain User & Access + Global Settings |
| TS-4 | AC4 | P0 | Architecture | Boundary test `pest-plugin-arch`: Model/Action domain tidak diakses lintas-domain kecuali via Service (AD-5/AD-6) |
| TS-5 | AC1 | P1 | Unit | `composer.json` mendeklarasikan `filament/filament` ^5.8 + constraint PHP/Laravel sesuai stack |
| TS-6 | AC2 | P1 | Unit | Struktur `src/{Domain}/` (Install, User, Settings minimal) tersedia sesuai Structural Seed |
| TS-7 | AC4 | P1 | Integration | `bazaar:status` berjalan & melaporkan heartbeat scheduler+queue (AD-17) |
| TS-8 | AC3 | P1 | Integration | Migration baru pakai ULID primary key (AD-18), bukan auto-increment |
| TS-9 | AC3 | P2 | Static | Tidak ada SQL spesifik-engine di file migration (AD-23) |
| TS-10 | AC4 | P2 | Integration | Nav group "User & Access" & "Global Settings" tampil di panel setelah install (UX-DR13) |

**Red phase confirmation:** semua 10 skenario dipastikan gagal sekarang — `bazaar:install`/`bazaar:status` belum ada (baru `BazaarCommand` generik dari skeleton), `filament/filament` belum jadi dependency, `database/migrations/` kosong, tidak ada `src/{Domain}/` selain skeleton generik, `tests/ArchTest.php` belum punya rule boundary domain.

### Confidence Gate

```
Confidence: 7
Rationale: AC dikutip langsung dari epics.md Story 1.1 (baris 268–294); mekanisme bazaar:install/bazaar:status
  dan lokasinya (src/Install/) dikutip dari Structural Seed ARCHITECTURE-SPINE.md; boundary rule TS-4 dikutip
  literal dari AD-5; heartbeat TS-7 dikutip literal dari AD-17 (scheduler_last_tick/queue_last_processed);
  composer.json TS-5 diverifikasi langsung (filament/filament memang belum ada di require).
Unknowns:
  - Nama tabel konkret untuk User/Role/Permission/Settings BELUM ditentukan di dokumen manapun (risiko
    collision dengan tabel host app karena AD-10: satu DB per install, tanpa tenant_id) — TS-3/TS-8 sengaja
    tidak hardcode nama tabel, hanya assert command exit + pola migration; keputusan penamaan jadi task
    eksplisit di Implementation Checklist sebelum GREEN phase.
  - Format output persis `bazaar:status` (AD-17 cuma menjelaskan mekanisme, bukan format tampilan) — TS-7
    diasersikan longgar (exit code + menyebut kata kunci scheduler/queue), bukan string persis.
```
Confidence 7 ≥ 7 → lanjut generate scaffold, asumsi di atas disurface eksplisit (bukan disembunyikan).

---

## Generation Note (deviation from JS/subagent orchestration)

Step 4/4C skill ini dirancang untuk dual-subagent (Worker A: API, Worker B: E2E) yang menulis JSON ke `/tmp` untuk output TypeScript/Playwright. Karena `{detected_stack}` = `backend` (no E2E) dan proyek ini PHP/Pest (bukan JS), scaffold ditulis langsung (sequential, satu track) tanpa subagent — sesuai deviasi yang sudah disetujui di Step 1.

## Red-Phase Test Scaffolds Created (14 skipped + 1 self-activating architecture guard)

### Unit Tests (3 tests)

**File:** `tests/Unit/PackageManifestTest.php` (16 baris)

- ✅ **Test:** `it declares filament/filament ^5.8 as a dependency`
  - **Status:** SKIPPED (red-phase) — `filament/filament` belum ada di `composer.json`
  - **Verifies:** AC1
- ✅ **Test:** `it narrows illuminate/contracts to Laravel ^12/^13 per the Architecture Spine stack`
  - **Status:** SKIPPED — constraint saat ini masih `^11.0||^12.0||^13.0`
  - **Verifies:** AC1

**File:** `tests/Unit/PackageStructureTest.php` (7 baris)

- ✅ **Test:** `it ships the domain-grouped src/{Domain} structure this story requires`
  - **Status:** SKIPPED — hanya skeleton generik yang ada
  - **Verifies:** AC2 (Structural Seed: `src/Install`, `src/User`, `src/Settings`)

### Integration Tests — Testbench (10 tests)

**File:** `tests/Feature/Install/BazaarInstallCommandTest.php` (24 baris)

- ✅ `it runs bazaar:install without error` — SKIPPED, command belum ada — **Verifies:** AC2
- ✅ `it registers at least one Bazaar Filament resource on the existing panel` — SKIPPED — **Verifies:** AC2
- ✅ `it does not register a new Filament panel` — SKIPPED — **Verifies:** AC2

**File:** `tests/Feature/Install/DomainMigrationTest.php` (30 baris)

- ✅ `it runs php artisan migrate cleanly for the User & Access and Global Settings domains` — SKIPPED, `database/migrations/` masih cuma `.stub` — **Verifies:** AC3
- ✅ `it uses ULID primary keys on every Bazaar migration, never auto-increment` — SKIPPED — **Verifies:** AC3 / AD-18
- ✅ `it contains no MySQL- or PostgreSQL-specific SQL in migration files` — SKIPPED — **Verifies:** AC3 / AD-23

**File:** `tests/Feature/Install/BazaarStatusCommandTest.php` (16 baris)

- ✅ `it runs bazaar:status without error` — SKIPPED, command belum ada — **Verifies:** AC4 / AD-17
- ✅ `it reports scheduler heartbeat staleness` — SKIPPED — **Verifies:** AC4 / AD-17
- ✅ `it reports queue worker heartbeat staleness` — SKIPPED — **Verifies:** AC4 / AD-17

**File:** `tests/Feature/Install/PanelNavigationTest.php` (11 baris)

- ✅ `it shows the User & Access and Global Settings navigation groups after install` — SKIPPED — **Verifies:** AC4 / UX-DR13

### Architecture Test — self-activating, NOT skipped (AC4)

**File:** `tests/ArchDomainBoundaryTest.php` (26 baris)

- ✅ `it has at least one domain-grouped src/{Domain} directory to enforce AD-5/AD-6 boundaries against` — SKIPPED for now (tidak ada domain sama sekali)
- 🟢 **Dynamic `arch()` rules** (0 terdaftar saat ini, bukan bug): loop atas `glob(src/*)` — begitu domain `Install`/`User`/`Settings` dibuat di story ini, rule `{Domain}: Models are only used within {Domain}'s own Services/Actions` dan `{Domain}: Actions are only used within {Domain}'s own Services` otomatis terdaftar dan aktif tanpa perlu edit file ini lagi. Ini BUKAN scaffold yang perlu "diaktifkan" manual — nilainya justru berjalan sejak domain pertama muncul.

---

## Data Factories / Fixtures / Mocks

**Tidak diperlukan untuk story ini** — Story 1.1 murni instalasi paket, migrasi, dan Artisan command; tidak ada entity CRUD yang butuh factory data, tidak ada UI komponen yang butuh fixture, dan tidak ada layanan eksternal yang butuh mock (Midtrans/RajaOngkir baru masuk Epic 4/5).

---

## Implementation Checklist

### Prasyarat lingkungan (sebelum test manapun bisa diaktifkan)

- [ ] Tambahkan `filament/filament: ^5.8` ke `composer.json` (`require`)
- [ ] Set up fixture Testbench (`workbench/` belum ada) dengan 1 Filament panel kosong yang sudah terpasang — mensimulasikan "proyek klien" di AC1's Given clause
- [ ] **Putuskan konvensi penamaan tabel** untuk domain User & Access + Global Settings (Unknown dari Step 3 — AD-10: satu DB per install tanpa `tenant_id`, jadi ada risiko collision nama tabel dengan host app; belum ada keputusan di planning docs manapun)

### Test: `PackageManifestTest` (AC1)

- [ ] Tambahkan `filament/filament` ke `require`
- [ ] Ubah `illuminate/contracts` jadi `^12.0||^13.0` (drop `^11.0`)
- [ ] Jalankan: `vendor/bin/pest --filter=PackageManifestTest`
- [ ] ✅ Test lulus (green)

### Test: `PackageStructureTest` (AC2)

- [ ] Buat direktori `src/Install/`, `src/User/`, `src/Settings/` sesuai Structural Seed
- [ ] Jalankan: `vendor/bin/pest --filter=PackageStructureTest`

### Test: `BazaarInstallCommandTest` (AC2)

- [ ] Buat `src/Install/Commands/BazaarInstallCommand.php` (`bazaar:install`)
- [ ] Daftarkan command di `BazaarServiceProvider`
- [ ] Implementasikan penyambungan Resource/Page ke panel existing (bukan panel baru) — AD-2
- [ ] Jalankan: `vendor/bin/pest --filter=BazaarInstallCommandTest`

### Test: `DomainMigrationTest` (AC3)

- [ ] Tulis migration nyata untuk domain User & Access (User, Role, Permission tables via `spatie/laravel-permission`) + Global Settings
- [ ] Semua PK pakai `->ulid('id')`, bukan `->id()` (AD-18)
- [ ] Hindari fungsi JSON spesifik-MySQL/Postgres (AD-23)
- [ ] Hapus `database/migrations/create_bazaar_table.php.stub` (placeholder skeleton)
- [ ] Jalankan: `vendor/bin/pest --filter=DomainMigrationTest`

### Test: `BazaarStatusCommandTest` (AC4)

- [ ] Buat `src/Install/Commands/BazaarStatusCommand.php` (`bazaar:status`) sesuai AD-17 (`scheduler_last_tick`, `queue_last_processed`)
- [ ] Jalankan: `vendor/bin/pest --filter=BazaarStatusCommandTest`

### Test: `PanelNavigationTest` (AC4)

- [ ] Daftarkan navigation group "User & Access" dan "Global Settings" (UX-DR13)
- [ ] Jalankan: `vendor/bin/pest --filter=PanelNavigationTest`

### Test: `ArchDomainBoundaryTest` (AC4 / AD-5 / AD-6)

- [ ] Tidak ada task tambahan — begitu domain dibuat (task-task di atas), rule aktif otomatis
- [ ] Jalankan: `vendor/bin/pest --filter=ArchDomainBoundaryTest`

---

## Running Tests

```bash
# Jalankan semua test story ini
vendor/bin/pest tests/Unit/PackageManifestTest.php tests/Unit/PackageStructureTest.php \
  tests/Feature/Install tests/ArchDomainBoundaryTest.php

# Jalankan 1 file spesifik
vendor/bin/pest --filter=BazaarInstallCommandTest

# Jalankan seluruh suite package
composer test

# Dengan coverage
composer test-coverage
```

---

## Red-Green-Refactor Workflow

### RED Phase (Complete) ✅

- ✅ 14 test scaffold ditulis dengan `->skip('alasan AC/AD')` (idiom Pest, setara `test.skip()`)
- ✅ 1 architecture guard (`ArchDomainBoundaryTest`) ditulis aktif (bukan skip) — nilainya justru berjalan sejak sekarang, akan otomatis menegakkan AD-5/AD-6 begitu domain pertama dibuat
- ✅ Tidak ada fixture/factory/mock diperlukan
- ✅ Implementation Checklist dibuat per file test
- ✅ Verifikasi terjalankan nyata (lihat Test Execution Evidence)

### GREEN Phase (Dev — Story 1.1 implementation)

1. Pilih 1 test dari Implementation Checklist (mulai dari prasyarat lingkungan)
2. Hapus `->skip(...)` untuk test itu, konfirmasi dulu bahwa test GAGAL (bukan error PHP)
3. Implementasikan kode minimal agar test itu lulus
4. Jalankan test, pastikan hijau
5. Centang task, lanjut ke test berikutnya

### REFACTOR Phase

Setelah semua test AC1–AC4 hijau: review kualitas kode, jalankan `vendor/bin/pint` + `vendor/bin/phpstan analyse`, pastikan `composer test` tetap hijau.

---

## Next Steps

1. Story 1.1 belum punya file story individual — begitu `create-story` dijalankan, mirror path checklist ini + 7 file test ke `Dev Notes`-nya
2. Selesaikan prasyarat lingkungan (Filament dependency, workbench fixture, keputusan penamaan tabel) sebelum mengaktifkan test manapun
3. Aktifkan 1 scaffold per task, red → green
4. Setelah semua lulus, refactor + `composer test` hijau
5. Update `sprint-status.yaml`: `1-1-package-skeleton-filament-panel-connection-migration` → status sesuai (`in-progress` lalu `done`)

---

## Knowledge Base References Applied

- **test-quality.md** — Definition of Done generik (deterministic, explicit assertions, no hidden asserts, ≤1000 baris, structure/naming) diterjemahkan ke idiom Pest PHP
- **confidence-gate.md** — confidence declaration (7/10) sebelum menulis scaffold, dengan Unknown (nama tabel, format `bazaar:status`) disurface eksplisit, bukan ditebak
- **Tidak dipakai:** seluruh fragment Playwright Utils / Pact.js (JS/TS-only, tidak relevan untuk PHP/Pest tanpa HTTP/browser surface) — lihat "Stack Deviation Notice" di atas

---

## Test Execution Evidence

### Initial Scaffold Verification (RED-phase inert-check)

**Command:** `vendor/bin/pest`

**Results:**

```
PASS  Tests\ArchTest
✓ it will not use debugging functions

WARN  Tests\Feature\Install\BazaarStatusCommandTest
- it reports scheduler heartbeat staleness → AC4 / AD-17 — scheduler_last_tick heartbeat is not implemented yet (Story 1.1)
- it runs bazaar:status without error → AC4 / AD-17 — bazaar:status command does not exist yet (Story 1.1)
- it reports queue worker heartbeat staleness → AC4 / AD-17 — queue_last_processed heartbeat is not implemented yet (Story 1.1)

WARN  Tests\Feature\Install\BazaarInstallCommandTest
- it registers at least one Bazaar Filament resource on the existing panel → AC2 — bazaar:install and the Filament resources it registers do not exist yet (Story 1.1)
- it does not register a new Filament panel → AC2 — bazaar:install does not exist yet, and no fixture panel is registered in TestCase yet (Story 1.1)
- it runs bazaar:install without error → AC2 — bazaar:install command does not exist yet (Story 1.1)

WARN  Tests\ArchDomainBoundaryTest
- it has at least one domain-grouped src/{Domain} directory to enforce AD-5/AD-6 boundaries against → AC4 / AD-5 — only the generic package skeleton exists so far, no domain directories yet (Story 1.1)

WARN  Tests\Feature\Install\DomainMigrationTest
- it runs php artisan migrate cleanly for the User & Access and Global Settings domains → AC3 — no domain migrations exist yet beyond the generic skeleton placeholder (Story 1.1)
- it contains no MySQL- or PostgreSQL-specific SQL in migration files → AC3 / AD-23 — verified once real domain migrations exist (Story 1.1)
- it uses ULID primary keys on every Bazaar migration, never auto-increment → AC3 / AD-18 — migrations directory still only has the skeleton .stub placeholder (Story 1.1)

WARN  Tests\Unit\PackageStructureTest
- it ships the domain-grouped src/{Domain} structure this story requires → AC2 — only the generic package skeleton exists so far (Story 1.1)

WARN  Tests\Unit\PackageManifestTest
- it declares filament/filament ^5.8 as a dependency → AC1 — filament/filament is not declared in composer.json yet (Story 1.1)
- it narrows illuminate/contracts to Laravel ^12/^13 per the Architecture Spine stack → AC1 — current constraint still allows ^11.0 (Story 1.1)

WARN  Tests\Feature\Install\PanelNavigationTest
- it shows the User & Access and Global Settings navigation groups after install → AC4 / UX-DR13 — panel navigation groups are not registered yet (Story 1.1)

PASS  Tests\ExampleTest
✓ it can test

Tests: 14 skipped, 2 passed (4 assertions)
Exit code: 0
```

**Summary:**

- Total tests: 16 (2 pra-eksisting + 14 scaffold baru)
- Skipped: 14 (semua scaffold baru, red-phase, sesuai desain)
- Pra-eksisting: 2 passed (tidak terganggu)
- Status: ✅ Red-phase scaffolds terverifikasi inert — tidak ada fatal error, tidak ada regresi, exit code 0 (aman untuk CI)

---

## Notes

- File story individual (`create-story`) belum dijalankan — checklist ini merujuk `epics.md` langsung. Ketika file story dibuat, mirror artifact paths ke `Dev Notes`-nya.
- **Unknown terbuka (perlu keputusan sebelum GREEN):** nama tabel konkret domain User & Access + Global Settings (risiko collision karena AD-10 no-tenant-id/satu-DB); format output persis `bazaar:status`.
- `ArchDomainBoundaryTest` sengaja dibuat aktif (bukan skip) karena nilainya adalah pencegahan regresi sejak commit pertama yang menambah domain — bukan sesuatu yang "diaktifkan" satu-per-satu seperti scaffold lain.

---

**Generated by John (Product Manager, embodying Master Test Architect role for `bmad-testarch-atdd`)** — 2026-09-13
