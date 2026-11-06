# Skema Database — StockHub

| Atribut | Nilai |
|---|---|
| Tujuan | Mendokumentasikan rancangan ERD, definisi seluruh tabel, kolom, tipe data, constraint, indeks, strategi migrasi, dan seeder aktual. |
| Cakupan | Seluruh tabel MVP dan framework pada MySQL 8.0 InnoDB; tidak mencakup multi-gudang (Later). |
| Audiens | Developer, Database Administrator (DBA), QA Engineer, Backend Lead |
| Status | [SUDAH DIIMPLEMENTASI] (Tahap 1 — Database MVP) |
| Versi aplikasi | 1.0.0 |
| Terakhir diperbarui | 2026-11-06 |
| Pemilik | Backend Lead |
| Terkait | `prd.md` §11, §17.5, §18.3; `docs/architecture.md`; `docs/business-rules.md` |

---

## 1. Ketentuan Skema Umum

- **Storage Engine:** InnoDB pada seluruh tabel aplikasi.
- **Karakter Set & Collation:** `utf8mb4` dengan collation `utf8mb4_unicode_ci` (perbandingan case-insensitive).
- **Primary Key:** `BIGINT UNSIGNED AUTO_INCREMENT` bernama `id` (kecuali tabel `settings` yang menggunakan string `key`).
- **Foreign Key:** Dideklarasikan eksplisit dengan aturan `ON DELETE RESTRICT` (menegakkan BR-03 bahwa data master dan riwayat transaksi tidak dihapus).
- **Presisi Numerik:** Kuantitas barang memakai `DECIMAL(15,3)`, sedangkan nilai uang/harga memakai `DECIMAL(15,2)` (PRD A-13).
- **Enforcing Invarian:** Menggunakan CHECK constraint MySQL (≥ 8.0.16) untuk mencegah nilai negatif, transaksi kuantitas nol, formula selisih salah, dan ketidaksesuaian relasi.
- **Append-Only History:** Tabel `stock_movements` dan `activity_logs` hanya memiliki kolom `created_at` (tanpa `updated_at`) serta dilindungi oleh model guard Eloquent (BR-15).

---

## 2. Entity Relationship Diagram (ERD)

