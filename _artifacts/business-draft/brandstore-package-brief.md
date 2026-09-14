# Brand Store Commerce Package — Business & Technical Brief (Draft)

### Internal Reusable Package untuk Brand Store Multi-Client (Barang Baru & Second)

**Status: 🟡 DRAFT — Working Document, belum final.** Dokumen ini merangkum hasil diskusi desain awal dan keputusan yang sudah diambil sejauh ini. Ditulis supaya bisa dilanjutkan di sesi kerja terpisah tanpa perlu re-derive ulang konteks.

---

## Daftar Isi

1. Latar Belakang & Tujuan
2. Visi Produk & Model Distribusi
3. Prinsip Arsitektur Utama
4. Struktur Package
5. Domain Module Breakdown
6. Keputusan Desain Mendalam per Topik Kunci
7. Pola Flow Referensi — Barang Second / Unique Item
8. Non-Goals / Di Luar Scope v1
9. Open Items — Belum Diputuskan
10. Rencana Lanjutan

---

## 1. Latar Belakang & Tujuan

Pemilik proyek ini menangani pembuatan Brand Store (Direct-to-Customer e-commerce) untuk beberapa client dengan model bisnis yang mirip satu sama lain: menjual barang lewat website sendiri, dengan campuran client yang jual barang baru saja, dan minimal satu client yang juga jual barang second (unique/pre-owned item).

Membangun tiap Brand Store dari nol untuk tiap client boros waktu, sementara pola bisnisnya cukup mirip untuk digeneralisasi. Dokumen ini merancang **package Laravel internal, reusable**, yang mengcover domain umum sebuah Brand Store — termasuk kasus khusus barang second (unique/1-of-1 inventory) yang tidak dicover package e-commerce generic di pasaran.

**Kenapa tidak pakai package e-commerce open-source yang sudah ada:** sudah dilakukan kajian terhadap beberapa package headless e-commerce populer di ekosistem Laravel. Kesimpulannya:
- Pola generic (product → variant → quantity stock) cocok untuk kasus barang baru, tapi tidak ada satupun yang native mendukung model "1 unit fisik unik = 1 listing, grading kondisi manual per unit, tanpa cart, atomic locking per unit" yang dibutuhkan untuk kasus barang second.
- Memaksakan kasus barang second ke package generic berarti override mayoritas fitur inti package tsb (cart, order state machine bawaan, stock model berbasis quantity) — effort override itu lebih mahal daripada bangun sendiri, khususnya kalau kasus second-hand-nya cuma dipakai di sedikit client.
- Untuk kasus barang baru murni (retail standar), package existing memang cukup baik — tapi begitu 1 client butuh keduanya (hybrid), lebih murah punya 1 pondasi sendiri yang mengcover keduanya secara native, daripada menjahit 2 sistem berbeda.

**Keputusan**: bangun package sendiri, general purpose, mengcover baik flow barang baru maupun barang second dalam 1 fondasi domain yang sama.

---

## 2. Visi Produk & Model Distribusi

- **Bentuk distribusi**: package Composer biasa (mirip cara package headless e-commerce didistribusikan di ekosistem Laravel) — bukan platform SaaS multi-tenant. Tiap client punya Laravel app & database sendiri; package ini jadi dependency yang di-install ke tiap project, menyuntikkan domain logic, migration, dan admin panel.
- **Tidak ada kolom `tenant_id`** atau isolasi multi-tenant di level schema — karena tiap instalasi memang berdiri sendiri per client.
- **Target pemakaian**: package ini dipakai berulang lintas project client baru ke depan. Tiap client boleh beda kombinasi fitur yang diaktifkan (misal: client A cuma barang baru, client B cuma barang second, client C hybrid keduanya).

---

## 3. Prinsip Arsitektur Utama

Ringkasan keputusan besar yang jadi pegangan di semua domain (detail rasional tiap poin ada di Bagian 6):

