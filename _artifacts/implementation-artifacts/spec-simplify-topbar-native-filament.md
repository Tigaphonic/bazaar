---
title: 'Sederhanakan topbar Shell: pakai kontrol native Filament, bukan custom'
type: 'refactor'
created: '2026-09-17'
status: 'done'
route: 'dispatch'
review_loop_iteration: 0
context: []
baseline_commit: 'b96921ba27073db2fa1813068c24cd810a7257dd'
---

<frozen-after-approval reason="human-owned intent — do not modify unless human renegotiates">

## Intent

**Problem:** Sejak Story 1.3 memasang `->login()`, panel Filament merender user-menu native-nya sendiri (avatar, theme switcher bawaan, sudah aktif default). Tapi Shell (Story 1.2) masih memasang kontrol topbar custom sendiri (search/bell/mobile-menu icon buttons + user-icon dengan dropdown theme-toggle+language) via render hook `TOPBAR_END` — dua set kontrol berdampingan tanpa koordinasi: icon custom tidak selaras dengan avatar native, dan dropdown theme-toggle custom bertabrakan dengan dropdown user-menu native. Investigasi konfirmasi: search/mobile-menu/theme-toggle custom 100% redundan dengan yang sudah native & aktif default di Filament v5.8 (`->userMenuItems()`, `HasDarkMode`/theme switcher, `HasGlobalSearch`, native sidebar-toggle button). Language switcher tidak punya padanan native, tapi bisa dipindah ke dalam dropdown user-menu native lewat API resmi `->userMenuItems()`, bukan dropdown terpisah. Notification bell custom selama ini tidak fungsional apa pun (icon kosong) — notifikasi in-app Staff sungguhan sudah direncanakan sebagai Story 2.3 (Epic 2) terpisah di `epics.md`.

**Approach:** Hapus render hook `TOPBAR_END` beserta `resources/views/shell/topbar.blade.php` dan CSS-nya sepenuhnya (search/bell/mobile-menu/user-dropdown). Pindahkan Language switcher jadi satu item di dalam `->userMenuItems()` native Filament, memakai ulang JS client-side yang sudah ada (`window.bazaarShell.setLocale`) tanpa mengubah mekanismenya. Tambah `$recordTitleAttribute` ke `UserResource`/`RoleResource` supaya global search native (sudah aktif default) benar-benar mengembalikan hasil. Tulis ulang test aksesibilitas topbar yang lama (yang me-render view yang dihapus) supaya sesuai realita baru.

**Keputusan (dijawab manusia):** Notification bell dihapus dulu (bukan diganti `->databaseNotifications()` native Filament) — bell kosong yang ada sekarang tidak melakukan apa-apa, dan notifikasi in-app sungguhan (butuh infrastruktur baru: tabel `notifications` + trait `Notifiable`) memang scope Story 2.3, bukan story ini.

## Boundaries & Constraints

**Always:** Mekanisme persistence `UserPreferences`/`ApplyUserLocale` (AD-18, Bazaar-owned `bazaar_user_preferences` table) tetap utuh tidak disentuh — hanya UI pemicunya yang pindah tempat. Render hook `SIDEBAR_NAV_START` (branding/live-dot sidebar) tetap ada, tidak termasuk scope ini.

**Never:** Tidak membangun Staff Notification Center sungguhan (bell asli, badge unread, dropdown) — itu Story 2.3 Epic 2. Tidak memperbaiki gap wiring persistence theme/locale ke request nyata (lihat Open finding di Tasks) — itu pre-existing, dicatat terpisah di `deferred-work.md`, bukan diperbaiki di sini.

## I/O & Edge-Case Matrix

| Scenario | Input / State | Expected Output / Behavior | Error Handling |
|----------|--------------|---------------------------|----------------|
| Buka halaman admin manapun | Panel ter-render normal | Topbar hanya berisi kontrol native Filament (search, sidebar-toggle, avatar+user-menu) + satu item "Language" di dalam dropdown user-menu -- tidak ada icon custom Bazaar yang tumpang tindih | N/A |
| Klik item Language di dropdown user-menu | User memilih EN/ID | Label chrome Shell (`[data-i18n]`) berubah instan tanpa reload, sama seperti perilaku lama (localStorage-based) | N/A |
| Ketik query di search native Filament | Query cocok dengan nama User/Role | Hasil pencarian User/Role muncul (karena `$recordTitleAttribute` sudah diset) | N/A |

