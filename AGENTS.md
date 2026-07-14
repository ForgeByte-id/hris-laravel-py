# AGENT.md — HRIS Norton Bali Computer

## Prinsip Dasar

Gunakan `hris_db.sql` sebagai satu-satunya Source of Truth (SOT) untuk struktur data.
Jangan mengubah schema database (migration) maupun isi file PHPUnit test.
Modifikasi HANYA diperbolehkan pada: Model (relationship), Service, Controller,
Repository/Query, dan Seeder — agar seluruh test menjadi PASS.

pada intinya migrationnya harus sama dengan `hris_db.sql`. jika beda perbaiki dan sesuaikan. jika sudah sama biarkan

## Gambaran Sistem

Aplikasi ini adalah sistem administrasi SDM berbasis web (PHP/Laravel + MySQL) yang
menggantikan proses manual (absensi kertas/manual, formulir cuti fisik, jadwal kerja di
spreadsheet) dengan satu aplikasi terpusat. Tiga modul inti yang saling terhubung:

1. **Absensi** — pencatatan kehadiran harian karyawan, diverifikasi menggunakan pengenalan
   wajah (face embedding, disimpan di `karyawan.face_embedding`, dicocokkan saat proses
   absen dan dicatat lewat `absensi.face_verified` / `face_confidence` / `photo_hash`).
2. **Cuti** — pengajuan cuti oleh karyawan, persetujuan oleh atasan, dan pelacakan kuota
   cuti tahunan per jenis cuti.
3. **Jadwal Kerja & Shift** — penjadwalan shift kerja karyawan per tanggal, dikelola oleh
   HR/Atasan, menjadi acuan validasi jam masuk/pulang di modul absensi.

## Aktor & Hak Akses

- **Karyawan**: login, absen (face recognition), mengajukan cuti, melihat status/riwayat
  cuti, melihat jadwal kerja miliknya sendiri.
- **Atasan / Division Head**: mengelola jadwal kerja tim, menyetujui/menolak pengajuan
  cuti — namun **terbatas pada karyawan di divisinya sendiri saja**. Peran ini dipetakan
  lewat tabel `roles` (mis. `manager`, `supervisor`) dan relasi `karyawan.id_divisi`.
- **HR/Admin**: kelola data master (karyawan, jabatan, divisi, shift), kelola jadwal kerja
  lintas divisi, serta melihat/mencetak laporan absensi, cuti, dan jadwal kerja.
- Otorisasi berbasis role (`roles`, `model_has_roles`, `permissions`,
  `role_has_permissions`) — jangan menambahkan mekanisme otorisasi baru di luar tabel ini.

## Model Data & Relasi yang Valid (wajib diikuti persis, jangan menambah relasi lain)

- `users` (`id_user` PK) — 1:1 → `karyawan` (`karyawan.id_user` FK).
- `karyawan` (`id_karyawan` PK):
    - `id_jabatan` → `jabatans.id`
    - `id_divisi` → `divisis.id` (relasi Eloquent yang benar: `divisi()`, BUKAN `devisi()`)
    - TIDAK memiliki `id_shift`. Shift bukan atribut statis karyawan.
    - 1:N → `absensi`, `cuti`, `jadwal_kerja`.
- `jadwal_kerja` (`id_jadwal` PK) — menghubungkan `id_karyawan` + `tanggal` ke
  `id_shift` (kode shift, varchar). Ini adalah SATU-SATUNYA jalur yang sah untuk
  mengetahui shift seorang karyawan pada tanggal tertentu:
  `jadwal_kerja.id_shift` dicocokkan ke `shifts.kode_shift`, lalu diambil
  `shifts.jam_masuk` / `shifts.jam_pulang` sebagai acuan waktu kerja.
- `absensi` (`id_absensi` PK) — `id_karyawan` FK, kolom `tanggal`, `jam_masuk`,
  `jam_pulang`, `status`, `menit_terlambat`, `face_verified`, `face_confidence`,
  `photo_hash`, `recorded_by`.
- `cuti` (`id_cuti` PK) — `id_karyawan` FK, `jenis_cuti`, `tanggal_mulai`,
  `tanggal_selesai`, `keterangan`, `tanggal_persetujuan`, `status_persetujuan`
  (default `pending`), `id_atasan` (approver).
- `persetujuan_cuti` (`id_persetujuan` PK) — catatan approval terpisah: `id_cuti`,
  `id_penyetuju`, `status_persetujuan`, `tanggal_persetujuan`, `catatan`. Setiap
  perubahan status pada `cuti` harus tercermin konsisten di tabel ini juga.
