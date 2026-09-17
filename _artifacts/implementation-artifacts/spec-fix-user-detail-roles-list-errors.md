---
title: 'Fix: halaman detail User & list Roles error (tag publish migration permission salah)'
type: 'bugfix'
created: '2026-09-17'
status: 'done'
route: 'dispatch'
review_loop_iteration: 0
context: []
baseline_commit: '0b74263387248eef48955672ce36283a451c2364'
---

<frozen-after-approval reason="human-owned intent — do not modify unless human renegotiates">

## Intent

**Problem:** Halaman edit User dan list Roles di host app sama-sama 500. Root cause bersama: `BazaarInstallCommand` mem-publish migration `spatie/laravel-permission` pakai tag `laravel-permission-migrations`, padahal versi package yang ter-install (lewat `laravel-package-tools`) mendaftarkan tag itu sebagai `permission-migrations` (`shortName()` = `Str::after('laravel-permission', 'laravel-')` = `'permission'`). `vendor:publish` dengan tag salah cuma no-op diam-diam (tetap exit SUCCESS), jadi migration `roles`/`permissions`/dst tidak pernah ter-publish maupun ter-migrate. `RoleResource`'s list query dan `UserResource`'s `CheckboxList::make('roles')` (form maupun auto-hydrate saat edit) sama-sama menyentuh tabel yang tidak ada. Root cause kedua, khusus edit User: `UserService::syncRoles()`/`CheckboxList` butuh host's User model pakai `Spatie\Permission\Traits\HasRoles` — persyaratan manual-install yang sudah terdokumentasi di spec Story 1.3 tapi tidak pernah disebut di output `bazaar:install` maupun README, jadi silent gap juga.

**Approach:** Perbaiki tag publish di `BazaarInstallCommand` ke `permission-migrations`, tambah test yang assert file migration benar ter-publish (bukan cuma exit code, yang bisa lolos walau tag salah). Tambah guidance eksplisit di output `bazaar:install` + README tentang syarat manual `HasRoles` trait, supaya onboarding host app berikutnya tidak kena silent gap yang sama.

**Keputusan (dijawab manusia):** Sekalian perbaiki `app-bazaar-sandbox/app/Models/User.php` (tambah `use Spatie\Permission\Traits\HasRoles;`) supaya bug "detail User error" hilang juga di sandbox verifikasi live — pola identik dengan `workbench/app/Models/User.php` milik package ini sejak Story 1.3.

## Boundaries & Constraints

**Always:** Presentation code (`UserResource`/`RoleResource`) tetap hanya lewat Service layer (AD-5) — tidak diubah spec ini. Package tidak pernah menyuntik trait ke host's User model otomatis (AD-16) — `HasRoles` tetap manual install step; `app-bazaar-sandbox`'s User model diedit langsung sebagai instans dari langkah manual itu, bukan lewat mekanisme otomatis package.

**Never:** Tidak mengubah skema tabel roles/permissions (dependency-owned, AD-18).

## I/O & Edge-Case Matrix

| Scenario | Input / State | Expected Output / Behavior | Error Handling |
|----------|--------------|---------------------------|----------------|
| Fresh host, migration belum pernah dipublish | `php artisan bazaar:install` lalu `php artisan migrate` | File `create_permission_tables` ter-publish ke `database/migrations/`, `migrate` sukses bikin tabel `roles`/`permissions`/dst | N/A |
| `bazaar:install` dijalankan dua kali berturut-turut | Migration sudah pernah ter-publish dari run pertama | Run kedua tidak error, tidak dobel-publish file | N/A |

</frozen-after-approval>

## Code Map

