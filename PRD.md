# Product Requirements Document (PRD)

## HRIS Norton Bali Computer — Absensi Pengenalan Wajah, Cuti, dan Jadwal Kerja

Version: 1.0
Status: Active
Pemilik Produk: Tim pengembang (internal), klien: Norton Bali Computer
Dasar: Skripsi Krismayanti (2026) + kondisi implementasi aktual (`repomix-output.xml`, `docs/erd-current.md`, `docs/manual-test-checklist.md`)

---

## 1. Ringkasan Produk

HRIS Norton Bali Computer adalah aplikasi web internal untuk menggantikan proses administrasi SDM manual (absensi manual, cuti via formulir kertas, jadwal kerja via spreadsheet) dengan sistem terintegrasi. Fitur pembeda utama adalah **absensi berbasis pengenalan wajah** menggunakan `face_recognition` (dlib), dioperasikan secara terkontrol oleh Admin/HR pada satu titik komputer di kantor untuk mencegah penyalahgunaan absensi dari luar lokasi kerja.

## 2. Tujuan Produk

- Menggantikan pencatatan kehadiran manual dengan verifikasi identitas berbasis wajah, mengurangi risiko "titip absen".
- Mendigitalkan pengajuan dan persetujuan cuti, lengkap dengan perhitungan sisa kuota otomatis.
- Mengelola jadwal kerja karyawan secara terpusat, termasuk penjadwalan massal dan per-shift.
- Menyediakan pelaporan terpadu untuk HR dan atasan divisi sebagai dasar evaluasi dan pengambilan keputusan.

## 3. Target Pengguna (Personas / Aktor)

Sesuai Gambar 3.5 (Use Case Diagram), dengan penambahan peran `Admin` dan `Atasan Divisi` sebagai hasil penghalusan implementasi:

| Aktor | Deskripsi | Akses Utama |
| --- | --- | --- |
| **Karyawan** | Staf operasional Norton Bali Computer | Login, melihat absensi/jadwal/cuti pribadi, mengajukan cuti |
| **Atasan Divisi** | Kepala/wakil kepala divisi | Menyetujui/menolak cuti karyawan divisinya, melihat jadwal & laporan divisi |
| **HR / HRD** | Bagian SDM | Melihat data karyawan, absensi, cuti, jadwal, dan laporan (read-only pada approval) |
| **Admin** | Operator sistem / HR dengan akses penuh | Seluruh fungsi HR + operasi absensi wajah (capture, verifikasi, pencatatan) + manajemen master data, role, dan visibilitas menu |

## 4. Prinsip Desain & Batasan Produk (Carry-over dari Skripsi)

1. **Single point of attendance capture**: Operasi absensi wajah (`/api/attendance/*`) hanya dapat dijalankan oleh role `admin`, dari satu komputer kantor — bukan self-service oleh setiap karyawan. Ini adalah keputusan produk yang **harus tetap dipertahankan** kecuali ada keputusan ulang dari klien.
2. **Model pretrained, tanpa retraining**: Sistem menggunakan model `face_recognition`/dlib pretrained (face embedding 128 dimensi, threshold Euclidean distance 0.6). Tidak ada rencana melatih ulang model.
3. **Tidak menghapus, hanya menyembunyikan**: Setiap perubahan UI yang menyembunyikan suatu fitur (contoh: status "Remote/WFH" pada dashboard, atau menu Role/Divisi/Jabatan di sidebar) **tidak boleh menghapus skema database, route, atau logic backend** terkait — harus reversibel melalui konfigurasi/flagging.
4. **Flow harus selaras dengan Use Case Diagram (Gambar 3.5)** yang sudah disetujui klien. Penambahan fitur baru (role Admin, Atasan Divisi, manajemen shift/divisi/jabatan, dsb.) diperlakukan sebagai **perluasan** dari use case yang ada, bukan penggantian — narasi ke klien tetap: "Karyawan mengajukan cuti & melihat jadwal, Atasan menyetujui cuti, HR mengelola data & laporan, sistem mencatat kehadiran via wajah."

## 5. Lingkup Rilis Saat Ini (Fitur yang Sudah Berjalan)

