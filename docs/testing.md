# Strategi Pengujian — StockHub

| Atribut | Nilai |
|---|---|
| Tujuan | Menjelaskan strategi pengujian, konfigurasi lingkungan uji MySQL, struktur test suite, dan matriks test case. |
| Cakupan | Pengujian Unit, Feature (HTTP + DB), Concurrency, Architecture, dan Static Analysis. |
| Audiens | Developer, QA Engineer, Code Reviewer |
| Status | [SUDAH DIIMPLEMENTASI] (Tahap 1–4 Lengkap: Master & Transaksi Stok) |
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
│   ├── Transaction/
│   │   ├── StockInTest.php             # TC-STI-01..10, AC-STI-01..12, TC-AUTHZ-02
│   │   ├── StockOutTest.php            # TC-STO-01..07, AC-STO-01..08, AJAX stock
│   │   ├── StockAdjustmentTest.php     # TC-ADJ-01..04, AC-ADJ-01..06, finality
│   │   └── ConcurrencyTest.php         # TC-CON-01..03, 50 iterasi stres konkurensi MySQL
│   ├── Architecture/
│   │   └── StockArchitectureTest.php  # Verifikasi tunggal StockService & larangan delete
│   ├── Security/
│   │   ├── ActivityLoggerSecurityTest.php # TC-LOG-02, penyaringan kata sandi & rahasia
│   │   └── ManagerReadOnlyAuthorizationTest.php # TC-AUTHZ-03, MG ditolak semua POST/PUT/PATCH
│   └── ProfileTest.php                 # Uji profil mandiri & penolakan delete akun (BR-03)
```

---

## 3. Matriks Test Case Terimplementasi (Tahap 1–4)

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
| `TC-AUTHZ-02`| Staff Gudang tidak dapat mengakses atau mengelola transaksi milik staf lain (HTTP 404, AC-STI-09) | Feature | `Transaction/StockInTest.php` | PASS |
| `TC-AUTHZ-03`| Manajer (MG) ditolak melakukan mutasi data pada seluruh modul (semua POST/PUT/PATCH -> 403) | Feature | `Security/ManagerReadOnlyAuthorizationTest.php` | PASS |
| `TC-AUTHZ-04`| Staff Gudang (SG) tidak menerima kolom/nilai harga pada HTML maupun JSON (AC-PRD-06, BR-26) | Feature | `Master/ProductTest.php` | PASS |
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
| `TC-SEC-05` | Unggah file palsu (bukan gambar), SVG, > 2 MB, atau dimensi di luar 100x100..4000x4000 ditolak (AC-PRD-07, SEC-08) | Feature | `Master/ProductTest.php` | PASS |
| `TC-PRD-06` | Nilai negatif pada harga, stok minimum, atau stok awal ditolak validasi dan CHECK (AC-PRD-08, BR-02) | Feature | `Master/ProductTest.php` | PASS |
| `TC-PRD-07` | Field `current_stock` diabaikan bila dikirim via form/request; StockService penulis tunggal (AC-PRD-09, BR-06) | Feature | `Master/ProductTest.php` | PASS |
| `TC-STI-01` | Simpan/ubah draft barang masuk tidak mengubah stok (AC-STI-01, BR-07) | Feature | `Transaction/StockInTest.php` | PASS |
| `TC-STI-02` | Konfirmasi masuk: stok 10 + 20 = 30, movement stock_in tercatat (AC-STI-02, FR-STI-03) | Feature | `Transaction/StockInTest.php` | PASS |
| `TC-STI-03` | Rollback atomik total jika salah satu baris memuat barang nonaktif (AC-STI-03, BR-08) | Feature | `Transaction/StockInTest.php` | PASS |
| `TC-STI-04` | Transaksi confirmed ditolak diubah/diedit (HTTP 409 INVALID_STATE_TRANSITION, AC-STI-04, BR-12) | Feature | `Transaction/StockInTest.php` | PASS |
| `TC-STI-05` | Pembatalan confirmed masuk: stok kembali berkurang, movement stock_in_cancel dibuat (AC-STI-05, BR-13) | Feature | `Transaction/StockInTest.php` | PASS |
| `TC-STI-06` | Pembatalan masuk ditolak REVERSAL_INSUFFICIENT_STOCK jika sisa stok tidak mencukupi (AC-STI-06, BR-13) | Feature | `Transaction/StockInTest.php` | PASS |
| `TC-STI-07` | Supplier wajib diisi jika sumber barang masuk adalah pembelian (AC-STI-08, BR-22) | Feature | `Transaction/StockInTest.php` | PASS |
| `TC-STI-08` | Barang duplikat dalam satu dokumen transaksi masuk ditolak (AC-STI-10, BR-11) | Feature | `Transaction/StockInTest.php` | PASS |
| `TC-STI-09` | Nomor dokumen yang dibatalkan tidak dipakai ulang (AC-STI-11, BR-19) | Feature | `Transaction/StockInTest.php` | PASS |
| `TC-STI-10` | Pembatalan draft memerlukan alasan minimal 10 karakter, tanpa efek mutasi stok (AC-STI-12, BR-14) | Feature | `Transaction/StockInTest.php` | PASS |
| `TC-STO-01` | Konfirmasi keluar 5 dari 25: stok menjadi 20, movement stock_out tercatat (AC-STO-01, FR-STO-03) | Feature | `Transaction/StockOutTest.php` | PASS |
| `TC-STO-02` | Draft boleh > stok; saat konfirmasi ditolak INSUFFICIENT_STOCK tanpa perubahan parsial (AC-STO-02, BR-10) | Feature | `Transaction/StockOutTest.php` | PASS |
| `TC-STO-03` | Pembatalan confirmed keluar mengembalikan kuantitas stok gudang (AC-STO-04, FR-STO-05) | Feature | `Transaction/StockOutTest.php` | PASS |
| `TC-STO-04` | Draft lama yang memuat barang yang kemudian nonaktif gagal dikonfirmasi (AC-STO-05, BR-04) | Feature | `Transaction/StockOutTest.php` | PASS |
| `TC-STO-05` | Endpoint AJAX `/ajax/products/search` dan `/stock` mengembalikan data stok real-time (AC-STO-06, FR-STO-01) | Feature | `Transaction/StockOutTest.php` | PASS |
| `TC-STO-06` | Kegagalan database saat konfirmasi memicu rollback total tanpa mutasi yatim (AC-STO-07, BR-08) | Feature | `Transaction/StockOutTest.php` | PASS |
| `TC-STO-07` | Pembatalan draft keluar memerlukan alasan; SG bukan pembuat ditolak 404 (AC-STO-08, BR-14, BR-23) | Feature | `Transaction/StockOutTest.php` | PASS |
| `TC-ADJ-01` | Penyesuaian selisih -3 (50 -> 47): stok menjadi 47, adjustment & movement tercatat (AC-ADJ-01, FR-ADJ-01) | Feature | `Transaction/StockAdjustmentTest.php` | PASS |
| `TC-ADJ-02` | Penyesuaian selisih +2 (10 -> 12): stok menjadi 12 (AC-ADJ-02) | Feature | `Transaction/StockAdjustmentTest.php` | PASS |
| `TC-ADJ-03` | Hasil hitung fisik sama dengan stok sistem ditolak NO_DIFFERENCE (AC-ADJ-04, FR-ADJ-01) | Feature | `Transaction/StockAdjustmentTest.php` | PASS |
| `TC-ADJ-04` | Penyesuaian tidak memiliki rute edit/delete; SG dan MG ditolak 403 (AC-ADJ-05, AC-ADJ-06, BR-16) | Feature | `Transaction/StockAdjustmentTest.php` | PASS |
| `TC-CON-01` | Konfirmasi ganda pada dokumen sama secara paralel menghasilkan tepat satu sukses dan satu 409 (AC-STI-07) | Concurrency | `Transaction/ConcurrencyTest.php` | PASS |
| `TC-CON-02` | Dua dokumen berbeda menguras barang yang sama secara bersamaan: stok tidak pernah negatif (AC-STO-03, BR-09) | Concurrency | `Transaction/ConcurrencyTest.php` | PASS |
| `TC-CON-03` | Stok sistem berubah saat form opname dibuka: ditolak STOCK_CHANGED 409 (AC-ADJ-03, D-33) | Concurrency | `Transaction/ConcurrencyTest.php` | PASS |
| `CON-STRESS` | Uji stres konkurensi 50 iterasi transaksi locked berurutan di MySQL: 100% konsisten (PRD §16.2) | Concurrency | `Transaction/ConcurrencyTest.php` | PASS |
| `ARCH-STOCK` | Uji arsitektur: hanya StockService yang menulis current_stock di seluruh codebase (BR-06) | Arch | `Architecture/StockArchitectureTest.php` | PASS |
| `TC-DSH-01` | Dasbor bulanan hanya menghitung transaksi confirmed bulan ini (AC-DSH-01, FR-DSH-01) | Feature | `Dashboard/DashboardTest.php` | PASS |
| `TC-DSH-02` | Dasbor status stok: habis=1, minimum=1, tersedia=2 (AC-DSH-02, FR-DSH-01) | Feature | `Dashboard/DashboardTest.php` | PASS |
| `TC-DSH-03` | Dasbor SG dibatasi hanya transaksi sendiri, tanpa grafik global (AC-DSH-03, FR-DSH-05) | Feature | `Dashboard/DashboardTest.php` | PASS |
| `TC-DSH-04` | Dasbor tidak menjumlahkan kuantitas fisik lintas satuan berbeda (AC-DSH-04, BR-27) | Feature | `Dashboard/DashboardTest.php` | PASS |
| `TC-STK-01` | Batas status stok min=5 (0->habis, 3->min, 5->min, 6->aman) (AC-STK-01, BR-18) | Feature | `Stock/StockMonitoringTest.php` | PASS |
| `TC-STK-02` | Batas status stok min=0 (0->habis, >0->aman) (AC-STK-02, BR-18) | Feature | `Stock/StockMonitoringTest.php` | PASS |
| `TC-STK-04` | Lonceng notifikasi navbar live menampilkan jumlah aktif habis+minimum (AC-STK-04, FR-NTF-01) | Feature | `Stock/StockMonitoringTest.php` | PASS |
| `TC-STK-05` | Perubahan min_stock langsung mengubah status stok tanpa movement (AC-STK-06, BR-29) | Feature | `Stock/StockMonitoringTest.php` | PASS |
| `TC-RPT-01` | Laporan Barang Masuk hanya memuat transaksi confirmed (AC-RPT-01, BR-21) | Feature | `Report/ReportTest.php` | PASS |
| `TC-RPT-02` | Laporan Pergerakan Stok memuat movement asli dan pembalik (AC-RPT-02, FR-RPT-05) | Feature | `Report/ReportTest.php` | PASS |
| `TC-RPT-03` | Query Object menghasilkan data konsisten antara paginate() dan get() (AC-RPT-03, BR-31) | Feature | `Report/ReportTest.php` | PASS |
| `TC-RPT-04` | Laporan persediaan as_of_date menghitung saldo dari movement lampau (AC-RPT-04, FR-RPT-01) | Feature | `Report/ReportTest.php` | PASS |
| `PERF-NPLUS1`| Audit N+1: preventLazyLoading tidak memicu exception pada dasbor, stok, mutasi, laporan | Feature | `Performance/PerformanceAndNPlusOneTest.php` | PASS |

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
