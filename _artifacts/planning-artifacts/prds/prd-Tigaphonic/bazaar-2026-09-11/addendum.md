# Addendum: Bazaar — Technical & Mechanism Detail

*Companion to `prd.md`. Holds architecture-how content that doesn't belong in the PRD narrative: mechanism/transport decisions, schema-level detail, rejected-alternative rationale. PRD sections cross-reference here with "→ addendum". This is input to `bmad-architecture`, not a spec to re-litigate the PRD's product decisions.*

## A. Architecture Principles (source: `_artifacts/business-draft/brandstore-package-brief.md` §3, carried forward unchanged)

| # | Prinsip | Ringkasan |
|---|---|---|
| P1 | **Single Package** | Semua domain logic + admin panel dalam 1 package Composer, bukan dipecah jadi core/admin/gateway terpisah — hindari overhead maintenance versi lintas repo. |
| P2 | **Admin dikunci ke Filament** | Admin panel dibangun & disediakan langsung pakai Filament di dalam package ini. Tidak dirancang untuk swap ke panel admin lain. Styling/theme di-override lewat mekanisme native Filament per project client. |
| P3 | **Customer Portal sepenuhnya bebas/headless** | Tidak dipaketkan sama sekali. Package expose Service layer (Blade+Livewire monolith) dan opsional API tipis (Sanctum + API Resource), config-registered, tidak dipaksa aktif. |
| P4 | **Gateway (Payment/Shipping) hidup di dalam package** | Interface `PaymentGateway` / `ShippingGateway` didefinisikan di package ini; implementasi konkret juga di package yang sama, pakai SDK resmi/komunitas sebagai dependency biasa. Tidak ada rencana ekstraksi ke package gateway reusable terpisah kecuali ada bukti kebutuhan nyata. |
| P5 | **Domain-grouped code organization** | Business logic dikelompokkan per domain namespace, masing-masing Models/Services/Actions/Enums/States/Events/Filament sesuai kebutuhan. |
| P6 | **Service → Action → Model layering** | Service (utama) → Action (escape hatch operasi kompleks) → Model method (predikat reusable, no mutating side-effect). Presentation layer (Filament Resource, Controller, Blade) tidak boleh berisi business logic. |
| P7 | **Cross-domain lewat service, bukan mutasi model langsung** | Domain A yang butuh domain B wajib lewat Service domain B. Dependency 1 arah per domain; side-effect yang melawan arah dependency pakai Event. |
| P8 | **Snapshot principle** | Master data yang bisa berubah (harga, alamat warehouse, biaya ongkir, konten promo) wajib di-snapshot ke record transaksi saat transaksi terjadi — tidak resolve "live". |
| P9 | **Atomic reservation, bukan read-then-write** | Semua operasi kunci stok wajib pakai conditional atomic update (1 SQL statement, WHERE guard) — cegah race condition. |
| P10 | **Unified inventory model** | Barang New (Pooled) dan Second (Serialized) pakai 1 mekanisme reservasi & 1 bentuk skema yang sama — bukan 2 sistem locking berbeda. |

## B. Package Folder Structure (source: brief §4, diperbarui mengikuti domain final PRD)

```
src/
  Catalog/        Models: Brand, Category, AttributeTemplate, Item, Stock, Warehouse
  Order/          Models: Customer, Order, Cart, CartItem, Shipment, CancelRequest, ReturRequest, RefundRequest, Promo, CsInteraction, Review
                  States: PaymentStatus, OrderStatus, ShippingStatus
  Payment/        Models: Payment, Refund. Contracts/PaymentGateway.php, Gateways/ (implementasi konkret per provider)
  Shipping/       Contracts/ShippingGateway.php, Gateways/
  User/           Models: User, Role, Permission, AuditLog
  Content/        Models: HeroBanner, Blog, Page, BlogCategory, PageCategory, FeaturedItem, SeoMeta
  Reporting/      Services (read-only, konsumsi lintas domain)
  Notification/   Models: NotificationTemplate, NotificationLog, StaffNotification
  Settings/       Models/Services/Filament (List+Edit only)
  Seo/            Models: RedirectMapping, BrokenLinkLog. Services: SitemapFeed, StructuredDataFeed (§4.10)
  Install/        Artisan command bazaar:install (§4.9 FR-33)
  Http/Api/       opsional, config-registered, untuk client headless (§4.9 FR-35)
```

