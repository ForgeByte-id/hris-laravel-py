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
        'id_shift',
    ];

    protected $casts = [
        'tanggal' => 'date',
    ];

    public function karyawan()
    {
        return $this->belongsTo(Karyawan::class, 'id_karyawan', 'id_karyawan');
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class, 'id_shift', 'kode_shift');
    }

    public function isLibur()
    {
        return $this->id_shift === 'L';
    }

    public function getShiftColorAttribute()
    {
        return match($this->id_shift) {
            'P' => '#4CAF50',
            'S' => '#2196F3',
            'L' => '#f44336',
            'C' => '#6f42c1',
            default => '#9E9E9E'
        };
    }

    public function getShiftShortAttribute()
    {
        return match($this->id_shift) {
            'P' => 'P',
            'S' => 'S',
            'L' => 'L',
            'C' => 'C',
            default => '-'
        };
    }
}