- `tipe_cuti` (`id` PK) — master jenis cuti (`nama_cuti`, `kuota_cuti`,
  `berlaku_untuk_status`, `is_active`).
- `kuota_cuti_karyawan` — sisa kuota cuti per karyawan per jenis cuti per tahun
  (`id_karyawan`, `leave_type_id` → `tipe_cuti.id`, `year`, `quota`, `remaining_quota`).
  Saat cuti disetujui, `remaining_quota` harus berkurang sesuai durasi cuti (jika logic
  ini sudah ada di service, JANGAN diubah strukturnya — hanya perbaiki relasi yang salah).
- `divisis` (`id` PK, `nama_divisi`) dan `jabatans` (`id` PK, `nama_jabatan`) — tabel
  master pendukung, dipakai untuk pengelompokan dan otorisasi berbasis divisi.
- `shifts` (`id_shift` PK, `kode_shift`, `nama_shift`, `jam_masuk`, `jam_pulang`) — master
  shift, hanya diakses lewat `jadwal_kerja`, tidak pernah langsung dari `karyawan`.

## Perbaikan yang Harus Dilakukan

### 1. Seeder — Duplicate Class

- `LeaveTypeSeeder.php` saat ini mendeklarasikan class `TipeCutiSeeder`, menyebabkan
  duplicate class declaration saat seeding.
- Samakan nama file dengan nama class (mis. file `LeaveTypeSeeder.php` →
  `class LeaveTypeSeeder`), sehingga autoload PSR-4 konsisten dan tidak ada dua class
  dengan nama sama dalam project.
- Seeder tetap harus mengisi tabel `tipe_cuti` sesuai data pada SOT (`Cuti Tahunan`,
  `Cuti Hari Raya`, `Cuti Sakit`) dengan kuota masing-masing sesuai kolom `kuota_cuti`,
  tanpa mengubah struktur tabel.

### 2. Attendance — Clock In/Out gagal (`AttendanceClockOutRuleTest`)

- **Root cause**: kode memanggil relasi `Karyawan::shift()` yang TIDAK ADA di schema.
  Shift seorang karyawan pada tanggal tertentu hanya bisa diperoleh melalui
  `jadwal_kerja` pada tanggal berjalan, bukan atribut statis di `karyawan`.
- Perbaikan yang harus dilakukan:
    - Hapus/refactor pemanggilan `Karyawan::shift()` (atau relasi Eloquent apa pun yang
      mengasumsikan FK yang tidak ada di SOT).
    - Tambahkan/perbaiki relasi yang valid, misalnya `Karyawan::jadwalKerja()` (hasMany ke
      `jadwal_kerja`), lalu dari situ ambil baris jadwal pada tanggal absen berjalan, dan
      cocokkan `id_shift`-nya ke `shifts.kode_shift` untuk mendapatkan `jam_masuk`/
      `jam_pulang` shift tersebut.
    - `clockIn()` harus tetap lulus pada skenario test yang sudah ada:
        - Hanya bisa clock in jika belum ada baris `absensi` dengan `jam_masuk` terisi pada
          tanggal berjalan untuk karyawan tersebut.
        - Jam masuk aktual dibandingkan terhadap jam masuk shift (dari jadwal kerja hari itu)
          untuk menentukan `status` (`tepat_waktu` / terlambat) dan menghitung
          `menit_terlambat`.
        - Verifikasi wajah (`face_verified`, `face_confidence`) tidak diubah alurnya — hanya
          relasi shift yang diperbaiki.
    - `clockOut()` harus tetap konsisten dengan business rule di
      `AttendanceClockOutRuleTest`: tidak bisa clock out sebelum clock in (harus ada
      `jam_masuk` lebih dulu), `jam_pulang` diisi sesuai waktu aktual saat clock out
      dipanggil, tanpa menambah kolom atau relasi baru di luar SOT.
    - Jangan membuat relationship baru yang tidak ada representasinya di `hris_db.sql`.

### 3. Leave Approval — `Call to undefined relationship 'devisi'`

- Nama relasi/tabel yang benar adalah `divisi`, melalui FK
  `karyawan.id_divisi → divisis.id` (nama tabel jamak `divisis`, tapi relasi Eloquent
  disarankan bernama `divisi()` — singular — konsisten dengan penggunaan di
  service/controller yang sudah ada).
- Ganti seluruh pemanggilan relasi `devisi` (termasuk di query builder / eager load
  `with('devisi')`) menjadi `divisi` yang benar-benar terdaftar di model `Karyawan`.
