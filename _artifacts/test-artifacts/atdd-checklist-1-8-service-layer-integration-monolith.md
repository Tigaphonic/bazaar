---
stepsCompleted:
  - step-01-preflight-and-context
  - step-02-generation-mode
  - step-03-test-strategy
  - step-04-generate-tests
  - step-04c-aggregate
  - step-05-validate-and-complete
lastStep: step-05-validate-and-complete
lastSaved: '2026-09-19'
storyId: '1.8'
storyKey: 1-8-service-layer-integration-monolith
storyFile: _artifacts/planning-artifacts/epics.md
atddChecklistPath: _artifacts/test-artifacts/atdd-checklist-1-8-service-layer-integration-monolith.md
generatedTestFiles:
  - tests/Feature/Install/ServiceLayerIntegrationTest.php
inputDocuments:
  - _artifacts/planning-artifacts/epics.md
  - _artifacts/implementation-artifacts/epic-1-context.md
  - _bmad/tea/config.yaml
  - _artifacts/planning-artifacts/architecture/architecture-Tigaphonic/bazaar-2026-09-12/ARCHITECTURE-SPINE.md
---

# ATDD Checklist: Story 1.8 — Service Layer Integration (Monolith)

## TDD Red-Phase Status

| Item | Status |
|------|--------|
| Test scaffold dibuat | ✅ |
| Tests fail untuk alasan yang benar | ✅ 1 failed (AC2 docs belum ada), 1 skipped, 12 passed* |
| Tidak ada placeholder assertion | ✅ |
| Semua P0 tests pass (Services sudah ada) | ✅ |

> **Catatan:** 12 tests pass karena Services (UserService, AuditTrailService, dll.) sudah diimplementasikan di Story 1.1–1.7. Yang merah adalah **AC2** (dokumentasi panduan integrasi belum ada). Ini adalah status red-phase yang benar untuk Story 1.8 — implementasi utamanya adalah dokumentasi + pastikan services publik terdaftar dengan benar.

## Generated Test File

- [`tests/Feature/Install/ServiceLayerIntegrationTest.php`](../../../tests/Feature/Install/ServiceLayerIntegrationTest.php)

## Stack Detection

- **Detected Stack:** `fullstack`
- **Generation Mode:** AI Generation (sequential)
- **E2E Tests:** N/A — Story 1.8 tidak punya UI surface
- **Pact CDC:** N/A — tidak ada HTTP endpoint baru (Service Layer dipanggil in-process)

## Acceptance Criteria Coverage

| # | AC | Test ID(s) | Priority | Status |
|---|---|---|---|---|
| AC1 | Service dapat dipanggil via DI/facade tanpa HTTP | INT-001–012 | P0–P1 | 🔴 Scaffold (12 pass, Service sudah exist) |
| AC2 | Tersedia panduan integrasi (availability-check, checkout) | INT-013–014 | P2 | 🔴 FAIL — dokumentasi belum ada |

## Test Strategy

### Kenapa Pest Feature Test, bukan TypeScript/Playwright?

Story 1.8 adalah **backend-only contract** — tidak ada UI surface, tidak ada HTTP endpoint baru. Playwright browser tests tidak relevan. Pest Feature Tests menggunakan Testbench (in-process Laravel kernel) untuk membuktikan DI container resolution dan in-process callability.

### Level Test per Skenario

| Test ID | Skenario | Level | Alasan |
|---------|----------|-------|--------|
| INT-001–005 | Container resolution tiap Service | Integration | Membutuhkan Laravel container |
| INT-006–007 | Constructor DI simulation | Integration | Verifikasi binding pattern |
| INT-008–012 | Callable without HTTP | Integration | Verifikasi tidak ada socket |
| INT-013–014 | Dokumentasi tersedia | Integration (proxy) | Proxy untuk AC2 yang non-testable secara otomatis |

## Test File Details

### [`tests/Feature/Install/ServiceLayerIntegrationTest.php`](../../../tests/Feature/Install/ServiceLayerIntegrationTest.php)

```
Tests:    1 failed, 1 skipped, 12 passed
Duration: 0.49s
```