```mermaid
erDiagram
    users ||--o{ stock_ins : created_by
    users ||--o{ stock_outs : created_by
    users ||--o{ stock_adjustments : created_by
    users ||--o{ stock_movements : user_id
    users |o--o{ activity_logs : user_id
    users ||--o{ settings : updated_by
    roles ||--o{ users : "model_has_roles"
    permissions ||--o{ roles : "role_has_permissions"
    categories ||--o{ products : category_id
    units ||--o{ products : unit_id
    suppliers |o--o{ products : main_supplier_id
    suppliers |o--o{ stock_ins : supplier_id
    stock_ins ||--|{ stock_in_details : stock_in_id
    stock_outs ||--|{ stock_out_details : stock_out_id
    products ||--o{ stock_in_details : product_id
    products ||--o{ stock_out_details : product_id
    products ||--o{ stock_adjustments : product_id
    products ||--o{ stock_movements : product_id

    users {
        bigint unsigned id PK
        string name "100"
        string email "150 UK"
        string password "255"
        boolean is_active "default 1"
        boolean must_change_password "default 0"
        datetime last_login_at "nullable"
        string last_login_ip "45, nullable"
        timestamp created_at
        timestamp updated_at
    }

    categories {
        bigint unsigned id PK
        string name "100 UK"
        string description "500, nullable"
        boolean is_active "default 1"
        timestamp created_at
        timestamp updated_at
    }

    units {
        bigint unsigned id PK
        string name "50 UK"
        string symbol "10 UK"
        string description "255, nullable"
        boolean allow_decimal "default 0"
        boolean is_active "default 1"
        timestamp created_at
        timestamp updated_at
    }

    suppliers {
        bigint unsigned id PK
        string code "20 UK"
        string name "150"
        string contact_name "100, nullable"
        string phone "30, nullable"
        string email "150, nullable"
        text address "nullable"
        text notes "nullable"
        boolean is_active "default 1"
        timestamp created_at
        timestamp updated_at
    }

    products {
        bigint unsigned id PK
        string code "30 UK"
        string name "150"
        bigint unsigned category_id FK
        bigint unsigned unit_id FK
        bigint unsigned main_supplier_id FK "nullable"
        decimal purchase_price "15,2 default 0"
        decimal selling_price "15,2 nullable"
        decimal current_stock "15,3 default 0"
        decimal min_stock "15,3 default 0"
        string storage_location "100, nullable"
        text description "nullable"
        string photo_path "255, nullable"
        boolean is_active "default 1"
        bigint unsigned created_by FK "nullable"
        bigint unsigned updated_by FK "nullable"
        timestamp created_at
        timestamp updated_at
    }

    stock_ins {
        bigint unsigned id PK
        string number "30 UK"
        date transaction_date
        string source_type "20"
        bigint unsigned supplier_id FK "nullable"
        string reference_document "100, nullable"
        text notes "nullable"
        string status "20 default draft"
        decimal total_amount "15,2 default 0"
        bigint unsigned created_by FK
        bigint unsigned confirmed_by FK "nullable"
        bigint unsigned cancelled_by FK "nullable"
        datetime confirmed_at "nullable"
        datetime cancelled_at "nullable"
        string cancel_reason "255, nullable"
        timestamp created_at
        timestamp updated_at
    }

    stock_in_details {
        bigint unsigned id PK
        bigint unsigned stock_in_id FK
        bigint unsigned product_id FK
        decimal quantity "15,3"
        decimal unit_price "15,2 default 0"
        timestamp created_at
        timestamp updated_at
    }

    stock_outs {
        bigint unsigned id PK
        string number "30 UK"
        date transaction_date
        string purpose_type "20"
        string destination "150"
        text notes "nullable"
        string status "20 default draft"
        bigint unsigned created_by FK
        bigint unsigned confirmed_by FK "nullable"
        bigint unsigned cancelled_by FK "nullable"
        datetime confirmed_at "nullable"
        datetime cancelled_at "nullable"
        string cancel_reason "255, nullable"
        timestamp created_at
        timestamp updated_at
    }

    stock_out_details {
        bigint unsigned id PK
        bigint unsigned stock_out_id FK
        bigint unsigned product_id FK
        decimal quantity "15,3"
        timestamp created_at
        timestamp updated_at
    }

    stock_adjustments {
        bigint unsigned id PK
        string number "30 UK"
        date adjustment_date
        bigint unsigned product_id FK
        decimal system_qty "15,3"
        decimal physical_qty "15,3"
        decimal difference "15,3"
        string reason_code "30"
        text notes
        bigint unsigned created_by FK
        timestamp created_at
        timestamp updated_at
    }

    stock_movements {
        bigint unsigned id PK
        bigint unsigned product_id FK
        string movement_type "20"
        string reference_type "30, nullable"
        bigint unsigned reference_id "nullable"
        string reference_number "30, nullable"
        date movement_date
        decimal qty_before "15,3"
        decimal qty_change "15,3"
        decimal qty_after "15,3"
        bigint unsigned user_id FK
        string notes "255, nullable"
        timestamp created_at
    }

    activity_logs {
        bigint unsigned id PK
        bigint unsigned user_id FK "nullable"
        string action "60"
        string description "255"
        string subject_type "30, nullable"
        bigint unsigned subject_id "nullable"
        json old_values "nullable"
        json new_values "nullable"
        string ip_address "45, nullable"
        string user_agent "255, nullable"
        timestamp created_at
    }

    document_sequences {
        bigint unsigned id PK
        string sequence_key "40 UK"
        int unsigned last_number "default 0"
        timestamp created_at
        timestamp updated_at
    }

    settings {
        string key PK "100"
        text value "nullable"
        bigint unsigned updated_by FK "nullable"
        timestamp updated_at "nullable"
    }
```

---

## 3. Definisi dan Spesifikasi Tabel Aktual

### 3.1 `users`
Tabel akun pengguna sistem (diautentikasi via Laravel Breeze + Spatie Roles).
- **Kolom:** `id`, `name` (100), `email` (150), `password` (255), `is_active` (boolean, default 1), `must_change_password` (boolean, default 0), `last_login_at` (datetime), `last_login_ip` (varchar 45), `remember_token` (varchar 100), `created_at`, `updated_at`.
- **Indeks & Constraint:** `UNIQUE(email)`, `INDEX(is_active)`.

### 3.2 `categories`
Kategori pengelompokan barang.
- **Kolom:** `id`, `name` (100), `description` (500), `is_active` (boolean, default 1), `created_at`, `updated_at`.
- **Indeks & Constraint:** `UNIQUE(name)`.

### 3.3 `units`
Satuan kuantitas barang beserta penanda aturan desimal (BR-01, BR-25).
- **Kolom:** `id`, `name` (50), `symbol` (10), `description` (255), `allow_decimal` (boolean, default 0), `is_active` (boolean, default 1), `created_at`, `updated_at`.
- **Indeks & Constraint:** `UNIQUE(name)`, `UNIQUE(symbol)`.

