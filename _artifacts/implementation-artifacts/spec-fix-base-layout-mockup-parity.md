---
title: 'Perbaiki base layout Shell (sidebar, topbar, main container) agar sesuai mockup'
type: 'bugfix'
created: '2026-09-19'
status: 'done'
route: 'dispatch'
review_loop_iteration: 0
baseline_commit: 'ec7d77e4442a68eb099674e599c6baa31875b802'
context:
  - '{project-root}/_artifacts/planning-artifacts/ux-designs/ux-Tigaphonic/bazaar-2026-09-11/DESIGN.md'
  - '{project-root}/_artifacts/planning-artifacts/ux-designs/ux-Tigaphonic/bazaar-2026-09-11/imports/admin-dashboard.html'
---

<frozen-after-approval reason="human-owned intent — do not modify unless human renegotiates">

## Intent

**Problem:** Panel Filament di sandbox masih tampil seperti Filament stock. `resources/css/shell.css` hanya mengatur lebar dan breakpoint sidebar (`#fi-main-sidebar`); tidak ada styling untuk sidebar (brand block, nav item, active bar 3px, footer user), topbar (tinggi 62px, border, search fill soft) maupun main container (kanvas `soft`, content padding 26/28/40). Tampilan tidak mengikuti mockup `imports/admin-dashboard.html` dan DESIGN.md (Layout & Spacing, Sidebar Nav, Topbar).

**Approach:** Restyle primitif native Filament (`fi-sidebar*`, `fi-topbar*`, `fi-main`, `fi-page`) lewat `shell.css` dengan token DESIGN.md, mengikuti anatomi mockup. Tambahkan dua render hook bila markup native tidak cukup: brand block (badge inisial + nama + "Admin Portal" + baris env "Live · Brand Store") dan footer user (avatar + nama + role) di sidebar.

## Boundaries & Constraints

**Always:** Pakai token DESIGN.md yang sudah ada di `shell.css`/`DesignTokens.php` (jangan hardcode warna baru). Dukung light dan dark (`.dark`). Pertahankan breakpoint tablet rail 64px dan mobile drawer. Label bilingual tidak boleh clipping (tanpa lebar piksel tetap). Density tidak dilonggarkan. Kontrol topbar tetap native Filament (search, sidebar-toggle, user-menu + item Language).

**Never:** Jangan kembalikan kontrol topbar custom (sudah dihapus di `spec-simplify-topbar-native-filament.md`). Jangan bangun Notification Bell (Story 2.3). Jangan ubah mekanisme persistence theme/locale. Jangan pakai `->viteTheme()`. Jangan sentuh komponen Modal/Tabs/Empty State/Alert Banner.

## I/O & Edge-Case Matrix

| Scenario | Input / State | Expected Output / Behavior | Error Handling |
|----------|--------------|---------------------------|----------------|
| Desktop ≥1280px, light | Staff login, buka halaman admin | Sidebar 250px putih + border kanan; brand block; nav item Poppins 13.5px; item aktif fill `primary-50` + bar kiri 3px; footer avatar+nama+role; topbar 62px putih + border bawah; konten di atas kanvas `soft` dengan padding 26/28/40 | N/A |
| Dark mode | Toggle theme native | Semua region memakai token `*-dark`, bukan inversi literal | N/A |
| Tablet 768–1279px | Viewport sempit | Sidebar rail 64px, label tersembunyi; brand block dan footer hanya avatar/badge | N/A |
| Mobile <768px | Viewport sempit | Sidebar drawer via toggle topbar | N/A |
| User tanpa role / bukan `HasRoles` | Footer dirender | Footer tampil avatar + nama, tanpa baris role | Tidak error |

</frozen-after-approval>

## Code Map

- `resources/css/shell.css` -- hanya memuat width/breakpoint sidebar; tambah rule layout untuk `.fi-sidebar`, `.fi-sidebar-header*`, `.fi-sidebar-nav`, `.fi-sidebar-item*`, `.fi-sidebar-item-active`, `.fi-sidebar-footer`, `.fi-topbar`, `.fi-main`, `.fi-page`. Jangan sentuh `.modal*`, `.tabs*`, `.empty-state*`, `.alert-banner*`, `.bazaar-sidebar__*`.
- `resources/dist/shell.css` -- artefak build; regenerasi dengan `npm run build`.
- `resources/views/shell/sidebar-nav-start.blade.php` -- render hook `SIDEBAR_NAV_START` (item Dashboard placeholder); pertahankan, sesuaikan agar tampil sebagai item aktif.
- `resources/views/shell/sidebar-brand.blade.php` -- baru; brand block via `PanelsRenderHook::SIDEBAR_LOGO_AFTER` atau setara.
- `resources/views/shell/sidebar-footer.blade.php` -- baru; footer user via `PanelsRenderHook::SIDEBAR_FOOTER`.
- `src/BazaarServiceProvider.php:registerShellRenderHooks()` -- daftarkan hook baru.
- `lang/en/shell.php`, `lang/id/shell.php` -- string "Admin Portal" dan label footer.
- `vendor/filament/filament/resources/views/livewire/{sidebar,topbar}.blade.php` -- referensi class hook Filament; jangan ubah.
- `tests/Feature/Shell/AccessibilityTest.php`, `PanelThemeOverrideTest.php` -- pola test yang ada; tambah test untuk hook baru.

