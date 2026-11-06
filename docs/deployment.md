# Panduan Deployment dan Operasional — StockHub

| Atribut | Nilai |
|---|---|
| Tujuan | Menjelaskan konfigurasi lingkungan server, build aset, prosedur rilis, migrasi, rollback, dan backup database. |
| Cakupan | Deployment pada server Linux Ubuntu LTS, Nginx, PHP-FPM, MySQL 8.0/8.4 LTS, dan cron scheduler. |
| Audiens | DevOps Engineer, System Administrator, Tech Lead |
| Status | [BELUM DIIMPLEMENTASI] (Kerangka Dokumentasi Tahap 0) |
| Versi aplikasi | 1.0.0 |
| Terakhir diperbarui | 2026-11-06 |
| Pemilik | Tech Lead |
| Terkait | `prd.md` §17; `docs/architecture.md`; `docs/database.md` |

---

## 1. Spesifikasi Server Produksi (A-05)
- Sistem Operasi: Linux (Ubuntu LTS).
- Web Server: Nginx (HTTPS dengan sertifikat TLS terpasang).
- PHP Runtime: PHP-FPM 8.2+ dengan modul pendukung (`pdo_mysql`, `bcmath`, `fileinfo`, `gd`, `zip`, `xml`, `mbstring`, `opcache`).
- Database: MySQL Server 8.0.x atau 8.4 LTS dengan engine InnoDB.
- Background Worker: Cron scheduler Laravel.
- Resource Minimum: 2 vCPU, 4 GB RAM, 40 GB SSD.

## 2. Prosedur Rilis Produksi `[DIRENCANAKAN]`
1. Pastikan seluruh pipeline CI GitHub Actions berstatus hijau.
2. Build aset produksi di CI: `npm ci && npm run build` dan `composer install --no-dev --optimize-autoloader`.
3. Buat backup database penuh sebelum menjalankan migrasi: `mysqldump --single-transaction`.
4. Aktifkan maintenance mode: `php artisan down --retry=60`.
5. Jalankan migrasi basis data: `php artisan migrate --force`.
6. Optimalkan cache sistem: `php artisan config:cache && php artisan route:cache && php artisan view:cache && php artisan event:cache`.
7. Reload layanan PHP-FPM untuk mereset opcache.
8. Nonaktifkan mode pemeliharaan: `php artisan up`.
9. Lakukan uji verifikasi operasional (*smoke test*).

## 3. Penjadwalan Cron Scheduler
Tambahkan entri crontab sistem:
```bash
* * * * * cd /path/to/stockhub && php artisan schedule:run >> /dev/null 2>&1
```
Cron ini menjalankan perintah rekonsiliasi harian `php artisan stockhub:reconcile` pada pukul 01:00 WIB.

## 4. Strategi Backup dan Pemulihan (Disaster Recovery)
- Backup harian penuh menggunakan `mysqldump` dengan flag `--single-transaction`.
- Retensi backup: 14 hari harian dan 8 minggu mingguan.
- File backup dienkripsi dan disimpan di lokasi luar server (*off-site storage*).
