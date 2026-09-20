---
stepsCompleted:
  - step-01-preflight-and-context
  - step-02-generation-mode
  - step-03-test-strategy
  - step-04-generate-tests
  - step-04c-aggregate
  - step-05-validate-and-complete
lastStep: step-05-validate-and-complete
lastSaved: '2026-09-20'
storyId: '1.9'
storyKey: 1-9-api-layer-integration-headless
storyFile: _artifacts/planning-artifacts/epics.md
atddChecklistPath: _artifacts/test-artifacts/atdd-checklist-1-9-api-layer-integration-headless.md
generatedTestFiles:
  - tests/Feature/Install/ApiLayerIntegrationTest.php
inputDocuments:
  - _artifacts/planning-artifacts/epics.md
  - _artifacts/implementation-artifacts/epic-1-context.md
  - _bmad/tea/config.yaml
  - _artifacts/planning-artifacts/architecture/architecture-Tigaphonic/bazaar-2026-09-12/ARCHITECTURE-SPINE.md
---

# ATDD Checklist: Story 1.9 — API Layer Integration (Headless)

## TDD Red Phase Status

| Item | Status |
|------|--------|
| Test scaffold dibuat | ✅ |
| Tests fail untuk alasan yang benar | ✅ 14 skipped (implementasi belum ada) |
| Tidak ada placeholder assertion | ✅ |
| Semua skip menggunakan `->skip()` (bukan `skip()` global) | ✅ |

> **Catatan:** 14 tests semuanya skip karena `config/bazaar.php` belum punya key `bazaar.api`,
> route API belum diregistrasikan di `BazaarServiceProvider`, dan webhook route belum ada.
> Ini adalah status red-phase yang benar untuk Story 1.9.

## Generated Test File

- [`tests/Feature/Install/ApiLayerIntegrationTest.php`](../../../tests/Feature/Install/ApiLayerIntegrationTest.php)

## Stack Detection

- **Detected Stack:** `fullstack`
- **Generation Mode:** AI Generation (sequential)
- **E2E Tests:** N/A — Story 1.9 tidak punya UI surface (backend-only contract)
- **Pact CDC:** N/A — bukan inter-service; portal headless memanggil API Bazaar via HTTP, bukan sebaliknya

## Acceptance Criteria Coverage

| # | AC | Test ID(s) | Priority | Status |
|---|---|---|---|---|
| AC1 | API Layer off by default — tidak ada route API terdaftar | API-001, API-002, API-003 | P0 | 🔴 Scaffold |
| AC2 | Saat diaktifkan: Sanctum auth + API Resource response | API-004, API-005, API-006, API-007 | P0–P1 | 🔴 Scaffold |
| AC2 | Route versioned + toggle off kembali menghapus routes | API-008, API-009 | P1 | 🔴 Scaffold |
| AC3 (AD-11) | Webhook Payment selalu terdaftar (flag apapun) | API-010, API-012 | P0 | 🔴 Scaffold |
| AC3 (AD-11) | Webhook Shipping selalu terdaftar (flag apapun) | API-011, API-012 | P0 | 🔴 Scaffold |
| Struktural | API Layer delegasi ke Service (tidak re-implement logic) | API-013 | P1 | 🔴 Scaffold |
| Dokumentasi | README mendokumentasikan aktivasi API Layer + Sanctum | API-014 | P2 | 🔴 Scaffold |

## Test Strategy

### Kenapa Pest Feature Test, bukan TypeScript/Playwright?

Story 1.9 adalah **backend-only contract** — tidak ada UI surface, tidak ada browser interaction.
Sama dengan Story 1.8. Playwright browser tests tidak relevan.
Pest Feature Tests menggunakan Testbench (in-process Laravel kernel) untuk membuktikan:
- Flag respects route registration
- Sanctum middleware applied
- Webhook routes always-on (AD-11)

### Level Test per Skenario