### 5.1 Autentikasi & Peran
- Login berbasis username/password, session-based.
- 7 role: `admin`, `hr`, `hrd`, `atasan_divisi`, `manager`, `supervisor`, `karyawan`/`employee`, dengan permission granular.

### 5.2 Absensi Wajah
- Capture wajah via kamera (admin), auto-detect clock-in/clock-out.
- Pencocokan wajah via service Python (`face_recognition`), threshold 0.6.
- Pencatatan status: `hadir`, `terlambat`, `remote`, `tidak_hadir`, dengan audit lengkap (siapa mencatat, confidence, hash foto, GPS, device, IP) dan record terkunci setelah disimpan.
- Deteksi keterlambatan otomatis berdasarkan jadwal/shift karyawan.

### 5.3 Registrasi Wajah
- Registrasi via kamera per karyawan.
- **Import wajah via upload foto tunggal** (form admin: pilih karyawan → upload 1 foto JPG/JPEG/PNG ≤5MB).
- **Import wajah massal via CSV** + folder `storage/app/imports/faces/` (kolom `face_image_path` per baris).
- Validasi & pesan error berbahasa Indonesia (wajah tidak terdeteksi, lebih dari satu wajah, dll).
- Penghapusan data wajah karyawan.

### 5.4 Cuti
- Pengajuan cuti oleh karyawan (jenis, tanggal mulai/selesai, keterangan).
- Routing otomatis ke atasan divisi karyawan tersebut.
- Approval scoped per divisi untuk `atasan_divisi` (tidak bisa approve cuti sendiri, tidak bisa lintas divisi).
- `hr`/`hrd` read-only pada approval; `admin` dapat memproses semua.
- Riwayat & status cuti untuk karyawan; pembatalan cuti `pending`.
- Perhitungan otomatis jumlah hari cuti dan sisa kuota tahunan (kuota berbeda untuk Tetap vs Kontrak/Training).

### 5.5 Jadwal Kerja
- CRUD jadwal per karyawan per tanggal.
- Bulk create harian dan bulk range (per rentang tanggal, target: semua/divisi/karyawan tertentu, opsi overwrite).
- Libur massal.
- Entitas Shift terstandarisasi (kode, nama, jam masuk/pulang) dirujuk oleh karyawan & jadwal.

### 5.6 Manajemen Data Master
- CRUD Karyawan + akun login terkait (1:1) + import CSV massal.
- CRUD Divisi, Jabatan, Shift.
- Manajemen Role & Permission, visibilitas menu sidebar (flagging).

### 5.7 Laporan & Dashboard
- Laporan terpusat absensi/cuti/jadwal untuk HR & Atasan.
- Dashboard Karyawan: jadwal hari ini, sisa cuti, hadir bulan ini, status absensi hari ini, riwayat 7 hari, cuti terbaru.
- Dashboard Admin/HR: rekap absensi harian (total karyawan, sudah/belum absen, terlambat, tepat waktu, tidak hadir, cuti approved) + tabel absensi hari ini seluruh karyawan.
- **Status "Remote/WFH" dihitung di backend tapi tidak ditampilkan** sebagai card/chart di dashboard utama (lihat §6).

---

## 6. Perubahan Produk Terbaru: Penyembunyian "Remote/WFH" dari Dashboard

### 6.1 Latar Belakang
Klien/tim internal memutuskan bahwa metrik "Remote/WFH" tidak relevan untuk ditampilkan pada rekap harian utama saat ini, namun status tersebut sudah tertanam di data absensi historis dan logic terkait (`Absensi::STATUSES`, `FACE_REQUIRED_STATUSES`, validasi `admin-record`).

### 6.2 Keputusan Produk
- **UI**: Card "Remote" dihapus dari rekap absensi hari ini (`resources/views/dashboard/dashboard.blade.php`); entri "Remote" dihapus dari array `dailyAttendanceChartData` di `DashboardController`.
- **Backend**: `dailyAttendanceSummary['remote']` tetap dihitung (dengan komentar kode menjelaskan bahwa ini status legacy/opsional yang disembunyikan dari rekap utama), agar API/konsumen lain yang mungkin membaca summary ini tidak rusak.
- **Database**: Tidak ada migration baru, tidak ada perubahan tabel/kolom. Data `status = 'remote'` pada `absensi` tetap valid.
- **Dokumentasi**: `docs/manual-test-checklist.md` diperbarui agar tidak lagi mensyaratkan card "Remote" tampil di pengujian dashboard.
- **Verifikasi**: `php artisan view:cache` dijalankan dan sukses (tidak ada error parse Blade).

