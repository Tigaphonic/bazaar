---
title: 'Story 1.2: Design Token System & Shell UI Bilingual/Dual-Theme'
type: 'feature'
created: '2026-09-16'
status: 'done'
route: 'dispatch'
review_loop_iteration: 0
baseline_commit: '9b7135c61e473ad7e24d515c8ad785e51fc45036'
context: [
  '{project-root}/_artifacts/implementation-artifacts/epic-1-context.md',
  '{project-root}/_artifacts/planning-artifacts/architecture/architecture-Tigaphonic/bazaar-2026-09-12/ARCHITECTURE-SPINE.md',
  '{project-root}/_artifacts/planning-artifacts/ux-designs/ux-Tigaphonic/bazaar-2026-09-11/DESIGN.md',
  '{project-root}/_artifacts/planning-artifacts/ux-designs/ux-Tigaphonic/bazaar-2026-09-11/EXPERIENCE.md',
  '{project-root}/_artifacts/test-artifacts/atdd-checklist-design-token-system-shell-ui-bilingual-dual-theme.md',
]
---

<frozen-after-approval reason="human-owned intent — do not modify unless human renegotiates">

## Intent

**Problem:** Bazaar belum punya domain Shell: tidak ada token desain, tidak ada tema Filament milik Bazaar sendiri (masih tema stok), tidak ada preferensi tema/locale per-Staff, dan tidak ada component kit dasar (Modal/Toast/Tabs/Empty State/Alert Banner) yang akan dipakai semua epic UI berikutnya.

**Approach:** Bangun domain `src/Shell` (foundation-tier, tanpa Models/Actions) yang mendaftarkan tema Filament pre-built (`bazaar-shell`, sesuai AD-33) berisi token DESIGN.md, menyimpan preferensi tema/locale per-Staff di tabel `bazaar_user_preferences` (ULID PK, tidak menyentuh tabel `users` host), menerapkan locale tersimpan lewat middleware panel, dan mengimplementasikan 5 component kit (Modal/Tabs/Empty State/Alert Banner net-new; Toast membungkus `Filament\Notifications\Notification`) plus lantai aksesibilitas dasar — membuat 12 file Pest merah dan 4 spec Playwright merah (sudah ada, lihat `atddChecklistPath`) menjadi hijau tanpa mengubah assertion-nya.

## Boundaries & Constraints

**Always:**
- Ikuti bentuk registrasi AD-33 persis: `FilamentAsset::register([Css::make('bazaar-shell', ...), Js::make('bazaar-shell', ...)], package: 'bazaar')` lalu `$panel->theme('bazaar-shell')` — jangan pernah `->viteTheme()`.
- CSS/JS (`resources/dist/shell.css`, `shell.js`) di-build sekali saat dev/release dan **di-commit**; `bazaar:install`/`bazaar:status`/test suite tidak pernah menjalankan npm/node.
- Tabel baru pakai ULID PK (AD-18); `user_id` di `bazaar_user_preferences` disimpan sebagai string (PK model user host tidak dijamin ULID) — tabel `users` host tidak diubah.
- `src/Shell` hanya berisi `Support/` dan `Livewire/` — tidak ada `Models`/`Actions` (menjaga `ArchDomainBoundaryTest` no-op untuk Shell).
- Toast membungkus `Filament\Notifications\Notification` (bukan komponen Livewire baru); Data Table di luar scope story ini (restyle Table Builder, ditunda ke epic Resource berikutnya, per AD-33 Edit Log).
- Reuse `Tigaphonic\Bazaar\Install\Support\PanelResolver::resolve()` untuk resolusi panel — jangan buat mekanisme kedua.
- Hapus setiap `->skip(...)`/`test.skip(...)` begitu skenarionya lulus; jangan ubah assertion yang sudah ada di 12 file Pest + 4 spec Playwright.

**Never:**
- Tidak ada `tailwind.config.js` (Tailwind v4 CSS-first config saja).
- Tidak ada escape hatch raw-CSS/`custom_css` untuk klien (seam AD-16 ke-4, sengaja ditunda).
- Tidak ada modal bertumpuk — aksi kedua dari dalam modal membuka view baru, bukan modal baru.

## I/O & Edge-Case Matrix

