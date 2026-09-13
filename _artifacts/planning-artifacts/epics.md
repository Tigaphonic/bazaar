---
stepsCompleted: [1, 2, 3, 4]
inputDocuments:
  - _artifacts/planning-artifacts/prds/prd-Tigaphonic/bazaar-2026-09-11/prd.md
  - _artifacts/planning-artifacts/prds/prd-Tigaphonic/bazaar-2026-09-11/addendum.md
  - _artifacts/planning-artifacts/architecture/architecture-Tigaphonic/bazaar-2026-09-12/ARCHITECTURE-SPINE.md
  - _artifacts/planning-artifacts/ux-designs/ux-Tigaphonic/bazaar-2026-09-11/DESIGN.md
  - _artifacts/planning-artifacts/ux-designs/ux-Tigaphonic/bazaar-2026-09-11/EXPERIENCE.md
  - _artifacts/planning-artifacts/ux-designs/ux-Tigaphonic/bazaar-2026-09-11/reconcile-prd.md
---

# Tigaphonic/bazaar - Epic Breakdown

## Overview

This document provides the complete epic and story breakdown for Tigaphonic/bazaar, decomposing the requirements from the PRD, UX Design (DESIGN.md + EXPERIENCE.md), and Architecture Spine (32 ADs) into implementable stories.

## Requirements Inventory

### Functional Requirements

**4.1 Catalog**

FR-1: Manage Brand — Staff/Admin dapat membuat, mengedit, dan mengarsipkan Brand sebagai master data katalog; setiap Item terasosiasi ke tepat 1 Brand; mengarsipkan Brand tidak menghapus/meng-orphan Item yang sudah memakainya.

FR-2: Manage Category (2 level: Main → Sub) — Staff/Admin mengelola hierarki tetap 2 level (Main Category tanpa parent, Sub-Category tepat 1 Main Category sebagai parent); setiap Item wajib terasosiasi ke 1 Sub-Category; tiap Category punya field Slug dan metadata SEO.

FR-3: Manage Attribute Template — Staff/Admin mendefinisikan atribut dinamis (text/number/dropdown/checklist) per Sub-Category tanpa deploy; mengubah template tidak otomatis mengubah data Item lama (snapshot); sistem menampilkan peringatan diff field & tombol penyelarasan manual (staff-triggered, tidak otomatis).

FR-4: Manage Item — Staff/Admin membuat & mengedit Item (Condition, Inventory Strategy, 1 Brand, 1 Sub-Category, atribut sesuai template); Item baru selalu Draft tanpa kecuali; hanya Staff dengan Approval Role yang bisa publish; Grading wajib untuk Condition=Second; alt text gambar default ke nama Item bila kosong.

FR-5: Manage Stock — Staff/Admin melihat & menyesuaikan ledger Stock per Item × Warehouse; Item Pooled dibatasi 1 baris Stock (kuantitas ≥0, validasi Service, bukan DB constraint); Item Serialized dibatasi 1 baris permanen (kuantitas selalu 1); Default Warehouse di-resolve & di-snapshot bila tak eksplisit dipilih; sistem mencegah 2 Order bersamaan over-reserve unit yang sama.

FR-6: Manage Warehouse — Staff/Admin membuat & mengedit Warehouse sebagai master data lokasi fisik + alamat asal pengiriman; bisa ditetapkan sebagai Default Warehouse; tidak bisa dihapus/dinonaktifkan selama masih direferensikan Stock aktif.

FR-30: Item Visibility Based on Stock & Order Status — visibilitas Item di portal mengikuti kombinasi Stock + status Order yang masih menahannya (bukan cuma kuantitas Stock mentah); Item dengan Order pending yang menahan unit terakhir tetap tampil berlabel "Sedang Di-restock"/"Sedang Proses Penjualan"; status ini tersedia lewat availability-check service yang sama dengan Cart (FR-8).

**4.2 Order**

FR-7: Manage Customer — sistem create-or-find Customer otomatis berdasarkan email saat checkout (guest, tanpa mewajibkan password); skema Customer auth-ready (kolom password/email_verified_at nullable); Staff dapat melihat & mengedit data Customer + riwayat Order dari dashboard.

FR-8: Manage Cart — Customer menambah/menghapus Item (Pooled maupun Serialized) ke Cart tanpa membuat reservasi stok apapun; Bazaar menyediakan availability-check service/endpoint on-demand; domain Event (StockReserved/StockReleased/ItemPublished/ItemUnpublished) di-dispatch tiap kali status ketersediaan berubah, dikonsumsi portal sendiri (Bazaar tidak menyediakan broadcast/WebSocket).

FR-9: Checkout & Order Creation — Customer menyelesaikan checkout dari Cart menjadi 1 Order lewat verifikasi OTP wajib (semua checkout, guest) + re-validasi ketersediaan ulang tiap Item; Item gagal validasi dikembalikan sebagai daftar spesifik (Order tidak dibuat); Item lolos direservasi atomic pada momen pembuatan Order; Order baru dibuat dengan 3 status layer independen.

FR-10: Order Status Lifecycle — Order dilacak lewat 3 layer status independen (Payment/Order/Shipping Status), masing-masing berubah menurut pemicunya sendiri (mis. Payment=Paid sementara Shipping=Not Shipped adalah normal); Staff melihat ketiganya dari 1 tampilan Order detail. Order Status minimal: Processing, Cancelled, Return Requested, Returned, Completed. Shipping Status minimal: Not Shipped, Shipped, Delivered, AWB Voided, Returned to Warehouse.

FR-11: Manage Shipment — setelah Order eligible (Payment=Paid), Staff memicu fulfillment per-Shipment (1 per Warehouse berbeda yang terlibat) lewat 1 tombol aksi; sistem memanggil Shipping Gateway vendor untuk membuat AWB (idempotent — klik berulang tidak duplikat); status pengiriman tetap tersinkron vendor (webhook/polling); fallback manual-entry AWB & tombol refresh/override saat sinkronisasi/vendor gagal.

FR-12: Order Detail Access & Review — Customer mengakses detail Order (termasuk Shipping Status) lewat tokenized link tanpa login; saat Delivered menerima email + link tokenized + form Review + batas waktu submit (Timeout Timer, default H+5); submit dalam waktu → Order Completed + notifikasi in-app moderasi ke Approval Role; lewat batas waktu → Order Completed otomatis tanpa notifikasi moderasi. Halaman tokenized tertutup otomatis setelah jangka waktu tertentu, menampilkan riwayat Order bertahap sebelum tertutup.

FR-13: Cancel & Retur Order — Customer (lewat CS) mengajukan pembatalan/retur; jalur & status akhir tergantung tahap fulfillment (3 cabang: (A) belum ada AWB → Cancel Request; (B) AWB ada, barang belum fisik dikirim → Return Request → Shipping=AWB Voided; (C) barang sudah fisik dikirim → Return Request → Shipping=Returned to Warehouse); semua lewat Request tercatat (alasan) + maker-checker approval; disetujui → reservasi/Stock dikembalikan & Item Serialized republish; tanpa jalur self-service otomatis.

FR-14: Manage Promo — Staff membuat Promo (diskon persentase/nominal tetap, kuota total, limit redemption per Customer, periode berlaku); sistem menjamin redemption atomic saat checkout, mencegah 2 checkout bersamaan redeem kuota terakhir yang sama.

FR-15: CS Interaction Log — Staff CS mencatat riwayat interaksi/komunikasi per Order (channel, catatan, waktu, staff pencatat), terlihat di Order detail; menjadi dasar sebelum eksekusi Cancel/Retur/Refund.

**4.3 Finance**

FR-16: Manage Payment — sistem mencatat transaksi Payment per Order, direkonsiliasi per gateway; hanya metode pembayaran yang di-enable Staff (Global Settings) yang ditampilkan ke portal; tiap transaksi mencatat gateway, metode, jumlah, status (Pending/Success/Failed), referensi/ID gateway; jumlah tercatat adalah snapshot final (tidak berubah walau harga Item berubah).

FR-17: Manage Refund — Staff Finance mengajukan Refund Request atas Order Cancelled (Payment sempat Paid) atau Returned; field nominal pre-filled dengan Maximum Refund % (selalu valid saat dibuka), bisa diturunkan sampai Minimum Refund %; nominal wajib berada di rentang Min–Max Refund % dari total Order (dikonfigurasi per instalasi di Global Settings); disetujui Role terpisah (permission approve Refund) → Payment Status=Refunded; transfer dana aktual manual di luar sistem (Bazaar tidak memanggil API refund gateway).

**4.4 User & Access**

FR-18: Manage User — Staff berwenang membuat, mengedit, menonaktifkan akun User (internal, terpisah total dari Customer) serta menetapkan Role-nya (minimal 1); menonaktifkan User mencabut akses tanpa menghapus jejak historis di Audit Trail.

FR-19: Manage Role & Permission — Staff berwenang membuat Role baru & mencentang permission granular tanpa deploy; "Approval Role" merujuk ke Role apa pun yang memegang permission terkait (publish Item, moderasi Review, approve Cancel/Retur, approve Refund) — bukan role hardcoded; perubahan permission suatu Role berlaku ke semua User pemegangnya.

FR-20: Audit Trail — sistem mencatat semua aksi mutasi (create/update/delete) di seluruh domain secara otomatis: siapa (User), apa (aksi & entity), kapan (timestamp), before-after; read-only bagi semua User (tidak bisa diedit/dihapus lewat UI manapun); filterable per User/entity/rentang waktu.

**4.5 Content**

FR-21: Manage Hero Banner — Staff membuat & mengatur Hero Banner (gambar, link, urutan tampil, jadwal periode aktif) disediakan lewat Service/API untuk portal.

FR-22: Manage Blog & Page — Staff membuat, mengedit, mempublish entry Blog dan Page lewat rich text editor (Markdown), masing-masing dikelompokkan lewat taksonomi terpisah (Blog Category/Page Category); Draft→Published lewat maker-checker sama seperti Item; wajib tepat 1 Feature Image (alt text default ke judul entry) + field Slug.

FR-23: Manage Featured Item — Staff memilih & mengatur urutan Item (harus Published) yang ditandai Featured untuk ditonjolkan di portal.

FR-24: Entity-Level SEO Metadata — Staff mengisi metadata SEO (Meta Title, Meta Description, Canonical URL opsional, OG Title, OG Description, OG Image) langsung di form edit Item, Category, Blog, Page; fallback berjenjang bila Staff mengosongkan field (OG→Meta→nama/judul entity; OG Image→gambar utama entity).

**4.6 Reporting**

