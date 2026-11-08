# Aturan Bisnis — StockHub

| Atribut | Nilai |
|---|---|
| Tujuan | Merinci katalog seluruh aturan bisnis (BR-01 s.d. BR-32), transisi status, rumus stok, dan lokasi penegakan kode. |
| Cakupan | Seluruh aturan penegakan integritas data persediaan, autentikasi, dan audit untuk satu gudang. |
| Audiens | Developer, QA Engineer, Product Owner, Stakeholder |
| Status | [SUDAH DIIMPLEMENTASI] (Tahap 1–5: Auth, Master Data, Transaksi, Dashboard, Persediaan, Laporan) |
| Versi aplikasi | 1.0.0 |
| Terakhir diperbarui | 2026-11-08 |
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

## 5. Status dan Transisi Transaksi (STI, STO, ADJ)

### 5.1 Siklus Hidup Transaksi Dokumen (BR-07, BR-12, BR-13, BR-14)
- **Draft:**
  - Dokumen tersimpan di database (`stock_ins` atau `stock_outs`), tetapi belum memengaruhi saldo persediaan (`current_stock`) dan belum menghasilkan mutasi (`stock_movements`).
  - Draft dapat diubah detail dan headernya kapan saja oleh pembuatnya (Staff Gudang) atau oleh Super Admin / Admin Gudang.
  - Draft barang keluar mengizinkan kuantitas melebihi stok yang ada saat itu (menampilkan peringatan draft di antarmuka).
  - Draft dapat dibatalkan oleh pembuatnya atau SA/AG dengan mencantumkan alasan minimal 10 karakter. Pembatalan draft tidak mengubah stok.
- **Confirmed:**
  - Status konfirmasi memposting perubahan stok secara riil dan atomik ke dalam basis data melalui `StockService`.
  - Pada barang keluar, konfirmasi memeriksa saldo stok setelah row lock. Jika ada item yang tidak mencukupi, seluruh transaksi ditolak dengan `InsufficientStockException` (tidak ada perubahan parsial).
  - Transaksi confirmed bersifat **final dan terkunci**: tidak dapat diedit atau dihapus secara fisik (BR-12).
  - Satu-satunya koreksi untuk transaksi confirmed adalah **Pembatalan Transaksi (Reversal)** yang hanya dapat dilakukan oleh peran Super Admin atau Admin Gudang (BR-13).
- **Cancelled:**
  - Pembatalan transaksi confirmed memicu mutasi pembalik (`stock_in_cancel` dengan kuantitas negatif untuk barang masuk, atau `stock_out_cancel` dengan kuantitas positif untuk barang keluar).
  - Untuk pembatalan barang masuk, sistem memeriksa apakah stok saat ini mencukupi untuk dikurangi. Jika stok saat ini telah terpakai oleh pengeluaran lain sehingga kurang dari kuantitas pembatalan, sistem menolak pembatalan dengan `ReversalInsufficientStockException` (HTTP 422).
  - Dokumen berstatus cancelled bersifat permanen (*terminal state*) dan tidak dapat diaktifkan kembali.
  - Nomor dokumen yang telah dibatalkan tidak pernah dipakai ulang (BR-19).

### 5.2 Penyesuaian Stok Opname (BR-16)
- Hanya dapat dilakukan oleh Super Admin dan Admin Gudang (Staff Gudang dan Manajer ditolak HTTP 403).
- Dokumen penyesuaian tidak memiliki status draft; proses penyimpanan langsung memposting perubahan stok dan berstatus final.
- Sistem memverifikasi kecocokan stok sistem terkunci dengan nilai yang dilihat pengguna saat membuka form (`expected_system_qty`). Jika terdapat transaksi lain yang mengubah stok di antara pembukaan form dan klik simpan, sistem menolak posting dengan `StockChangedException` (HTTP 409 `STOCK_CHANGED`).
- Jika stok fisik sama persis dengan stok sistem (selisih = 0), sistem menolak dengan `NoDifferenceException` (HTTP 422 `NO_DIFFERENCE`).
- Dokumen penyesuaian tidak dapat diubah maupun dibatalkan; koreksi lanjutan harus dilakukan melalui dokumen penyesuaian baru.

### 5.3 Batasan Scope "Own" Staff Gudang (BR-23)
- Pengguna dengan peran Staff Gudang (SG) hanya dapat melihat, mengubah, mengonfirmasi, atau membatalkan draft transaksi yang dibuat oleh dirinya sendiri (`created_by = auth()->id()`).
- Upaya mengakses atau memanipulasi ID transaksi milik staf lain menghasilkan respons **HTTP 404** (bukan 403) untuk mencegah *account enumeration* dan celah IDOR.

---

## 6. Rumus dan Definisi Status Stok (BR-18)

- `habis`: Kondisi ketika `current_stock = 0`.
- `minimum`: Kondisi ketika `0 < current_stock <= min_stock`.
- `aman`: Kondisi ketika `current_stock > min_stock`.
Dihitung secara terpusat melalui enum `App\Enums\StockStatus::fromStock($current, $min)`. Berlaku seragam di Dashboard, Halaman Stok Barang, Halaman Stok Minimum, Notifikasi Lonceng Navbar, dan Laporan.

---

## 7. Larangan Agregasi Kuantitas Lintas Satuan (BR-27)

- **Deskripsi:** Sistem secara tegas **dilarang menjumlahkan total kuantitas fisik lintas satuan yang berbeda** (misalnya menjumlahkan pcs dengan kg atau liter), karena menghasilkan angka yang tidak bermakna bisnis.
- **Implementasi:**
  - Kartu dasbor "Total stok tersedia" diganti dengan kartu hitungan jumlah jenis barang yang memiliki stok > 0 (`availableStockProductsCount`).
  - Grafik dasbor membandingkan **jumlah transaksi confirmed**, bukan volume kuantitas.
  - Tabel dan layar laporan tidak menampilkan baris footer grand total kuantitas lintas barang. Subtotal kuantitas hanya diizinkan per jenis barang spesifik.

---

## 8. Integritas Data Laporan (BR-21)

- **Transaksi Masuk & Keluar:** Seluruh laporan transaksi hanya menyertakan data yang berstatus `confirmed`. Transaksi berstatus `draft` dan `cancelled` tidak dimasukkan dalam laporan atau statistik dasbor.
- **Laporan Pergerakan Stok:** Memuat seluruh mutasi riwayat stok, baik mutasi asli (`stock_in`, `stock_out`, `adjustment`, `opening_balance`) maupun mutasi pembalik pembatalan (`stock_in_cancel`, `stock_out_cancel`).
- **Laporan Persediaan As-Of:** Perhitungan stok pada tanggal lampau dihitung dari akumulasi `SUM(qty_change)` pada tabel `stock_movements` yang memiliki `movement_date <= as_of_date`. Status stok dievaluasi terhadap `min_stock` barang saat ini.

---

## 9. Prinsip Single Source of Truth Laporan & Ekspor (BR-31)

- **Deskripsi:** Layar antarmuka web, dokumen PDF, dan berkas Excel untuk setiap jenis laporan wajib menggunakan **Query Object yang sama persis** (`app/Reports/*ReportQuery.php`).
- **Implementasi:** Tidak ada logika query terpisah yang ditulis untuk ekspor file; filter, kondisi status, dan sorting dijamin menghasilkan baris data yang identik antara layar dan hasil unduhan.
