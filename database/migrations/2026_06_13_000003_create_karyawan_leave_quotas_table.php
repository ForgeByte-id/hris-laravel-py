<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('karyawan_leave_quotas')) {
            return;
        }

        Schema::create('karyawan_leave_quotas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_karyawan');
            $table->foreignId('leave_type_id')->constrained('leave_types')->cascadeOnDelete();
            $table->unsignedSmallInteger('year');
            $table->unsignedInteger('quota')->default(0);
            $table->unsignedInteger('remaining_quota')->default(0);
            $table->timestamps();

            $table->unique(['id_karyawan', 'leave_type_id', 'year'], 'karyawan_leave_quota_unique');
            $table->foreign('id_karyawan')
                ->references('id_karyawan')
                ->on('karyawan')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('karyawan_leave_quotas');
    }
};