FR-25: Reporting Dashboard — Staff mengakses laporan read-only lintas domain dalam 6 kategori (Sales & Revenue, Customer Analytics, Inventory, Marketing & Promo, Operational & Support, SEO Health), masing-masing dengan filter rentang tanggal, export CSV/XLSX/PDF, dan opsi scheduled report ke email. Rincian laporan per kategori: Sales Summary, Sales by Product/SKU, Payment & Shipping share; New vs Returning Customers, Abandoned Cart, Customer Demographics; Low Stock Alert, Stock Turnover Rate, Dead Stock; Coupon Performance; Fulfillment SLA, Return & Refund Report; Content Completeness, Broken Link (404).

**4.7 Notification**

FR-26: Manage Notification Template — Staff mengedit konten Notification (subjek, body, placeholder variable) per titik trigger tanpa deploy; dispatch/trigger tetap didesentralisasi di tiap domain lewat Laravel Events (domain Notification hanya menyimpan konten & log). Titik trigger email minimal mencakup: Order Status→Completed/Cancelled/Returned, Payment Status→Paid/Refunded (tiap email menyertakan link tokenized Order).

FR-27: Notification Log & Monitoring — Staff melihat riwayat pengiriman Notification (terkirim/gagal, waktu, penerima) dan memicu resend manual.

FR-32: Staff In-App Notification — Staff menerima notifikasi in-app di dashboard untuk event butuh perhatian/aksi (minimal: Order baru diproses, Payment→Paid, registrasi AWB gagal, permintaan Cancel/Retur/Refund butuh approval, Review baru butuh moderasi), hanya dikirim ke User yang Role-nya memegang permission relevan — bukan broadcast; event tanpa kebutuhan aksi tidak memicu notifikasi in-app.

**4.8 Global Settings**

FR-28: Manage Global Settings — Staff melihat & mengedit parameter konfigurasi sistem yang sifatnya tetap (List+Edit only, tidak bisa tambah/hapus parameter lewat UI). Mencakup minimal: Timeout Timer (OTP, batas bayar, batas submit Review, batas akses halaman tokenized default H+5, window On-Process, auto-confirm), Payment Gateway config (aktif/nonaktif + kredensial + metode pembayaran aktif), Shipping config, Default Warehouse, General Store Info, Minimum/Maximum Refund %, Robots.txt Content, Analytics Verification Codes (GSC/GA4/Facebook Pixel).

FR-29: Global SEO Defaults — bagian Global Settings yang menampung default/fallback metadata SEO per-entity (FR-24): default meta title template, default meta description, default OG image.

**4.9 Package Installation & Integration**

FR-33: Install & Connect to Filament Panel — Developer memasang Bazaar lewat Composer (`composer require tigaphonic/bazaar`) dan menghubungkannya ke Filament panel existing lewat 1 perintah Artisan (bukan membuat panel baru); perintah migrasi menginisiasi seluruh skema domain.

FR-34: Service Layer Integration (Monolith) — Developer mengintegrasikan portal Blade+Livewire langsung lewat Service Layer (dependency injection/facade), tanpa HTTP.

FR-35: API Layer Integration (Headless) — Developer mengintegrasikan portal headless (mis. Next.js/Vue) lewat API Layer opsional, diaktifkan lewat config (opt-in); terautentikasi Sanctum, response API Resource.

**4.10 SEO & Discoverability**

FR-36: Media Upload Optimization — setiap gambar yang diunggah (Item, Hero Banner, Blog/Page Feature Image, Brand logo, Store Info logo/favicon) otomatis dikompres & dikonversi ke format modern (WebP) tanpa aksi tambahan Staff; 1 mekanisme seragam lintas semua titik upload.

FR-37: Sitemap Data Feed — Bazaar menyediakan Service/API yang mengembalikan daftar entity siap-index (Item/Category/Blog/Page Published) beserta Slug + waktu update terakhir; mengikuti status Published/Unpublished secara real-time (bukan cache basi); Bazaar tidak menghasilkan/hosting file sitemap.xml.

FR-38: Structured Data Feed (Schema.org) — Bazaar menyediakan Service/API yang mengembalikan data Item terstruktur sesuai schema.org Product (harga, status ketersediaan, agregat rating Review) untuk disuntikkan portal sebagai JSON-LD; murni agregasi data existing, tanpa field baru yang perlu diisi manual.

FR-39: Redirect Manager — Staff membuat & mengelola mapping URL lama → target baru (entity Bazaar lain atau URL eksternal) + alasan opsional; Bazaar tidak mengeksekusi HTTP redirect (tanggung jawab portal).

FR-40: Broken Link (404) Tracking — portal dapat melaporkan event 404 (url, timestamp, referrer opsional) ke Bazaar lewat 1 API endpoint; Staff melihat daftar & jumlah dari dashboard Reporting; pelaporan murni pasif (Bazaar tidak crawling sendiri).

### NonFunctional Requirements

NFR1 (Concurrency/Atomicity — Stock): Semua operasi reservasi Stock (Pooled & Serialized) harus atomic di bawah concurrent load — 1 statement conditional atomic UPDATE dengan WHERE guard, bukan read-then-write — mencegah race condition over-reservation unit yang sama. (FR-5, FR-9)

NFR2 (Concurrency/Atomicity — Promo): Redemption kuota Promo harus atomic saat checkout bersamaan — mencegah 2 checkout berhasil redeem kuota terakhir yang sama secara simultan. (FR-14)

NFR3 (Idempotency — AWB creation): Aksi registrasi AWB per Shipment harus idempotent — menekan tombol aksi berulang untuk Shipment yang sama tidak boleh membuat AWB duplikat maupun efek samping data ganda. (FR-11)

NFR4 (Data Integrity/Immutability — Audit Trail): Audit Trail bersifat append-only — tidak bisa diedit atau dihapus lewat UI manapun oleh User mana pun, termasuk yang memiliki permission administratif. (FR-20)

NFR5 (Data Freshness — SEO feeds): Sitemap Data Feed dan Structured Data Feed harus mencerminkan status Published/Unpublished entity secara real-time (query langsung terhadap data terkini), bukan hasil cache yang basi. (FR-37, FR-38)

NFR6 (Consistency — Media Pipeline): Optimasi gambar (kompresi + konversi WebP) harus berlaku seragam di semua titik upload lintas domain (Catalog, Content, Global Settings) lewat 1 mekanisme bersama — bukan implementasi terpisah per domain yang bisa menghasilkan kualitas/format tidak konsisten.

NFR7 (Configurability — no-deploy changes): Perubahan operasional berikut wajib bisa dilakukan tanpa deploy kode: Attribute Template per Sub-Category (FR-3), Role & Permission granular (FR-19), Notification Template per trigger (FR-26), dan seluruh parameter Global Settings (FR-28) — semuanya staff-editable dari dashboard.

NFR8 (Rate-limiting — unauthenticated write endpoint): Endpoint yang menerima write dari sumber tak terautentikasi (mis. broken-link report FR-40) wajib berada di belakang rate-limiting sejak hari pertama, mencegah volume tak terbatas dari sumber yang tak bisa diidentifikasi/dipertanggungjawabkan.

### Additional Requirements

*(dari Architecture Spine — 32 AD, bazaar-2026-09-12; addendum.md sudah diserap penuh ke dalamnya)*

- **Starter Template: NONE.** Bazaar adalah Composer package yang dipasang ke aplikasi Laravel+Filament klien yang sudah ada — bukan scaffold/starter greenfield. Epic 1 Story 1 = setup skeleton package (struktur domain-grouped `src/{Domain}/`) + `bazaar:install` command, bukan bootstrap aplikasi baru.
- **Tech stack tetap** (Architecture Spine → Stack): PHP ^8.4, Laravel `illuminate/contracts` ^12.0 atau ^13.0, Filament ^5.8, MySQL 8.x & PostgreSQL 15+ (keduanya first-class, AD-23), `spatie/laravel-permission` ^8.3, `spatie/laravel-model-states` ^2.14, `spatie/laravel-medialibrary` ^11.23, `spatie/laravel-activitylog` ^5.1.1+, `spatie/laravel-settings` ^3.9, `filament/spatie-laravel-settings-plugin`, `laravel/sanctum`, `maatwebsite/excel` ^4.0, `barryvdh/laravel-dompdf` ^3.1, Pest/Testbench/Pint/Larastan (versi skeleton existing).
- **Infrastruktur operasional wajib per instalasi:** queue worker + Laravel scheduler cron harus berjalan — dibutuhkan untuk payment-timeout auto-cancel (FR-31), review-deadline auto-complete (FR-12), tokenized-link expiry, AWB retry, dispatch notifikasi. Kehadirannya di-self-check lewat heartbeat AD-17 (`bazaar:status` Artisan + widget dashboard Global Settings), bukan diasumsikan diam-diam.
- **Enforcement batas domain otomatis:** aturan cross-domain-lewat-Service (AD-6) dan Service→Action→Model layering (AD-5) wajib ditegakkan lewat automated architecture test (`pestphp/pest-plugin-arch`) yang assert tidak ada `Models\*`/`Actions\*` suatu domain di-`use` dari luar namespace domain itu kecuali lewat `Services\*` — bukan disiplin code-review semata. Ini bagian dari Definition of Done tiap domain, bukan story terpisah.
- **Webhook ingress selalu aktif:** route webhook/callback Payment (Midtrans) dan Shipping (RajaOngkir) didaftarkan sendiri oleh domain masing-masing, selalu-on, terlepas dari apakah API Layer opsional (FR-35) diaktifkan — instalasi Service-Layer-murni (FR-34) tetap butuh vendor bisa mencapai Bazaar server-to-server.
- **Rate-limiting default:** endpoint tak terautentikasi (FR-40 broken-link report, dan endpoint serupa di masa depan) wajib berada di belakang Laravel rate-limiting middleware sejak awal (AD-32) — lihat NFR8.
- **Disiplin rilis package:** distribusi lewat Composer/Packagist di bawah semver (AD-24); tidak ada rollout terpusat lintas instalasi klien — tiap klien upgrade independen sesuai jadwalnya sendiri.
- **Konstruksi database-engine-agnostic:** skema & query harus berfungsi identik di MySQL 8+ maupun PostgreSQL 15+ (AD-23) — pakai Eloquent JSON cast (bukan fungsi JSON khusus MySQL), pola atomic UPDATE...WHERE (portable ke keduanya), tanpa SQL spesifik-engine di manapun.
- **Identifier & transaction numbering:** setiap Model memakai ULID sebagai primary key (AD-18, tanpa kecuali per-tabel); entity transaksional yang butuh referensi manusia (Order, Payment, RefundRequest, Shipment, CancelRequest, ReturnRequest) tambahan memakai nomor transaksi human-readable lewat 1 mekanisme bersama (`transaction_counters` table, atomic guarded-UPDATE) — tidak boleh diimplementasikan ulang per domain.
- **Uang sebagai integer:** nilai uang disimpan sebagai whole-Rupiah integer (unsigned bigint), bukan desimal/float — berlaku di semua entity finansial (Payment, Refund, Order.total, dst).

### UX Design Requirements

