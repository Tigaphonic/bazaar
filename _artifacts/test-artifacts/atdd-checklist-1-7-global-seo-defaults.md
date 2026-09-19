---
stepsCompleted:
  - step-01-preflight-and-context
  - step-02-generation-mode
  - step-03-test-strategy
  - step-04-generate-tests
lastStep: step-04-generate-tests
lastSaved: '2026-09-19'
storyId: '1.7'
storyKey: 1-7-global-seo-defaults
storyFile: _artifacts/planning-artifacts/epics.md
atddChecklistPath: _artifacts/test-artifacts/atdd-checklist-1-7-global-seo-defaults.md
generatedTestFiles:
  - tests/Feature/Settings/SeoResolverTest.php
inputDocuments:
  - _artifacts/planning-artifacts/epics.md
  - _artifacts/implementation-artifacts/epic-1-context.md
  - _artifacts/implementation-artifacts/spec-1-6-manage-global-settings.md
  - _artifacts/test-artifacts/atdd-checklist-1-6-manage-global-settings.md
  - _bmad/tea/config.yaml
  - playwright.config.ts
  - tests/TestCase.php
  - tests/Pest.php
  - tests/Feature/Settings/GlobalSettingsServiceTest.php
---

# ATDD Checklist — Story 1.7: Global SEO Defaults

> 🔴 **TDD RED PHASE** — Semua test scaffold di bawah akan **merah** sampai Story 1.7 diimplementasikan.

## Preflight

| Item | Status |
|---|---|
| Stack | `fullstack` (Pest/PHP + Playwright; backend-only untuk story ini) |
| Framework | Pest 4 + Testbench Workbench |
| tea_use_playwright_utils | `true` (mandate aktif; tidak berlaku ke file ini — backend-only) |
| Source story | epics.md baris 403–418 (FR-24/FR-29) + epic-1-context.md baris 54 |
| Prerequisites | ✅ AC jelas; playwright.config.ts ada; BazaarSettings + SettingsService sudah hijau (Story 1.6) |

> [!NOTE]
> Story 1.7 adalah **section** di dalam Global Settings, bukan modul terpisah. Field `seo_default_*` di `BazaarSettings` dan persistensinya di `SettingsService` sudah dikover oleh Story 1.6 AC10 (hijau). Scope 1.7 = **`SeoResolverService`** yang membaca settings tersebut dan menerapkan fallback berjenjang saat metadata entity kosong.

## Generation Mode

**AI Generation** — backend Pest feature tests; tidak perlu browser recording atau Playwright untuk resolver logic.

## Acceptance Criteria → Test Mapping

| AC | Keterangan | File | Priority | Red? |
|---|---|---|---|---|
| AC1-persist | SEO defaults tersimpan sebagai bagian Global Settings | GlobalSettingsServiceTest (sudah hijau 1.6) | P0 | ✅ hijau (skip duplikasi) |
| AC2-resolve | Entity tanpa gambar → fallback `seo_default_og_image` | SeoResolverTest | P0 | 🔴 |
| AC2-entity-own | Entity punya gambar → gambar entity dipakai | SeoResolverTest | P1 | 🔴 |
| AC2-null-key | Entity tidak punya kunci `og_image` sama sekali | SeoResolverTest | P1 | 🔴 |
| AC2-no-default | Entity + default OG keduanya null → null | SeoResolverTest | P1 | 🔴 |
| AC3-meta-title-own | Entity punya meta title eksplisit → dipakai | SeoResolverTest | P1 | 🔴 |
| AC3-meta-title-template | Entity tanpa meta title → template diterapkan | SeoResolverTest | P0 | 🔴 |
| AC3-meta-title-fallback | Template tidak dikonfigurasikan → nama entity | SeoResolverTest | P1 | 🔴 |
| AC4-meta-desc-own | Entity punya meta description → dipakai | SeoResolverTest | P1 | 🔴 |
| AC4-meta-desc-default | Entity kosong → `seo_default_meta_description` | SeoResolverTest | P1 | 🔴 |
| AC4-meta-desc-null | Keduanya null → null | SeoResolverTest | P1 | 🔴 |
| AC5-og-title-chain | OG Title → Meta Title → nama entity (FR-24) | SeoResolverTest | P1 | 🔴 |
| AC6-di | SeoResolverService dapat di-resolve dari container | SeoResolverTest | P1 | 🔴 |
| AC7-boundary | Class di namespace Settings, tidak import Model domain lain | SeoResolverTest | P1 | 🔴 |
| AC8-all-types | Resolver bekerja untuk semua tipe entity (Item/Blog/Page/Category) | SeoResolverTest | P1 | 🔴 |
| AC9-arch | Domain boundary test Settings tidak mengakses Catalog/User Model langsung | ArchDomainBoundaryTest (auto-applies) | P0 | ✅ (auto) |

## Generated Test Files

### `tests/Feature/Settings/SeoResolverTest.php`

Pest feature tests untuk `SeoResolverService`:

- `SeoResolverService` dapat di-resolve dari container (DI)
- `resolveOgImage()` — entity punya gambar → gambar entity dipakai
- `resolveOgImage()` — entity tanpa gambar → `seo_default_og_image` dari settings
- `resolveOgImage()` — kunci `og_image` tidak ada → fallback ke default
- `resolveOgImage()` — entity null + default null → null
- `resolveMetaTitle()` — entity punya meta title eksplisit → dipakai
- `resolveMetaTitle()` — entity kosong → template `{nama entity} — {nama toko}` diterapkan
- `resolveMetaTitle()` — template null → nama entity saja
- `resolveMetaDescription()` — entity punya deskripsi → dipakai
- `resolveMetaDescription()` — entity kosong → `seo_default_meta_description`
- `resolveMetaDescription()` — keduanya null → null
- `resolveOgTitle()` — OG Title → Meta Title → nama entity (chain FR-24)
- Class di namespace `Tigaphonic\Bazaar\Settings\`
- Class tidak import Model domain lain langsung
- Resolver bekerja untuk semua tipe entity via array metadata

## TDD Phase Status

```
🔴 RED — 17 test scaffolds, 0 passing
         Semua menggunakan $this->markTestSkipped('RED — SeoResolverService belum ada; dibuat Story 1.7')
         Aktifkan satu per satu saat implementasi Story 1.7 berjalan
```

## Implementation Guidance (untuk developer)

> [!IMPORTANT]
> **Class yang perlu dibuat:** `src/Settings/Services/SeoResolverService.php`
>
> **Interface resolver:**
> ```php
> class SeoResolverService {
>     public function resolveOgImage(array $entityMeta): ?string;
>     public function resolveMetaTitle(array $entityMeta): ?string;
>     public function resolveMetaDescription(array $entityMeta): ?string;
>     public function resolveOgTitle(array $entityMeta): ?string;
> }
> ```
>
> **Pola pemanggilan:** Entity domain mengirim array metadata mentah, bukan Eloquent Model. Resolver membaca `SettingsService::get()` untuk defaults. Tidak ada import Model domain lain.

## Deferred / Out-of-Scope

- **SEO Defaults UI**: Form section sudah dikover Story 1.6. Tidak ada form baru.
- **Portal integration**: Cara portal memanggil `SeoResolverService` dikover Story 1.8 (Service Layer Integration).
- **Cache resolver output**: Belum ada kebutuhan caching — bisa ditambah saat performa jadi isu nyata.
- **Playwright E2E**: Tidak dijadwalkan di red-phase ini; behavior fallback SEO adalah Service concern, bukan visual concern yang perlu browser.
