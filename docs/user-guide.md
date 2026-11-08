# Panduan Pengguna — StockHub

| Atribut | Nilai |
|---|---|
| Tujuan | Memberikan instruksi operasional penggunaan aplikasi StockHub per peran pengguna dan per modul. |
| Cakupan | Panduan antarmuka web untuk peran Super Admin, Admin Gudang, Staff Gudang, dan Manajer. |
| Audiens | Pengguna Akhir, Administrator Sistem, QA Tester |
| Status | [SUDAH DIIMPLEMENTASI] (Tahap 1–4: Auth, Akun, Master Data, dan Transaksi Stok) |
| Versi aplikasi | 1.0.0 |
| Terakhir diperbarui | 2026-11-07 |
| Pemilik | QA Lead & Product Owner |
| Terkait | `prd.md` §5, §8, §13; `docs/authentication.md` |

---

## 1. Masuk ke Sistem (Login)

1. Buka URL aplikasi pada peramban web (`http://localhost/login`).
2. Masukkan alamat email terdaftar dan kata sandi Anda.
3. Klik tombol **Masuk ke Sistem**.
4. **Catatan Keamanan:**
   - Tidak tersedia registrasi akun mandiri. Akun hanya didaftarkan oleh Super Admin.
   - Percobaan masuk dibatasi maksimal 5 kali per menit. Percobaan ke-6 akan ditahan selama 60 detik dengan hitung mundur di layar.
   - Jika akun Anda berstatus nonaktif, sistem akan menampilkan pesan kesalahan yang sama demi alasan keamanan.

---

## 2. Penggantian Kata Sandi Sementara (Login Pertama)

Bagi pengguna baru atau akun yang kata sandinya baru direset oleh Super Admin:
1. Setelah login berhasil, sistem secara otomatis mengarahkan Anda ke layar **Ganti Kata Sandi Sementara**.
2. Masukkan kata sandi baru yang memenuhi kriteria:
   - Minimal 10 karakter.
   - Mengandung minimal satu huruf besar (`A-Z`).
   - Mengandung minimal satu huruf kecil (`a-z`).
   - Mengandung minimal satu angka (`0-9`).
   - Tidak boleh sama dengan alamat email Anda.
3. Ulangi kata sandi baru pada kolom konfirmasi.
4. Klik **Simpan Kata Sandi Baru**. Anda akan langsung diarahkan ke halaman Ringkasan Dasbor.

---

## 3. Pengelolaan Profil dan Kata Sandi Mandiri

Setiap pengguna yang telah masuk dapat mengelola profil pribadi:
1. Klik nama pengguna pada pojok kanan atas bilah navigasi, lalu pilih **Profil Saya** (atau buka `/profile`).
2. **Informasi Profil:** Ubah nama lengkap atau alamat email, lalu klik **Simpan Profil**.
3. **Ubah Kata Sandi:**
   - Masukkan kata sandi Anda saat ini.
   - Masukkan kata sandi baru dan konfirmasinya.
   - Klik **Simpan Kata Sandi**.
   - *Penting:* Tindakan ini secara otomatis mengakhiri (*logout*) sesi aktif Anda pada perangkat lain.

---

## 4. Manajemen Pengguna (Khusus Super Admin)

Super Admin memiliki menu **Data Pengguna** (`/users`) pada bilah sisi navigasi:

### 4.1 Melihat dan Memfilter Daftar Pengguna
- **Pencarian:** Masukkan nama atau email pada kotak pencarian untuk menemukan pengguna.
- **Filter:** Saring daftar berdasarkan Peran (*Super Admin*, *Admin Gudang*, *Staff Gudang*, *Manajer*) dan Status (*Aktif*, *Nonaktif*).

### 4.2 Menambah Pengguna Baru
1. Klik tombol **Tambah Pengguna** di pojok kanan atas.
2. Isi nama lengkap dan alamat email unik.
3. Pilih salah satu peran yang sesuai.
4. Masukkan kata sandi sementara yang kuat.
5. Klik **Simpan Pengguna**. Pengguna akan otomatis berstatus wajib mengganti kata sandi saat pertama kali login.

### 4.3 Mengubah Data Pengguna
1. Klik tombol **Ubah** pada baris pengguna yang dituju.
2. Ubah nama, email, atau peran sesuai kebutuhan.
3. Klik **Simpan Perubahan**.
4. *Batasan (BR-24):* Pengguna tidak dapat mengubah peran akunnya sendiri. Super Admin terakhir yang aktif tidak dapat diturunkan perannya.

