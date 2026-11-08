# Spesifikasi Endpoint dan API — StockHub

| Atribut | Nilai |
|---|---|
| Tujuan | Menjelaskan kontrak antarmuka web HTTP, endpoint AJAX JSON, skema validasi, dan katalog kode error. |
| Cakupan | Rute sesi web browser dan endpoint AJAX internal; tidak mencakup REST API publik berbasis token (Later). |
| Audiens | Developer, QA Engineer, Frontend Reviewer |
| Status | [SUDAH DIIMPLEMENTASI] (Tahap 1–5: Auth, Master Data, Transaksi, Dashboard, Persediaan, Laporan) |
| Versi aplikasi | 1.0.0 |
| Terakhir diperbarui | 2026-11-08 |
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

## 4. Rute Master Data Aktual (Tahap 3)

### 4.1 Kategori Barang (`categories.*`)

| Method | Path | Nama Rute | Otorisasi / Permission | Peran Diizinkan | Deskripsi & Validasi |
|---|---|---|---|---|---|
| `GET` | `/categories` | `categories.index` | `categories.view` / `categories.manage` | SA, AG, SG, MG | Daftar kategori: filter `q`, `status`, pagination 15/25/50/100, hitungan barang |
| `GET` | `/categories/create` | `categories.create` | `categories.manage` | SA, AG | Formulir tambah kategori baru |
| `POST` | `/categories` | `categories.store` | `categories.manage` | SA, AG | Simpan kategori: `name` (3–100 unik case-insensitive), `description` (maks 500), `is_active` |
| `GET` | `/categories/{category}/edit` | `categories.edit` | `categories.manage` | SA, AG | Formulir ubah kategori |
| `PUT` | `/categories/{category}` | `categories.update` | `categories.manage` | SA, AG | Perbarui data kategori; nonaktif ditolak jika ada barang aktif terkait (BR-04) |
| `PATCH`| `/categories/{category}/status` | `categories.status` | `categories.manage` | SA, AG | Toggle status; nonaktif ditolak jika ada barang aktif terkait (BR-04) |

### 4.2 Satuan Barang (`units.*`)

| Method | Path | Nama Rute | Otorisasi / Permission | Peran Diizinkan | Deskripsi & Validasi |
|---|---|---|---|---|---|
| `GET` | `/units` | `units.index` | `units.view` / `units.manage` | SA, AG, SG, MG | Daftar satuan: filter `q`, `status`, pagination |
| `GET` | `/units/create` | `units.create` | `units.manage` | SA, AG | Formulir tambah satuan baru |
| `POST` | `/units` | `units.store` | `units.manage` | SA, AG | Simpan satuan: `name` (2–50), `symbol` (1–10), `allow_decimal` (bool), `description` (maks 255) |
| `GET` | `/units/{unit}/edit` | `units.edit` | `units.manage` | SA, AG | Formulir ubah satuan (`allow_decimal` terkunci bila sudah dipakai barang, BR-25) |
| `PUT` | `/units/{unit}` | `units.update` | `units.manage` | SA, AG | Perbarui data satuan; perubahan `allow_decimal` ditolak bila sudah dipakai barang |
| `PATCH`| `/units/{unit}/status` | `units.status` | `units.manage` | SA, AG | Toggle status; nonaktif ditolak bila dipakai barang aktif (BR-04) |

### 4.3 Supplier (`suppliers.*`)

