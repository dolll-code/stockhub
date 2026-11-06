# StockHub

**Sistem Informasi Manajemen Persediaan Barang Berbasis Web (Single Warehouse)**

| Atribut | Nilai |
|---|---|
| Status Dokumen | [SUDAH DIIMPLEMENTASI] (Tahap 0 — Baseline Setup) |
| Framework | Laravel 12.71.0 (PHP 8.2.29) |
| Database | MySQL 8.0.30 (InnoDB, `utf8mb4_unicode_ci`) |
| Versi Aplikasi | 1.0.0-dev |

---

## 1. Deskripsi Proyek

StockHub adalah aplikasi web manajemen persediaan barang yang dirancang untuk toko, perusahaan, dan pengelola gudang dengan sistem satu gudang (*single warehouse*). Sistem ini mengutamakan integritas saldo stok barang melalui pencatatan transaksi masuk, transaksi keluar, penyesuaian stok (*stock opname*), pemantauan stok minimum, riwayat mutasi permanen (*append-only*), serta jejak audit lengkap (*audit log*).

Aplikasi dibangun menggunakan pola **monolit Laravel 12 server-rendered** (Blade + Tailwind CSS + Alpine.js). Seluruh perubahan stok dikelola secara terpusat oleh `StockService` dengan transaksi database atomik dan *pessimistic row locking* untuk mencegah stok negatif dan *concurrency race condition*.

---

## 2. Fitur dan Status Implementasi

> **Catatan Fase:** Saat ini proyek berada pada **Tahap 0 (Kerangka Baseline)**. Dependensi inti dan kerangka kerja telah siap, sedangkan logika dan alur bisnis berada pada status `[BELUM DIIMPLEMENTASI]`.

### 2.1 Fitur MVP (Rilis 1.0.0)
- **Autentikasi & Akun:** Login berbasis sesi, throttle percobaan login, wajib ubah password sementara, tanpa registrasi publik (akun hanya dibuat Super Admin) `[BELUM DIIMPLEMENTASI]`.
- **Manajemen Pengguna & Peran:** 4 peran tetap (*Super Admin*, *Admin Gudang*, *Staff Gudang*, *Manajer*) via Spatie Laravel Permission `[BELUM DIIMPLEMENTASI]`.
- **Dashboard:** Kartu ringkasan barang, grafik perbandingan transaksi masuk vs keluar, tabel transaksi terbaru, daftar stok kritis `[BELUM DIIMPLEMENTASI]`.
- **Master Data:** Kategori, Satuan (aturan bulat vs desimal), Supplier (kode otomatis), Barang (saldo awal, foto barang) `[BELUM DIIMPLEMENTASI]`.
- **Transaksi Barang Masuk:** Alur draft, konfirmasi (tambah stok), pembatalan dengan movement pembalik `[BELUM DIIMPLEMENTASI]`.
- **Transaksi Barang Keluar:** Alur draft, konfirmasi dengan pengecekan stok terkunci, pembatalan dengan movement pembalik `[BELUM DIIMPLEMENTASI]`.
- **Penyesuaian Stok:** Penyesuaian fisik vs sistem, deteksi selisih, alasan penyesuaian, verifikasi stok terkunci `[BELUM DIIMPLEMENTASI]`.
- **Riwayat Stok & Pemantauan:** Lonceng notifikasi stok minimum di navbar, halaman stok barang, riwayat mutasi permanen `[BELUM DIIMPLEMENTASI]`.
- **Laporan & Ekspor:** 5 laporan inti (Persediaan, Barang Masuk, Barang Keluar, Penyesuaian, Mutasi Stok) dengan ekspor PDF (DomPDF) dan Excel (Maatwebsite Excel) `[BELUM DIIMPLEMENTASI]`.
- **Audit & Keamanan:** Log aktivitas append-only, rekonsiliasi harian integritas stok (`php artisan stockhub:reconcile`) `[BELUM DIIMPLEMENTASI]`.

### 2.2 Fitur Fase Berikutnya `[DIRENCANAKAN]`
- Multi-gudang dan transfer stok antar gudang.
- Konversi satuan bertingkat.
- Scan barcode/QR code barang.
- Impor data master dari file Excel.
- Reset password mandiri via email (SMTP).
- API token (Laravel Sanctum) untuk integrasi pihak ketiga.

---

## 3. Tech Stack dan Versi Aktual

