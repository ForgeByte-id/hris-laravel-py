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
        'kode_shift',
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

    public function shift()
    {
        return $this->belongsTo(Shift::class, 'kode_shift', 'kode_shift');
    }

    // Check apakah hari libur
    public function isLibur()
    {
        return $this->kode_shift === 'L' || $this->jam_kerja === 'Libur';
    }

    // Get badge color untuk jam kerja
    public function getShiftColorAttribute()
    {
        return match($this->kode_shift ?: $this->jam_kerja) {
            'P', 'Pagi (08:00-17:00)' => '#4CAF50',
            'M', 'Middle (11:00-20:00)' => '#FF9800',
            'S', 'Siang (13:00-22:00)' => '#2196F3',
            'L', 'Libur' => '#f44336',
            'C' => '#6f42c1',
            default => '#9E9E9E'
        };
    }

    // Get shift short name
    public function getShiftShortAttribute()
    {
        return match($this->kode_shift ?: $this->jam_kerja) {
            'P', 'Pagi (08:00-17:00)' => 'P',
            'M', 'Middle (11:00-20:00)' => 'M',
            'S', 'Siang (13:00-22:00)' => 'S',
            'L', 'Libur' => 'L',
            'C' => 'C',
            'H' => 'H',
            default => '-'
        };
    }

    
}