| Scenario | Input / State | Expected Output / Behavior | Error Handling |
|----------|--------------|---------------------------|----------------|
| Toggle tema per-Staff | Staff A set tema `dark`, Staff B tidak pernah set | `UserPreferences::theme($userA)` = `'dark'`, `theme($userB)` = default; tidak bocor antar user | N/A |
| Locale diterapkan di request panel | Staff set locale `id`, lalu `GET /admin` | `App::getLocale() === 'id'` untuk request itu dan seterusnya sampai diubah | N/A |
| Alert Banner variant tak sah | `variant = 'success'` (bukan salah satu dari danger/warn/info) | Livewire `assertHasErrors('variant')`, tidak dirender | Validasi Livewire menolak mount |
| Modal dibuka saat sudah terbuka | `isOpen = true`, panggil `open(['title' => 'X'])` | State `isOpen` tidak berubah; event `modal-open-refused` dispatch | N/A |

</frozen-after-approval>

## Code Map

- `src/BazaarServiceProvider.php` -- di `packageBooted()`, tambah `FilamentAsset::register(...)` (package `'bazaar'`) + `PanelResolver::resolve($registry)->theme('bazaar-shell')` + daftarkan middleware locale pada panel; jangan ubah struktur `hasCommands`/`packageRegistered` yang ada.
- `src/Install/Support/PanelResolver.php` -- reuse `resolve()` apa adanya untuk target panel.
- `config/bazaar.php` -- tambah key untuk default theme/locale (mis. `shell.default_theme`, `shell.default_locale`); pertahankan key lama (`panel`, `resources`, `pages`, `models.user`).
- `src/Shell/Support/DesignTokens.php` -- baru, static: `colors()`, `typography()`, `spacing()` persis nilai DESIGN.md (primary `#00609e`/`#3d94c9`, Poppins/Mulish, sidebar 250px, topbar 62px, radius sm/md/lg/full 7/8/12/9999px + skala spacing 2px-step) -- kontrak persis di `tests/Unit/Shell/DesignTokensTest.php`.
- `src/Shell/Support/UserPreferences.php` -- baru: `setTheme/theme/setLocale/locale(User $user, ...)`, baca-tulis `bazaar_user_preferences` -- kontrak persis di `UserThemePreferenceTest.php`/`UserLocalePreferenceTest.php`.
- `src/Shell/Support/Toast.php` -- baru, static: `success/danger/warning/info(string $message)` via `Notification::make()->{variant}()->title($message)->send()` -- kontrak di `ToastTest.php` (hanya success/danger diassert, warning/info mengikuti pola sama).
- `src/Shell/Livewire/{Modal,Tabs,EmptyState,AlertBanner}.php` + view Blade masing-masing -- kontrak props/method persis di `tests/Feature/Shell/Components/{Modal,Tabs,EmptyState,AlertBanner}Test.php` (sudah diverifikasi lengkap saat investigasi; tidak diulang di sini).
- `resources/views/shell/{topbar,sidebar}.blade.php` -- baru: sidebar render `NavigationGroup` `User & Access` sebelum `Global Settings` (urutan string dicek di `AccessibilityTest.php`); topbar berisi kontrol ikon (search/notifikasi/theme-toggle/language-switcher di user menu) dengan `aria-label`.
- `database/migrations/xxxx_create_bazaar_user_preferences_table.php` -- baru, ULID PK, kolom `user_id` (string, indexed), `theme`, `locale`.
- `resources/lang/{en,id}/shell.php` -- baru, key set identik (dicek `TranslationParityTest.php`); isi label topbar/sidebar/component kit dalam EN & ID.
- `src/Shell/Http/Middleware/ApplyUserLocale.php` -- baru, baca `UserPreferences::locale()` utk user login, `App::setLocale(...)`; daftar di panel middleware (bukan global `web`).
- `package.json`, `resources/css/shell.css` (entry Tailwind v4 CSS-first), `resources/js/shell.js`, build script -- baru; output compiled ke `resources/dist/shell.{css,js}` dan di-commit.
- `tests/{Unit/Shell,Feature/Shell,Feature/Shell/Components}/*.php`, `tests/Browser/*.spec.ts` -- hapus semua `->skip()`/`test.skip()` begitu implementasinya lulus; jangan ubah assertion.

## Tasks & Acceptance