- **Business rule Division Head / Atasan**:
    - Approver hanya boleh melihat/memproses pengajuan cuti dengan
      `cuti.status_persetujuan = 'pending'` dari karyawan yang berada pada divisi yang
      sama dengan divisi approver tersebut — dibandingkan lewat `karyawan.id_divisi`
      milik pemohon vs `karyawan.id_divisi` milik approver (bukan berdasarkan `id_atasan`
      yang di-hardcode per baris cuti, kecuali test memang mengharuskan demikian).
    - Saat approval disetujui/ditolak, pastikan konsisten antara:
        - `cuti.status_persetujuan`, `cuti.tanggal_persetujuan`, `cuti.id_atasan`, dan
        - baris terkait di `persetujuan_cuti` (`id_penyetuju`, `status_persetujuan`,
          `tanggal_persetujuan`, `catatan`).
    - Jika approval disetujui dan terdapat baris `kuota_cuti_karyawan` yang cocok
      (`id_karyawan` + `leave_type_id` + tahun berjalan), logic pengurangan
      `remaining_quota` yang sudah ada harus tetap berjalan — jangan mengubah alurnya,
      hanya benahi query/relasi yang salah nama.
    - Jangan mengubah kolom atau menambah tabel baru untuk keperluan ini — gunakan
      struktur `cuti` + `persetujuan_cuti` + `kuota_cuti_karyawan` yang sudah ada di SOT.

## Rules (wajib dipatuhi)

- `hris_db.sql` adalah satu-satunya Source of Truth untuk nama tabel, kolom, dan tipe
  data. Jika ada perbedaan antara asumsi di kode dan SOT, SOT yang menang.
- Jangan mengubah migration, schema SQL, atau file PHPUnit test yang sudah ada.
- Modifikasi HANYA diperbolehkan pada: Model (relationship), Service, Controller,
  Repository/Query, dan Seeder — agar seluruh test menjadi PASS.
- Jika ditemukan relasi/kolom yang dipakai di kode tapi tidak ada di SOT, relasi/kolom
  tersebut yang harus disesuaikan (dihapus/diganti), bukan menambah kolom baru ke
  database.
- Semua perbaikan harus tetap menghormati batasan hak akses per role (Karyawan/
  Atasan/HR) — jangan melonggarkan atau mengetatkan scope akses di luar yang dituntut
  oleh test.
- Setelah perbaikan, jalankan ulang seluruh PHPUnit test dan pastikan tidak ada test
  yang sebelumnya PASS menjadi FAIL (no regression).

### Revision

## Catatan arsitektur penting (baca dulu sebelum mulai)

Sistem saat ini punya **dua sumber otorisasi yang tidak sinkron**:

1. **`Jabatan`** (`app/Models/Jabatan.php`, kolom `nama_jabatan` — mis. "SDM", "Manager Divisi", "Manager Umum") → hanya label jabatan karyawan, **tidak dipakai untuk kontrol akses/menu/approval sama sekali**.
2. **Spatie `Role`** (`database/seeders/RoleSeeder.php`, `RolePermissionSeeder.php` — `admin`, `hr`, `hrd`, `atasan_divisi`, `manager`, `supervisor`, `karyawan`) → ini yang benar-benar dipakai untuk:
    - Visibilitas menu (`resources/views/layouts/partials/nav-items.blade.php` → `MenuItem::isAccessibleByRole($userRole)`)
    - Approval cuti (`app/Services/CutiApprovalService.php` → cek `hasRole('atasan_divisi')`, `hasRole('admin')`, dst.)
    - Akses absensi (`app/Services/AuthorizationService.php` → cek `hasRole('admin')`, `hasRole('hr')`)

**Akar masalah poin A, F, dan G** adalah karena pembagian akses yang diminta klien (berbasis **Jabatan**: SDM, Manager Divisi, Manager Umum, Karyawan) belum dipetakan ke **Role** Spatie yang sebenarnya mengontrol sistem. Jadi walaupun `Jabatan` "SDM" / "Manager Divisi" / "Manager Umum" sudah ada di seeder (`JabatanSeeder.php`), user dengan jabatan tersebut belum tentu punya Role yang sesuai (`admin` / `atasan_divisi`, dll).

**Rekomendasi pendekatan revisi:** buat mapping eksplisit Jabatan → Role (baik lewat seeder/assignment saat create/update karyawan, atau lewat logic di `AuthorizationService`), lalu jadikan `AuthorizationService` sebagai satu-satunya sumber kebenaran untuk keputusan akses (menu, cuti, approval, absensi), menggantikan pengecekan role yang tersebar di `CutiApprovalService`, `nav-items.blade.php`, dll.

