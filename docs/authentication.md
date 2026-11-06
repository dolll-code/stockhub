# Autentikasi dan Hak Akses — StockHub

| Atribut | Nilai |
|---|---|
| Tujuan | Mendokumentasikan mekanisme autentikasi, manajemen sesi, kebijakan kata sandi, penegakan otorisasi peran, dan audit aktivitas. |
| Cakupan | Alur login sesi, proteksi brute force, kontrol hak akses berbasis Spatie Permission, Policy, dan ActivityLogger. |
| Audiens | Developer, QA Engineer, Administrator Sistem, Security Auditor |
| Status | [SUDAH DIIMPLEMENTASI] (Tahap 2 — Autentikasi & Akun) |
| Versi aplikasi | 1.0.0 |
| Terakhir diperbarui | 2026-11-06 |
| Pemilik | Backend Lead |
| Terkait | `prd.md` §4, §5.1, §5.2; `docs/api.md`; `docs/business-rules.md` |

---

## 1. Mekanisme Autentikasi Berbasis Sesi

StockHub adalah aplikasi web *server-rendered* yang mengandalkan autentikasi berbasis sesi (cookie web).
- **Kredensial:** Alamat email (case-insensitive, di-trim dan di-lowercase) dan kata sandi.
- **Tanpa Registrasi Publik (D-31, FR-AUTH-01):** Rute pendaftaran mandiri dinonaktifkan (`GET /register` dan `POST /register` menghasilkan HTTP 404). Akun pengguna hanya dapat dibuat oleh Super Admin.
- **Tanpa Fitur Remember-Me (A-08):** Opsi "ingat saya" ditiadakan; flag remember selalu disetel ke `false` pada backend.
- **Manajemen Sesi:**
  - Driver sesi menggunakan basis data (`SESSION_DRIVER=database`).
  - Masa aktif sesi tanpa aktivitas (*idle lifetime*) ditetapkan 120 menit (`SESSION_LIFETIME=120`).
  - Regenerasi ID sesi otomatis dilakukan pada setiap keberhasilan login untuk mencegah serangan *session fixation* (FR-AUTH-02, SEC-03).
  - Logout wajib menggunakan method `POST /logout`, yang menghancurkan sesi di server dan meregenerasi token CSRF (FR-AUTH-06).

---

## 2. Kebijakan Keamanan dan Kata Sandi (Password Policy)

Sesuai ketentuan PRD §5.1 dan aturan validasi `App\Rules\StockHubPassword`:
1. **Panjang Minimum:** Minimal 10 karakter.
2. **Komposisi Karakter:** Wajib mengandung minimal satu huruf besar (`A-Z`), satu huruf kecil (`a-z`), dan satu angka (`0-9`).
3. **Pencegahan Identik Email:** Kata sandi tidak boleh sama dengan alamat email pengguna (perbandingan case-insensitive).
4. **Enkripsi Hash:** Kata sandi disimpan menggunakan algoritma Bcrypt dengan cost factor minimal 12 (`BCRYPT_ROUNDS=12`).
5. **Kerahasiaan Mutlak (SEC-01):** Kata sandi tidak pernah disimpan dalam plain text, tidak pernah dicatat pada log aktivitas, tidak pernah dimasukkan ke dalam respon HTTP, dan tidak pernah dipertahankan pada old input form.

---

## 3. Pembatasan Percobaan Login (Rate Limiting / Throttle)

- **Batas Percobaan:** Maksimal 5 kali percobaan gagal per menit per kombinasi alamat email + alamat IP (FR-AUTH-04).
- **Penolakan HTTP 429:** Pada percobaan ke-6, sistem langsung menolak dengan status HTTP 429 (*Too Many Requests*) disertai header `Retry-After: <detik>`.
- **Tampilan Hitung Mundur:** Halaman 429 (`resources/views/errors/429.blade.php`) menampilkan penghitung mundur (*countdown*) waktu tunggu berbasis Alpine.js.
- **Pesan Kegagalan Generik (FR-AUTH-03, AC-AUTH-02):**
  Untuk mencegah serangan enumerasi pengguna (*account enumeration*), pesan kegagalan login selalu identik untuk kondisi:
  1. Alamat email tidak terdaftar.
  2. Kata sandi salah.
  3. Akun berstatus nonaktif (`is_active = false`).
  *Pesan standar:* `"Email atau kata sandi yang Anda masukkan salah."`
