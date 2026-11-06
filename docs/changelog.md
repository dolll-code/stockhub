# Changelog — StockHub

Semua perubahan penting pada proyek StockHub didokumentasikan di berkas ini.
Format mengikuti standar [Keep a Changelog](https://keepachangelog.com/id/1.0.0/) dan menganut penomoran [Semantic Versioning](https://semver.org/lang/id/).

---

## [Unreleased]

### Added
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