---

## [A] Pembagian Menu berdasarkan Jabatan

**Requirement dari klien:**

- Jabatan **SDM** → Role **Admin** → akses semua menu; menu Cuti untuk dirinya sendiri saja (bukan mengajukan untuk orang lain — lihat poin G).
- Jabatan **Manager Divisi** & **Manager Umum** → Role **Atasan** → akses: Dashboard, menu Cuti (pengajuan untuk diri sendiri + approval bawahan), Jadwal Kerja.
- Jabatan selain itu → **Karyawan** → akses: Dashboard, menu Cuti (untuk dirinya), Jadwal (untuk dirinya).

**Kondisi saat ini:**

- Menu di-render berdasarkan Spatie Role via `MenuItem::isAccessibleByRole($userRole)` (`resources/views/layouts/partials/nav-items.blade.php`), bukan berdasarkan `nama_jabatan`.
- Tidak ada mapping otomatis dari `Jabatan` (SDM/Manager Divisi/Manager Umum) ke Role yang sesuai.

**Yang perlu dikerjakan:**

1. Buat mapping Jabatan → Role (bisa via kolom baru `role_default` di tabel `jabatans`, atau lookup table di service), minimal untuk 3 kelompok: SDM→admin, {Manager Divisi, Manager Umum}→atasan (role `atasan_divisi` atau role baru yang mewakili "Atasan"), lainnya→`karyawan`.
2. Terapkan mapping ini saat karyawan dibuat/diedit di `KaryawanController@store` / `@update`, atau via job/console command migrasi untuk data existing.
3. Audit `role_menu_permissions` (`database/migrations/2026_05_04_125626_create_role_menu_permissions_table.php`) dan `MenuItemSeeder.php` agar konfigurasi menu per role sesuai daftar akses di atas.
4. Pastikan menu Cuti untuk role Atasan menampilkan **dua** hal: form pengajuan cuti untuk dirinya + halaman approval bawahan (`cuti.approval`), bukan cuma salah satu.

**Acceptance criteria:**

- Login sebagai karyawan berjabatan "SDM" → melihat seluruh menu, menu Cuti hanya bisa mengajukan untuk dirinya sendiri (dropdown pilih karyawan lain hilang/disabled — lihat poin G).
- Login sebagai "Manager Divisi"/"Manager Umum" → melihat Dashboard, Cuti (pengajuan + approval), Jadwal Kerja saja.
- Login sebagai jabatan lain → melihat Dashboard, Cuti (miliknya), Jadwal (miliknya) saja.

---

## [B] Kuota Cuti di halaman Detail Karyawan harus menampilkan semua jenis cuti

**Kondisi saat ini (bug konkret ditemukan):**
File `resources/views/employees/karyawan_show.blade.php`, bagian "Kuota Cuti" saat ini:

```blade
<span class="text-muted small">Kuota Cuti</span>
<span class="fw-semibold small">{{ $karyawan->status_karyawan ?? 0 }}/{{ $karyawan->status_karyawan ?? 0 }} hari</span>
```

Ini bug — menampilkan `status_karyawan` (string status kepegawaian seperti "Tetap", bukan angka kuota) di kedua sisi pecahan, makanya selalu tampil **0/0 hari** di screenshot. Field ini juga tidak mengambil data dari sistem kuota cuti yang sudah ada (`app/Models/KuotaCutiKaryawan.php`, di-generate oleh `app/Services/LeaveQuotaService.php`), dan tidak dipisah per jenis cuti (Tahunan, Hari Raya, Sakit).

Selain itu, `KaryawanController@show` saat ini hanya eager-load `['jabatan', 'divisi', 'absensi', 'cuti']` — **tidak** load relasi kuota cuti sama sekali.

**Yang perlu dikerjakan:**

1. Di `KaryawanController@show`, panggil `LeaveQuotaService::ensureBalancesFor($karyawan)` (service sudah ada) untuk mendapatkan seluruh `KuotaCutiKaryawan` tahun berjalan per jenis cuti, lalu kirim ke view.
2. Di `karyawan_show.blade.php`, ganti blok "Kuota Cuti" jadi list/loop menampilkan **setiap jenis cuti aktif** (Cuti Tahunan, Cuti Hari Raya, Cuti Sakit, dan jenis baru ke depannya) dengan format `sisa/total hari`, bukan hanya satu baris hardcoded.

**Acceptance criteria:**

