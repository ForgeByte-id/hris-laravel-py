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
            $table->string('kode_shift', 2)->nullable()->after('jam_kerja');
            $table->foreign('kode_shift')->references('kode_shift')->on('shifts')->nullOnDelete();
        });

        DB::table('jadwal_kerja')->orderBy('id_jadwal')->chunkById(100, function ($rows) {
            foreach ($rows as $row) {
                $kodeShift = match ($row->jam_kerja) {
                    'Pagi (08:00-17:00)' => 'P',
                    'Middle (11:00-20:00)' => 'M',
                    'Siang (13:00-22:00)' => 'S',
                    'Libur' => 'L',
                    default => null,
                };

                DB::table('jadwal_kerja')
                    ->where('id_jadwal', $row->id_jadwal)
                    ->update(['kode_shift' => $kodeShift]);
            }
        }, 'id_jadwal', 'id_jadwal');
    }

    public function down(): void
    {
        Schema::table('jadwal_kerja', function (Blueprint $table) {
            $table->dropForeign(['kode_shift']);
            $table->dropColumn('kode_shift');
        });
    }
};
