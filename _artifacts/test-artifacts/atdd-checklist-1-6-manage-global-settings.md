---
stepsCompleted:
  - step-01-preflight-and-context
  - step-02-generation-mode
  - step-03-test-strategy
  - step-04-generate-tests
lastStep: step-04-generate-tests
lastSaved: '2026-09-19'
storyId: '1.6'
storyKey: 1-6-manage-global-settings
storyFile: _artifacts/implementation-artifacts/epic-1-context.md
atddChecklistPath: _artifacts/test-artifacts/atdd-checklist-1-6-manage-global-settings.md
generatedTestFiles:
  - tests/Feature/Settings/GlobalSettingsServiceTest.php
  - tests/Feature/Settings/GlobalSettingsPage/GlobalSettingsPageTest.php
inputDocuments:
  - _artifacts/implementation-artifacts/epic-1-context.md
  - _artifacts/planning-artifacts/prds/prd-Tigaphonic/bazaar-2026-09-11/prd.md
  - _bmad/tea/config.yaml
  - playwright.config.ts
  - tests/TestCase.php
  - tests/Pest.php
---

# ATDD Checklist — Story 1.6: Manage Global Settings

> 🔴 **TDD RED PHASE** — Semua test scaffold di bawah akan **merah** sampai Story 1.6 diimplementasikan.

## Preflight

| Item | Status |
|---|---|
| Stack | `fullstack` (Pest/PHP + Playwright) |
| Framework | Pest + Testbench Workbench |
| tea_use_playwright_utils | `true` (mandate aktif; tidak berlaku ke file ini karena backend-only) |
| Source story | Derived dari PRD FR-28/FR-29 + epic-1-context.md |
| Prerequisites | ✅ AC jelas; playwright.config.ts ada |

## Generation Mode

**AI Generation** — backend Pest feature tests; tidak perlu browser recording untuk Global Settings form.  
Browser E2E untuk Settings tidak dijadwalkan di red-phase ini (UI behavior sudah dicakup Livewire test).

## Acceptance Criteria → Test Mapping

| AC | Keterangan | File | Priority | Red? |
|---|---|---|---|---|
| AC1 | Page render tanpa error | GlobalSettingsPageTest | P1 | ✅ |
| AC2 | Edit + save persisten via SettingsService | GlobalSettingsServiceTest + PageTest | P0 | ✅ |
| AC3 | Tidak ada Create/Delete parameter | GlobalSettingsPageTest | P1 | ✅ |
| AC4 | Gateway credentials terenkripsi | GlobalSettingsServiceTest | P0 | ✅ |
| AC5 | Timeout Timer family dapat diatur | GlobalSettingsServiceTest | P1 | ✅ |
| AC6 | Default Warehouse FK valid | GlobalSettingsServiceTest | P1 | ✅ |
| AC7 | Robots.txt text bebas; Bazaar tidak serve | GlobalSettingsServiceTest | P1 | ✅ |
| AC8 | Analytics codes terpisah per kolom | GlobalSettingsServiceTest | P1 | ✅ |
| AC9 | Min < Max Refund %; whole-number | GlobalSettingsServiceTest + PageTest | P0 | ✅ |
| AC10 | SEO Defaults (FR-29) persisted | GlobalSettingsServiceTest | P1 | ✅ |
| AC11 | Audit Trail auto-captured | GlobalSettingsServiceTest | P1 | ✅ |
| AC12 | bazaar:status widget heartbeat | GlobalSettingsPageTest | P1 | ✅ |
| AC13 | Authorization — manage-settings permission | GlobalSettingsServiceTest + PageTest | P0 | ✅ |
| AC14 | SettingsService domain boundary (AD-5) | Existing ArchDomainBoundaryTest (auto-applies) | P0 | ✅ (auto) |

## Generated Test Files

### `tests/Feature/Settings/GlobalSettingsServiceTest.php`

Pest feature tests untuk `SettingsService`:

- `SettingsService::get()` returns all required setting keys
- `SettingsService::update()` persists changed values
- `SettingsService::update()` only changes provided keys (immutable remainder)
- Unknown keys silently ignored (fixed schema enforced)
- Payment gateway credentials encrypted at rest; decrypted transparently via `get()`
- All Timeout Timer parameters accepted and persisted
- `default_warehouse_id` FK validated; invalid ID throws exception
- `robots_txt_content` stored verbatim; no `/robots.txt` route registered
- Analytics codes (GSC / GA4 / FB Pixel) stored in separate fields
- Refund %: `min >= max` throws `InvalidSettingValueException`
- Refund %: equal values throw exception
- Refund %: valid `min < max` accepted
- Refund %: decimal values rejected (whole-number only)
- SEO Defaults (FR-29): meta title template, description, OG image persisted
- Audit Trail auto-captured without `activity()` call in SettingsService
- Authorization: `get()` + `update()` throw `AuthorizationException` for Staff tanpa permission

### `tests/Feature/Settings/GlobalSettingsPage/GlobalSettingsPageTest.php`

Pest Livewire tests untuk `GlobalSettings` Filament page:

- Page render 200 untuk Staff berwenang
- Page 403 untuk Staff tanpa permission
- Tidak ada HeaderAction bernama `create`
- Form submit dengan `store_name` baru persists via SettingsService
- Success toast notification setelah save
- Form validation error ketika `refund_min_percent >= refund_max_percent`
- Navigation group terdaftar sebagai `'Global Settings'`
- `BazaarStatusWidget` termount di halaman

## TDD Phase Status

```
🔴 RED — 24 test assertions, 0 passing
         Semua menggunakan test()->skip('RED — ...') sesuai TDD protocol
         Aktifkan satu per satu saat implementasi Story 1.6 berjalan
```

## Deferred / Out-of-Scope

- **AC6 (Default Warehouse FK)**: requires Catalog/Warehouse domain (Epic 3). Test scaffold ada tapi skip reason menyebut dependency. Warehouse factory akan tersedia saat Epic 3 dimulai.
- **Playwright E2E**: tidak dijadwalkan di red-phase ini; behavior visual Settings tercakup Livewire test. Tambahkan di `tests/Browser/` saat Story 1.6 green jika ada perilaku yang tidak bisa diverifikasi Pest.
- **Payment gateway active/method selection**: test `payment_gateways` array saat ini hanya cek enkripsi. Tes aktif/nonaktif per metode ditambahkan di green-phase saat struktur data final jelas.