**Tidak ada kolom `tenant_id`** di skema manapun — setiap instalasi Bazaar berdiri sendiri per client (app + database terpisah), bukan multi-tenant SaaS dengan isolasi data berbasis kolom (lihat prd.md §1 Vision).

Distribusi package tetap tunggal (P1) — rasional lengkap → lihat bagian I.

## C. Inventory Model — Item, Stock, Warehouse (source: brief §6.1)

3 tabel terpisah, `Stock` sebagai ledger/pivot antara `Item` dan `Warehouse`:

```
Item                              Stock (ledger)                    Warehouse
─────────────────────             ─────────────────────             ─────────────────────
id                                 id                                 id
brand_id (FK)                      item_id (FK → Item)                name
sub_category_id (FK)               warehouse_id (FK → Warehouse)      address_line, city, dst
condition (New/Second)             quantity_on_hand                   (dipakai juga sbg origin
inventory_strategy                 quantity_reserved                   address utk shipping)
grading (nullable, khusus Second)
attribute_snapshot (JSON)
price
media
publish_status (Draft/Published)
```

**1 mekanisme untuk Pooled & Serialized** (bukan 2 sistem locking terpisah):
- **Reservasi** (checkout dimulai, §4.2 FR-9): `UPDATE stock SET quantity_reserved = quantity_reserved + N WHERE id=? AND (quantity_on_hand - quantity_reserved) >= N` — 1 statement atomic (P9).
- **Commit** (Payment settlement): kurangi `quantity_on_hand` & `quantity_reserved` bareng sejumlah N.
- **Release** (payment timeout §4.2 FR-31, atau Retur disetujui §4.2 FR-13): kurangi `quantity_reserved` saja (release), atau tambah `quantity_on_hand` lagi (Retur — barang fisik balik ke gudang).

Untuk **Serialized**: `quantity_on_hand` dikunci permanen di 1; `quantity_reserved` cuma toggle 0/1 — mereplikasi Available → On Process → Sold lewat mekanisme kuantitas yang sama, bukan enum flag terpisah.

**v1 vs permanen**: Pooled dibatasi 1 baris `Stock` per Item di level **validasi Service** (bukan DB constraint) — bisa dilonggarkan tanpa migrasi nanti. Serialized dibatasi 1 baris per Item **permanen** — bukan keterbatasan teknis yang akan di-backlog-kan.

**Resolusi Default Warehouse**: kolom `warehouse_id` di `Stock` tidak pernah null. Kalau staff tidak eksplisit pilih warehouse, sistem resolve ke Default Warehouse (Global Settings) dan simpan sebagai snapshot di baris `Stock` (P8) — bukan reference hidup.

**Item Visibility (§4.1 FR-30, baru sesi ini)**: query availability-check (dipakai Cart §4.2 FR-8 dan portal listing) mengevaluasi `quantity_on_hand - quantity_reserved` dan keberadaan Order aktif (non-Completed) yang menahan unit terakhir, bukan cuma `quantity_on_hand` mentah — implementasinya perlu join/subquery ke Order melalui Stock reservation record, bukan murni baca kolom Stock.

## C2. Attribute Template Snapshot (§4.1 FR-3, baru sesi ini)