### 3.4 `suppliers`
Data pemasok barang.
- **Kolom:** `id`, `code` (20), `name` (150), `contact_name` (100), `phone` (30), `email` (150), `address` (text), `notes` (text), `is_active` (boolean, default 1), `created_at`, `updated_at`.
- **Indeks & Constraint:** `UNIQUE(code)`, `INDEX(name)`, `INDEX(is_active)`.

### 3.5 `products`
Master data barang dan saldo stok terkini (`current_stock`).
- **Kolom:** `id`, `code` (30), `name` (150), `category_id` (FK), `unit_id` (FK), `main_supplier_id` (FK nullable), `purchase_price` (DECIMAL 15,2), `selling_price` (DECIMAL 15,2 nullable), `current_stock` (DECIMAL 15,3), `min_stock` (DECIMAL 15,3), `storage_location` (100), `description` (text), `photo_path` (255), `is_active` (boolean, default 1), `created_by` (FK nullable), `updated_by` (FK nullable), `created_at`, `updated_at`.
- **Indeks & Constraint:**
  - `UNIQUE(code)`
  - `INDEX(name)`
  - `INDEX(category_id, is_active)`
  - `INDEX(is_active, current_stock)`
  - `CHECK (purchase_price >= 0)`
  - `CHECK (selling_price IS NULL OR selling_price >= 0)`
  - `CHECK (current_stock >= 0)` (BR-02, G-02)
  - `CHECK (min_stock >= 0)`

### 3.6 `stock_ins` & `stock_in_details`
Dokumen transaksi penerimaan barang dan baris rincian item.
- **Header (`stock_ins`):** `id`, `number` (30 UK), `transaction_date` (date), `source_type` (varchar 20), `supplier_id` (FK nullable), `reference_document` (100), `notes` (text), `status` (varchar 20, default draft), `total_amount` (DECIMAL 15,2), `created_by` (FK), `confirmed_by` (FK), `cancelled_by` (FK), `confirmed_at` (datetime), `cancelled_at` (datetime), `cancel_reason` (255), `created_at`, `updated_at`.
  - Constraints: `CHECK (source_type <> 'purchase' OR supplier_id IS NOT NULL)` (BR-22), `CHECK (total_amount >= 0)`, `CHECK (source_type IN ('purchase', 'internal', 'return', 'other'))`, `CHECK (status IN ('draft', 'confirmed', 'cancelled'))`.
  - Indeks: `INDEX(status, transaction_date)`, `INDEX(supplier_id)`, `INDEX(created_by, status)`.
- **Detail (`stock_in_details`):** `id`, `stock_in_id` (FK), `product_id` (FK), `quantity` (DECIMAL 15,3), `unit_price` (DECIMAL 15,2), `created_at`, `updated_at`.
  - Constraints: `UNIQUE(stock_in_id, product_id)` (BR-11), `CHECK (quantity > 0)`, `CHECK (unit_price >= 0)`.
  - Indeks: `INDEX(product_id)`.

### 3.7 `stock_outs` & `stock_out_details`
Dokumen transaksi pengeluaran barang dan baris rincian item.
- **Header (`stock_outs`):** `id`, `number` (30 UK), `transaction_date` (date), `purpose_type` (varchar 20), `destination` (150), `notes` (text), `status` (varchar 20, default draft), `created_by` (FK), `confirmed_by` (FK), `cancelled_by` (FK), `confirmed_at` (datetime), `cancelled_at` (datetime), `cancel_reason` (255), `created_at`, `updated_at`.
  - Constraints: `CHECK (purpose_type IN ('usage', 'sale', 'shipment', 'transfer_to_party', 'other'))`, `CHECK (status IN ('draft', 'confirmed', 'cancelled'))`.
  - Indeks: `INDEX(status, transaction_date)`, `INDEX(created_by, status)`.
- **Detail (`stock_out_details`):** `id`, `stock_out_id` (FK), `product_id` (FK), `quantity` (DECIMAL 15,3), `created_at`, `updated_at`.
  - Constraints: `UNIQUE(stock_out_id, product_id)` (BR-11), `CHECK (quantity > 0)`.
  - Indeks: `INDEX(product_id)`.

