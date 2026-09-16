# Epic 1 Context: Fondasi — Instalasi, Kontrol Akses & Pengaturan Sistem

<!-- Compiled from planning artifacts. Edit freely. Regenerate with compile-epic-context if planning docs change. -->

## Goal

Memberi Developer backend admin yang siap pasang, sekaligus membangun fondasi bersama yang dipakai seluruh epic berikutnya. Developer memasang Bazaar ke proyek Laravel+Filament klien yang sudah ada lewat Composer + 1 perintah Artisan — tanpa panel baru, tanpa scaffold greenfield — dan langsung mendapat panel fungsional tempat Staff bisa login. Akses diatur lewat Role & Permission dinamis (tanpa role hardcoded), setiap aksi mutasi otomatis terekam di Audit Trail immutable, dan parameter operasional dikonfigurasi lewat Global Settings tanpa deploy. Epic ini juga membangun domain baru `Shell` — substrat UI Foundation-tier (design token DESIGN.md sebagai Filament theme override, kit komponen UI inti, bilingual EN/ID + dual theme, accessibility floor) yang dipakai setiap domain lain untuk UI-nya — ditutup dengan dua jalur integrasi (Service Layer monolith, API Layer headless opsional) dan satu pipeline optimasi media (compress+WebP) bersama untuk seluruh package.

## Stories

- Story 1.1: Package Skeleton, Filament Panel Connection & Migration
- Story 1.2: Design Token System & Shell UI Bilingual/Dual-Theme
- Story 1.3: Manage Role & Permission
- Story 1.4: Manage User
- Story 1.5: Audit Trail
- Story 1.6: Manage Global Settings
- Story 1.7: Global SEO Defaults
- Story 1.8: Service Layer Integration (Monolith)
- Story 1.9: API Layer Integration (Headless)
- Story 1.10: Media Upload Optimization (Shared Pipeline)

## Requirements & Constraints

- Bazaar dipasang ke proyek Laravel+Filament klien yang *sudah ada* — bukan scaffold aplikasi baru; instalasi menyambung ke panel existing, tidak pernah membuat panel kedua. Migrasi di epic ini hanya mencakup User & Access + Global Settings; epic berikutnya menambah migration-nya sendiri.
- User (Staff internal) adalah realm terpisah total dari Customer, wajib punya minimal 1 Role. "Approval Role" tidak pernah nama role hardcoded — selalu berarti Role manapun yang memegang permission terkait; perubahan permission suatu Role berlaku instan ke semua pemegangnya. Menonaktifkan User mencabut akses seketika tanpa menghapus jejak historisnya di Audit Trail.
- Audit Trail read-only mutlak di semua UI untuk semua User termasuk admin, mencatat otomatis siapa/apa/kapan/before-after tiap create/update/delete di semua domain tanpa instrumentasi manual per domain, filterable per User/entity/rentang waktu.
- Global Settings List+Edit only (tanpa tambah/hapus parameter lewat UI), mencakup Timeout Timer family, Payment Gateway (aktif/nonaktif + kredensial terenkripsi + metode yang di-expose ke Customer), Shipping config, Default Warehouse, Store Info, Min/Max Refund %, Robots.txt, Analytics Verification Codes. Global SEO Defaults adalah section di dalamnya (bukan modul terpisah): default meta title/description/OG image sebagai fallback terakhir.
- Service Layer memungkinkan portal monolith memanggil Service domain manapun via DI/facade tanpa HTTP. API Layer opsional & off by default; saat aktif terautentikasi Sanctum (token per-service) dan merespons via API Resource — terlepas dari flag ini, webhook Payment/Shipping selalu terdaftar & aktif.
- Optimasi media (compress+WebP) wajib satu mekanisme bersama dipakai ulang tiap titik upload di seluruh package; file asli selalu tetap tersimpan sebagai sumber.
- Architecture test (`pestphp/pest-plugin-arch`) wajib lulus, menegakkan tidak ada Model/Action suatu domain diakses dari luar kecuali lewat Service-nya — Definition of Done tiap domain. `bazaar:status` + widget Global Settings wajib mengecek heartbeat queue worker & scheduler.

## Technical Decisions

