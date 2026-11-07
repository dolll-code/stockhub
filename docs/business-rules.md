# Aturan Bisnis — StockHub

| Atribut | Nilai |
|---|---|
| Tujuan | Merinci katalog seluruh aturan bisnis (BR-01 s.d. BR-32), transisi status, rumus stok, dan lokasi penegakan kode. |
| Cakupan | Seluruh aturan penegakan integritas data persediaan, autentikasi, dan audit untuk satu gudang. |
| Audiens | Developer, QA Engineer, Product Owner, Stakeholder |
| Status | [SUDAH DIIMPLEMENTASI] (Tahap 2 Auth/Pengguna & Tahap 3 Master Data) |
| Versi aplikasi | 1.0.0 |
| Terakhir diperbarui | 2026-11-07 |
| Pemilik | Product Owner & Backend Lead |
| Terkait | `prd.md` §6, §7; `docs/architecture.md`; `docs/database.md`; `docs/authentication.md` |

---

## 1. Aturan Manajemen Akun dan Super Admin (BR-24)

- **Deskripsi:** Email pengguna bersifat unik (case-insensitive). Setiap pengguna memiliki tepat satu peran aktif. Pengguna dilarang menonaktifkan akun miliknya sendiri atau mengubah peran akunnya sendiri. Sistem wajib memastikan selalu terdapat minimal satu Super Admin yang berstatus aktif.
- **Lokasi Penegakan di Kode:**
  - `App\Http\Controllers\UserController::update`: Menolak pengubahan peran mandiri dan penurunan peran Super Admin terakhir (`LastSuperAdminException`).
  - `App\Http\Controllers\UserController::toggleStatus`: Menolak penonaktifan diri sendiri dan penonaktifan Super Admin terakhir.
  - `App\Http\Requests\UserStoreRequest` & `UserUpdateRequest`: Validasi email unik, trim, dan lowercase.
- **Test Case Terkait:** `TC-USR-01`, `TC-USR-02`.

---

## 2. Aturan Pencatatan Audit Log (BR-30)

- **Deskripsi:** Seluruh aktivitas operasional dan keamanan penting wajib dicatat secara permanen (*append-only*): login, login gagal, logout, ubah kata sandi, penambahan pengguna, pengubahan data/peran pengguna, pengaktifan/penonaktifan akun, dan reset kata sandi. Data sensitif (kata sandi, token) dilarang dicatat.
- **Lokasi Penegakan di Kode:**
  - `App\Services\ActivityLogger`: Titik tunggal pencatatan audit log dengan filter sanitasi data sensitif rekursif.
  - `App\Models\ActivityLog`: Guard Eloquent menolak operasi `update` dan `delete`.
- **Test Case Terkait:** `TC-AUTH-01`, `TC-AUTH-02`, `TC-LOG-02`.

---

## 3. Aturan Zona Waktu dan Tanggal (BR-32)

- **Deskripsi:** Seluruh waktu disimpan dan ditampilkan dalam zona waktu `Asia/Jakarta` (WIB). Tanggal bisnis dokumen transaksi disimpan bertipe `DATE`.
- **Lokasi Penegakan di Kode:**
  - `config/app.php`: `'timezone' => 'Asia/Jakarta'`.
  - `.env.example` & `.env`: `APP_TIMEZONE=Asia/Jakarta`.

---

## 4. Prinsip Integritas Saldo Stok & Master Data (Tahap 3)

