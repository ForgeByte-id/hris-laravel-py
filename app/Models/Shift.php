<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shift extends Model
{
    use HasFactory;

    protected $table = 'shifts';
    protected $primaryKey = 'id_shift';

    protected $fillable = [
        'kode_shift',
        'nama_shift',
        'jam_masuk',
        'jam_pulang',
    ];

    public function jadwalKerja()
    {
        return $this->hasMany(JadwalKerja::class, 'id_shift', 'id_shift');
    }

    public function isLiburLike(): bool
    {
        return in_array($this->kode_shift, ['L', 'C'], true);
    }

    public function getLabelAttribute(): string
    {
        if ($this->jam_masuk && $this->jam_pulang) {
            return "{$this->nama_shift} (" . substr($this->jam_masuk, 0, 5) . '-' . substr($this->jam_pulang, 0, 5) . ')';
        }

        return $this->nama_shift;
    }

    public function getColorHexAttribute(): string
    {
        return match ($this->kode_shift) {
            'Pa' => '#4CAF50',
            'Si' => '#2196F3',
            'L' => '#f44336',
            'C' => '#6f42c1',
            default => '#9E9E9E',
        };
    }

    public function getShiftShortAttribute(): string
    {
        return match ($this->kode_shift) {
            'Pa' => 'P',
            'Si' => 'S',
            default => $this->kode_shift, // 'L' and 'C' are already single letters
        };
    }
}
