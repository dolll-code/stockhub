## Ringkasan
<!-- Jelaskan apa yang diubah dan alasan perubahan. Sebutkan ID terkait: FR-..., BR-..., AC-..., TC-... -->

## Kategori Perubahan
- [ ] Fitur Baru (`feat`)
- [ ] Perbaikan Bug (`fix`)
- [ ] Dokumentasi (`docs`)
- [ ] Pembaruan Pengujian (`test`)
- [ ] Refaktor Kode (`refactor`)
- [ ] Konfigurasi / Dependensi (`chore`)

## Checklist Kualitas & Definition of Done
- [ ] Pengujian baru/relevan ditulis dan seluruh test suite Pest lulus (`composer test`)
- [ ] Kode lulus linter Laravel Pint tanpa error (`composer lint`)
- [ ] Kode lulus analisis statis Larastan Level ≥ 5 (`composer analyse`)
- [ ] Otorisasi backend diuji untuk seluruh peran pengguna terkait
- [ ] Skema migration diuji pada database kosong dan database rilis sebelumnya
- [ ] Build aset Vite berhasil (`npm run build`)
- [ ] Dokumentasi teknis di `docs/` diperbarui sesuai aturan pemicu PRD §19.8
- [ ] Catatan perubahan ditambahkan pada `docs/changelog.md` bagian `[Unreleased]`
- [ ] Matriks traceability diperbarui bila ada ID requirement baru atau berubah
- [ ] Bebas dari credential nyata, secret, password, atau API key
- [ ] Navigasi keyboard, kontras warna, dan responsivitas UI diverifikasi (jika ada perubahan tampilan)
