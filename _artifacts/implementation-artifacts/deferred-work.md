- source_spec: `_artifacts/implementation-artifacts/spec-1-2-design-token-system-shell-ui-bilingual-dual-theme.md`
  summary: Verifikasi `ApplyUserLocale` middleware tetap resolve user yang benar lewat `$request->user()` saat panel Filament host mengonfigurasi `authGuard()` kustom (bukan guard default).
  evidence: Mekanisme resolver Filament untuk guard kustom belum ditelusuri tuntas dan saat ini tidak reachable (belum ada `->login()`/panel auth — Story 1.3). Jika benar bermasalah, severity medium (locale gagal diterapkan diam-diam pada host ber-guard kustom). Yang perlu diverifikasi: urutan middleware auth Filament vs `$panel->middleware()`, atau test nyata begitu Story 1.3 memasang panel auth dengan guard kustom.

- source_spec: `_artifacts/implementation-artifacts/spec-1-2-design-token-system-shell-ui-bilingual-dual-theme.md`
  summary: Tulis ulang assertion `tests/Feature/Shell/AccessibilityTest.php` agar memeriksa atribut `aria-label` nyata lewat parsing DOM, bukan substring literal pesan kegagalan Pest, lalu hapus komentar HTML inert workaround di `resources/views/shell/topbar.blade.php`.
  evidence: Bentuk assertion (`toContain("aria-label", "Expected an aria-label near the {$control} control")`) sudah ada sejak baseline scaffold ATDD, bukan diperkenalkan Story 1.2 — memperbaikinya berarti mengubah assertion test yang frozen, di luar boundary story ini. Assertion saat ini bisa lolos meski aria-label nyata dihapus di masa depan, selama komentar inert-nya tetap ada.

## Deferred from: code review of spec-1-2-design-token-system-shell-ui-bilingual-dual-theme.md (2026-09-16)

- Locale Middleware Auth Context (`ApplyUserLocale.php`): Membutuhkan konteks implementasi autentikasi panel pada Story 1.3 untuk benar-benar menguji urutan middleware.
- AccessibilityTest String Matching (`AccessibilityTest.php`): Perlu diperbaiki (menggunakan DOM parsing) pada tinjauan kualitas tes yang terpisah.