</frozen-after-approval>

## Code Map

- `src/BazaarServiceProvider.php:123-134` (`registerShellRenderHooks()`) -- hapus registrasi `PanelsRenderHook::TOPBAR_END`; `SIDEBAR_NAV_START` tetap.
- `src/BazaarServiceProvider.php:47-70` (`packageRegistered()`, chain `PanelResolver::resolve($registry)->...`) -- tambah `->userMenuItems([...])` dengan satu Action "Language" yang memanggil `window.bazaarShell.setLocale(...)` (lihat Design Notes).
- `resources/views/shell/topbar.blade.php` -- hapus seluruh file (search/bell/mobile-menu button + user-icon dropdown theme+language).
- `resources/css/shell.css` -- hapus rule mati: `.bazaar-topbar-controls`, `.bazaar-icon-btn` (+ hover/dark varian), `.bazaar-icon`, `.bazaar-icon--{search,bell,menu,user}`, `.bazaar-mobile-menu-btn`, `.bazaar-user-menu*` (dropdown/item/language). Sesuaikan juga selector gabungan focus-ring (baris awal file) yang menyebut class-class ini. JANGAN sentuh `.bazaar-sidebar__*`, `.modal*`, `.tabs*`, `.empty-state*`, `.alert-banner*` (komponen lain, di luar scope).
- `resources/js/shell.js` -- TIDAK diubah; `window.bazaarShell.setLocale/applyLocale` dan I18N dict dipakai ulang oleh Language action yang baru.
- `src/User/Filament/Resources/UserResource.php`, `RoleResource.php` -- tambah `protected static ?string $recordTitleAttribute = 'name';` supaya global search native (aktif default di Filament, `HasGlobalSearch.php:31`) mengembalikan hasil untuk kedua resource ini.
- `tests/Feature/Shell/AccessibilityTest.php:15-25` -- test pertama me-render `bazaar::shell.topbar` yang dihapus; tulis ulang supaya menguji realita baru (kontrol native Filament + Language action), bukan view yang sudah tidak ada.
- `_artifacts/implementation-artifacts/deferred-work.md` -- tambah entri baru mendokumentasikan gap pre-existing: `UserPreferences::setTheme()/setLocale()` sudah ada & teruji terisolasi sejak Story 1.2, tapi tidak pernah benar-benar terhubung ke request/Livewire action nyata manapun (grep `src/` konfirmasi nol caller di luar test) -- di luar scope perubahan ini, sudah begitu sebelum topbar disederhanakan.

## Tasks & Acceptance

**Execution:**
- [x] `src/BazaarServiceProvider.php` -- hapus hook `TOPBAR_END`, tambah `->userMenuItems([...])` dengan Action "Language" -- root fix tumpang tindih kontrol.
- [x] `resources/views/shell/topbar.blade.php` -- hapus file.
- [x] `resources/css/shell.css` -- hapus rule mati terkait kontrol topbar lama, rebuild `resources/dist/shell.css` (`npm run build`).
- [x] `src/User/Filament/Resources/UserResource.php`, `RoleResource.php` -- tambah `$recordTitleAttribute`.
- [x] `tests/Feature/Shell/AccessibilityTest.php` -- tulis ulang test topbar pertama.
- [x] `_artifacts/implementation-artifacts/deferred-work.md` -- tambah entri gap persistence wiring.

**Acceptance Criteria:**
- Given panel admin sudah ter-install dan Staff login, when membuka halaman manapun, then topbar hanya menampilkan kontrol native Filament (tidak ada icon/dropdown custom Bazaar yang tumpang tindih dengan user-menu native).
- Given dropdown user-menu native dibuka, when Staff memilih item Language, then label chrome `[data-i18n]` berubah instan tanpa reload (perilaku sama seperti sebelumnya).
- Given Staff mengetik nama User atau Role yang ada di kotak search native Filament, when hasil dicari, then hasil pencarian muncul.
- Given `tests/Feature/Shell/AccessibilityTest.php` dan seluruh test suite dijalankan, when `vendor/bin/pest` dijalankan berulang dengan random order, then semua lulus tanpa regresi.

