<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Removed: yearly_leave_quota dan remaining_leave_quota tidak ada di SQL schema.
        // Kuota cuti sekarang disimpan di tabel kuota_cuti_karyawan.
    }

    public function down(): void
    {
    }
};