- Halaman Detail Karyawan menampilkan seluruh jenis cuti yang berlaku untuk status karyawan tsb, masing-masing dengan sisa kuota yang benar (bukan 0/0 kecuali memang kuotanya 0), sesuai `LeaveQuotaService`.

---

## ABSENSI

## [C] Aturan absen pulang harus sesuai jadwal shift (pagi/siang), bukan "minimal 5 jam" generik

**Requirement dari klien:** absen pulang seharusnya tersedia berdasarkan jam berakhir shift sesuai jadwal kerja hari itu (Pagi 08:00–17:00 atau Siang 13:00–22:00), bukan sekadar minimal 5 jam sejak jam masuk.

**Kondisi saat ini:**
`app/Services/AttendanceService.php` → method `resolveClockOutAvailability()` sebenarnya **sudah** punya logic berbasis shift (`$shiftWindowStart` dihitung dari `jam_pulang` shift dikurangi 30 menit, via `JadwalKerja`/`Shift` model), TAPI:

- Kalau tidak ditemukan `JadwalKerja` untuk hari itu ATAU shift tidak match, sistem fallback ke aturan **flat 5 jam** (`$minimumClockOutAt = clockInAt->addHours(5)`), yang menghasilkan pesan generik "Absen pulang tersedia minimal 5 jam..." — inilah yang muncul di screenshot 2.
- Ada **bug angka desimal aneh** di pesan: _"Sisa 261.21002045 menit lagi"_ — kemungkinan dari `diffInMinutes()` yang mengembalikan float/tidak dibulatkan sebelum ditampilkan di frontend, kemungkinan besar karena data `jam_masuk` yang tersimpan tidak wajar (misal timezone/format salah) sehingga selisih jadi sangat besar dan tidak presisi. Perlu ditelusuri sumber datanya juga (kemungkinan `jam_masuk` tersimpan dengan tanggal berbeda / offset timezone tidak konsisten dengan `Carbon::today()`).

**Yang perlu dikerjakan:**

1. Pastikan setiap karyawan **selalu** punya `JadwalKerja` (Pagi/Siang) untuk hari berjalan sebelum absen — kalau tidak ada jadwal, tampilkan pesan yang jelas ("Belum ada jadwal kerja hari ini, hubungi Admin/SDM") daripada fallback ke rule 5 jam generik. Cek proses assignment jadwal di `app/Services/JadwalBulkService.php` / `JadwalKerjaController`.
2. Hilangkan/kurangi ketergantungan pada fallback `minimumClockOutAt` flat 5 jam sebagai aturan utama — jadikan fallback darurat saja, dan sinkronkan pesannya dengan bahasa shift ("Absen pulang tersedia setelah jam pulang shift Pagi (17:00)"), bukan "minimal 5 jam".
3. Perbaiki perhitungan `diffInMinutes` agar selalu integer bulat dan masuk akal (bulatkan dengan `round()`/`intval`, dan tambahkan validasi/logging kalau selisihnya di luar rentang wajar, misal >1440 menit, sebagai tanda ada bug data jam_masuk).
4. Update copy pesan di frontend `resources/views/absensi/attendance_index.blade.php` (bagian info box "Pilih Aksi") agar dinamis mengikuti shift karyawan yang dipilih, bukan teks statis "minimal 5 jam".

**Acceptance criteria:**

- Karyawan shift Pagi (08:00–17:00) check-in jam 08:00 → tombol/absen pulang baru aktif mendekati jam 17:00 (bukan jam 13:00 hasil dari rule "5 jam").
- Tidak ada lagi pesan dengan angka menit desimal aneh; semua nilai menit yang ditampilkan adalah bilangan bulat wajar.

## [D] Update juga data terkait di Dashboard

**Konteks:** ini instruksi umum agar perubahan pada modul Absensi (poin C) juga direfleksikan konsisten di Dashboard.

**Lokasi terkait:** `app/Http/Controllers/DashboardController.php` — sudah menghitung `dailyAttendanceSummary`, `todayAttendanceRows`, dll dari tabel `Absensi`/`JadwalKerja` yang sama.

**Yang perlu dikerjakan:**

- Setelah perbaikan poin C (terutama jika ada perubahan status/definisi "terlambat", jam pulang, atau field baru terkait shift), pastikan query & kartu ringkasan di `DashboardController@index` dan view `resources/views/dashboard/dashboard.blade.php` ikut disesuaikan — jangan sampai Dashboard menampilkan angka yang tidak konsisten dengan halaman Absensi setelah perbaikan.
- Lakukan regression check pada widget: total hadir, terlambat, belum absen, cuti approved hari ini (`$dailyAttendanceSummary`) dan tabel `todayAttendanceRows`.

