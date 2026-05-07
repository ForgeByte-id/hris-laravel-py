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
            $table->unsignedInteger('yearly_leave_quota')->default(12)->after('tanggal_masuk');
            $table->unsignedInteger('remaining_leave_quota')->default(12)->after('yearly_leave_quota');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('karyawan', function (Blueprint $table) {
            $table->dropColumn(['yearly_leave_quota', 'remaining_leave_quota']);
        });
    }
};