- `src/Install/Commands/BazaarInstallCommand.php:46-59` — tag publish salah (`laravel-permission-migrations`), harus `permission-migrations`; tambah satu baris info() soal `HasRoles`.
- `vendor/spatie/laravel-package-tools/src/Concerns/PackageServiceProvider/ProcessMigrations.php:33` — referensi read-only: bukti tag sebenarnya `"{$package->shortName()}-migrations"`.
- `tests/Feature/Install/BazaarInstallCommandTest.php` — tambah test yang assert file migration `create_permission_tables` benar ter-publish ke `database_path('migrations')`, guard regresi tag salah.
- `README.md` — tambah bagian singkat syarat manual `HasRoles` trait (AD-16), belum ada sama sekali saat ini.
- `src/User/Filament/Resources/UserResource.php`, `RoleResource.php` — TIDAK diubah; sudah benar begitu tabel `roles` ada.
- `/Users/mastin/devphp-valet/app-bazaar-sandbox/app/Models/User.php` — repo terpisah; tambah `use Spatie\Permission\Traits\HasRoles;` (satu baris, pola identik `workbench/app/Models/User.php`).

## Tasks & Acceptance

**Execution:**
- [x] `src/Install/Commands/BazaarInstallCommand.php` -- ganti tag jadi `permission-migrations`, tambah info() soal `HasRoles` trait -- root cause fix + tutup onboarding gap.
- [x] `tests/Feature/Install/BazaarInstallCommandTest.php` -- test baru: assert migration `create_permission_tables` ter-publish (dan tidak dobel saat `bazaar:install` dijalankan dua kali) -- cegah regresi tag salah.
- [x] `README.md` -- tambah bagian "Manual install step" soal `HasRoles`.
- [x] `/Users/mastin/devphp-valet/app-bazaar-sandbox/app/Models/User.php` -- tambah `use Spatie\Permission\Traits\HasRoles;` -- keputusan manusia di Open Questions, supaya sandbox verifikasi live tidak error lagi.

**Acceptance Criteria:**
- Given host app baru menjalankan `bazaar:install` lalu `migrate`, when developer membuka halaman Roles list, then halaman render normal (bukan 500 `relation "roles" does not exist`).
- Given tabel roles/permissions sudah ter-migrate dan User model host sudah pakai `HasRoles`, when developer membuka halaman edit User, then form ter-render dengan CheckboxList roles terisi tanpa error.
- Given `bazaar:install` dijalankan dua kali berturut-turut, when command kedua dijalankan, then tidak error dan tidak dobel-publish file migration.

## Implementation Notes

Fixing the tag surfaced a latent test-isolation gap in `tests/TestCase.php`: once `vendor:publish` actually publishes (it was a silent no-op before), the published `..._create_permission_tables.php` file lands in the shared on-disk Testbench skeleton (`vendor/orchestra/testbench-core/laravel/database/migrations/`), which persists across every test in the same Pest process. `DomainMigrationTest`'s plain `php artisan migrate` then picked it up and collided with the `roles`/`permissions` tables `TestCase::setUp()` creates directly (outside the migrator) for every test. Fixed by purging any such leaked file at the top of `setUp()`, so each test starts clean regardless of run order — test-harness-only change, no production code or schema touched (AD-18 intact).

Verified end-to-end in `app-bazaar-sandbox` (path-repo-linked, so it already picks up the fix): `bazaar:install` run twice in a row is idempotent (single migration file, no duplication), `migrate` reports "Nothing to migrate" on the second pass, `Schema::hasTable('roles')`/`('permissions')` are both `true`, and `$user->roles()` (the exact call `CheckboxList::make('roles')` hydration uses) resolves without the previous `relation "roles" does not exist` error.