| # | Prinsip | Ringkasan |
|---|---|---|
| P1 | **Single Package** | Semua domain logic + admin panel dalam 1 package Composer, bukan dipecah jadi core/admin/gateway terpisah — untuk hindari overhead maintenance versi lintas repo yang tidak sepadan manfaatnya |
| P2 | **Admin dikunci ke Filament** | Admin panel dibangun & disediakan langsung pakai Filament di dalam package ini. Tidak dirancang untuk swap ke panel admin lain. Styling/theme tetap bisa di-override lewat mekanisme native Filament (custom theme CSS, override Resource class, override render hook) di masing-masing project client |
| P3 | **Customer Portal sepenuhnya bebas/headless** | Tidak dipaketkan sama sekali. Package cuma expose Service layer (dipakai langsung kalau client pakai Blade+Livewire monolith) dan opsional 1 lapisan API tipis (Sanctum + API Resource) untuk client yang mau frontend headless penuh (Next.js/Vue/dst), didaftarkan lewat config, tidak dipaksa aktif |
| P4 | **Gateway (Payment/Shipping) hidup di dalam package, bukan package reusable terpisah** | Kontrak (interface) `PaymentGateway` / `ShippingGateway` didefinisikan di package ini; implementasi konkret (misal integrasi payment gateway & shipping aggregator tertentu) juga tinggal di package yang sama, memanfaatkan SDK resmi/komunitas sebagai dependency biasa. Tidak ada rencana ekstraksi jadi package gateway reusable terpisah kecuali ada bukti kebutuhan nyata di kemudian hari |
| P5 | **Domain-grouped code organization** | Business logic dikelompokkan per domain namespace (lihat Bagian 4 & 5), masing-masing dengan subdirektori Models/Services/Actions/Enums/States/Events/Filament sesuai kebutuhan |
| P6 | **Service → Action → Model layering** | Service class (utama) → Action class (escape hatch untuk operasi kompleks) → Model method (predikat reusable, tanpa mutating side-effect). Presentation layer (Filament Resource, Controller, Blade) tidak boleh berisi business logic |
| P7 | **Cross-domain lewat service, bukan mutasi model langsung** | Domain A yang butuh domain B wajib lewat Service domain B, tidak boleh mutasi model domain B secara langsung. Arah dependency harus 1 arah (dijabarkan per domain di Bagian 5), side-effect yang melawan arah dependency pakai Event |
| P8 | **Snapshot principle** | Data master yang bisa berubah sewaktu-waktu (harga, alamat warehouse, biaya ongkir, konten promo) WAJIB di-snapshot ke record transaksi (Order/Stock/dst) pada saat transaksi terjadi — tidak boleh resolve "live" dari master data yang berubah-ubah. Ini mencegah data historis berubah diam-diam ketika master data-nya di-update kemudian |
| P9 | **Atomic reservation, bukan read-then-write** | Semua operasi kunci stok (baik unique unit maupun quantity pool) wajib pakai conditional atomic update (1 SQL statement dengan WHERE guard), bukan baca-lalu-tulis terpisah — mencegah race condition |
| P10 | **Unified inventory model** | Barang baru (pooled/quantity) dan barang second (serialized/unique) pakai 1 mekanisme reservasi & 1 bentuk skema yang sama (lihat Bagian 6.1) — bukan 2 sistem locking berbeda |

---

## 4. Struktur Package

Struktur folder internal (dalam 1 package), menegakkan pemisahan layer (P6) lewat konvensi folder, bukan lewat batas package:

```
src/
  Catalog/
    Models/          (Brand, Category, AttributeTemplate, Item, Stock, Warehouse)
    Services/
    Actions/
    Enums/
    Filament/
  Order/
    Models/          (Customer, Order, Shipment, ReturCase, Promo, ReviewOrder)
    Services/
    Actions/
    States/          (PaymentStatus, OrderStatus, ShippingStatus — state classes)
    Events/
    Filament/
  Payment/
    Contracts/PaymentGateway.php
    Gateways/        (implementasi konkret per provider)
    Filament/
  Shipping/
    Contracts/ShippingGateway.php
    Gateways/
    Filament/
  User/
    Models/          (User, Role, Permission)
    Services/
    Filament/
  Content/
    Models/          (Banner, Page/Blog, FeaturedItem, SeoMeta)
    Services/
    Filament/
  Reporting/
    Services/        (read-only, konsumsi lintas domain lain)
    Filament/
  Notification/
    Models/          (NotificationTemplate, NotificationLog)
    Services/
    Filament/
  Settings/
    Models/ Services/ Filament/
  Http/
    Api/             (opsional — didaftarkan lewat config, untuk client headless)
```

**Distribusi package tetap tunggal**, tidak dipecah jadi beberapa Composer package (lihat P1). Alasan lengkap ada di Bagian 6.7.

---

## 5. Domain Module Breakdown

