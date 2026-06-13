# Manual Test Checklist

## Commands

Run these after pulling the changes:

```bash
php artisan migrate
php artisan db:seed --class=RoleSeeder
php artisan db:seed --class=RolePermissionSeeder
php artisan db:seed --class=LeaveTypeSeeder
php artisan db:seed --class=RealEmployeeSeeder
php artisan config:clear
php artisan cache:clear
```

Optional env values:

```env
HRIS_DEFAULT_IMPORT_PASSWORD=change-this-default-password
HRIS_FLAGGING_SECRET=
```

For CSV face image import, place images under:

```text
storage/app/imports/faces
```

## Real Employee Seeder

- Set `HRIS_DEFAULT_IMPORT_PASSWORD` before running `RealEmployeeSeeder`.
- Run `php artisan migrate`.
- Run `php artisan db:seed` or run specific seeders:
  `RolePermissionSeeder`, `DevisiSeeder`, `JabatanSeeder`, `ShiftSeeder`, `LeaveTypeSeeder`, `RealEmployeeSeeder`.
- Re-run the same seeder command and confirm users/karyawan/divisi/jabatan/shift are updated, not duplicated.
- Confirm divisions exist: NBCS, NSC1, NSC2, Office.
- Confirm positions exist: Manager Divisi, Wakil Manager Divisi, Kasir, Customer Service, Teknisi, Supervisor, Wakil Manager Umum, Accounting, SDM, Manager Umum, Online Marketing.
- Confirm shifts exist: Pagi 08:00-17:00 and Siang 13:00-22:00.
- Confirm `tanggal_mulai_kerja`, `status_aktif`, and `status_karyawan` are filled for seeded employees.
- Confirm employees with Manager/Wakil Manager/Supervisor positions receive `atasan_divisi` role.
- Confirm Tetap employees get annual quota 12, while Kontrak/Training get annual quota 0.

## Import Manual Wajah

- Open `Karyawan > Import Wajah`.
- Select a karyawan and upload `.jpg`, `.png`, or `.webp` under 2 MB.
- Confirm `karyawan.face_embedding` is filled.
- Open detail karyawan and confirm the uploaded face image appears as a thumbnail.
- Open existing camera registration page and confirm camera registration still works.
- Try an invalid file type and confirm a friendly validation error appears.

## Import Karyawan CSV

- Download template from `Karyawan > Import Karyawan`.
- Import a CSV, JSON, and XLSX with matching headers and valid `kode_shift`.
- Import `karyawan-dataset/data-karyawan.csv`; confirm headers `Nama Lengkap`, `Divisi`, `Posisi`, `Mulai Kerja`, `Aktif`, and `Status` are accepted.
- Import the same row again and confirm it is skipped instead of duplicating or updating user/karyawan.
- Fill `tanggal_mulai_kerja`, `status_aktif`, and `status_karyawan`; confirm all three fields are saved.
- Use a new `nama_devisi` and `nama_jabatan`; confirm both are auto-created.
- Add `face_image_path` pointing to `storage/app/imports/faces/<file>` with `.jpg`, `.png`, or `.webp` and confirm `face_embedding` plus compressed preview are saved.
- Import a row with invalid `kode_shift`; confirm the row is failed in summary and other rows still process.
- Import a new user without password while `HRIS_DEFAULT_IMPORT_PASSWORD` is blank; confirm a friendly failed row.

## Hidden Menu Flagging

- Confirm Role Management, Hak Akses, Divisi, and Jabatan are hidden by default in sidebar.
- Login as admin and open `/flagging` or `/flagging?token=<HRIS_FLAGGING_SECRET>` when secret is set.
- Toggle hidden menus and confirm sidebar updates.
- Open a direct URL such as `/divisi`; confirm existing permission/routing behavior still applies.

## Bulk Range Jadwal

- Open `Jadwal Kerja > Input Jadwal Massal`.
- Use existing one-day bulk form and confirm old flow still creates schedules.
- Use Bulk Range with target all karyawan and overwrite false; confirm duplicate schedules are skipped.
- Repeat with overwrite true; confirm existing schedules are updated.
- Use target by divisi and target karyawan tertentu.
- Submit tanggal selesai before tanggal mulai; confirm validation error.

## Dashboard Rekap Hari Ini

- Login as admin and HR/HRD.
- Confirm cards show total karyawan, sudah absen masuk, belum absen, terlambat, tepat waktu/hadir, tidak hadir, and cuti approved.
- Confirm table "Absensi Hari Ini" shows nama, divisi, jabatan, jadwal/shift, jam masuk, jam pulang, and status.
- Login as employee and confirm personal dashboard still appears.

## Approval Atasan Divisi

- Confirm `leave_types` has Cuti Tahunan 12 days for Tetap, Cuti Hari Raya 4 days, and Cuti Sakit 6 days.
- Submit and approve Cuti Hari Raya; confirm Hari Raya balance decreases while yearly annual balance stays unchanged.
- Submit Cuti Tahunan from a Kontrak/Training employee; confirm backend rejects it because the type is not applicable.
- Seed or assign role `atasan_divisi` to a user with a karyawan record and `id_devisi`.
- Submit cuti from employee in same divisi; confirm `cuti.id_atasan` is set.
- Login as atasan divisi A and confirm only pending cuti from divisi A appears.
- Confirm atasan divisi A cannot approve/reject cuti divisi B via direct PATCH.
- Confirm atasan divisi cannot approve/reject their own cuti.
- Login as HR/HRD and confirm approval page is readonly and direct PATCH returns 403.
- Login as admin and confirm admin can approve/reject any pending cuti.
- Login as employee and confirm approval page is not accessible.
