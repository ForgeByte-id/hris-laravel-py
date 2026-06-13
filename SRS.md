# Software Requirements Specification (SRS)

## Aplikasi Sumber Daya Manusia (HRIS) — Norton Bali Computer
### Fitur Absensi Berbasis Pengenalan Wajah (face_recognition / dlib)

Version: 1.0
Status: Living document — disesuaikan dengan implementasi aktual per 13 Juni 2026
Sumber: Skripsi "Implementasi Fitur Absensi Berbasis Pengenalan Wajah Menggunakan Library Face_Recognition Berbasis Dlib pada Aplikasi Sumber Daya Manusia di Norton Bali Computer" (Ni Luh Kade Krismayanti, 2515444022), direkonsiliasi dengan codebase (`repomix-output.xml`) dan catatan perubahan implementasi terbaru.

---

## 1. Pendahuluan

### 1.1 Tujuan

Dokumen ini mendefinisikan kebutuhan perangkat lunak untuk aplikasi HRIS berbasis web di Norton Bali Computer, dengan fokus pada modul absensi berbasis pengenalan wajah, pengelolaan cuti, dan manajemen jadwal kerja. SRS ini dijadikan acuan tunggal ("source of truth") agar flow aplikasi tetap konsisten dengan Use Case Diagram (Gambar 3.5) yang disepakati klien, sekaligus mendokumentasikan deviasi implementasi yang telah terjadi sehingga pengembangan lanjutan tidak merusak fondasi yang sudah disetujui.

### 1.2 Lingkup

Sistem mencakup:
- Autentikasi & otorisasi berbasis peran (role-based access).
- Absensi berbasis pengenalan wajah (face recognition) — dikontrol penuh oleh admin/HR pada satu titik perangkat di kantor.
- Registrasi wajah karyawan, baik via kamera real-time maupun via upload foto (batch/individual).
- Pengajuan, persetujuan, dan riwayat cuti.
- Manajemen jadwal kerja (termasuk shift dan penjadwalan massal).
- Manajemen data karyawan, divisi, jabatan.
- Laporan dan rekapitulasi absensi, cuti, dan jadwal kerja.
- Dashboard ringkasan harian untuk admin/HR dan dashboard personal untuk karyawan.

### 1.3 Definisi, Akronim, dan Singkatan

| Istilah | Keterangan |
| --- | --- |
| HRIS | Human Resource Information System |
| SDM | Sumber Daya Manusia |
| Karyawan | Aktor utama yang memiliki akun login dan data kepegawaian |
| HR / HRD | Human Resource — peran administratif read-only untuk pelaporan |
| Admin | Peran dengan akses penuh, termasuk operasional absensi |
| Atasan / Atasan Divisi | Aktor yang menyetujui/menolak pengajuan cuti karyawan dalam divisinya |
| Face Embedding | Representasi vektor 128 dimensi dari wajah karyawan, dihasilkan oleh `face_recognition` (dlib) |
| WFH / Remote | Status kehadiran "Work From Home" — saat ini status legacy, disembunyikan dari rekap dashboard utama |
| ERD | Entity Relationship Diagram |
| UAT | User Acceptance Testing |

### 1.4 Referensi
- Skripsi Krismayanti (2026), BAB III — Metode Penelitian, khususnya Gambar 3.5 (Use Case Diagram) dan Gambar 3.6 (ERD awal).
- `repomix-output.xml` — snapshot kode aktual aplikasi (Laravel 12 + Python face_recognition microservice).
- `docs/erd-current.md`, `docs/manual-test-checklist.md` (dokumen implementasi internal).

---

## 2. Deskripsi Umum Sistem

### 2.1 Perspektif Produk

Aplikasi adalah sistem web client-server:
- **Client**: Browser, diakses oleh tiga kelompok aktor (Karyawan, Atasan/Atasan Divisi, HR/Admin).
- **Server**: Aplikasi Laravel 12 (PHP 8.2+, MVC), MySQL 8.0 sebagai basis data, ditambah microservice Python (Flask + `face_recognition`/dlib) untuk operasi pengenalan wajah, berjalan terpisah di port 5000 dan dipanggil via HTTP internal.
- Arsitektur ini sesuai dengan Gambar 3.4 pada skripsi (client-server berbasis web, HTTPS, autentikasi berbasis peran, penyimpanan terpusat termasuk face embeddings).

### 2.2 Aktor Sistem (selaras dengan Gambar 3.5 — Use Case Diagram)

