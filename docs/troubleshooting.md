# Panduan Troubleshooting — StockHub

| Atribut | Nilai |
|---|---|
| Tujuan | Menyediakan panduan diagnosis gejala masalah operasional umum, akar penyebab, langkah solusi, dan pencegahan. |
| Cakupan | Kendala login, deadlock transaksi, selisih rekonsiliasi, batas ekspor, symlink storage, dan kesalahan operasional stok. |
| Audiens | Administrator Sistem, IT Support, Developer |
| Status | [SUDAH DIIMPLEMENTASI] (Tahap 1–6: Transaksi, Concurrency, Locking, Master Data, Ekspor, dan Rekonsiliasi) |
| Versi aplikasi | 1.0.0 |
| Terakhir diperbarui | 2026-11-08 |
| Pemilik | Tech Lead |
| Terkait | `prd.md` §15, §17; `docs/deployment.md`; `docs/api.md` |

---

## 1. Matriks Troubleshooting Masalah Operasional

| Gejala Masalah | Kemungkinan Penyebab | Langkah Diagnosis | Solusi & Tindakan | Tindakan Pencegahan |
|---|---|---|---|---|
| Konfirmasi keluar ditolak `INSUFFICIENT_STOCK` (HTTP 422) | Kuantitas barang yang diminta melebihi sisa fisik `current_stock` saat ini | Periksa sisa stok barang di tabel `products` atau endpoint `/ajax/products/{id}/stock` | Sesuaikan kuantitas keluar sesuai sisa stok fisik, atau lakukan konfirmasi penerimaan barang masuk terlebih dahulu | Perhatikan indikator stok kuning saat mengisi form draft barang keluar |
| Pembatalan barang masuk ditolak `REVERSAL_INSUFFICIENT_STOCK` (HTTP 422) | Barang masuk yang ingin dibatalkan sudah terlanjur dikeluarkan oleh transaksi lain sehingga sisa stok < kuantitas pembatalan | Bandingkan `current_stock` barang dengan kuantitas yang ada pada baris dokumen masuk | Jangan membatalkan dokumen masuk jika barang sudah keluar. Bila terjadi salah catat, gunakan menu **Penyesuaian Stok Opname** | Pastikan verifikasi barang fisik sebelum mengonfirmasi barang masuk |
| Penyesuaian stok ditolak `STOCK_CHANGED` (HTTP 409) | Terjadi transaksi barang masuk/keluar lain saat form penyesuaian opname sedang dibuka (D-33) | Periksa apakah ada mutasi baru pada barang tersebut di menu Riwayat Stok | Muat ulang (*refresh*) form penyesuaian untuk membaca stok sistem terkini, periksa selisih baru, lalu simpan ulang | Lakukan opname pada saat jam operasional gudang sedang tenang |
| Penyesuaian stok ditolak `NO_DIFFERENCE` (HTTP 422) | Angka fisik yang diisi sama persis dengan angka stok sistem | Periksa kolom hasil hitung fisik dan kolom stok sistem | Tidak diperlukan penyesuaian jika stok fisik cocok dengan sistem. Batalkan formulir | Pastikan hanya membuat dokumen penyesuaian jika benar-benar ada selisih opname fisik |
| Transaksi gagal dengan kode `LOCK_TIMEOUT` (HTTP 503) | Deadlock atau antrean lock baris produk melebihi timeout MySQL setelah 3 kali retry | Periksa query MySQL yang sedang berjalan (`SHOW FULL PROCESSLIST`) | Ulangi operasi konfirmasi transaksi beberapa saat kemudian (data aman dalam transaksi) | Pastikan urutan lock ID produk naik (`ORDER BY id ASC`) dipertahankan pada seluruh operasi batch |
| Transaksi ditolak `INVALID_STATE_TRANSITION` (HTTP 409) | Dokumen sudah dikonfirmasi/dibatalkan oleh staf lain, atau mencoba mengedit dokumen confirmed | Periksa kolom `status` pada dokumen di basis data | Muat ulang halaman detail transaksi untuk melihat status terkini dokumen | Hindari menekan tombol konfirmasi ganda (*double-click*); sistem memblokir klik ganda di frontend |
| Nonaktif barang ditolak | Barang masih memiliki sisa stok (`current_stock > 0`) | Periksa kolom `current_stock` pada master barang | Barang hanya dapat dinonaktifkan jika stok bernilai 0 (BR-04). Keluarkan atau sesuaikan sisa stok terlebih dahulu | Habiskan stok terlebih dahulu sebelum mendepresiasi produk |
| Nonaktif kategori / satuan ditolak | Masih digunakan oleh barang yang berstatus aktif (BR-04) | Periksa jumlah barang aktif yang terhubung ke kategori/satuan tersebut | Pindahkan barang-barang terkait ke kategori/satuan lain, atau nonaktifkan barangnya terlebih dahulu | Audit relasi master barang sebelum menonaktifkan unit/kategori |
| Ekspor gagal dengan kode `EXPORT_LIMIT_EXCEEDED` (HTTP 422) | Jumlah baris data yang diekspor melebihi batas sistem (PDF > 5.000 baris atau Excel > 100.000 baris) | Periksa rentang tanggal, kategori, atau filter yang dipilih | Persempit filter laporan (misal: persempit rentang tanggal atau pilih kategori/supplier tertentu) | Terapkan filter spesifik sebelum mengklik tombol ekspor berkas |
| Rekonsiliasi mendeteksi selisih integritas stok (`stock-integrity.log`) | Saldo `current_stock` tidak cocok dengan akumulasi mutasi, atau ada rantai mutasi yang terputus (misal: modifikasi langsung database di luar `StockService`) | 1. Buka berkas `storage/logs/stock-integrity.log` untuk melihat kode barang dan rincian selisih.<br>2. Buka menu **Log Aktivitas** (`/activity-logs`) untuk memeriksa aktivitas modifikasi terkait.<br>3. Buka **Kartu Stok** barang bersangkutan (`/products/{id}`) untuk melihat kronologi mutasi.<br>4. Lakukan penghitungan fisik nyata di gudang | Jangan pernah mengubah database secara manual! Buat dokumen **Penyesuaian Stok Opname** (`/adjustments/create`) untuk menyelaraskan catatan sistem dengan stok fisik nyata hasil investigasi | Pastikan database produksi tidak memberi hak akses langsung selain aplikasi, dan tegakkan aturan BR-06 |
| Foto barang tidak muncul di browser | Tautan simbolik direktori storage belum dibuat | Periksa keberadaan tautan folder `public/storage` | Jalankan perintah `php artisan storage:link` | Pastikan `storage:link` dijalankan saat setup awal |
| Login mengembalikan HTTP 429 (*Too Many Requests*) | Melebihi batas 5 kali percobaan gagal dalam 1 menit | Periksa IP dan email di tabel `activity_logs` | Tunggu hingga hitung mundur throttle selesai (maks 60 detik) | Gunakan kredensial akun yang valid |
| Halaman kedaluwarsa HTTP 419 (*Page Expired*) | Token CSRF kedaluwarsa karena form dibiarkan terbuka lama | Periksa masa aktif sesi di `config/session.php` | Muat ulang (*refresh*) halaman browser sebelum menekan tombol submit | Perpanjang session lifetime jika diperlukan |