### 6.3 Status: Reversibel
Untuk menampilkan kembali "Remote/WFH" di dashboard: tambahkan kembali 1 entri ke `dailyAttendanceChartData` dan 1 card di blade. Tidak memerlukan migration atau perubahan model.

### 6.4 Dampak terhadap Use Case Diagram
Tidak ada. "Remote/WFH" bukan merupakan use case tersendiri pada Gambar 3.5 — ini adalah salah satu nilai dari atribut `status` pada entitas `Absensi`, sehingga perubahan tampilan ini tidak mengubah flow yang dijanjikan ke klien (Karyawan tetap melihat status absensinya sendiri di dashboard personal, terlepas dari apakah "Remote" muncul sebagai kategori agregat di dashboard admin).

---

## 7. Kebutuhan Baru: Bulk Face Enrollment via Upload Selfie (30–50 Karyawan)

### 7.1 Problem Statement
Mendaftarkan wajah 30–50 karyawan satu per satu melalui kamera real-time memakan waktu dan tidak praktis saat onboarding massal. Dibutuhkan cara agar foto selfie karyawan dapat diunggah dan diproses oleh layanan `face_recognition` Python untuk menghasilkan `face_embedding`, tanpa setiap karyawan harus bergiliran di depan kamera kantor.

### 7.2 Status Saat Ini (sudah tersedia, perlu dikomunikasikan ke klien)
Implementasi **sudah memiliki** dua jalur bulk enrollment:

1. **Jalur CSV + folder foto** (`Karyawan > Import Karyawan`):
   - Admin menyiapkan folder `storage/app/imports/faces/` berisi foto-foto selfie karyawan (format JPG/JPEG/PNG).
   - Admin menyiapkan CSV (template tersedia via `karyawan.import.template`) dengan kolom `face_image_path` menunjuk ke nama file di folder tersebut, beserta data karyawan lain (nama, username, email, divisi, jabatan, kode shift, dst).
   - Saat import dijalankan, untuk setiap baris dengan `face_image_path` terisi, sistem memanggil `KaryawanFaceImportService::encodeImportPath()` → `face_recognition` Python service → `face_embedding` tersimpan ke `karyawan.face_embedding`.
   - Baris yang gagal (misal wajah tidak terdeteksi) tercatat di ringkasan import (`failed`) tanpa menghentikan proses baris lain.

2. **Jalur upload satu-per-satu** (`Karyawan > Import Wajah`):
   - Admin memilih satu karyawan, upload satu foto, sistem langsung memproses dan menyimpan `face_embedding`.
   - Cocok untuk koreksi/registrasi individual, kurang efisien untuk 30–50 karyawan sekaligus.

### 7.3 Gap & Rekomendasi Peningkatan (Roadmap, belum diimplementasikan)

| Gap | Dampak | Rekomendasi |
| --- | --- | --- |
| Tidak ada UI untuk upload **banyak foto sekaligus** dalam satu form (drag multiple files) yang langsung dipasangkan ke karyawan berdasarkan nama file | Admin harus mengandalkan CSV + akses filesystem server, yang kurang nyaman untuk operator non-teknis | Tambahkan halaman "Bulk Face Enrollment": admin mengunggah banyak file sekaligus (multi-file input), sistem mencocokkan nama file dengan `username`/NIK karyawan, lalu memproses tiap file via `KaryawanFaceImportService::encodeUploadedFile()` dalam loop, dan menampilkan ringkasan hasil per file (sukses/gagal + alasan) |
| Tidak ada antarmuka **self-service** bagi karyawan untuk mengunggah selfie sendiri | Seluruh proses bergantung pada Admin/HR | (Opsional, perlu konfirmasi klien) Tambahkan halaman karyawan "Upload Foto Wajah Saya", hasil upload masuk ke status `pending_review`, Admin meninjau dan menyetujui sebelum `face_embedding` final disimpan — menjaga kontrol kualitas tanpa melanggar prinsip "single point of control" untuk *pencatatan absensi* (berbeda dari *registrasi* wajah) |
| Proses sinkron untuk banyak file dapat memperlambat response (tiap foto memanggil service Python) | Risiko timeout pada upload 30–50 foto sekaligus | Proses secara batch dengan progres (queue job Laravel) jika volume besar; untuk volume kecil (≤50), proses sinkron dengan loop + ringkasan per file biasanya masih dapat diterima dalam 1 request, namun perlu pengujian beban |
| Validasi kualitas foto hanya memeriksa "ada tepat satu wajah" | Foto buram/pencahayaan buruk tetap diterima jika 1 wajah terdeteksi, berpotensi menurunkan akurasi pengenalan saat absensi | Pertimbangkan pengecekan tambahan (ukuran minimum wajah relatif terhadap frame, skor kualitas) — di luar lingkup skripsi (yang membatasi pada kondisi citra ideal), namun relevan untuk produksi |

