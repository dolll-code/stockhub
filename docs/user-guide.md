# Panduan Pengguna — StockHub

| Atribut | Nilai |
|---|---|
| Tujuan | Memberikan instruksi operasional penggunaan aplikasi StockHub per peran pengguna dan per modul. |
| Cakupan | Panduan antarmuka web untuk peran Super Admin, Admin Gudang, Staff Gudang, dan Manajer. |
| Audiens | Pengguna Akhir, Administrator Sistem, QA Tester |
| Status | [SUDAH DIIMPLEMENTASI] (Sebagian — Modul Autentikasi & Akun Pengguna Tahap 2) |
| Versi aplikasi | 1.0.0 |
| Terakhir diperbarui | 2026-11-06 |
| Pemilik | QA Lead & Product Owner |
| Terkait | `prd.md` §5, §8, §13; `docs/authentication.md` |

---

## 1. Masuk ke Sistem (Login)

1. Buka URL aplikasi pada peramban web (`http://localhost/login`).
2. Masukkan alamat email terdaftar dan kata sandi Anda.
3. Klik tombol **Masuk ke Sistem**.
4. **Catatan Keamanan:**
   - Tidak tersedia registrasi akun mandiri. Akun hanya didaftarkan oleh Super Admin.
   - Percobaan masuk dibatasi maksimal 5 kali per menit. Percobaan ke-6 akan ditahan selama 60 detik dengan hitung mundur di layar.
   - Jika akun Anda berstatus nonaktif, sistem akan menampilkan pesan kesalahan yang sama demi alasan keamanan.

---

## 2. Penggantian Kata Sandi Sementara (Login Pertama)

Bagi pengguna baru atau akun yang kata sandinya baru direset oleh Super Admin:
1. Setelah login berhasil, sistem secara otomatis mengarahkan Anda ke layar **Ganti Kata Sandi Sementara**.
2. Masukkan kata sandi baru yang memenuhi kriteria:
   - Minimal 10 karakter.
   - Mengandung minimal satu huruf besar (`A-Z`).
   - Mengandung minimal satu huruf kecil (`a-z`).
   - Mengandung minimal satu angka (`0-9`).
   - Tidak boleh sama dengan alamat email Anda.
3. Ulangi kata sandi baru pada kolom konfirmasi.
4. Klik **Simpan Kata Sandi Baru**. Anda akan langsung diarahkan ke halaman Ringkasan Dasbor.

---

## 3. Pengelolaan Profil dan Kata Sandi Mandiri

Setiap pengguna yang telah masuk dapat mengelola profil pribadi:
1. Klik nama pengguna pada pojok kanan atas bilah navigasi, lalu pilih **Profil Saya** (atau buka `/profile`).
2. **Informasi Profil:** Ubah nama lengkap atau alamat email, lalu klik **Simpan Profil**.
3. **Ubah Kata Sandi:**
   - Masukkan kata sandi Anda saat ini.
   - Masukkan kata sandi baru dan konfirmasinya.
   - Klik **Simpan Kata Sandi**.
   - *Penting:* Tindakan ini secara otomatis mengakhiri (*logout*) sesi aktif Anda pada perangkat lain.

---

## 4. Manajemen Pengguna (Khusus Super Admin)

Super Admin memiliki menu **Data Pengguna** (`/users`) pada bilah sisi navigasi:

### 4.1 Melihat dan Memfilter Daftar Pengguna
- **Pencarian:** Masukkan nama atau email pada kotak pencarian untuk menemukan pengguna.
- **Filter:** Saring daftar berdasarkan Peran (*Super Admin*, *Admin Gudang*, *Staff Gudang*, *Manajer*) dan Status (*Aktif*, *Nonaktif*).

### 4.2 Menambah Pengguna Baru
1. Klik tombol **Tambah Pengguna** di pojok kanan atas.
2. Isi nama lengkap dan alamat email unik.
3. Pilih salah satu peran yang sesuai.
4. Masukkan kata sandi sementara yang kuat.
5. Klik **Simpan Pengguna**. Pengguna akan otomatis berstatus wajib mengganti kata sandi saat pertama kali login.

### 4.3 Mengubah Data Pengguna
1. Klik tombol **Ubah** pada baris pengguna yang dituju.
2. Ubah nama, email, atau peran sesuai kebutuhan.
3. Klik **Simpan Perubahan**.
4. *Batasan (BR-24):* Pengguna tidak dapat mengubah peran akunnya sendiri. Super Admin terakhir yang aktif tidak dapat diturunkan perannya.

### 4.4 Mengaktifkan atau Menonaktifkan Akun
1. Klik tombol **Nonaktifkan** pada pengguna yang bersangkutan.
2. Konfirmasi tindakan pada dialog peramban. Sesi aktif pengguna tersebut akan langsung diputus.
3. Untuk mengaktifkan kembali, klik tombol **Aktifkan**.
4. *Batasan (BR-24):* Anda tidak dapat menonaktifkan akun Anda sendiri, dan Super Admin aktif terakhir tidak dapat dinonaktifkan.

### 4.5 Reset Kata Sandi Pengguna
1. Klik tombol **Reset Password** pada baris pengguna.
2. Masukkan kata sandi sementara baru pada jendela sembul (*modal*).
3. Klik **Tetapkan Password**. Seluruh sesi aktif pengguna tersebut akan diputus dan ia wajib mengganti password saat login kembali.

---

## 5. Melihat Matriks Peran dan Hak Akses

Super Admin dapat membuka menu **Peran & Hak Akses** (`/roles`) untuk melihat tabel referensi 26 permission terhadap 4 peran sistem secara *read-only*.
