---
title: 'Story 1.10: Media Upload Optimization (Shared Pipeline)'
type: 'feature'
created: '2026-09-20'
status: 'done'
route: 'dispatch'
review_loop_iteration: 0
baseline_commit: 'fd6223aedbdc4aace7276dbf3bd9b1c3a423c684'
context: [
  '{project-root}/_artifacts/implementation-artifacts/epic-1-context.md',
  '{project-root}/_artifacts/test-artifacts/atdd-checklist-1-10-media-upload-optimization-shared-pipeline.md',
]
---

<frozen-after-approval reason="human-owned intent — do not modify unless human renegotiates">

## Intent

**Problem:** Belum ada pipeline media bersama. `spatie/laravel-medialibrary` belum terpasang, `src/Media/` belum ada, dan 17 test PHP + 4 test browser ATDD Story 1.10 masih di-skip. Epic 3 (Item) dan Epic 6 (Hero Banner/Blog/Page) butuh pipeline ini sebagai prasyarat titik upload.

**Approach:** Domain `src/Media/` baru: `BazaarWebpConversion` (satu-satunya definisi kompres+WebP), trait `HasBazaarMedia` (mendaftarkan konversi `webp` sekali), dan `MediaService` (gateway URL varian/original/delete, AD-5). Original tetap disimpan; WebP adalah konversi terpisah (AD-31).

## Boundaries & Constraints

**Always:** Satu konversi bernama `webp`. Domain lain hanya lewat `MediaService` (AD-5). Model pengunggah memakai `HasBazaarMedia`, bukan `InteractsWithMedia` langsung. ULID untuk model baru.

**Never:** Implementasi konversi per domain. Menimpa original. Mengekspos path filesystem lewat Service. Mengubah assertion AC pada test selain yang perlu agar bisa dijalankan.

**Decisions (Binyo, 2026-09-20):** (1) Wiring logo/favicon Settings ditunda ke story tersendiri; `GlobalSettings`, `BazaarSettings`, `StoreResource` tidak disentuh. (2) Komponen Dropzone Shell + hint line (AC3) ditunda ke story pengguna upload pertama (Epic 3/6); 4 test browser `dropzone-hint.spec.ts` tetap `test.skip`.

## I/O & Edge-Case Matrix

| Scenario | Input / State | Expected Output / Behavior | Error Handling |
|----------|--------------|---------------------------|----------------|
| Upload gambar | jpg/png via `addMedia()->toMediaCollection('images')` | original tersimpan; varian `webp` (.webp, lebih kecil) terbentuk | N/A |
| URL varian | `MediaService::getVariantUrl($media,'webp')` | URL http(s), bukan path filesystem | N/A |
| URL original | `MediaService::getOriginalUrl($media)` | URL file asli, berbeda dari varian | N/A |
| Hapus | `MediaService::deleteMedia($media)` | original + semua varian terhapus | N/A |

</frozen-after-approval>

## Code Map

- `composer.json` -- tambah `spatie/laravel-medialibrary ^11.23` (AD-31, versi sesuai spine).
- `src/Media/{Concerns/HasBazaarMedia,Conversions/BazaarWebpConversion,Services/MediaService}.php` -- baru.
- `src/BazaarServiceProvider.php` -- `hasMigrations()` dan publish/bind sesuai konvensi; migrasi `media` dimiliki dependensi (AD-18), dipublish `bazaar:install`.
- `workbench/app/Models/MediaTestModel.php` + migrasi tabelnya -- fixture test.
- `tests/Feature/Media/MediaPipelineTest.php`, `tests/Unit/Media/MediaServiceTest.php` -- un-skip; test 2 (anonymous class) dan mock `Media` perlu disesuaikan agar bisa jalan.
- `tests/ArchDomainBoundaryTest.php` -- `src/Media` otomatis jadi domain; harus tetap hijau.
- `src/Settings/Filament/Pages/GlobalSettings.php`, `tests/Browser/Media/dropzone-hint.spec.ts` -- JANGAN diubah (ditunda).

## Tasks & Acceptance

