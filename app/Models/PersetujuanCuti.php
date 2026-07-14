<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PersetujuanCuti extends Model
{
    protected $table = 'persetujuan_cuti';
    protected $primaryKey = 'id_persetujuan';

    protected $fillable = [
        'id_cuti',
        'id_penyetuju',
        'status_persetujuan',
        'tanggal_persetujuan',
        'catatan',
    ];

    protected $casts = [
        'tanggal_persetujuan' => 'date',
    ];

    public $timestamps = false;

    public function cuti()
    {
        return $this->belongsTo(Cuti::class, 'id_cuti', 'id_cuti');
    }

    public function penyetuju()
    {
        return $this->belongsTo(Karyawan::class, 'id_penyetuju', 'id_karyawan');
    }
}