### 5.1 Manajemen Katalog
| Item | Status |
|---|---|
| Brand (master data) | Disepakati |
| Kategori (taksonomi tetap, menentukan Template Attribute mana yang aktif) | Disepakati |
| Template Attribute (atribut dinamis per kategori — tipe input text/angka/dropdown/checklist, dikelola tanpa deploy kode) | Disepakati |
| Item/Produk (bisa Condition = Baru atau Second) | Disepakati, detail skema lihat 6.1–6.2 |
| Stock (ledger ketersediaan per Item × Warehouse) | Disepakati, lihat 6.1 |
| Warehouse (master lokasi fisik, dobel fungsi jadi alamat asal pengiriman) | Disepakati, lihat 6.1 |

### 5.2 Manajemen Order
| Item | Status |
|---|---|
| Customer (create-or-find by email, guest-first, auth-ready untuk masa depan) | Disepakati, lihat 6.4 |
| Order (3-layer status: Payment/Order/Shipping) | Disepakati, lihat 6.2 |
| Shipping / Shipment (1 Order bisa punya banyak Shipment, per Warehouse) | Disepakati, lihat 6.2 |
| Retur & Pembatalan (extend Order Status, pola maker-checker) | Disepakati (pola diadopsi dari referensi flow second-hand, lihat Bagian 7) |
| Promo (voucher/diskon — kode kuota, limit per customer, redemption atomik) | Dipindah ke domain Order (bukan Finance) |
| CS (histori interaksi customer service terkait order) | Ditambahkan ke domain Order |
| Review Order (trigger dari Order lifecycle; moderasi/tampilan bisa nampang di nav Content) | Dipindah ke domain Order |
| Cart | **Belum diputuskan** — lihat Bagian 9 |

### 5.3 Finance
| Item | Status |
|---|---|
| Payment (transaksi, rekonsiliasi per gateway) | Disepakati |
| Refund (maker-checker, extend Payment Status) | Disepakati |

### 5.4 Manajemen User
| Item | Status |
|---|---|
| User (staff/admin internal — beda dari Customer) | Disepakati |
| Role & Permission (dinamis, bisa bikin role baru + assign permission granular tanpa deploy kode) | Disepakati |
| Audit Trail / Activity Log (semua aksi mutating tercatat: siapa/apa/kapan/before-after) | Disepakati |

### 5.5 Manajemen Konten
| Item | Status |
|---|---|
| Hero Banner | Disepakati |
| Blog/Page | Disepakati |
| Featured Item | Disepakati |
| SEO (metadata, kemungkinan besar sebagai concern lintas Content+Catalog, bukan entity tersendiri) | Disepakati konsep, detail teknis menyusul |

### 5.6 Reporting / Analytics
Domain sendiri, read-only, cross-domain consumer (lihat 6.5). Isi berupa Report Service per topik: Sales, Conversion Funnel, Margin, Inventory, dst — detail metrik menyusul saat breakdown domain ini.

### 5.7 Notification
Domain sendiri, mencakup 2 sub-concern (lihat 6.6):
- **Template/Setup** — konten notifikasi per titik trigger, editable admin tanpa deploy
- **Log/Monitoring** — riwayat notifikasi terkirim, status (sent/failed), opsi resend manual

*(Dispatch/trigger logic tetap tersebar di masing-masing domain lewat Laravel Event — tidak disentralkan.)*

### 5.8 Global Setting
| Item | Status |
|---|---|
| Timeout Timer (durasi OTP, window On Process, auto-confirm, dst) | Disepakati |
| Payment Gateway (config aktif/tidak, kredensial) | Disepakati |
| Shipping (config kurir aktif, dst) | Disepakati |
| Default Warehouse (pointer ke 1 Warehouse, fallback kalau Item tidak eksplisit set) | Disepakati |
| General Info Store (Nama Store, Logo, Favicon, Sosmed) | Ditambahkan |
| Lainnya | Menyusul per kebutuhan |

*(Pola modul ini List + Edit saja — tidak ada Create/Delete parameter baru dari UI, karena parameternya fixed/tetap.)*

---

## 6. Keputusan Desain Mendalam per Topik Kunci

### 6.1 Model Inventory Terpadu — Item, Stock, Warehouse (3 Tabel Terpisah)

**Keputusan final**: 3 tabel terpisah, dengan `Stock` sebagai ledger/pivot antara `Item` dan `Warehouse`.

