# ERD HRIS Saat Ini

ERD ini mengikuti implementasi operasional aplikasi setelah update import wajah, import karyawan, bulk jadwal, dashboard rekap, dan approval atasan divisi.

Sesuai request, menu/modul yang disembunyikan lewat flagging tidak ditampilkan sebagai entitas ERD: Role Management, Hak Akses, Divisi, dan Jabatan. Field `id_devisi` dan `id_jabatan` tetap ada di `karyawan` karena masih dipakai data karyawan dan approval divisi.

```mermaid
erDiagram
    USERS ||--|| KARYAWAN : dimiliki
    KARYAWAN ||--o{ ABSENSI : memiliki
    KARYAWAN ||--o{ CUTI : mengajukan
    KARYAWAN ||--o{ JADWAL_KERJA : memiliki
    KARYAWAN ||--o{ CUTI : menjadi_atasan
    SHIFTS ||--o{ KARYAWAN : default_shift
    SHIFTS ||--o{ JADWAL_KERJA : shift_jadwal

    USERS {
        bigint id_user PK
        string username
        string email
        string password
        string role
        timestamp email_verified_at
        timestamp created_at
        timestamp updated_at
    }

    KARYAWAN {
        bigint id_karyawan PK
        bigint id_user FK
        string nama
        bigint id_jabatan
        bigint id_devisi
        string kode_shift FK
        date tanggal_masuk
        date tanggal_mulai_kerja
        string status_aktif
        string status_karyawan
        int yearly_leave_quota
        int remaining_leave_quota
        text face_embedding
        timestamp created_at
        timestamp updated_at
    }

    SHIFTS {
        bigint id_shift PK
        string kode_shift UK
        string nama_shift
        time jam_masuk
        time jam_pulang
        timestamp created_at
        timestamp updated_at
    }

    ABSENSI {
        bigint id_absensi PK
        bigint id_karyawan FK
        date tanggal
        time jam_masuk
        time jam_pulang
        string status
        int menit_terlambat
        bigint recorded_by
        boolean face_verified
        float face_confidence
        string photo_hash
        decimal gps_lat
        decimal gps_lng
        text device_info
        string ip_address
        boolean is_locked
        timestamp created_at
        timestamp updated_at
    }

    CUTI {
        bigint id_cuti PK
        bigint id_karyawan FK
        string jenis_cuti
        date tanggal_mulai
        date tanggal_selesai
        text keterangan
        date tanggal_persetujuan
        string status_persetujuan
        bigint id_atasan FK
        timestamp created_at
        timestamp updated_at
    }

    JADWAL_KERJA {
        bigint id_jadwal PK
        bigint id_karyawan FK
        date tanggal
        string jam_kerja
        string kode_shift FK
        text keterangan
        timestamp created_at
        timestamp updated_at
    }

    LEAVE_TYPES {
        bigint id PK
        string nama_cuti
        int default_quota
        string applies_to_status
        boolean is_active
        timestamp created_at
        timestamp updated_at
    }
```
