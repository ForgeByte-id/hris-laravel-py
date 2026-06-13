# AGENTS.md — HRIS Norton Bali Computer

Panduan ini ditujukan untuk siapa pun (manusia atau AI agent/Claude/Codex/dll.) yang melanjutkan pengembangan repo ini. Tujuannya: jangan merusak flow yang sudah disepakati klien, jangan menghapus data/skema tanpa keputusan eksplisit, dan jaga konsistensi antara skripsi (proposal) dan implementasi.

---

## 1. Konteks Proyek

- **Domain**: HRIS (Human Resource Information System) untuk Norton Bali Computer.
- **Dasar akademik**: Skripsi "Implementasi Fitur Absensi Berbasis Pengenalan Wajah Menggunakan Library Face_Recognition Berbasis Dlib pada Aplikasi Sumber Daya Manusia di Norton Bali Computer" (Ni Luh Kade Krismayanti, NIM 2515444022, Politeknik Negeri Bali, 2026).
- **Dokumen acuan** (baca sebelum mengubah apa pun yang berkaitan dengan flow/use case/ERD):
  - `docs/SRS.md` — Software Requirements Specification (kebutuhan fungsional/non-fungsional + tabel deviasi rancangan vs implementasi).
  - `docs/PRD.md` — Product Requirements Document (lingkup produk, prinsip desain, roadmap).
  - `docs/erd-current.md` — ERD aktual (Mermaid), pengganti Gambar 3.6 pada skripsi.
  - `docs/manual-test-checklist.md` — checklist pengujian manual yang harus tetap valid setelah perubahan.

## 2. Stack Teknis

- **Backend**: Laravel 12, PHP 8.2+, MySQL 8.0, Spatie Permission untuk role/permission.
- **Frontend**: Blade + Bootstrap 5.2 + Bootstrap Icons + Tabler theme, Select2, Leaflet.js, SweetAlert2, DataTables, Vite.
- **Face Recognition**: Microservice Python 3.10 (Flask + `face_recognition`/dlib), port 5000, dipanggil via HTTP dari `App\Services\FaceRecognitionService` menggunakan `FACE_SERVICE_URL`.
- **Auth**: Session-based, guard custom (`username` sebagai identifier), role via Spatie (`admin`, `hr`, `hrd`, `atasan_divisi`, `manager`, `supervisor`, `karyawan`/`employee`).
- **Docker**: nginx (8085) → Laravel (php-fpm) → MySQL; face-service terpisah (`face-service:5000` di Docker, `localhost:5050`/`5000` lokal).
- **Timezone**: GMT+8 (Asia/Singapore) untuk seluruh data waktu absensi.

## 3. ATURAN WAJIB — Jangan Dilanggar Tanpa Konfirmasi Eksplisit Klien

### 3.1 Flow harus tetap sesuai Use Case Diagram (Gambar 3.5 skripsi)
Lima use case Karyawan, dua use case Atasan, tiga use case HR dalam Gambar 3.5 adalah **kontrak fungsional dengan klien**. Setiap penambahan fitur baru harus:
- Diposisikan sebagai **perluasan** dari use case yang ada (lihat tabel pemetaan di `docs/PRD.md` §11), bukan penggantian.
- Tidak menghilangkan kemampuan dasar yang dijanjikan: Karyawan login, melihat absensi/jadwal/cuti, mengajukan cuti; Atasan menyetujui/menolak cuti; HR mengelola data karyawan, jadwal, dan laporan.
- Jika sebuah fitur baru tampak "menggantikan" use case lama (contoh: absensi yang dilakukan admin, bukan karyawan sendiri), pastikan itu **selaras dengan batasan masalah skripsi** (sesi absensi harian hanya via satu komputer khusus HR di kantor) dan dicatat sebagai deviasi terdokumentasi di `docs/SRS.md` §2.4, bukan dibiarkan jadi inkonsistensi diam-diam.

### 3.2 ERD harus tetap konsisten dengan `docs/erd-current.md`
- ERD awal skripsi (Gambar 3.6, 5 entitas) sudah berubah signifikan (lihat `docs/SRS.md` §2.4.2). `docs/erd-current.md` adalah **sumber kebenaran ERD saat ini**.
- Setiap migration baru **wajib** disertai update ke `docs/erd-current.md` (tambah/ubah entitas, kolom, relasi) pada PR/commit yang sama.
- Jangan menghapus kolom yang masih dirujuk model/service tanpa audit penuh (`grep` codebase) — khususnya `karyawan.face_embedding`, `karyawan.id_devisi`, `karyawan.id_jabatan`, `karyawan.kode_shift`, dan kolom audit `absensi.*`.