| Method | Path | Nama Rute | Otorisasi / Permission | Peran Diizinkan | Deskripsi & Validasi |
|---|---|---|---|---|---|
| `GET` | `/suppliers` | `suppliers.index` | `suppliers.view` / `suppliers.manage` | SA, AG, SG, MG | Daftar supplier: filter `q` (kode/nama/kontak), `status`, pagination |
| `GET` | `/suppliers/create` | `suppliers.create` | `suppliers.manage` | SA, AG | Formulir tambah supplier |
| `POST` | `/suppliers` | `suppliers.store` | `suppliers.manage` | SA, AG | Simpan supplier: kode otomatis `SUP-NNNN` (immutable), `name` (3–150), `phone`, `email` |
| `GET` | `/suppliers/{supplier}` | `suppliers.show` | `suppliers.view` / `suppliers.manage` | SA, AG, SG, MG | Detail supplier + 10 transaksi masuk terakhir |
| `GET` | `/suppliers/{supplier}/edit` | `suppliers.edit` | `suppliers.manage` | SA, AG | Formulir ubah supplier (kode readonly) |
| `PUT` | `/suppliers/{supplier}` | `suppliers.update` | `suppliers.manage` | SA, AG | Perbarui data supplier (kode immutable tidak berubah) |
| `PATCH`| `/suppliers/{supplier}/status` | `suppliers.status` | `suppliers.manage` | SA, AG | Toggle status aktif/nonaktif kapan saja (BR-04) |

### 4.4 Master Barang (`products.*`)

| Method | Path | Nama Rute | Otorisasi / Permission | Peran Diizinkan | Deskripsi & Validasi |
|---|---|---|---|---|---|
| `GET` | `/products` | `products.index` | `products.view` / `products.manage` | SA, AG, SG, MG | Daftar barang: filter `q`, `category_id`, `stock_status`, `status`, sort, pagination (harga disembunyikan dari SG) |
| `GET` | `/products/create` | `products.create` | `products.manage` | SA, AG | Formulir tambah barang |
| `POST` | `/products` | `products.store` | `products.manage` | SA, AG | Simpan barang baru: kode auto/manual regex, upload foto, opening_stock -> StockService |
| `GET` | `/products/{product}` | `products.show` | `products.view` / `products.manage` | SA, AG, SG, MG | Detail barang + kartu stok 20 mutasi terakhir (harga disembunyikan dari SG) |
| `GET` | `/products/{product}/edit` | `products.edit` | `products.manage` | SA, AG | Formulir ubah barang (satuan terkunci bila ada mutasi, BR-25) |
| `PUT` | `/products/{product}` | `products.update` | `products.manage` | SA, AG | Perbarui data barang (kode immutable, current_stock diabaikan) |
| `PATCH`| `/products/{product}/status` | `products.status` | `products.manage` | SA, AG | Toggle status; nonaktif ditolak bila current_stock > 0 (BR-04) |
| `DELETE`| `/products/{product}/photo` | `products.photo.destroy` | `products.manage` | SA, AG | Hapus foto barang dari storage publik |

### 4.5 Transaksi Barang Masuk (`stock-ins.*`)

| Method | Path | Nama Rute | Otorisasi / Permission | Peran Diizinkan | Deskripsi & Validasi |
|---|---|---|---|---|---|
| `GET` | `/stock-ins` | `stock-ins.index` | `stock-ins.view` | SA, AG, MG; SG (own) | Daftar transaksi: filter `number`, `status`, `source_type`, `supplier_id`, tanggal |
| `GET` | `/stock-ins/create` | `stock-ins.create` | `stock-ins.create` | SA, AG, SG | Formulir draft masuk (1–100 baris item) |
| `POST` | `/stock-ins` | `stock-ins.store` | `stock-ins.create` | SA, AG, SG | Simpan draft baru: nomor otomatis `IN-YYYYMM-NNNN`, status draft |
| `GET` | `/stock-ins/{stock_in}` | `stock-ins.show` | `stock-ins.view` | SA, AG, MG; SG (own) | Detail dokumen + baris barang (SG milik orang lain -> 404) |
| `GET` | `/stock-ins/{stock_in}/edit` | `stock-ins.edit` | `stock-ins.create` | SA, AG; SG (own) | Formulir ubah draft masuk (hanya status draft) |
| `PUT` | `/stock-ins/{stock_in}` | `stock-ins.update` | `stock-ins.create` | SA, AG; SG (own) | Perbarui draft masuk (status confirmed ditolak 409) |
| `POST` | `/stock-ins/{stock_in}/confirm` | `stock-ins.confirm` | `stock-ins.create` | SA, AG; SG (own) | Konfirmasi draft masuk: posting stok atomik via StockService |
| `POST` | `/stock-ins/{stock_in}/cancel` | `stock-ins.cancel` | `stock-ins.create` / `.cancel-confirmed` | draft: pembuat/SA/AG; confirmed: SA/AG | Batalkan transaksi (draft: tanpa efek stok; confirmed: reversal stok) |