```
Item                              Stock (ledger)                    Warehouse
─────────────────────             ─────────────────────             ─────────────────────
id                                 id                                 id
brand_id (FK)                      item_id (FK → Item)                name
category_id (FK)                   warehouse_id (FK → Warehouse)      address_line, city, dst
condition (Baru/Second)            quantity_on_hand                   (dipakai juga sbg origin
dynamic_attributes (JSON)          quantity_reserved                   address utk shipping)
grading (nullable, khusus Second)
price
media
publish_status
```

**Kenapa 1 mekanisme untuk keduanya (bukan 2 sistem locking berbeda):**

Reservasi/locking untuk kedua tipe barang pakai mekanisme kuantitas yang sama, bukan cabang logic terpisah (unique-unit-flag vs quantity-decrement):

- **Reservasi** (order dibuat, menunggu bayar): `UPDATE stock SET quantity_reserved = quantity_reserved + N WHERE id=? AND (quantity_on_hand - quantity_reserved) >= N` — 1 statement atomic (P9)
- **Commit** (payment settlement): kurangi `quantity_on_hand` & `quantity_reserved` bareng sejumlah N
- **Release** (timeout/cancel): kurangi `quantity_reserved` saja, kembali ke available

Untuk **Item Second (Serialized)**: `quantity_on_hand` dikunci permanen di 1 (tidak pernah direstock — unit fisik yang sama = listing yang sama selamanya), sehingga `quantity_reserved` cuma toggle 0/1. Ini secara efektif mereplikasi lifecycle Available → On Process → Sold, tapi lewat mekanisme kuantitas yang sama, bukan enum flag terpisah.

Untuk **Item Baru (Pooled)**: `quantity_on_hand` bisa >1, reservasi bisa kurangi beberapa sekaligus. Kode atomic-update-nya identik, cuma beda angka N.

**Aturan v1 vs permanen (penting, beda makna walau skema sama):**
- **Pooled**: v1 dibatasi cuma boleh 1 baris `Stock` per `Item` (belum ada alokasi multi-warehouse), tapi ini pembatasan **sementara**, ditegakkan di level **validasi Service, bukan constraint database** — supaya nanti dilonggarkan tanpa migrasi skema, cukup hapus validasi.
- **Serialized**: pembatasan 1 baris per `Item` itu **permanen** — 1 unit fisik tidak akan pernah ada di 2 lokasi sekaligus, ini bukan keterbatasan teknis yang akan di-backlog-kan.

**Resolusi Default Warehouse**: kolom `warehouse_id` di `Stock` **tidak pernah null**. Kalau staff tidak eksplisit pilih warehouse saat membuat Item, sistem resolve ke Default Warehouse yang berlaku saat itu (dari Global Setting) dan simpan sebagai nilai final di baris `Stock` — snapshot, bukan reference hidup (P8).

### 6.2 Order — 3-Layer Status + Shipment Terpisah untuk Split-Shipment

3 layer status tetap independen satu sama lain:
- **Payment Status** — cerminan callback payment gateway (Pending/Paid/Expired/Denied/Cancelled/Refunded/Disputed)
- **Order Status** — status operasional (siklus dari reservasi sampai selesai/retur)
- **Shipping Status** — status pengiriman fisik

**Keputusan penting**: karena Warehouse mendukung multi-lokasi sungguhan, **Shipping Status tidak bisa lagi jadi 1 field flat di Order**. Model yang dipakai: `Shipment` sebagai entity sendiri, **1 Order bisa punya banyak Shipment** (1 per Warehouse yang terlibat memenuhi order tsb). Tiap `Shipment` punya kurir, AWB/resi, dan Status Pengiriman-nya sendiri.

*(Catatan: logic alokasi otomatis "kalau order butuh dari 2 warehouse, split jadi 2 Shipment bagaimana caranya" masih perlu didetailkan pas breakdown domain Order — skemanya sudah siap untuk itu, tapi algoritma alokasinya belum dirancang, lihat Bagian 9.)*

**Guard condition & state machine**: dipakai pola orchestrated (transisi wajib 1 DB transaction, dengan rollback) vs event-driven (side-effect non-blocking, di-queue), dipilih berdasarkan apakah transisi itu butuh rollback atau retryable. Scheduled job untuk transisi tertunda (timer auto-confirm, dst) wajib re-check state saat ini sebelum eksekusi — guard condition, bukan job cancellation (menghindari race antara cancel-timer vs timer-keburu-fire).

### 6.3 Condition vs Inventory Strategy — Dua Dimensi Independen