### 3.3 "Sembunyikan", jangan "hapus" — kecuali ada keputusan eksplisit
Pola yang sudah berjalan dan harus diikuti untuk kasus serupa:
- **Status `remote`/WFH**: tetap ada di `Absensi::STATUSES`, `FACE_REQUIRED_STATUSES`, validasi `admin-record`, dan dihitung di `DashboardController::dailyAttendanceSummary`. Hanya **disembunyikan** dari card & chart dashboard (`dashboard.blade.php` dan `dailyAttendanceChartData`). Lihat `docs/PRD.md` §6 untuk detail keputusan ini.
- **Menu Role Management, Hak Akses, Divisi, Jabatan**: disembunyikan dari sidebar via `MenuVisibilityService` + `config('hris.flaggable_hidden_menus')`, tapi route/controller/relasi DB tetap aktif dan dapat dipulihkan via `/flagging`.

Jika diminta "hapus fitur X":
1. Tanyakan/konfirmasi apakah ini "sembunyikan dari UI" atau "hapus permanen dari sistem (termasuk data & skema)".
2. Default ke "sembunyikan dari UI" jika tidak ada konfirmasi eksplisit, karena ini lebih aman terhadap data lama dan flow yang sudah disepakati.
3. Dokumentasikan keputusan di `docs/SRS.md` §2.4 (tabel deviasi) dan/atau `docs/PRD.md` (perubahan produk).

### 3.4 Migration & Database
- Selalu cek `Schema::hasColumn`/`Schema::hasTable` sebelum `create`/`add` (pola yang sudah dipakai di migration existing) agar migration idempotent terhadap environment yang berbeda.
- Jangan membuat migration yang men-drop kolom yang masih punya data produksi tanpa rencana migrasi data (backfill/export) — ini termasuk `face_embedding`, `remaining_leave_quota`, kolom audit `absensi`.
- Setelah migration: jalankan checklist relevan di `docs/manual-test-checklist.md`, dan tambahkan entri baru ke checklist jika fitur baru memerlukan pengujian manual.

