---
title: 'Story 1.9: API Layer Integration (Headless)'
type: 'feature'
created: '2026-09-20'
status: 'done'
route: 'dispatch'
review_loop_iteration: 0
baseline_commit: '7aabf943e174aacb094cb5f0f183c742c0e16b1a'
context: [
  '{project-root}/_artifacts/implementation-artifacts/epic-1-context.md',
  '{project-root}/_artifacts/test-artifacts/atdd-checklist-1-9-api-layer-integration-headless.md',
]
---

<frozen-after-approval reason="human-owned intent — do not modify unless human renegotiates">

## Intent

**Problem:** Portal headless (Next.js/Vue) belum punya jalur HTTP ke Service Layer Bazaar. `laravel/sanctum` belum terpasang, `bazaar.api.enabled` belum ada, dan 14 test ATDD Story 1.9 masih di-skip.

**Approach:** Tambah API Layer opt-in (`bazaar.api.enabled`, default `false`): `routes/api.php` dimuat kondisional oleh `BazaarServiceProvider`, dilindungi `auth:sanctum` (token per-service), prefix `/bazaar/api/v1`, satu endpoint `GET /store` yang mendelegasikan ke `SettingsService::get()` dan membalas lewat `StoreResource`. Webhook Payment/Shipping tidak diberi route stub (dimiliki Payment/Shipping, Epic 4/5, AD-11); jaminan always-on dibuktikan secara struktural: satu-satunya file route yang digerbangi flag adalah `routes/api.php`, dan file itu tidak memuat route webhook.

## Boundaries & Constraints

**Always:** Controller hanya memanggil Service (AD-5). Flag dibaca saat boot; key config hilang = `false`. Error API memakai envelope default Laravel (`message` + `errors`). `StoreResource` hanya mengekspos field publik: `store_name`, `store_logo`, `store_favicon`, `store_social_media`, `seo_default_meta_title_template`, `seo_default_meta_description`, `seo_default_og_image`.

**Never:** Route stub webhook. Token per-Customer (AD-20). Endpoint issuance/rotasi token (ditunda oleh arsitektur). Mengekspos `payment_gateways`, `shipping_couriers`, atau kunci vendor. Mengubah assertion AC pada test selain yang perlu agar bisa dijalankan.

## I/O & Edge-Case Matrix

| Scenario | Input / State | Expected Output / Behavior | Error Handling |
|----------|--------------|---------------------------|----------------|
| Default off | `bazaar.api.enabled` false/hilang | Tidak ada route `bazaar/api*` | N/A |
| Enabled, tanpa token | `GET /bazaar/api/v1/store` | 401 | envelope Laravel |
| Enabled, token valid, owner punya `manage-settings` | Bearer token Sanctum | 200, `{data: {...7 field...}}` | N/A |
| Enabled, owner tanpa `manage-settings` | Bearer token | 403 | `AuthorizationException` dari Service |

</frozen-after-approval>

## Code Map

- `src/BazaarServiceProvider.php` -- `packageBooted()`: tambah gerbang `loadRoutesFrom(routes/api.php)`.
- `config/bazaar.php` -- tambah key `api.enabled` (false).
- `composer.json` -- `laravel/sanctum` ke `require`.
- `src/Settings/Services/SettingsService.php` -- `get()` (sudah ada, dipakai apa adanya; authorize via `auth()->user()`).
- `src/Settings/Support/BazaarSettings.php` -- sumber field `StoreResource`.
- `tests/Feature/Install/ApiLayerIntegrationTest.php` -- ATDD merah; un-skip per kelompok.
- `tests/ArchDomainBoundaryTest.php` -- `src/Http` otomatis terdeteksi sebagai domain; harus tetap hijau.
- `workbench/app/Models/User.php` -- perlu `HasApiTokens` untuk test.
- `README.md` -- baris 151 "planned"; ganti dengan section API Layer.

## Tasks & Acceptance

**Execution:**
- [x] `composer.json` -- `composer require laravel/sanctum` -- token auth per-service (AD-3/AD-20)
- [x] `config/bazaar.php` -- key `api.enabled` => false + komentar opt-in -- FR-35
- [x] `routes/api.php` -- grup `bazaar/api/v1`, middleware `['api','auth:sanctum']`, `GET /store` -- versioned, terautentikasi
- [x] `src/Http/Api/Controllers/StoreController.php`, `src/Http/Api/Resources/StoreResource.php` -- delegasi ke `SettingsService`, whitelist field -- AD-5, tidak bocor kunci vendor
- [x] `src/BazaarServiceProvider.php` -- muat `routes/api.php` hanya bila flag true -- default off
- [x] `workbench/app/Models/User.php` -- `use HasApiTokens` -- test bisa `createToken`
- [x] `tests/Feature/Install/ApiLayerIntegrationTest.php` (+ test case flag-on dengan `#[WithConfig]`) -- un-skip API-001..014, ganti placeholder API-006/007/013, ubah API-010..012 jadi cek struktural, perbaiki `base_path()` README -> `dirname(__DIR__, 3)` -- red -> green
- [x] `README.md` -- section "API Layer (Headless)": aktivasi, `HasApiTokens` di User host, `createToken('portal-service')`, owner token butuh `manage-settings`, `route:cache` ulang saat flag berubah, contoh curl, webhook selalu aktif -- API-014