1. **Satu Jalur Penulisan (BR-06):** Nilai `products.current_stock` hanya boleh diperbarui melalui `StockService`. Kolom `current_stock` dihapus dari `$fillable` Eloquent; tidak ada form, endpoint, controller, atau seeder yang menulis langsung ke kolom tersebut.
2. **Saldo Awal Registrasi (BR-17):** Field `opening_stock` pada form pembuatan barang dieksekusi melalui `StockService::recordOpeningBalance`. Bila `opening_stock > 0`, sistem menyisipkan tepat satu movement `opening_balance` (before: 0, change: +opening_stock, after: opening_stock) dan memperbarui `current_stock` dalam satu transaksi atomik. Bila `opening_stock = 0`, tidak ada movement yang dibuat. Bila terjadi kegagalan, seluruh pembuatan barang di-rollback.
3. **Format Kuantitas & Desimal (BR-01):** Jika `units.allow_decimal = false`, kuantitas stok minimum dan stok awal harus berupa bilangan bulat. Jika true, kuantitas mengizinkan desimal hingga maksimal 3 digit.
4. **Pembatasan Penonaktifan Master Data (BR-04):**
   - Kategori dan satuan dilarang dinonaktifkan jika masih digunakan oleh barang berstatus aktif (ditolak HTTP 422 `REFERENCE_IN_USE` dengan pesan menyebut jumlah barang aktif terkait).
   - Barang hanya boleh dinonaktifkan jika `current_stock = 0`.
   - Supplier dapat dinonaktifkan kapan saja; supplier nonaktif tidak dapat dipilih untuk data/transaksi baru, tetapi riwayat tetap terjaga.
   - Reaktivasi selalu diizinkan untuk semua entitas master data.
5. **Immutabilitas Kode Identitas (BR-05):** Kode supplier (`SUP-NNNN`) dan kode barang (`BRG-NNNN` atau manual) bersifat permanen (immutable) setelah disimpan. Endpoint update mengabaikan upaya modifikasi kode.
6. **Penomoran Otomatis Berurutan (BR-19):** Penomoran kode `SUP-NNNN` dan `BRG-NNNN` dibangkitkan oleh `DocumentNumberService` menggunakan *pessimistic row lock* (`lockForUpdate()`) pada tabel `document_sequences` dalam transaksi basis data untuk menjamin keunikan di bawah konkurensi.
7. **Penguncian Satuan (BR-25):** Nilai `allow_decimal` pada satuan terkunci permanen setelah satuan digunakan oleh barang mana pun. Satuan pada master barang terkunci permanen setelah barang memiliki riwayat mutasi stok.
8. **Penyembunyian Harga Sensitif (BR-26):** Pengguna tanpa izin `products.view-price` (seperti Staff Gudang / SG) tidak pernah menerima field harga beli maupun harga jual pada tampilan HTML maupun payload JSON.
9. **Penentuan Status Stok (BR-18):**
   - `habis`: jika `current_stock <= 0`.
   - `minimum`: jika `0 < current_stock <= min_stock`.
   - `aman`: jika `current_stock > min_stock`.
10. **Keamanan Upload Foto (SEC-08):** Format yang diterima: JPG, JPEG, PNG, WebP (maks 2 MB, dimensi 100x100 s.d. 4000x4000 px). File SVG dan MIME palsu ditolak keras; nama file diacak dan disimpan di `storage/app/public/products`.

---

## 5. Status dan Transisi Transaksi `[DIRENCANAKAN]` (Tahap 4)

- **Draft:** Dokumen tersimpan namun belum memengaruhi saldo stok barang.
- **Confirmed:** Dokumen sah dan telah diposting; saldo stok bertambah (masuk) atau berkurang (keluar), serta riwayat mutasi permanen dibuat. Dokumen confirmed tidak dapat diedit.
- **Cancelled:** Dokumen dibatalkan. Jika sebelumnya berstatus confirmed, sistem memposting mutasi pembalik (*reversal*) untuk menetralkan efek stok sebelumnya.

---

## 6. Rumus dan Definisi Status Stok (BR-18)

- `habis`: Kondisi ketika `current_stock = 0`.
- `minimum`: Kondisi ketika `0 < current_stock <= min_stock`.
- `aman`: Kondisi ketika `current_stock > min_stock`.
Dihitung secara dinamis melalui enum `App\Enums\StockStatus::fromStock($current, $min)`.