**Acceptance criteria:**

- Angka-angka di Dashboard (hadir/terlambat/belum absen) match dengan data riil di halaman Absensi & Riwayat Absensi setelah perbaikan poin C.

---

## CUTI

## [E] Semua jenis cuti harus muncul di daftar saat pengajuan cuti

**Jenis cuti yang wajib tersedia (sudah didefinisikan di `database/seeders/LeaveTypeSeeder.php`):**
| Jenis Cuti | Kuota | Berlaku untuk status |
|---|---|---|
| Cuti Tahunan | 12 hari | **Tetap** saja |
| Cuti Hari Raya | 4 hari | Semua status |
| Cuti Sakit | 6 hari | Semua status |

**Kondisi saat ini:**
Di `app/Http/Controllers/CutiController.php@create`, daftar jenis cuti yang dikirim ke form (`jenisCuti`) berbeda tergantung siapa yang login:

- Kalau **admin**: ambil semua `TipeCuti::where('is_active', true)` → lengkap.
- Kalau **karyawan biasa**: ambil dari `LeaveQuotaService::ensureBalancesFor($karyawan)` yang difilter `applicableTypesFor()` — Cuti Tahunan hanya muncul kalau `status_karyawan === 'Tetap'` (lihat `LeaveQuotaService::isAnnualLeave()` + `defaultQuotaFor()`). Karyawan dengan status selain "Tetap" **tidak akan melihat Cuti Tahunan** di daftar — ini kemungkinan besar penyebab laporan "jenis cuti belum semua muncul".

**Yang perlu dikerjakan:**

1. Konfirmasi ke klien: apakah memang Cuti Tahunan hanya untuk karyawan status Tetap (by design), atau semua jenis cuti harus muncul untuk semua karyawan terlepas status. Kalau by design, maka bukan bug — cukup tambahkan **keterangan di UI** ("Cuti Tahunan hanya berlaku untuk karyawan tetap") di `resources/views/cuti/create.blade.php` supaya tidak terlihat seperti error.
2. Jika ternyata seharusnya semua jenis cuti tetap muncul (hanya kuotanya 0 untuk yang tidak berlaku), sesuaikan `LeaveQuotaService::applicableTypesFor()` agar tidak meng-exclude jenis cuti, cukup set kuota 0 dan disable opsi tersebut di form dengan alasan yang jelas.
3. Pastikan `TipeCuti::is_active` untuk ketiga jenis cuti di atas benar-benar `true` di data production (cek lewat `TipeCuti` model/seeder run).

**Acceptance criteria:**

- Saat karyawan (status apapun) membuka form pengajuan cuti, ketiga jenis cuti tampil di dropdown/list — baik bisa dipilih (kuota > 0) maupun tampil non-aktif dengan keterangan alasan (kuota tidak berlaku untuk status karyawan tsb).

## [F] Line approval cuti harus berjenjang: SDM → Manager Divisi → Manager Umum

**Requirement dari klien:** alur approval cuti harus berjenjang 3 level (SDM → Manager Divisi → Manager Umum), dan daftar approval harus muncul di akun approver terkait (bukan cuma tampil di akun Admin).

**Kondisi saat ini:**
`app/Services/CutiApprovalService.php` hanya mendukung **1 level approval**: `atasan_divisi` di divisi yang sama (`findDivisionHeadFor()`, `pendingQueryFor()`, `canUpdateStatus()`). Tidak ada logic berjenjang (SDM dulu, baru diteruskan ke Manager Divisi, baru ke Manager Umum). `pendingQueryFor()` untuk role selain admin/hr/hrd/atasan_divisi akan mengembalikan query kosong (`whereRaw('1=0')`) — inilah sebab "list approval baru muncul di admin aja": user dengan jabatan Manager Divisi/Manager Umum kemungkinan besar belum diberi role `atasan_divisi` (lihat catatan arsitektur di atas), sehingga query approval mereka selalu kosong.

**Yang perlu dikerjakan:**