1. **Karyawan** — pengguna utama, menggunakan aplikasi untuk:
   - Login.
   - Melihat status absensi pribadi (absensi berbasis pengenalan wajah dilakukan oleh Admin/HR di satu titik kamera kantor — lihat catatan deviasi §2.4).
   - Mengajukan cuti.
   - Melihat status dan riwayat cuti.
   - Melihat jadwal kerja.
2. **Atasan / Atasan Divisi** — menyetujui/menolak pengajuan cuti karyawan di divisinya, serta dapat mengelola/melihat jadwal kerja divisinya.
3. **HR (Human Resource / HRD)** — mengelola data karyawan, mengelola jadwal kerja, serta melihat dan mencetak laporan absensi/cuti/jadwal kerja.
4. **Admin** — peran tambahan pada implementasi (di luar tiga aktor skripsi) yang memegang kontrol operasional penuh, termasuk merekam absensi berbasis wajah pada satu komputer khusus di kantor, mengelola seluruh data master, dan menjalankan seluruh fungsi HR. Admin ditambahkan agar kontrol akses lebih granular tanpa mengubah peran fungsional yang dijanjikan ke klien (Karyawan, Atasan, HR tetap melihat sistem sesuai use case diagram).

### 2.3 Lingkungan Operasional

- Aplikasi web, dihosting online, diakses via HTTPS.
- Sesuai batasan skripsi: untuk mencegah penyalahgunaan absensi dari luar kantor, **sesi/pencatatan absensi wajah hanya dapat dilakukan melalui satu komputer/akun khusus (Admin/HR) di lingkungan kantor Norton Bali Computer** — bukan self-service oleh setiap karyawan dari device masing-masing.
- Server: Laravel 12 (PHP 8.2+) via nginx; database MySQL 8.0; face recognition service: Python 3.10 + `face_recognition` (dlib) via Flask, port 5000, dipanggil melalui `FACE_SERVICE_URL`.
- Waktu sistem: GMT+8 (Asia/Singapore) untuk seluruh pencatatan waktu absensi.

### 2.4 Deviasi Implementasi terhadap Rancangan Awal (Gambar 3.5 & 3.6)

Bagian ini mendokumentasikan **perubahan yang sudah terjadi pada implementasi** dibanding rancangan awal pada skripsi, agar developer berikutnya tidak menganggapnya sebagai bug, dan agar narasi skripsi (BAB IV) dapat ditulis secara jujur sesuai kondisi nyata.

#### 2.4.1 Perubahan terhadap Use Case Diagram (Gambar 3.5)

| Use Case pada Gambar 3.5 | Status Implementasi | Catatan |
| --- | --- | --- |
| Login / Autentikasi Pengguna | **Sesuai** | Implementasi: session-based auth, guard custom berbasis `username`, role via Spatie Permission. |
| Absensi berbasis pengenalan wajah (aktor: Karyawan) | **Deviasi (alur, bukan fitur)** | Pada rancangan awal, absensi dilakukan langsung oleh Karyawan via device masing-masing. Pada implementasi, **seluruh aksi absensi (`/attendance/*`) dibatasi untuk role `admin`** melalui middleware `is_admin`. Karyawan tetap "memiliki" hasil absensi (dapat melihatnya di dashboard pribadi, read-only), namun proses capture wajah & pencatatan dilakukan oleh Admin/HR pada satu komputer kantor. Perubahan ini **sejalan dengan batasan masalah skripsi** ("proses pembukaan sesi absensi harian hanya dapat dilakukan melalui akun HR pada satu komputer khusus") — sehingga tidak bertentangan dengan dokumen proposal, hanya memperjelas siapa yang menekan tombol. |
| Mengajukan Cuti (aktor: Karyawan) | **Sesuai** | `CutiController::store`, route `cuti.store`. |
| Melihat Status dan Riwayat Cuti (aktor: Karyawan) | **Sesuai** | `cuti.history`, `cuti.show`. |
| Melihat Jadwal Kerja (aktor: Karyawan) | **Sesuai** | `jadwal.show` (per karyawan) + ditampilkan di dashboard personal. |
| Mengelola Data Karyawan (aktor: HR) | **Sesuai, diperluas** | `KaryawanController` — CRUD lengkap + import CSV massal + import wajah massal (lihat §2.4.3). Pada implementasi, akses penuh berada di role `admin`; role `hr`/`hrd` bersifat read-only sesuai `RolePermissionSeeder`. |
| Menyetujui/Menolak Pengajuan Cuti (aktor: Atasan) | **Diperluas menjadi "Atasan Divisi"** | Ditambahkan role `atasan_divisi` dengan scoping ketat per divisi (`CutiApprovalService`): atasan divisi hanya dapat melihat & memproses pengajuan cuti dari karyawan **di divisinya sendiri**, dan tidak dapat menyetujui cuti miliknya sendiri. Role `hr`/`hrd` bersifat read-only pada halaman approval (tidak bisa PATCH). Ini adalah penghalusan dari rancangan awal ("Atasan") agar sesuai struktur organisasi riil Norton Bali Computer (multi-divisi). |
| Mengelola Jadwal Kerja Karyawan (aktor: Atasan & HR) | **Sesuai, diperluas** | `JadwalKerjaController` — CRUD per-karyawan, bulk create per hari, **bulk range** (rentang tanggal, target seluruh karyawan/per divisi/per karyawan tertentu, dengan opsi overwrite), serta "libur massal". |
| Melihat dan Mencetak Laporan Absensi, Cuti, dan Jadwal Kerja (aktor: HR & Atasan) | **Sesuai** | `LaporanController` — halaman laporan terpusat. |