### 4.4 Mengaktifkan atau Menonaktifkan Akun
1. Klik tombol **Nonaktifkan** pada pengguna yang bersangkutan.
2. Konfirmasi tindakan pada dialog peramban. Sesi aktif pengguna tersebut akan langsung diputus.
3. Untuk mengaktifkan kembali, klik tombol **Aktifkan**.
4. *Batasan (BR-24):* Anda tidak dapat menonaktifkan akun Anda sendiri, dan Super Admin aktif terakhir tidak dapat dinonaktifkan.

### 4.5 Reset Kata Sandi Pengguna
1. Klik tombol **Reset Password** pada baris pengguna.
2. Masukkan kata sandi sementara baru pada jendela sembul (*modal*).
3. Klik **Tetapkan Password**. Seluruh sesi aktif pengguna tersebut akan diputus dan ia wajib mengganti password saat login kembali.

---

## 5. Melihat Matriks Peran dan Hak Akses

Super Admin dapat membuka menu **Peran & Hak Akses** (`/roles`) untuk melihat tabel referensi 26 permission terhadap 4 peran sistem secara *read-only*.

---

## 6. Manajemen Kategori Barang (`/categories`)

Hak akses: Super Admin dan Admin Gudang (kelola); Staff Gudang dan Manajer (hanya lihat).

### 6.1 Melihat Daftar & Filter Kategori
- **Pencarian:** Cari berdasarkan nama kategori pada kotak pencarian.
- **Filter Status:** Tampilkan semua, kategori aktif, atau nonaktif.
- **Kolom Jumlah Barang:** Menampilkan banyaknya barang yang terhubung ke kategori tersebut.

### 6.2 Menambah & Mengubah Kategori
1. Klik **Tambah Kategori** atau klik **Ubah** pada baris kategori.
2. Masukkan nama kategori (3–100 karakter, unik case-insensitive) dan deskripsi opsional.
3. Klik **Simpan**.

### 6.3 Mengaktifkan / Menonaktifkan Kategori
1. Klik tombol **Nonaktifkan** atau **Aktifkan** pada baris kategori.
2. Konfirmasi aksi pada dialog sembul.
3. **Aturan Pembatasan (BR-04):** Penonaktifan kategori akan **ditolak** oleh sistem apabila kategori tersebut masih digunakan oleh barang aktif, lengkap dengan pesan jumlah barang terkait. Kategori nonaktif tidak akan muncul pada pilihan pembuatan barang baru.

---

## 7. Manajemen Satuan Barang (`/units`)

Hak akses: Super Admin dan Admin Gudang (kelola); Staff Gudang dan Manajer (hanya lihat).

### 7.1 Format Kuantitas (Bulat vs Desimal)
- **Bilangan Bulat:** Kuantitas transaksi/stok tidak boleh memiliki pecahan desimal (contoh: pcs, unit, box, karton, pack).
- **Desimal:** Kuantitas dapat memiliki angka desimal hingga maksimal 3 digit (contoh: kg, gram, liter, meter).
- **Penguncian Permanen (BR-25):** Pengaturan desimal satuan terkunci permanen setelah satuan tersebut digunakan oleh master barang.

### 7.2 Menambah & Mengubah Satuan
1. Klik **Tambah Satuan** atau **Ubah** pada baris yang dituju.
2. Masukkan nama satuan (2–50 karakter) dan simbol/singkatan unik (1–10 karakter).
3. Tentukan aturan kuantitas (hanya saat membuat, atau sebelum dipakai barang).
4. Klik **Simpan**.

### 7.3 Status Satuan
Satuan tidak dapat dinonaktifkan jika masih digunakan oleh barang aktif mana pun (BR-04).

---

## 8. Manajemen Supplier (`/suppliers`)

Hak akses: Super Admin dan Admin Gudang (kelola); Staff Gudang dan Manajer (hanya lihat).

### 8.1 Kode Supplier Otomatis & Terurut (BR-05, BR-19)
- Kode supplier dibangkitkan otomatis oleh sistem (`SUP-0001`, `SUP-0002` dst.) dengan jaminan keunikan konkurensi.
- Kode supplier bersifat tetap (*immutable*) dan tidak dapat diedit setelah disimpan.

### 8.2 Menambah & Mengubah Data Supplier
1. Klik **Tambah Supplier** untuk membuka formulir.
2. Isi nama supplier/perusahaan, nama kontak PIC, nomor telepon, email, dan alamat.
3. Klik **Simpan**.

### 8.3 Detail Supplier & Riwayat Transaksi (FR-SUP-04)
Klik tombol **Detail** pada supplier untuk melihat data lengkap serta kartu riwayat 10 transaksi barang masuk terakhir dari supplier tersebut.

