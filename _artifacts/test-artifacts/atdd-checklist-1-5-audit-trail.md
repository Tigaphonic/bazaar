---
stepsCompleted: ['step-01-preflight-and-context', 'step-02-generation-mode', 'step-03-test-strategy', 'step-04-generate-tests', 'step-04c-aggregate', 'step-05-validate-and-complete']
lastStep: 'step-05-validate-and-complete'
lastSaved: '2026-09-17'
workflowType: 'testarch-atdd'
storyId: '1.5'
storyKey: '1-5-audit-trail'
storyFile: '_artifacts/planning-artifacts/epics.md'
atddChecklistPath: '_artifacts/test-artifacts/atdd-checklist-1-5-audit-trail.md'
generatedTestFiles:
  - 'tests/Feature/AuditTrail/AuditTrailCaptureTest.php'
  - 'tests/Feature/AuditTrail/AuditTrailImmutabilityTest.php'
  - 'tests/Feature/AuditTrail/AuditTrailResource/ListAuditTrailTest.php'
inputDocuments:
  - '_artifacts/planning-artifacts/epics.md (Story 1.5, baris 360–379)'
  - '_artifacts/implementation-artifacts/epic-1-context.md'
  - '_artifacts/planning-artifacts/architecture/architecture-Tigaphonic/bazaar-2026-09-12/ARCHITECTURE-SPINE.md (AD-5, AD-18, NFR4)'
  - '_artifacts/implementation-artifacts/sprint-status.yaml'
  - '_artifacts/test-artifacts/atdd-checklist-1-4-manage-user.md (pola referensi)'
  - 'src/User/Services/RoleService.php'
  - 'src/User/Services/UserService.php'
  - 'tests/Feature/User/RoleServiceTest.php'
  - 'tests/Feature/User/UserServiceTest.php'
  - 'tests/Feature/User/UserResource/ListUsersTest.php'
  - 'tests/TestCase.php'
  - 'tests/Pest.php'
  - 'playwright.config.ts'
  - '_bmad/tea/config.yaml'
  - '.agents/skills/bmad-testarch-atdd/resources/knowledge/test-quality.md'
  - '.agents/skills/bmad-testarch-atdd/resources/knowledge/confidence-gate.md'
  - '.agents/skills/bmad-testarch-atdd/resources/knowledge/test-levels-framework.md'
  - '.agents/skills/bmad-testarch-atdd/resources/knowledge/test-priorities-matrix.md'
  - '.agents/skills/bmad-testarch-atdd/resources/knowledge/data-factories.md'
---

# ATDD Checklist — Epic 1, Story 1.5: Audit Trail

**Date:** 2026-09-17  
**Author:** Binyo  
**Primary Test Level:** Integration (Testbench + Pest + Filament/Livewire testing) — nol Playwright file baru

---

## Story Summary

Staff dapat melihat log otomatis semua aksi mutasi di seluruh sistem, sehingga bisa menyelidiki perubahan data dan mempertanggungjawabkan setiap aksi ke User yang melakukannya.

**As a** Staff  
**I want** melihat log otomatis semua aksi mutasi di seluruh sistem  
**So that** saya bisa menyelidiki perubahan data dan mempertanggungjawabkan setiap aksi ke User yang melakukannya

---

## Acceptance Criteria

1. **AC1** — Given aksi create/update/delete terjadi di domain manapun. When aksi tsb tersimpan. Then Audit Trail **otomatis** mencatat entri: siapa (User), apa (aksi & entity), kapan (timestamp), dan nilai before-after — tanpa perlu instrumentasi manual per domain.

2. **AC2** — Given Staff membuka Audit Trail. When Staff mencoba mengedit atau menghapus sebuah entri. Then tidak ada kontrol edit/delete tersedia di UI manapun — **read-only mutlak** (NFR4).

3. **AC3** — Given Staff ingin menyelidiki insiden tertentu. When Staff memfilter Audit Trail per User, per entity, atau rentang waktu. Then hasil terfilter sesuai kriteria ditampilkan dalam Data Table dengan pagination standar.

---

## Story Integration Metadata