### 4.6 Transaksi Barang Keluar (`stock-outs.*`)

| Method | Path | Nama Rute | Otorisasi / Permission | Peran Diizinkan | Deskripsi & Validasi |
|---|---|---|---|---|---|
| `GET` | `/stock-outs` | `stock-outs.index` | `stock-outs.view` | SA, AG, MG; SG (own) | Daftar transaksi keluar: filter `number`, `status`, `purpose_type`, `destination`, tanggal |
| `GET` | `/stock-outs/create` | `stock-outs.create` | `stock-outs.create` | SA, AG, SG | Formulir draft keluar (1–100 baris item, indikator stok AJAX) |
| `POST` | `/stock-outs` | `stock-outs.store` | `stock-outs.create` | SA, AG, SG | Simpan draft baru: nomor otomatis `OUT-YYYYMM-NNNN` |
| `GET` | `/stock-outs/{stock_out}` | `stock-outs.show` | `stock-outs.view` | SA, AG, MG; SG (own) | Detail dokumen barang keluar (SG milik orang lain -> 404) |
| `GET` | `/stock-outs/{stock_out}/edit` | `stock-outs.edit` | `stock-outs.create` | SA, AG; SG (own) | Formulir ubah draft keluar (hanya status draft) |
| `PUT` | `/stock-outs/{stock_out}` | `stock-outs.update` | `stock-outs.create` | SA, AG; SG (own) | Perbarui draft keluar (status confirmed ditolak 409) |
| `POST` | `/stock-outs/{stock_out}/confirm` | `stock-outs.confirm` | `stock-outs.create` | SA, AG; SG (own) | Konfirmasi keluar: verifikasi stok cukup & potong stok via StockService |
| `POST` | `/stock-outs/{stock_out}/cancel` | `stock-outs.cancel` | `stock-outs.create` / `.cancel-confirmed` | draft: pembuat/SA/AG; confirmed: SA/AG | Batalkan transaksi (draft: tanpa efek stok; confirmed: pemulihan stok) |

### 4.7 Penyesuaian Stok Opname (`adjustments.*`)

| Method | Path | Nama Rute | Otorisasi / Permission | Peran Diizinkan | Deskripsi & Validasi |
|---|---|---|---|---|---|
| `GET` | `/adjustments` | `adjustments.index` | `adjustments.view` | SA, AG, MG | Daftar penyesuaian: filter `product_id`, `reason_code`, tanggal |
| `GET` | `/adjustments/create` | `adjustments.create` | `adjustments.create` | SA, AG | Formulir opname: input fisik & expected_system_qty |
| `POST` | `/adjustments` | `adjustments.store` | `adjustments.create` | SA, AG | Simpan & posting penyesuaian: nomor `ADJ-YYYYMM-NNNN`, final langsung |
| `GET` | `/adjustments/{adjustment}` | `adjustments.show` | `adjustments.view` | SA, AG, MG | Detail hasil penyesuaian stok opname |

*Catatan:* Penyesuaian stok tidak memiliki rute edit/update/delete (BR-16).

### 4.8 Pemantauan Stok & Riwayat Mutasi (`stocks.*`, `stock-movements.*`)

| Method | Path | Nama Rute | Otorisasi / Permission | Peran Diizinkan | Deskripsi & Validasi |
|---|---|---|---|---|---|
| `GET` | `/stocks` | `stocks.index` | `stocks.view` | SA, AG, SG, MG | Daftar status stok semua barang: filter `q`, `category_id`, `stock_status`, `status`, sort |
| `GET` | `/stocks/low` | `stocks.low` | `stocks.view` | SA, AG, SG, MG | Daftar barang kritis aktif (status `habis` dan `minimum`), habis didahulukan |
| `GET` | `/stock-movements` | `stock-movements.index` | `stock-movements.view` | SA, AG, MG | Buku besar mutasi riwayat stok global (SG ditolak 403) |