**Execution:**
- [x] `composer.json` -- require medialibrary -- dependensi pipeline
- [x] `src/Media/Conversions/BazaarWebpConversion.php` -- kompres + WebP -- AD-31
- [x] `src/Media/Concerns/HasBazaarMedia.php` -- daftarkan konversi `webp`, delegasi ke `BazaarWebpConversion` -- satu entry point
- [x] `src/Media/Services/MediaService.php` -- `getVariantUrl/getOriginalUrl/deleteMedia` -- AD-5
- [x] `workbench/app/Models/MediaTestModel.php` + migrasi -- fixture
- [x] `tests/Feature/Media/*`, `tests/Unit/Media/*` -- un-skip red -> green

**Acceptance Criteria:**
- Given gambar diunggah lewat model ber-`HasBazaarMedia`, when disimpan, then varian `webp` terbentuk otomatis tanpa aksi tambahan.
- Given konversi berjalan, then original tetap ada dan tidak berubah; varian di path terpisah.
- Given domain selain `src/Media/`, then tidak ada `registerMediaConversions` maupun akses langsung ke model `Media`.

## Implementation Notes

- `composer require spatie/laravel-medialibrary:^11.23` terpasang 11.23.8 (spatie/image 3.9.6, driver GD, dukungan WebP aktif). Percobaan pertama timeout jaringan lalu dicoba ulang; `composer run build` memulihkan sqlite testbench.
- `BazaarWebpConversion::apply()` menerima `Conversion` dari trait (bukan `register(HasMedia)`), agar `HasBazaarMedia` bebas dari `->format(`/`->optimize(` (ATDD) dan model anonim tetap bisa dites. `getRegisteredMediaConversions()` ada di trait untuk test.
- Konversi `nonQueued()`: default medialibrary memakai queue (`database` di Testbench), sehingga varian belum ada saat upload selesai. Sync menjamin AC1/AC2 dan URL Service tidak menunjuk file yang belum ada. Trade-off: upload gambar besar memblok request; ubah ke queued bila Epic 3/6 butuh.
- `MediaService` mengembalikan `url()` absolut; URL disk publik relatif tidak berguna bagi portal headless. URL absolut dari S3 dilewatkan apa adanya.
- `bazaar:install` kini juga mempublish `create_media_table` (tag `medialibrary-migrations`); `tests/TestCase.php` memuat stub yang sama dan membersihkan file bocor. Tambah test install dan test hapus nyata (file original + webp hilang).
- `phpstan.neon.dist`: ignore `trait.unused` terscope untuk `HasBazaarMedia` (belum ada pemakai di `src/`).
- Diuji: 17 test ATDD hijau + 2 baru; Pest penuh 277 passed, 2 skipped (sudah ada sebelumnya), 4 failed identik baseline 1.7. PHPStan bersih; Pint bersih pada `src/Media`, repo lama tak patuh Pint.
- Risiko tercatat di `deferred-work.md`: stub `media` memakai `morphs('model')` (bigint) sedangkan model Bazaar ULID (AD-18 melarang edit migrasi vendor).

## Spec Change Log

## Review Triage Log

Layer subagent dilewati (tidak diminta eksplisit); self-review inline atas diff.

| Finding | Verdict | Route | Evidence |
|---|---|---|---|
| `getRegisteredMediaConversions()` mereset `$mediaConversions` milik model | low | reject | Helper untuk test/introspeksi; upload memakai `registerAllMediaConversions()` yang memuat ulang sendiri. |
| Konversi sync memblok request untuk gambar besar | low | reject | Keputusan sadar (varian harus ada saat disimpan, AC1/AC2); dicatat di Implementation Notes. |
| `media.model_id` bigint vs model ULID | medium | defer | Pre-existing konflik AD-18 vs stub vendor; belum ada model produksi ber-media; dicatat di `deferred-work.md`. |

## Verification

**Commands:**
- `vendor/bin/pest tests/Feature/Media tests/Unit/Media` -- expected: hijau, 0 skipped
- `vendor/bin/pest` -- expected: tidak ada kegagalan baru selain 4 baseline 1.7
- `vendor/bin/phpstan analyse` -- expected: bersih
