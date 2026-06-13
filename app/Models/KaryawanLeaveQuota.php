<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KaryawanLeaveQuota extends Model
{
    protected $fillable = [
        'id_karyawan',
        'leave_type_id',
        'year',
        'quota',
        'remaining_quota',
    ];

    protected $casts = [
        'year' => 'integer',
        'quota' => 'integer',
        'remaining_quota' => 'integer',
    ];

    public function karyawan()
    {
        return $this->belongsTo(Karyawan::class, 'id_karyawan', 'id_karyawan');
    }

    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class);
    }
}
