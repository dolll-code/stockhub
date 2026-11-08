# Arsitektur Sistem — StockHub

| Atribut | Nilai |
|---|---|
| Tujuan | Mendokumentasikan gaya arsitektur, lapisan sistem, komponen, alur posting stok, dan penanganan konkurensi. |
| Cakupan | Arsitektur aplikasi monolit Laravel 12 server-rendered; mutasi stok, penomoran dokumen, dan transaksi. |
| Audiens | Developer, Software Architect, Code Reviewer, Technical Lead |
| Status | [SUDAH DIIMPLEMENTASI] (Tahap 1–4: Fondasi, Auth, Master Data, dan Inti Transaksi Stok) |
| Versi aplikasi | 1.0.0 |
| Terakhir diperbarui | 2026-11-07 |
| Pemilik | Tech Lead |
| Terkait | `prd.md` §9, §10, §14; `docs/database.md`; `docs/business-rules.md` |

---

## 1. Gambaran Arsitektur Sistem
Aplikasi StockHub dirancang sebagai **monolit Laravel 12 (MVC) server-rendered** dengan Blade, Tailwind CSS, dan Alpine.js. Seluruh logika transaksi dan mutasi saldo stok dipisahkan secara ketat ke dalam **Service Layer**. Tidak ada microservice, queue terdistribusi, atau cache kompleks pada MVP (menghindari overengineering sesuai PRD §9.1).

---

## 2. Lapisan Sistem dan Tanggung Jawab
- **Middleware:** Penegakan autentikasi cookie web, verifikasi keaktifan sesi (`EnsureUserIsActive`), kewajiban ganti password (`EnsurePasswordChanged`), pembatasan request (`throttle:5,1`), dan otorisasi izin Spatie (`can:`).
- **Controllers:** Menangani alur request HTTP, memanggil Service yang relevan, dan mengembalikan respons HTML atau JSON (tipis, tanpa logika bisnis atau manipulasi stok langsung).
- **FormRequest & Policy:** Validasi skema input server-side dan otorisasi berbasis izin serta scope `own` (Staff Gudang hanya berhak mengelola draft miliknya, transaksi orang lain mengembalikan HTTP 404).
- **Services:** Menampung seluruh logika bisnis, transaksi database (`DB::transaction`), *pessimistic row locking*, penomoran dokumen, dan pencatatan audit log:
  - `StockService`: Satu-satunya gerbang penulisan kolom `products.current_stock` dan pembuatan baris `stock_movements`.
  - `StockInService`: Pembuatan dan pembaruan dokumen draft barang masuk.
  - `StockOutService`: Pembuatan dan pembaruan dokumen draft barang keluar.
  - `DocumentNumberService`: Pembangkit nomor dokumen transaksi dan master data terurut atomik berbasis tabel `document_sequences` dengan *row lock*.
  - `ActivityLogger`: Pencatat rekam jejak aktivitas sistem yang bersifat *append-only*.
- **Models & Enums:** Definisi entitas Eloquent, relasi data, guard *append-only* (BR-15), dan status enum PHP (`TransactionStatus`, `MovementType`, `StockInSource`, `StockOutPurpose`, `AdjustmentReason`, `StockStatus`).

---

## 3. Alur Posting Stok dan Konkurensi (StockService)

Setiap perubahan stok berjalan dalam **satu database transaction atomik** yang dikelola terpusat oleh `StockService` dengan tahapan ketat:

```text
[HTTP Request]
       │
       ▼
[Controller & Policy] ──(Otorisasi peran / scope 'own')
       │
       ▼
[StockService::confirm / cancel / createAdjustment]
       │
       ▼
[DB::transaction (attempts: 3)]
       │
       ├─► 1. SELECT header transaksi FOR UPDATE (jika dokumen)
       │       Validasi status == DRAFT (konfirmasi) atau CONFIRMED (pembatalan)
       │       Jika tidak cocok ──► throw InvalidStateTransitionException (409)
       │
       ├─► 2. Kumpulkan product_id, urutkan ID naik (ORDER BY id ASC)
       │       SELECT ... FROM products WHERE id IN (...) ORDER BY id ASC FOR UPDATE
       │       (Mencegah deadlock pada multi-item locking, BR-09)
       │
       ├─► 3. Validasi Invarian Lapangan:
       │       - Barang wajib aktif (jika tidak -> ProductInactiveException 422)
       │       - Supplier wajib aktif jika source_type == purchase
       │       - Tanggal transaksi dalam rentang backdate (Setting::max_backdate_days)
       │       - Kuantitas > 0 dan mematuhi allow_decimal satuan
       │       - Kuantitas keluar <= current_stock (jika kurang -> InsufficientStockException 422)
       │       - Kuantitas reversal <= current_stock (jika kurang -> ReversalInsufficientStockException 422)
       │       - Penyesuaian: expected_system_qty == current_stock (jika beda -> StockChangedException 409)
       │       - Penyesuaian: physical_qty != current_stock (jika sama -> NoDifferenceException 422)
       │
       ├─► 4. Kalkulasi Stok (BCMath DECIMAL 15,3):
       │       - before = current_stock
       │       - change = +qty (masuk/pembalik keluar) atau -qty (keluar/pembalik masuk) atau (fisik - sistem)
       │       - after = before + change
       │
       ├─► 5. Insert baris stock_movements (Append-only, BR-15)
       ├─► 6. UPDATE products SET current_stock = after (StockService sole writer, BR-06)
       ├─► 7. UPDATE header dokumen SET status = confirmed / cancelled, confirmed_at, confirmed_by
       ├─► 8. Insert ActivityLog (Append-only)
       │
       ▼
[DB::commit] ──► Sukses (Redirect dengan Flash atau JSON)
```

### Penanganan Deadlock dan Lock Timeout
- Operasi DB dibungkus dengan mekanisme retry otomatis 3 kali (`DB::transaction(..., attempts: 3)`).
- Jika kegagalan lock terjadi berulang kali (MySQL error code 1213 deadlock atau 1205 lock wait timeout), pengecualian dipetakan secara terpusat ke `LockTimeoutException` (HTTP 503 dengan pesan *"Sistem sedang sibuk. Coba lagi beberapa saat lagi."*).
