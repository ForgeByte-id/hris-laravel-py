<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('karyawan', function (Blueprint $table) {
            $table->unsignedBigInteger('id_jabatan')->nullable()->after('nama');
            $table->foreign('id_jabatan')
                ->references('id')
                ->on('jabatans')
                ->onDelete('set null');
            $table->dropColumn('jabatan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('karyawan', function (Blueprint $table) {
            $table->dropForeign(['id_jabatan']);
            $table->dropColumn('id_jabatan');
            $table->string('jabatan')->after('nama');
        });
    }
};