*(dari DESIGN.md + EXPERIENCE.md, bmad-ux spine pair — bazaar-2026-09-11/12)*

UX-DR1: **Design token system sebagai Filament panel theme override** — implementasikan token penuh DESIGN.md: warna (primary + 6 pasangan semantik success/warn/info/danger/neutral/done, untuk light DAN dark theme — dark adalah desain net-new, bukan inversi otomatis), tipografi (Poppins untuk heading/nav/button, Mulish untuk body, ramp 11–26px), skala spacing 2px-stepped (2–28px), skala radius (sm/md/lg/full) — dipasang sebagai theme override di atas Filament default (bukan stock Filament theme), dapat di-theme-override lagi per instalasi klien lewat mekanisme native Filament.

UX-DR2: **Set komponen Net-new** yang tidak tersedia di Filament default, harus dibangun sebagai komponen reusable: Modal/Dialog (1 level, tidak nested, focus-trap + Esc-to-close), Toast (stack kanan-bawah, wajib tampil di **setiap** aksi state-changing tanpa kecuali, auto-dismiss ~4 detik + tombol tutup manual), Tabs (ganti konten tanpa reload, deep-linkable per tab), Pagination (tanpa infinite scroll), Alert Banner (3 varian danger/warn/info, aturan persistence beda per varian — info tidak bisa di-dismiss), Countdown/Deadline Indicator (3 state normal/urgent/expired berbasis warna+teks, dihitung ulang saat page load — bukan live-ticking JS), Attribute Reconcile Banner (collapsible, 2 kolom diff Ditambahkan/Dihapus, selalu inline di atas form — tidak pernah modal), Empty State (icon + headline + CTA opsional, pesan beda untuk "kosong asli" vs "hasil filter kosong"), Staff Notification Dropdown (panel 340px, tint unread, footer link "Lihat semua").

UX-DR3: **Perilaku Data Table seragam di semua list surface** (Items, Orders, Payments, Audit Trail, Users, Roles, dll.): klik baris di mana pun membuka detail; baris yang butuh perhatian (mis. Payment Disputed, AWB gagal) mendapat full-row tint (bukan border saja); aksi baris paling umum (mis. Approve) selalu icon button yang terlihat, tidak pernah disembunyikan dalam menu; loading state pakai skeleton rows (bukan spinner) menjaga bentuk tabel tetap terlihat; pagination saja, tanpa infinite scroll.

UX-DR4: **Status Pill dengan mapping enum→family semantik tetap**: Payment Status (7 nilai), Order Status (5 nilai), Shipping Status (5 nilai) — 1 pill per nilai status, warna+teks tidak pernah color-only; 3 layer status Order selalu render sebagai 3 pill/kolom terpisah, tidak pernah digabung jadi 1 kolom "Status".

UX-DR5: **Pola Maker-Checker/Approval generik** diimplementasikan di 5 instance (Item, Blog/Page, Review, Cancel/Return Request, Refund Request) dalam 2 bentuk (2-state Draft→Published tanpa Reject formal; 3-state Pending→Approved/Rejected dengan notifikasi + terminal state eksplisit) — kontrol Approve/Reject wajib permission-gated saat render (disembunyikan, bukan sekadar disabled) untuk viewer tanpa permission approve terkait; label selalu generik, tidak pernah nama role hardcoded (mis. "Menunggu Approval", bukan "Menunggu approval Owner").

UX-DR6: **Approvals Inbox sebagai 1 surface agregasi lintas-domain** — baris typed (icon+label) untuk Item/Blog-Page/Review/Cancel-Return/Refund, masing-masing menampilkan submitted-by/at + preview ringkas, visibility per baris di-scope sesuai permission viewer.

UX-DR7: **Kebutuhan bilingual EN/ID di level komponen** — setiap button/pill/nav-label/elemen chrome berukuran mengikuti konten (`white-space:nowrap`), tanpa lebar piksel tetap yang bisa memotong string Indonesia yang lebih panjang; language switcher di topbar user menu, berlaku instan tanpa reload, persisten per-User.

UX-DR8: **Dual theme (light/dark) first-class** — token dark didesain net-new (bukan inversi warna literal), pasangan semantik membalik hubungan luminance bg/text sambil mempertahankan hue family untuk menjaga kontras; theme toggle di topbar user menu, instan tanpa reload, persisten per-User.

UX-DR9: **Accessibility floor (standar internal-tool, bukan audit WCAG formal)**: seluruh form/tabel/aksi approval bisa dioperasikan penuh via keyboard (Tab+Enter/Space) dengan tab order mengikuti urutan visual; setiap kontrol icon-only punya accessible name; status tidak pernah color-only (selalu ada label teks); Modal/Dialog trap focus + Esc menutup + focus kembali ke trigger; field disabled/auto-synced ditandai `aria-disabled`/`readonly`; Toast bisa dijangkau & ditutup via keyboard dalam durasi tampilnya; focus outline tetap terlihat di setiap sel/baris tabel interaktif walau rapat.

UX-DR10: **Breakpoint responsif** (net-new, lapisan nice-to-have di atas target utama desktop-first ≥1280px): Tablet (768–1279px) — sidebar collapse jadi rail icon-only 64px dengan tooltip label, stat-grid/grid-2 turun ke 2/1 kolom, tabel pakai `overflow-x:auto`; Mobile (<768px) — sidebar jadi off-canvas drawer, semua grid multi-kolom collapse ke 1 kolom, baris tabel reflow jadi stacked card (label/value), target tap interaktif dapat tinggi minimum 40px (satu-satunya pengecualian densitas). Approvals Inbox dan Order detail wajib tetap fully operable di lebar tablet.

UX-DR11: **Komposisi halaman Order detail** — 3 grup status pill independen berdampingan (tidak pernah digabung), Shipment card (1 per Warehouse terlibat) masing-masing dengan tombol aksi AWB sendiri, tab CS History, entry point aksi Cancel/Retur, entry point moderasi Review, indikator expiry tokenized-link, Order Status Stepper (visually inert — timeline transisi Shipping Status dengan timestamp; aksi selalu di Shipment card, tidak pernah di stepper).

UX-DR12: **Komposisi form edit Item** — Attribute Reconcile Banner (inline, collapsible, di atas form, tidak pernah modal) muncul saat `attribute_snapshot` berbeda dari Attribute Template Sub-Category saat ini; visibilitas field Grading kondisional (wajib hanya saat Condition=Second); Dropzone dengan pipeline compress+WebP silent + hint line syarat minimum; alt text auto-fill dari nama Item bila kosong; treatment visual berbeda untuk field disabled/auto-synced (mis. Default Warehouse ter-resolve otomatis).

UX-DR13: **Information Architecture global** — sidebar domain-grouped (Catalog/Order/Finance/User & Access/Content/Reporting/Notification/Global Settings/SEO & Discoverability) + topbar global chrome (search, Staff Notification Bell, kontrol bahasa/tema, user menu); domain Package Installation eksplisit **tanpa** dashboard surface (CLI-only) — jangan sampai story generation keliru membuatkan menu/screen untuk FR-33/34/35.

UX-DR14: **Aturan Voice & Tone microcopy** — bahasa lugas, literal, tidak idiomatik di EN maupun ID (mis. "Item disimpan sebagai Draft", bukan bahasa perayaan/urgency palsu); setiap string user-facing harus ada versi EN dan ID, tanpa asumsi panjang string 1:1 antar bahasa.

UX-DR15: **Batasan pola interaksi (dilarang)** — tanpa infinite scroll (pagination saja), tanpa drag-and-drop reordering (pakai tombol move-up/move-down eksplisit, mis. untuk urutan Featured Item/Hero Banner), tanpa animasi loading dekoratif di luar skeleton/spinner standar, tanpa nested modal (maks 1 level), tanpa makna status color-only. Approve/Reject langsung eksekusi + toast tanpa modal konfirmasi tambahan (maker-checker sendiri sudah jadi langkah konfirmasi) — kecuali aksi yang sungguh sulit dibalik (Deactivate User, Delete Brand/Category) yang tetap dapat modal konfirmasi.

### FR Coverage Map

FR-1: Epic 3 - Manage Brand
FR-2: Epic 3 - Manage Category (2 level)
FR-3: Epic 3 - Manage Attribute Template
FR-4: Epic 3 - Manage Item
FR-5: Epic 3 - Manage Stock
FR-6: Epic 3 - Manage Warehouse
FR-7: Epic 4 - Manage Customer
FR-8: Epic 4 - Manage Cart
FR-9: Epic 4 - Checkout & Order Creation
FR-10: Epic 4 - Order Status Lifecycle
FR-11: Epic 4 - Manage Shipment
FR-12: Epic 4 - Order Detail Access & Review
FR-13: Epic 4 - Cancel & Retur Order
FR-14: Epic 4 - Manage Promo
FR-15: Epic 4 - CS Interaction Log
FR-16: Epic 5 - Manage Payment
FR-17: Epic 5 - Manage Refund
FR-18: Epic 1 - Manage User
FR-19: Epic 1 - Manage Role & Permission
FR-20: Epic 1 - Audit Trail
FR-21: Epic 6 - Manage Hero Banner
FR-22: Epic 6 - Manage Blog & Page
FR-23: Epic 6 - Manage Featured Item
FR-24: Epic 6 - Entity-Level SEO Metadata
FR-25: Epic 8 - Reporting Dashboard
FR-26: Epic 2 - Manage Notification Template
FR-27: Epic 2 - Notification Log & Monitoring
FR-28: Epic 1 - Manage Global Settings
FR-29: Epic 1 - Global SEO Defaults
FR-30: Epic 3 - Item Visibility Based on Stock & Order Status
FR-31: Epic 4 - Order Payment Timeout Auto-Cancellation
FR-32: Epic 2 - Staff In-App Notification
FR-33: Epic 1 - Install & Connect to Filament Panel
FR-34: Epic 1 - Service Layer Integration (Monolith)
FR-35: Epic 1 - API Layer Integration (Headless)
FR-36: Epic 1 - Media Upload Optimization (shared pipeline, dipakai Item/Hero Banner/Blog/Page/Store Info)
FR-37: Epic 7 - Sitemap Data Feed
FR-38: Epic 7 - Structured Data Feed (Schema.org)
FR-39: Epic 7 - Redirect Manager
FR-40: Epic 7 - Broken Link (404) Tracking

## Epic List

### Epic 1: Fondasi — Instalasi, Kontrol Akses & Pengaturan Sistem
Developer dapat memasang Bazaar ke proyek klien dan langsung mendapat panel admin yang fungsional — Staff dapat login, aksesnya dikelola lewat Role & Permission dinamis, setiap aksi mutasi terekam otomatis di Audit Trail, dan parameter operasional sistem dapat dikonfigurasi lewat Global Settings. Epic ini juga membangun design token system (DESIGN.md) dan kit komponen UI inti bersama (Data Table, Modal, Toast, Tabs, Pagination, Empty State, Alert Banner), shell Information Architecture global (sidebar/topbar), dukungan bilingual EN/ID + dual theme, accessibility floor, dan pipeline optimasi media bersama — seluruhnya dipakai oleh setiap epic berikutnya.
**FRs covered:** FR-33, FR-34, FR-35, FR-18, FR-19, FR-20, FR-28, FR-29, FR-36

