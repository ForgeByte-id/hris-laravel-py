<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Shifts extends Model
{
    protected $fillable = ['nama_shift', 'kode_shift', 'jam_masuk', 'jam_pulang', 'icon', 'color'];
}