### 3.5 Face Recognition
- Jangan mengubah threshold (0.6 Euclidean distance) tanpa pengujian akurasi ulang terhadap dataset karyawan terdaftar — ini berdampak langsung ke klaim akurasi di BAB IV skripsi.
- Jangan menambahkan endpoint Python baru yang membaca file di luar `ALLOWED_IMAGE_DIRS` (`get_safe_image_path`). Validasi path traversal & symlink di `face_recognition_service.py` harus tetap berlaku untuk endpoint baru.
- Model `FaceEncoding` (tabel `face_encodings`) saat ini **tidak dipakai** oleh alur aktif (`FaceRecognitionService` membaca dari `karyawan.face_embedding`). Jangan menulis fitur baru yang bergantung pada tabel ini tanpa migrasi data + keputusan arsitektur eksplisit (lihat `docs/SRS.md` §6 rekomendasi #1).
- Untuk fitur **bulk face enrollment** (registrasi wajah massal via upload foto, mis. 30–50 karyawan):
  - Gunakan kembali `KaryawanFaceImportService` (`encodeUploadedFile` untuk file upload, `encodeImportPath` untuk path dari folder `storage/app/imports/faces/`).
  - Pola error-handling yang sudah ada: per-item gagal tidak menggagalkan proses item lain; tampilkan ringkasan sukses/gagal+alasan (lihat `KaryawanImportService` summary: `success`, `updated`, `skipped`, `failed`).
  - Pesan error harus tetap berbahasa Indonesia dan ramah pengguna (`KaryawanFaceImportService::friendlyFaceError`).
  - Jangan menyimpan file foto mentah secara permanen di luar lokasi yang sudah diizinkan (`storage/app/imports/faces/`, temp dir via `FaceRecognitionService::makeTempPath`).

### 3.6 Otorisasi & Role
- Operasi absensi (`/api/attendance/*`, `/attendance`, `/attendance/history`) **hanya** untuk role `admin` (middleware `is_admin` + `AuthorizationService::canManageAttendance`). Jangan membuka akses ini ke role lain tanpa keputusan produk eksplisit (ini adalah kontrol keamanan inti dari skripsi: "satu komputer khusus HR di kantor").
- Approval cuti untuk `atasan_divisi` **harus** tetap di-scope per `id_devisi` dan tidak boleh mengizinkan approve cuti sendiri (`CutiApprovalService::canUpdateStatus`). `hr`/`hrd` tetap read-only pada approval.
- Saat menambah role/permission baru, update `RoleSeeder` dan `RolePermissionSeeder` secara konsisten, dan tambahkan ke tabel aktor pada `docs/PRD.md` §3 jika relevan dengan use case klien.

## 4. Konvensi Kode

- **Bahasa pesan pengguna**: Bahasa Indonesia untuk semua pesan validasi/error/sukses yang tampil ke pengguna (konsisten dengan pola existing di `KaryawanController`, `KaryawanFaceImportService`, `AttendanceController`).
- **Nama tabel/kolom**: Bahasa Indonesia mengikuti pola existing (`karyawan`, `absensi`, `cuti`, `jadwal_kerja`, `id_karyawan`, dst.) — jangan mencampur konvensi penamaan Inggris/Indonesia untuk entitas inti yang sudah ada. Tabel baru yang murni infrastruktur (misal `leave_types`, `shifts`) sudah memakai Inggris — boleh konsisten dengan pola masing-masing area.
- **Service layer**: logic bisnis bertempat di `app/Services/*`, controller tetap tipis (validasi request + delegasi ke service + response).
- **View**: Blade, perpanjang `layouts.app`; gunakan komponen Tabler/Bootstrap yang sudah ada (cek `resources/views/layouts/partials/` sebelum membuat partial baru).

## 5. Workflow Perubahan

1. **Sebelum membuat fitur/perubahan**: baca `docs/SRS.md` §2.4 (deviasi) dan `docs/PRD.md` §4 (prinsip desain) untuk memastikan perubahan tidak melanggar kontrak dengan klien.
2. **Migration**: idempotent (`hasColumn`/`hasTable` checks), update `docs/erd-current.md`.
3. **Implementasi**: service layer dulu, lalu controller, lalu view. Pesan ke pengguna dalam Bahasa Indonesia.
4. **Testing**:
   - Jalankan `php artisan view:cache` setelah mengubah Blade untuk memastikan tidak ada error parse.
   - Tambahkan/perbarui test di `tests/Feature/` untuk logic kritikal (lihat `AttendanceClockOutRuleTest`, `CutiQuotaTest` sebagai contoh pola).
   - Update `docs/manual-test-checklist.md` dengan skenario manual baru.
5. **Dokumentasi**: setiap perubahan yang berdampak pada flow/ERD/role harus tercermin di `docs/SRS.md` dan/atau `docs/PRD.md` pada commit/PR yang sama — jangan biarkan dokumen menjadi stale lagi seperti yang terjadi sebelumnya (skripsi tidak sinkron dengan implementasi).

## 6. Item Terbuka / Roadmap (lihat detail di `docs/PRD.md` §7 dan `docs/SRS.md` §6)

- [ ] Putuskan nasib tabel/model `FaceEncoding` (hapus atau gunakan untuk multi-embedding).
- [ ] Pertimbangkan halaman "Bulk Face Enrollment" (multi-file upload + auto-mapping ke karyawan) untuk menyederhanakan registrasi 30–50 karyawan tanpa CSV/akses filesystem.
- [ ] (Opsional, perlu konfirmasi klien) Halaman self-service upload selfie oleh karyawan + review admin sebelum `face_embedding` final.
- [ ] Evaluasi apakah status `remote`/WFH perlu dihapus permanen atau cukup tetap tersembunyi (saat ini: tetap tersembunyi, lihat `docs/PRD.md` §6).

## 7. Larangan Eksplisit

- Jangan menghapus migration/kolom yang menyebabkan hilangnya data historis tanpa rencana migrasi & persetujuan eksplisit.
- Jangan mengubah scope akses absensi (admin-only) atau scope approval cuti (per-divisi) sebagai "side effect" dari refactor lain.
- Jangan menambahkan ketergantungan baru pada tabel `face_encodings`/model `FaceEncoding` tanpa keputusan arsitektur tertulis.
- Jangan menghapus dokumentasi deviasi di `docs/SRS.md` §2.4 — ini adalah catatan penting untuk penulisan BAB IV skripsi (perbandingan rancangan vs realisasi) dan untuk developer berikutnya.