### 4.9 Laporan Persediaan & Mutasi (`reports.*`)

| Method | Path | Nama Rute | Otorisasi / Permission | Peran Diizinkan | Deskripsi & Validasi |
|---|---|---|---|---|---|
| `GET` | `/reports` | `reports.index` | `reports.view` | SA, AG, MG | Pengalihan rute ke `/reports/stock` |
| `GET` | `/reports/{type}` | `reports.show` | `reports.view` | SA, AG, MG | Layar laporan interaktif 5 jenis: `stock`, `stock-ins`, `stock-outs`, `adjustments`, `movements` (SG ditolak 403) |

---

## 5. Rangkuman Endpoint AJAX JSON (PRD §12.3)

| Method | Path | Tujuan | Permission | Status |
|---|---|---|---|:---:|
| `GET` | `/ajax/products/search?q=&limit=20` | Pencarian barang aktif untuk dropdown transaksi | `products.view` | [SUDAH DIIMPLEMENTASI] |
| `GET` | `/ajax/products/{id}/stock` | Pengambilan data stok terkini, satuan, dan status stok | `products.view` | [SUDAH DIIMPLEMENTASI] |
| `GET` | `/ajax/dashboard/chart?period=daily\|weekly\|monthly` | Data analitik grafik dasbor transaksi masuk vs keluar | `dashboard.view` (SG: 403) | [SUDAH DIIMPLEMENTASI] |

---

## 6. Format Amplop Error JSON

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

## 7. Katalog Kode Error Domain Terkait

| Kode Error | HTTP Status | Pemicu / Keterangan |
|---|---|---|
| `VALIDATION_FAILED` | 422 | Format isian form tidak memenuhi spesifikasi |
| `REFERENCE_IN_USE` | 422 | Penolakan penonaktifan kategori/satuan yang masih dipakai barang aktif (BR-04) |
| `LAST_SUPER_ADMIN` | 422 | Penolakan penonaktifan atau penurunan peran Super Admin aktif terakhir (BR-24) |
| `INSUFFICIENT_STOCK` | 422 | Kuantitas barang keluar melebihi stok tersedia saat konfirmasi (BR-10) |
| `PRODUCT_INACTIVE` | 422 | Terdapat barang berstatus nonaktif dalam transaksi atau penyesuaian |
| `REVERSAL_INSUFFICIENT_STOCK` | 422 | Pembatalan barang masuk membuat stok persediaan saat ini bernilai negatif (BR-13) |
| `NO_DIFFERENCE` | 422 | Hasil hitung fisik sama dengan stok sistem; tidak ada yang perlu disesuaikan |
| `INVALID_STATE_TRANSITION` | 409 | Transisi status tidak sah / dokumen sudah diproses atau bukan status draft (BR-12) |
| `STOCK_CHANGED` | 409 | Stok sistem berubah oleh transaksi lain saat form penyesuaian sedang dibuka (D-33) |
| `PASSWORD_CHANGE_REQUIRED` | 403 | Akses rute ditolak karena akun berstatus `must_change_password = true` |
| `ACCOUNT_INACTIVE` | 403 | Akun dinonaktifkan saat sesi masih aktif |
| `TOO_MANY_REQUESTS` | 429 | Melebihi batas throttle login 5 kali dalam 1 menit |
| `FORBIDDEN` | 403 | Pengguna tidak memiliki hak akses permission yang disyaratkan |
| `NOT_FOUND` | 404 | Data tidak ditemukan atau data milik pengguna lain di luar scope *own* (BR-23) |
| `LOCK_TIMEOUT` | 503 | Deadlock / lock wait timeout pada MySQL setelah 3 kali percobaan (BR-09) |
