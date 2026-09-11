---
title: Bazaar
created: 2026-09-11
updated: 2026-09-11
status: final
---

# PRD: Bazaar

## 0. Document Purpose
PRD ini ditujukan untuk tim Tigaphonic — developer yang mengimplementasikan Bazaar, dan pengambil keputusan produk. PRD ini juga menjadi basis bagi artefak turunan (UX spec, arsitektur, epics/stories). Kosakata dijangkarkan di Glossary (§3); fitur dikelompokkan dengan Functional Requirement (FR) bernomor global bersarang di dalamnya (§4); asumsi ditandai inline dengan `[ASSUMPTION]` dan diindeks ulang di §9; keputusan yang sengaja ditunda dengan syarat revisit ditandai `[NOTE FOR PM]` di §6.2. PRD ini dibangun di atas draft brief `docs/draft/brandstore-package-brief.md` — detail arsitektur/teknis (prinsip desain P1-P10, mekanisme inventory & order) didokumentasikan di `addendum.md`, bukan diduplikasi di sini.

## 1. Vision
Bazaar adalah Laravel package yang menyediakan seluruh backend domain logic dan admin dashboard (berbasis Filament) untuk sebuah eCommerce Brand Store — mencakup Catalog, Order, Finance, User & Role Management, Content, Reporting, Notification, SEO & Discoverability, dan Settings — dan bersifat headless: package ini tidak pernah merender antarmuka customer, hanya menyediakan Service layer plus API tipis opsional yang bisa dipasangkan ke frontend apa pun (monolith Blade/Livewire atau fully headless Next.js/Vue).

Bagi developer Tigaphonic, Bazaar menghapus kebutuhan membangun ulang backend admin Brand Store dari nol di tiap proyek klien. Setiap instalasi berdiri sendiri per klien (Composer package biasa, bukan SaaS multi-tenant), dan kombinasi fitur bisa diaktifkan berbeda-beda per klien — barang baru saja, second-hand saja, atau hybrid — dari satu foundation yang sama.

Ini penting sekarang karena Tigaphonic berulang kali membangun backend Brand Store dengan pola serupa, termasuk kasus barang second-hand/unique (1 unit = 1 listing, grading kondisi manual, locking atomic per unit) yang tidak dicover package eCommerce generik manapun — baik di ekosistem Laravel/Filament (Lunar PHP paling dekat, namun klaim headless-nya pincang karena tidak memiliki API resmi) maupun platform lain. Bazaar dibangun untuk kebutuhan internal Tigaphonic terlebih dahulu, dengan fondasi yang cukup solid untuk suatu saat di-productize atau open-source jika arahnya ke sana.

## 2. Target User

### 2.1 Jobs To Be Done

**Developer Tigaphonic** *(target user utama Bazaar sebagai product — yang install, konfigurasi per-klien, dan extend)*:
- Memasang backend admin Brand Store yang lengkap (Catalog, Order, Finance, dst.) ke proyek klien baru tanpa membangun ulang dari nol.
- Mengaktifkan/menonaktifkan kombinasi fitur per klien (barang baru saja / second-hand saja / hybrid) tanpa fork package.
- Mengekspos Service layer atau API tipis yang stabil, agar frontend customer klien (monolith atau headless) bisa dibangun di atasnya tanpa terikat pada detail implementasi package.
- Tidak lagi mengulang pekerjaan yang sama di tiap proyek klien; punya satu foundation yang teruji dan bisa diandalkan.

**Staff/Admin toko klien** *(end-user dari hasil jadi — dashboard Filament yang dihasilkan Bazaar — bukan pengguna langsung package-nya)*:
- Mengelola katalog, order, stok, konten, dsb. dari satu dashboard tanpa perlu tahu detail teknis di baliknya.
- Kebutuhan spesifik per peran mengalir dari tiap fitur di §4; journey mereka dipetakan di §2.3 setelah sesi Journey-led.

### 2.2 Non-Users (v1)
- **Customer/end-buyer** — berinteraksi dengan portal customer yang terpisah dan di luar package ini; tidak pernah menyentuh dashboard Bazaar secara langsung.
- **Sisi *beli* dari customer** (buy-outright/trade-in/consignment second-hand) — v1 fokus ke sisi *jual* saja (lihat §5 Non-Goals).

### 2.3 Key User Journeys

- **UJ-1. Andi (Developer Tigaphonic) memasang Bazaar ke proyek klien baru.**
  - **Persona + context:** Andi, developer Tigaphonic, memulai proyek Brand Store baru untuk klien; Laravel app sudah ada dengan Filament panel kosong terpasang.
  - **Entry state:** Belum ada Bazaar; panel Filament kosong.
  - **Path:**
    1. `composer require tigaphonic/bazaar`.
    2. `php artisan bazaar:install` — menyambungkan seluruh Resource Bazaar ke panel Filament yang sudah ada (bukan membuat panel baru).
    3. `php artisan migrate` — inisiasi skema database Bazaar.
    4. Login ke panel, verifikasi tiap domain (Catalog, Order, dst.) berjalan sesuai ekspektasi.
    5. Integrasi frontend: Blade+Livewire lewat Service Layer langsung, atau frontend headless lewat API Layer (diaktifkan via config).
  - **Klimaks:** Panel admin Brand Store lengkap berjalan tanpa Andi menulis logic Catalog/Order/dst. dari nol.
  - **Resolusi:** Andi lanjut ke kustomisasi tema & integrasi frontend spesifik klien.
  - Realizes: FR-33, FR-34, FR-35.

- **UJ-2. Sari (Staff Catalog) membuat & mem-publish Item baru.**
  - **Persona + context:** Sari, Staff Catalog, dapat kiriman produk baru untuk di-listing.
  - **Entry state:** Login ke dashboard; Role Sari tidak punya permission publish.
  - **Path:**
    1. Sari isi Item baru: Brand, Sub-Category, Attribute Template, Warehouse, Stock.
    2. Simpan — Item otomatis Draft.
    3. Rara (Role dengan permission approve publish) cek kelengkapan data di daftar Item Draft.
    4. Rara approve → Item Published, muncul di portal.
  - **Klimaks:** Item tayang di portal tanpa Sari sendiri yang punya kewenangan mem-publish — maker-checker berjalan.
  - **Resolusi:** Sari lanjut ke Item berikutnya.
  - **Edge case:** Kalau data Item kurang lengkap/salah, tidak ada status "Ditolak" formal di sistem — Rara & Sari koordinasi di luar sistem (chat/komunikasi internal); Item tetap Draft sampai Sari revisi dan Rara approve lagi.
  - Realizes: FR-4, FR-3, FR-5.

- **UJ-3. Order dari checkout sampai selesai (jalur normal + Review).**
  - **Persona + context:** Budi (Customer) belanja di Brand Store klien; Dewi (Staff Order) memproses dari sisi admin.
  - **Entry state:** Budi baru selesai checkout — Order Processing, Payment Pending.
  - **Path:**
    1. Dewi dapat notifikasi in-app: Order baru diproses.
    2. Dewi buka detail Order — Payment masih Pending.
    3. Cabang bayar — **Gagal:** Budi tidak bayar dalam batas waktu (Timeout Timer) → Order auto-Cancelled, Budi dapat email order dibatalkan. **Berhasil:** Budi bayar → Dewi dapat notif in-app Payment=Paid; Budi dapat email invoice + link tokenized tracking.
    4. Dewi packing barang (di luar sistem), lalu klik registrasi AWB per Shipment (per grup Warehouse kalau Order lintas Warehouse).
    5. Cabang AWB — **Sukses:** Dewi cetak surat jalan/resi dari respons vendor; Shipping Status → Shipped. **Gagal:** Dewi cek ke CS; kalau masalah sistem sementara, retry dari Bazaar; kalau tidak, registrasi manual di vendor lalu input AWB manual ke Bazaar.
    6. Status Pengiriman berjalan (webhook/manual check) sampai Delivered.
    7. Saat Delivered, Budi dapat email "barang diterima, cek kondisi" + link tokenized + form Review + batas waktu submit sebelum auto-close.
    8. Cabang Review — **Diisi dalam waktu:** Order → Selesai; Dewi dapat notif in-app moderasi Review; Dewi approve/reject tayang. **Tidak diisi:** Order auto-Selesai setelah lewat waktu, tanpa notif moderasi ke Dewi.
  - **Klimaks:** Order tuntas 1 siklus penuh tanpa Staff mengetik status manapun secara paksa — semua status berjalan lewat trigger/otomatis.
  - **Resolusi:** Visibilitas Item di portal mengikuti status Stock+Order — Item Pooled/Serialized tetap tampil berlabel "Sedang Di-restock"/"Sedang Proses Penjualan" selama masih ada Order yang menahannya tapi belum Selesai; hilang dari portal begitu benar-benar habis/terjual tuntas.
  - Realizes: FR-10, FR-11, FR-12, FR-28, FR-30, FR-31, FR-32.

