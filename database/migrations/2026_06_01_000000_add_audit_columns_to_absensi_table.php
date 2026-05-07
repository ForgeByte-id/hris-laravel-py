<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('absensi', function (Blueprint $table) {
            // Admin who recorded the attendance
            $table->unsignedBigInteger('recorded_by')->nullable()->after('status');
            $table->foreign('recorded_by')->references('id_user')->on('users')->onDelete('set null');

            // Face verification audit
            $table->boolean('face_verified')->default(false)->after('recorded_by');
            $table->decimal('face_confidence', 5, 2)->nullable()->after('face_verified');

            // Photo integrity (SHA-256 of captured image)
            $table->string('photo_hash', 64)->nullable()->after('face_confidence');

            // GPS at time of recording
            $table->decimal('gps_lat', 10, 7)->nullable()->after('photo_hash');
            $table->decimal('gps_lng', 10, 7)->nullable()->after('gps_lat');

            // Device metadata (User-Agent)
            $table->text('device_info')->nullable()->after('gps_lng');

            // IP address
            $table->string('ip_address', 45)->nullable()->after('device_info');

            // Immutability flag — once true the record cannot be changed
            $table->boolean('is_locked')->default(false)->after('ip_address');
        });
    }

    public function down(): void
    {
        Schema::table('absensi', function (Blueprint $table) {
            $table->dropForeign(['recorded_by']);
            $table->dropColumn([
                'recorded_by', 'face_verified', 'face_confidence',
                'photo_hash', 'gps_lat', 'gps_lng',
                'device_info', 'ip_address', 'is_locked',
            ]);
        });
    }
};
