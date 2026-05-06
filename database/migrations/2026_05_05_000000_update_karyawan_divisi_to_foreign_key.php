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
            // Add id_devisi column if it doesn't exist
            if (!Schema::hasColumn('karyawan', 'id_devisi')) {
                $table->unsignedBigInteger('id_devisi')->nullable()->after('id_jabatan');
                $table->foreign('id_devisi')->references('id')->on('devisis')->onDelete('set null');
            }

            // Drop old divisi string column if it exists
            if (Schema::hasColumn('karyawan', 'divisi')) {
                $table->dropColumn('divisi');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('karyawan', function (Blueprint $table) {
            // Reverse: add divisi string column back
            if (!Schema::hasColumn('karyawan', 'divisi')) {
                $table->string('divisi')->nullable()->after('id_jabatan');
            }

            // Drop id_devisi column
            if (Schema::hasColumn('karyawan', 'id_devisi')) {
                $table->dropForeign(['id_devisi']);
                $table->dropColumn('id_devisi');
            }
        });
    }
};