### Epic 2: Notifikasi — Template, Log & Staff In-App Alert
Staff dapat mengelola konten notifikasi Customer per titik trigger tanpa deploy, memantau riwayat pengiriman notifikasi + resend manual, dan menerima notifikasi in-app (bell) untuk event yang butuh aksinya. Infrastruktur dispatch tetap desentralisasi — tiap domain berikutnya men-trigger Event-nya sendiri ke sistem ini.
**FRs covered:** FR-26, FR-27, FR-32

### Epic 3: Katalog — Brand, Kategori, Item & Stok
Staff Catalog dapat mengelola seluruh master data produk end-to-end: Brand, Category 2-level (Main→Sub), Attribute Template dinamis per Sub-Category, Item (Condition×Inventory Strategy, Draft→Published lewat maker-checker), Stock per Item×Warehouse, Warehouse, dan aturan visibilitas Item di portal berdasarkan kombinasi Stock+status Order.
**FRs covered:** FR-1, FR-2, FR-3, FR-4, FR-5, FR-6, FR-30

### Epic 4: Order — Cart, Checkout, Fulfillment, Cancel/Retur & Promo
Siklus transaksi lengkap dari Cart Customer sampai Order Selesai: Checkout+OTP, 3-layer status independen (Payment/Order/Shipping), fulfillment Shipment+AWB per Warehouse, auto-cancel payment timeout, akses tokenized + Review pasca-Delivered, Cancel/Retur 3-cabang dengan maker-checker, Promo dengan redemption atomic, dan CS Interaction Log.
**FRs covered:** FR-7, FR-8, FR-9, FR-10, FR-11, FR-12, FR-13, FR-14, FR-15, FR-31

### Epic 5: Finance — Payment & Refund
Sistem mencatat & merekonsiliasi transaksi Payment per gateway; Staff Finance dapat mengajukan Refund Request (nominal dibatasi rentang Minimum–Maximum Refund %) yang disetujui Role terpisah sebelum transfer dana manual dieksekusi di luar sistem.
**FRs covered:** FR-16, FR-17

### Epic 6: Konten & Marketing
Staff Content dapat mengelola Hero Banner, Blog & Page (maker-checker sama seperti Item), Featured Item, dan metadata SEO per-entity — seluruhnya disediakan lewat Service/API untuk portal merender.
**FRs covered:** FR-21, FR-22, FR-23, FR-24

### Epic 7: SEO & Discoverability
Bazaar menyediakan feed Sitemap & Structured Data real-time, Redirect Manager, dan Broken Link (404) tracking — seluruhnya data/config API tipis yang dikonsumsi portal sendiri, konsisten dengan prinsip headless. (Media Upload Optimization/FR-36 dipindah ke Epic 1 sebagai infrastruktur dasar.) Ditempatkan sebelum Reporting karena Architecture Spine menetapkan Reporting membaca domain Seo, bukan sebaliknya.
**FRs covered:** FR-37, FR-38, FR-39, FR-40

### Epic 8: Reporting
Staff (termasuk Owner) mendapat 1 area laporan read-only lintas seluruh domain — Sales & Revenue, Customer Analytics, Inventory, Marketing & Promo, Operational & Support, SEO Health — dengan filter rentang tanggal, export CSV/XLSX/PDF, dan scheduled report.
**FRs covered:** FR-25

## Epic 1: Fondasi — Instalasi, Kontrol Akses & Pengaturan Sistem

Developer dapat memasang Bazaar ke proyek klien dan langsung mendapat panel admin yang fungsional — Staff dapat login, aksesnya dikelola lewat Role & Permission dinamis, setiap aksi mutasi terekam otomatis di Audit Trail, dan parameter operasional sistem dapat dikonfigurasi lewat Global Settings. Epic ini juga membangun design token system dan kit komponen UI inti bersama, shell IA global, bilingual EN/ID + dual theme, dan accessibility floor — dipakai setiap epic berikutnya.

### Story 1.1: Package Skeleton, Filament Panel Connection & Migration

As a Developer Tigaphonic,
I want memasang Bazaar via Composer dan menghubungkannya ke Filament panel klien yang sudah ada lewat 1 perintah Artisan,
So that saya mendapat backend admin Brand Store lengkap tanpa membangun ulang dari nol.

**Acceptance Criteria:**

**Given** proyek Laravel klien dengan Filament panel kosong sudah terpasang
**When** Developer menjalankan `composer require tigaphonic/bazaar`
**Then** package terpasang tanpa error, mengikuti tech stack Architecture Spine (PHP ^8.4, Laravel ^12/^13, Filament ^5.8)

**Given** package ter-install
**When** Developer menjalankan `php artisan bazaar:install`
**Then** seluruh Resource/Page Filament Bazaar tersambung ke panel existing — tidak ada panel baru dibuat
**And** struktur domain-grouped `src/{Domain}/` tersedia (tanpa starter/greenfield scaffold aplikasi)

**Given** migrasi belum dijalankan
**When** Developer menjalankan `php artisan migrate`
**Then** migrasi berjalan bersih terhadap skema yang sudah ada di package saat itu (dimulai dari domain User & Access + Global Settings di epic ini; setiap domain berikutnya — Catalog, Order, dst. — menambahkan migration-nya sendiri di epic masing-masing, bukan dibuat sekaligus di sini), kompatibel MySQL 8.x maupun PostgreSQL 15+ tanpa SQL spesifik-engine

**Given** instalasi selesai
**When** Developer login ke panel
**Then** domain dasar (User & Access, Global Settings) tampil & berfungsi
**And** automated architecture test (`pestphp/pest-plugin-arch`) terpasang & lulus, menegakkan tidak ada Model/Action suatu domain diakses dari luar domain kecuali lewat Service-nya
**And** `bazaar:status` Artisan command tersedia untuk cek heartbeat queue worker + scheduler (AD-17)

### Story 1.2: Design Token System & Shell UI Bilingual/Dual-Theme

As a Staff/Admin,
I want dashboard dengan shell konsisten (sidebar, topbar), mendukung Bahasa Indonesia/Inggris dan tema terang/gelap,
So that saya bisa menavigasi & memakai setiap layar domain dengan nyaman dan konsisten.

**Acceptance Criteria:**

