<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Converts karyawan.divisi from string field to id_devisi foreign key
     */
    public function up(): void
    {
        Schema::table('karyawan', function (Blueprint $table) {
            if (!Schema::hasColumn('karyawan', 'id_divisi')) {
                $table->unsignedBigInteger('id_divisi')->nullable()->after('id_jabatan');
                $table->foreign('id_divisi')->references('id')->on('divisis')->onDelete('set null');
            }

            if (Schema::hasColumn('karyawan', 'divisi')) {
                $table->dropColumn('divisi');
            }
        });
    }

    public function down(): void
    {
        Schema::table('karyawan', function (Blueprint $table) {
            if (!Schema::hasColumn('karyawan', 'divisi')) {
                $table->string('divisi')->nullable()->after('id_jabatan');
            }

            if (Schema::hasColumn('karyawan', 'id_divisi')) {
                $table->dropForeign(['id_divisi']);
                $table->dropColumn('id_divisi');
            }
        });
    }
};