- **Story ID:** `1.5`
- **Story Key:** `1-5-audit-trail`
- **Story File:** `_artifacts/planning-artifacts/epics.md` (Story 1.5 section, baris 360–379)
- **Checklist Path:** `_artifacts/test-artifacts/atdd-checklist-1-5-audit-trail.md`
- **Generated Test Files:** lihat bagian "Red-Phase Test Scaffolds Created" di bawah

Story ini belum melalui `create-story` BMM — begitu file story individual dibuat, mirror path checklist & test files ini ke `Dev Notes`-nya.

---

## Stack Deviation Notice

Sama seperti Story 1.1/1.2/1.3/1.4: knowledge base skill ini berorientasi JS/TS + browser-first; sebagian besar tidak relevan di sini.

- **Tidak dipakai:** Playwright Utils mandate dan seluruh fragment turunannya — `@seontechnologies/playwright-utils` belum jadi dependency nyata; gate relevansinya no-op. Tidak ada spec Playwright baru dibuat.
- **Tidak dipakai:** Pact.js — tidak ada contract boundary microservices.
- **Dipakai (diterjemahkan ke idiom Pest PHP):** `test-quality.md`, `confidence-gate.md`, `test-levels-framework.md`, `test-priorities-matrix.md`, `data-factories.md`
- **Test level:** Testbench `TestCase` + Pest v4, plus Filament/Livewire testing helpers (`Livewire::test()`) — pola identik Story 1.3/1.4.

---

## Generation Mode

**Mode: AI Generation (Sequential)** — AC standar (auto-capture via LogsActivity trait, read-only Resource, filter + pagination). Tidak ada interaksi UI yang butuh verifikasi browser nyata. Semua AC dapat diverifikasi lewat `Livewire::test()` + Pest + Testbench. **Nol file Playwright baru untuk story ini** (deviasi eksplisit, dicatat di sini).

---

## Test Strategy

### Confidence Gate

**Confidence: 8**  
**Rationale:** `spatie/laravel-activitylog` disebut eksplisit di `epic-1-context.md` sebagai teknologi yang dipakai. `AuditTrail` adalah subclass `Activity` (eksplisit di epic-1-context.md). Pola Filament Resource testing sudah established di `tests/Feature/User/RoleResource/` dan `tests/Feature/User/UserResource/`. AD-5 dan NFR4 dikutip langsung dari Architecture Spine.

**Unknowns (diputuskan sebelum generate):**
- ✅ **Directory test:** `tests/Feature/AuditTrail/` terpisah (bukan di bawah `User/`) — keputusan Binyo, dicatat di sesi ini.
- ✅ **Scope auto-capture:** cukup dari mutasi Story 1.3 (Role) dan 1.4 (User) untuk Story 1.5 ini.
- ✅ **Model AuditTrail:** `spatie/laravel-activitylog` — subclass `Spatie\Activitylog\Models\Activity`.

### Scenario Coverage

