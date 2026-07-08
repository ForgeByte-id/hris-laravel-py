<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tipe_cuti')) {
            return;
        }

        Schema::create('tipe_cuti', function (Blueprint $table) {
            $table->id();
            $table->string('nama_cuti')->unique();
            $table->unsignedInteger('kuota_cuti');
            $table->string('berlaku_untuk_status')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tipe_cuti');
    }
};
