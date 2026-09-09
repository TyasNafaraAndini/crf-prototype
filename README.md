# Prototype Change Request Form (CRF) — PT Persona Prima Utama

Prototype standalone untuk menguji alur:
**Form CRF → Submit → Data tersimpan → Admin memproses → Status Solve/Cancel**

Belum terintegrasi dengan SIAP PPU. Tidak ada halaman login — sistem
langsung mengarahkan ke Form CRF, dengan `user_id = 1` disimulasikan
sebagai user yang sedang login.

Dibangun dengan **PHP Native + MySQL + Bootstrap 5** (tanpa framework
backend), sesuai spesifikasi.

---

## 1. Cara Instalasi

**Prasyarat:** XAMPP (Apache + MySQL + PHP 8.x) sudah terpasang.

1. Salin folder `crf-prototype/` ke dalam folder `htdocs` XAMPP, misalnya:
   ```
   C:\xampp\htdocs\crf-prototype\        (Windows)
   /Applications/XAMPP/htdocs/crf-prototype/   (Mac)
   /opt/lampp/htdocs/crf-prototype/      (Linux)
   ```
2. Jalankan **Apache** dan **MySQL** dari XAMPP Control Panel.
3. Buka **phpMyAdmin** ( `http://localhost/phpmyadmin` ).
4. Klik tab **Import** → pilih file `database.sql` → klik **Go**.
   File ini akan otomatis:
   - Membuat database `crf_prototype`
   - Membuat tabel `users`, `change_requests`, `attachments`
   - Mengisi 2 data user dummy (1 user biasa, 1 admin)
5. Pastikan folder `uploads/` dapat ditulisi oleh web server
   (biasanya sudah otomatis bisa di XAMPP default; jika perlu, beri
   permission `755` atau `775` di Mac/Linux).
6. Jika konfigurasi MySQL kamu bukan default (`root` tanpa password),
   sesuaikan di `config/database.php`.

## 2. Cara Menjalankan

Buka browser dan akses:

```
http://localhost/crf-prototype/
```

Anda akan langsung diarahkan ke **Form CRF** (`user/form_crf.php`).

Untuk membuka **Dashboard Admin**:

```
http://localhost/crf-prototype/admin/dashboard.php
```

(Prototype ini belum memisahkan akses user/admin dengan login — kedua
halaman bisa diakses langsung untuk keperluan pengujian alur.)

## 3. Cara Testing (Skenario Alur Prototype)

Ikuti langkah ini untuk memverifikasi seluruh alur berjalan
(lihat juga brief butir 32):

1. Buka `http://localhost/crf-prototype/` → Form CRF langsung tampil.
2. Perhatikan field **Dari**, **Hari/Tanggal**, dan **Nomor Register**
   sudah otomatis terisi (readonly, diambil dari database/sistem).
3. Isi **Rincian Permohonan Perubahan**, **Benefit**, **Dampak**, dan
   **Alasan**.
4. (Opsional) Upload 1–2 file bukti pendukung.
5. Pilih salah satu **Biaya/Anggaran** dan isi nominalnya.
6. Pilih **Kategori Perubahan** (coba juga pilih "Lainnya" untuk
   melihat field Detail Kategori menjadi wajib).
7. Klik **Simpan Draft** — coba dengan beberapa field kosong, harus
   tetap tersimpan dengan status **Draft**.
8. Kembali ke Form CRF, isi ulang field yang wajib, lalu klik
   **Submit CRF** — jika ada field wajib yang kosong, browser akan
   menahan submit (validasi HTML5); jika lengkap, akan muncul pesan
   sukses beserta Nomor Register.
9. Buka **Dashboard Admin** — pastikan data CRF yang baru saja dibuat
   muncul di tabel, termasuk yang berstatus Draft.
10. Coba fitur **Search** (cari berdasarkan Nomor Register atau nama
    pengaju) dan **Filter Status/Kategori**.
11. Klik **Detail** pada salah satu baris → pastikan seluruh isi
    pengajuan tampil dengan benar, termasuk file lampiran (bisa
    diklik untuk dibuka).
12. Klik **Kelola CRF** → tentukan **Level Complain**, lalu ubah
    **Status** menjadi **Solve** → akan muncul dialog konfirmasi →
    setelah disimpan, status tampil sebagai **Selesai** dan waktu
    `solved_at` tercatat.
13. Ulangi untuk salah satu CRF lain dengan memilih status
    **Cancel** → tampil sebagai **Dibatalkan** dengan `cancelled_at`
    tercatat.
14. Kembali ke Dashboard Admin dan pastikan badge Level/Status pada
    tabel sudah sesuai dengan perubahan yang baru dilakukan.

---

## 4. Struktur Folder & Penjelasan File

