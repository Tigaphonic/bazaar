---
stepsCompleted:
  - step-01-preflight-and-context
  - step-02-generation-mode
  - step-03-test-strategy
  - step-04-generate-tests
  - step-04c-aggregate
lastStep: step-04c-aggregate
lastSaved: '2026-09-20'
storyId: '1.10'
storyKey: 1-10-media-upload-optimization-shared-pipeline
storyFile: _artifacts/planning-artifacts/epics.md
atddChecklistPath: _artifacts/test-artifacts/atdd-checklist-1-10-media-upload-optimization-shared-pipeline.md
generatedTestFiles:
  - tests/Feature/Media/MediaPipelineTest.php
  - tests/Unit/Media/MediaServiceTest.php
  - tests/Browser/Media/dropzone-hint.spec.ts
inputDocuments:
  - _artifacts/planning-artifacts/epics.md
  - _artifacts/planning-artifacts/architecture/architecture-Tigaphonic/bazaar-2026-09-12/ARCHITECTURE-SPINE.md
  - _artifacts/implementation-artifacts/epic-1-context.md
  - _artifacts/implementation-artifacts/sprint-status.yaml
  - _bmad/tea/config.yaml
  - playwright.config.ts
---

# ATDD Checklist: Story 1.10 — Media Upload Optimization (Shared Pipeline)

## TDD Red Phase (Current)

✅ Red-phase test scaffolds generated — semua tes di-skip sampai implementasi selesai.

| Layer | File | Tes | Status |
|-------|------|-----|--------|
| Feature (PHP) | `tests/Feature/Media/MediaPipelineTest.php` | 11 | 🔴 RED — all skipped |
| Unit (PHP) | `tests/Unit/Media/MediaServiceTest.php` | 5 | 🔴 RED — all skipped |
| Browser (E2E) | `tests/Browser/Media/dropzone-hint.spec.ts` | 4 | 🔴 RED — all `test.skip()` |
| **Total** | | **20** | |

---

## Acceptance Criteria Coverage

### AC1: Auto-kompres & konversi WebP via 1 mekanisme bersama

| Test | File | Priority |
|------|------|----------|
| `BazaarWebpConversion class exists at the canonical location` | MediaPipelineTest.php | P0 |
| `HasBazaarMedia trait registers exactly one conversion named "webp"` | MediaPipelineTest.php | P0 |
| `HasBazaarMedia does not define its own image manipulation logic` | MediaPipelineTest.php | P0 |
| `uploading an image triggers the webp conversion automatically` | MediaPipelineTest.php | P0 |
| `webp conversion produces a .webp file format` | MediaPipelineTest.php | P0 |
| `webp conversion file size is smaller than the original` | MediaPipelineTest.php | P1 |
| `no domain outside src/Media/ defines its own conversion class (AD-31)` | MediaPipelineTest.php | P0 |
| `HasBazaarMedia is the only entry point — singleton pattern (AD-31)` | MediaPipelineTest.php | P0 |

### AC2: File asli tetap; WebP adalah varian terpisah (tidak pernah overwrite)

| Test | File | Priority |
|------|------|----------|
| `original file is preserved after webp conversion` | MediaPipelineTest.php | P0 |
| `getUrl() vs getUrl("webp") returns different URLs` | MediaPipelineTest.php | P0 |
| `MediaService::getVariantUrl() exposes webp URL` | MediaServiceTest.php | P0 |
| `MediaService::getOriginalUrl() returns original URL` | MediaServiceTest.php | P0 |
| `webp conversion stored as separate path — not in-place overwrite` | MediaPipelineTest.php | P0 |
| `no domain outside src/Media/ accesses Media model directly (AD-5)` | MediaServiceTest.php | P0 |

### AC3: Dropzone UI seragam + hint line

| Test | File | Priority |
|------|------|----------|
| `dropzone shows correct hint line for logo upload` | dropzone-hint.spec.ts | P1 |
| `dropzone shows correct hint line for favicon upload` | dropzone-hint.spec.ts | P1 |
| `settings page uses Dropzone component, not plain file input` | dropzone-hint.spec.ts | P2 |
| `after upload, original + webp URLs both accessible (browser sanity)` | dropzone-hint.spec.ts | P1 |

---

## Test Strategy

**Stack detected:** `fullstack` (PHP Pest backend + Playwright browser)

**Generation mode:** Sequential AI generation (no browser recording needed — UI belum ada)

**Execution mode (config):** `auto` → resolved to `sequential`

**`tea_use_playwright_utils`:** `true` (flag) — tapi `@seontechnologies/playwright-utils` **belum terpasang** sebagai paket npm.
Browser tests mengikuti pola existing (`@playwright/test` langsung) dan akan di-upgrade ke merged-fixtures saat paket di-install. Ini tercatat sebagai wiring yang perlu dilakukan sebelum green phase.