Independently re-verified (bmad-build step-03, not just the implementation subagent's own report) via real authenticated browser session against `app-bazaar-sandbox`: `/admin/users/1/edit` renders the Edit User form (Roles field present, no 500), and `/admin/roles` renders the Roles list ("No roles" empty state, no 500) — both matrix rows and both acceptance criteria confirmed visually, closing the one manual-check gap the subagent flagged (it only reached tinker-level verification since its own session was unauthenticated).

## Spec Change Log

## Review Triage Log

- [blind-hunter] `src/Install/Commands/BazaarInstallCommand.php:71` -- pesan info `HasRoles` baru tidak punya test coverage sama sekali. **medium** -- verified: `grep -n "expectsOutput\|HasRoles" tests/Feature/Install/BazaarInstallCommandTest.php` kosong. Ironis karena story ini sendiri tentang menutup silent gap, tapi guidance message barunya sendiri bisa hilang tanpa ketahuan. -> patch.
- [blind-hunter] `src/Install/Commands/BazaarInstallCommand.php:71` -- pesan pakai `$this->components->info()`, padahal README bilang skip langkah ini bikin 500 di produksi; `Warn` component lebih pas. **low** -- real tapi kosmetik, fix trivial (satu kata) jadi tidak direject oleh aturan reject-low. -> patch.
- [blind-hunter] Reminder `HasRoles` tercetak tanpa syarat di setiap run `bazaar:install`, tidak ada pengecekan apakah trait sudah terpasang. **low** -- real tapi fix perlu logic baru (cek trait/reflection) = lebih dari koreksi langsung -> reject (low + fix non-trivial).
- [blind-hunter] `README.md` -- contoh kode `class User { use HasRoles; }` tanpa placeholder trait lain (`HasFactory, Notifiable`, dst), berisiko pembaca copy-paste dan menghapus trait yang sudah ada. **low** -- real, fix trivial (tambah satu kalimat/placeholder) -> patch.
- [blind-hunter] `tests/TestCase.php` -- `@unlink($leakedMigration)` menelan gagal unlink secara diam-diam. **medium** -- verified: kalau unlink gagal, file lama tetap ada dan `glob()` di test regresi baru tetap menemukan file (walau tag balik salah lagi), bikin test regresi false-pass -- persis melemahkan tujuan fix ini sendiri. -> patch.
- [edge-case-hunter] `tests/TestCase.php:54-56` -- klaim sama: unlink gagal -> file leak lama membuat regression test false-pass. **medium** -- verified, root cause sama dengan baris di atas, digabung satu grup untuk routing. -> patch (grouped).
- [blind-hunter] Komentar pembersihan mengasumsikan Pest jalan satu proses, tidak ada guard/catatan untuk `pest --parallel`. **false** -- verified: `grep -rn "parallel" composer.json phpunit.xml*` kosong, repo ini tidak pernah menjalankan test paralel; situasi race condition-nya tidak pernah tercapai.
- [blind-hunter] Purge cuma jalan di `setUp()`, bukan di `tearDown()`/akhir suite -- file publikasi bisa tertinggal di skeleton Testbench antar-invocation `vendor/bin/pest` terpisah. **low** -- real tapi dampak sangat kecil (di dalam satu suite run sudah aman via setUp per-test), pola sama dengan `BazaarInstallCommandTest`'s asset publish lain yang juga tidak dibersihkan (konvensi pre-existing di repo ini); fix perlu tambahan tearDown/after-suite = lebih dari koreksi langsung -> reject (low + fix non-trivial).
- [blind-hunter] Pola glob `*_create_permission_tables.php` terduplikasi literal di 3 file (2 test + `TestCase.php`), tidak ada constant/helper bersama. **low** -- real tapi risiko rendah (rename spatie akan gagal keras, bukan diam-diam) dan fix perlu refactor ke shared constant = lebih dari koreksi langsung -> reject (low + fix non-trivial).

## Verification

**Commands:**
- `vendor/bin/pest tests/Feature/Install` -- expected: semua lulus termasuk test baru
- `vendor/bin/pest` -- expected: tidak ada regresi di 108+ test lain

**Manual checks (if no CLI):**
- Jalankan `bazaar:install` + `migrate` di `app-bazaar-sandbox`, buka `/admin/roles` dan `/admin/users/{id}/edit` via browser, screenshot tanpa error 500.