- **UJ-4. Dewi menangani pembatalan & retur.**
  - **Persona + context:** Dewi (Staff), menangani permintaan pembatalan/retur dari Customer lewat CS.
  - **Entry state:** Order dalam berbagai tahap fulfillment.
  - **Path** *(3 cabang tergantung tahap Order):*
    - **(A) Belum ada AWB:** Customer minta batal → Dewi input Cancel Request + alasan → notifikasi in-app ke Role approver → *Approved:* Order → Cancelled. *Rejected:* Order lanjut normal.
    - **(B) AWB ada, barang belum fisik dikirim:** Customer minta batal (jadi Retur, bukan Cancel) → Dewi input Retur Request + alasan → notif ke approver → *Approved:* Dewi void AWB manual (di vendor), barang balik gudang, Dewi input foto+catatan, aksi "Retur Selesai" → Shipping Status = AWB Voided, Order Status = Returned; Item republish (Serialized) / Stock dikembalikan (Pooled). *Rejected:* Order lanjut normal.
    - **(C) Barang sudah fisik dikirim:** Customer minta retur → Dewi input Retur Request + alasan → notif ke approver → *Approved:* Dewi tunggu barang fisik balik ke gudang, input foto+catatan, aksi "Retur Selesai" → Shipping Status = Returned to Warehouse, Order Status = Returned; Item/Stock dikembalikan sama seperti (B). *Rejected:* Order lanjut normal.
  - **Edge case:** Barang yang kembali tidak sesuai deskripsi/kondisi — diselesaikan manual di luar sistem; secara sistem tetap mengikuti flow yang sama (tidak ada status "Retur Ditolak" terpisah).
  - Realizes: FR-13, FR-15.

## 3. Glossary
*Downstream workflows dan pembaca harus memakai istilah ini persis. FR, UJ, dan SM memakai istilah Glossary verbatim.*

- **Brand** — master data brand yang diasosiasikan ke Item.
- **Category** — taksonomi tetap 2 level: **Main Category** (tanpa parent) dan **Sub-Category** (tepat 1 Main Category sebagai parent). Attribute Template didefinisikan di level Sub-Category.
- **Attribute Template** — atribut dinamis per Sub-Category (text/number/dropdown/checklist), admin-editable tanpa deploy. Setiap Item menyimpan snapshot nilai atribut sesuai versi template saat Item dibuat/terakhir diselaraskan.
- **Item** — listing produk yang bisa dijual; punya **Condition** (New atau Second) dan **Inventory Strategy** (Pooled atau Serialized) — dua dimensi independen. Setiap Item baru berstatus **Draft** sampai di-publish oleh Approval Role menjadi **Published**.
- **Condition** — dimensi deskriptif Item: New atau Second.
- **Inventory Strategy** — dimensi bisnis Item: Pooled (stok berbasis kuantitas) atau Serialized (1 unit fisik = 1 listing, stok selalu 1). Beberapa unit fisik dengan model/series yang sama tetap jadi listing Item terpisah masing-masing — tidak pernah digabung jadi 1 Item dengan kuantitas >1.
- **Grading** — skala kondisi terstandar & terkontrol (pilihan tetap, bukan free-text) untuk Item Condition = Second; dipakai badge & filter katalog. Tidak relevan untuk Item Condition = New.
- **Stock** — ledger ketersediaan per Item × Warehouse.
- **Warehouse** — master data lokasi fisik, sekaligus alamat asal pengiriman; satu Warehouse bisa ditetapkan sebagai Default Warehouse.
- **Customer** — end-buyer, create-or-find by email, guest-first, skema auth-ready.
- **Order** — transaksi pembelian; 3 layer status independen (Payment/Order/Shipping Status). Order Status minimal mencakup: Processing, Cancelled, Return Requested, Returned, Completed.
- **Shipment** — unit pengiriman fisik terikat 1 Warehouse; 1 Order bisa punya banyak Shipment. Shipping Status minimal mencakup: Not Shipped, Shipped, Delivered, **AWB Voided** (AWB dibatalkan sebelum barang fisik bergerak), **Returned to Warehouse** (barang sempat fisik berjalan, lalu dikembalikan).
- **Cancel Request** — pengajuan pembatalan Order yang belum registrasi AWB; dicatat dengan alasan, disetujui/ditolak lewat maker-checker (§4.2 FR-13).
- **Retur Request** — pengajuan retur untuk Order yang sudah registrasi AWB (baik barang belum maupun sudah fisik dikirim); dicatat dengan alasan, disetujui/ditolak lewat maker-checker (§4.2 FR-13).
- **Review** — ulasan Customer atas Order/Item, disubmit lewat halaman tokenized Order setelah Shipping Status Delivered; berstatus pending sampai dimoderasi Approval Role sebelum tampil di portal (§4.2 FR-12).
- **Timeout Timer** — keluarga parameter jangka waktu yang dikonfigurasi di Global Settings: durasi OTP, batas waktu pembayaran, batas waktu submit Review, batas akses halaman tokenized Order, window On-Process, auto-confirm, dst. (§4.8 FR-28).
- **Promo** — voucher/diskon dengan kuota kode, limit per-customer, redemption atomic.
- **Payment** — transaksi finansial, rekonsiliasi per gateway.
- **Refund** — pembalikan Payment; maker-checker (Refund Request → approval), nominal dibatasi rentang Minimum–Maximum Refund % dari total Order (detail §4.3 FR-17).
- **User** — akun staff/admin internal (beda dari Customer).
- **Role & Permission** — unit akses dinamis, bisa dibuat tanpa deploy.
- **Audit Trail** — log semua aksi mutasi (who/what/when/before-after).
- **Approval Role** — role berwenang mem-publish Item Draft, memoderasi Review, & approve Cancel/Retur/Refund (maker-checker).
- **Service Layer** — interface utama tiap domain; domain lain & konsumen eksternal (customer portal) wajib lewat Service ini, tidak boleh mutasi Model domain lain langsung.
- **API Layer** — lapisan HTTP tipis opsional (Sanctum + API Resource) di atas Service Layer, untuk klien yang butuh headless penuh; config-registered, tidak dipaksa aktif.
- **Cart** — kumpulan Item (Pooled maupun Serialized) yang diinginkan Customer sebelum checkout; add-to-cart tidak membuat reservasi/lock stok apapun — reservasi baru terjadi saat checkout dimulai.
- **Hero Banner** — banner promosi di halaman utama portal, dengan jadwal periode aktif (§4.5 FR-21).
- **Blog** — entry artikel Content, dikelompokkan lewat Blog Category, lewat maker-checker publish gate yang sama dengan Item (§4.5 FR-22).
- **Page** — entry halaman statis Content, dikelompokkan lewat Page Category, lewat maker-checker publish gate yang sama dengan Item (§4.5 FR-22).
- **Blog Category** — taksonomi untuk mengelompokkan entry Blog; terpisah dari Category (Catalog) dan dari Page Category.
- **Page Category** — taksonomi untuk mengelompokkan entry Page; terpisah dari Category (Catalog) dan dari Blog Category.
- **Featured Item** — penanda Item (harus sudah Published) yang ditonjolkan di portal, dengan urutan tampil yang diatur Staff (§4.5 FR-23).
- **Slug** — pengenal URL-friendly per entity (Category, Item, Blog, Page), dapat diedit Staff; dipakai portal untuk membangun URL customer-facing-nya sendiri (§4.10).
- **Redirect Mapping** — pasangan path/slug lama → target baru yang dikelola Staff lewat Redirect Manager; eksekusi HTTP redirect sesungguhnya dilakukan portal, bukan Bazaar (§4.10 FR-39).
- **Notification Template** — konten notifikasi email/SMS ke Customer per titik trigger, admin-editable tanpa deploy (§4.7 FR-26).
- **Notification Log** — riwayat pengiriman Notification Template ke Customer (terkirim/gagal), dengan opsi resend manual (§4.7 FR-27).
- **Staff In-App Notification** — notifikasi di dalam dashboard untuk Staff (bukan email ke Customer), dikirim ke Role yang memegang permission relevan dengan event yang butuh aksi (§4.7 FR-32).

