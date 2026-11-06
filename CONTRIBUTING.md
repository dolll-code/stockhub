# Panduan Kontribusi — StockHub

Dokumen ini menjelaskan alur kerja, standar kode, konvensi Git, dan Definition of Done (DoD) untuk pengembangan proyek StockHub.

---

## 1. Alur Kerja Git dan Percabangan (Branching)

- **`main` / `master`**: Branch produksi/staging yang stabil dan selalu lulus CI.
- **`feature/<nama-fitur>`**: Branch untuk pengembangan fungsionalitas baru (misal: `feature/auth-matrix`, `feature/stock-service`).
- **`fix/<deskripsi-bug>`**: Branch untuk perbaikan cacat sistem (misal: `fix/rounding-decimal-qty`).
- **`chore/<tugas-pemeliharaan>`**: Branch untuk pembaruan dependensi, konfigurasi CI, atau refaktor internal (misal: `chore/ci-matrix`).

Semua pekerjaan wajib dibuat dalam branch terpisah dan digabungkan melalui Pull Request (PR). Jangan pernah melakukan *direct push* ke branch utama.

---

## 2. Konvensi Pesan Commit (Conventional Commits)

Format pesan commit mengikuti standar berikut:
```text
<tipe>(<scope-opsional>): <deskripsi singkat imperatif> [ID-Terkait]

[penjelasan lebih detail jika diperlukan]
```

Contoh tipe:
- `feat`: Penambahan fitur baru (misal: `feat(stock): implementasi row locking pada transaksi keluar (FR-STO-03, BR-09)`)
- `fix`: Perbaikan bug (misal: `fix(auth): cegah penonaktifan super admin terakhir (BR-24)`)
- `docs`: Penambahan atau pembaruan dokumentasi (misal: `docs: perbarui matriks izin pada docs/authentication.md`)
- `test`: Penambahan atau perbaikan pengujian (misal: `test(concurrency): tambahkan uji konkurensi stok negatif (TC-CON-02)`)
- `refactor`: Perubahan struktur kode tanpa mengubah fungsionalitas
- `chore`: Tugas rutin konfigurasi build, CI, atau dependensi

---

## 3. Standar Kualitas Kode

Sebelum membuka Pull Request, developer wajib memastikan:
1. **Linter:** `composer lint:fix` (Laravel Pint) dieksekusi dan tidak ada pelanggaran gaya kode.
2. **Analisis Statis:** `composer analyse` (Larastan / PHPStan Level ≥ 5) lulus tanpa error.
3. **Pengujian:** Seluruh pengujian Pest lulus pada basis data MySQL:
   ```bash
   composer test
   ```
4. **Build Aset:** Vite build sukses tanpa warning/error:
   ```bash
   npm run build
   ```
5. **Keamanan:** Audit dependensi bersih dari celah kritis:
   ```bash
   composer audit
   npm audit
   ```

---

## 4. Definition of Done (DoD) per Pull Request

Setiap Pull Request wajib memenuhi kriteria berikut (PRD §19.9):
- [ ] Acceptance Criteria (AC) terkait terpenuhi dan seluruh Test Case (TC) lulus.
- [ ] Seluruh endpoint atau rute baru/diubah diuji otorisasi terhadap 4 peran (*Super Admin*, *Admin Gudang*, *Staff Gudang*, *Manajer*).
- [ ] Skema migration dapat dijalankan pada database bersih maupun peningkatan dari rilis sebelumnya.
- [ ] Dokumentasi terkait diperbarui sesuai aturan pemicu PRD §19.8:
  - Perubahan fitur/perilaku: `docs/business-rules.md`, `docs/user-guide.md`, `docs/api.md`, `docs/testing.md`.
  - Perubahan skema DB: `docs/database.md`.
  - Perubahan otorisasi/peran: `docs/authentication.md`.
  - Perubahan konfigurasi/env: `.env.example`, `README.md`.
- [ ] Entri catatan perubahan ditambahkan pada bagian `[Unreleased]` di `docs/changelog.md`.
- [ ] Tidak ada credential nyata, token, password, atau API key yang dimasukkan ke dalam kode atau dokumentasi.
- [ ] Perubahan UI diuji terhadap navigasi keyboard, kontras warna, dan tampilan responsif (mobile 360px hingga desktop).
- [ ] Seluruh cek CI GitHub Actions berstatus hijau.
- [ ] Minimal satu reviewer menyetujui Pull Request.