Dipisahkan secara skema sejak awal:
- **Condition** — Baru / Second / Refurbished (deskriptif, soal kualitas barang)
- **Inventory Strategy** — Serialized (unique unit) vs Pooled (quantity) — ditentukan lewat aturan bisnis di Catalog Service, dengan default preset (Baru→Pooled, Second→Serialized), tapi tidak di-hardcode 1:1 supaya client masa depan yang punya kombinasi berbeda (misal barang bekas tapi dijual per lot/kuantitas) tetap bisa diakomodasi tanpa migrasi skema.

### 6.4 Customer — Guest-First, Data Tetap Tersimpan, Auth-Ready

- v1 fokus penuh di guest checkout (tanpa Member Login/Wishlist).
- Setiap checkout tetap melakukan **create-or-find Customer record berdasarkan email** — data customer selalu tersimpan sebagai master data internal, terlepas dari tidak adanya sistem login.
- **Skema `Customer` disiapkan "auth-ready" sejak awal** (kolom auth seperti password/email_verified_at disiapkan nullable di tabel yang sama) — supaya kalau Member Login diaktifkan nanti, itu aditif (isi kolom yang sudah ada), **bukan** migrasi data menyatukan 2 tabel terpisah (guest records lama vs akun baru).

### 6.5 Reporting — Domain Read-Only, Konsumen Lintas Domain

Diputuskan jadi domain sendiri (bukan disebar ke tiap domain), karena:
- Report yang berguna umumnya lintas domain (misal laporan margin butuh data Order + Payment + Shipping cost + Promo sekaligus) — tidak cocok dipaksa masuk ke satu domain "hulu" saja tanpa melanggar arah dependency (P7).
- Reporting bersifat read-only & downstream dari semua domain lain — boleh baca dari mana saja, tapi tidak ada domain lain yang boleh depend balik ke Reporting. Arah dependency-nya 1 arah, bersih.
- Mencegah Service domain lain (yang fokusnya mutation/lifecycle) jadi bengkak berisi query-query analitik yang tidak berhubungan langsung dengan tanggung jawab utamanya.

### 6.6 Notification — Dipisah 3 Concern

1. **Dispatch/trigger** (kapan notifikasi dikirim) — tetap tersebar di masing-masing domain lewat Laravel Event, TIDAK disentralkan (konsisten dengan P7).
2. **Setup/Template** — konten notifikasi per titik trigger (subjek, body, placeholder variable), editable admin tanpa deploy. Disentralkan jadi domain `Notification`.
3. **Log/Monitoring** — riwayat pengiriman (terkirim/gagal), opsi resend manual. Disentralkan juga, mirip filosofi Audit Trail tapi khusus untuk notifikasi keluar.

### 6.7 Kenapa Single Package (Bukan Dipecah Core/Admin/Gateway)

Sempat dipertimbangkan pemisahan jadi 3 package terpisah (core domain / admin Filament / gateway integrations), tapi diputuskan tetap 1 package karena:
- Admin dikunci ke Filament (P2) — argumen utama pisah admin dari core (supaya panel admin bisa diganti) jadi tidak relevan.
- Gateway tidak perlu reusable lintas package/engine lain (P4) — argumen pisah gateway juga hilang.
- Memecah jadi beberapa Composer package independen berarti menanggung: matrix kompatibilitas versi antar package, CI/testing terpisah, overhead rilis berkoordinasi tiap ada perubahan lintas package — biaya nyata yang harus dibayar terus-menerus, padahal manfaat yang tadinya jadi alasan pisah (swap admin, reusable gateway) memang tidak dibutuhkan.
- Pemisahan layer (P6) tetap ditegakkan lewat disiplin struktur folder/namespace di dalam 1 package, bukan lewat batas package.

---

## 7. Pola Flow Referensi — Barang Second / Unique Item

Beberapa pola berikut divalidasi lewat studi kasus riil implementasi Brand Store yang menjual barang second/unique item, dan dijadikan baseline generalisasi untuk domain `Item` bertipe Serialized:

