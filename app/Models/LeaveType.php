<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeaveType extends Model
{
    protected $fillable = [
        'nama_cuti',
        'default_quota',
        'applies_to_status',
        'is_active',
    ];

    protected $casts = [
        'default_quota' => 'integer',
        'is_active' => 'boolean',
    ];

    public function karyawanQuotas()
    {
        return $this->hasMany(KaryawanLeaveQuota::class);
    }
}