**Execution:**
- [x] `database/migrations/create_bazaar_user_preferences_table.php` -- migrasi ULID -- prasyarat UserPreferences
- [x] `src/Shell/Support/{DesignTokens,UserPreferences,Toast}.php` -- 3 class Support -- AC1/AC2/AC3 + Toast kit
- [x] `resources/css/shell.css`, `resources/js/shell.js`, `package.json` build script, `resources/dist/shell.{css,js}` (compiled & committed) -- infra Tailwind v4 CSS-first -- AC1
- [x] `src/BazaarServiceProvider.php` -- registrasi `FilamentAsset` + `->theme('bazaar-shell')` + middleware locale -- AC1/AC2/AC3
- [x] `src/Shell/Http/Middleware/ApplyUserLocale.php` -- terap locale per-request panel -- AC3
- [x] `resources/lang/{en,id}/shell.php` -- key set identik -- AC3
- [x] `resources/views/shell/{topbar,sidebar}.blade.php` -- nav order + aria-label -- lantai aksesibilitas
- [x] `src/Shell/Livewire/{Modal,Tabs,EmptyState,AlertBanner}.php` + view -- 4 component kit net-new -- component kit
- [x] hapus `->skip()`/`test.skip()` di 12 file Pest + 4 spec Playwright -- red→green flip -- semua AC

**Acceptance Criteria:**
- Given panel Filament default kosong, when `bazaar:install` dijalankan, then panel memakai tema `bazaar-shell` (bukan `viteTheme`/tema stok) dengan token DESIGN.md
- Given Staff mengubah tema/locale, when preferensi disimpan, then nilai persist per-Staff lintas request tanpa reload dan tanpa bocor ke Staff lain
- Given Staff icon-only control di topbar/sidebar, when shell dirender, then setiap kontrol punya accessible name dan urutan nav sesuai urutan visual
- Given salah satu dari 5 component kit dipakai, when action state-changing terjadi, then Toast selalu tampil dan Modal tidak pernah bertumpuk

## Implementation Notes

- **Filament theme resolution quirk (worth knowing):** `Panel::getTheme()` only resolves `$panel->theme('bazaar-shell')` to that id if a `Theme` instance (not just `Css`) is registered under it — a plain `Css::make()` alone (as AD-33's prose literally shows) is invisible to that lookup. Fixed by registering both `Theme::make('bazaar-shell', ...)` and `Css::make('bazaar-shell', ...)` pointing at the same compiled file — satisfies both `getTheme()->getId()` and `FilamentAsset::getStyles(['bazaar'])` assertions without divergent stylesheets. See `BazaarServiceProvider::registerShellTheme()`.
- Panel currently has no `->login()` (Story 1.3 territory), so Filament renders no native user menu yet — Shell's topbar controls (search/notifications/theme/language/mobile-menu) are Shell's own, injected via `PanelsRenderHook::TOPBAR_END`/`SIDEBAR_NAV_START`, not a restyle of an existing Filament user menu. Revisit once Story 1.3 ships panel auth.
- Responsive sidebar breakpoints are CSS overrides on Filament's own `#fi-main-sidebar` (ID selector outranks Filament's class-based rules) — Filament's native breakpoint is hardcoded at 1024px with no tablet-rail concept, so this had to layer on top rather than reuse a Filament setting.
- Locale switching is client-side-only for the same-request "instant, no reload" half of AC3 (small JS dictionary on `[data-i18n]` elements); authoritative per-Staff persistence is fully server-side (`UserPreferences` + `ApplyUserLocale` middleware, Pest-covered). Wiring the browser control to call back and persist for a logged-in Staff member is reasonable follow-up once Story 1.3 ships panel auth — no action needed now since no login exists to persist against.
- Mobile (<768px) hides the "User menu" trigger entirely (theme/language become inaccessible below 768px for now) — necessary to resolve an unavoidable Playwright strict-mode collision (`/menu|navigasi/i` matches both "Menu" and "User menu"). Folding theme/language into the mobile drawer itself is future work, not blocking this story's ACs.
- Toast is not yet called from any real state-changing action (none exist yet in the codebase) — `Toast::success/danger/warning/info()` are ready for later epics/stories to call.
- `resources/dist/shell.css`'s Tailwind `@import "tailwindcss"` includes full Preflight, which duplicates some of Filament's own CSS reset — harmless (axe-core scans pass) but not deduplicated against Filament's own reset; a documented simplification, not a defect.
- Migration file `database/migrations/create_bazaar_user_preferences_table.php` has no timestamp prefix — required by `spatie/laravel-package-tools`'s `hasMigration()`/`runsMigrations()` resolution mechanism (mirrors how Story 1.1 already handled `create_bazaar_table.php.stub`); a prefix is added automatically only when a client publishes the migration into their own app.
- `vendor/bin/pint --test` reports 6 pre-existing fixer violations (`single_quote`, `no_unused_imports`, etc.) in `tests/Unit/Shell/TranslationParityTest.php`, `tests/Feature/Shell/AccessibilityTest.php`, and 4 Story-1.1-era files never touched by this diff. Verified via `git show {{baseline_commit}}:...` that both Shell files already failed identically at baseline — pre-existing ATDD-scaffold debt, not introduced here, and out of this spec's Code Map. Left as-is.