**Given** Bazaar terpasang (Story 1.1)
**When** Staff membuka panel
**Then** shell (sidebar 250px + topbar 62px) tampil sesuai token DESIGN.md (primary #00609e, Poppins/Mulish, radius & spacing scale) sebagai Filament panel theme override — bukan stock Filament theme

**Given** Staff berada di topbar user menu
**When** Staff memilih toggle tema
**Then** tema berganti terang↔gelap instan tanpa reload, persisten per-User, dan token dark-mode diterapkan (bukan inversi warna literal)

**Given** Staff berada di topbar user menu
**When** Staff mengganti bahasa EN↔ID
**Then** seluruh label chrome berubah instan tanpa reload, tidak ada elemen yang terpotong akibat string ID yang lebih panjang

**And** kit komponen UI inti bersama (Data Table dengan skeleton-loading+pagination, Modal 1-level, Toast wajib-tampil-tiap-aksi, Tabs, Empty State, Alert Banner 3 varian) tersedia sebagai komponen reusable untuk epic berikutnya
**And** accessibility floor terpenuhi: tab order mengikuti urutan visual, setiap icon-only control punya accessible name, focus ring native terlihat AA-contrast
**And** breakpoint responsif (Desktop ≥1280px unchanged, Tablet 768–1279px sidebar→rail icon, Mobile <768px sidebar→drawer) diterapkan pada shell

### Story 1.3: Manage Role & Permission

As a Staff berwenang,
I want membuat Role baru dan mencentang permission granular untuknya tanpa deploy kode,
So that saya bisa mengatur siapa berwenang melakukan apa tanpa menunggu rilis developer.

**Acceptance Criteria:**

**Given** Staff berwenang membuka User & Access → Roles
**When** Staff membuat Role baru dan mencentang sejumlah permission (mis. "can approve publish Item", "can approve Return")
**Then** Role tersimpan dengan kumpulan permission granular tsb, tanpa perlu deploy kode

**Given** sebuah Role sudah dipakai beberapa User
**When** Staff mengubah permission Role tsb
**Then** perubahan berlaku ke semua User pemegang Role itu secara instan

**Given** "Approval Role" dirujuk di berbagai domain (publish Item, moderasi Review, approve Cancel/Retur, approve Refund)
**When** Staff memeriksa konsep ini
**Then** sistem tidak memiliki role hardcoded bernama "Approval Role" — istilah ini merujuk ke Role manapun yang memegang permission terkait
**And** Role bisa dihapus dari dashboard (dengan modal konfirmasi karena aksi sulit dibalik)

### Story 1.4: Manage User

As a Staff berwenang,
I want membuat, mengedit, dan menonaktifkan akun User internal serta menetapkan Role-nya,
So that saya bisa mengelola siapa saja yang punya akses ke dashboard dan level aksesnya.

**Acceptance Criteria:**

**Given** Staff berwenang membuka User & Access → Users
**When** Staff membuat User baru dan menetapkan minimal 1 Role (dari Story 1.3)
**Then** User tersimpan sebagai akun internal, terpisah total dari skema Customer

**Given** seorang User yang sudah tidak aktif bekerja
**When** Staff menonaktifkan akun User tsb
**Then** akses User tsb dicabut seketika, tapi jejak historis aksinya di Audit Trail (Story 1.5) tetap tercatat atas nama User tsb, tidak terhapus

**Given** Staff mencoba membuat User tanpa Role
**When** Staff menyimpan form
**Then** sistem menolak — User wajib punya minimal 1 Role

### Story 1.5: Audit Trail

As a Staff,
I want melihat log otomatis semua aksi mutasi di seluruh sistem,
So that saya bisa menyelidiki perubahan data dan mempertanggungjawabkan setiap aksi ke User yang melakukannya.

**Acceptance Criteria:**

**Given** aksi create/update/delete terjadi di domain manapun
**When** aksi tsb tersimpan
**Then** Audit Trail otomatis mencatat entri: siapa (User), apa (aksi & entity), kapan (timestamp), dan nilai before-after — tanpa perlu instrumentasi manual per domain

**Given** Staff membuka Audit Trail
**When** Staff mencoba mengedit atau menghapus sebuah entri
**Then** tidak ada kontrol edit/delete tersedia di UI manapun — read-only mutlak

**Given** Staff ingin menyelidiki insiden tertentu
**When** Staff memfilter Audit Trail per User, per entity, atau rentang waktu
**Then** hasil terfilter sesuai kriteria ditampilkan dalam Data Table dengan pagination standar

### Story 1.6: Manage Global Settings

As a Staff berwenang,
I want melihat & mengedit parameter konfigurasi sistem yang sifatnya tetap,
So that saya bisa menyesuaikan perilaku operasional Bazaar per instalasi klien tanpa menyentuh kode.

**Acceptance Criteria:**

**Given** Staff membuka Global Settings
**When** Staff melihat daftar parameter
**Then** hanya parameter yang sudah ditentukan (fixed) yang tampil — tidak ada opsi tambah/hapus parameter baru lewat UI (List+Edit only)

**Given** Staff mengedit nilai Timeout Timer (OTP, batas bayar, batas submit Review, batas akses tokenized default H+5, window On-Process, auto-confirm)
**When** Staff menyimpan
**Then** nilai baru langsung berlaku untuk transaksi berikutnya

**Given** Staff mengaktifkan sebuah Payment Gateway
**When** Staff memilih metode pembayaran mana yang di-enable untuk Customer
**Then** hanya metode yang eksplisit di-enable yang akan tersedia ke portal — bukan seluruh metode yang didukung gateway secara default

**And** Default Warehouse, General Store Info, Minimum/Maximum Refund %, Robots.txt Content, dan Analytics Verification Codes (GSC/GA4/Facebook Pixel) semuanya dapat diedit dari surface yang sama
**And** field kredensial (mis. Midtrans server key, RajaOngkir API key) tersimpan terenkripsi (encrypted cast)

### Story 1.7: Global SEO Defaults

As a Staff,
I want mengatur nilai default/fallback metadata SEO di level sistem,
So that entity yang tidak diisi metadata SEO-nya secara manual tetap punya nilai SEO yang masuk akal.

**Acceptance Criteria:**

**Given** Staff membuka Global Settings → SEO Defaults
**When** Staff mengisi default meta title template (mis. "{nama entity} — {nama toko}"), default meta description, dan default OG image
**Then** nilai-nilai ini tersimpan sebagai bagian Global Settings (bukan modul terpisah)

**Given** sebuah Item/Blog/Page/Category tidak punya gambar sama sekali
**When** metadata SEO entity tsb di-resolve untuk portal
**Then** default OG image dari Global SEO Defaults dipakai sebagai fallback terakhir

### Story 1.8: Service Layer Integration (Monolith)

As a Developer Tigaphonic,
I want memanggil Service Layer tiap domain langsung dari kode aplikasi klien,
So that saya bisa membangun portal Blade+Livewire di atas Bazaar tanpa lewat HTTP.

**Acceptance Criteria:**

**Given** portal monolith klien (Blade+Livewire) butuh data/aksi dari Bazaar
**When** Developer melakukan dependency injection/facade ke Service Layer domain terkait
**Then** Service dapat dipanggil langsung dari kode aplikasi klien tanpa request HTTP apapun

**Given** dokumentasi package
**When** Developer membaca panduan integrasi
**Then** tersedia contoh pemanggilan Service Layer untuk skenario umum (mis. availability-check, checkout)

### Story 1.9: API Layer Integration (Headless)

As a Developer Tigaphonic,
I want mengaktifkan API Layer opsional untuk portal headless,
So that saya bisa mengintegrasikan frontend terpisah (Next.js/Vue) tanpa membangun ulang autentikasi/response layer.

**Acceptance Criteria:**

**Given** API Layer belum diaktifkan (default)
**When** Developer mengecek konfigurasi
**Then** tidak ada route API Bazaar yang terdaftar/aktif

**Given** Developer mengaktifkan API Layer lewat config (`bazaar.api.enabled`)
**When** Developer memanggil endpoint API
**Then** request terautentikasi via Sanctum (token per-service, bukan per-Customer individual) dan response berbentuk API Resource yang mengekspos kapabilitas Service Layer yang sama secara terstruktur
**And** webhook ingress Payment/Shipping tetap terdaftar & aktif terlepas dari flag ini (AD-11) — server-to-server callback vendor tidak pernah bergantung pada API Layer opt-in

### Story 1.10: Media Upload Optimization (Shared Pipeline)

As a Staff (domain manapun yang mengunggah gambar),
I want setiap gambar yang saya unggah otomatis dikompres & dikonversi ke format modern,
So that performa halaman portal tetap cepat tanpa saya perlu mengoptimasi manual.

**Acceptance Criteria:**

**Given** Staff mengunggah gambar di titik upload manapun (dipakai kemudian oleh Item di Epic 3, Hero Banner/Blog/Page di Epic 6, dan Store Info logo/favicon di Story 1.6)
**When** file disimpan
**Then** gambar otomatis dikompres & dikonversi ke format modern (WebP) tanpa aksi tambahan Staff, lewat 1 mekanisme bersama (`spatie/laravel-medialibrary` conversions, AD-31) — bukan implementasi terpisah per domain

**Given** file asli diunggah
**When** konversi berjalan
**Then** file original tetap disimpan sebagai sumber; hasil kompresi/WebP disimpan sebagai varian terpisah yang disajikan lewat Service/API — tidak pernah menimpa file asli secara permanen

**And** Dropzone (leaf control dari Story 1.2) dipakai sebagai UI upload seragam di semua domain, dengan hint line yang menyatakan syarat minimum sesuai konteksnya (mis. "0 foto diunggah — minimal 1 foto wajib")

## Epic 2: Notifikasi — Template, Log & Staff In-App Alert

Staff dapat mengelola konten notifikasi Customer per titik trigger tanpa deploy, memantau riwayat pengiriman notifikasi + resend manual, dan menerima notifikasi in-app (bell) untuk event yang butuh aksinya. Infrastruktur dispatch tetap desentralisasi — tiap domain berikutnya men-trigger Event-nya sendiri ke sistem ini.

### Story 2.1: Manage Notification Template

As a Staff,
I want mengedit konten notifikasi (subjek, body, placeholder variable) per titik trigger tanpa deploy,
So that pesan yang diterima Customer selalu relevan & bisa disesuaikan tanpa menunggu developer.

**Acceptance Criteria:**

**Given** Staff membuka Notification → Templates
**When** Staff mengedit subjek/body sebuah trigger point (mis. "Order Confirmed") dan menyisipkan placeholder (`{{customer_name}}`, `{{order_id}}`)
**Then** template tersimpan tanpa deploy kode

**Given** template tersimpan
**When** trigger point tsb ditembak dari domain manapun (Laravel Event)
**Then** placeholder di-resolve otomatis dengan data transaksi terkait saat dikirim
**And** dispatch/trigger tetap didesentralisasi di tiap domain lewat Laravel Events — domain Notification hanya menyimpan konten & log, tidak memicu sendiri
**And** minimal titik trigger berikut tersedia sebagai template siap-edit: Order Status→Completed/Cancelled/Returned, Payment Status→Paid/Refunded, OTP Checkout — setiap email menyertakan link tokenized Order (FR-12) di body-nya

### Story 2.2: Notification Log & Monitoring

As a Staff,
I want melihat riwayat pengiriman Notification dan melakukan resend manual,
So that saya bisa memastikan Customer benar-benar menerima komunikasi penting dan memperbaiki kegagalan kirim.

**Acceptance Criteria:**

**Given** sebuah Notification dikirim (berhasil atau gagal)
**When** proses pengiriman selesai
**Then** tercatat di Notification Log dengan status (Terkirim/Gagal), waktu, dan penerima

**Given** Staff melihat entri Gagal di Log
**When** Staff klik resend manual
**Then** Notification tsb dikirim ulang dan entri baru tercatat

### Story 2.3: Staff In-App Notification (Bell & Dropdown)

As a Staff,
I want menerima notifikasi in-app di dashboard untuk event yang butuh perhatian/aksi saya,
So that saya tidak perlu mengecek tiap domain satu-satu untuk tahu ada yang perlu ditindaklanjuti.

**Acceptance Criteria:**

**Given** event yang butuh aksi terjadi (Order baru diproses, Payment→Paid, AWB gagal, permintaan Cancel/Retur/Refund butuh approval, Review baru butuh moderasi)
**When** event tsb dipicu
**Then** `StaffNotification` dibuat & badge unread muncul di bell topbar

**Given** Staff klik bell
**When** dropdown 340px terbuka
**Then** tiap baris menampilkan icon tipe + ringkasan 1 baris + timestamp relatif, item unread bertint `info`, dan klik baris deep-link ke record sumbernya
**And** membuka dropdown menandai item yang terlihat sebagai read

**Given** sebuah Role tidak memegang permission relevan dengan event tsb
**When** event terjadi
**Then** Staff dengan Role itu tidak menerima notifikasi in-app tsb — tidak pernah broadcast ke semua Staff

**Given** event yang tidak butuh aksi Staff (mis. Order auto-Completed tanpa Review masuk)
**When** event terjadi
**Then** tidak ada notifikasi in-app yang dipicu
**And** notifikasi in-app ini berbeda dari Toast (transient/per-aksi) — bell adalah inbox persisten

## Epic 3: Katalog — Brand, Kategori, Item & Stok

Staff Catalog dapat mengelola seluruh master data produk end-to-end: Brand, Category 2-level (Main→Sub), Attribute Template dinamis per Sub-Category, Item (Condition×Inventory Strategy, Draft→Published lewat maker-checker), Stock per Item×Warehouse, Warehouse, dan aturan visibilitas Item di portal berdasarkan kombinasi Stock+status Order.

### Story 3.1: Manage Brand

As a Staff Catalog,
I want membuat, mengedit, dan mengarsipkan Brand,
So that saya punya master data brand yang konsisten untuk semua Item.

**Acceptance Criteria:**

**Given** Staff membuka Catalog → Brands
**When** Staff membuat Brand baru
**Then** Brand tersimpan dan tersedia sebagai pilihan untuk Item

**Given** sebuah Brand sudah dipakai Item
**When** Staff mengarsipkan Brand tsb
**Then** Brand hilang dari pilihan Brand untuk Item baru, tapi Item existing yang memakainya tidak terhapus/ter-orphan

### Story 3.2: Manage Category (2-Level: Main → Sub)

As a Staff Catalog,
I want mengelola taksonomi Category tetap 2 level,
So that Item terorganisir konsisten dan portal bisa membangun navigasi/filter dari sana.

**Acceptance Criteria:**

**Given** Staff membuat Main Category
**When** disimpan
**Then** Main Category tidak punya parent

**Given** Staff membuat Sub-Category
**When** disimpan
**Then** Sub-Category wajib punya tepat 1 Main Category sebagai parent; nesting lebih dari 2 level tidak didukung

**Given** Staff mengisi Category
**When** form dibuka
**Then** tersedia field Slug (editable) dan field metadata SEO (Meta Title/Description, Canonical URL opsional, OG Title/Description/Image), dengan fallback: OG kosong→Meta, Meta kosong→nama Category, OG Image kosong→gambar utama Category (FR-24)

### Story 3.3: Manage Attribute Template

As a Staff Catalog,
I want mendefinisikan atribut dinamis per Sub-Category tanpa deploy,
So that tiap kategori produk bisa punya field spesifik (mis. ukuran, warna) tanpa perlu developer.

**Acceptance Criteria:**

**Given** sebuah Sub-Category (dari Story 3.2)
**When** Staff mendefinisikan Attribute Template-nya (field text/number/dropdown/checklist)
**Then** tepat 1 Attribute Template aktif tersimpan untuk Sub-Category tsb, tanpa deploy kode

**Given** Attribute Template sudah dipakai Item existing
**When** Staff mengubah template (tambah/hapus/ganti tipe field)
**Then** perubahan tidak otomatis mengubah snapshot atribut Item yang sudah ada — hanya berlaku untuk Item baru yang dibuat setelahnya

### Story 3.4: Manage Warehouse

As a Staff Catalog,
I want membuat & mengedit Warehouse,
So that saya punya master data lokasi fisik & alamat asal pengiriman untuk Stock dan Shipment.

**Acceptance Criteria:**

**Given** Staff membuat Warehouse baru
**When** disimpan
**Then** Warehouse tersedia sebagai master data lokasi + alamat asal pengiriman, dan bisa ditetapkan sebagai Default Warehouse di Global Settings (Story 1.6)

**Given** sebuah Warehouse masih direferensikan Stock aktif
**When** Staff mencoba menghapus/menonaktifkannya
**Then** sistem menolak sampai Stock tsb di-reassign ke Warehouse lain

### Story 3.5: Manage Item

As a Staff Catalog,
I want membuat & mengedit Item dengan Condition, Inventory Strategy, dan atribut sesuai template,
So that produk baru bisa masuk katalog dan (setelah disetujui) tampil di portal.

**Acceptance Criteria:**

**Given** Staff membuat Item baru (Brand, Sub-Category, Condition, Inventory Strategy, nilai atribut sesuai Attribute Template aktif Sub-Category-nya)
**When** disimpan
**Then** Item otomatis berstatus Draft tanpa kecuali

**Given** Item Draft
**When** Staff tanpa Approval Role mencoba mem-publish-nya
**Then** kontrol Approve/Publish tidak muncul untuknya (permission-gated at render time) — hanya Staff dengan Approval Role yang bisa mem-publish

**Given** Condition = Second dipilih
**When** form ditampilkan
**Then** field Grading (pilihan tetap, bukan free-text) wajib diisi; Condition = New tidak menampilkan/tidak mewajibkan Grading

**Given** sistem menyarankan default pairing (New→Pooled, Second→Serialized)
**When** Staff memilih kombinasi lain
**Then** override diperbolehkan

**Given** Staff mengunggah foto Item ke Dropzone tanpa mengisi alt text
**When** disimpan
**Then** alt text otomatis terisi nama Item (bisa ditimpa manual kapan saja); gambar dikompres & dikonversi WebP otomatis (FR-36, mekanisme sama semua domain)

**Given** Item punya field Slug + metadata SEO (fallback sama seperti Story 3.2)
**When** form dibuka
**Then** field-field tsb tersedia dan berfungsi sesuai FR-24

**Given** Staff membuka form edit sebuah Item yang `attribute_snapshot`-nya berbeda dari Attribute Template Sub-Category saat ini (Story 3.3)
**When** form dibuka
**Then** Attribute Reconcile Banner tampil inline di atas form (collapsed: "Template atribut berubah — N field baru, M field dihapus"), bisa di-expand menjadi 2 kolom Ditambahkan/Dihapus
**And** tombol "Selaraskan" menjalankan merge (field baru ditambah kosong ke snapshot, field terhapus dibuang dari snapshot, field yang ada di keduanya tidak disentuh) — banner ini tidak pernah muncul sebagai modal, dan reconcile murni manual/staff-triggered

### Story 3.6: Manage Stock

As a Staff Catalog,
I want melihat & menyesuaikan ledger Stock per Item × Warehouse,
So that ketersediaan produk akurat dan tidak over-sold saat checkout bersamaan.

**Acceptance Criteria:**

**Given** Item Pooled (dari Story 3.5)
**When** Staff membuat baris Stock-nya
**Then** dibatasi tepat 1 baris Stock per Item, kuantitas bisa berapa pun ≥0

**Given** Item Serialized
**When** Staff membuat baris Stock-nya
**Then** dibatasi permanen 1 baris Stock per Item, kuantitas selalu 1

**Given** Staff tidak eksplisit memilih Warehouse saat membuat Stock
**When** disimpan
**Then** sistem resolve ke Default Warehouse (Global Settings) saat itu dan menyimpannya sebagai nilai tetap (snapshot) — perubahan Default Warehouse nanti tidak memindahkan Stock yang sudah ada

**Given** 2 proses mencoba me-reserve unit Stock yang sama secara bersamaan
**When** keduanya dieksekusi
**Then** hanya 1 statement atomic `UPDATE...WHERE (quantity_on_hand - quantity_reserved) >= N` yang berhasil — tidak ada over-reservation (NFR1)

### Story 3.7: Item Visibility Based on Stock & Order Status

As a (calon) Customer via portal,
I want melihat status ketersediaan Item yang akurat,
So that saya tahu Item mana yang benar-benar bisa dibeli vs sedang ditahan Order lain.

**Acceptance Criteria:**

**Given** Item Pooled dengan Stock tersisa = 0, tapi masih ada Order belum Selesai yang menahan reservasi terakhirnya
**When** availability-check dipanggil
**Then** Item tetap tampil berlabel "Sedang Di-restock"

**Given** Item Pooled Stock=0 dan tidak ada Order pending yang menahannya
**When** availability-check dipanggil
**Then** Item tidak tampil

**Given** Item Serialized yang unitnya sudah terjual tuntas (Order Selesai)
**When** availability-check dipanggil
**Then** Item tidak tampil lagi

**Given** Item Serialized sedang dalam Order belum Selesai
**When** availability-check dipanggil
**Then** Item tetap tampil berlabel "Sedang Proses Penjualan"
**And** service ini adalah service yang sama dipakai Cart (FR-8, Epic 4) — 1 mekanisme, bukan 2 query terpisah

## Epic 4: Order — Cart, Checkout, Fulfillment, Cancel/Retur & Promo

Siklus transaksi lengkap dari Cart Customer sampai Order Selesai: Checkout+OTP, 3-layer status independen (Payment/Order/Shipping), fulfillment Shipment+AWB per Warehouse, auto-cancel payment timeout, akses tokenized + Review pasca-Delivered, Cancel/Retur 3-cabang dengan maker-checker, Promo dengan redemption atomic, dan CS Interaction Log.

### Story 4.1: Approvals Inbox (Cross-Domain Aggregation Surface)

As a Staff dengan Approval Role,
I want 1 layar terpusat menampilkan semua item yang menunggu persetujuan saya lintas domain,
So that saya tidak perlu bolak-balik cek tiap domain satu-satu.

**Acceptance Criteria:**

**Given** Staff dengan Approval Role membuka Approvals Inbox
**When** halaman dimuat
**Then** semua entity pending yang izinnya dia pegang ditampilkan sebagai baris typed (icon+label), diambil lewat 1 shared contract `pendingFor(User $staff): Collection<PendingApprovalItem>` yang dipanggil ke tiap domain pemilik lewat Service-nya sendiri (bukan query lintas-domain langsung) — dimulai dari Item Draft (Epic 3) sebagai instance pertama yang terdaftar

**Given** baris pending ditampilkan
**When** Staff melihat baris tsb
**Then** compact preview + submitted-by/at terlihat tanpa perlu buka detail penuh

**Given** Staff tidak memegang permission approve untuk tipe entity tertentu
**When** halaman dimuat
**Then** baris entity tsb tidak muncul sama sekali baginya

**And** approve/reject dari Inbox memanggil balik ke Service domain pemilik yang sama — Inbox sendiri tidak memutasi data langsung

### Story 4.2: Manage Customer

As a system/Staff,
I want Customer otomatis create-or-find by email saat checkout,
So that Customer tidak perlu bikin akun untuk belanja, dan Staff tetap bisa lihat riwayatnya.

**Acceptance Criteria:**

**Given** Customer checkout dengan email belum terdaftar
**When** checkout diproses
**Then** Customer record baru dibuat otomatis tanpa mewajibkan password

**Given** email sudah terdaftar
**When** checkout diproses
**Then** Customer existing dipakai (find, bukan duplikat)

**Given** skema Customer auth-ready (kolom password/email_verified_at nullable)
**When** dicek
**Then** tidak butuh migrasi tambahan nanti untuk Member Login v2+

**Given** Staff membuka Customer detail
**When** dilihat
**Then** riwayat Order Customer tsb tampil

### Story 4.3: Manage Cart

As a Customer,
I want menambah/menghapus Item ke Cart tanpa reservasi stok,
So that saya bisa kumpulkan barang incaran tanpa mengunci stok untuk orang lain.

**Acceptance Criteria:**

**Given** Customer add-to-cart Item (Pooled/Serialized)
**When** ditambahkan
**Then** tidak ada reservasi/lock Stock — Cart murni representasi keinginan

**Given** Cart berisi Item
**When** portal panggil availability-check on-demand (Story 3.7)
**Then** status ketersediaan tampil konsisten dengan listing katalog

**And** domain Event (StockReserved/StockReleased/ItemPublished/ItemUnpublished) di-dispatch tiap ketersediaan berubah — Bazaar tidak menyediakan broadcast/WebSocket

### Story 4.4: Order Status Model Setup

As a Staff Order,
I want Order dilacak lewat 3 layer status independen,
So that saya bisa memahami tahap pembayaran, order, dan pengiriman terpisah tanpa status tunggal yang bercampur.

**Acceptance Criteria:**

**Given** sebuah Order
**When** dibuat
**Then** Order membawa 3 kolom status independen (Payment/Order/Shipping Status), masing-masing state machine dengan guard-check sendiri

**Given** Payment Status berubah
**While** Shipping Status belum berubah
**Then** keduanya tidak saling memengaruhi — Paid+Not Shipped valid & normal

**Given** Staff membuka Order detail
**When** dilihat
**Then** ketiga status tampil sebagai 3 pill group terpisah, tidak pernah digabung 1 kolom

### Story 4.5: Checkout & Order Creation

As a Customer,
I want checkout dari Cart menjadi Order lewat verifikasi OTP,
So that pesanan saya terjamin valid dan tidak double-booked.

**Acceptance Criteria:**

**Given** Customer mulai checkout (semua guest)
**When** proses berjalan
**Then** step verifikasi OTP wajib dilalui sebelum Order dianggap Processing

**Given** OTP berhasil
**When** sistem re-validasi tiap Item di Cart
**Then** Pooled dicek kuantitas ≤ Stock tersedia, Serialized dicek unit belum direservasi pembeli lain

**Given** ada Item gagal validasi
**When** hasil dikembalikan
**Then** daftar Item spesifik yang tidak tersedia ditampilkan; Order tidak dibuat sampai Cart disesuaikan

**Given** semua Item lolos validasi
**When** Order dibuat
**Then** reservasi terjadi atomic pada momen yang sama (NFR1), Order baru: Payment=Pending, Order=Processing, Shipping=Not Shipped
**And** reservasi checkout dipin ke momen mulai checkout-attempt (sebelum OTP terkirim, bukan sesudah sukses)

### Story 4.6: Manage Shipment

As a Staff Order,
I want memicu fulfillment per-Shipment lewat 1 tombol aksi,
So that saya tidak perlu input manual AWB dan status pengiriman tetap akurat.

**Acceptance Criteria:**

**Given** Order eligible (Payment=Paid) melibatkan >1 Warehouse
**When** sistem membuat Shipment
**Then** 1 Shipment per Warehouse berbeda, tanpa algoritma alokasi tambahan

**Given** Staff klik "Buat AWB"
**When** vendor call sukses
**Then** kurir & AWB terisi otomatis, Shipping→Shipped

**Given** Staff klik "Buat AWB" dua kali pada Shipment sama
**When** aksi kedua dieksekusi
**Then** tidak ada AWB duplikat — idempotent (NFR3)

**Given** vendor call gagal
**When** Staff melihat status
**Then** failure state jelas + Retry tanpa duplikasi data; kegagalan berlanjut → input manual courier+AWB sebagai fallback

**Given** AWB berhasil dibuat
**When** Staff klik cetak
**Then** surat jalan/resi tercetak dari data Shipment
**And** status pengiriman sinkron via webhook/polling sampai Delivered; tombol refresh/override manual sebagai fallback

### Story 4.7: Order Payment Timeout Auto-Cancellation

As a system,
I want Order yang tidak dibayar dalam Timeout Timer otomatis dibatalkan,
So that Stock tertahan tidak terkunci selamanya oleh Order yang gagal bayar.

**Acceptance Criteria:**

**Given** Order Payment masih Pending melewati batas Timeout Timer
**When** scheduled job berjalan
**Then** job re-check state Order sebagai guard condition sebelum bertindak

**Given** guard condition mengonfirmasi Pending & lewat waktu
**When** auto-cancel dieksekusi
**Then** Order→Cancelled, reservasi dilepas lewat 1 jalur transisi yang sama (tanpa approval)

**Given** auto-cancel terjadi
**When** selesai
**Then** Customer menerima email notifikasi Order dibatalkan (via Notification Template, Epic 2)

### Story 4.8: Order Detail Access & Review

As a Customer,
I want mengakses detail Order tanpa login dan memberi Review setelah barang diterima,
So that saya bisa memantau pesanan dan menyampaikan pengalaman saya dengan mudah.

**Acceptance Criteria:**

**Given** Shipping Status→Delivered
**When** event terjadi
**Then** Customer menerima email: pemberitahuan barang diterima + link tokenized unik per Order + form Review + batas waktu submit

**Given** Customer membuka link tokenized
**When** diakses
**Then** riwayat Order lengkap tampil bertahap tanpa login

**Given** Customer submit Review dalam batas waktu
**When** diterima
**Then** Order→Completed, Staff Approval Role dapat notifikasi in-app moderasi (Epic 2 Story 2.3)

**Given** Customer tidak submit sampai lewat waktu
**When** timer lewat
**Then** Order otomatis→Completed tanpa Review, tanpa notifikasi moderasi

**Given** jangka akses tokenized (default H+5) terlewati
**When** diakses
**Then** halaman tertutup; Staff lihat catatan inline link kedaluwarsa (informasional, tak memblok aksi lain)

### Story 4.9: Cancel & Retur Order

As a Customer (lewat CS)/Staff,
I want mengajukan & memproses pembatalan/retur sesuai tahap fulfillment,
So that permintaan ditangani konsisten dan bisa dipertanggungjawabkan.

**Acceptance Criteria:**

**Given** Order belum registrasi AWB
**When** Customer minta batal
**Then** Cancel Request+alasan → Order tetap Processing selama Pending → notifikasi ke Role approve → Approved: Order→Cancelled+reservasi dilepas; Rejected: tak berubah

**Given** AWB ada, barang belum fisik dikirim
**When** Customer minta batal
**Then** diproses sebagai Return Request → Order→Return Requested selama Pending → Approved: Staff void AWB manual, input foto+catatan, "Retur Selesai" → Shipping→AWB Voided, Order→Returned, Item Serialized republish/Stock Pooled dikembalikan; Rejected: kembali ke status sebelumnya

**Given** barang sudah fisik dikirim
**When** Customer minta retur
**Then** Return Request sama, tapi approve → Shipping→Returned to Warehouse (bukan AWB Voided), Order→Returned

**Given** semua Cancel/Retur (New maupun Second)
**When** diproses
**Then** lewat 1 alur generic sama, tanpa self-service otomatis
**And** tidak ada status "Retur Ditolak" terpisah — barang tak sesuai deskripsi diselesaikan manual di luar sistem

### Story 4.10: Manage Promo

As a Staff Marketing,
I want membuat Promo dengan kuota & limit redemption,
So that saya bisa menjalankan kampanye diskon tanpa risiko over-redeem.

**Acceptance Criteria:**

**Given** Staff membuat Promo (diskon %/nominal, kuota total, limit per-Customer, periode aktif)
**When** disimpan
**Then** Promo aktif otomatis sesuai periode

**Given** kuota tersisa 1 dan 2 checkout redeem bersamaan
**When** keduanya dieksekusi
**Then** hanya 1 berhasil (atomic, NFR2) — tanpa double-spend

### Story 4.11: CS Interaction Log

As a Staff CS,
I want mencatat riwayat interaksi terkait sebuah Order,
So that ada jejak komunikasi sebelum saya eksekusi Cancel/Retur/Refund.

**Acceptance Criteria:**

**Given** Staff CS menerima komunikasi Customer
**When** mencatat entri (channel, catatan)
**Then** tersimpan dengan waktu & staff pencatat otomatis, terlihat di tab CS History Order detail

**Given** Staff akan eksekusi Cancel/Retur (4.9) atau Refund (Epic 5)
**When** buka CS History
**Then** riwayat relevan sudah tersedia sebagai dasar keputusan

## Epic 5: Finance — Payment & Refund

Sistem mencatat & merekonsiliasi transaksi Payment per gateway; Staff Finance dapat mengajukan Refund Request (nominal dibatasi rentang Minimum–Maximum Refund %) yang disetujui Role terpisah sebelum transfer dana manual dieksekusi di luar sistem.

### Story 5.1: Manage Payment

As a Staff Finance,
I want sistem mencatat transaksi Payment per Order secara otomatis,
So that saya bisa merekonsiliasi pemasukan per gateway tanpa mencatat manual.

**Acceptance Criteria:**

**Given** Order menerima Payment lewat gateway aktif (Midtrans Core API, dikonfigurasi Story 1.6)
**When** transaksi terjadi
**Then** sistem mencatat gateway, metode pembayaran spesifik, jumlah, status (Pending/Success/Failed), dan referensi/ID transaksi dari gateway

**Given** Staff melihat daftar metode pembayaran tersedia
**When** portal meminta daftar metode
**Then** hanya metode yang di-enable Staff (Story 1.6) yang ditampilkan — bukan seluruh metode yang didukung gateway

**Given** Payment tercatat
**When** harga Item berubah setelahnya
**Then** jumlah yang tercatat di Payment tidak ikut berubah — snapshot final
**And** nilai uang disimpan sebagai whole-Rupiah integer (unsigned bigint), bukan desimal/float (AD-19)

### Story 5.2: Manage Refund

As a Staff Finance,
I want mengajukan Refund Request atas Order Cancelled/Returned dengan nominal terbatas,
So that pengembalian dana terkontrol dan tidak melebihi kebijakan instalasi klien.

**Acceptance Criteria:**

**Given** Order berstatus Cancelled (dengan Payment sempat Paid) atau Returned
**When** Staff membuka form Refund Request
**Then** field nominal pre-filled dengan Maximum Refund % dari total Order — selalu valid saat form dibuka

**Given** Staff menurunkan nominal
**When** disubmit
**Then** nominal wajib berada di rentang Minimum–Maximum Refund % (Story 1.6); di luar rentang ditolak dengan rentang Rupiah valid ditampilkan sebagai panduan

**Given** Refund Request diajukan
**When** tersimpan
**Then** berstatus Pending, notifikasi in-app dikirim ke Role dengan permission approve Refund

**Given** Refund Request disetujui
**When** approval terjadi
**Then** Payment Status Order→Refunded sejumlah nominal diajukan; catatan mengingatkan Staff bahwa transfer dana aktual dilakukan manual di luar sistem (Bazaar tidak memanggil API refund gateway apapun)

**Given** Refund Request ditolak
**When** approval terjadi
**Then** tidak ada perubahan Payment Status, tidak ada dana yang perlu ditransfer

## Epic 6: Konten & Marketing

Staff Content dapat mengelola Hero Banner, Blog & Page (maker-checker sama seperti Item), Featured Item, dan metadata SEO per-entity — seluruhnya disediakan lewat Service/API untuk portal merender.

### Story 6.1: Manage Hero Banner

As a Staff Content,
I want membuat & mengatur Hero Banner dengan jadwal aktif,
So that promosi utama toko selalu relevan dan terjadwal tanpa campur tangan developer.

**Acceptance Criteria:**

**Given** Staff membuat Hero Banner (gambar, link, jadwal periode aktif)
**When** disimpan
**Then** Banner tersedia lewat Service/API untuk portal merender; gambar melewati pipeline compress+WebP yang sama (FR-36)

**Given** beberapa Banner aktif bersamaan
**When** Staff mengatur urutan tampil
**Then** urutan tersimpan dan tersedia ke portal sesuai urutan tsb

### Story 6.2: Manage Blog & Page

As a Staff Content,
I want membuat, mengedit, dan mempublish entry Blog dan Page lewat editor Markdown,
So that saya bisa mempublikasikan konten marketing tanpa maker-checker Item mengulang logic yang sama dari nol.

**Acceptance Criteria:**

**Given** Staff membuat entry Blog/Page baru dengan taksonomi terpisah (Blog Category/Page Category)
**When** disimpan
**Then** entry berstatus Draft, memakai maker-checker Shape 1 yang sama seperti Item (Story 3.5) — hanya Staff dengan Approval Role bisa publish, tanpa Reject formal

**Given** entry Blog/Page
**When** form dibuka
**Then** wajib tepat 1 Feature Image (alt text default ke judul entry bila kosong), field Slug editable, dan field metadata SEO lengkap (FR-24) dengan fallback yang sama seperti Item/Category (OG→Meta→judul entry; OG Image→gambar utama entry)
**And** body entry disimpan dalam skema Markdown lewat rich text editor

### Story 6.3: Manage Featured Item

As a Staff Content,
I want memilih & mengatur urutan Item Published untuk ditonjolkan,
So that produk unggulan mendapat visibilitas lebih di portal.

**Acceptance Criteria:**

**Given** hanya Item berstatus Published (Epic 3)
**When** Staff mencoba menandai Featured
**Then** hanya Item Published yang bisa dipilih — Item Draft tidak muncul sebagai kandidat

**Given** beberapa Item ditandai Featured
**When** Staff mengatur urutan tampil
**Then** urutan tersimpan dan tersedia ke portal

### Story 6.4: SEO Metadata Fallback Verification (Lintas Entity)

As a Staff SEO,
I want fallback metadata SEO bekerja konsisten di semua entity (Item, Category, Blog, Page),
So that halaman yang belum lengkap SEO-nya tetap punya representasi wajar di mesin pencari & social share.

**Acceptance Criteria:**

**Given** entity manapun (Item/Category/Blog/Page) dengan OG Title/Description kosong
**When** metadata di-resolve
**Then** sistem pakai Meta Title/Description sebagai fallback

**Given** Meta Title/Description juga kosong
**When** di-resolve
**Then** sistem pakai nama/judul entity

**Given** entity benar-benar tidak punya gambar sama sekali
**When** OG Image di-resolve
**Then** fallback berjenjang: gambar utama entity → default OG image dari Global SEO Defaults (Story 1.7)
**And** perilaku ini diverifikasi identik di keempat entity (tidak ada logic fallback yang berbeda antar domain, karena semua pakai 1 mekanisme, per AD-30)

## Epic 7: SEO & Discoverability

Bazaar menyediakan feed Sitemap & Structured Data real-time, Redirect Manager, dan Broken Link (404) tracking — seluruhnya data/config API tipis yang dikonsumsi portal sendiri, konsisten dengan prinsip headless. (Media Upload Optimization/FR-36 sudah dipindah ke Epic 1 sebagai infrastruktur dasar.) Epic ini ditempatkan sebelum Reporting karena Architecture Spine menetapkan Reporting membaca domain Seo (`Reporting -.reads.-> Seo`) — bukan sebaliknya.

### Story 7.1: Sitemap Data Feed

As a Developer (portal),
I want mengambil daftar entity siap-index lewat Service/API,
So that saya bisa membangun sitemap.xml portal sendiri tanpa Bazaar merender file itu.

**Acceptance Criteria:**

**Given** Item/Category/Blog/Page berstatus Published
**When** feed dipanggil
**Then** daftar entity tsb dikembalikan beserta Slug + waktu update terakhir

**Given** sebuah entity baru saja di-unpublish
**When** feed dipanggil lagi
**Then** entity tsb hilang dari hasil secara real-time (query langsung, bukan cache basi — NFR5)
**And** Bazaar tidak menghasilkan atau meng-hosting file sitemap.xml apapun

### Story 7.2: Structured Data Feed (Schema.org)

As a Developer (portal),
I want mengambil data Item terstruktur sesuai schema.org Product,
So that saya bisa menyuntikkan JSON-LD di halaman produk portal saya.

**Acceptance Criteria:**

**Given** sebuah Item Published
**When** feed dipanggil
**Then** harga, status ketersediaan (dari Story 3.7), dan agregat rating Review (dari Story 4.8) dikembalikan sebagai data terstruktur
**And** field yang dikembalikan murni agregasi data existing — tidak ada field baru yang perlu diisi manual oleh Staff
**And** Bazaar tidak pernah menyuntikkan tag `<script type="application/ld+json">` — itu tanggung jawab portal

### Story 7.3: Redirect Manager

As a Staff SEO,
I want mengelola mapping URL lama ke target baru,
So that Item/Category/Blog/Page yang dipindah/discontinue tidak meninggalkan link mati di mesin pencari.

**Acceptance Criteria:**

**Given** Staff mendaftarkan path/slug lama + target baru (entity Bazaar via referensi, atau URL bebas) + alasan opsional
**When** disimpan
**Then** mapping tersimpan dan tersedia lewat Service/API untuk portal
**And** Bazaar sendiri tidak mengeksekusi HTTP redirect 301 apapun — portal yang query/sync mapping ini dan menjalankan redirect di sisi routing-nya sendiri

### Story 7.4: Broken Link (404) Tracking

As a Staff SEO,
I want menerima laporan 404 dari portal,
So that saya bisa memantau & menindaklanjuti link mati dari satu tempat.

**Acceptance Criteria:**

**Given** portal mengirim event 404 (url, timestamp, referrer opsional) ke 1 API endpoint
**When** payload diterima
**Then** tersimpan ke `BrokenLinkLog`, tersedia untuk dikonsumsi Reporting → SEO Health (Epic 8 Story 8.7) begitu epic itu dibangun

**Given** endpoint ini tidak terautentikasi (tidak ada identitas Customer/User)
**When** menerima request
**Then** endpoint berada di belakang rate-limiting middleware sejak awal (NFR8/AD-32) — mencegah volume tak terbatas dari sumber tak terverifikasi
**And** pelaporan murni pasif — Bazaar tidak melakukan crawling/pengecekan link sendiri

## Epic 8: Reporting

Staff (termasuk Owner) mendapat 1 area laporan read-only lintas seluruh domain — Sales & Revenue, Customer Analytics, Inventory, Marketing & Promo, Operational & Support, SEO Health — dengan filter rentang tanggal, export CSV/XLSX/PDF, dan scheduled report.

### Story 8.1: Reporting Shell — Date Filter, Export & Scheduled Report Infrastructure

As a Staff,
I want mekanisme filter tanggal, export, dan scheduled report yang seragam di semua laporan,
So that saya tidak perlu mempelajari cara berbeda tiap kategori laporan.

**Acceptance Criteria:**

**Given** Staff membuka laporan manapun
**When** memilih filter rentang tanggal (hari ini/7 hari/30 hari/kustom)
**Then** data laporan menyesuaikan tanpa reload penuh

**Given** Staff memilih export
**When** memilih format (CSV/XLSX/PDF)
**Then** file ter-generate via job queued (`maatwebsite/excel`/`barryvdh/laravel-dompdf`, AD-28), tidak blocking request

**Given** Staff mengatur scheduled report (laporan apa, filter, format, penerima, jadwal)
**When** disimpan sebagai `ReportSchedule`
**Then** generasi berjalan queued sesuai jadwal, dan pengiriman ke email penerima lewat Notification Service (bukan mail langsung) sehingga tercatat di Notification Log (AD-13 exception)

### Story 8.2: Sales & Revenue Reports

As a Staff/Owner,
I want melihat Sales Summary, Sales by Product/SKU, dan Payment & Shipping share,
So that saya paham performa penjualan tanpa membuka Order satu-satu.

**Acceptance Criteria:**

**Given** rentang tanggal dipilih
**When** Sales Summary dibuka
**Then** Gross Revenue, Net Revenue, Total Orders, AOV, Total Discount tampil per periode harian/mingguan/bulanan/tahunan

**Given** rentang tanggal dipilih
**When** Sales by Product/SKU dibuka
**Then** nama Item, SKU, Quantity Sold, Total Revenue per Item tampil

**Given** rentang tanggal dipilih
**When** Payment & Shipping dibuka
**Then** share metode pembayaran per Payment Gateway aktif dan share kurir pengiriman tampil

### Story 8.3: Customer Analytics Reports

As a Staff/Owner,
I want melihat New vs Returning Customers, Abandoned Cart, dan Customer Demographics,
So that saya paham perilaku pembeli.

**Acceptance Criteria:**

**Given** rentang tanggal dipilih
**When** New vs Returning dibuka
**Then** jumlah Customer baru, tingkat kembali, dan frekuensi pembelian tampil

**Given** rentang tanggal dipilih
**When** Abandoned Cart dibuka
**Then** tingkat Cart ditinggalkan, estimasi nilai hilang, dan tahap funnel drop-off tampil

**Given** rentang tanggal dipilih
**When** Customer Demographics dibuka
**Then** kota/provinsi pembeli terbanyak tampil

### Story 8.4: Inventory Reports

As a Staff Catalog/Owner,
I want melihat Low Stock Alert, Stock Turnover Rate, dan Dead Stock,
So that saya bisa bertindak sebelum kehabisan atau kelebihan stok mati.

**Acceptance Criteria:**

**Given** ambang stok aman dikonfigurasi
**When** Low Stock Alert dibuka
**Then** Item di bawah ambang tampil dengan status reorder

**Given** rentang tanggal dipilih
**When** Stock Turnover Rate dibuka
**Then** Days Sales of Inventory & Item paling cepat laku tampil

**Given** ambang waktu Dead Stock (default >90 hari, dikonfigurasi)
**When** dibuka
**Then** Item tanpa penjualan dalam jangka waktu tsb tampil beserta jumlah unit & nilai modal tertahan
**And** klik Item pada laporan manapun mengarahkan Staff ke surface Catalog nyata (Epic 3) untuk mengedit — laporan sendiri tidak pernah memutasi data

### Story 8.5: Marketing & Promo Report

As a Staff Marketing/Owner,
I want melihat Coupon Performance,
So that saya bisa menilai efektivitas tiap kode Promo.

**Acceptance Criteria:**

**Given** rentang tanggal dipilih
**When** Coupon Performance dibuka
**Then** kode Promo, jumlah pemakaian, total penjualan yang dihasilkan, dan biaya diskon tampil per kode

### Story 8.6: Operational & Support Reports

As a Staff Order/Owner,
I want melihat Fulfillment SLA dan Return & Refund Report,
So that saya bisa menilai kecepatan tim & tingkat retur.

**Acceptance Criteria:**

**Given** rentang tanggal dipilih
**When** Fulfillment SLA dibuka
**Then** rata-rata waktu packing & waktu sampai Shipment terkirim tampil

**Given** rentang tanggal dipilih
**When** Return & Refund Report dibuka
**Then** tingkat Retur, total nilai Refund, dan alasan Retur terbanyak tampil

### Story 8.7: SEO Health Reports

As a Staff SEO/Owner,
I want melihat Content Completeness dan Broken Link (404),
So that saya tahu konten mana yang perlu dilengkapi dan URL mana yang perlu di-redirect.

**Acceptance Criteria:**

**Given** rentang tanggal dipilih
**When** Content Completeness dibuka
**Then** jumlah Item/Blog/Page yang kehilangan deskripsi, gambar, atau metadata SEO tampil

**Given** portal sudah melaporkan 404 (FR-40, Epic 7 Story 7.4)
**When** Broken Link dibuka
**Then** daftar & jumlah URL 404 dari `BrokenLinkLog` tampil
