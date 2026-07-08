<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('kuota_cuti_karyawan')) {
            return;
        }

        Schema::create('kuota_cuti_karyawan', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_karyawan');
            $table->unsignedBigInteger('leave_type_id');
            $table->unsignedSmallInteger('year');
            $table->unsignedInteger('quota')->default(0);
            $table->unsignedInteger('remaining_quota')->default(0);
            $table->timestamps();

            $table->unique(['id_karyawan', 'leave_type_id', 'year'], 'karyawan_leave_quota_unique');
            $table->foreign('id_karyawan')
                ->references('id_karyawan')
                ->on('karyawan')
                ->cascadeOnDelete();
            $table->foreign('leave_type_id')
                ->references('id')
                ->on('tipe_cuti')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kuota_cuti_karyawan');
    }
};