## 4. Features

### 4.1 Catalog
**Description:** Domain yang mengelola master data produk — Brand, Category, Attribute Template, Item, Stock, dan Warehouse — fondasi yang paling banyak domain lain bergantung padanya.

**Functional Requirements:**

#### FR-1: Manage Brand
Staff/Admin dapat membuat, mengedit, dan mengarsipkan Brand sebagai master data katalog.

**Consequences (testable):**
- Setiap Item diasosiasikan ke tepat 1 Brand.
- Mengarsipkan Brand menyembunyikannya dari pilihan Brand untuk Item baru, tapi tidak menghapus atau meng-orphan Item yang sudah memakainya.

#### FR-2: Manage Category (2 level: Main → Sub)
Staff/Admin dapat membuat & mengelola Category dalam hierarki tetap 2 level (Main Category → Sub-Category).

**Consequences (testable):**
- Main Category tidak punya parent; Sub-Category tepat punya 1 Main Category sebagai parent. Nesting lebih dari 2 level tidak didukung.
- Setiap Item wajib diasosiasikan ke 1 Sub-Category (bukan langsung ke Main Category); Main Category berfungsi sebagai pengelompokan navigasi/filter saja.
- Attribute Template selalu didefinisikan di level Sub-Category.
- Setiap Category punya field **Slug** (URL-friendly, dapat diedit Staff) — dipakai portal untuk membangun URL customer-facing-nya sendiri.
- Category punya field metadata SEO (lihat §4.5 FR-24).

#### FR-3: Manage Attribute Template
Staff/Admin dapat mendefinisikan atribut dinamis (text/number/dropdown/checklist) per Sub-Category tanpa deploy kode.

**Consequences (testable):**
- Setiap Sub-Category punya tepat 1 Attribute Template aktif.
- Mengubah Attribute Template (tambah/hapus/ganti tipe field) tidak otomatis mengubah data Item yang sudah ada — Item lama tetap menyimpan snapshot nilai atribut versi lama.
- Saat staff membuka form edit sebuah Item yang snapshot atributnya berbeda dari Attribute Template Sub-Category saat ini, sistem menampilkan peringatan yang merinci field mana saja yang berbeda (field baru di master yang belum ada di snapshot Item, dan field di snapshot yang sudah dihapus dari master).
- Staff dapat menekan tombol penyelarasan untuk: menghapus dari snapshot Item field yang sudah tidak ada di master template, dan menambahkan ke snapshot Item field baru dari master template (kosong, menunggu diisi). Penyelarasan ini murni manual/staff-triggered — snapshot Item tidak berubah sendiri saat template diedit.
- Item baru yang dibuat setelah perubahan template otomatis memakai template versi terbaru.

**Notes:** Snapshot atribut per Item (bukan referensi hidup ke Attribute Template) adalah keputusan arsitektur; detail implementasi → addendum.

#### FR-4: Manage Item
Staff/Admin dapat membuat & mengedit Item, masing-masing dengan Condition (New atau Second), Inventory Strategy (Pooled atau Serialized), 1 Brand, 1 Sub-Category, dan nilai atribut sesuai Attribute Template aktif.

**Consequences (testable):**
- Setiap Item baru otomatis berstatus Draft, tanpa kecuali — termasuk Item New/Pooled.
- Item hanya menjadi Published (terlihat/bisa dipesan) setelah staff dengan Approval Role mem-publish-nya secara eksplisit.
- Staff tanpa Approval Role bisa membuat/mengedit Item Draft, tapi tidak bisa mem-publish.
- Condition & Inventory Strategy independen; sistem menyarankan default (New→Pooled, Second→Serialized) tapi staff bisa override kombinasi lain.
- Item dengan Condition = Second punya field **Grading** wajib diisi — skala kondisi terstandar & terkontrol (pilihan tetap, bukan free-text), dipakai untuk badge & filter katalog di portal. Item dengan Condition = New tidak punya Grading (nullable/tidak relevan).
- Trust signal untuk Item Second mengandalkan kombinasi foto aktual (media) + Grading — bukan modul verifikasi/sertifikasi keaslian terpisah (lihat §6 MVP Scope, dicatat sebagai trade-off sadar).
- Item punya field metadata SEO (lihat §4.5 FR-24), dan field **Slug** (URL-friendly, dapat diedit Staff) yang dipakai portal untuk membangun URL customer-facing-nya sendiri.
- Kalau Staff tidak mengisi alt text gambar Item, sistem otomatis mengisinya dengan nama Item sebagai default (bisa ditimpa manual kapan saja).

#### FR-5: Manage Stock
Staff/Admin dapat melihat & menyesuaikan ledger Stock per kombinasi Item × Warehouse.

**Consequences (testable):**
- Item Pooled: dibatasi tepat 1 baris Stock (1 Warehouse) per Item di v1; kuantitas bisa berapapun ≥ 0.
- Item Serialized: dibatasi permanen 1 baris Stock per Item, kuantitas selalu 1.
- Jika staff tidak eksplisit pilih Warehouse saat membuat Stock suatu Item, sistem resolve ke Default Warehouse (Global Setting) yang berlaku saat itu dan menyimpannya sebagai nilai tetap di baris Stock (snapshot — perubahan Default Warehouse nanti tidak memindahkan Stock yang sudah ada).
- Sistem mencegah 2 Order bersamaan berhasil me-reserve unit Stock yang sama melebihi kuantitas tersedia.

**Feature-specific NFRs:**
- Reservasi Stock harus atomic di bawah concurrent load (mekanisme detail → addendum).

#### FR-6: Manage Warehouse
Staff/Admin dapat membuat & mengedit Warehouse sebagai master data lokasi fisik, masing-masing membawa alamat asal pengiriman.

**Consequences (testable):**
- Warehouse bisa ditetapkan sebagai Default Warehouse di Global Settings (§4.8).
- Warehouse yang masih direferensikan oleh Stock aktif tidak bisa dihapus/dinonaktifkan tanpa reassignment Stock tersebut terlebih dulu.

#### FR-30: Item Visibility Based on Stock & Order Status
Visibilitas Item di customer portal mengikuti kombinasi Stock dan status Order yang masih menahannya — bukan cuma kuantitas Stock mentah.

**Consequences (testable):**
- Item Pooled dengan Stock tersisa = 0, tapi masih ada Order yang belum berstatus Selesai/Completed yang menahan reservasi terakhirnya → tetap tampil di portal, dengan label "Sedang Di-restock".
- Item Pooled dengan Stock = 0 dan tidak ada Order pending yang menahannya (Order terakhir sudah Selesai) → tidak tampil di portal.
- Item Serialized yang unitnya sudah terjual tuntas (Order terkait sudah Selesai) → tidak tampil lagi di portal.
- Item Serialized yang unitnya sedang berada dalam Order yang belum Selesai → tetap tampil di portal, dengan label "Sedang Proses Penjualan"/"On-Process".
- Status visibilitas ini tersedia lewat availability-check service yang sama dengan Cart (§4.2 FR-8), supaya portal bisa menampilkan label yang konsisten.

### 4.2 Order
**Description:** Domain yang mengelola siklus transaksi — dari Cart, Checkout, Order, Shipment, sampai Cancel/Retur, Promo, dan interaksi CS terkait.

**Functional Requirements:**