## Implementation Notes

- `->label(__('bazaar::shell.language_switcher'))` had to become `->label(fn (): string => __(...))`: the whole `->userMenuItems()` chain runs inside `afterResolving(PanelRegistry::class, ...)`, registered from `packageRegistered()` specifically so it fires during the *register* phase, ahead of Filament's own `boot()`-time `PanelRegistry` resolution (see that method's existing doc comment). In practice that closure can execute before Bazaar's own `hasTranslations()` wiring has booted, so an eager `__()` call silently returned the raw key `"bazaar::shell.language_switcher"` instead of "Language" -- caught by manually inspecting the rendered HTML during verification, not by Pest (a raw untranslated key still satisfies a substring-based `toContain` unless you assert the exact translated string, which the test does). A `Closure` label defers resolution to actual request-time rendering, after all providers have booted.
- `->extraAttributes(['x-on:click' => '...'])` (the Design Notes' first suggestion) silently drops the click handler for a `userMenuItems()` Action with no `->url()`/`->action()`: Filament's own attribute bag for a grouped dropdown item (`Action.php`'s `toGroupedHtml()`) already declares `'x-on:click' => $this->getAlpineClickHandler()` (null when unset) *before* merging in `extraAttributes()`, and Laravel's `ComponentAttributeBag::merge()` keeps the pre-existing bag's value for any non-class/style key -- so the pre-declared `null` wins and the extra `x-on:click` is discarded. The rendered button instead kept Filament's default `wire:click="mountAction('language')"` (a Livewire round-trip), which is exactly what the Design Notes said to avoid. Fixed by using `->alpineClickHandler(...)` instead (`Action.php:321`), which both supplies the real `x-on:click` value directly and internally calls `livewireClickHandlerEnabled(false)`, suppressing the default `wire:click="mountAction(...)"` entirely. Verified by dumping the actual rendered `<button>` markup in a scratch test before and after the fix.
- The Language action renders as a single toggle (click switches to the other of the two supported locales, reading the current locale off `<html lang="...">`), matching the spec's explicit "satu item" (one item) instruction rather than the old two-item EN/ID listbox -- this is a like-for-like port of behavior (still purely client-side, same `window.bazaarShell.setLocale` call), not a new design decision.
- `AccessibilityTest.php`'s rewritten first test requests `/admin/users` (not `/admin`): the panel root redirects (302) to the first registered resource's index page, so asserting against `/admin`'s response body would only see a redirect stub, not the actual topbar markup.
- Filament's native user-menu (and therefore the Language action inside it) only renders when `filament()->auth()->check()` is true (`vendor/filament/filament/resources/views/livewire/topbar.blade.php:275`) -- the rewritten test authenticates via `actingAs($user)` to exercise that real path, same pattern as the existing `UserLocalePreferenceTest.php`.
- Did not add `->login()` to `workbench/app/Providers/Filament/TestPanelProvider.php`: it wasn't in the Code Map, and `actingAs()` + a real authenticated request already exercises the native user-menu's actual render condition (`auth()->check()`) without it. The real host app used for manual verification (`app-bazaar-sandbox`) already has `->login()` wired (`AdminPanelProvider.php`).
- Independently re-verified (bmad-build step-03, not just the implementation subagent's own report) via real authenticated browser session against `app-bazaar-sandbox`: topbar shows only native Filament controls (search box, single avatar) -- no leftover custom icons. Opened the native user-menu dropdown: one dropdown only (profile header, native theme switcher sun/moon/system, the new "Language" item, Sign out) -- no second overlapping dropdown. Clicked "Language" (via element ref, not raw pixel coordinates -- manual raw-coordinate clicks initially missed the target because of a screenshot/DOM-viewport scale mismatch, not a product bug): `<html lang>` flipped `id` -> `en`, `localStorage['bazaar-locale']` updated, and the sidebar's `nav_dashboard` label changed instantly from "Dasbor" to "Dashboard" with no reload -- confirms all three I/O matrix rows and all four acceptance criteria hold in a live browser, not just in Pest.

## Spec Change Log

## Review Triage Log

- [blind-hunter] `tests/Feature/Shell/AccessibilityTest.php` -- test baru tidak lagi assert `aria-label` apa pun, padahal docblock-nya masih mengutip EXPERIENCE.md's accessibility floor sebagai alasan. **medium** -- verified: dibaca ulang, test hanya cek absennya class mati + kehadiran `fi-topbar`/`fi-user-menu-trigger`, tidak pernah assert accessible-name nyata. Language action sendiri tidak icon-only (sudah punya label teks visible) jadi tidak ada regresi nyata hari ini, tapi regresi masa depan (mis. Language jadi icon-only lagi) tidak akan tertangkap. -> patch.
- [blind-hunter] Language switcher lama (listbox EN/ID dengan `aria-selected` di locale aktif) kehilangan indikasi locale mana yang sedang aktif -- toggle baru cuma satu tombol statis. **low** -- real, tapi spec's frozen Intent eksplisit memutuskan "satu item" (bukan listbox dua opsi) sebagai desain yang disetujui manusia -- mengubahnya balik berarti merenegosiasi intent yang sudah frozen, bukan patch. -> reject (konsekuensi desain yang sudah disetujui, bukan defect).
- [blind-hunter] Toggle di-hardcode biner EN<->ID, tidak iterate daftar locale seperti dropdown lama -- regresi desain kalau locale ke-3 ditambah. **false** -- verified: `EXPERIENCE.md`/`epics.md` cuma pernah menyebut EN/ID, tidak ada requirement multi-locale >2 di mana pun di planning artifacts; situasi yang dikhawatirkan tidak pernah tercapai oleh product scope saat ini.
- [blind-hunter] + [edge-case-hunter] `resources/lang/{en,id}/shell.php` -- key `search`/`notifications`/`theme_toggle`/`user_menu`/`menu` jadi mati (satu-satunya caller, `topbar.blade.php`, sudah dihapus). **medium** -- verified: `grep -rn` di `resources/`, `src/`, `tests/` untuk kelima key itu kosong total. -> patch (hapus 5 key dari kedua file, sisakan `language_switcher` dkk, jaga `TranslationParityTest.php` tetap lulus).
- [blind-hunter] Diff yang disodorkan ke reviewer cuma diffstat untuk `resources/dist/shell.css` + `topbar.blade.php` ditempel sebagai raw content, bukan hunk diff `git` yang proper -- sulit diverifikasi independen dari file itu saja. **false** -- ini komplain soal cara saya (koordinator) menyusun diff-file untuk reviewer, bukan defect di kode yang di-review; saya sendiri sudah independently verify CSS rebuild (631038 bytes, 0 dead class) dan penghapusan file lewat `git status` sebelum sesi review ini.
- [blind-hunter] Test baru hardcode route `/admin/users` dengan komentar asumsi soal redirect panel-root, bukan `UserResource::getUrl('index')` -- rapuh kalau urutan resource berubah. **low** -- real, tapi fix trivial (satu baris ganti ke `getUrl()`) jadi tidak lolos aturan reject-low. -> patch.
- [blind-hunter] Test baru panggil `User::create()` langsung dengan field minimal, dikhawatirkan ada field wajib lain yang terlewat. **false** -- verified: pola identik persis sudah dipakai & lulus di `UserLocalePreferenceTest.php`/`UserThemePreferenceTest.php` yang sudah ada di suite ini.
- [blind-hunter] `$recordTitleAttribute` di `UserResource`/`RoleResource` dianggap scope creep di luar "topbar simplification". **false** -- verified: perubahan ini eksplisit tercantum di frozen Approach + Code Map + Tasks spec ini sendiri, bukan sisipan diam-diam.
- [blind-hunter] Entri `deferred-work.md` baru soal gap wiring persistence tidak menyebut kapan/lewat story apa akan diselesaikan. **low** -- real, tapi tidak ada story masa depan yang diketahui untuk dikutip (bukan technical debt yang sudah terjadwal); fix bukan koreksi langsung. -> reject.
- [blind-hunter] Komentar panjang soal `->alpineClickHandler()` vs `->extraAttributes()` tidak dijaga test regresi kalau internal Filament berubah lagi. **false** -- verified: kalau regresi ke `extraAttributes()` yang rusak terjadi, `wire:click="mountAction(...)"` akan menggantikan `x-on:click`, sehingga string `'setLocale'` tidak akan muncul lagi di HTML -- `AccessibilityTest.php`'s assertion `toContain('setLocale')` yang sudah ada SUDAH menjaga skenario ini.
- [edge-case-hunter] `src/BazaarServiceProvider.php` -- klik Language sebelum `window.bazaarShell` ter-register (timing race shell.js) bikin klik silently no-op lewat guard `&&`, tanpa feedback ke Staff. **low** -- real tapi jendela race sangat sempit (baru bisa diklik setelah Staff sempat buka dropdown, jauh setelah script termuat) dan self-recovering di klik berikutnya. -> reject.
- [verification-gap] `src/User/Filament/Resources/RoleResource.php`/`UserResource.php` -- penambahan `$recordTitleAttribute` (fitur global search native yang jadi pengganti search custom) nol test coverage; regresi (attribute salah ketik/kehapus) akan lolos diam-diam. **medium**, pre-verified oleh reviewer. -> patch (tambah test `getGlobalSearchResults()` untuk kedua resource).

## Design Notes

Filament v5.8's `->userMenuItems()` (`Panel/Concerns/HasUserMenu.php:36`) menerima array `Filament\Actions\Action`. Karena locale switching Shell murni client-side (localStorage + DOM swap via `window.bazaarShell.setLocale('id')`, tidak ada server round-trip), item Language sebaiknya TIDAK memakai `->action(fn () => ...)` (itu memicu Livewire request PHP) -- pakai `->extraAttributes(['x-on:click' => "window.bazaarShell.setLocale('id')"])` atau pendekatan Alpine serupa supaya tetap murni client-side seperti perilaku lama. `Action::sort(< 0)` merender item sebelum theme switcher bawaan, sort non-negatif merender sesudahnya (`HasUserMenu.php:21-25`).

## Verification

**Commands:**
- `npm run build` -- ran. `resources/dist/shell.css` rebuilt (Tailwind v4.3.3, 244ms) with zero occurrences of `bazaar-topbar-controls`/`bazaar-icon-btn`/`bazaar-user-menu` remaining (`grep -c` confirmed 0). `resources/dist/shell.js` rebuilt unchanged (source `shell.js` untouched).
- `vendor/bin/pest` -- ran. 111 passed, 31 skipped (pre-existing Story 1.5 Audit Trail red-phase scaffolds, unrelated to this change), 0 failed, 280 assertions.
- `vendor/bin/pest --order-by=random` -- ran 3x with different seeds. All 3 runs: 111 passed, 31 skipped, 0 failed.
- Ad-hoc scratch check (not committed): `UserResource::canGloballySearch()` / `RoleResource::canGloballySearch()` both now return `true`, and `getGloballySearchableAttributes()` returns `['name']` for both -- confirms AC3 (native search returning User/Role results) is satisfied by the `$recordTitleAttribute` addition, beyond what `AccessibilityTest.php` itself asserts.
- Ad-hoc scratch check (not committed): dumped the actual rendered `<button>` markup for the Language action from a real `actingAs()->get('/admin/users')` request, confirming `x-on:click="window.bazaarShell && window.bazaarShell.setLocale(...)"` is present and `wire:click="mountAction('language')"` is absent (see Implementation Notes for why the first `->extraAttributes()` attempt failed this check).

**Manual checks (if no CLI):**
- Not performed by this agent (no browser tooling in this session). `app-bazaar-sandbox` (the real host app with `->login()` wired, at `/Users/mastin/devphp-valet/app-bazaar-sandbox`) is the right place to confirm visually: topbar shows only native Filament chrome, the Language item lives inside the native user-menu dropdown, clicking it swaps `[data-i18n]` labels instantly with no reload, and no second/overlapping dropdown appears. Left for the human or a future browser-tooling session.
