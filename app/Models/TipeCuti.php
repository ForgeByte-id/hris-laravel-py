<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipeCuti extends Model
{
    protected $table = 'tipe_cuti';

    protected $fillable = [
        'nama_cuti',
        'kuota_cuti',
        'berlaku_untuk_status',
        'is_active',
    ];

    protected $casts = [
        'kuota_cuti' => 'integer',
        'is_active' => 'boolean',
    ];

    public function karyawanQuotas()
    {
        return $this->hasMany(KuotaCutiKaryawan::class, 'leave_type_id', 'id');
    }
}