#### FR-7: Manage Customer
Sistem membuat/menemukan Customer otomatis berdasarkan email saat checkout; Staff/Admin dapat melihat & mengedit data Customer dari dashboard.

**Consequences (testable):**
- Saat checkout, sistem mencari Customer existing berdasarkan email; jika belum ada, membuat record baru (create-or-find), tanpa mewajibkan Customer membuat password/akun.
- Skema Customer auth-ready (kolom password, email_verified_at nullable) untuk mendukung Member Login di masa depan tanpa migrasi data.
- Staff dapat melihat riwayat Order per Customer dari dashboard admin.

#### FR-8: Manage Cart
Customer dapat menambah/menghapus Item (Pooled maupun Serialized) ke dalam Cart tanpa membuat reservasi stok.

**Consequences (testable):**
- Add-to-cart tidak mengunci/reservasi Stock maupun unit Serialized manapun — Cart murni representasi keinginan beli.
- Bazaar menyediakan availability-check service/endpoint on-demand yang bisa dipanggil portal kapan saja untuk menampilkan status ketersediaan tiap Item di Cart.
- Bazaar men-dispatch domain Event (mis. `StockReserved`, `StockReleased`, `ItemPublished`/`ItemUnpublished`) lewat Laravel Events setiap kali status ketersediaan berubah; portal dapat subscribe ke Event ini untuk membangun broadcast/real-time UX-nya sendiri — Bazaar tidak menyediakan infrastruktur broadcast/WebSocket.
- Cart tidak memvalidasi ketersediaan sampai proses checkout dimulai (lihat FR-9).

#### FR-9: Checkout & Order Creation
Customer menyelesaikan checkout dari isi Cart menjadi 1 Order, melalui verifikasi identitas dan validasi ketersediaan ulang.

**Consequences (testable):**
- Semua checkout (guest, karena Member Login belum aktif di v1) wajib melalui step verifikasi OTP sebelum Order dianggap terkonfirmasi ke tahap diproses — berlaku untuk semua Order, tanpa memandang jenis Item di dalamnya.
- Setelah verifikasi berhasil, sistem re-validasi tiap Item di Cart: Pooled → kuantitas diminta ≤ Stock tersedia; Serialized → unit belum direservasi/dibeli pembeli lain.
- Jika ada Item gagal validasi, sistem mengembalikan daftar Item spesifik yang tidak tersedia; Order tidak dibuat sampai Cart disesuaikan.
- Item yang lolos validasi direservasi secara atomic pada momen pembuatan Order.
- Order baru dibuat dengan 3 status layer independen: Payment Status, Order Status, Shipping Status.

#### FR-10: Order Status Lifecycle
Sistem melacak tiap Order lewat 3 layer status independen — Payment Status, Order Status, dan Shipping Status — yang masing-masing berubah menurut pemicunya sendiri.

**Consequences (testable):**
- Payment Status, Order Status, dan Shipping Status berubah independen satu sama lain (mis. Payment bisa berstatus Paid sementara Shipping masih Not Shipped).
- Staff dapat melihat ketiga status ini dari 1 tampilan Order detail di dashboard.

**Notes:** Daftar nilai state persis di tiap layer (mis. daftar lengkap status Payment/Order/Shipping) adalah keputusan desain teknis → addendum; PRD ini hanya menetapkan bahwa ketiganya independen dan harus terlihat jelas ke staff. Nilai spesifik yang PRD ini tetapkan secara eksplisit karena berulang muncul di Journey: Order Status minimal mencakup Processing, Cancelled, Return Requested, Returned, Completed; Shipping Status minimal mencakup Not Shipped, Shipped, Delivered, AWB Voided, Returned to Warehouse (lihat FR-13).

#### FR-31: Order Payment Timeout Auto-Cancellation
Order yang Payment-nya tidak diselesaikan Customer dalam jangka waktu yang dikonfigurasi otomatis dibatalkan sistem, tanpa perlu aksi Staff.

**Consequences (testable):**
- Jangka waktu batas pembayaran dikonfigurasi lewat parameter Timeout Timer di Global Settings (§4.8 FR-28).
- Kalau Payment tidak selesai sampai batas waktu terlewati, sistem otomatis mengubah Order Status menjadi Cancelled (§4.2 FR-10) dan melepas reservasi Stock/unit Serialized yang sempat dibuat saat Order dibuat (§4.2 FR-9).
- Customer menerima email notifikasi bahwa Order dibatalkan karena pembayaran tidak selesai dalam waktu yang ditentukan.
- Auto-cancellation ini tidak memerlukan approval/maker-checker — murni otomatis berbasis waktu.

#### FR-11: Manage Shipment
Setelah Order eligible (mis. Payment Status = Paid), Staff memicu proses fulfillment per-Shipment lewat 1 tombol aksi — bukan input manual kurir/AWB. Sistem memanggil Shipping Gateway vendor terkait untuk membuat AWB, dan status pengiriman selanjutnya tetap tersinkron dengan vendor.

**Consequences (testable):**
- Sistem membuat 1 Shipment per Warehouse berbeda yang terlibat dalam sebuah Order. Karena tiap Item Pooled dibatasi 1 Warehouse per Item di v1 (§4.1 FR-5), tidak ada ambiguitas "warehouse mana yang memenuhi" — grouping Shipment murni mengikuti Warehouse yang sudah melekat di tiap Item; algoritma alokasi otomatis lintas-warehouse **tidak dibutuhkan** di v1 (konsisten dengan §5 Non-Goals).
- Staff memicu pembuatan AWB per Shipment lewat 1 tombol aksi; sistem otomatis mengisi kurir & nomor AWB dari response vendor — tidak ada input manual field kurir/AWB oleh Staff.
- Menekan tombol aksi lebih dari sekali untuk Shipment yang sama tidak membuat AWB duplikat (idempotent).
- Jika pemanggilan vendor gagal (network/vendor down), Staff melihat status gagal yang jelas di dashboard dan bisa mencoba ulang (retry) tanpa efek samping data ganda.
- Kalau kegagalan vendor berlanjut terus-menerus, Staff dapat registrasi AWB manual langsung di aplikasi vendor shipping (di luar Bazaar), lalu memasukkan nomor AWB & kurir hasil registrasi manual tsb ke Shipment terkait di Bazaar sebagai fallback.
- Begitu AWB berhasil dibuat (otomatis maupun manual), Staff dapat mencetak surat jalan/resi dari data yang tersimpan di Shipment.
- Status Pengiriman tetap tersinkron dengan status aktual di vendor tanpa Staff perlu mengetik ulang status secara manual sebagai jalur utama.
- Staff punya tombol refresh/override manual sebagai fallback, untuk kasus sinkronisasi otomatis tertunda atau tidak tersedia.

**Notes:** Mekanisme sinkronisasi status (webhook vendor vs Bazaar polling API cek resi) adalah keputusan transport/mekanisme → addendum, sejalan dengan ShippingGateway interface (P4) yang sudah disebut brief. PRD ini hanya menetapkan bahwa Staff tidak pernah mengetik status manual sebagai jalur utama, dan tersedia fallback manual saat sinkronisasi otomatis gagal.

#### FR-12: Order Detail Access & Review
Customer dapat mengakses halaman detail Order (termasuk Status Pengiriman) lewat tokenized link tanpa perlu login, menerima notifikasi saat barang diterima, dan mengirim Review dalam batas waktu tertentu setelah Shipping Status menjadi Delivered.

**Consequences (testable):**
- Customer mengakses detail Order & Shipping via link bertoken unik per Order (bukan lewat akun/login), konsisten dengan model guest-first.
- Saat Shipping Status menjadi Delivered, Customer menerima email berisi: pemberitahuan barang diterima (untuk dicek kondisinya), link tokenized ke halaman detail Order, form Review, dan batas waktu submit Review sebelum Order otomatis ditutup.
- Batas waktu submit Review dikonfigurasi lewat Timeout Timer di Global Settings (§4.8 FR-28).
- Kalau Customer submit Review dalam batas waktu: Order Status menjadi Completed, dan Staff dengan Approval Role menerima notifikasi in-app untuk memoderasi Review tsb (approve/reject) sebelum tampil di portal.
- Kalau Customer tidak submit Review sampai batas waktu terlewati: Order Status otomatis menjadi Completed tanpa Review, dan **tidak ada** notifikasi in-app moderasi yang dikirim ke Staff (tidak ada yang perlu dimoderasi).
- Halaman tokenized otomatis tertutup/tidak bisa diakses lagi setelah jangka waktu tertentu (default H+5 — 5 hari) sejak perubahan status Order terakhir; jangka waktunya dikonfigurasi lewat Timeout Timer di Global Settings (§4.8 FR-28), sama seperti batas waktu Timeout Timer lain di PRD ini. Sebelum tertutup, halaman menampilkan riwayat Order lengkap secara bertahap (timeline status).