### 8.4 Penonaktifan Supplier (BR-04)
Supplier dapat dinonaktifkan kapan saja. Supplier nonaktif tidak akan muncul di form barang masuk baru atau form barang baru, namun nama supplier tetap terjaga utuh pada seluruh laporan dan riwayat historis.

---

## 9. Manajemen Master Barang (`/products`)

Hak akses: Super Admin dan Admin Gudang (kelola); Staff Gudang dan Manajer (hanya lihat).

### 9.1 Daftar & Filter Pencarian Barang (FR-PRD-01)
- **Pencarian:** Cari berdasarkan kode atau nama barang.
- **Filter Multi-kriteria:** Kategori, status keaktifan, dan status stok (Aman, Minimum, Habis sesuai BR-18).
- **Pengurutan:** Urutkan berdasarkan tanggal dibuat, nama barang, atau kuantitas stok saat ini.
- **Paginasi:** Pilihan 15, 25, 50, atau 100 baris per halaman.

### 9.2 Menambah Barang Baru & Saldo Awal (FR-PRD-02, BR-17)
1. Klik **Tambah Barang Baru**.
2. **Kode Barang:** Boleh dikosongkan untuk penomoran otomatis (`BRG-NNNN`), atau diisi manual dengan format alfanumerik 3–30 karakter (contoh: `SKU.001-A`). Kode akan otomatis di-uppercase dan bersifat *immutable*.
3. **Kategori & Satuan:** Wajib memilih kategori dan satuan yang berstatus aktif.
4. **Stok Minimum:** Tentukan batas minimum untuk memicu peringatan stok menipis.
5. **Saldo Awal (`opening_stock`):**
   - Bila diisi `> 0`, sistem akan membuat mutasi saldo awal (`opening_balance`) secara aman melalui `StockService` dalam transaksi database yang sama (BR-17).
   - Bila diisi `0`, tidak ada mutasi stok yang dibuat.
   - Kolom `current_stock` dikelola eksklusif oleh sistem dan bukan merupakan input formulir (BR-06).
6. **Upload Foto (FR-PRD-06, SEC-08):** Unggah foto produk (format JPG/PNG/WebP, maks 2 MB, dimensi 100x100 s.d. 4000x4000 px). Sistem memverifikasi MIME sebenarnya dan menolak file SVG atau manipulasi ekstensi.
7. Klik **Simpan Barang**.

### 9.3 Mengubah Barang & Penguncian Satuan (BR-25)
1. Klik **Ubah** pada baris barang.
2. Perbarui nama, kategori, stok minimum, atau ganti/hapus foto.
3. *Aturan Satuan:* Pilihan satuan terkunci dan tidak dapat diubah apabila barang tersebut sudah memiliki riwayat mutasi stok.

### 9.4 Penonaktifan Barang (BR-04)
Barang **hanya dapat dinonaktifkan jika stok saat ini bernilai 0**. Jika stok masih tersedia (>0), permintaan penonaktifan akan ditolak oleh sistem dengan menampilkan jumlah stok yang masih tersisa.

### 9.5 Kebijakan Kerahasiaan Harga (BR-26)
Pengguna dengan peran **Staff Gudang (SG)** tidak memiliki izin `products.view-price`. Kolom harga beli dan harga jual disembunyikan sepenuhnya dari layar Staff Gudang dan tidak pernah disertakan dalam respon server.

---

## 10. Transaksi Barang Masuk (`/stock-ins`)

Hak akses: Super Admin, Admin Gudang, Staff Gudang (buat draft, ubah draft miliknya, konfirmasi); Manajer (hanya lihat).

### 10.1 Alur Pembuatan Draft Barang Masuk (FR-STI-01)
1. Buka menu **Barang Masuk**, lalu klik **Buat Barang Masuk**.
2. **Tanggal Transaksi:** Tentukan tanggal bisnis penerimaan barang (maksimal 7 hari lampau sesuai batas `max_backdate_days`).
3. **Sumber Masuk:** Pilih sumber (`pembelian`, `internal`, `retur`, atau `lainnya`). Bila sumber adalah pembelian (`purchase`), kolom **Supplier** wajib dipilih.
4. **Item Barang:** Tambahkan 1 hingga 100 baris barang. Sistem secara otomatis mendeteksi satuan dan aturan desimal.
5. Klik **Simpan Draft Masuk**. Dokumen tersimpan dengan nomor urut `IN-YYYYMM-NNNN` berstatus `draft`.
6. **Catatan Stok (BR-07):** Pembuatan dan pengubahan draft tidak memengaruhi kuantitas stok persediaan.

