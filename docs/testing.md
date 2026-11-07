# Strategi Pengujian — StockHub

| Atribut | Nilai |
|---|---|
| Tujuan | Menjelaskan strategi pengujian, konfigurasi lingkungan uji MySQL, struktur test suite, dan matriks test case. |
| Cakupan | Pengujian Unit, Feature (HTTP + DB), Concurrency, Architecture, dan Static Analysis. |
| Audiens | Developer, QA Engineer, Code Reviewer |
| Status | [SUDAH DIIMPLEMENTASI] (Tahap 1, 2, dan 3 Master Data) |
| Versi aplikasi | 1.0.0 |
| Terakhir diperbarui | 2026-11-07 |
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
│   ├── Master/
│   │   ├── CategoryTest.php            # TC-CAT-01..03, AC-CAT-01..03, BR-04 penonaktifan
│   │   ├── UnitTest.php                # TC-UNT-01..02, AC-UNT-01..02, penguncian allow_decimal
│   │   ├── SupplierTest.php            # TC-SUP-01..03, AC-SUP-01..03, kode terurut SUP-NNNN
│   │   └── ProductTest.php             # TC-PRD-01..07, AC-PRD-01..09, TC-AUTHZ-04, TC-SEC-05
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
| `TC-CAT-01` | Duplikasi nama kategori (case-insensitive) ditolak (AC-CAT-01) | Feature | `Master/CategoryTest.php` | PASS |
| `TC-CAT-02` | Penonaktifan kategori yang dipakai barang aktif ditolak dengan jumlah terkait (AC-CAT-02, BR-04) | Feature | `Master/CategoryTest.php` | PASS |
| `TC-CAT-03` | Kategori nonaktif tidak muncul di pilihan form barang baru (AC-CAT-03, BR-03) | Feature | `Master/CategoryTest.php` | PASS |
| `TC-UNT-01` | Nilai `allow_decimal` terkunci setelah satuan digunakan oleh barang (AC-UNT-01, BR-25) | Feature | `Master/UnitTest.php` | PASS |
| `TC-UNT-02` | Kuantitas desimal ditolak pada satuan bilangan bulat (AC-UNT-02, BR-01) | Feature | `Master/UnitTest.php` | PASS |
| `TC-SUP-01` | Kode supplier `SUP-NNNN` terurut dan unik di bawah konkurensi / row lock (AC-SUP-01, BR-19) | Feature | `Master/SupplierTest.php` | PASS |
| `TC-SUP-02` | Supplier nonaktif tidak muncul pada pilihan data baru (AC-SUP-02, BR-22) | Feature | `Master/SupplierTest.php` | PASS |
| `TC-SUP-03` | Kode supplier immutable dan supplier dapat dinonaktifkan kapan saja (AC-SUP-03, BR-04, BR-05) | Feature | `Master/SupplierTest.php` | PASS |
| `TC-PRD-01` | Pembuatan barang + `opening_stock > 0` menghasilkan tepat 1 movement `opening_balance` & set `current_stock` (AC-PRD-01, BR-17) | Feature | `Master/ProductTest.php` | PASS |
| `TC-PRD-02` | `opening_stock = 0` tidak membuat movement; kegagalan mutasi memicu rollback total (AC-PRD-02) | Feature | `Master/ProductTest.php` | PASS |
| `TC-PRD-03` | Kode barang bersifat *immutable* setelah disimpan (AC-PRD-03, BR-05) | Feature | `Master/ProductTest.php` | PASS |
| `TC-PRD-04` | Penonaktifan barang dengan stok > 0 ditolak; hanya diizinkan saat stok = 0 (AC-PRD-04, BR-04) | Feature | `Master/ProductTest.php` | PASS |
| `TC-PRD-05` | Satuan barang terkunci setelah memiliki riwayat mutasi stok (AC-PRD-05, BR-25) | Feature | `Master/ProductTest.php` | PASS |
| `TC-AUTHZ-04`| Staff Gudang (SG) tidak menerima kolom/nilai harga pada HTML maupun JSON (AC-PRD-06, BR-26) | Feature | `Master/ProductTest.php` | PASS |
| `TC-SEC-05` | Unggah file palsu (bukan gambar), SVG, > 2 MB, atau dimensi di luar 100x100..4000x4000 ditolak (AC-PRD-07, SEC-08) | Feature | `Master/ProductTest.php` | PASS |
| `TC-PRD-06` | Nilai negatif pada harga, stok minimum, atau stok awal ditolak validasi dan CHECK (AC-PRD-08, BR-02) | Feature | `Master/ProductTest.php` | PASS |
| `TC-PRD-07` | Field `current_stock` diabaikan bila dikirim via form/request; StockService penulis tunggal (AC-PRD-09, BR-06) | Feature | `Master/ProductTest.php` | PASS |

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