## Tasks & Acceptance

**Execution:**
- [x] `resources/css/shell.css` -- styling sidebar, topbar, main container sesuai mockup + DESIGN.md, light/dark -- akar masalah
- [x] `resources/views/shell/sidebar-brand.blade.php` + `sidebar-footer.blade.php` -- komponen brand dan footer user
- [x] `src/BazaarServiceProvider.php` -- daftarkan render hook baru
- [x] `lang/{en,id}/shell.php` -- string baru
- [x] `resources/dist/shell.css` -- rebuild (`npm run build`)
- [x] `tests/Feature/Shell/` -- test render brand block dan footer (role ada / tidak ada)

**Acceptance Criteria:**
- Given Staff login di desktop, when membuka halaman admin, then sidebar, topbar, dan container konten cocok dengan mockup `imports/admin-dashboard.html` (dimensi, warna, active bar, kanvas) di light dan dark.
- Given viewport tablet atau mobile, when halaman dimuat, then rail 64px atau drawer berfungsi tanpa clipping.
- Given `vendor/bin/pest` dijalankan, when semua test berjalan, then lulus tanpa regresi.

## Implementation Notes

- Filament v5 merender topbar full-width di atas layout dan sidebar `sticky top:62px`; mockup memakai sidebar setinggi layar dengan topbar hanya di atas konten. Ditangani di `shell.css` (sidebar `fixed`, topbar `fixed` dengan offset lebar sidebar, `.fi-layout` diberi margin/padding) untuk >=768px; mobile memakai layout native.
- Placeholder Dashboard (`sidebar-nav-start`) di-hardcode aktif oleh subagent; diperbaiki menjadi aktif hanya di URL home panel, dan disembunyikan bila navigasi native sudah punya item ke URL home (host dengan `Dashboard::class`).
- Rentang 768-1279px: overlay drawer dan tombol toggle topbar disembunyikan karena rail selalu terlihat.
- Verifikasi browser di sandbox: desktop light, dark, tablet 1000px, mobile 600px. Aset sandbox perlu `php artisan filament:assets` setelah `npm run build`, dan hard reload karena URL asset bersih versi.

## Spec Change Log

## Review Triage Log

- [edge-case] `shell.css` search wrapper `box-shadow:none` menghapus indikator fokus -- medium, nyata (a11y floor EXPERIENCE.md). -> patch (outline `:focus-within`, light+dark).
- [edge-case/blind] `.fi-sidebar-item-btn` `white-space:nowrap` di sidebar 250px bisa memotong label Indonesia panjang -- medium, melanggar "tanpa clipping". -> patch (wrap + `overflow-wrap:anywhere`, juga nama brand/footer).
- [edge-case] Grup sidebar yang di-collapse (localStorage) tetap tersembunyi di rail tablet sementara tombol grup disembunyikan -- medium. -> patch (`group-items` selalu tampil di rail).
- [blind/edge] Hex `#ffffff`/`#f4f6f7` hardcode -- low, melanggar Always "jangan hardcode". -> patch (token `--color-white`, `--color-nav-hover`).
- [verification-gap] Aktif/tersembunyi placeholder Dashboard tanpa test -- medium. -> patch (test non-dashboard page; cabang `hasNativeDashboard` tidak bisa diuji di workbench tanpa Dashboard page, tercakup verifikasi browser).
- [blind/edge] `.fi-sidebar-header-logo-ctn` `display:none !important` mengabaikan `brandLogo()` panel -- low, nyata tapi keputusan desain mockup (badge inisial). -> defer.
- [blind] `dist/shell.css` tidak ada di diff -- false; file di-rebuild dan berubah di working tree (filter diff saya yang mengecualikan artefak).
- [blind] Fokus `.fi-sidebar-item-btn` tanpa `:focus-visible` -- false; aturan awal `[data-shell-sidebar] a:focus-visible` mencakup, atribut ada di `#fi-main-sidebar`.
- [edge] `getUrl()` throw di tenancy; `getItems()` pada item tanpa grup; RTL; mode collapsible desktop -- false/low: Bazaar tanpa tenancy, `getNavigation()` selalu grup, EN/ID saja, sidebar tidak collapsible (lebar dipaksa per breakpoint). -> reject.
- [edge] Deteksi aktif via `url()->current()` di Livewire/SPA -- maybe-false low; SPA mode tidak aktif. -> reject.
- [edge/blind] Query role per render, `catch \Throwable`, inisial kosong, duplikasi logika inisial, z-index sidebar, footer tidak terpin -- low/false: satu query ringan, footer terverifikasi terpin di browser, z-index sidebar 20 vs topbar 30 terverifikasi. -> reject.
- [blind] `data-i18n` sisa mockup -- false; dipakai `shell.js` untuk swap locale.

## Design Notes

Nama brand berasal dari `filament()->getBrandName()`; badge = inisial 1-2 huruf. Role diambil dari nama role pertama user bila `roles()` tersedia (lihat `src/User/Contracts/HasRolesUser.php`).

## Verification

**Commands:**
- `npm run build` -- expected: `resources/dist/shell.css` terbarui tanpa error
- `vendor/bin/pest` -- expected: semua lulus

**Manual checks (if no CLI):**
- Buka `http://app-bazaar-sandbox.test/admin` (login sebagai Staff), bandingkan dengan `imports/admin-dashboard.html` di light, dark, tablet, mobile.
