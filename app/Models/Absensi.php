<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Absensi extends Model
{
    use HasFactory;

    protected $table = 'absensi';
    protected $primaryKey = 'id_absensi';

    protected $fillable = [
        'id_karyawan',
        'tanggal',
        'jam_masuk',
        'jam_pulang',
        'status',
        'menit_terlambat',
        // audit fields
        'recorded_by',
        'face_verified',
        'face_confidence',
        'photo_hash',
        'gps_lat',
        'gps_lng',
        'device_info',
        'ip_address',
        'is_locked',
    ];

    protected $casts = [
        'tanggal'          => 'date',
        'menit_terlambat'  => 'integer',
        'face_verified'    => 'boolean',
        'face_confidence'  => 'float',
        'gps_lat'          => 'float',
        'gps_lng'          => 'float',
        'is_locked'        => 'boolean',
    ];

    // Statuses available for admin-recorded attendance
    const STATUSES = [
        'hadir'        => 'Hadir',
        'terlambat'    => 'Terlambat',
        'remote'       => 'Remote / WFH',
        'tidak_hadir'  => 'Tidak Hadir',
    ];

    // Statuses that require face verification
    const FACE_REQUIRED_STATUSES = ['hadir', 'terlambat', 'remote'];

    // Relationship ke Karyawan
    public function karyawan()
    {
        return $this->belongsTo(Karyawan::class, 'id_karyawan', 'id_karyawan');
    }

    // Relationship ke User (admin who recorded)
    public function recorder()
    {
        return $this->belongsTo(User::class, 'recorded_by', 'id_user');
    }

    /**
     * Human-readable status label
     */
    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst($this->status ?? '-');
    }

    /**
     * Bootstrap colour for the status badge
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'hadir'       => 'success',
            'terlambat'   => 'warning',
            'remote'      => 'info',
            'tidak_hadir' => 'danger',
            // legacy values kept working
            'tepat_waktu' => 'success',
            default       => 'secondary',
        };
    }
}