**Use case tambahan di luar Gambar 3.5** (hasil pengembangan lanjutan, tidak menghapus use case yang sudah disepakati):
- Manajemen Role & Hak Akses (Admin) — `Admin/RoleController`, `Admin/PermissionController`. Saat ini disembunyikan dari sidebar via `MenuVisibilityService`/flagging, namun tetap berfungsi.
- Manajemen Divisi & Jabatan (HR/Admin) — `DivisiController`, `JabatanController`. Disembunyikan dari sidebar (sama seperti di atas) tetapi tetap dipakai secara internal (relasi `karyawan.id_devisi`, `karyawan.id_jabatan`, scoping approval cuti per divisi).
- Manajemen Shift — `ShiftController`, entitas `shifts` (kode shift, jam masuk/pulang) yang dirujuk oleh `karyawan.kode_shift` dan `jadwal_kerja.kode_shift`.
- Import Karyawan (CSV) dan Import Wajah (gambar tunggal & batch) — lihat §2.4.3.
- Profil pengguna (`ProfileController`).

#### 2.4.2 Perubahan terhadap ERD (Gambar 3.6)

ERD awal (Gambar 3.6) memiliki 5 entitas: `User`, `Karyawan`, `Absensi`, `Cuti`, `Jadwal_Kerja`. ERD implementasi aktual (`docs/erd-current.md`) menambah dan memodifikasi sejumlah hal:

| Aspek | Rancangan Awal (Gambar 3.6) | Implementasi Aktual | Alasan |
| --- | --- | --- | --- |
| `karyawan.jabatan` | Kolom string bebas | Diganti menjadi `id_jabatan` (FK → tabel `jabatans`) | Normalisasi data jabatan agar konsisten & bisa dikelola terpusat. |
| `karyawan.divisi` | Kolom string bebas | Diganti menjadi `id_devisi` (FK → tabel `devisis`) | Normalisasi data divisi; menjadi basis scoping approval cuti per divisi (`atasan_divisi`). |
| Shift / jam kerja | Tidak ada entitas Shift; `jadwal_kerja.jam_kerja` berupa string bebas (misal "Pagi (08:00-17:00)") | Ditambah entitas `shifts` (`id_shift`, `kode_shift`, `nama_shift`, `jam_masuk`, `jam_pulang`). `karyawan.kode_shift` dan `jadwal_kerja.kode_shift` menjadi FK ke `shifts.kode_shift`. Kolom `jadwal_kerja.jam_kerja` (string) **dipertahankan** untuk kompatibilitas data lama, namun nilai barunya diturunkan dari shift. | Standarisasi shift, memudahkan kalkulasi keterlambatan/jam pulang otomatis (`AttendanceService`), dan menghindari parsing string. |
| Kuota cuti | Tidak ada di ERD awal | Ditambahkan `karyawan.yearly_leave_quota` dan `karyawan.remaining_leave_quota` (default 12), serta entitas baru `leave_types` (`nama_cuti`, `default_quota`, `applies_to_status`, `is_active`). | Mendukung perhitungan sisa cuti otomatis (sesuai kebutuhan fungsional skripsi: "perhitungan sisa cuti secara otomatis") dan jenis cuti yang dapat dikonfigurasi serta kuota berbeda untuk status karyawan (Tetap vs Kontrak/Training). |
| Audit absensi | Tidak ada di ERD awal | Tabel `absensi` ditambah kolom: `recorded_by` (FK → `users.id_user`), `face_verified`, `face_confidence`, `photo_hash`, `gps_lat`, `gps_lng`, `device_info`, `ip_address`, `is_locked`, `menit_terlambat`. | Mendukung jejak audit untuk absensi yang dicatat oleh Admin (siapa, kapan, dengan tingkat kepercayaan wajah berapa, dari device/lokasi mana), serta mengunci record setelah tercatat (`is_locked = true`) agar tidak bisa diubah — memenuhi kebutuhan integritas data kehadiran. |
| Data karyawan tambahan | Tidak ada di ERD awal | Ditambahkan `tanggal_mulai_kerja`, `status_aktif` (Aktif/Nonaktif), `status_karyawan` (Tetap/Kontrak/Training) pada tabel `karyawan`. | Data kepegawaian riil dari Norton Bali Computer (seeder `RealEmployeeSeeder`), digunakan untuk menentukan kuota cuti default. |
| `face_embedding` | Disimpan sebagai atribut `karyawan` (sesuai Gambar 3.6) | **Tetap dipertahankan** sebagai kolom `karyawan.face_embedding` (JSON array 128-dimensi). Catatan: terdapat juga model `FaceEncoding` (tabel terpisah, kemungkinan sisa eksperimen awal) yang **tidak digunakan** oleh service pengenalan wajah aktif (`FaceRecognitionService` membaca langsung dari `karyawan.face_embedding`). | Implementasi memilih pendekatan ERD awal (1 embedding per karyawan disimpan di tabel `karyawan`) demi kesederhanaan; tabel `face_encodings`/model `FaceEncoding` adalah artefak yang perlu diputuskan apakah dihapus atau dipakai untuk multi-embedding di masa depan (lihat §6 Rekomendasi). |
| Relasi `User`—`Karyawan` | 1:1 | **Tetap 1:1**, FK `karyawan.id_user` unik, `onDelete('cascade')`. | Sesuai rancangan awal. |