1. **Prasyarat:** selesaikan mapping Jabatan → Role di poin A, supaya user dengan jabatan Manager Divisi/Manager Umum benar-benar punya role approval.
2. Tambahkan konsep **level approval** pada model `Cuti` (`app/Models/Cuti.php`, tabel `persetujuan_cuti` sudah dibuat via migration `2026_06_29_000000_create_persetujuan_cuti_table.php` — cek apakah tabel ini sudah dipakai; kalau belum, di sinilah tempat menyimpan histori approval tiap level).
3. Ubah alur di `CutiController@store` & `CutiApprovalService`:
    - Cuti diajukan karyawan → status awal **pending, level 1 (SDM)**.
    - SDM approve → status **pending, level 2 (Manager Divisi)**.
    - Manager Divisi approve → status **pending, level 3 (Manager Umum)**.
    - Manager Umum approve → status **approved** (baru di sini `LeaveQuotaService::decrementForApprovedLeave()` dipanggil).
    - Reject di level manapun → status **rejected**, proses berhenti.
4. `CutiApprovalService::pendingQueryFor()` harus difilter berdasarkan level approval saat ini + role/jabatan approver yang login, bukan hanya divisi yang sama.
5. Update view `resources/views/cuti/approval.blade.php` untuk menampilkan level approval saat ini pada tiap baris pengajuan.

**Acceptance criteria:**

- Karyawan mengajukan cuti → muncul di daftar approval milik akun SDM.
- SDM approve → hilang dari daftar SDM, muncul di daftar akun Manager Divisi terkait.
- Manager Divisi approve → muncul di daftar akun Manager Umum.
- Manager Umum approve → status final "approved", kuota cuti terpotong.
- Reject di level manapun langsung menghentikan alur dan mengubah status jadi "rejected".

## [G] SDM/Admin hanya boleh mengajukan cuti untuk dirinya sendiri, tidak untuk orang lain

**Requirement dari klien:** karena akun Admin adalah representasi jabatan SDM, di menu Cuti dia hanya boleh mengajukan cuti untuk dirinya sendiri — bukan atas nama karyawan lain. Pengajuan cuti wajib dilakukan oleh masing-masing orang.

**Kondisi saat ini (bug konkret):**
`app/Http/Controllers/CutiController.php`:

- `create()`: `$karyawanList = $isAdmin ? Karyawan::orderBy('nama')->get() : collect();` → kalau login sebagai admin, form pengajuan menampilkan **dropdown pilih karyawan lain**.
- `store()`: validasi `'id_karyawan' => $isAdmin ? 'required|exists:karyawan,id_karyawan' : 'nullable'` dan `$karyawan = $isAdmin ? Karyawan::findOrFail($request->id_karyawan) : ...` → admin **bisa submit cuti atas nama karyawan manapun**, dan otomatis **auto-approved** (`'status_persetujuan' => $isAdmin ? 'approved' : 'pending'`).

Ini bertentangan langsung dengan requirement: admin (SDM) mengajukan cuti hanya untuk dirinya sendiri, statusnya pun tetap harus melalui approval (bukan auto-approved), karena SDM sendiri berada di level 1 approval (lihat poin F).

**Yang perlu dikerjakan:**

1. Hapus logic `$karyawanList` dan dropdown pilih karyawan lain di `cuti/create.blade.php` untuk role admin — form pengajuan cuti selalu berdasarkan karyawan yang login (`Karyawan::where('id_user', $user->id_user)`), **tanpa pengecualian untuk admin**.
2. Hapus branch `$isAdmin` di `store()` yang meng-auto-approve cuti sendiri; cuti admin/SDM tetap masuk sebagai `pending` dan mengikuti alur approval berjenjang (poin F), mulai dari level Manager Divisi (karena SDM sendiri yang ada di level 1, tidak mungkin approve pengajuannya sendiri — atau tentukan approver khusus untuk pengajuan SDM, misal langsung ke Manager Umum).
3. Sisakan kemampuan admin untuk **melihat semua** pengajuan cuti (`CutiController@index`, `@history`) — ini boleh tetap, yang dihilangkan hanya kemampuan mengajukan **atas nama** orang lain.

**Acceptance criteria:**

- Login sebagai admin (SDM) → form Cuti tidak ada pilihan karyawan lain, hanya mengajukan untuk dirinya sendiri.
- Pengajuan cuti oleh admin berstatus "pending" (bukan langsung "approved"), dan masuk ke alur approval yang sesuai.

## [H] Tampilkan data Cuti (kuota + riwayat) di halaman Detail Karyawan

**Kondisi saat ini:**
`KaryawanController@show` sudah eager-load relasi `cuti` (riwayat pengajuan), tapi:

- Belum menyertakan data kuota cuti per jenis (terkait langsung dengan perbaikan poin B).
- Section "Riwayat Cuti" di `karyawan_show.blade.php` perlu dicek ulang setelah poin E/F/G selesai, supaya menampilkan jenis cuti yang benar, status approval (termasuk level approval dari poin F), dan history yang update real-time begitu ada pengajuan baru.