**Pact / Contract testing:** Tidak relevan — tidak ada dua independently-deployable service di repo ini.

---

## Arsitektur yang Diperlukan Sebelum Green Phase

Berdasarkan test scaffolds di atas, implementasi yang harus ada:

### src/Media/ domain (baru, sesuai AD-31)

```
src/Media/
  Concerns/
    HasBazaarMedia.php          ← trait; di-use setiap model yang upload gambar
  Conversions/
    BazaarWebpConversion.php    ← satu konversi bersama (compress + format WebP)
  Services/
    MediaService.php            ← gateway; expose getVariantUrl(), getOriginalUrl(), deleteMedia()
```

### Workbench fixture model (untuk test)

```
workbench/app/Models/
  MediaTestModel.php            ← model sederhana yang menggunakan HasBazaarMedia
```

### Migration

```
database/migrations/
  ????_create_media_table.php   ← spatie/laravel-medialibrary punya artisan command untuk ini:
                                   php artisan vendor:publish --tag="medialibrary-migrations"
```

### npm package (sebelum green phase browser tests)

```bash
# Re-evaluate ketika @seontechnologies/playwright-utils tersedia
# Saat ini browser tests pakai @playwright/test langsung (lihat dropzone-hint.spec.ts)
```

---

## Next Steps — Task-by-Task Activation

Urutan aktivasi saat implementasi Story 1.10:

### Task 1: Setup spatie/laravel-medialibrary
1. Verify `spatie/laravel-medialibrary` sudah terinstall (ada di addendum.md / ARCHITECTURE-SPINE.md)
2. Publish migrations: `php artisan vendor:publish --tag="medialibrary-migrations"`
3. Buat `workbench/app/Models/MediaTestModel.php` untuk test fixture
4. **Aktifkan:** `tests/Feature/Media/MediaPipelineTest.php` — test `BazaarWebpConversion class exists`

### Task 2: Buat BazaarWebpConversion + HasBazaarMedia
1. Implement `src/Media/Conversions/BazaarWebpConversion.php`
2. Implement `src/Media/Concerns/HasBazaarMedia.php`
3. **Aktifkan:** semua test AC1 di `MediaPipelineTest.php`
4. Jalankan: `./vendor/bin/pest tests/Feature/Media/MediaPipelineTest.php`
5. Verifikasi: semua test AC1 hijau

### Task 3: Buat MediaService
1. Implement `src/Media/Services/MediaService.php`
2. **Aktifkan:** semua test di `MediaServiceTest.php`
3. Jalankan: `./vendor/bin/pest tests/Unit/Media/MediaServiceTest.php`
4. Verifikasi: semua test AC2 hijau

### Task 4: Pasang HasBazaarMedia ke Settings (logo/favicon)
1. Tambahkan `use HasBazaarMedia` ke Settings model yang menyimpan logo/favicon
2. Update Settings form dengan `SpatieMediaLibraryFileUpload` + Dropzone component + hint line
3. **Aktifkan:** browser tests di `dropzone-hint.spec.ts`
4. Jalankan: `npx playwright test tests/Browser/Media/dropzone-hint.spec.ts`
5. Verifikasi: Dropzone tampil dengan hint line yang benar

### Task 5: AD-5 + AD-31 arch enforcement
1. **Aktifkan:** arch test `no domain outside src/Media/ defines its own conversion class`
2. **Aktifkan:** arch test `no domain outside src/Media/ accesses Media model directly`
3. Jalankan: `./vendor/bin/pest tests/Feature/Media/ tests/Unit/Media/`

---

## Perintah Menjalankan Test

```bash
# PHP Pest — semua media tests
./vendor/bin/pest tests/Feature/Media/ tests/Unit/Media/ --verbose

# Playwright — browser tests
npx playwright test tests/Browser/Media/ --reporter=list

# Verifikasi semua skipped (red phase — harus lulus dengan 0 failures, N skipped)
./vendor/bin/pest tests/Feature/Media/ --verbose 2>&1 | grep -E "skipped|SKIP"
```

---

## ATDD Artifacts

- **Checklist:** `_artifacts/test-artifacts/atdd-checklist-1-10-media-upload-optimization-shared-pipeline.md`
- **Feature test:** `tests/Feature/Media/MediaPipelineTest.php`
- **Unit test:** `tests/Unit/Media/MediaServiceTest.php`
- **Browser E2E:** `tests/Browser/Media/dropzone-hint.spec.ts`
- **Story source:** `_artifacts/planning-artifacts/epics.md` §Story 1.10