Diagram ERD lengkap saat ini tersedia di `docs/erd-current.md` (format Mermaid) dan menjadi acuan basis data yang berlaku, **menggantikan** Gambar 3.6 untuk keperluan penulisan BAB IV skripsi (Hasil Implementasi).

#### 2.4.3 Perubahan Implementasi Lain (di luar ERD/Use Case langsung)

1. **Registrasi wajah multi-mode**: Selain registrasi via kamera real-time (`karyawan.register-face`, sesuai konsep awal skripsi), sistem kini mendukung:
   - **Import wajah satu-per-satu via upload foto** (`karyawan.import-face`) — admin memilih karyawan, upload 1 file gambar (JPG/JPEG/PNG, maks 5MB), sistem memanggil `KaryawanFaceImportService::encodeUploadedFile()` → `FaceRecognitionService::encodeFace()` → menyimpan `face_embedding`.
   - **Import wajah massal via CSV** (`karyawan.import` / `KaryawanImportService`) — kolom `face_image_path` pada CSV merujuk ke file di `storage/app/imports/faces/<file>`; sistem memanggil `KaryawanFaceImportService::encodeImportPath()` untuk setiap baris.
   - Tujuan: memungkinkan registrasi wajah 30–50 karyawan dalam satu waktu tanpa harus bergiliran di depan kamera (lihat §2.4.4 — kebutuhan baru "bulk face enrollment via selfie upload").

2. **Status absensi "Remote/WFH" — disembunyikan dari dashboard, dipertahankan di backend**:
   - `Absensi::STATUSES` masih mendefinisikan `remote => 'Remote / WFH'`, dan `remote` termasuk dalam `FACE_REQUIRED_STATUSES`.
   - `DashboardController::index()` masih **menghitung** `dailyAttendanceSummary['remote']` (untuk kompatibilitas data & potensi penggunaan API lain), namun **tidak lagi ditampilkan** sebagai card terpisah di `dashboard.blade.php`, dan **tidak lagi dimasukkan** ke `dailyAttendanceChartData` (array chart hanya berisi: Tepat Waktu/Hadir, Terlambat, Tidak Hadir, Belum Absen, Cuti Approved).
   - Data absensi lama dengan `status = 'remote'` tetap valid dan tidak terpengaruh secara struktural (tidak ada migration/penghapusan kolom).
   - `docs/manual-test-checklist.md` telah diperbarui agar checklist dashboard tidak lagi mengharuskan card "Remote" tampil.
   - **Keputusan ini bersifat reversibel**: untuk menampilkan kembali, cukup tambahkan kembali entri `remote` ke `dailyAttendanceChartData` dan tambahkan card di blade — tidak ada perubahan skema yang diperlukan.