```
crf-prototype/
│
├── config/
│   └── database.php        Koneksi PDO ke MySQL + set timezone Asia/Jakarta
│
├── includes/
│   ├── session.php         Simulasi user aktif (user_id = 1) + fungsi h()
│   ├── functions.php       Generator Nomor Register, format tanggal/rupiah,
│   │                       label kategori/status/level, handler upload file
│   ├── header.php          Head HTML + navbar (dipakai semua halaman)
│   └── footer.php          Footer HTML + script Bootstrap/JS
│
├── user/
│   └── form_crf.php        Halaman utama: Form CRF (8 section sesuai brief)
│
├── admin/
│   ├── dashboard.php       Daftar semua CRF + search + filter status/kategori
│   ├── detail.php          Detail lengkap 1 CRF (read-only) + daftar lampiran
│   └── edit.php            Form admin: Level Complain, Status, PIR, Implementasi
│
├── actions/
│   ├── submit_crf.php      Proses tombol "Submit CRF" (validasi wajib + insert)
│   ├── save_draft.php      Proses tombol "Simpan Draft" (insert tanpa validasi wajib)
│   └── update_crf.php      Proses form admin/edit.php (update level/status/PIR/implementasi)
│
├── uploads/                 Folder penyimpanan file lampiran (+ .htaccess pengaman)
│
├── assets/
│   ├── css/style.css        Desain (putih/biru/abu-abu, kartu per section, badge)
│   └── js/script.js         Interaksi form: detail kategori dinamis, preview file,
│                             toggle nominal anggaran, konfirmasi ubah status
│
├── database.sql             Skema database + data dummy (users)
└── index.php                Redirect ke user/form_crf.php
```

### A. Struktur Database

**users** — data user (dummy, belum ada login sesungguhnya)
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | INT PK | |
| name, phone, email | VARCHAR | |
| department, division | VARCHAR | dipakai untuk field "Dari" |
| role | ENUM(user, admin) | |

**change_requests** — satu baris = satu pengajuan CRF
Berisi seluruh field form (lihat `database.sql` untuk daftar lengkap),
termasuk `request_number` (unik, auto), `status`
(Draft/Dalam Proses/Solve/Cancel), `level` (Kecil/Sedang/Tinggi), serta
`solved_at`/`cancelled_at`.

**attachments** — file bukti pendukung, banyak-ke-satu terhadap
`change_requests` (satu CRF bisa punya banyak lampiran).

### B. Relasi Tabel

```
users (1) ───< (banyak) change_requests
change_requests (1) ───< (banyak) attachments
```

### C. Struktur Halaman

```
index.php  ──►  user/form_crf.php  (Form CRF, tanpa login)
                     │
                     ▼ (submit / simpan draft)
            admin/dashboard.php  (daftar semua CRF)
                     │
                     ▼ (klik Detail)
            admin/detail.php  (rincian lengkap, read-only)
                     │
                     ▼ (klik Kelola CRF)
            admin/edit.php  (ubah Level, Status, PIR, Implementasi)
```

### D. Workflow Sistem

```
User buka Form CRF
   → Field otomatis terisi (Tanggal, Dari, Nomor Register, Kepada)
   → User isi detail CRF + upload bukti (opsional)
   → [Simpan Draft]  →  status = Draft
   → [Submit CRF]    →  validasi → status = Dalam Proses
        → muncul di Dashboard Admin
        → Admin buka Detail → Kelola CRF
        → Admin tentukan Level Complain
        → Admin ubah Status → Solve (Selesai) / Cancel (Dibatalkan)
```

### E. Struktur Folder

Lihat diagram folder di atas — mengikuti persis struktur yang diminta
pada brief (config/, includes/, user/, admin/, actions/, uploads/,
assets/, database.sql, index.php).

---

## 5. Catatan Penting

- **Nomor Register**: format `PPU-02.4.XXXX.MM.YY` dipertahankan sesuai
  contoh dokumen perusahaan. Segmen `02` dan `4` tidak diberi arti
  khusus (dokumen asli tidak menjelaskannya) — yang dibuat dinamis
  hanya 4 digit urut serta bulan/tahun pengajuan.
- **Level Complain** (Kecil/Sedang/Tinggi) pada prototype ini berbeda
  dari **Level Urgensi** (Tinggi/Normal/Rendah) yang ada di dokumen
  CRF resmi perusahaan — keduanya sengaja dipisahkan sesuai instruksi.
- Fitur yang **sengaja tidak dibuat** pada prototype ini (scope
  pengembangan berikutnya): login CRF/SIAP PPU, dashboard user,
  integrasi Help Desk, SSO, notifikasi WhatsApp/email, approval
  multi-level, dan workflow Incident/Non-IT.
- Validasi keamanan dasar sudah diterapkan: prepared statements (PDO),
  escaping output (`h()`), validasi tipe/ukuran file upload, folder
  `uploads/` diberi `.htaccess` supaya file yang diupload tidak bisa
  dieksekusi sebagai script, dan pesan error database tidak
  ditampilkan mentah ke user.