- **Pencatatan Log Kegagalan (D-32, BR-30):**
  Setiap kegagalan dicatat ke tabel `activity_logs` dengan aksi `auth.login_failed`, alamat email yang dicoba, alamat IP, dan user agent, dengan kolom `user_id` bernilai `null` dan tanpa menyimpan kata sandi yang diketikkan.

---

## 4. Penegakan Status Akun dan Perubahan Kata Sandi

### 4.1 Penonaktifan Segera (`EnsureUserIsActive`)
Middleware `App\Http\Middleware\EnsureUserIsActive` berjalan pada setiap request web:
- Jika akun pengguna dinonaktifkan oleh Super Admin saat sesi pengguna tersebut masih terbuka, request berikutnya langsung memutus sesi, menghancurkan cookie, dan mengarahkan pengguna kembali ke halaman login (FR-AUTH-08, AC-AUTH-05).

### 4.2 Kata Sandi Sementara (`EnsurePasswordChanged`)
- Akun yang baru dibuat oleh Super Admin atau mengalami reset kata sandi memiliki flag `must_change_password = true`.
- Middleware `App\Http\Middleware\EnsurePasswordChanged` membatasi navigasi pengguna tersebut hanya pada:
  - `GET /password/change` (tampilan formulir ganti kata sandi wajib).
  - `PUT /password/change` (proses penyimpanan kata sandi baru).
  - `POST /logout` (keluar dari sistem).
- Akses ke rute lain secara otomatis dialihkan ke `/password/change` (FR-AUTH-05, AC-AUTH-04).
- Setelah kata sandi baru disimpan, flag diubah menjadi `false` dan pengguna diarahkan ke `/dashboard`.

### 4.3 Pembaruan Kata Sandi Mandiri pada Profil
- Pengguna dapat mengganti kata sandi melalui menu Profil (`PUT /password` atau `PUT /profile/password`).
- Penggantian kata sandi mandiri mensyaratkan konfirmasi kata sandi saat ini (*current password*) dan otomatis memutus sesi pada seluruh perangkat lain (`Auth::logoutOtherDevices`).

---

## 5. Matriks Peran dan Izin (Spatie Laravel Permission)

StockHub menetapkan 4 peran tetap (*fixed roles*) yang di-seed via `RoleAndPermissionSeeder`:
1. **`super_admin` (Super Admin):** Penguasa penuh sistem, manajemen akun pengguna, peninjauan peran, log audit, dan konfigurasi.
2. **`admin_gudang` (Admin Gudang):** Pengelola data master, persediaan, transaksi barang masuk/keluar, penyesuaian stok, dan ekspor laporan.
3. **`staff_gudang` (Staff Gudang):** Pelaksana operasional; mencatat dan mengonfirmasi transaksi masuk/keluar miliknya sendiri; tanpa akses harga beli/jual barang.
4. **`manajer` (Manajer/Pimpinan):** Pengawas operasional (*read-only*); melihat dasbor, stok, mutasi, dan mengunduh laporan PDF/Excel.

### Matriks 26 Izin Resmi (PRD §4.2)