| ID | Scenario | AC | Level | Priority | File |
|---|---|---|---|---|---|
| 1.5-INT-001 | Auto-capture create Role → Activity entry, event=created, old=null | AC1 | Integration | P0 | AuditTrailCaptureTest |
| 1.5-INT-002 | Auto-capture create User → Activity entry, event=created | AC1 | Integration | P0 | AuditTrailCaptureTest |
| 1.5-INT-003 | event=created + correct subject_type saat Role dibuat | AC1 | Integration | P0 | AuditTrailCaptureTest |
| 1.5-INT-004 | old=null untuk entri pertama (create) — tidak ada prior state | AC1 | Integration | P1 | AuditTrailCaptureTest |
| 1.5-INT-005 | Auto-capture update Role → before/after, old holds previous name | AC1 | Integration | P0 | AuditTrailCaptureTest |
| 1.5-INT-006 | Auto-capture update User → before/after values | AC1 | Integration | P0 | AuditTrailCaptureTest |
| 1.5-INT-007 | Hanya changed attributes yang direkam dalam diff, bukan full model | AC1 | Integration | P1 | AuditTrailCaptureTest |
| 1.5-INT-008 | Auto-capture delete Role → event=deleted | AC1 | Integration | P0 | AuditTrailCaptureTest |
| 1.5-INT-009 | Causer_id + causer_type direkam saat Staff ter-autentikasi | AC1 | Integration | P1 | AuditTrailCaptureTest |
| 1.5-INT-010 | Causer direkam via lifecycle binding (bukan manual call) | AC1 | Integration | P1 | AuditTrailCaptureTest |
| 1.5-INT-011 | RoleService tidak mengandung `activity()` call langsung (structural) | AC1 | Integration | P1 | AuditTrailCaptureTest |
| 1.5-INT-012 | UserService tidak mengandung `activity()` call langsung (structural) | AC1 | Integration | P1 | AuditTrailCaptureTest |
| 1.5-INT-013 | AuditTrailResource: tidak ada DeleteAction di tabel | AC2 | Integration | P0 | AuditTrailImmutabilityTest |
| 1.5-INT-014 | AuditTrailResource: tidak ada bulk DeleteAction | AC2 | Integration | P0 | AuditTrailImmutabilityTest |
| 1.5-INT-015 | AuditTrailResource: tidak ada EditAction di tabel | AC2 | Integration | P0 | AuditTrailImmutabilityTest |
| 1.5-INT-016 | AuditTrailResource: tidak ada `edit` route di getPages() | AC2 | Integration | P0 | AuditTrailImmutabilityTest |
| 1.5-INT-017 | AuditTrailResource: tidak ada `create` route di getPages() | AC2 | Integration | P0 | AuditTrailImmutabilityTest |
| 1.5-INT-018 | AuditTrailService: tidak ada delete()/update()/destroy() method (NFR4) | AC2 | Integration | P0 | AuditTrailImmutabilityTest |
| 1.5-INT-019 | Data Table kolom wajib: causer, event, subject_type, subject_id, created_at, properties | AC1+AC3 | Integration | P1 | ListAuditTrailTest |
| 1.5-INT-020 | Filter causer_id: hanya entri dari User tsb yang tampil | AC3 | Integration | P1 | ListAuditTrailTest |
| 1.5-INT-021 | Filter causer_id off: semua entri tampil | AC3 | Integration | P2 | ListAuditTrailTest |
| 1.5-INT-022 | Filter subject_type: hanya entri entity tsb yang tampil | AC3 | Integration | P1 | ListAuditTrailTest |
| 1.5-INT-023 | Filter subject_type: entri entity lain tidak tampil | AC3 | Integration | P1 | ListAuditTrailTest |
| 1.5-INT-024 | Filter date from/until: hanya entri dalam range | AC3 | Integration | P1 | ListAuditTrailTest |
| 1.5-INT-025 | Filter date from/until: entri lebih lama dari boundary dikecualikan | AC3 | Integration | P1 | ListAuditTrailTest |
| 1.5-INT-026 | Pagination: first page worth saja yang tampil, bukan semua record | AC3 | Integration | P1 | ListAuditTrailTest |
| 1.5-INT-027 | Pagination: count visible rows = page size, bukan total count | AC3 | Integration | P1 | ListAuditTrailTest |
| 1.5-INT-028 | AuditTrailService::list() returns Activity collection | AC3 | Integration | P1 | ListAuditTrailTest |
| 1.5-INT-029 | AuditTrailService::list(causerId:) filter | AC3 | Integration | P1 | ListAuditTrailTest |
| 1.5-INT-030 | AuditTrailService::list(subjectType:) filter | AC3 | Integration | P1 | ListAuditTrailTest |
| 1.5-INT-031 | AuditTrailService::list(from:, until:) date filter | AC3 | Integration | P1 | ListAuditTrailTest |

**Total: 31 scenarios, 31 skipped (TDD RED PHASE)**

---

## TDD Red Phase — ✅ VALID

```
🔴 TDD RED PHASE: Test Scaffolds Generated

Tests:  31 skipped (0 assertions)
Regresi: NONE (104 existing tests still pass)
Duration: ~12s (full suite)
```

---

## Red-Phase Test Scaffolds Created