3. **Menu/modul yang disembunyikan dari sidebar** (via `MenuVisibilityService` + `config/hris.php` `flaggable_hidden_menus`): Role Management, Hak Akses, Divisi, Jabatan. Modul-modul ini **tetap berfungsi penuh** secara backend (rute, controller, dan relasi database tetap aktif) dan dapat diaktifkan kembali melalui halaman `/flagging` (admin) tanpa migration tambahan.

#### 2.4.4 Kebutuhan Baru — Bulk Face Enrollment via Upload Selfie

**Latar belakang**: Mendaftarkan wajah 30–50 karyawan satu per satu melalui kamera real-time tidak efisien. Klien membutuhkan cara bagi karyawan untuk mengirimkan foto selfie (yang kemudian diproses oleh layanan Python `face_recognition`) sebagai pengganti/atau pelengkap registrasi via kamera.

**Status implementasi saat ini**: Kebutuhan ini **sebagian sudah terpenuhi** melalui dua jalur yang sudah ada (lihat §2.4.3 poin 1):
- Upload satu foto per karyawan melalui form `karyawan.import-face` (admin memilih karyawan satu per satu, lalu upload foto).
- Import massal via CSV + folder `storage/app/imports/faces/` (`KaryawanImportService` + `KaryawanFaceImportService::encodeImportPath`), di mana setiap baris CSV dapat menyertakan `face_image_path` menuju file foto yang sudah diunggah ke server.

**Gap yang masih perlu dirancang/dikonfirmasi** (lihat juga §6 Rekomendasi & Roadmap PRD):
- Belum ada antarmuka **self-service** bagi karyawan untuk mengunggah foto selfie mereka sendiri (saat ini seluruh proses import wajah dilakukan oleh Admin/HR melalui menu `Karyawan > Import Wajah`).
- Belum ada mekanisme upload **multi-file sekaligus** dalam satu form (saat ini: 1 file per submit untuk form manual; CSV mendukung banyak baris tetapi file foto harus sudah berada di `storage/app/imports/faces/` sebelum import dijalankan — biasanya via akses filesystem/SFTP oleh admin).
- Validasi kualitas foto (1 wajah terdeteksi, pencahayaan cukup) sudah ada di level Python service (`face_recognition.face_locations`), dengan pesan error yang diterjemahkan ke Bahasa Indonesia oleh `KaryawanFaceImportService::friendlyFaceError()`.

---

## 3. Kebutuhan Fungsional (Functional Requirements)

Penomoran `FR-xx` mengacu pada kebutuhan fungsional skripsi (§3.3.3.1), diperluas dengan sub-fitur implementasi aktual.

### FR-01 Autentikasi & Otorisasi
- FR-01.1 Sistem menyediakan halaman login dengan username & password.
- FR-01.2 Sistem memvalidasi kredensial dan mengarahkan pengguna ke dashboard sesuai peran (role-based).
- FR-01.3 Sistem mendukung peran: `admin`, `hr`, `hrd`, `atasan_divisi`, `manager`, `supervisor`, `karyawan`/`employee`, dengan hak akses (permission) granular via Spatie Permission.
- FR-01.4 Sistem menampilkan pesan "Login gagal" jika kredensial tidak valid.

### FR-02 Absensi Berbasis Pengenalan Wajah
- FR-02.1 Sistem menyediakan antarmuka pengambilan citra wajah via kamera (admin/HR di satu titik komputer kantor).
- FR-02.2 Sistem mengirim citra ke layanan Python (`face_recognition`/dlib) untuk deteksi wajah, ekstraksi face embedding (128 dimensi), dan pencocokan terhadap data wajah karyawan tersimpan (Euclidean distance, threshold 0.6).
- FR-02.3 Sistem secara otomatis menentukan jenis aksi (clock-in / clock-out) berdasarkan status absensi karyawan pada hari berjalan (`AttendanceService::processAutoAttendance`).
- FR-02.4 Sistem menghitung status kehadiran (`hadir`/`tepat_waktu`, `terlambat`, `tidak_hadir`, `remote`) berdasarkan jadwal/shift karyawan dan mencatat `menit_terlambat`.
- FR-02.5 Sistem mencatat metadata audit untuk setiap absensi yang direkam admin: `recorded_by`, `face_verified`, `face_confidence`, `photo_hash` (SHA-256), `gps_lat`/`gps_lng`, `device_info`, `ip_address`, dan mengunci record (`is_locked = true`) setelah tersimpan.
- FR-02.6 Sistem menolak pencatatan jika wajah yang terdeteksi cocok dengan karyawan lain selain yang dipilih (`FACE_MISMATCH`).
- FR-02.7 Sistem menyediakan endpoint verifikasi wajah independen (`verify-face`) dan endpoint pencatatan admin (`admin-record`) dengan validasi status (`hadir`, `terlambat`, `remote`, `tidak_hadir`).
- FR-02.8 Status `remote`/WFH **dipertahankan** sebagai opsi valid pada `admin-record` dan pada perhitungan internal `dailyAttendanceSummary`, namun **tidak ditampilkan** sebagai card/chart pada dashboard rekap utama (lihat §2.4.3 poin 2).