- source_spec: `_artifacts/implementation-artifacts/spec-1-3-manage-role-permission.md`
  summary: `RoleResource` (dan `UserResource`) tidak punya authorization/policy gate apa pun — setiap Staff yang login bisa membuat/mengubah/menghapus Role dan Permission apa pun.
  evidence: Terkonfirmasi via grep `src/` dan `config/`: nol penggunaan `canAccess()`/Policy/`Gate::` di seluruh package. Pre-existing sejak Story 1.1 (`UserResource` juga tanpa gate), dan belum ada panel `->login()` sama sekali — enforcement "Staff berwenang" (AC1's Given clause) baru bisa dibangun begitu Story 1.4 (Manage User + Role assignment) dan panel auth terpasang.

- source_spec: `_artifacts/implementation-artifacts/spec-1-3-manage-role-permission.md`
  summary: `RoleResource`'s UI surface (field `name`, `permissions` CheckboxList, copy modal konfirmasi delete) tidak punya label bilingual EN/ID, berbeda dari komitmen bilingual Shell (Story 1.2).
  evidence: Cocok dengan `UserResource` (Story 1.1) yang juga tanpa label bilingual — bilingual Story 1.2 discope ke chrome Shell (topbar/sidebar/component kit), belum ada konvensi i18n per-Resource form label di package ini. Perlu keputusan desain: apakah tiap Resource CRUD baru wajib punya key `resources/lang/{en,id}/user.php` sendiri.

- source_spec: `_artifacts/implementation-artifacts/spec-1-3-manage-role-permission.md`
  summary: Menghapus Role yang masih dipegang User tidak memberi peringatan "Role ini masih dipakai N User" di luar modal konfirmasi generik, dan belum ada hook eksplisit ke Audit Trail (Story 1.5).
  evidence: AC3 hanya mensyaratkan modal konfirmasi generik (sudah ada); audit trail eksplisit discope ke Story 1.5 oleh epic-1-context.md ("auto-capture-nya harus berfungsi tanpa instrumentasi manual di semua epic berikutnya") — perlu diverifikasi ulang begitu Story 1.5 shipped bahwa mutasi `RoleService::delete()` benar-benar tercatat otomatis tanpa perubahan kode di sini.

## Deferred from: code review (spec-1-3-manage-role-permission.md)
- [ ] Missing Authorization Gate (canAccess) in RoleResource — Pre-existing gap logged previously, awaiting Story 1.4 for proper user management and panel auth.

- source_spec: `_artifacts/implementation-artifacts/spec-1-4-manage-user.md`
  summary: `UserResource` (create/edit/deactivate) masih tanpa authorization/policy gate apa pun, meski AC1 menyebut "Staff berwenang" (authorized).
  evidence: Terkonfirmasi via grep `src/` dan `config/`: nol penggunaan `canAccess()`/Policy/`Gate::`. Sama seperti item RoleResource sebelumnya — panel auth (`->login()`) masih belum terpasang dan eksplisit di luar Boundaries Story 1.4 ("Tidak menyentuh panel auth"), jadi prasyarat untuk membangun gate ini masih belum terpenuhi. Menunggu story panel-auth.

## Deferred from: code review of spec-1-4-manage-user.md (2026-09-17)
- Ineffectual Test Assertion di UserAccessRevocationTest [tests/Feature/User/UserAccessRevocationTest.php] — Tes ini merupakan frozen boundary dari story sebelumnya, tidak boleh dimodifikasi tanpa persetujuan eksplisit.

- source_spec: none
  summary: Sederhanakan topbar Shell agar "cukup menggunakan Filament saja" — lepas custom topbar controls Bazaar (search/bell/menu/user-menu dengan dropdown theme+language sendiri, dipasang di render hook `TOPBAR_END` sejak Story 1.2) dan pakai kontrol native Filament (user menu + theme switcher bawaan panel `->login()`) sebagai gantinya.
  evidence: Dilaporkan pengguna (2026-09-17): icon search/notification custom tidak selaras dengan icon user default Filament, dan dropdown theme-toggle custom bertabrakan/tumpang-tindih dengan popup user-menu native Filament. Filament belum punya panel auth saat Story 1.2 dibangun sehingga Bazaar terpaksa membuat kontrolnya sendiri; sejak Story 1.3 memasang `->login()`, Filament sudah render user-menu-nya sendiri, membuat kedua set kontrol berdampingan tanpa terkoordinasi. Menyentuh design commitment eksplisit di EXPERIENCE.md §Interaction Primitives ("search/staff notification bell/user chip dengan theme+language" adalah requirement topbar) — mengganti dengan kontrol native Filament berarti mundur dari sebagian requirement itu (Filament tidak native search/notification bell), perlu keputusan desain eksplisit sebelum dikerjakan, bukan sekadar bug fix CSS.
  resolution: Dikerjakan oleh `spec-simplify-topbar-native-filament.md` (2026-09-17) — TOPBAR_END hook & topbar.blade.php dihapus; Language dipindah jadi satu `Action` di dalam `->userMenuItems()` native Filament (BazaarServiceProvider::packageRegistered()); search/notifications/theme-toggle custom dihapus tanpa pengganti (bell kosong sebelumnya tidak fungsional; search/theme sudah native & aktif default).

- source_spec: `_artifacts/implementation-artifacts/spec-simplify-topbar-native-filament.md`
  summary: `UserPreferences::setTheme()`/`setLocale()` (persistence layer, AD-18, `bazaar_user_preferences`) sudah ada dan teruji terisolasi sejak Story 1.2, tapi tidak pernah benar-benar terhubung ke request/Livewire action nyata manapun — tidak ada UI/controller/action yang memanggilnya di luar test.
  evidence: `grep -rn "setTheme\|setLocale" src/` mengonfirmasi nol caller di `src/` selain definisi method itu sendiri di `Shell/Support/UserPreferences.php` dan pembacaan `locale()` di `Shell/Http/Middleware/ApplyUserLocale.php` (yang membaca, bukan menulis). Locale switching Shell tetap murni client-side (`window.bazaarShell.setLocale`, localStorage-only, lihat `resources/js/shell.js`) — Language action baru di `->userMenuItems()` (spec ini) memakai ulang mekanisme JS itu apa adanya, tidak menyambungkannya ke `UserPreferences::setLocale()`. Sudah begitu sejak sebelum topbar disederhanakan; di luar boundary perubahan ini ("Never: ... Tidak memperbaiki gap wiring persistence theme/locale ke request nyata").

- source_spec: `_artifacts/implementation-artifacts/spec-fix-base-layout-mockup-parity.md`
  summary: Brand block sidebar hanya merender badge inisial; logo gambar dari `->brandLogo()` panel diabaikan.
  evidence: `.fi-sidebar-header-logo-ctn` di-`display:none` di `shell.css` dan `sidebar-brand.blade.php` hanya memakai `getBrandName()`; host yang mengatur logo tidak melihatnya. Selesaikan bila ada kebutuhan logo per klien.
