# Aturan Bisnis — StockHub

| Atribut | Nilai |
|---|---|
| Tujuan | Merinci katalog seluruh aturan bisnis (BR-01 s.d. BR-32), transisi status, rumus stok, dan lokasi penegakan kode. |
| Cakupan | Seluruh aturan penegakan integritas data persediaan, autentikasi, dan audit untuk satu gudang. |
| Audiens | Developer, QA Engineer, Product Owner, Stakeholder |
| Status | [SUDAH DIIMPLEMENTASI] (Sebagian — Aturan Autentikasi, Akun, dan Audit Tahap 2) |
| Versi aplikasi | 1.0.0 |
| Terakhir diperbarui | 2026-11-06 |
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

## 4. Prinsip Integritas Saldo Stok (Fondasi)

1. **Satu Jalur Penulisan (BR-06):** Nilai `products.current_stock` hanya boleh diperbarui melalui `StockService` (tahap 4).
2. **Atomik dan Terkunci (BR-08, BR-09):** Setiap perubahan stok berjalan dalam satu transaksi basis data dengan *pessimistic row locking* berurutan ID naik untuk mencegah *deadlock*.
3. **Pencegahan Stok Negatif (BR-02):** Nilai stok, kuantitas transaksi (>0), stok minimum, dan harga tidak boleh bernilai negatif (dijaga oleh validasi aplikasi dan CHECK constraint MySQL).
4. **Append-Only History (BR-15):** Tabel `stock_movements` dan `activity_logs` tidak boleh diubah (*update*) atau dihapus (*delete*). Model Eloquent melempar `RuntimeException` pada event updating/deleting.

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