| Komponen | Teknologi | Versi Terkunci / Baseline |
|---|---|---|
| Backend Framework | Laravel | 12.71.0 |
| Bahasa Pemrograman | PHP | 8.2.29 (ZTS Visual C++ 2019 x64) |
| Database Engine | MySQL | 8.0.30 (InnoDB, `utf8mb4_unicode_ci`) |
| Starter Kit Autentikasi | Laravel Breeze | 2.4.2 (Blade stack, registrasi publik dinonaktifkan) |
| Otorisasi & Peran | Spatie Laravel Permission | 6.25.0 |
| CSS Framework | Tailwind CSS | 3.4.17 |
| Frontend Interaktivitas | Alpine.js | 3.4.2 |
| Grafik & Chart | Chart.js | 4.4.7 |
| Alert & Dialog UI | SweetAlert2 | 11.16.1 |
| Build Tool | Vite | 6.0.11 |
| PDF Generator | barryvdh/laravel-dompdf | 3.1.2 |
| Excel Generator | maatwebsite/excel | 3.1.70 |
| Testing Framework | Pest PHP | 3.8.7 (PHPUnit 11.5.56) |
| Code Formatter | Laravel Pint | 1.30.4 |
| Static Analysis | Larastan (PHPStan) | 3.14.0 (Level 5) |

---

## 4. Prasyarat Lingkungan

Pastikan perangkat lokal (Laragon / Windows) telah terpasang:
1. **PHP:** Versi 8.2.x dengan ekstensi aktif: `pdo_mysql`, `mbstring`, `bcmath`, `fileinfo`, `gd`, `zip`, `xml`, `curl`, `tokenizer`.
2. **Composer:** Versi 2.x.
3. **Node.js & npm:** Node LTS (v20+ atau v24+) dan npm v10+.
4. **MySQL:** MySQL Server versi 8.0.x (mendukung CHECK constraint dan InnoDB row-level locking).
5. **Git:** Git client untuk version control.

---

## 5. Panduan Instalasi Lokal (Laragon / Windows)

Langkah-langkah berikut dapat dijalankan dari repositori hasil *clean clone*:

### 5.1 Clone Repositori
```bash
cd C:\laragon\www
git clone <url-repository> stockhub
cd stockhub
```

### 5.2 Instal Dependensi Backend
```bash
composer install
```

### 5.3 Instal Dependensi Frontend
```bash
npm install
```

### 5.4 Konfigurasi Environment File
Salin template konfigurasi `.env.example` ke `.env`:
```bash
copy .env.example .env
```

Bangkitkan application encryption key:
```bash
php artisan key:generate
```

### 5.5 Konfigurasi Database MySQL
Pastikan daemon MySQL di Laragon telah berjalan. Buat database untuk aplikasi dan testing:
```sql
CREATE DATABASE stockhub CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE stockhub_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Periksa konfigurasi di file `.env`:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=stockhub
DB_USERNAME=root
DB_PASSWORD=

SESSION_DRIVER=database
SESSION_LIFETIME=120
CACHE_STORE=database
QUEUE_CONNECTION=sync
APP_TIMEZONE=Asia/Jakarta
APP_LOCALE=id
```

### 5.6 Tautkan Storage Simbolik
```bash
php artisan storage:link
```

### 5.7 Kompilasi Aset Frontend
Untuk mode pengembangan (*watch mode*):
```bash
npm run dev
```

Untuk kompilasi produksi:
```bash
npm run build
```

---

## 6. Prosedur Akun Super Admin Awal

StockHub **tidak mengizinkan registrasi mandiri/publik**. Akun Super Admin pertama dibuat melalui seeder pada tahap migrasi basis data (Tahap 1):
1. Tetapkan email administrator pada file `.env` variabel `INITIAL_ADMIN_EMAIL`.
2. Jalankan seeder resmi: `php artisan db:seed --class=InitialSuperAdminSeeder`.
3. Password acak sementara satu kali pakai akan dicetak di konsol terminal saat seeder dieksekusi.
4. Pada login pertama, Super Admin diwajibkan mengganti password (`must_change_password = true`).
5. **Dilarang keras menyimpan password atau credential pengguna nyata di source control/repositori.**

---

## 7. Pengujian, Linting, dan Kualitas Kode

Perintah standar untuk verifikasi kualitas kode:

