# Changelog — StockHub

Semua perubahan penting pada proyek StockHub didokumentasikan di berkas ini.
Format mengikuti standar [Keep a Changelog](https://keepachangelog.com/id/1.0.0/) dan menganut penomoran [Semantic Versioning](https://semver.org/lang/id/).

---

## [Unreleased]

### Added
- Modul Dashboard lengkap per peran (`DashboardController`): 8 kartu metrik untuk SA/AG/MG, grafik interaktif perbandingan transaksi confirmed masuk vs keluar (harian 14 hari, mingguan 12 minggu, bulanan 12 bulan) dengan cache 60 detik, 10 transaksi terbaru, dan 10 barang paling kritis (FR-DSH-01..04, BR-27).
- Tampilan Dashboard terbatas untuk Staff Gudang (SG): hanya 3 kartu stok (tersedia, minimum, habis), daftar stok kritis, dan 10 transaksi terbaru milik sendiri tanpa grafik global atau data harga (FR-DSH-05, BR-26).
- Halaman Stok Barang (`/stocks`): pemantauan status stok, kategori, kuantitas saat ini, batas minimum, lokasi rak, pencarian, dan paginasi (FR-STK-01).
- Halaman Stok Minimum (`/stocks/low`): penyaringan khusus barang aktif berkategori kritis (`habis` dan `minimum`) dengan pengurutan berdasarkan tingkat krisis ketersediaan (FR-STK-02).
- Notifikasi live lonceng navbar: indikator badge real-time yang menghitung jumlah jenis barang aktif berstatus habis + minimum dengan tautan langsung ke `/stocks/low` tanpa tabel notifikasi (FR-NTF-01, AC-STK-04).
- Halaman Riwayat Stok Global (`/stock-movements`): buku besar mutasi stok read-only untuk SA, AG, MG dengan filter barang, jenis mutasi, rentang tanggal, dan pengguna (FR-STK-03).
- Lima Query Objects Laporan terpusat (`App\Reports\*ReportQuery`): Persediaan (dengan dukungan `as_of_date` historis), Barang Masuk (hanya confirmed), Barang Keluar (hanya confirmed), Penyesuaian Stok, dan Pergerakan Stok (asli + pembalik) sesuai BR-21, BR-27, dan BR-31.
- Halaman Laporan Interaktif (`ReportController`, `/reports/{type}`): navigasi tab 5 laporan resmi, filter lengkap per kriteria bisnis, dan proteksi dari Staff Gudang (HTTP 403).
- Suite pengujian fitur Tahap 5: `DashboardTest.php` (TC-DSH-01..04), `StockMonitoringTest.php` (TC-STK-01..05), `ReportTest.php` (TC-RPT-01..04), serta `PerformanceAndNPlusOneTest.php` untuk verifikasi efisiensi query dan audit `preventLazyLoading`.
- Layanan inti transaksi dan mutasi stok `StockService` (`App\Services\StockService`) sebagai gerbang tunggal penulisan `products.current_stock` menggunakan kalkulasi presisi BCMath, *pessimistic row locking* berurutan ID naik, dan penanganan *deadlock retry* 3x (Fase 4, BR-06, BR-08, BR-09).
- Modul Transaksi Barang Masuk (`StockInService`, `StockInController`): alur draft (1–100 item), konfirmasi penambahan stok atomik, pembatalan draft, dan pembatalan confirmed dengan mutasi pembalik `stock_in_cancel` (FR-STI-01..07, BR-13, BR-22).
- Modul Transaksi Barang Keluar (`StockOutService`, `StockOutController`): alur draft (1–100 item), pencegahan *overselling* saat konfirmasi dengan penolakan menyeluruh (`INSUFFICIENT_STOCK`), pembatalan draft, dan pemulihan stok confirmed via `stock_out_cancel` (FR-STO-01..07, BR-10).
- Modul Penyesuaian Stok Opname (`StockAdjustmentController`): formulir hitung fisik opname, deteksi perubahan stok latar belakang (`STOCK_CHANGED`), penolakan selisih nol (`NO_DIFFERENCE`), dan posting final langsung (FR-ADJ-01..02, BR-16).
- Generator nomor dokumen terurut sekuensial aman konkurensi pada `DocumentNumberService`: `IN-YYYYMM-NNNN`, `OUT-YYYYMM-NNNN`, dan `ADJ-YYYYMM-NNNN` (BR-19).
- Endpoint AJAX JSON untuk transaksi: `/ajax/products/search` (pencarian barang aktif) dan `/ajax/products/{id}/stock` (stok dan satuan terkini) dengan penyembunyian harga dari Staff Gudang (PRD §12.3).
- Hirarki exception domain terpusat: `InsufficientStockException`, `ProductInactiveException`, `ReversalInsufficientStockException`, `NoDifferenceException`, `InvalidStateTransitionException`, `StockChangedException`, dan `LockTimeoutException` (PRD §15).
- Tampilan Blade/Tailwind/Alpine responsif dengan baris barang dinamis, kalkulasi subtotal otomatis, modal konfirmasi SweetAlert2 anti-double-click, dan penanda peringatan stok kurang.
- Suite pengujian transaksi dan konkurensi Pest: `StockInTest.php` (TC-STI-01..10), `StockOutTest.php` (TC-STO-01..07), `StockAdjustmentTest.php` (TC-ADJ-01..04), `ConcurrencyTest.php` (TC-CON-01..03 & 50-iteration MySQL stress test), `ManagerReadOnlyAuthorizationTest.php` (TC-AUTHZ-03), dan `StockArchitectureTest.php` (ARCH-STOCK).
- Layanan penomoran berurutan aman konkurensi `DocumentNumberService` (`App\Services\DocumentNumberService`) dengan row-lock `document_sequences` untuk kode supplier `SUP-NNNN` dan barang `BRG-NNNN` (BR-19, Fase 3).
- Primitive minimal `StockService::recordOpeningBalance` (`App\Services\StockService`) sebagai titik penulisan tunggal `products.current_stock` untuk mutasi saldo awal barang baru (BR-06, BR-17).
- Service layer master data terisolasi: `CategoryService`, `UnitService`, `SupplierService`, dan `ProductService`.
- FormRequest validasi server-side lengkap: `CategoryStoreRequest`, `CategoryUpdateRequest`, `UnitStoreRequest`, `UnitUpdateRequest`, `SupplierStoreRequest`, `SupplierUpdateRequest`, `ProductStoreRequest`, `ProductUpdateRequest`.
- Controller dan view Blade/Tailwind/Alpine untuk master data: Kategori (`/categories`), Satuan (`/units`), Supplier (`/suppliers`), dan Master Barang (`/products`).
- Fitur upload foto barang aman dengan validasi real MIME, verifikasi dimensi 100x100 s.d. 4000x4000 px, penolakan SVG dan file palsu, serta nama acak di `storage/app/public/products` (SEC-08, FR-PRD-06).
- Kebijakan kerahasiaan harga: penyembunyian harga beli dan jual dari peran Staff Gudang (SG) pada tampilan HTML maupun JSON (BR-26, TC-AUTHZ-04).
- Penegakan penguncian satuan: penguncian `allow_decimal` setelah dipakai barang, dan penguncian satuan pada barang yang memiliki riwayat mutasi stok (BR-25).
- Penegakan pembatasan status: penolakan penonaktifan kategori/satuan yang masih dipakai barang aktif (`REFERENCE_IN_USE`), serta penolakan penonaktifan barang yang masih memiliki stok > 0 (BR-04).
- Suite pengujian fitur dan acceptance criteria Tahap 3: `CategoryTest.php` (TC-CAT-01..03), `UnitTest.php` (TC-UNT-01..02), `SupplierTest.php` (TC-SUP-01..03), dan `ProductTest.php` (TC-PRD-01..07, TC-AUTHZ-04, TC-SEC-05).
- Inisialisasi kerangka kerja aplikasi Laravel 12.71 pada baseline PHP 8.2.29 dan MySQL 8.0.30/InnoDB (PRD §10, Fase 0).
- Instalasi starter kit Laravel Breeze dengan stack Blade, Tailwind CSS 3.4, dan Alpine.js 3.4.
- Konfigurasi dependensi frontend analitik dan interaktif: Chart.js 4.4 dan SweetAlert2 11.16.
- Konfigurasi dependensi backend terverifikasi: Spatie Laravel Permission 6.25, Barryvdh Laravel DomPDF 3.1, dan Maatwebsite Excel 3.1.70.
- Setup pengujian dan penjaminan mutu: Pest 3.8 (PHPUnit 11.5), Laravel Pint 1.30, serta Larastan 3.14 (Level 5).
- Pipeline CI otomatis pada GitHub Actions (`.github/workflows/ci.yml`) dengan runner MySQL 8.0 service.
- Layout baseline Blade: Sidebar navigasi responsif, Top Navbar dengan lonceng notifikasi stok minimum, dan kerangka area konten.
- Halaman error standar berbahasa Indonesia: HTTP 403, 404, 419, 429, 500, dan 503 (NFR-UI-07).
- Dokumentasi fondasi: `README.md`, `CONTRIBUTING.md`, `.github/pull_request_template.md`, dan kerangka metadata `docs/` sesuai PRD §19.5.
- Layanan `ActivityLogger` (`App\Services\ActivityLogger`) sebagai fondasi jejak audit permanen sistem (D-29, BR-30).
- Middleware `EnsureUserIsActive` dan `EnsurePasswordChanged` untuk kontrol keamanan akun (FR-AUTH-05, FR-AUTH-08).
- Rule validasi `StockHubPassword` untuk penegakan kebijakan kata sandi minimal 10 karakter, huruf besar, kecil, angka, dan tidak sama dengan email.
- Modul manajemen pengguna khusus Super Admin (`UserController`) dengan fitur pencarian, filter peran, filter status, pengaktifan/penonaktifan akun, dan reset password (FR-USR-01..05).
- Halaman peninjauan matriks peran dan izin Spatie *read-only* (`RoleController`, FR-USR-06).
- Halaman dan rute penggantian kata sandi wajib awal (`PasswordChangeController`, FR-AUTH-05).

### Changed
- Konfigurasi default aplikasi disesuaikan: timezone `Asia/Jakarta`, locale `id`, fallback locale `id`, faker locale `id_ID`.
- Database default dialihkan ke koneksi MySQL dengan engine InnoDB dan collation `utf8mb4_unicode_ci`.
- Konfigurasi session lifetime ditetapkan 120 menit dengan session driver `database`.
- Pengaturan queue default ditetapkan ke mode `sync` dan cache store ke `database`.
- Registrasi formatting mata uang IDR (`Number::useCurrency('IDR')`, `Number::useLocale('id')`, directive `@currency`) di AppServiceProvider.
- Formulir profil mandiri diperbarui untuk memutus sesi perangkat lain saat kata sandi diubah (`Auth::logoutOtherDevices`, FR-AUTH-09).

### Security
- Rute dan antarmuka registrasi publik dinonaktifkan sepenuhnya (`GET /register` dan `POST /register` mengembalikan HTTP 404); akun pengguna hanya dibuat oleh Super Admin (FR-AUTH-01, D-31).
- Pembatasan percobaan login: maksimal 5 kali gagal per menit per kombinasi email + IP; percobaan ke-6 menghasilkan HTTP 429 dengan hitung mundur (FR-AUTH-04, AC-AUTH-03).
- Pesan kegagalan login dibuat identik dan generik untuk email tidak terdaftar, kata sandi salah, dan akun nonaktif guna mencegah *account enumeration* (FR-AUTH-03, AC-AUTH-02).
- Regenerasi ID sesi otomatis saat login berhasil untuk mencegah *session fixation* (FR-AUTH-02, SEC-03).
- Pemutusan sesi aktif secara langsung dari database saat akun dinonaktifkan atau kata sandi direset oleh Super Admin (FR-AUTH-08, FR-USR-05, AC-USR-03).
- Penegakan aturan BR-24: larangan penonaktifan akun sendiri, larangan pengubahan peran akun sendiri, dan perlindungan Super Admin aktif terakhir (`LAST_SUPER_ADMIN`).
- Penyaringan otomatis data sensitif (password, token, secret, app_key) pada payload `ActivityLogger` (SEC-16, FR-LOG-03).
- Audit dependensi otomatis via `composer audit` dan `npm audit`.
- Template konfigurasi `.env.example` diproteksi tanpa menyertakan secret atau credential riil.

### Removed
- Rute dan tampilan `DELETE /profile` dihapus demi mematuhi prinsip integritas data master bahwa entitas pengguna tidak pernah dihapus secara fisik (BR-03, BR-12).
- Rute lupa password via email dan verifikasi email bawaan Breeze dinonaktifkan sesuai batasan MVP rilis 1.0.0.

### Architecture
- Struktur direktori aplikasi diperluas sesuai rancangan monolit PRD §9.3 (`Enums/`, `Exceptions/`, `Services/`, `Policies/`, `Reports/`, `Exports/`).
- Penetapan satu jalur tulis stok melalui `StockService` dengan penulisan terpusat (BR-06).

### Schema
- Penyesuaian tabel `users` dengan penambahan kolom `is_active`, `must_change_password`, `last_login_at`, `last_login_ip` serta penyesuaian panjang field `name` dan `email` (PRD §11.3).
- Penerbitan migrasi Spatie Permission (`permissions`, `roles`, `model_has_permissions`, `model_has_roles`, `role_has_permissions`).
- Pembuatan migrasi tabel master data: `categories`, `units`, `suppliers`, dan `products` dengan FK `ON DELETE RESTRICT` serta CHECK constraint MySQL (PRD §11.3, BR-02).
- Pembuatan migrasi tabel transaksi: `stock_ins`, `stock_in_details`, `stock_outs`, `stock_out_details`, dan `stock_adjustments` dengan `UNIQUE (header_id, product_id)` dan formula CHECK (BR-11, BR-16).
- Pembuatan migrasi tabel append-only dan sistem: `stock_movements`, `activity_logs`, `document_sequences`, dan `settings` dengan formula invarian mutasi (BR-15, BR-19).
- Implementasi PHP backed Enums: `TransactionStatus`, `MovementType`, `StockInSource`, `StockOutPurpose`, `AdjustmentReason`, serta `StockStatus` (status turunan).
- Pembuatan Model Eloquent lengkap beserta relasi, casts, scopes, dan model guard append-only pada `StockMovement` dan `ActivityLog`.
- Pembuatan Model Factories dan Seeder idempoten (`RoleAndPermissionSeeder`, `UnitSeeder`, `SettingSeeder`, `InitialSuperAdminSeeder`, dan `DemoSeeder`).
- Pembaruan dokumen `docs/database.md` mencerminkan skema aktual dan ERD Mermaid.