#### FR-13: Cancel & Retur Order
Customer (lewat CS) dapat mengajukan pembatalan atau retur atas sebuah Order; jalur dan status akhirnya berbeda tergantung tahap fulfillment Order saat permintaan diajukan — semua lewat Request tercatat (alasan + approval), tanpa jalur self-service otomatis.

**Consequences (testable):**
- **(A) Order belum registrasi AWB** — Customer minta batal → Staff mencatat Cancel Request (alasan, dari CS) → sistem kirim notifikasi in-app ke Role yang punya permission approve Cancel; Order Status tetap Processing selama Cancel Request pending (belum ada shipment yang mulai diproses, jadi tidak perlu status antara terpisah). Disetujui → Order Status menjadi Cancelled, reservasi Stock/unit dilepas. Ditolak → Order lanjut seperti biasa, tidak ada perubahan.
- **(B) Order sudah registrasi AWB, barang belum fisik dikirim** — permintaan Customer diproses sebagai Retur (bukan Cancellation), karena AWB sudah ada. Staff mencatat Retur Request (alasan) → Order Status menjadi **Return Requested** → notifikasi in-app ke Role approve Retur. Disetujui → Staff membatalkan AWB secara manual di vendor (di luar Bazaar), barang dikembalikan ke gudang, Staff input foto & catatan kondisi, lalu menjalankan aksi "Retur Selesai" → Shipping Status menjadi **AWB Voided**, Order Status menjadi **Returned**; Item Serialized terkait kembali Published, Stock Item Pooled terkait dikembalikan. Ditolak → Order Status kembali ke status sebelumnya, lanjut seperti biasa.
- **(C) Barang sudah fisik dikirim** — Staff mencatat Retur Request (alasan) → Order Status menjadi **Return Requested** → notifikasi in-app ke Role approve Retur. Disetujui → Staff menunggu barang fisik kembali ke gudang, input foto & catatan kondisi, lalu menjalankan aksi "Retur Selesai" → Shipping Status menjadi **Returned to Warehouse** (beda dari kasus B karena barang sempat benar-benar berjalan fisik), Order Status menjadi **Returned**; Item/Stock dikembalikan sama seperti (B). Ditolak → Order Status kembali ke status sebelumnya, lanjut seperti biasa.
- Kalau barang yang kembali secara fisik tidak sesuai deskripsi/kondisi yang diharapkan, itu diselesaikan manual di luar sistem — sistem tidak punya status "Retur Ditolak" terpisah; alur tetap sama seperti kasus barang belum dikirim.
- Semua Cancel/Retur Request — baik untuk Item New maupun Second — diproses lewat 1 alur generic yang sama, tanpa jalur self-service otomatis.
- Refund (§4.3 FR-17) relevan kalau Payment Order terkait sudah terlanjur Paid sebelum Cancel/Retur diproses — berlaku untuk hasil akhir Cancelled (kasus A, kalau pembayaran sudah masuk sebelum dibatalkan) maupun Returned (kasus B/C). Kalau Cancel terjadi sebelum Payment berhasil (masih Pending), tidak ada Refund yang perlu dieksekusi karena belum ada dana masuk.

#### FR-14: Manage Promo
Staff dapat membuat Promo (voucher/diskon) dengan kuota total dan limit redemption per Customer; sistem menjamin redemption atomic saat checkout.

**Consequences (testable):**
- Promo mendukung diskon persentase atau nominal tetap, dengan kode kuota (jumlah redemption maksimal) dan limit penggunaan per Customer.
- Promo punya periode berlaku (tanggal mulai-selesai) yang dikonfigurasi Staff.
- Sistem mencegah 2 checkout bersamaan berhasil redeem kuota terakhir yang sama (atomic redemption; mekanisme detail → addendum).

#### FR-15: CS Interaction Log
Staff CS dapat mencatat riwayat interaksi/komunikasi terkait sebuah Order (channel, catatan, waktu), terlihat di Order detail.

**Consequences (testable):**
- Tiap entri log tercatat dengan waktu, staff yang mencatat, dan isi catatan/ringkasan komunikasi.
- Riwayat CS menjadi dasar sebelum Staff mengeksekusi Cancel/Retur/Refund (FR-13, dan Refund di §4.3).

### 4.3 Finance
**Description:** Domain yang mengelola transaksi finansial — Payment dan Refund — terhadap Order.

**Functional Requirements:**

#### FR-16: Manage Payment
Sistem mencatat transaksi Payment untuk tiap Order, direkonsiliasi per Payment Gateway yang dipakai.

**Consequences (testable):**
- Order menerima Payment lewat gateway yang aktif (dikonfigurasi di Global Settings §4.8).
- Service yang mengembalikan daftar metode pembayaran tersedia ke portal hanya menampilkan metode yang diaktifkan Staff (§4.8 FR-28) — bukan seluruh metode yang didukung gateway secara default.
- Tiap transaksi Payment mencatat gateway, metode pembayaran spesifik yang dipakai, jumlah, status (Pending/Success/Failed), dan referensi/ID transaksi dari gateway.
- Jumlah yang tercatat di Payment adalah snapshot final — tidak berubah walau harga Item berubah setelahnya.

**Notes:** Mekanisme rekonsiliasi (webhook/callback/verifikasi manual) tergantung kapabilitas tiap gateway → addendum.

#### FR-17: Manage Refund
Staff Finance mengajukan Refund Request atas Payment sebuah Order yang Cancelled (dengan Payment sempat Paid) atau Returned; Refund baru berlaku setelah disetujui Role terpisah (permission approve Refund), lewat transfer bank manual di luar sistem untuk v1.

**Consequences (testable):**
- Refund Request hanya bisa diajukan untuk Order berstatus Cancelled (dengan Payment yang sudah Paid sebelum dibatalkan) atau Returned.
- Field nominal Refund Request menerima input Rupiah bebas (bukan pilihan preset), pre-filled dengan nilai **Maximum Refund %** dari total Order (bukan full 100%) sebagai nilai awal — sehingga nilai default selalu otomatis valid begitu form dibuka. Finance bisa menurunkan nominal itu sampai batas Minimum Refund %.
- Nominal yang disimpan — baik nilai awal (full) maupun hasil ubahan Finance — **wajib berada di antara Minimum Refund % dan Maximum Refund % dari total harga Order** (mis. total Rp100.000, Minimum 20% = Rp20.000, Maximum 80% = Rp80.000 → nominal yang bisa disubmit hanya Rp20.000–Rp80.000). Persentase batas dikonfigurasi per instalasi di Global Settings (§4.8 FR-28) — PRD ini tidak menetapkan angka default-nya, itu keputusan operasional tiap klien. Sistem menampilkan rentang Rupiah yang valid ke Finance sebagai panduan; nominal di luar rentang ditolak saat submit.
- Staff Finance mengajukan Refund Request → tersimpan sebagai pending → sistem kirim notifikasi in-app ke Role dengan permission approve Refund (§4.7 FR-32).
- Ditolak → tidak ada perubahan Payment Status, tidak ada dana yang perlu ditransfer.
- Disetujui → Payment Status Order terkait berubah menjadi Refunded, sejumlah nominal yang diajukan. Transfer dana aktual ke Customer dilakukan manual oleh Staff Finance di luar sistem (bank transfer) setelahnya — Bazaar tidak memanggil API refund gateway pembayaran manapun secara otomatis, dan tidak memverifikasi otomatis bahwa transfer benar-benar terjadi.

