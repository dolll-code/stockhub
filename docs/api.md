# Spesifikasi Endpoint dan API — StockHub

| Atribut | Nilai |
|---|---|
| Tujuan | Menjelaskan kontrak antarmuka web HTTP, endpoint AJAX JSON, skema validasi, dan katalog kode error. |
| Cakupan | Rute sesi web browser dan endpoint AJAX internal; tidak mencakup REST API publik berbasis token (Later). |
| Audiens | Developer, QA Engineer, Frontend Reviewer |
| Status | [SUDAH DIIMPLEMENTASI] (Sebagian — Rute Auth & Pengguna Tahap 2) |
| Versi aplikasi | 1.0.0 |
| Terakhir diperbarui | 2026-11-06 |
| Pemilik | Backend Lead |
| Terkait | `prd.md` §12, §15; `docs/authentication.md`; `docs/business-rules.md` |

---

## 1. Konvensi Antarmuka HTTP

- **Autentikasi:** Berbasis cookie sesi web (`web` guard).
- **Proteksi CSRF:** Wajib pada semua request non-GET (`X-CSRF-TOKEN` atau input `_token`).
- **Negosiasi Respons:** Request HTML mengembalikan redirect + flash notification; request AJAX/JSON mengembalikan respons terstruktur.
- **Batasan Akses:** Seluruh rute dilindungi oleh penegakan izin Spatie (`can:<permission>`) dan Policy. Akses data di luar hak scope *own* mengembalikan **HTTP 404** (mencegah IDOR).

---

## 2. Rute Autentikasi dan Akun Aktual (Tahap 2)

| Method | Path | Nama Rute | Otorisasi / Permission | Peran Diizinkan | Deskripsi & Parameter |
|---|---|---|---|---|---|
| `GET` | `/login` | `login` | Publik (Guest) | Semua | Formulir masuk sistem |
| `POST` | `/login` | `login` | Publik (Guest, throttle 5/min) | Semua | Proses login: `email`, `password` |
| `POST` | `/logout` | `logout` | `auth` | Semua | Keluar sistem, memutus sesi, regenerasi CSRF |
| `GET` | `/password/change` | `password.change` | `auth` | Semua | Halaman ganti password wajib |
| `PUT` | `/password/change` | `password.change.update` | `auth` | Semua | Simpan kata sandi baru: `password`, `password_confirmation` |
| `GET` | `/profile` | `profile.edit` | `auth` | Semua | Formulir profil diri |
| `PATCH` | `/profile` | `profile.update` | `auth` | Semua | Ubah profil diri: `name` (3–100), `email` (maks 150) |
| `PUT` | `/profile/password` | `profile.password.update` | `auth` | Semua | Ubah kata sandi diri: `current_password`, `password`, `password_confirmation` (terminasi perangkat lain) |
| `GET` | `/dashboard` | `dashboard` | `auth`, `can:dashboard.view` | SA, AG, SG, MG | Halaman utama dasbor |

---

## 3. Rute Manajemen Pengguna dan Peran (Khusus Super Admin)

| Method | Path | Nama Rute | Otorisasi / Permission | Peran Diizinkan | Deskripsi & Validasi |
|---|---|---|---|---|---|
| `GET` | `/users` | `users.index` | `can:users.manage` | SA | Daftar pengguna dengan filter `q`, `role`, `status`, pagination |
| `GET` | `/users/create` | `users.create` | `can:users.manage` | SA | Formulir tambah pengguna baru |
| `POST` | `/users` | `users.store` | `can:users.manage` | SA | Simpan user: `name`, `email` (unik case-insensitive), `role`, `password` |
| `GET` | `/users/{user}` | `users.show` | `can:users.manage` | SA | Dialihkan ke formulir ubah pengguna |
| `GET` | `/users/{user}/edit` | `users.edit` | `can:users.manage` | SA | Formulir ubah data pengguna |
| `PUT` | `/users/{user}` | `users.update` | `can:users.manage` | SA | Simpan perubahan: `name`, `email`, `role` (cek BR-24) |
| `PATCH`| `/users/{user}/status` | `users.status` | `can:users.manage` | SA | Toggle aktif/nonaktif (cek BR-24, putus sesi) |
| `POST` | `/users/{user}/reset-password` | `users.reset-password` | `can:users.manage` | SA | Reset kata sandi sementara baru & putus sesi pengguna |
| `GET` | `/roles` | `roles.index` | `can:roles.view` | SA | Matriks peran dan hak akses *read-only* |

*Catatan:* Sesuai BR-03 dan BR-12, **tidak ada rute DELETE** untuk akun pengguna maupun profil.

---

## 4. Format Amplop Error JSON

```json
{
  "message": "Pesan deskriptif dalam Bahasa Indonesia.",
  "code": "KODE_ERROR_TERSTANDAR",
  "errors": {
    "field_name": ["Detail pesan kesalahan per field."]
  }
}
```

---

## 5. Rangkuman Endpoint AJAX `[DIRENCANAKAN]` (Tahap Berikutnya)

- `GET /ajax/products/search`: Pencarian barang untuk dropdown baris formulir.
- `GET /ajax/products/{id}/stock`: Pengambilan stok terkini dan status stok barang.
- `GET /ajax/dashboard/chart`: Data grafik perbandingan transaksi masuk vs keluar.

---

## 6. Katalog Kode Error Domain Terkait

| Kode Error | HTTP Status | Pemicu / Keterangan |
|---|---|---|
| `VALIDATION_FAILED` | 422 | Format isian form tidak memenuhi spesifikasi |
| `LAST_SUPER_ADMIN` | 422 | Penolakan penonaktifan atau penurunan peran Super Admin aktif terakhir (BR-24) |
| `PASSWORD_CHANGE_REQUIRED` | 403 | Akses rute ditolak karena akun berstatus `must_change_password = true` |
| `ACCOUNT_INACTIVE` | 403 | Akun dinonaktifkan saat sesi masih aktif |
| `TOO_MANY_REQUESTS` | 429 | Melebihi batas throttle login 5 kali dalam 1 menit |
| `FORBIDDEN` | 403 | Pengguna tidak memiliki hak akses permission yang disyaratkan |
| `NOT_FOUND` | 404 | Data tidak ditemukan atau data milik orang lain di luar scope *own* |
