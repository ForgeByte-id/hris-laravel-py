<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('karyawan', function (Blueprint $table) {
            if (!Schema::hasColumn('karyawan', 'tanggal_mulai_kerja')) {
                $table->date('tanggal_mulai_kerja')->nullable()->after('tanggal_masuk');
            }

            if (!Schema::hasColumn('karyawan', 'status_aktif')) {
                $table->string('status_aktif')->default('Aktif')->after('tanggal_mulai_kerja');
            }

            if (!Schema::hasColumn('karyawan', 'status_karyawan')) {
                $table->string('status_karyawan')->nullable()->after('status_aktif');
            }
        });
    }

    public function down(): void
    {
        Schema::table('karyawan', function (Blueprint $table) {
            if (Schema::hasColumn('karyawan', 'status_karyawan')) {
                $table->dropColumn('status_karyawan');
            }

            if (Schema::hasColumn('karyawan', 'status_aktif')) {
                $table->dropColumn('status_aktif');
            }

            if (Schema::hasColumn('karyawan', 'tanggal_mulai_kerja')) {
                $table->dropColumn('tanggal_mulai_kerja');
            }
        });
    }
};