| Test ID | Skenario | Level | Prioritas |
|---------|----------|-------|-----------|
| API-001–003 | Default off / missing config key | Integration | P0 |
| API-004 | Routes terdaftar saat `api.enabled = true` | Integration | P0 |
| API-005 | Unauthenticated → 401 | Integration | P0 |
| API-006 | Token Sanctum per-service → success | Integration | P0 |
| API-007 | Response shape = API Resource | Integration | P1 |
| API-008 | Route prefix `/bazaar/api/v1` | Integration | P1 |
| API-009 | Disable → routes hilang | Integration | P1 |
| API-010–012 | Webhook always-on (AD-11) | Integration | P0 |
| API-013 | Controller delegasi ke Service | Integration | P1 |
| API-014 | Dokumentasi README | Integration (proxy) | P2 |

## Implementation Guidance

Story 1.9 perlu diimplementasikan dalam urutan ini:

### 1. Config key `bazaar.api`
Tambahkan ke `config/bazaar.php`:
```php
'api' => [
    'enabled' => false,  // opt-in; toggled by Developer per instalasi
],
```
→ Un-skip: **API-001, API-003**

### 2. Webhook routes (tanpa kondisi — AD-11)
Di `BazaarServiceProvider::packageBooted()`:
```php
$this->loadRoutesFrom(__DIR__.'/../routes/webhooks.php');
```
File `routes/webhooks.php` mendaftarkan endpoint Midtrans + RajaOngkir tanpa `auth:sanctum` guard.
→ Un-skip: **API-010, API-011, API-012**

### 3. Conditional API route registration
Di `BazaarServiceProvider::packageBooted()`:
```php
if (config('bazaar.api.enabled', false)) {
    $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
}
```
→ Un-skip: **API-002, API-004, API-009**

### 4. Sanctum middleware pada `routes/api.php`
```php
Route::prefix('bazaar/api/v1')
    ->middleware(['api', 'auth:sanctum'])
    ->group(function () {
        Route::get('/status', [ApiStatusController::class, 'index']);
        // Epic 3+ tambah di sini
    });
```
→ Un-skip: **API-005, API-008**

### 5. Sanctum token per-service + ApiStatusController
- Controller delegasi ke `SettingsService` atau `BazaarStatusService`
- Response via `ApiResource` (Laravel API Resource class)
- Tidak ada business logic di controller
→ Un-skip: **API-006, API-007, API-013**

### 6. Dokumentasi README
Tambahkan section "API Layer (Headless)" dengan contoh:
- Aktifkan `bazaar.api.enabled = true`
- Buat Sanctum token untuk service: `$user->createToken('portal-service')`
- Contoh request dengan Bearer token
→ Un-skip: **API-014**

## Risks & Assumptions

| # | Risiko / Asumsi | Mitigasi |
|---|---|---|
| R1 | Route caching di production mempengaruhi flag toggle | Dokumentasikan bahwa `php artisan route:cache` harus dijalankan ulang saat flag berubah |
| R2 | Sanctum perlu `laravel/sanctum` di `composer.json` (sudah ada di Architecture Spine) | Verifikasi saat un-skip API-005 |
| R3 | Endpoint path `/bazaar/api/v1` bisa konflik dengan route host app klien | Pertimbangkan konfigurabilitas prefix via `config('bazaar.api.prefix')` |
| A1 | "Token per-service" = satu Sanctum PAT untuk seluruh portal, bukan per-Customer | Konsisten dengan AC2: "token per-service, bukan per-Customer individual" |
| A2 | Webhook route domain (Payment/Shipping) dikerjakan Epic 4/5 bukan Epic 1 | Story 1.9 hanya membuktikan `always-on` guarantee via route existence — route stub boleh return 200 untuk saat ini |

## Next Steps

Story 1.9 implementation perlu (task-by-task activation):

1. **Tambah config key** `bazaar.api.enabled` → un-skip API-001, API-003
2. **Register webhook routes tanpa kondisi** → un-skip API-010, API-011, API-012
3. **Conditional API route registration** → un-skip API-002, API-004, API-009
4. **Sanctum middleware** → un-skip API-005, API-008
5. **ApiStatusController + API Resource** → un-skip API-006, API-007, API-013
6. **Dokumentasi README** → un-skip API-014
7. Setelah un-skip masing-masing kelompok, jalankan `pest tests/Feature/Install/ApiLayerIntegrationTest.php` dan verifikasi FAIL → PASS
8. Commit tests passing bersama implementasinya