### 4.4 User & Access
**Description:** Domain yang mengelola akun internal Staff, kontrol akses berbasis Role & Permission dinamis, dan jejak audit atas seluruh sistem.

**Functional Requirements:**

#### FR-18: Manage User
Staff berwenang dapat membuat, mengedit, dan menonaktifkan akun User (staff/admin internal) serta menetapkan Role-nya.

**Consequences (testable):**
- User adalah akun internal, terpisah total dari Customer.
- Setiap User punya minimal 1 Role yang menentukan aksesnya.
- Menonaktifkan User mencabut akses tanpa menghapus jejak historisnya di Audit Trail — histori aksi tetap tercatat atas nama User tsb.

#### FR-19: Manage Role & Permission
Staff berwenang dapat membuat Role baru dan mencentang permission granular untuknya, tanpa deploy kode — tidak ada Role yang hardcoded.

**Consequences (testable):**
- Role bisa dibuat/diedit/dihapus dari dashboard; tiap Role adalah kumpulan permission granular yang dicentang Staff (mis. Role "Supervisor" dengan permission "can approve publish Item", "can approve Return", dst.).
- "Approval Role" yang dipakai di seluruh PRD ini (publish Item §4.1 FR-4, moderasi Review §4.2 FR-12, approve Cancel/Retur §4.2 FR-13, approve Refund §4.3 FR-17) merujuk ke Role apa pun yang memegang permission terkait — bukan konsep role terpisah atau hardcoded.
- Perubahan permission suatu Role berlaku ke semua User pemegang Role itu.

#### FR-20: Audit Trail
Sistem mencatat semua aksi mutasi (create/update/delete) di seluruh domain secara otomatis.

**Consequences (testable):**
- Tiap entri mencatat: siapa (User), apa (aksi & entity), kapan (timestamp), dan nilai before-after.
- Audit Trail read-only bagi semua User — tidak bisa diedit/dihapus lewat UI manapun.
- Staff dapat memfilter/mencari Audit Trail per User, per entity, atau rentang waktu.

### 4.5 Content
**Description:** Domain yang mengelola konten pemasaran/marketing yang disediakan lewat Service/API Layer untuk dirender customer portal — Hero Banner, Blog, Page, Featured Item, dan metadata SEO per-entity.

**Functional Requirements:**

#### FR-21: Manage Hero Banner
Staff dapat membuat & mengatur Hero Banner (gambar, link, urutan tampil, jadwal aktif) untuk disediakan ke customer portal.

**Consequences (testable):**
- Hero Banner punya jadwal periode aktif (tanggal mulai-selesai) yang dikonfigurasi Staff.
- Staff mengatur urutan tampil antar Banner yang sedang aktif.
- Data disediakan lewat Service/API Layer — portal yang merender tampilannya (headless).

#### FR-22: Manage Blog & Page
Staff dapat membuat, mengedit, dan mempublish entry Blog dan Page lewat rich text editor berbasis Markdown, masing-masing dikelompokkan lewat taksonomi terpisah (Blog Category / Page Category).

**Consequences (testable):**
- Entry Blog dan Page baru berstatus Draft; hanya menjadi Published setelah staff dengan Approval Role mem-publish-nya — pola maker-checker yang sama dengan Item (§4.1 FR-4).
- Blog dikelompokkan lewat Blog Category; Page dikelompokkan lewat Page Category — dua taksonomi terpisah dari Category Catalog maupun satu sama lain.
- Isi/body entry ditulis lewat rich text editor dengan skema penyimpanan Markdown.
- Setiap entry Blog/Page wajib memiliki tepat 1 Feature Image; kalau Staff tidak mengisi alt text-nya, sistem otomatis mengisi dengan judul entry sebagai default.
- Setiap entry Blog/Page punya field **Slug** (URL-friendly, dapat diedit Staff) yang dipakai portal untuk membangun URL customer-facing-nya sendiri.

#### FR-23: Manage Featured Item
Staff memilih & mengatur urutan Item yang ditandai "Featured" untuk ditonjolkan di portal.

**Consequences (testable):**
- Hanya Item berstatus Published yang bisa ditandai Featured.
- Staff mengatur urutan tampil Featured Item.

#### FR-24: Entity-Level SEO Metadata
Staff dapat mengisi metadata SEO langsung di form edit Item, Category, Blog, dan Page.

**Consequences (testable):**
- SEO metadata melekat sebagai field tambahan di tiap entity terkait (Item §4.1 FR-4, Category §4.1 FR-2, Blog & Page §4.5 FR-22) — bukan CRUD/module terpisah.
- Field yang tersedia: Meta Title, Meta Description, Canonical URL (opsional), OG Title, OG Description, OG Image. Tidak ada field Twitter Card terpisah — Twitter/X membaca tag OG (`summary_large_image`) sebagai default kalau tag Twitter tidak ada.
- Fallback kalau Staff mengosongkan field: OG Title/Description kosong → pakai Meta Title/Description; Meta Title/Description kosong → pakai nama/judul entity; OG Image kosong → pakai gambar utama entity.

**Notes:** Kebutuhan SEO yang sifatnya global/site-wide (bukan melekat ke 1 entity) — default fallback, sitemap, robots.txt, dsb. — ditangani terpisah sebagai bagian dari §4.8 FR-28/FR-29 dan §4.10, bukan di sini.

### 4.6 Reporting
**Description:** Domain read-only yang mengonsumsi data lintas domain lain untuk menghasilkan laporan bisnis.

**Functional Requirements:**

#### FR-25: Reporting Dashboard
Staff mengakses kumpulan laporan read-only lintas domain dari 1 area Reporting, terbagi dalam 5 kategori, masing-masing dengan filter rentang tanggal dan opsi export.

**Consequences (testable):**
- Reporting adalah domain read-only dengan dependency 1 arah — Reporting boleh membaca semua domain lain, tidak ada domain lain yang bergantung balik ke Reporting.
- **Sales & Revenue:**
  - *Sales Summary* — Gross Revenue, Net Revenue, Total Orders, Average Order Value (AOV), Total Discount; per periode harian/mingguan/bulanan/tahunan.
  - *Sales by Product/SKU* — nama Item, SKU, Quantity Sold, Total Revenue per Item, performa per varian.
  - *Payment & Shipping* — share metode pembayaran per Payment Gateway aktif, share kurir pengiriman.
- **Customer Analytics:**
  - *New vs Returning Customers* — jumlah Customer baru, tingkat Customer kembali, frekuensi pembelian.
  - *Abandoned Cart* — tingkat Cart ditinggalkan, estimasi nilai hilang, tahap funnel drop-off.
  - *Customer Demographics* — kota/provinsi pembeli terbanyak.
- **Inventory:**
  - *Low Stock Alert* — Item dengan Stock di bawah ambang aman, status reorder.
  - *Stock Turnover Rate* — kecepatan Item terjual dalam 1 periode (Days Sales of Inventory), Item paling cepat laku.
  - *Dead Stock* — Item tanpa penjualan dalam jangka waktu tertentu (default >90 hari, dikonfigurasi), jumlah unit & nilai modal yang tertahan.
- **Marketing & Promo:**
  - *Coupon Performance* — kode Promo, jumlah pemakaian, total penjualan yang dihasilkan, biaya diskon.
- **Operational & Support:**
  - *Fulfillment SLA* — rata-rata waktu packing, waktu sampai Shipment terkirim (§4.2 FR-11).
  - *Return & Refund Report* — tingkat Retur, total nilai Refund, alasan Retur terbanyak.
- **SEO Health** *(§4.10)*:
  - *Content Completeness* — jumlah Item/Blog/Page yang kehilangan deskripsi, gambar, atau metadata SEO (§4.5 FR-24).
  - *Broken Link (404)* — daftar & jumlah URL yang dilaporkan 404 oleh portal (§4.10 FR-40).
- Tiap laporan mendukung: filter rentang tanggal custom (hari ini/7 hari terakhir/30 hari terakhir/rentang kustom), export ke CSV/XLSX/PDF, dan opsi scheduled report (dikirim otomatis ke email tim manajemen pada jadwal tertentu).