Tiap Item menyimpan `attribute_snapshot` (JSON, lihat bagian C) sebagai salinan bentuk Attribute Template Sub-Category-nya **pada saat Item dibuat/terakhir diselaraskan** — bukan referensi hidup (foreign key) ke `AttributeTemplate`. Konsekuensi implementasi:
- Mengedit `AttributeTemplate` (tambah/hapus/ganti tipe field) hanya menulis ke tabel master, tidak menyentuh `attribute_snapshot` milik Item manapun.
- Form edit Item membandingkan `attribute_snapshot` Item dengan definisi `AttributeTemplate` terkini milik Sub-Category-nya (diff field-by-field) untuk menampilkan peringatan (§4.1 FR-3).
- Tombol penyelarasan menjalankan merge terkontrol: field yang ada di master tapi tidak di snapshot → ditambahkan ke snapshot (kosong); field yang ada di snapshot tapi sudah dihapus dari master → dihapus dari snapshot. Field yang ada di keduanya tidak disentuh (nilai Staff yang sudah diisi tetap).
- Item baru selalu meng-copy definisi `AttributeTemplate` terkini milik Sub-Category yang dipilih ke `attribute_snapshot`-nya saat pertama dibuat.

## D. Order — 3-Layer Status + Shipment (source: brief §6.2, diperluas sesi ini)

3 layer status independen:
- **Payment Status** — cerminan callback payment gateway: Pending, Paid, Expired, Denied, Cancelled, Refunded, Disputed.
- **Order Status** (§4.2 FR-10, nilai minimal ditetapkan PRD): Processing, Cancelled, Return Requested, Returned, Completed.
- **Shipping Status** (idem): Not Shipped, Shipped, Delivered, AWB Voided, Returned to Warehouse.

Karena Warehouse mendukung multi-lokasi, Shipping Status bukan 1 field flat di Order — `Shipment` adalah entity sendiri, 1 Order bisa punya banyak Shipment (1 per Warehouse yang terlibat). v1: karena tiap Item Pooled dibatasi 1 Warehouse (bagian C), grouping Shipment murni ikut Warehouse yang sudah melekat di tiap Item — tidak ada algoritma alokasi yang perlu dirancang di v1 (lihat §4.2 FR-11).

**Guard condition & state machine**: pola orchestrated (1 DB transaction + rollback) dipakai untuk transisi yang butuh rollback; event-driven (queued, non-blocking) untuk side-effect yang retryable. Scheduled job untuk transisi tertunda (payment timeout §4.2 FR-31, review deadline §4.2 FR-12) wajib re-check state saat ini sebelum eksekusi (guard condition), bukan job cancellation — menghindari race antara cancel-timer vs timer-keburu-fire.

### D2. AWB Registration (§4.2 FR-11, baru sesi ini)

Tombol aksi Staff memanggil `ShippingGateway::createAwb()` (kontrak P4). Idempotency dijamin lewat unique constraint/lock di level Shipment (mis. kolom `awb_number` + status "registering" yang dicek sebelum call kedua diizinkan) — bukan idempotency key dari vendor. Fallback manual: field `awb_number`/`courier` di Shipment tetap writable langsung oleh Staff kalau status Shipment menandakan "manual entry" (vendor call gagal berulang). Sinkronisasi status pengiriman: mekanisme webhook vs polling API cek resi dipilih per vendor (tergantung dukungan `ShippingGateway` konkret) — kontrak abstrak harus mengakomodasi kedua mode.

### D3. Cart & Availability (§4.2 FR-8, baru sesi ini)

Cart tidak membuat row reservasi apapun — murni tabel `CartItem` (customer_id/session, item_id, qty). Availability-check adalah query read-only terhadap Stock (bagian C) yang sama dengan yang dipakai listing portal. Domain Event (`StockReserved`, `StockReleased`, `ItemPublished`, `ItemUnpublished`) di-dispatch dari Service Catalog/Order tiap kali kondisi ketersediaan berubah — portal subscribe lewat listener custom-nya sendiri; Bazaar tidak menyediakan broadcast driver/channel.

### D4. Tokenized Order Page (§4.2 FR-12, baru sesi ini)

