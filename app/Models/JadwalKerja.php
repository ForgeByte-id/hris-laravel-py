<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JadwalKerja extends Model
{
    use HasFactory;

    protected $table = 'jadwal_kerja';
    protected $primaryKey = 'id_jadwal';

    protected $fillable = [
        'id_karyawan',
        'tanggal',
        'jam_kerja',
        'keterangan',
    ];

    protected $casts = [
        'tanggal' => 'date',
    ];

    // Relationship ke Karyawan
    public function karyawan()
    {
        return $this->belongsTo(Karyawan::class, 'id_karyawan', 'id_karyawan');
    }

    // Check apakah hari libur
    public function isLibur()
    {
        return $this->jam_kerja === 'Libur';
    }

    // Get badge color untuk jam kerja
    public function getShiftColorAttribute()
    {
        return match($this->jam_kerja) {
            'Pagi (08:00-17:00)' => '#4CAF50',
            'Middle (11:00-20:00)' => '#FF9800',
            'Siang (13:00-22:00)' => '#2196F3',
            'Libur' => '#f44336',
            default => '#9E9E9E'
        };
    }

    // Get shift short name
    public function getShiftShortAttribute()
    {
        return match($this->jam_kerja) {
            'Pagi (08:00-17:00)' => 'P',
            'Middle (11:00-20:00)' => 'M',
            'Siang (13:00-22:00)' => 'S',
            'Libur' => 'L',
            default => '-'
        };
    }

    
}