- **1 unit fisik = 1 listing**, stok selalu bernilai 1 (kalau ada beberapa unit series/model sama, tetap jadi listing terpisah masing-masing — tidak digabung)
- **Grading kondisi terstandar & terkontrol** (skala tetap, bukan free-text), dipakai untuk badge & filter katalog — dikombinasikan dengan atribut deskriptif kondisi & kelengkapan yang terstruktur per kategori (bukan paragraf bebas)
- **Publish gate**: listing baru berstatus Draft sampai staff yang berwenang (approval role tertentu) mem-publish — pola maker-checker yang sama dipakai lagi di alur Retur, Pembatalan, dan Refund
- **Guest checkout dengan verifikasi step** (mis. OTP) sebelum order dianggap terkonfirmasi ke tahap "diproses" — mencegah reservasi stok terjadi sebelum identitas pemesan minimal tervalidasi
- **Atomic locking per unit** saat checkout, supaya 2 pembeli tidak bisa checkout unit fisik yang sama secara bersamaan
- **Retur diproses manual/case-by-case** (bukan self-service otomatis) karena tiap unit kondisinya unik — disepakati lewat kanal komunikasi customer service, baru dieksekusi lewat alur approval di admin
- **Refund selalu nominal penuh**, tidak ada skema partial, untuk menghindari ambiguitas negosiasi
- **Trust signal mengandalkan foto aktual + grading**, tanpa modul verifikasi/sertifikasi keaslian terpisah — dicatat sebagai keputusan sadar (trade-off biaya vs risiko trust), bukan kealpaan. Layak dipertimbangkan ulang per client tergantung kategori barang & rentang harga.

Pola generic (retail barang baru: cart, checkout, tanpa grading) mengikuti flow e-commerce standar pada umumnya — tidak memerlukan pola khusus sebagaimana barang second di atas.

---

## 8. Non-Goals / Di Luar Scope v1

Eksplisit tidak dikerjakan/dirancang detail di v1 package ini:
- Member Login/Wishlist/Loyalty — cuma disiapkan skema-nya, fitur aktifnya belum
- Alokasi otomatis split-shipment lintas warehouse (skema Shipment sudah siap, algoritma pemilihan warehouse mana yang memenuhi order belum dirancang)
- Multi-warehouse-per-Item untuk barang Pooled (skema siap, validasi masih membatasi 1 warehouse per item di v1)
- Multi-currency / cross-border payment & shipping
- Alur akuisisi barang second dari customer (beli putus/trade-in/konsinyasi) — package ini fokus ke sisi jual (toko → customer), bukan sisi beli (customer → toko)
- Modul verifikasi/sertifikasi keaslian barang second sebagai fitur aktif

---

## 9. Open Items — Belum Diputuskan

1. **Cart untuk katalog campuran (Serialized + Pooled dalam 1 sesi)** — apakah cart mendukung campuran keduanya dengan reservasi immediate begitu Serialized item dimasukkan cart (TTL pendek), atau Serialized tetap dipaksa direct-checkout terpisah dari cart. Masalah "beda warehouse" sudah tidak jadi halangan lagi (karena Shipment sudah mendukung split per Order), tapi cara reservasi/locking-nya masih perlu dirancang.
2. **Algoritma alokasi warehouse untuk Pooled multi-lokasi** (begitu fitur ini diaktifkan pasca-v1) — warehouse mana yang dipenuhi duluan, apakah bisa split 1 line item ke beberapa warehouse sekaligus, dst.
3. **Return/Refund policy — configurable per client atau 1 alur generic?** Barang Second historisnya manual/case-by-case (Bagian 7); barang Baru biasanya self-service dengan window/alasan terstruktur. Perlu diputuskan apakah domain Retur dirancang 1 alur generic yang dipakai semua (manual+approval, self-service jadi shortcut di atasnya), atau genuinely configurable per client/kategori sejak awal.
4. **SEO** — apakah jadi entity/tabel tersendiri, atau cukup trait/concern yang nempel di Content & Catalog (metadata field tambahan), bukan modul CRUD sendiri.

---

## 10. Rencana Lanjutan

Breakdown detail per domain belum dimulai kecuali fondasi Inventory (Bagian 6.1) yang sudah cukup matang. Urutan yang disarankan untuk sesi lanjutan:

1. **Katalog** (Brand → Kategori → Template Attribute → Item → Stock) — direkomendasikan duluan karena paling banyak domain lain bergantung ke sini
2. **Order** (termasuk menuntaskan Open Item #1 dan #3 di atas — Cart & Return policy)
3. **Payment & Shipping** (kontrak gateway + detail integrasi)
4. **User/RBAC & Audit Trail**
5. **Content, Reporting, Notification, Settings** — domain pendukung, bisa didetailkan belakangan setelah 4 domain inti di atas settle

---

*Dokumen ini adalah working draft — akan terus di-update seiring keputusan baru diambil di sesi-sesi berikutnya.*