| Test ID | Test Name | Priority | Red Phase Result |
|---------|-----------|----------|-----------------|
| 1.8-INT-001 | resolves UserService from container | P0 | ✅ pass |
| 1.8-INT-002 | resolves RoleService from container | P0 | ✅ pass |
| 1.8-INT-003 | resolves AuditTrailService from container | P0 | ✅ pass |
| 1.8-INT-004 | resolves SettingsService from container | P0 | ✅ pass |
| 1.8-INT-005 | resolves SeoResolverService from container | P0 | ✅ pass |
| 1.8-INT-006 | constructor DI simulation | P0 | ✅ pass |
| 1.8-INT-007 | multiple resolutions tidak throw | P0 | ✅ pass |
| 1.8-INT-008 | AuditTrailService::list() returns Collection | P0 | ✅ pass |
| 1.8-INT-009 | SettingsService::get() returns BazaarSettings | P0 | ✅ pass |
| 1.8-INT-010 | SeoResolverService::resolveOgImage() dengan entity value | P0 | ✅ pass |
| 1.8-INT-011 | SeoResolverService fallback ke Global SEO Default | P0 | ✅ pass |
| 1.8-INT-012 | Tidak ada outbound HTTP request | P1 | ✅ pass |
| 1.8-INT-013 | README/docs mendokumentasikan Service Layer | P2 | ❌ FAIL (docs belum ada) |
| 1.8-INT-014 | README mengandung contoh konkret | P2 | ⏭ skip (README tidak ada "Service Layer") |

## Next Steps (Task-by-Task Activation)

Story 1.8 implementation perlu:

1. **Pastikan semua Services publik terdaftar** — review `BazaarServiceProvider` bindings; verifikasi `app(XxxService::class)` bekerja dari kode host app (bukan hanya Testbench). INT-001–012 membuktikan ini sudah benar.

2. **Tulis dokumentasi Service Layer Integration** (AC2):
   - Tambahkan section "Service Layer Integration" di `README.md` atau `docs/service-layer.md`
   - Sertakan contoh konkret: `app(SettingsService::class)->get()` untuk availability-check pattern
   - Sertakan contoh constructor DI di Livewire component
   - Setelah selesai, INT-013 dan INT-014 akan pass

3. **Un-skip test saat implementasi**: Tests INT-013/014 sudah aktif (bukan `test.skip()`), langsung fail. Setelah dokumentasi ditambahkan → tests pass.

4. **Run full suite**: `php vendor/bin/pest tests/Feature/Install/ServiceLayerIntegrationTest.php`

5. **Stage files:**
   ```bash
   git add tests/Feature/Install/ServiceLayerIntegrationTest.php
   git add _artifacts/test-artifacts/atdd-checklist-1-8-service-layer-integration-monolith.md
   ```

## Assumptions & Risks

| Item | Detail |
|------|--------|
| P0 tests pass di red phase | Services sudah exist dari Story 1.1–1.7; ini menunjukkan kontrak Service Layer sudah benar secara teknis |
| AC2 tidak bisa ditest secara behavioral | Test proxy (string contains dalam README) adalah pragmatic — diterima untuk documentation requirement |
| `SettingsService::get()` membutuhkan unauthenticated context | BazaarSettings pass through tanpa user auth di console/test context — verified pass |
| Pest `->or->` bukan sintaks valid | Fixed ke `expect($x === null || is_string($x))->toBeTrue()` |

## Confidence Gate

```
Confidence: 8
Rationale: Story AC dibaca langsung dari epics.md L419-433. Pola test
ada di tests/Feature/User/UserServiceTest.php (DI via app(), Pest).
Services sudah exist dan DI berfungsi (diverifikasi run). Satu-satunya
unknown adalah AC2 (dokumentasi) yang secara by-design failing.
Unknowns:
- Tidak ada ServiceLayerFacade terpisah yang direncanakan (AD-5 bicara
  DI/Service, bukan Laravel Facade class) — tests menggunakan app()
  bukan Bazaar::service() facade; jika facade diputuskan dibuat, tambah
  tests facade.
```