### FR-03 Registrasi Wajah Karyawan
- FR-03.1 Sistem menyediakan halaman registrasi wajah via kamera per-karyawan (`karyawan.register-face`).
- FR-03.2 Sistem menyediakan halaman import wajah via upload foto tunggal per karyawan (`karyawan.import-face`), menerima JPG/JPEG/PNG maks 5MB, dengan validasi: tepat satu wajah terdeteksi.
- FR-03.3 Sistem menyediakan import karyawan massal via CSV (`karyawan.import`) dengan kolom opsional `face_image_path` yang merujuk ke file pada `storage/app/imports/faces/`, memungkinkan registrasi wajah puluhan karyawan dalam satu proses import.
- FR-03.4 Sistem menampilkan pesan error berbahasa Indonesia yang ramah pengguna jika wajah tidak terdeteksi, terdapat lebih dari satu wajah, atau format file tidak valid.
- FR-03.5 Sistem menyediakan endpoint penghapusan data wajah karyawan (`deleteFaceEncoding`).

### FR-04 Pengajuan & Persetujuan Cuti
- FR-04.1 Karyawan dapat mengajukan cuti (jenis cuti, tanggal mulai/selesai, keterangan) — `cuti.store`.
- FR-04.2 Sistem menyimpan pengajuan dengan status awal `pending` dan meneruskannya ke atasan divisi yang relevan (`CutiApprovalService::findDivisionHeadFor`).
- FR-04.3 Karyawan dapat melihat status & riwayat pengajuan cuti miliknya (`cuti.history`, `cuti.show`).
- FR-04.4 Karyawan dapat membatalkan pengajuan cuti yang masih `pending` (`cuti.cancel`).
- FR-04.5 Atasan Divisi dapat melihat dan memproses (approve/reject) pengajuan cuti **hanya dari karyawan di divisinya sendiri**, dan **tidak dapat** memproses cuti miliknya sendiri.
- FR-04.6 Role `hr`/`hrd`/`admin` dapat melihat seluruh pengajuan cuti; `admin` dapat memproses cuti pengajuan apapun; `hr`/`hrd` bersifat read-only pada halaman approval (PATCH dikembalikan 403).
- FR-04.7 Sistem menghitung jumlah hari cuti otomatis (`Cuti::getJumlahHariAttribute`) dan mengelola sisa kuota cuti tahunan (`karyawan.remaining_leave_quota`), dengan kuota default berbeda berdasarkan `status_karyawan` (Tetap: 12 hari/tahun; Kontrak/Training: 0 — dapat dikonfigurasi via `leave_types`).

### FR-05 Manajemen Jadwal Kerja
- FR-05.1 HR/Admin/Atasan dapat membuat, mengubah, dan menghapus jadwal kerja per karyawan per tanggal (`jadwal.store`, `jadwal.update`, `jadwal.destroy`).
- FR-05.2 Sistem menyediakan pembuatan jadwal massal harian (`jadwal.bulk-store`) dan rentang tanggal (`jadwal.bulk-range-store`) dengan target: seluruh karyawan, per divisi, atau karyawan tertentu, serta opsi overwrite jadwal yang sudah ada.
- FR-05.3 Sistem menyediakan fitur "libur massal" (`jadwal.libur-massal`) untuk menetapkan hari libur ke banyak karyawan sekaligus.
- FR-05.4 Karyawan dapat melihat jadwal kerjanya sendiri (`jadwal.show` + dashboard personal — "Jadwal Hari Ini").
- FR-05.5 Jadwal kerja dapat dikaitkan dengan entitas `shifts` (kode shift, nama shift, jam masuk/pulang) untuk standarisasi jam kerja dan perhitungan otomatis keterlambatan/ketersediaan clock-out.