### 10.2 Mengonfirmasi Barang Masuk (FR-STI-03)
1. Buka detail dokumen draft barang masuk.
2. Klik tombol **Konfirmasi Masuk**, lalu setujui kotak dialog konfirmasi.
3. Sistem memproses penambahan stok secara atomik melalui `StockService`, memperbarui status menjadi `confirmed`, mencatat mutasi riwayat `stock_in`, dan mencatat log aktivitas.
4. Dokumen berstatus `confirmed` terkunci dan tidak dapat diedit lagi.

### 10.3 Pembatalan Dokumen Barang Masuk (FR-STI-04, FR-STI-05)
- **Membatalkan Draft:** Dapat dilakukan oleh pembuat dokumen atau SA/AG dengan memasukkan alasan pembatalan minimal 10 karakter. Dokumen menjadi `cancelled` tanpa memengaruhi stok.
- **Membatalkan Confirmed (Reversal):** Hanya dapat dilakukan oleh Super Admin atau Admin Gudang. Sistem akan mengurangi kembali stok yang telah masuk sebelumnya via mutasi pembalik `stock_in_cancel`. Pembatalan ditolak apabila sisa stok saat ini lebih kecil dari kuantitas yang dibatalkan.

---

## 11. Transaksi Barang Keluar (`/stock-outs`)

Hak akses: Super Admin, Admin Gudang, Staff Gudang (buat draft, ubah draft miliknya, konfirmasi); Manajer (hanya lihat).

### 11.1 Alur Pembuatan Draft Barang Keluar (FR-STO-01)
1. Buka menu **Barang Keluar**, lalu klik **Buat Barang Keluar**.
2. **Peruntukan & Tujuan:** Pilih peruntukan (`pemakaian`, `penjualan`, `pengiriman`, dll.) dan isi tujuan penerima barang (3–150 karakter).
3. **Item Barang:** Pilih barang. Antarmuka akan menampilkan sisa stok yang tersedia secara real-time via AJAX.
4. **Peringatan Stok Kurang (BR-10):** Jika kuantitas yang dimasukkan melebihi stok yang ada, baris akan ditandai dengan peringatan kuning, namun penyimpanan draft tetap diizinkan.
5. Klik **Simpan Draft Keluar**. Nomor dokumen `OUT-YYYYMM-NNNN` diterbitkan berstatus `draft`.

### 11.2 Mengonfirmasi Barang Keluar (FR-STO-03)
1. Buka detail dokumen draft barang keluar.
2. Klik tombol **Konfirmasi Keluar**.
3. Sistem mengunci baris produk di database dan memverifikasi ulang kecukupan stok saat ini.
4. **Pencegahan Overselling:** Jika ada satu saja item barang yang stoknya tidak mencukupi, konfirmasi akan **ditolak secara utuh** dengan pesan kesalahan per baris (`INSUFFICIENT_STOCK`). Tidak ada pemotongan stok parsial.
5. Jika seluruh stok mencukupi, stok akan dikurangkan, status menjadi `confirmed`, dan mutasi `stock_out` tercatat.

### 11.3 Pembatalan Dokumen Barang Keluar
- **Membatalkan Draft:** Status menjadi `cancelled` tanpa ada mutasi stok.
- **Membatalkan Confirmed:** Hanya oleh SA/AG; sistem memposting mutasi pembalik `stock_out_cancel` dan mengembalikan kuantitas barang ke dalam stok persediaan gudang.

---

## 12. Penyesuaian Stok Opname (`/adjustments`)

Hak akses: Khusus Super Admin dan Admin Gudang (Staff Gudang dan Manajer ditolak HTTP 403).

### 12.1 Kapan Menggunakan Penyesuaian?
Digunakan saat hasil stok opname fisik di gudang berbeda dengan catatan sistem (misal: barang rusak, hilang, salah hitung saat masuk, atau barang lebih ditemukan).

### 12.2 Prosedur Input Penyesuaian (FR-ADJ-01, BR-16)
1. Buka menu **Penyesuaian** pada bilah sisi, lalu klik **Penyesuaian Stok Baru**.
2. Pilih barang yang disesuaikan. Sistem akan otomatis menampilkan stok sistem saat ini.
3. Masukkan angka hasil hitung fisik pada kolom **Hasil Hitung Fisik**. Sistem secara otomatis menghitung selisih (`Fisik - Sistem`).
4. Pilih alasan selisih (rusak, hilang, salah hitung, kadaluwarsa, ditemukan, dll.).
5. Isi **Keterangan Detail Investigasi** minimal 10 karakter.
6. Klik **Posting Penyesuaian**, lalu konfirmasi pada dialog sembul.
7. Dokumen langsung berstatus **Final** (nomor `ADJ-YYYYMM-NNNN`) dan stok sistem langsung diperbarui sesuai angka fisik melalui `StockService`. Dokumen penyesuaian tidak dapat diedit maupun dibatalkan.