**Yang perlu dikerjakan:**

1. Pastikan `karyawan_show.blade.php` menampilkan:
    - Kuota cuti per jenis (hasil perbaikan poin B).
    - Riwayat cuti lengkap: jenis cuti, tanggal, status (pending/level approval saat ini/approved/rejected), nama approver.
2. Uji dengan data karyawan yang sudah pernah mengajukan cuti untuk memastikan riwayat tidak kosong ("Belum ada riwayat cuti") padahal datanya ada.

**Acceptance criteria:**

- Halaman Detail Karyawan menampilkan kuota cuti per jenis dan riwayat cuti yang akurat & real-time, konsisten dengan data di menu Cuti/Approval.

---

## Rangkuman Prioritas Pengerjaan (disarankan)

1. **A** — mapping Jabatan → Role (fondasi untuk F & G).
2. **G** — hapus kemampuan admin mengajukan cuti untuk orang lain.
3. **F** — approval berjenjang SDM → Manager Divisi → Manager Umum.
4. **E** — audit tampilan jenis cuti di form pengajuan.
5. **B, H** — perbaikan tampilan kuota & riwayat cuti di Detail Karyawan (bug `status_karyawan` di `karyawan_show.blade.php`).
6. **C, D** — perbaikan aturan absen pulang berbasis shift + sinkronisasi Dashboard.

## File-file kunci yang akan tersentuh

- `app/Services/AuthorizationService.php`, `app/Services/CutiApprovalService.php`, `app/Services/MenuVisibilityService.php`
- `app/Services/LeaveQuotaService.php`, `app/Services/AttendanceService.php`
- `app/Http/Controllers/CutiController.php`, `AttendanceController.php`, `KaryawanController.php`, `DashboardController.php`
- `app/Models/Jabatan.php`, `Cuti.php`, `KuotaCutiKaryawan.php`, `TipeCuti.php`
- `resources/views/cuti/create.blade.php`, `cuti/approval.blade.php`, `employees/karyawan_show.blade.php`, `absensi/attendance_index.blade.php`, `dashboard/dashboard.blade.php`, `layouts/partials/nav-items.blade.php`
- `database/seeders/RoleSeeder.php`, `RolePermissionSeeder.php`, `JabatanSeeder.php`, `LeaveTypeSeeder.php`
- Migration `2026_06_29_000000_create_persetujuan_cuti_table.php` (kemungkinan perlu dipakai untuk approval berjenjang di poin F)

## TODO (Check if Done):

[ ] Explore codebase structure and understand current state
[ ] [A] Mapping Jabatan → Role for menu access
[ ] [B] Fix kuota cuti display on detail karyawan page
[ ] [C] Fix absen pulang rule based on shift schedule
[ ] [D] Sync dashboard with attendance changes
[ ] [E] All leave types must appear in leave application form
[ ] [F] Multi-level leave approval (SDM → Manager Divisi → Manager Umum)
[ ] [G] Admin/SDM can only apply leave for themselves
[ ] [H] Show leave quota & history on detail karyawan page

## Notes

To address the issues in the HRIS application, we'll tackle each point systematically. Here's a concise plan:

1. Role Mapping (Point A):

- Create a mapping between job positions and roles in AuthorizationService.php.
- Update KaryawanController to assign roles based on job position during user creation.

2. Leave Quota Display (Point B):

- Modify KaryawanController@show to fetch leave quota data using LeaveQuotaService.
- Update karyawan_show.blade.php to display all leave types with their respective quotas.

3. Attendance Check-Out Rule (Point C):

- Adjust AttendanceService.php to calculate check-out time based on shift schedules.
- Update the UI in attendance_index.blade.php to reflect shift-specific rules.

4. Dashboard Updates (Point D):

- Modify DashboardController@index to fetch updated attendance data.
- Update dashboard.blade.php to display accurate attendance metrics.

5. Leave Type Visibility (Point E):

- Update CutiController@create to include all active leave types.
- Modify create.blade.php to show all leave types, disabling those with zero quota.

6. Multi-Level Approval (Point F):

- Add approval levels to CutiApprovalService.php.
- Update approval.blade.php to display the current approval level.

7. Admin Leave Restrictions (Point G):

- Remove the employee selection dropdown in create.blade.php for admins.
- Ensure leaves are only applied for the admin themselves, following the approval流程.

8. Leave History Display (Point H):

- Fetch and display leave history in karyawan_show.blade.php, showing types, statuses, and approvers.
  Each change will be implemented with thorough testing to ensure functionality and stability.