### FR-06 Manajemen Data Karyawan
- FR-06.1 HR/Admin dapat menambah, melihat, mengubah, dan menghapus data karyawan (`karyawan.store/update/destroy`), termasuk pembuatan akun login (`User`) terkait secara otomatis (1:1).
- FR-06.2 Sistem mendukung import karyawan massal via CSV, termasuk pembuatan otomatis Divisi/Jabatan baru jika belum ada (`KaryawanImportService`).
- FR-06.3 Sistem menyimpan data kepegawaian: jabatan, divisi, kode shift, tanggal masuk, tanggal mulai kerja, status aktif (Aktif/Nonaktif), status karyawan (Tetap/Kontrak/Training), kuota cuti tahunan & sisa kuota.

### FR-07 Laporan & Rekapitulasi
- FR-07.1 HR/Admin/Atasan dapat melihat laporan absensi, cuti, dan jadwal kerja dalam satu halaman terpusat (`laporan.index`).
- FR-07.2 Sistem menyediakan rekap absensi harian pada dashboard admin/HR: total karyawan, sudah absen masuk, belum absen, terlambat, tepat waktu/hadir, tidak hadir, cuti approved (status `remote` dihitung tetapi tidak ditampilkan — lihat §2.4.3).
- FR-07.3 Sistem menyediakan endpoint riwayat absensi seluruh karyawan untuk keperluan widget dashboard (`attendance/recent-all`).

### FR-08 Dashboard
- FR-08.1 Dashboard Karyawan menampilkan: jadwal hari ini, sisa kuota cuti, jumlah hadir bulan ini, status absensi hari ini, riwayat absensi 7 hari terakhir, dan riwayat cuti terbaru.
- FR-08.2 Dashboard Admin/HR menampilkan: rekap absensi harian (lihat FR-07.2) dan tabel "Absensi Hari Ini" (nama, divisi, jabatan, jadwal/shift, jam masuk, jam pulang, status) untuk seluruh karyawan.

### FR-09 Manajemen Master Data (di luar use case diagram skripsi, mendukung FR di atas)
- FR-09.1 HR/Admin dapat mengelola Divisi (`devisi.*`) dan Jabatan (`jabatan.*`).
- FR-09.2 HR/Admin dapat mengelola Shift (`shift.*`): kode shift, nama, jam masuk, jam pulang.
- FR-09.3 Admin dapat mengelola Role & Permission (`admin.roles.*`, `admin.permissions.*`) serta visibilitas menu sidebar (`flagging.*`).

---

## 4. Kebutuhan Non-Fungsional (Non-Functional Requirements)

Mengacu pada §3.3.3.2 skripsi, diperluas:

| ID | Kategori | Deskripsi |
| --- | --- | --- |
| NFR-01 | Keamanan | Autentikasi sesi, otorisasi berbasis peran & permission (Spatie). Seluruh endpoint absensi dan operasi sensitif lain dilindungi middleware `is_admin` / pengecekan `AuthorizationService`. |
| NFR-02 | Keamanan Data Wajah | Path file gambar divalidasi di sisi Python service (`get_safe_image_path`) untuk mencegah path traversal & symlink; hanya direktori yang diizinkan (`ALLOWED_IMAGE_TMP_DIR`, opsional `LARAVEL_STORAGE_PATH`) yang dapat diakses. |
| NFR-03 | Integritas Data Absensi | Record absensi yang dicatat admin dikunci (`is_locked`) setelah disimpan; dilengkapi hash foto (SHA-256), GPS, info device, dan IP untuk audit. |
| NFR-04 | Kinerja | Permintaan dashboard dan rekap harian harus responsif untuk operasional harian (query dibatasi per hari/per rentang singkat; endpoint `recent-all` dibatasi maks 30 hari). |
| NFR-05 | Keandalan | Operasi CRUD dan pencatatan absensi konsisten; import (karyawan/wajah) menghasilkan ringkasan sukses/update/skip/failed agar baris bermasalah tidak menggagalkan seluruh proses. |
| NFR-06 | Usability | Antarmuka berbasis Bootstrap 5 + Tabler, sederhana, dengan validasi pesan berbahasa Indonesia yang ramah pengguna (khususnya pada error pengenalan wajah). |
| NFR-07 | Aksesibilitas | Aplikasi dapat diakses melalui browser tanpa instalasi tambahan; namun fitur absensi wajah dibatasi pada satu titik akses (Admin/HR) di kantor sesuai batasan skripsi. |
| NFR-08 | Kompatibilitas Data Lama | Perubahan UI (misal penyembunyian card "Remote/WFH") tidak boleh menghapus/mengubah skema database atau merusak data historis; status legacy tetap valid dan dapat dipulihkan tampilannya tanpa migration. |
| NFR-09 | Konsistensi Waktu | Seluruh waktu absensi disimpan dan ditampilkan dalam GMT+8 (Asia/Singapore). |
| NFR-10 | Akurasi Pengenalan Wajah | Sistem menggunakan threshold Euclidean distance 0.6 untuk menentukan kecocokan wajah; tingkat akurasi diuji terhadap seluruh dataset karyawan terdaftar (sesuai rencana pengujian skripsi §3.4.2). |

