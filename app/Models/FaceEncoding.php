<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FaceEncoding extends Model
{
    use HasFactory;

    protected $fillable = [
        'id_karyawan',
        'encoding',
        'photo_path',
    ];

    /**
     * Relationship ke Karyawan
     */
    public function karyawan()
    {
        return $this->belongsTo(Karyawan::class, 'id_karyawan', 'id_karyawan');
    }

    /**
     * Accessor untuk decode encoding dari JSON
     */
    public function getEncodingArrayAttribute()
    {
        return json_decode($this->encoding, true);
    }

    /**
     * Mutator untuk encode array ke JSON
     */
    public function setEncodingAttribute($value)
    {
        $this->attributes['encoding'] = is_array($value) ? json_encode($value) : $value;
    }
}