### 4.7 Notification
**Description:** Domain yang mengelola konten dan riwayat pengiriman notifikasi; pemicu (dispatch) tetap didesentralisasi di tiap domain lewat Laravel Events.

**Functional Requirements:**

#### FR-26: Manage Notification Template
Staff dapat mengedit konten Notification (subjek, body, placeholder variable) per titik trigger, tanpa deploy kode.

**Consequences (testable):**
- Tiap titik trigger (mis. Order Confirmed, Item Published, Refund Selesai, OTP Checkout) punya 1 Notification Template yang bisa diedit Staff.
- Template mendukung placeholder variable (mis. `{{customer_name}}`, `{{order_id}}`) yang di-resolve otomatis saat dikirim.
- Dispatch/trigger notifikasi tetap didesentralisasi di tiap domain masing-masing lewat Laravel Events — domain Notification hanya menyimpan konten & log, tidak memicu sendiri.
- Titik trigger email ke Customer juga mencakup minimal: Order Status berubah menjadi Completed atau Cancelled/Returned; Payment Status berubah menjadi Paid atau Refunded. Tiap email ini menyertakan link ke halaman tokenized Order (§4.2 FR-12), karena halaman itu menampilkan riwayat Order lengkap secara bertahap.

#### FR-27: Notification Log & Monitoring
Staff dapat melihat riwayat pengiriman Notification (terkirim/gagal) dan melakukan resend manual.

**Consequences (testable):**
- Tiap Notification yang dikirim tercatat dengan status (Terkirim/Gagal), waktu, dan penerima.
- Staff dapat memicu resend manual untuk Notification yang gagal atau perlu dikirim ulang.

#### FR-32: Staff In-App Notification
Staff menerima notifikasi in-app di dalam dashboard untuk event yang butuh perhatian/aksinya — bukan cuma Notification keluar ke Customer.

**Consequences (testable):**
- Event yang memicu notifikasi in-app mencakup minimal: Order baru diproses, Payment Status berubah menjadi Paid, registrasi AWB gagal, permintaan Cancel/Retur/Refund butuh approval, Review baru butuh moderasi.
- Notifikasi in-app hanya dikirim ke User yang Role-nya memegang permission relevan dengan event tsb (mis. hanya ke Role dengan permission approve Retur untuk event Retur Request) — bukan broadcast ke semua Staff.
- Event yang tidak butuh aksi Staff (mis. Order auto-Completed tanpa Review masuk) **tidak** memicu notifikasi in-app.
- Notifikasi in-app dan Notification Template/Log (FR-26, FR-27) adalah dua konsep terpisah — in-app untuk Staff, Notification untuk Customer — walau sama-sama dipicu lewat Laravel Events per domain.

### 4.8 Global Settings
**Description:** Domain List+Edit-only untuk parameter konfigurasi sistem yang sifatnya tetap — bukan CRUD bebas.

**Functional Requirements:**

#### FR-28: Manage Global Settings
Staff dapat melihat & mengedit parameter konfigurasi sistem yang sifatnya tetap.

**Consequences (testable):**
- Parameter yang tersedia sudah ditentukan lebih dulu (fixed); Staff tidak bisa menambah/menghapus parameter baru lewat UI, hanya List+Edit nilainya.
- Mencakup minimal: Timeout Timer (durasi OTP, batas waktu pembayaran, batas waktu submit Review, batas akses halaman tokenized Order — default H+5, window On-Process, auto-confirm, dst.), Payment Gateway config (aktif/nonaktif + kredensial per gateway, **dan metode pembayaran mana saja yang aktif untuk Customer** — mis. gateway aktif tapi hanya sebagian metode pembayarannya yang dibuka, bukan semua metode yang didukung gateway otomatis aktif), Shipping config (kurir aktif, dst.), Default Warehouse (pointer ke 1 Warehouse), General Store Info (Nama Toko, Logo, Favicon, Sosial media), Minimum Refund % dan Maximum Refund % (batas bawah/atas nominal Refund Request per instalasi — §4.3 FR-17, tanpa angka default yang dipatok PRD ini), Robots.txt Content (area teks bebas berisi aturan crawl, mis. disallow `/cart`, `/checkout` — portal yang fetch & serve di `/robots.txt` miliknya, Bazaar tidak serve file ini langsung), Analytics Verification Codes (kolom terpisah untuk Google Search Console, Google Analytics 4, Facebook Pixel — portal yang fetch & embed ke `<head>` halamannya).

#### FR-29: Global SEO Defaults
Bagian dari Global Settings yang menampung nilai default/fallback untuk metadata SEO per-entity (§4.5 FR-24) kalau Staff tidak mengisinya.

**Consequences (testable):**
- Mencakup: default meta title template (mis. `{nama entity} — {nama toko}`), default meta description, default OG image (dipakai kalau Item/Blog/Page tidak punya gambar sama sekali).
- Robots.txt Content dan Analytics Verification Codes (Google Search Console/GA4/Facebook Pixel) adalah config Global Settings umum, sudah dicakup di §4.8 FR-28 — FR-29 ini murni default/fallback metadata SEO, bukan config teknis lain.

### 4.9 Package Installation & Integration
**Description:** Kapabilitas yang dipakai Developer Tigaphonic (bukan Staff toko) untuk memasang Bazaar ke proyek klien baru dan mengintegrasikannya dengan customer portal — realizes UJ-1.

**Functional Requirements:**

#### FR-33: Install & Connect to Filament Panel
Developer dapat memasang Bazaar lewat Composer dan menghubungkannya ke Filament panel yang sudah ada di proyek klien lewat 1 perintah Artisan, tanpa perlu membuat panel Filament baru.

**Consequences (testable):**
- `composer require tigaphonic/bazaar` menambahkan package sebagai dependency standar.
- 1 perintah Artisan instalasi menyambungkan seluruh Resource/Page Filament milik Bazaar ke panel Filament yang sudah terpasang di proyek klien — bukan membuat panel terpisah.
- Perintah migrasi database menginisiasi seluruh skema domain Bazaar (Catalog, Order, Finance, dst.).
- Setelah instalasi, Developer dapat login ke panel dan memverifikasi seluruh domain berfungsi sesuai FR yang berlaku (§4.1–§4.8).

#### FR-34: Service Layer Integration (Monolith)
Developer dapat mengintegrasikan customer portal berbasis Blade + Livewire ke Bazaar langsung lewat Service Layer, tanpa lewat HTTP/API.

**Consequences (testable):**
- Service Layer tiap domain dapat dipanggil langsung dari kode aplikasi klien (dependency injection/facade), tanpa request HTTP.

#### FR-35: API Layer Integration (Headless)
Developer dapat mengintegrasikan customer portal berbasis framework frontend terpisah (mis. Next.js/Vue) ke Bazaar lewat API Layer opsional.

**Consequences (testable):**
- API Layer diaktifkan lewat config (opt-in, tidak dipaksa aktif).
- API terautentikasi Sanctum, response berbentuk API Resource, mengekspos kapabilitas Service Layer yang sama secara terstruktur.

### 4.10 SEO & Discoverability
**Description:** Domain yang mendukung keterlihatan Bazaar di mesin pencari. Konsisten dengan prinsip headless (§1 Vision): Bazaar tidak pernah merender file atau halaman customer-facing (sitemap.xml, robots.txt, tag JSON-LD, redirect HTTP) — domain ini menyediakan data/config lewat Service/API, portal yang mewujudkannya di sisinya sendiri.

**Functional Requirements:**

#### FR-36: Media Upload Optimization
Setiap gambar yang diunggah ke Bazaar (Item, Hero Banner, Blog/Page Feature Image, Brand logo, Store Info logo) otomatis dioptimasi untuk performa.

**Consequences (testable):**
- Gambar otomatis dikompres & dikonversi ke format modern (mis. WebP) saat diunggah, tanpa aksi tambahan dari Staff.
- Optimasi berlaku seragam di semua titik upload gambar lintas domain (Catalog, Content, Global Settings) — 1 mekanisme, bukan fitur per-domain terpisah.

**Notes:** Detail teknis (library kompresi, target kualitas/ukuran) → addendum.