### 3.8 `stock_adjustments`
Dokumen penyesuaian stok opname per barang fisik vs sistem.
- **Kolom:** `id`, `number` (30 UK), `adjustment_date` (date), `product_id` (FK), `system_qty` (DECIMAL 15,3), `physical_qty` (DECIMAL 15,3), `difference` (DECIMAL 15,3), `reason_code` (varchar 30), `notes` (text), `created_by` (FK), `created_at`, `updated_at`.
- **Indeks & Constraint:**
  - `CHECK (system_qty >= 0)`
  - `CHECK (physical_qty >= 0)`
  - `CHECK (difference <> 0)` (BR-16)
  - `CHECK (difference = physical_qty - system_qty)`
  - `CHECK (reason_code IN ('damaged', 'lost', 'recording_error', 'count_error', 'expired', 'found', 'other'))`
  - Indeks: `INDEX(adjustment_date)`, `INDEX(product_id, adjustment_date)`, `INDEX(created_by)`.

### 3.9 `stock_movements` (Append-Only)
Buku besar permanen riwayat perubahan stok barang.
- **Kolom:** `id` (urutan posting), `product_id` (FK), `movement_type` (varchar 20), `reference_type` (varchar 30), `reference_id` (bigint), `reference_number` (varchar 30), `movement_date` (date), `qty_before` (DECIMAL 15,3), `qty_change` (DECIMAL 15,3), `qty_after` (DECIMAL 15,3), `user_id` (FK), `notes` (255), `created_at` (timestamp).
- **Indeks & Constraint:**
  - `CHECK (movement_type IN ('opening_balance', 'stock_in', 'stock_out', 'adjustment', 'stock_in_cancel', 'stock_out_cancel'))`
  - `CHECK (qty_before >= 0)`
  - `CHECK (qty_change <> 0)`
  - `CHECK (qty_after >= 0)`
  - `CHECK (qty_after = qty_before + qty_change)`
  - Indeks: `INDEX(product_id, id)`, `INDEX(movement_date)`, `INDEX(movement_type, movement_date)`, `INDEX(reference_type, reference_id)`, `INDEX(user_id)`.

### 3.10 `activity_logs` (Append-Only)
Jejak audit operasional dan keamanan sistem.
- **Kolom:** `id`, `user_id` (FK nullable), `action` (60), `description` (255), `subject_type` (30), `subject_id` (bigint), `old_values` (json), `new_values` (json), `ip_address` (45), `user_agent` (255), `created_at` (timestamp).
- **Indeks:** `INDEX(user_id, created_at)`, `INDEX(action, created_at)`, `INDEX(subject_type, subject_id)`, `INDEX(created_at)`.

### 3.11 `document_sequences`
Pembangkit nomor dokumen berurutan dengan row locking atomik (BR-19, BR-34).
- **Kolom:** `id`, `sequence_key` (varchar 40 UK), `last_number` (int unsigned default 0), `created_at`, `updated_at`.

### 3.12 `settings`
Tabel pengaturan konfigurasi aplikasi.
- **Kolom:** `key` (varchar 100 PK), `value` (text nullable), `updated_by` (FK nullable), `updated_at` (timestamp nullable).

---

## 4. Seeder dan Data Inisialisasi

1. **`RoleAndPermissionSeeder` (Produksi & Dev):** Mendaftarkan 26 permission resmi dan mengikatkannya ke 4 peran tetap (`super_admin`, `admin_gudang`, `staff_gudang`, `manajer`). Bersifat idempoten.
2. **`UnitSeeder` (Produksi & Dev):** Mendaftarkan 9 satuan dasar (5 satuan bulat, 4 satuan desimal). Bersifat idempoten.
3. **`SettingSeeder` (Produksi & Dev):** Mendaftarkan konfigurasi default (`company_name`, `max_backdate_days=7`). Bersifat idempoten.
4. **`InitialSuperAdminSeeder` (Produksi & Dev):** Membaca `INITIAL_ADMIN_EMAIL`, membangkitkan password acak sekali pakai di konsol, menetapkan `must_change_password = true`, dan memberikan role `super_admin`. Tidak ada password tersimpan di repositori.
5. **`DemoSeeder` (Hanya Local/Testing):** Mengisi 6 kategori contoh, 2 pemasok contoh, 4 barang contoh dengan `current_stock = 0.000` (sesuai aturan BR-06), dan akun demo untuk AG, SG, MG. Otomatis menolak dieksekusi di `production`.

---

## 5. Perintah Pengelolaan Basis Data

```bash
# Menjalankan migrasi baru
php artisan migrate

# Mengisi seluruh seeder default (idempoten)
php artisan db:seed

# Reset total dan migrasi ulang pada basis data testing
php artisan migrate:fresh --seed
```
