<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jadwal_kerja', function (Blueprint $table) {
            if (!Schema::hasColumn('jadwal_kerja', 'id_shift')) {
                $table->string('id_shift', 2)->nullable()->after('tanggal');
            }
        });
    }

    public function down(): void
    {
        Schema::table('jadwal_kerja', function (Blueprint $table) {
            if (Schema::hasColumn('jadwal_kerja', 'id_shift')) {
                $table->dropColumn('id_shift');
            }
        });
    }
};
