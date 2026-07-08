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
        'recorded_by',
        'face_verified',
        'face_confidence',
        'photo_hash',
    ];

    protected $casts = [
        'tanggal'          => 'date',
        'menit_terlambat'  => 'integer',
        'face_verified'    => 'boolean',
        'face_confidence'  => 'float',
    ];

    const STATUSES = [
        'hadir'        => 'Hadir',
        'terlambat'    => 'Terlambat',
        'remote'       => 'Remote / WFH',
        'tidak_hadir'  => 'Tidak Hadir',
    ];

    const FACE_REQUIRED_STATUSES = ['hadir', 'terlambat', 'remote'];

    public function karyawan()
    {
        return $this->belongsTo(Karyawan::class, 'id_karyawan', 'id_karyawan');
    }

    public function recorder()
    {
        return $this->belongsTo(User::class, 'recorded_by', 'id_user');
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst($this->status ?? '-');
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'hadir'       => 'success',
            'terlambat'   => 'warning',
            'remote'      => 'info',
            'tidak_hadir' => 'danger',
            'tepat_waktu' => 'success',
            default       => 'secondary',
        };
    }
}
