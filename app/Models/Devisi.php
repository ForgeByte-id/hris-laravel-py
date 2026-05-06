<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Devisi extends Model
{
    use HasFactory;

    protected $table = 'devisis';
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'nama_devisi',
    ];

    // Relationship ke Karyawan
    public function karyawan()
    {
        return $this->hasMany(Karyawan::class, 'id_devisi', 'id');
    }
}
