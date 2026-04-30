<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cuti extends Model
{
    use HasFactory;

    protected $table = 'cuti';
    protected $primaryKey = 'id_cuti';

    protected $fillable = [
        'id_karyawan',
        'jenis_cuti',
        'tanggal_mulai',
        'tanggal_selesai',
        'keterangan',
        'tanggal_persetujuan',
        'status_persetujuan',
        'id_atasan',
    ];

    protected $casts = [
        'tanggal_mulai' => 'date',
        'tanggal_selesai' => 'date',
        'tanggal_persetujuan' => 'date',
    ];

    // Relationship ke Karyawan (yang mengajukan)
    public function karyawan()
    {
        return $this->belongsTo(Karyawan::class, 'id_karyawan', 'id_karyawan');
    }

    // Relationship ke Atasan (yang menyetujui)
    public function atasan()
    {
        return $this->belongsTo(Karyawan::class, 'id_atasan', 'id_karyawan');
    }

    // Hitung jumlah hari cuti
    public function getJumlahHariAttribute()
    {
        return $this->tanggal_mulai->diffInDays($this->tanggal_selesai) + 1;
    }

    // Badge color untuk status
    public function getStatusColorAttribute()
    {
        return match($this->status_persetujuan) {
            'approved' => 'success',
            'rejected' => 'danger',
            'pending' => 'warning',
            default => 'secondary'
        };
    }

    // Status text
    public function getStatusTextAttribute()
    {
        return match($this->status_persetujuan) {
            'approved' => 'Disetujui',
            'rejected' => 'Ditolak',
            'pending' => 'Menunggu',
            default => 'Unknown'
        };
    }
}