| File | Tests | Scope |
|---|---|---|
| [`tests/Feature/AuditTrail/AuditTrailCaptureTest.php`](file:///Users/mastin/devphp-valet/package-bazaar/tests/Feature/AuditTrail/AuditTrailCaptureTest.php) | 12 | AC1: auto-capture create/update/delete, causer, no manual instrumentation |
| [`tests/Feature/AuditTrail/AuditTrailImmutabilityTest.php`](file:///Users/mastin/devphp-valet/package-bazaar/tests/Feature/AuditTrail/AuditTrailImmutabilityTest.php) | 6 | AC2: read-only mutlak (NFR4), no edit/delete UI, no mutation methods |
| [`tests/Feature/AuditTrail/AuditTrailResource/ListAuditTrailTest.php`](file:///Users/mastin/devphp-valet/package-bazaar/tests/Feature/AuditTrail/AuditTrailResource/ListAuditTrailTest.php) | 13 | AC3: filter causer/entity/date, pagination, AuditTrailService::list() |

---

## Implementation Guidance (untuk Developer Story 1.5)

### Komponen yang perlu dibuat

**Model & Migration:**
- `src/User/Models/AuditTrail.php` — extends `Spatie\Activitylog\Models\Activity`, log_name `'bazaar'`
- Tambah `LogsActivity` trait ke `Spatie\Permission\Models\Role` — harus via extension/override karena ini package pihak ketiga; opsi: hook via `Activity::saving()` Observer, atau extend Role model lokal.
- Tambah `LogsActivity` trait ke `Workbench\App\Models\User` (workbench), dan ke model host via ServiceProvider config.

**Service:**
- `src/User/Services/AuditTrailService.php` — `list(causerId: null, subjectType: null, from: null, until: null)` sebagai satu-satunya public API (AD-5 read-only gate). Tidak boleh ada `delete()`, `update()`, dst.

**Filament Resource:**
- `src/User/Filament/Resources/AuditTrailResource.php` — `getPages()` hanya `['index' => ListAuditTrail::route('/')]`; tidak ada `create`/`edit` route.
- `src/User/Filament/Resources/AuditTrailResource/Pages/ListAuditTrail.php`
- Filters: `SelectFilter::make('causer_id')`, `SelectFilter::make('subject_type')`, `Filter::make('created_at')` dengan `from`/`until`.
- Tabel: kolom `causer`, `event`, `subject_type`, `subject_id`, `created_at`, `properties` (before/after diff).

**Aktifkan unskip per task:**
- Remove `->skip(...)` dari satu file / satu scenario group sebelum mengimplementasikan bagian tersebut.
- Verifikasi test fail dulu (RED), baru implementasi, lalu test hijau (GREEN).

### Pitfall yang sudah diketahui

- **`activity()` call manual di Service** — Tidak boleh. Capture harus via `LogsActivity` trait di Model. Test `AuditTrailCaptureTest` yang memeriksa source RoleService/UserService akan merah jika ini dilanggar.
- **Spatie Role bukan model Bazaar** — Tidak bisa langsung `use LogsActivity` pada `Spatie\Permission\Models\Role`. Solusi: extend Role locally atau gunakan Observer/model events global dari ServiceProvider.
- **`AuditTrailService` punya mutation method** — Langsung merahkan `AuditTrailImmutabilityTest`. NFR4 bukan cuma UI — ini juga domain boundary.

---

## Unknowns (Confidence Gate — untuk developer saat unskip)

| # | Unknown | Impact | Cara resolve |
|---|---|---|---|
| 1 | Apakah `LogsActivity` trait bisa dipasang ke Spatie's `Role` model tanpa fork? | High — menentukan arsitektur auto-capture | Periksa activitylog Observer alternative atau local extend Role |
| 2 | Apakah ada existing `activity()` log dari Bazaar yang perlu diconvert ke AuditTrail subclass? | Medium | Grep `activity()` di seluruh `src/` sebelum implementasi |
| 3 | Apakah Filament 5.8 support `causer` sebagai `BelongsTo` relationship column di tabel? | Low | Baca Filament docs untuk morphTo column pattern |

---

## Next Steps

1. Handoff ke **`bmad-build` / dev-story** — Story 1.5 siap untuk implementasi.
2. Saat implementasi: unskip per file/task, verifikasi RED dulu, baru implementasi → GREEN.
3. Setelah Story 1.5 selesai: jalankan `bmad-retrospective` untuk menutup story.
4. Domain berikutnya (Epic 3 Catalog, Epic 4 Order, dst.) yang menambah model baru — tambahkan `LogsActivity` ke model tersebut; file test AuditTrail ini akan tetap merah sampai model baru aktif.
