# AGENT.md — HRIS Norton Bali Computer

## Prinsip Dasar

Gunakan `hris_db.sql` sebagai satu-satunya Source of Truth (SOT) untuk struktur data.
Jangan mengubah schema database (migration) maupun isi file PHPUnit test.
Modifikasi HANYA diperbolehkan pada: Model (relationship), Service, Controller,
Repository/Query, dan Seeder — agar seluruh test menjadi PASS.

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