| Permission Key | Deskripsi Fungsional | SA | AG | SG | MG |
|---|---|:---:|:---:|:---:|:---:|
| `dashboard.view` | Melihat dasbor statistik | ✔ | ✔ | ✔ | ✔ |
| `users.manage` | Kelola akun pengguna (CRUD, status, reset password) | ✔ | – | – | – |
| `roles.view` | Melihat matriks peran dan izin | ✔ | – | – | – |
| `settings.manage` | Mengubah konfigurasi sistem | ✔ | – | – | – |
| `categories.view` | Melihat daftar kategori barang | ✔ | ✔ | ✔ | ✔ |
| `categories.manage` | Menambah/mengubah kategori barang | ✔ | ✔ | – | – |
| `units.view` | Melihat daftar satuan barang | ✔ | ✔ | ✔ | ✔ |
| `units.manage` | Menambah/mengubah satuan barang | ✔ | ✔ | – | – |
| `suppliers.view` | Melihat daftar supplier | ✔ | ✔ | ✔ | ✔ |
| `suppliers.manage` | Menambah/mengubah supplier | ✔ | ✔ | – | – |
| `products.view` | Melihat data master barang | ✔ | ✔ | ✔ | ✔ |
| `products.view-price` | Melihat nilai harga beli dan harga jual barang | ✔ | ✔ | – | ✔ |
| `products.manage` | Menambah/mengubah barang dan saldo awal | ✔ | ✔ | – | – |
| `stocks.view` | Melihat halaman stok barang dan stok minimum | ✔ | ✔ | ✔ | ✔ |
| `stock-ins.view` | Melihat daftar/detail barang masuk | ✔ | ✔ | ✔ (own) | ✔ |
| `stock-ins.create` | Membuat draft, ubah, dan konfirmasi barang masuk | ✔ | ✔ | ✔ (own) | – |
| `stock-ins.cancel-confirmed`| Membatalkan transaksi masuk terkonfirmasi | ✔ | ✔ | – | – |
| `stock-outs.view` | Melihat daftar/detail barang keluar | ✔ | ✔ | ✔ (own) | ✔ |
| `stock-outs.create` | Membuat draft, ubah, dan konfirmasi barang keluar | ✔ | ✔ | ✔ (own) | – |
| `stock-outs.cancel-confirmed`| Membatalkan transaksi keluar terkonfirmasi | ✔ | ✔ | – | – |
| `adjustments.view` | Melihat daftar penyesuaian stok | ✔ | ✔ | – | ✔ |
| `adjustments.create` | Melakukan penyesuaian stok opname | ✔ | ✔ | – | – |
| `stock-movements.view` | Melihat buku besar riwayat stok global | ✔ | ✔ | – | ✔ |
| `reports.view` | Melihat laporan persediaan dan mutasi | ✔ | ✔ | – | ✔ |
| `reports.export` | Mengekspor laporan ke file PDF / Excel | ✔ | ✔ | – | ✔ |
| `activity-logs.view` | Melihat jejak audit log aktivitas | ✔ | – | – | – |

*Catatan Integritas:*
- Satu pengguna memiliki tepat satu peran (ditegakkan via `syncRoles([$role])` pada service).
- Izin dibaca dari database pada setiap siklus request HTTP, bukan dipercaya dari nilai sesi mentah.

---

## 6. Aturan Bisnis Manajemen Pengguna (BR-24)

Ditegakkan pada `App\Http\Controllers\UserController` dan `App\Policies\UserPolicy`:
1. **Anti Self-Deactivation:** Pengguna tidak dapat menonaktifkan akun miliknya sendiri.
2. **Anti Self-Role-Change:** Pengguna tidak dapat mengubah peran akun miliknya sendiri.
3. **Perlindungan Super Admin Terakhir (`LAST_SUPER_ADMIN`):**
   Sistem menolak penonaktifan atau penurunan peran (*demotion*) Super Admin jika hanya tersisa satu Super Admin berstatus aktif di basis data (HTTP 422 dengan pesan `"Harus ada minimal satu Super Admin aktif."`).

---

## 7. Fondasi Audit: ActivityLogger

Layanan `App\Services\ActivityLogger` mencatat aktivitas penting secara permanen (*append-only*):
- **Katalog Aksi Tahap 2:**
  - `auth.login`: Login berhasil.
  - `auth.login_failed`: Login gagal (kredensial salah / akun nonaktif).
  - `auth.logout`: Pengguna keluar.
  - `auth.password_changed`: Kata sandi diubah (wajib maupun profil).
  - `user.created`: Penambahan pengguna baru oleh Super Admin.
  - `user.updated`: Pembaruan nama/email pengguna.
  - `user.status_changed`: Pengaktifan atau penonaktifan pengguna.
  - `user.role_changed`: Perubahan peran pengguna.
  - `user.password_reset`: Reset kata sandi sementara oleh Super Admin.
- **Penyaringan Data Sensitif:** Parameter kata sandi, token, dan kunci rahasia secara otomatis dihapus dari payload `old_values` dan `new_values`.
- **Keterikatan Transaksi:** Pencatatan log dijalankan di dalam `DB::transaction` yang sama dengan perubahan entitas pengguna terkait.