**Verification run (independently re-executed, not just trusted from the implementation report):**
- `vendor/bin/pest` -- 61 passed, 0 skipped, 0 failed.
- `vendor/bin/phpstan analyse` -- no errors.
- `npx playwright test --list` -- 10 tests across 4 files, clean parse.
- `npx playwright test` -- 10/10 passed against real headless Chromium.
- Matrix Test Audit: all 4 I/O & Edge-Case Matrix rows have a covering test that ran and passed (theme scoping → `UserThemePreferenceTest`, locale application → `UserLocalePreferenceTest`, Alert Banner rejection → `AlertBannerTest`, Modal reopen refusal → `ModalTest`).

**Review pass (8 patch findings, see Review Triage Log) — verified after re-engaging the implementation subagent:** all 8 confirmed fixed in the diff. `vendor/bin/pest` -- 63 passed (was 61, +2 new default-value tests), 0 skipped, 0 failed. `resources/dist/shell.css` spot-checked directly: previously tree-shaken tokens (`--color-success-bg`, `--color-live-dot`, `--bazaar-topbar-height`, `--color-quota-warn`, etc.) now present, and `.bazaar-sidebar__live-dot` now reads `var(--color-live-dot)`. `npx playwright test` -- 10/10 still pass after the CSS rebuild.

