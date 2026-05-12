<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('karyawan', function (Blueprint $table) {
            $table->string('kode_shift', 2)->default('P')->after('id_devisi');
            $table->foreign('kode_shift')->references('kode_shift')->on('shifts')->restrictOnDelete();
        });

        DB::table('karyawan')->whereNull('kode_shift')->update(['kode_shift' => 'P']);
    }

    public function down(): void
    {
        Schema::table('karyawan', function (Blueprint $table) {
            $table->dropForeign(['kode_shift']);
            $table->dropColumn('kode_shift');
        });
    }
};
