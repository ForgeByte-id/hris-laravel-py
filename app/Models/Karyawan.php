<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

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
        'id_divisi',
        'tanggal_masuk',
        'status_aktif',
        'status_karyawan',
        'face_embedding',
        'face_image_path',
    ];

    protected $casts = [
        'tanggal_masuk' => 'date',
    ];

    public function jabatan()
    {
        return $this->belongsTo(Jabatan::class, 'id_jabatan', 'id');
    }

    public function divisi()
    {
        return $this->belongsTo(Divisi::class, 'id_divisi', 'id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'id_user', 'id_user');
    }

    public function absensi()
    {
        return $this->hasMany(Absensi::class, 'id_karyawan', 'id_karyawan');
    }

    public function cuti()
    {
        return $this->hasMany(Cuti::class, 'id_karyawan', 'id_karyawan');
    }

    public function cutiToApprove()
    {
        return $this->hasMany(Cuti::class, 'id_atasan', 'id_karyawan');
    }

    public function leaveQuotas()
    {
        return $this->hasMany(KuotaCutiKaryawan::class, 'id_karyawan', 'id_karyawan');
    }

    public function hasFaceRegistered()
    {
        return !empty($this->face_embedding);
    }

    public function getFaceEncodingAttribute()
    {
        return $this->face_embedding ? json_decode($this->face_embedding, true) : null;
    }

    public function setFaceEncodingAttribute($value)
    {
        $this->attributes['face_embedding'] = is_array($value)
            ? json_encode($value)
            : $value;
    }
}