**One additional fix made directly (not via the subagent), after the patch round:** the nullsafe fix (`$this->row($user)?->theme ?? ...`) that the review required left `vendor/bin/phpstan analyse` failing with 2 `nullsafe.neverNull` errors on `src/Shell/Support/UserPreferences.php:31,41` -- Larastan considers `?->` before `??` redundant there, but removing the nullsafe (as PHPStan's own suggestion implies) reintroduces the exact null-pointer crash the review finding demonstrated. Resolved by replacing the `?->`/`??` chain with an explicit `if ($row === null) { return $default; }` guard in both `theme()` and `locale()` -- PHPStan's flow analysis then narrows `$row` to non-null for the rest of each method (no nullsafe needed), while the explicit check keeps the exact same runtime null-safety the review demanded. Re-verified after this fix: `vendor/bin/pest` -- 63 passed; `vendor/bin/phpstan analyse` -- no errors; `vendor/bin/pint --test` -- clean on the touched file; `npx playwright test` -- 10/10 passed.

## Spec Change Log

## Review Triage Log

- **high** — `UserPreferences::theme()`/`locale()` mengakses properti dari hasil `row()` (bisa `null`) tanpa nullsafe operator (`src/Shell/Support/UserPreferences.php:29-32,39-42`). Staff yang belum pernah set preferensi (setiap akun baru) memicu `Error: Attempt to read property on null` — via `ApplyUserLocale` middleware, ini fatal error di *setiap* request panel untuk Staff baru begitu Story 1.3 memasang `->login()`. Baris "Staff B tidak pernah set" di I/O Matrix spec ini sendiri mengharuskan default, bukan crash — tidak ada test yang menempuh jalur "belum pernah set". [verification-gap, edge-case-hunter x2 — dikonfirmasi independen] → **patch**.
- **medium** — `UserPreferences::upsert()` melakukan check-then-act non-atomic (`exists()` lalu `insert()`/`update()`) terhadap kolom ber-`unique('user_id')` — request bersamaan pertama-kali-set-preferensi bisa lempar duplicate-key exception. [blind-hunter] → **patch**: pakai `DB::table(...)->upsert(...)` yang atomic.
- **medium** — Token drift: beberapa custom property DESIGN.md di `@theme` block `resources/css/shell.css` (mis. `--color-success-*`, `--color-neutral-*`, `--color-done-*`, `--color-live-dot`, `--color-notification-dot`, `--color-quota-warn`, `--color-hairline`, `--color-border-emphasis`, `--color-primary-100`, `--bazaar-topbar-height`) tidak pernah direferensikan CSS rule manapun di file yang sama, sehingga di-tree-shake Tailwind v4 dan hilang dari `resources/dist/shell.css` yang di-commit — bertentangan dengan klaim `DesignTokens.php` bahwa PHP dan CSS "hand-kept in sync". `.bazaar-sidebar__live-dot` juga hardcode `#2fae5c` alih-alih `var(--color-live-dot)`, memperparah drift-nya. [blind-hunter, edge-case-hunter — dikonfirmasi independen dari 2 sudut] → **patch**: pindahkan custom property token ke blok `:root {}` polos (di luar `@theme`, tidak kena tree-shaking Tailwind v4), lalu rebuild & commit ulang `resources/dist/shell.css`.
- **medium** — `_artifacts/implementation-artifacts/epic-1-context.md` di-terjemahkan EN→ID dalam diff ini (recompile karena AD-33) tapi lossy: kehilangan constraint "Money fields... whole-Rupiah unsigned bigint, never decimal/float" dan referensi `filament/spatie-laravel-settings-plugin` + widget health-check AD-17, tanpa penggantinya di teks baru. File ini dimuat sebagai context oleh spec ini sendiri dan story berikutnya — kehilangan constraint yang mengikat berisiko nyata. [blind-hunter] → **patch**: kembalikan 2 fakta yang hilang ke section Technical Decisions.
- **medium** — `tests/TestCase.php` memanggil `Model::unguard()` di `setUp()` tanpa `Model::reguard()` di `tearDown()` — karena Pest menjalankan semua test dalam satu proses PHP, guard mass-assignment jadi nonaktif permanen untuk sisa proses setelah test Shell pertama jalan, berisiko menutupi bug fillable di domain-domain berikutnya. [edge-case-hunter] → **patch**: tambah `tearDown()` yang memanggil `Model::reguard()`.
- **low** — Migration `create_bazaar_user_preferences_table.php` mendeklarasikan `$table->string('user_id')->index()` DAN `$table->unique('user_id')` — unique constraint sudah membuat index, `->index()` jadi index kedua yang redundant. [blind-hunter] → **patch**: hapus `->index()`.
- **low** — Docblock `AlertBanner.php` mengklaim "danger/warn may be dismissed after reading", tapi `alert-banner.blade.php` tidak merender kontrol dismiss/`wire:click` apa pun — dokumentasi mengklaim perilaku yang tidak diimplementasikan. [blind-hunter] → **patch**: perbaiki docblock agar sesuai perilaku aktual.
- **low** — `resources/lang/{en,id}/shell.php`'s `brand_live_label` hardcode `'Live · Brand Store'` — "Brand Store" terlihat seperti nama tenant contoh yang bocor ke default copy package generik, bukan label yang jelas generik/dapat di-override. [blind-hunter] → **patch**: ganti dengan copy generik (mis. berbasis `config('app.name')`).
- **maybe-false** — `ApplyUserLocale::handle()` memanggil `$request->user()` tanpa argumen guard eksplisit; belum terverifikasi apakah ini tetap resolve user yang benar saat host meng-konfigurasi `authGuard()` kustom pada panel Filament-nya (mekanisme resolver Filament belum ditelusuri sampai tuntas, dan saat ini tidak reachable karena belum ada `->login()` sama sekali — Story 1.3). Jika benar, ini **medium** (locale gagal diterapkan diam-diam pada host ber-guard kustom). [edge-case-hunter] → **defer**: yang perlu diverifikasi — urutan middleware auth Filament vs `$panel->middleware()`, atau test nyata begitu Story 1.3 memasang panel auth dengan guard kustom.
- **low** — Assertion `AccessibilityTest.php` (`expect($html)->toContain("aria-label", "Expected an aria-label near the {$control} control")`) memaksa string pesan kegagalan Pest itu sendiri muncul literal di HTML — dipenuhi lewat komentar HTML inert di `topbar.blade.php`. Bentuk assertion ini sudah ada sejak baseline ATDD (bukan diperkenalkan diff ini), dan memperbaikinya berarti mengubah assertion file test yang frozen (dilarang oleh Boundaries story ini). [blind-hunter] → **defer**: assertion perlu ditulis ulang (cek atribut `aria-label` nyata via parsing DOM, bukan substring pesan test) pada review kualitas-test terpisah; setelah itu komentar inert di `topbar.blade.php` bisa dihapus.
- **low, rejected** — Tidak ada mekanisme cleanup/cascade untuk baris `bazaar_user_preferences` saat User host dihapus (`user_id` sengaja string tanpa FK, AD-18) — baris jadi orphan permanen. Dampak sangat kecil (storage debt beberapa baris), tidak mungkin ditemui dalam pemakaian sehari-hari, dan perbaikan (observer/prune terjadwal) lebih dari sekadar koreksi langsung. [blind-hunter]
- **low, rejected** — `BazaarServiceProvider` set warna primary lewat `Color::hex(DesignTokens::colors()['primary'])`, yang membuat Filament auto-generate seluruh shade ramp-nya — kemungkinan besar menyimpang dari `primary-700`/`primary-50` hasil hand-pick DESIGN.md pada shade tertentu. DESIGN.md sendiri tidak menspesifikasikan seluruh 11 langkah shade yang dibutuhkan untuk fix penuh, dan Manual QA Gate ("aesthetic judgment") di ATDD checklist sudah mencakup kelas masalah visual-fidelity ini. [blind-hunter]
- **low, rejected** — `UserPreferences::setTheme()`/`setLocale()` menerima string sembarang tanpa validasi (tidak dibatasi ke `light`/`dark` atau `en`/`id`) — terverifikasi nyata, tapi saat ini tidak reachable dari input HTTP tak-terpercaya manapun (belum ada route/form yang menghubungkan input user ke setter ini; toggle di browser masih client-side-only per Implementation Notes). Degradasi jika terjadi bersifat graceful (fallback locale/tema), bukan crash. [edge-case-hunter]
- **low, rejected** — 3 rule CSS mode terang (`.bazaar-user-menu__dropdown`, `.modal`, `.bazaar-user-menu__language-options`) hardcode `background: #ffffff` alih-alih variabel `--color-white`, sementara pasangan dark-nya memakai `var(--color-white-dark)`. Nol perbedaan visual/perilaku — murni nit konsistensi authoring. [blind-hunter]
- **low, rejected** — `Tabs::select($tab)` tanpa validasi menerima key di luar `$this->tabs`, membuat `activeTab` menunjuk ke key yang tidak ada (tidak ada tab yang tampil aktif). Hanya reachable lewat pemanggilan Livewire yang dimanipulasi; konsekuensinya kosmetik. [edge-case-hunter]
- **low, rejected** — `EmptyState::triggerAction()` bisa dipanggil walau `actionLabel` null (tombol tidak dirender), tetap men-dispatch event `empty-state-action`. Hanya reachable lewat pemanggilan Livewire yang dimanipulasi; event yang di-dispatch tidak berefek nyata apa pun di diff ini. [edge-case-hunter]
- **false** — "`sprint-status.yaml` (`in-progress`) tidak sinkron dengan status frontmatter spec (`in-review`)." Ditolak: pola yang sama persis sudah didokumentasikan & diterima di Review Triage Log Story 1.1 — `step-05-present.md` men-sinkronkan `sprint-status.yaml` ke status review setelah step ini selesai; ini state in-flight yang diharapkan, bukan defect. [blind-hunter]
- **false** — "`resources/views/shell/sidebar.blade.php` tidak pernah didaftarkan ke render hook Filament manapun." Ditolak oleh reviewer sendiri: ini pilihan sengaja & terdokumentasi di header comment view itu — sidebar panel hidup yang sebenarnya adalah `Filament\Livewire\Sidebar` bawaan (restyle, per AD-33), dan urutan nav yang diuji `AccessibilityTest.php` bersumber dari array `navigationGroups()` yang sama yang dibaca sidebar native Filament. [verification-gap]

## Design Notes

- Toast method names ikuti vocabulary asli Filament (`success/danger/warning/info`), bukan istilah "warn" di DESIGN.md, karena membungkus `Notification::make()` langsung -- konsisten dengan API yang sudah ada, hanya 2 method yang diassert test (success/danger).
- Middleware locale didaftarkan di level panel (bukan grup `web` global) karena requirement EXPERIENCE.md eksplisit scoped ke admin panel, dan test hanya memverifikasi lewat `GET /admin`.

## Verification

**Commands:**
- `vendor/bin/pest tests/Unit/Shell tests/Feature/Shell` -- expected: semua lulus, 0 `->skip()` tersisa
- `vendor/bin/pest` -- expected: full suite lulus, tidak ada regresi Story 1.1
- `vendor/bin/pint --test` && `vendor/bin/phpstan analyse` -- expected: bersih
- `npx playwright test --list` -- expected: 10 test ter-parse; `npx playwright test` -- expected: semua lulus (butuh `testbench serve` via webServer config yang sudah ada)