#### FR-37: Sitemap Data Feed
Bazaar menyediakan Service/API yang mengembalikan daftar entity siap-index (Item Published, Category, Blog & Page Published) beserta Slug dan waktu update terakhir, untuk dirangkai portal menjadi sitemap-nya sendiri.

**Consequences (testable):**
- Bazaar tidak menghasilkan file sitemap.xml maupun meng-hosting-nya — itu di luar package karena Bazaar tidak pernah merender URL customer-facing.
- Data yang dikembalikan feed ini mengikuti status Published/Unpublished tiap entity secara real-time (query langsung terhadap data terkini, bukan cache basi).
- Aksi ping ke Google Search Console dilakukan portal sendiri setelah membangun sitemap-nya, bukan oleh Bazaar.

#### FR-38: Structured Data Feed (Schema.org)
Bazaar menyediakan Service/API yang mengembalikan data Item terstruktur sesuai kebutuhan schema.org Product (harga, status ketersediaan, agregat rating Review), untuk disuntikkan portal sebagai JSON-LD di halaman produknya.

**Consequences (testable):**
- Bazaar tidak menyuntikkan tag `<script type="application/ld+json">` apa pun — itu dilakukan portal di halaman yang dirender portal sendiri.
- Field yang dikembalikan feed ini murni agregasi dari data yang sudah ada (harga Item §4.1 FR-4, status visibilitas §4.1 FR-30, agregat Review §4.2 FR-12) — tidak ada field baru yang perlu diisi Staff secara manual.

#### FR-39: Redirect Manager
Staff dapat membuat & mengelola mapping URL lama → target baru (entity Bazaar lain, atau URL eksternal), untuk kasus Item/Category/Blog/Page yang di-discontinue atau dipindah.

**Consequences (testable):**
- Staff mendaftarkan path/slug lama, target baru (referensi entity Bazaar atau URL bebas), dan alasan opsional.
- Bazaar menyediakan mapping ini lewat Service/API untuk dikonsumsi portal; Bazaar sendiri tidak mengeksekusi HTTP redirect 301 apa pun karena tidak mengontrol routing URL publik.
- Portal bertanggung jawab query/sync mapping ini dan menjalankan redirect 301 sesungguhnya di sisi routing-nya sendiri.

#### FR-40: Broken Link (404) Tracking
Portal dapat melaporkan event 404 (URL yang diminta visitor tapi tidak ditemukan) ke Bazaar lewat API, supaya Staff bisa memantaunya dari 1 tempat.

**Consequences (testable):**
- Bazaar menyediakan 1 API endpoint yang menerima laporan event 404 dari portal (URL yang diakses, timestamp, referrer kalau ada).
- Staff dapat melihat daftar & jumlah 404 yang dilaporkan dari dashboard Reporting (§4.6 FR-25).
- Pelaporan 404 murni pasif (portal yang berinisiatif kirim) — Bazaar tidak melakukan crawling/pengecekan link sendiri.

## 5. Non-Goals (Explicit)
*Sikap produk yang permanen — bukan cuma belum sempat dikerjakan, tapi memang bukan arah Bazaar. Item yang levelnya "belum sekarang, bisa direvisit" ada di §6.2, bukan di sini.*

- Alur akuisisi barang second dari Customer (beli-putus/trade-in/konsinyasi) — package fokus ke sisi jual (toko → Customer), bukan sisi beli.
- Partial refund **di luar rentang Minimum/Maximum Refund %** yang dikonfigurasi (§4.3 FR-17) — bukan partial refund bebas nilai berapa pun.
- Refund selalu lewat maker-checker manual (Request → approval) — tidak pernah self-service otomatis tanpa approval, di semua skenario.
- Infrastruktur broadcast/WebSocket real-time — tetap tanggung jawab customer portal; Bazaar hanya menyediakan availability-check on-demand & domain Events pasif.
- Rendering antarmuka customer-facing (storefront) apa pun — sepenuhnya di luar package (lihat §2.2 Non-Users, §1 Vision).

## 6. MVP Scope

### 6.1 In Scope
- 9 domain module dengan Filament admin UI penuh (FR-1–FR-40, lihat §4.1–§4.8 dan §4.10): Catalog, Order, Finance, User & Access, Content, Reporting, Notification, Global Settings, SEO & Discoverability.
- Service Layer untuk seluruh domain, bisa dikonsumsi langsung oleh monolith Blade/Livewire klien.
- API Layer tipis opsional (Sanctum + API Resource), config-registered, untuk klien yang butuh headless penuh.
- Model inventory terpadu: Condition (New/Second) × Inventory Strategy (Pooled/Serialized) dalam 1 skema.
- Kombinasi fitur yang bisa diaktifkan berbeda per instalasi klien (New-only, Second-only, atau hybrid).

### 6.2 Out of Scope for MVP
*Belum dikerjakan di v1, tapi bukan sikap permanen — bisa direvisit begitu ada kebutuhan riil klien. Bandingkan dengan §5 yang sikapnya permanen.*

- Member Login/Wishlist/Loyalty aktif — dideferred ke v2+; skema sudah disiapkan agar tidak perlu migrasi data saat diaktifkan.
- Algoritma alokasi warehouse otomatis untuk multi-warehouse-per-Item (Pooled), termasuk split-shipment otomatis lintas Warehouse untuk 1 Item — tidak relevan di v1 (Item Pooled dibatasi 1 Warehouse per Item), dideferred ke v2+ menyusul kebutuhan riil klien dengan stok Pooled lintas banyak Warehouse.
- Multi-currency / pembayaran & pengiriman cross-border — belum ada kebutuhan klien saat ini.
- `[NOTE FOR PM]` Modul verifikasi/sertifikasi keaslian barang second sebagai fitur aktif — sengaja tidak dikerjakan di v1 (trade-off biaya vs risiko trust yang disadari, bukan kealpaan); brief sendiri menandai ini "layak dipertimbangkan ulang per client tergantung kategori barang & rentang harga" — revisit kalau ada klien dengan kategori/rentang harga tinggi yang butuh trust signal lebih kuat.
- Self-service Return/Refund otomatis, dan Return/Refund policy yang beda per klien/kategori — v1 pakai 1 alur generic manual+approval untuk semua Condition; revisit kalau ternyata klien New-heavy butuh self-service.
- Eksekusi Refund otomatis lewat API gateway pembayaran — v1 memakai transfer bank manual oleh Staff Finance; revisit kalau volume Refund cukup besar untuk membenarkan integrasi otomatis.
- Field SEO global spesifik (§4.8 FR-29) — perlu dirinci lebih lanjut, bukan sengaja diblokir.

## 7. Success Metrics

**Primary**
- **SM-1**: Non-recurring defects — Bug/isu yang sebelumnya berulang muncul di tiap proyek custom (mis. race condition stok, inkonsistensi status Order, celah reservasi) tidak muncul lagi di proyek klien baru yang dibangun di atas Bazaar. Diukur kualitatif lewat retrospective tiap proyek baru, dibandingkan daftar isu historis dari proyek-proyek sebelum Bazaar ada.

**Counter-metrics (do not optimize)**
- **SM-C1**: Waktu implementasi proyek klien baru di atas Bazaar tidak boleh lebih lama dari target semula — mengejar "0 bug berulang" tidak boleh berujung over-engineering yang justru bikin integrasi Bazaar per klien makin lambat/rumit, karena itu melawan tujuan utama Bazaar (percepatan). Counterbalances SM-1.

## 8. Open Questions
*(Tidak ada — pertanyaan SEO yang sebelumnya di sini sudah dijawab user dan diselesaikan lewat §4.5 FR-24/FR-29 dan §4.10.)*

## 9. Assumptions Index
Tidak ada assumption inline yang masih outstanding di versi ini. Dua inferensi logis dari sesi sebelumnya sudah dikonfirmasi eksplisit oleh user dan menjadi keputusan tetap di badan dokumen: Refund (§4.3 FR-17) berlaku juga untuk Order yang Cancelled, bukan cuma Returned (FR-13 & FR-17); dan daftar field metadata SEO per-entity beserta aturan fallback-nya (§4.5 FR-24).
