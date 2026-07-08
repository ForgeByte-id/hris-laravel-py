<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('persetujuan_cuti')) {
            return;
        }

        Schema::create('persetujuan_cuti', function (Blueprint $table) {
            $table->id('id_persetujuan');
            $table->unsignedBigInteger('id_cuti');
            $table->unsignedBigInteger('id_penyetuju');
            $table->string('status_persetujuan');
            $table->date('tanggal_persetujuan');
            $table->text('catatan');

            $table->foreign('id_cuti')
                ->references('id_cuti')
                ->on('cuti')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            $table->foreign('id_penyetuju')
                ->references('id_karyawan')
                ->on('karyawan')
                ->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('persetujuan_cuti');
    }
};
