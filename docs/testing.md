# Strategi Pengujian — StockHub

| Atribut | Nilai |
|---|---|
| Tujuan | Menjelaskan strategi pengujian, konfigurasi lingkungan uji MySQL, struktur test suite, dan matriks test case. |
| Cakupan | Pengujian Unit, Feature (HTTP + DB), Concurrency, Architecture, dan Static Analysis. |
| Audiens | Developer, QA Engineer, Code Reviewer |
| Status | [SUDAH DIIMPLEMENTASI] (Sebagian — Pengujian Skema, Autentikasi, Akun, dan Keamanan) |
| Versi aplikasi | 1.0.0 |
| Terakhir diperbarui | 2026-11-06 |
| Pemilik | QA Lead |
| Terkait | `prd.md` §16, §20; `docs/architecture.md`; `docs/business-rules.md` |

---

## 1. Lingkungan Basis Data Pengujian

Pengujian otomatis menggunakan **MySQL Server nyata** (`stockhub_test`), bukan SQLite in-memory. Hal ini diwajibkan (PRD §16.1) karena:
- Perilaku CHECK constraint pada MySQL Server.
- Mekanisme *pessimistic row locking* (`SELECT ... FOR UPDATE`) yang akurat.
- Perilaku pembulatan dan presisi tipe data `DECIMAL(15,3)` yang identik dengan lingkungan produksi.

---

## 2. Struktur Test Suite (Pest PHP 3)

```text
tests/
├── Unit/
│   ├── EnumTest.php                    # Pengujian kalkulasi StockStatus dan label enum
│   └── ExampleTest.php
├── Feature/
│   ├── Auth/
│   │   ├── AuthenticationTest.php      # TC-AUTH-01..05, login throttle, pesan generik, logout
│   │   ├── PasswordUpdateTest.php      # TC-AUTH-06, validasi kebijakan password profil
│   │   └── RegistrationTest.php        # Penolakan registrasi publik (HTTP 404)
│   ├── Database/
│   │   ├── SchemaTest.php              # Verifikasi 26 tabel, InnoDB, kolom PRD §11.3
│   │   ├── CheckConstraintTest.php     # Uji CHECK constraint (stok, harga, qty, formula)
│   │   ├── UniqueAndForeignKeyConstraintTest.php # Uji UNIQUE dan FK RESTRICT
│   │   ├── AppendOnlyModelGuardTest.php # Uji penolakan update/delete pada model append-only
│   │   └── SeederTest.php              # Uji eksekusi seeder idempoten dan guard demo
│   ├── User/
│   │   └── UserManagementTest.php      # TC-USR-01..03, TC-AUTHZ-01 & 05, aturan BR-24
│   ├── Security/
│   │   └── ActivityLoggerSecurityTest.php # TC-LOG-02, penyaringan kata sandi & rahasia
│   └── ProfileTest.php                 # Uji profil mandiri & penolakan delete akun (BR-03)
└── Concurrency/                        # Pengujian race condition transaksi (Tahap 4)
```

---

## 3. Matriks Test Case Terimplementasi (Tahap 1 & 2)

| Test Case | Deskripsi Pengujian | Level | File Test | Status |
|---|---|---|---|:---:|
| `TC-AUTH-01` | Login sukses, regenerasi sesi, metadata login terisi, log `auth.login` tercatat | Feature | `Auth/AuthenticationTest.php` | PASS |
| `TC-AUTH-02` | Pesan kegagalan login identik untuk unreg, wrong password, dan inactive account | Feature | `Auth/AuthenticationTest.php` | PASS |
| `TC-AUTH-03` | Percobaan login ke-6 ditolak HTTP 429 dengan header `Retry-After` | Feature | `Auth/AuthenticationTest.php` | PASS |
| `TC-AUTH-04` | Pengguna dengan `must_change_password=true` dibatasi hanya ke formulir ganti password | Feature | `Auth/AuthenticationTest.php` | PASS |
| `TC-AUTH-05` | Akun dinonaktifkan saat login diputus sesinya pada request berikutnya | Feature | `Auth/AuthenticationTest.php` | PASS |
| `TC-AUTH-06` | Pembaruan kata sandi mandiri mensyaratkan kata sandi lama yang benar | Feature | `Auth/PasswordUpdateTest.php` | PASS |
| `TC-USR-01` | Penambahan pengguna dengan email duplikat (case-insensitive) ditolak | Feature | `User/UserManagementTest.php` | PASS |
| `TC-USR-02` | Super Admin aktif terakhir tidak dapat dinonaktifkan atau diturunkan perannya (`LAST_SUPER_ADMIN`) | Feature | `User/UserManagementTest.php` | PASS |
| `TC-USR-03` | Reset kata sandi menetapkan password sementara baru dan menghapus sesi aktif pengguna | Feature | `User/UserManagementTest.php` | PASS |
| `TC-AUTHZ-01`| Akses rute manajemen pengguna dan matriks peran oleh AG, SG, MG menghasilkan HTTP 403 | Feature | `User/UserManagementTest.php` | PASS |
| `TC-AUTHZ-05`| Admin Gudang ditolak mengakses `/users` dan `/roles` | Feature | `User/UserManagementTest.php` | PASS |
| `TC-LOG-02` | `ActivityLogger` menyaring kata sandi, token, dan secret dari payload log | Feature | `Security/ActivityLoggerSecurityTest.php` | PASS |
| `TC-STK-01..02`| Penentuan status stok (`habis`, `minimum`, `aman`) sesuai aturan batas | Unit | `Unit/EnumTest.php` | PASS |
| `BR-03 / 15` | Penghapusan akun mandiri ditolak, model append-only menolak update/delete | Feature | `ProfileTest.php`, `AppendOnlyModelGuardTest.php` | PASS |

---

## 4. Perintah Eksekusi Pengujian

```bash
# Menjalankan seluruh test suite Pest
composer test

# Menjalankan pengujian linter kode
composer lint

# Menjalankan analisis statis tipe data (PHPStan Level 5)
composer analyse

# Memeriksa keamanan dependensi
composer audit
npm audit
```
