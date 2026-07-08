<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('absensi', function (Blueprint $table) {
            if (!Schema::hasColumn('absensi', 'recorded_by')) {
                $table->unsignedBigInteger('recorded_by')->nullable()->after('status');
                $table->foreign('recorded_by')->references('id_user')->on('users')->onDelete('set null');
            }

            if (!Schema::hasColumn('absensi', 'face_verified')) {
                $table->boolean('face_verified')->default(false)->after('recorded_by');
            }

            if (!Schema::hasColumn('absensi', 'face_confidence')) {
                $table->decimal('face_confidence', 5, 2)->nullable()->after('face_verified');
            }

            if (!Schema::hasColumn('absensi', 'photo_hash')) {
                $table->string('photo_hash', 64)->nullable()->after('face_confidence');
            }
        });
    }

    public function down(): void
    {
        Schema::table('absensi', function (Blueprint $table) {
            if (Schema::hasColumn('absensi', 'recorded_by')) {
                $table->dropForeign(['recorded_by']);
            }

            $cols = ['recorded_by', 'face_verified', 'face_confidence', 'photo_hash'];
            foreach ($cols as $col) {
                if (Schema::hasColumn('absensi', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
