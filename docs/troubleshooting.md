# Panduan Troubleshooting — StockHub

| Atribut | Nilai |
|---|---|
| Tujuan | Menyediakan panduan diagnosis gejala masalah operasional umum, akar penyebab, langkah solusi, dan pencegahan. |
| Cakupan | Kendala login, deadlock transaksi, selisih rekonsiliasi, batas ekspor, symlink storage, dan konfigurasi lingkungan. |
| Audiens | Administrator Sistem, IT Support, Developer |
| Status | [BELUM DIIMPLEMENTASI] (Kerangka Dokumentasi Tahap 0) |
| Versi aplikasi | 1.0.0 |
| Terakhir diperbarui | 2026-11-06 |
| Pemilik | Tech Lead |
| Terkait | `prd.md` §15, §17; `docs/deployment.md`; `docs/api.md` |

---

## 1. Matriks Troubleshooting Masalah Operasional

| Gejala Masalah | Kemungkinan Penyebab | Langkah Diagnosis | Solusi & Tindakan | Tindakan Pencegahan |
|---|---|---|---|---|
| Login mengembalikan HTTP 429 (*Too Many Requests*) | Melebihi batas 5 kali percobaan gagal dalam 1 menit | Periksa IP dan email di tabel `activity_logs` | Tunggu hingga hitung mundur throttle selesai (maks 60 detik) atau reset cache throttle | Jangan mencoba brute force; gunakan kredensial yang valid |
| Halaman kedaluwarsa HTTP 419 (*Page Expired*) | Token CSRF kedaluwarsa karena form dibiarkan terbuka lama | Periksa masa aktif sesi di `config/session.php` | Muat ulang (*refresh*) halaman browser sebelum menekan tombol submit | Perpanjang session lifetime jika diperlukan |
| Transaksi gagal dengan kode `LOCK_TIMEOUT` (HTTP 503) | Deadlock atau antrean lock baris produk melebihi timeout | Periksa query MySQL yang sedang berjalan (`SHOW FULL PROCESSLIST`) | Ulangi operasi konfirmasi transaksi beberapa saat kemudian | Pertahankan urutan lock ID produk naik (`ORDER BY id ASC`) |
| Rekonsiliasi stok menemukan selisih (`stock-integrity`) | Ada manipulasi data langsung pada tabel `products` di luar `StockService` | Jalankan `php artisan stockhub:reconcile` dan periksa log integrity | Lakukan investigasi log mutasi dan posting transaksi penyesuaian | Pastikan hak akses DB produksi tidak mengizinkan modifikasi manual |
| Ekspor PDF/Excel gagal dengan `EXPORT_LIMIT_EXCEEDED` | Jumlah baris data melebihi batas (PDF > 5.000, Excel > 100.000) | Periksa rentang tanggal dan filter laporan yang aktif | Persempit filter periode laporan atau pilih filter kategori tertentu | Gunakan filter yang lebih spesifik |
| Foto barang tidak muncul di browser | Tautan simbolik direktori storage belum dibuat | Periksa keberadaan folder `public/storage` | Jalankan perintah `php artisan storage:link` | Tambahkan langkah `storage:link` pada prosedur deployment rilis |
| Error HTTP 500 dengan ID korelasi | Terjadi *unhandled exception* pada level server | Cari kode ID korelasi pada file log `storage/logs/laravel.log` | Identifikasi stack trace dan laporkan ke tim teknis | Periksa log secara berkala |
| Waktu transaksi tidak sesuai zona lokal | Konfigurasi timezone belum `Asia/Jakarta` | Periksa nilai `date_default_timezone_get()` via `php artisan tinker` | Pastikan `APP_TIMEZONE=Asia/Jakarta` di file `.env` | Gunakan waktu terstandar pada setiap server |
