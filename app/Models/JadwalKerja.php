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
        return $this->belongsTo(Shift::class, 'id_shift', 'id_shift');
    }

    public function isLibur()
    {
        return $this->id_shift === '3';
    }

    public function getShiftColorAttribute()
    {
        return match($this->id_shift) {
            '1' => '#4CAF50',
            '2' => '#2196F3',
            '3' => '#f44336',
            '4' => '#6f42c1',
            default => '#9E9E9E'
        };
    }

    public function getShiftShortAttribute()
    {
        return match($this->id_shift) {
            '1' => 'P',
            '2' => 'S',
            '3' => 'L',
            '4' => 'C',
            default => '-'
        };
    }
}
