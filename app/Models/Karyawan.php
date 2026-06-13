<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
//use Laravel\Sanctum\HasApiTokens;

class Karyawan extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = "karyawan";
    protected $primaryKey = "id_karyawan";
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'id_user',
        'nama',
        'id_jabatan',
        'id_devisi',
        'kode_shift',
        'tanggal_masuk',
        'tanggal_mulai_kerja',
        'status_aktif',
        'status_karyawan',
        'yearly_leave_quota',
        'remaining_leave_quota',
        'face_embedding',
        'face_image_path',
    ];

    protected $casts = [
        'tanggal_masuk' => 'date',
        'tanggal_mulai_kerja' => 'date',
        'yearly_leave_quota' => 'integer',
        'remaining_leave_quota' => 'integer',
    ];

    // Relationship ke Jabatan
    public function jabatan()
    {
        return $this->belongsTo(Jabatan::class, 'id_jabatan', 'id');
    }

    // Relationship ke Devisi
    public function devisi()
    {
        return $this->belongsTo(Devisi::class, 'id_devisi', 'id');
    }

    // Relationship ke User
    public function user()
    {
        return $this->belongsTo(User::class, 'id_user', 'id_user');
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class, 'kode_shift', 'kode_shift');
    }

    // Relationship ke Absensi
    public function absensi()
    {
        return $this->hasMany(Absensi::class, 'id_karyawan', 'id_karyawan');
    }

    // Relationship ke Cuti (yang diajukan)
    public function cuti()
    {
        return $this->hasMany(Cuti::class, 'id_karyawan', 'id_karyawan');
    }

    // Relationship ke Cuti yang perlu diapprove (sebagai atasan)
    public function cutiToApprove()
    {
        return $this->hasMany(Cuti::class, 'id_atasan', 'id_karyawan');
    }

    public function leaveQuotas()
    {
        return $this->hasMany(KaryawanLeaveQuota::class, 'id_karyawan', 'id_karyawan');
    }

    // Check apakah sudah registrasi wajah
    public function hasFaceRegistered()
    {
        return !empty($this->face_embedding);
    }

    // Get face encoding sebagai array
    public function getFaceEncodingAttribute()
    {
        return $this->face_embedding ? json_decode($this->face_embedding, true) : null;
    }

    // Set face encoding dari array
    public function setFaceEncodingAttribute($value)
    {
        $this->attributes['face_embedding'] = is_array($value)
            ? json_encode($value)
            : $value;
    }
}
