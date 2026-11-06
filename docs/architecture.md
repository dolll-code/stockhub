# Arsitektur Sistem — StockHub

| Atribut | Nilai |
|---|---|
| Tujuan | Mendokumentasikan gaya arsitektur, lapisan sistem, komponen, alur posting stok, dan penanganan konkurensi. |
| Cakupan | Arsitektur aplikasi monolit Laravel 12 server-rendered; tidak mencakup infrastruktur cloud atau multi-gudang (Later). |
| Audiens | Developer, Software Architect, Code Reviewer, Technical Lead |
| Status | [BELUM DIIMPLEMENTASI] (Kerangka Dokumentasi Tahap 0) |
| Versi aplikasi | 1.0.0 |
| Terakhir diperbarui | 2026-11-06 |
| Pemilik | Tech Lead |
| Terkait | `prd.md` §9, §10, §14; `docs/database.md`; `docs/business-rules.md` |

---

## 1. Gambaran Arsitektur Sistem
Aplikasi StockHub dirancang sebagai **monolit Laravel 12 (MVC) server-rendered** dengan Blade, Tailwind CSS, dan Alpine.js. Seluruh logika transaksi dan mutasi saldo stok dipisahkan secara ketat ke dalam **Service Layer**.

## 2. Lapisan Sistem dan Tanggung Jawab
- **Middleware:** Penegakan autentikasi, status aktif pengguna (`EnsureUserIsActive`), kewajiban ganti password (`EnsurePasswordChanged`), pembatasan request (`throttle`), dan otorisasi dasar (`can:`).
- **Controllers:** Menangani alur request HTTP, memanggil Service yang relevan, dan mengembalikan respons HTML atau JSON (tipis, tanpa query berat).
- **FormRequest & Policy:** Validasi skema input dan otorisasi tingkat model/objek (termasuk kepemilikan data scope *own*).
- **Services:** Menampung seluruh logika bisnis, transaksi database (`DB::transaction`), *pessimistic row locking*, penomoran dokumen, dan pencatatan audit log.
- **Models & Enums:** Definisi entitas Eloquent, relasi data, guard *append-only*, dan status enum PHP.
- **Reports (Query Objects):** Single Source of Truth query laporan untuk tampilan layar, ekspor PDF, dan ekspor Excel (BR-31).

## 3. Komponen Inti
- `StockService`: Satu-satunya gerbang penulisan kolom `products.current_stock` dan pembuatan baris `stock_movements`.
- `DocumentNumberService`: Pembangkit nomor dokumen transaksi atomik berbasis tabel `document_sequences` dengan *row lock*.
- `ActivityLogger`: Pencatat rekam jejak aktivitas sistem yang bersifat *append-only*.

## 4. Alur Posting Stok dan Konkurensi `[DIRENCANAKAN]`
Setiap perubahan stok mengikuti algoritma posting terpusat pada `StockService` dengan tahapan:
1. `DB::beginTransaction()` dengan retry deadlock maksimal 3 kali.
2. Lock header dokumen transaksi (`FOR UPDATE`).
3. Lock baris `products` terkait dengan urutan ID naik (`ORDER BY id ASC FOR UPDATE`) untuk mencegah *deadlock*.
4. Validasi ketersediaan stok dan kelayakan status barang.
5. Perbarui `products.current_stock`, catat `stock_movements`, ubah status dokumen, dan catat `activity_logs`.
6. `DB::commit()`.