- Stack: PHP ^8.4, Laravel ^12/^13, Filament ^5.8, MySQL 8.x/PostgreSQL 15+ first-class (tanpa SQL spesifik-engine). Struktur domain-grouped (`src/{Domain}/{Models,Services,Actions,...}`); alur Service→Action→Model method; kode presentasi hanya boleh memanggil Service; lintas-domain selalu lewat Service domain tujuan, Events untuk arah sebaliknya.
- **Domain `Shell` (baru, AD-33)**: Foundation-tier, tidak bergantung domain lain (termasuk User/Settings), dan tidak punya Models/Actions — murni substrat presentasi, di luar disiplin Service-call AD-5. CSS/JS kit komponennya (Tailwind v4/vanilla-Alpine) dikompilasi **sekali saat dev/release Bazaar sendiri** dan di-commit sebagai static asset — tidak pernah dibangun/fetch saat install atau request klien; Node/Tailwind adalah devDependency repo Bazaar saja, bukan kebutuhan host app. Diregistrasi lewat `FilamentAsset::register()` (Filament static-asset API, `$panel->theme('bazaar-shell')`) — tidak pernah `viteTheme()`; hanya `ServiceProvider` Shell yang boleh mendaftarkan asset, domain lain konsumsi via Blade component Shell. Klasifikasi tetap: **Net-new** (Modal, Tabs, Pagination, Empty State, Countdown Indicator, Alert Banner) vs **restyle primitive Filament** (Data Table, Sidebar, Topbar, Notification Dropdown, Card, Status Pill, Button, Form Input, dan Toast — memakai `Filament\Notifications\Notification` asli, ditema ulang). Preferensi tema/bahasa per-Staff tersimpan di tabel milik Bazaar sendiri (`bazaar_user_preferences`, ULID), bukan kolom di tabel `users` host app. Belum ada seam resmi untuk override visual klien (candidate seam-4 AD-16, Deferred) — dilarang menambah escape hatch raw-CSS-injection. Node/Tailwind yang kini sah sebagai dev-tooling juga jadi dasar test browser (Playwright) untuk memverifikasi perilaku visual/runtime yang tak bisa diverifikasi Pest saja.
- User & Access: Models User, Role, Permission, AuditTrail (subclass Activity dari `spatie/laravel-activitylog`), dibangun di atas `spatie/laravel-permission`. Modul ini juga menaungi Approvals Inbox (`User/Filament/Pages/ApprovalsInbox.php`, dipakai mulai epic berikutnya) — agregasi read-only, tiap domain pemilik expose `pendingFor(User $staff): Collection`, digate Policy per-record.
- Settings: `spatie/laravel-settings` untuk parameter operasional Staff-editable; `config/bazaar.php` hanya nilai deploy-time/struktural — jangan pernah dicampur. Dilengkapi `filament/spatie-laravel-settings-plugin` untuk UI List+Edit-nya plus widget health-check AD-17. Identifier: setiap Model ULID (`HasUlids`) tanpa kecuali. Empat mekanisme log-like tetap terpisah: `Log::` teknis, `AuditTrail`, `NotificationLog`/`StaffNotification` (Epic 2). Pipeline media: `spatie/laravel-medialibrary` conversions, di-hook sekali dipakai ulang tiap domain. Seam ekstensi klien hanya 3: container-binding override, Laravel Events, config-registered Resource class swap — tidak pernah edit source langsung. Rilis: semver, migrasi additive/non-destructive by default.
- Kolom uang di seluruh sistem selalu whole-Rupiah unsigned bigint, tidak pernah decimal/float — relevan bila scaffolding epic ini menyentuh setting terkait finansial (Min/Max Refund %).

## UX & Interaction Patterns

- Shell konsisten di seluruh produk: sidebar 250px + topbar 62px di atas token DESIGN.md (primary `#00609e`, Poppins heading/nav/button, Mulish body, spacing 2px-stepped, radius sm/md/lg/full) — tidak pernah stock Filament theme. Dual theme & bilingual EN/ID instan tanpa reload, persisten per-User; token dark net-new (bukan inversi literal); tanpa elemen chrome berlebar tetap yang memotong string ID lebih panjang.
- Komponen reusable inti dipakai semua epic berikutnya: Data Table (skeleton loading, pagination-only, full-row tint, klik baris buka detail, aksi umum selalu icon button visible), Modal (1 level, focus-trap+Esc), Toast (wajib tiap aksi state-changing, auto-dismiss ~4s), Tabs (deep-linkable), Pagination, Alert Banner (info tidak bisa di-dismiss), Empty State, Dropzone (+hint line syarat minimum), Staff Notification Dropdown (340px, tint unread).
- Pola generik untuk domain data lain: 1 pill per status (teks selalu menyertai warna), kontrol Approve/Reject maker-checker permission-gated saat render (disembunyikan, bukan disabled), label selalu generik tanpa nama role hardcoded.
- Accessibility floor: operable penuh via keyboard (tab order = urutan visual), accessible name tiap icon-only control, focus ring AA-contrast, field disabled/auto-synced ditandai `aria-disabled`/`readonly`.
- Breakpoint: Desktop ≥1280px unchanged; Tablet 768–1279px sidebar→rail icon; Mobile <768px sidebar→drawer, grid→1 kolom, tabel→stacked card, tap target min 40px.
- Terlarang: infinite scroll, drag-and-drop reordering, animasi loading dekoratif, nested modal, status color-only. Modal konfirmasi hanya untuk aksi sulit dibalik (mis. deactivate User); approve/reject rutin langsung eksekusi + toast. Voice & tone lugas/literal di EN & ID, tiap string user-facing wajib dua versi.

## Cross-Story Dependencies

- Story 1.2 membangun domain Shell — fondasi bagi seluruh story lain di epic ini dan UI setiap epic berikutnya; wajar dikerjakan lebih awal.
- Story 1.5 (Audit Trail) diverifikasi lewat mutasi Story 1.3/1.4 (Role/User CRUD) sebagai sumber pertama; auto-capture-nya harus berfungsi tanpa instrumentasi manual di semua epic berikutnya.
- Story 1.3 (Role & Permission) mendahului Story 1.4 (Manage User) untuk menegakkan aturan minimal 1 Role.
- Story 1.6 dan 1.7 berbagi satu surface — 1.7 adalah section di dalam 1.6.
- Pipeline media Story 1.10 adalah prasyarat titik upload di Epic 3 (Item) dan Epic 6 (Hero Banner/Blog/Page).
- Skeleton package & architecture test Story 1.1 adalah substrat tempat semua domain lain (termasuk Shell) dibangun; enforcement AD-5/AD-6-nya berlaku ke seluruh pekerjaan berikutnya.
- Story 1.8/1.9 mengekspos Service yang dibangun epic-epic lain — bukan gating dependency untuk story epic ini, tapi Service epic berikutnya wajib mengikuti konvensi pemanggilannya.
