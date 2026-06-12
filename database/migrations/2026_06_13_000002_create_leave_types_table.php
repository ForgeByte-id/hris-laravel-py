<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('leave_types')) {
            return;
        }

        Schema::create('leave_types', function (Blueprint $table) {
            $table->id();
            $table->string('nama_cuti')->unique();
            $table->unsignedInteger('default_quota');
            $table->string('applies_to_status')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_types');
    }
};