```bash
# Jalankan test suite Pest pada basis data MySQL stockhub_test
composer test
# atau
php artisan test

# Periksa format gaya kode (Pint dry-run)
composer lint
# atau
.\vendor\bin\pint --test

# Perbaiki format gaya kode secara otomatis
composer lint:fix
# atau
.\vendor\bin\pint

# Analisis statis PHPStan / Larastan (Level 5)
composer analyse
# atau
.\vendor\bin\phpstan analyse

# Audit keamanan dependensi Composer dan NPM
composer audit
npm audit
```

---

## 8. Struktur Direktori Utama

```text
stockhub/
├── app/
│   ├── Console/Commands/       # Perintah Artisan (termasuk rekonsiliasi harian)
│   ├── Enums/                  # PHP Enum domain (Status, Tipe Movement, dsb.)
│   ├── Exceptions/             # Domain Exception terpusat
│   ├── Exports/                # Definisi ekspor file Excel
│   ├── Http/
│   │   ├── Controllers/        # Controller server-rendered
│   │   ├── Middleware/         # Middleware auth, active user, password change
│   │   └── Requests/           # FormRequest validasi input
│   ├── Models/                 # Model Eloquent
│   ├── Policies/               # Policy otorisasi hak akses objek
│   ├── Reports/                # Query object laporan (Single Source of Truth)
│   └── Services/               # Service layer logika bisnis (StockService, dsb.)
├── config/                     # Konfigurasi sistem
├── database/
│   ├── factories/              # Factory data testing
│   ├── migrations/             # Skema tabel database
│   └── seeders/                # Seeder peran, satuan, pengaturan, demo
├── docs/                       # Dokumentasi teknis sistem (SoT)
├── public/                     # Web root dan build aset Vite
├── resources/
│   ├── css/                    # Tailwind CSS source
│   ├── js/                     # Alpine.js, Chart.js, SweetAlert2 init
│   └── views/                  # Blade templates (layouts, components, pages)
├── routes/                     # Definisi rute web dan AJAX
└── tests/
    ├── Concurrency/            # Pengujian race condition transaksi
    ├── Feature/                # Pengujian fungsional alur HTTP & DB
    └── Unit/                   # Pengujian logika unit murni
```

---

## 9. Peta Dokumentasi Proyek

Dokumentasi lengkap dan spesifikasi detail tersedia pada direktori `docs/` dengan struktur *Single Source of Truth* (SoT):

| Dokumen | Lokasi | Cakupan Utama | Status |
|---|---|---|---|
| **Product Requirements** | `prd.md` | Requirement produk, aturan bisnis, matriks otorisasi | [DITETAPKAN] |
| **Arsitektur Sistem** | `docs/architecture.md` | Arsitektur MVC monolit, service layer, locking, alur posting | [BELUM DIIMPLEMENTASI] |
| **Skema Database** | `docs/database.md` | ERD, kamus data tabel, indeks, CHECK constraint, migrasi | [BELUM DIIMPLEMENTASI] |
| **Spesifikasi API & Rute** | `docs/api.md` | Katalog rute web, endpoint AJAX JSON, katalog kode error | [BELUM DIIMPLEMENTASI] |
| **Autentikasi & Hak Akses**| `docs/authentication.md`| Matriks 4 peran, izin Spatie, kebijakan sesi & password | [BELUM DIIMPLEMENTASI] |
| **Aturan Bisnis** | `docs/business-rules.md` | Katalog BR-01 s.d. BR-32, status & transisi, rumus stok | [BELUM DIIMPLEMENTASI] |
| **Panduan Pengguna** | `docs/user-guide.md` | Alur kerja per peran pengguna (SA, AG, SG, MG) | [BELUM DIIMPLEMENTASI] |
| **Deployment & Ops** | `docs/deployment.md` | Prosedur rilis, migrasi produksi, backup, cron scheduler | [BELUM DIIMPLEMENTASI] |
| **Strategi Pengujian** | `docs/testing.md` | Setup MySQL test, pemetaan TC-*, konkurensi, UAT | [BELUM DIIMPLEMENTASI] |
| **Troubleshooting** | `docs/troubleshooting.md` | Panduan diagnosis error operasional dan solusinya | [BELUM DIIMPLEMENTASI] |
| **Changelog** | `docs/changelog.md` | Catatan riwayat perubahan per versi (Keep a Changelog) | [SUDAH DIIMPLEMENTASI] |
| **Kontribusi** | `CONTRIBUTING.md` | Standar git branch, commit message, dan checklist DoD | [SUDAH DIIMPLEMENTASI] |