Token unik per Order (bukan per Customer) — signed URL atau random token tersimpan di kolom Order, tanpa auth session. Expiry dihitung dari timestamp perubahan Order Status terakhir (bukan dari created_at), memakai nilai Timeout Timer "batas akses halaman tokenized" dari Global Settings (§4.8 FR-28, default H+5) — perlu scheduled job atau lazy-check-on-access untuk menutup akses setelah jangka waktu itu lewat.

### D5. Refund Request Validation (§4.3 FR-17, baru sesi ini)

`Minimum Refund %` dan `Maximum Refund %` (Global Settings, numeric, tanpa default yang dipatok PRD) dikonversi ke rentang Rupiah dari `Order.total` saat form `RefundRequest` (bagian B) dibuka; validasi server-side membandingkan nominal yang disubmit terhadap rentang tersebut — bukan validasi persentase langsung, supaya pembulatan Rupiah konsisten.

## E. Condition vs Inventory Strategy (Catalog domain — source: brief §6.3, disederhanakan sesi ini)

*Ditempatkan di sini, bukan di sebelah C/C2, supaya urutan bagian ini tetap mengikuti nomor bagian 6.x brief sumber — memudahkan lacak-balik ke brief, dengan konsekuensi bagian ini "melompat" keluar dari pengelompokan per-domain.*

Dipisah sejak awal secara skema:
- **Condition** — New / Second (deskriptif; brief awal juga menyebut Refurbished, dicabut oleh keputusan sesi ini — lihat `.memlog.md` entry Catalog FR-4).
- **Inventory Strategy** — Serialized vs Pooled — ditentukan lewat aturan bisnis di Catalog Service, default preset (New→Pooled, Second→Serialized), tidak di-hardcode 1:1 supaya kombinasi lain (mis. barang bekas dijual per lot) tetap bisa diakomodasi tanpa migrasi skema.

## F. Customer — Guest-First, Auth-Ready (source: brief §6.4, unchanged)

v1 fokus penuh guest checkout. Tiap checkout create-or-find Customer record by email. Skema `Customer` disiapkan auth-ready sejak awal (kolom `password`/`email_verified_at` nullable di tabel yang sama) — Member Login aktif nanti bersifat aditif, bukan migrasi data menyatukan 2 tabel.

## G. Reporting — Domain Read-Only (source: brief §6.5, unchanged)

Domain sendiri (bukan disebar ke tiap domain) karena report berguna umumnya lintas domain (mis. margin butuh Order + Payment + Shipping cost + Promo sekaligus). Read-only & downstream dari semua domain lain (P7) — mencegah Service domain lain bengkak berisi query analitik yang bukan tanggung jawab utamanya.

## H. Notification — 3 Concern Terpisah (source: brief §6.6, diperluas sesi ini)

1. **Dispatch/trigger** — tetap tersebar di masing-masing domain lewat Laravel Event (P7), tidak disentralkan.
2. **Setup/Template** (§4.7 FR-26) — konten notifikasi per titik trigger, admin-editable.
3. **Log/Monitoring** (§4.7 FR-27) — riwayat pengiriman, resend manual.

**Staff In-App Notification (§4.7 FR-32, baru sesi ini)** adalah concern ke-4 yang terpisah dari 3 di atas — bukan email/SMS keluar ke Customer, melainkan entry di dashboard Staff (mis. tabel `StaffNotification` dengan `user_id`/`role_id` target, dibaca/di-dismiss per Staff). Sama-sama dipicu lewat Laravel Event per domain, tapi listener & delivery channel-nya berbeda total dari Notification Template/Log Customer-facing.

## I. Why Single Package (source: brief §6.7, unchanged)

Split jadi 3 package (core/admin/gateway) sempat dipertimbangkan, ditolak karena: P2 (admin dikunci Filament) menghilangkan alasan utama pisah admin dari core; P4 (gateway tidak reusable lintas engine lain) menghilangkan alasan pisah gateway; split menanggung matrix kompatibilitas versi antar package + CI/testing terpisah + overhead rilis berkoordinasi — biaya nyata terus-menerus tanpa manfaat yang dibutuhkan. Pemisahan layer (P6) tetap ditegakkan lewat disiplin folder/namespace di dalam 1 package (bagian B), bukan lewat batas package.