**Acceptance Criteria:**
- Given default config, when route table dibaca, then tidak ada route `bazaar/api*`.
- Given `bazaar.api.enabled=true`, when `GET /bazaar/api/v1/store` tanpa token, then 401; dengan token valid (owner `manage-settings`), then 200 berbentuk API Resource.
- Given flag apa pun, then route non-API Bazaar tidak bergantung pada flag dan `routes/api.php` tidak memuat webhook.

## Implementation Notes

- Middleware: `routes/api.php` memakai `AuthenticateApi` (subclass `Authenticate`, `src/Http/Api/Middleware/`) alih-alih `auth:sanctum` polos. Tanpa header `Accept`, middleware stock memanggil `route('login')` dan melempar 500 di host headless tanpa route itu; `AuthenticateApi` memaksa JSON lebih dulu dan tidak pernah redirect. Alias `auth:sanctum` tidak dipakai karena diurutkan lebih dulu oleh priority middleware.
- Test flag-on: `tests/Feature/Install/ApiLayerEnabledTest.php` berbentuk kelas PHPUnit (Pest hanya mengizinkan satu TestCase per folder) di atas `tests/ApiEnabledTestCase.php`, yang menyetel flag di `getEnvironmentSetUp` (atribut `#[WithConfig]` terlambat) dan memuat migrasi Sanctum.
- Test tambahan di luar ATDD: API-015 (403 tanpa `manage-settings`), API-017 (401 JSON tanpa `Accept`). ATDD API-013 kini memeriksa dependensi konstruktor `StoreController`.
- `composer require laravel/sanctum` menjalankan `package:purge-skeleton` yang menghapus `.env` skeleton Testbench; `composer run build` memulihkannya. Berlaku di mesin lokal, bukan perubahan repo.
- Pest penuh: 256 passed, 2 skipped, 4 failed; 4 failure identik dengan baseline 1.7 (`CreateRoleTest` manage-settings, `GlobalSettingsAuditTest` x2, `GlobalSettingsServiceTest`). PHPStan bersih; Pint bersih pada file baru (repo lama sudah tidak patuh Pint).

## Spec Change Log

### Review Findings
- [x] [Review][Patch] API Middleware Stack Issues & Laravel 11 Compatibility — routes/api.php
- [x] [Review][Patch] AuthenticateApi overwrites Accept header unconditionally — src/Http/Api/Middleware/AuthenticateApi.php
- [x] [Review][Patch] Missing data delegation verification in StoreController test — tests/Feature/Install/ApiLayerEnabledTest.php

### Rejected
- `false`: API route group lack CORS middleware — Laravel 11/12 menangani CORS secara global di level aplikasi.
- `false`: API route group lack rate limiting — Grup middleware `api` secara default sudah menyertakan `throttle:api`.
- `false`: composer.json hard-require laravel/sanctum — Spec (AD-3/AD-20) secara eksplisit mewajibkan dependency ini.
- `low`: routes/api.php define route without name — Headless client tidak menggunakan route generation Laravel.
- `false`: Pest test 1.9-API-003 change config runtime — Spec mengakui bahwa perubahan config saat runtime tidak berefek pada registrasi route boot.
- `false`: Sanctum token ignore ability — Spec secara eksplisit memakai User `manage-settings` permission alih-alih token abilities.
- `false`: [1.9-API-012] regex preg_match_all expects exactly 1 match — Regex tersebut tidak ada di dalam diff.
- `false`: Test API-009 mutated without approval — Sesuai Design Notes spec, pengecekan toggle runtime tidak mungkin dilakukan.

## Review Triage Log

Layer subagent dilewati (tidak diminta eksplisit); self-review inline atas diff.

| Finding | Verdict | Route | Evidence |
|---|---|---|---|
| Request tanpa `Accept: application/json` ke route ber-`auth:sanctum` -> 500 `Route [login] not defined` | medium | patch | Reproduksi via test API-017 (merah, 500); diperbaiki dengan `AuthenticateApi`, kini hijau. |
| Gate `=== true` menolak nilai string truthy | low | reject | Strict disengaja: string `'false'` tidak boleh mengaktifkan API; `env()` sudah mengembalikan bool. |
| Guard webhook hanya struktural, tanpa route nyata | low | reject | Keputusan Binyo (opsi Guard struktural); route dimiliki Payment/Shipping (AD-11). |

## Design Notes

`SettingsService::get()` mengotorisasi `auth()->user()`; `auth:sanctum` menjadikan owner token sebagai user aktif, jadi token portal harus dimiliki User dengan `manage-settings`. Dipilih daripada permission baru: tanpa perubahan skema/permission di Epic 1, issuance/rotasi tetap ditunda sesuai arsitektur.

Test flag-on tidak bisa memutar config setelah boot (route dimuat saat boot); pakai subclass TestCase dengan `#[WithConfig('bazaar.api.enabled', true)]`.

## Verification

**Commands:**
- `vendor/bin/pest tests/Feature/Install/ApiLayerIntegrationTest.php` -- expected: semua hijau, 0 skipped
- `vendor/bin/pest` -- expected: tidak ada kegagalan baru selain 4 baseline 1.7
- `vendor/bin/pint --test` dan `vendor/bin/phpstan analyse` -- expected: bersih