---

## 5. Model Data (ERD Implementasi Aktual)

Lihat `docs/erd-current.md` untuk diagram Mermaid lengkap. Ringkasan entitas:

- **users**: `id_user` (PK), `username`, `email`, `password`, `role`, `email_verified_at`.
- **karyawan**: `id_karyawan` (PK), `id_user` (FK, unik, 1:1), `nama`, `id_jabatan` (FK), `id_devisi` (FK), `kode_shift` (FK), `tanggal_masuk`, `tanggal_mulai_kerja`, `status_aktif`, `status_karyawan`, `yearly_leave_quota`, `remaining_leave_quota`, `face_embedding` (JSON, 128-dim vector).
- **shifts**: `id_shift` (PK), `kode_shift` (UK), `nama_shift`, `jam_masuk`, `jam_pulang`.
- **absensi**: `id_absensi` (PK), `id_karyawan` (FK), `tanggal`, `jam_masuk`, `jam_pulang`, `status`, `menit_terlambat`, `recorded_by` (FK → users), `face_verified`, `face_confidence`, `photo_hash`, `gps_lat`, `gps_lng`, `device_info`, `ip_address`, `is_locked`.
- **cuti**: `id_cuti` (PK), `id_karyawan` (FK), `jenis_cuti`, `tanggal_mulai`, `tanggal_selesai`, `keterangan`, `tanggal_persetujuan`, `status_persetujuan`, `id_atasan` (FK → karyawan).
- **jadwal_kerja**: `id_jadwal` (PK), `id_karyawan` (FK), `tanggal`, `jam_kerja`, `kode_shift` (FK), `keterangan`.
- **leave_types**: `id` (PK), `nama_cuti`, `default_quota`, `applies_to_status`, `is_active`.
- **devisis**: `id` (PK), `nama_devisi`.
- **jabatans**: `id` (PK), `nama_jabatan`.

Relasi kunci:
- `users` 1:1 `karyawan`.
- `karyawan` 1:N `absensi`, 1:N `cuti` (sebagai pemohon), 1:N `cuti` (sebagai atasan via `id_atasan`), 1:N `jadwal_kerja`.
- `shifts` 1:N `karyawan` (default shift), 1:N `jadwal_kerja`.
- `devisis` 1:N `karyawan`; `jabatans` 1:N `karyawan`.

---

## 6. Rekomendasi & Item Terbuka untuk Pengembangan Lanjutan

1. **Model `FaceEncoding` / tabel `face_encodings`**: tidak digunakan oleh alur pengenalan wajah aktif. Perlu diputuskan: (a) hapus jika benar-benar tidak terpakai, atau (b) gunakan untuk mendukung multi-embedding per karyawan (misalnya beberapa sudut wajah) di masa depan.
2. **Self-service bulk face enrollment**: jika klien menginginkan karyawan dapat mengunggah selfie sendiri (bukan hanya admin), perlu modul baru: halaman upload selfie untuk karyawan + antrian pemrosesan (agar tidak membebani service Python saat 30–50 foto diunggah bersamaan) + halaman review/approve oleh admin sebelum `face_embedding` final disimpan.
3. **Multi-file upload untuk import wajah manual**: form `karyawan.import-face` saat ini hanya menerima 1 file per submit; dapat ditingkatkan menjadi multi-file dengan mapping nama file → karyawan (misal nama file = NIK/username).
4. **Penyembunyian "Remote/WFH"**: keputusan ini bersifat UI-only dan reversibel; jika ke depan klien ingin menghapus status ini secara permanen, diperlukan keputusan migrasi data terpisah (di luar lingkup perubahan saat ini).
5. **Dokumentasi BAB IV skripsi**: gunakan `docs/erd-current.md` sebagai ERD final pengganti Gambar 3.6, dan gunakan §2.4 dokumen ini sebagai dasar narasi "Analisis Implementasi dan Pengujian Sistem" terkait perbedaan rancangan vs realisasi.