## J. Concrete Gateway Targets — v1 (baru sesi ini)

Implementasi konkret pertama dari kontrak `ShippingGateway` dan `PaymentGateway` (P4):

- **Shipping**: RajaOngkir.
- **Payment**: Midtrans, lewat **Core API** — bukan **Snap** (UI checkout bawaan Midtrans). Pilihan ini konsisten dengan P3: Snap adalah hosted UI yang akan melanggar prinsip headless kalau dipakai apa adanya, sementara Core API murni transaksional (tidak membawa UI), cocok dipanggil dari Service layer tanpa membocorkan concern presentasi ke package.

**Konsekuensi untuk boundary headless (P3)**: Bazaar **tidak menyediakan halaman/form pembayaran maupun pengiriman apa pun** — input kartu, pilihan Virtual Account, redirect e-wallet, kalkulasi ongkir di UI, dst. Itu sepenuhnya tanggung jawab customer portal. Yang Bazaar sediakan lewat Service/API layer: buat transaksi Payment ke Midtrans Core API, terima & proses callback/webhook Midtrans untuk update Payment Status, serta panggilan ke RajaOngkir untuk cek ongkir/buat AWB (§4.2 FR-11/D2). Desain tampilan pembayaran & pengiriman di portal sepenuhnya bebas didesain custom oleh klien — ini justru bagian dari value proposition headless Bazaar, bukan keterbatasan.

**Metode pembayaran aktif (§4.8 FR-28, baru sesi ini)**: Midtrans mendukung banyak metode (Credit Card, GoPay, ShopeePay, QRIS, Bank Transfer/VA per bank, dll.), tapi tidak semua otomatis aktif begitu gateway diaktifkan. Global Settings menyimpan daftar metode yang di-enable Staff secara eksplisit (per kode metode Midtrans); Service pembuatan transaksi & Service daftar-metode-tersedia (§4.3 FR-16) sama-sama memfilter terhadap daftar ini sebelum berkomunikasi ke Core API atau mengembalikan hasil ke portal.

## K. SEO & Discoverability Mechanics (§4.10, baru sesi ini)

**Media upload optimization (FR-36)**: dijalankan lewat pipeline konversi (mis. Spatie Media Library conversions, atau Intervention Image) yang di-hook ke tiap titik upload gambar lintas domain (Catalog `Item`, Content `HeroBanner`/`Blog`/`Page`, Settings logo/favicon). Gambar asli tetap disimpan sebagai sumber; hasil kompresi/WebP disimpan sebagai varian terpisah yang disajikan lewat Service/API — bukan menimpa file asli secara permanen.

**Sitemap & Structured Data feed (FR-37, FR-38)**: keduanya Service read-only, dipanggil portal sesuai kebutuhannya sendiri (build-time, on-demand, atau cache berkala di sisi portal) — Bazaar tidak mendorong (push) data ini, portal yang menarik (pull). Tidak perlu endpoint khusus kalau portal sudah pakai API Layer (§4.9 FR-35); untuk konsumen Service Layer langsung (monolith), tinggal panggil method Service yang sama.

**Redirect Manager (FR-39)**: `RedirectMapping` (bagian B) menyimpan path lama, tipe target (entity Bazaar via polymorphic reference, atau URL bebas berupa string), dan alasan. Portal idealnya sync mapping ini secara berkala (bukan query per-request) supaya redirect tetap cepat meski Bazaar sedang tidak dapat diakses.

**Broken Link tracking (FR-40)**: endpoint API menerima payload `{url, timestamp, referrer?}` dari portal, disimpan ke `BrokenLinkLog` (bagian B). Tidak ada rate-limiting/dedup khusus yang ditetapkan PRD ini — kalau volume 404 tinggi (mis. bot scanning), itu detail implementasi (rate-limit, agregasi per-URL) untuk arsitektur, bukan keputusan produk.