### 7.4 Kriteria Penerimaan (untuk peningkatan di atas, jika dikerjakan)
- Admin dapat mengunggah ≥30 file foto sekaligus dalam satu form.
- Sistem menampilkan ringkasan: jumlah berhasil, gagal, dan alasan kegagalan per file (nama file).
- File yang gagal tidak menggagalkan proses file lain (perilaku konsisten dengan import CSV saat ini).
- `face_embedding` yang berhasil diproses langsung tersedia untuk pencocokan pada modul absensi tanpa langkah tambahan.
- Tidak ada perubahan skema database (tabel `karyawan.face_embedding` tetap digunakan).

---

## 8. Non-Goals (Eksplisit di Luar Lingkup)

- Retraining/fine-tuning model pengenalan wajah.
- Absensi self-service dari device pribadi karyawan di luar kantor.
- Penghapusan status `remote` dari database/model — hanya penyembunyian UI yang diizinkan tanpa keputusan ulang klien.
- Penanganan kondisi citra ekstrem (gelap, masker, kacamata hitam, oklusi wajah) — sesuai batasan skripsi.

## 9. Metrik Keberhasilan (selaras Rancangan Pengujian Skripsi §3.4.2)

- **Functional correctness**: seluruh skenario Black Box Testing untuk fitur absensi wajah, cuti, jadwal kerja, data karyawan, dan laporan berstatus "Berhasil".
- **User Acceptance**: skor UAT positif dari sampel masing-masing aktor (Karyawan, Atasan/Atasan Divisi, HR).
- **Akurasi pengenalan wajah**: diuji terhadap seluruh dataset karyawan terdaftar (target awal: 30 karyawan sesuai proposal; dapat bertambah seiring bulk enrollment).
- **Tidak ada regresi data**: setelah perubahan UI (misal penyembunyian Remote/WFH), `php artisan migrate` tidak menunjukkan perubahan skema yang tidak diinginkan dan data historis tetap utuh.

## 10. Glossary
Lihat SRS §1.3 untuk daftar istilah.

## 11. Lampiran — Pemetaan Use Case → Implementasi (Quick Reference)

| Use Case (Gambar 3.5) | Controller / Route Utama |
| --- | --- |
| Login / Autentikasi | `AuthController@proseslogin`, route `/`, `/proseslogin` |
| Absensi berbasis pengenalan wajah | `AttendanceController` (`/api/attendance/*`, admin only), `FaceRecognitionService`, Python `face_recognition_service.py` |
| Mengajukan Cuti | `CutiController@store`, route `cuti.store` |
| Melihat Status & Riwayat Cuti | `CutiController@history`, `@show`, route `cuti.history`, `cuti.show` |
| Melihat Jadwal Kerja | `JadwalKerjaController@show`, route `jadwal.show`, + dashboard personal |
| Mengelola Data Karyawan | `KaryawanController` (CRUD + import), route `karyawan.*` |
| Menyetujui/Menolak Cuti | `CutiController@approval`, `@updateStatus`, `CutiApprovalService` |
| Mengelola Jadwal Kerja Karyawan | `JadwalKerjaController` (CRUD + bulk + libur massal) |
| Melihat & Mencetak Laporan | `LaporanController@index` |
