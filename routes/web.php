<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\KaryawanController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\CutiController;
use App\Http\Controllers\JadwalKerjaController;



Route::middleware('auth')->prefix('api')->group(function () {
    Route::post('/karyawan/register-face', [KaryawanController::class, 'storeFaceEncoding']);
    Route::delete('/karyawan/{id_karyawan}/face', [KaryawanController::class, 'deleteFaceEncoding']);

    Route::post('/attendance/check-in', [AttendanceController::class, 'checkIn']);
    Route::get('/attendance/todays-summary', [AttendanceController::class, 'todaysSummary']);
    Route::get('/attendance/current-status/{idKaryawan}', [AttendanceController::class, 'getCurrentStatus']);
    Route::get('/attendance/history/{idKaryawan}', [AttendanceController::class, 'getHistory']);
});

Route::middleware(['guest'])->group(function () {
    Route::get('/', function () {
        return view('auth.login');
    })->name('login');

    Route::post('/proseslogin', [AuthController::class, 'proseslogin'])->name('proseslogin');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::get('/proseslogout', [AuthController::class, 'proseslogout']);

    // Karyawan Routes
    Route::get('/karyawan', [KaryawanController::class, 'index'])->name('karyawan.index');
    Route::get('/karyawan/create', [KaryawanController::class, 'create'])->name('karyawan.create');
    Route::post('/karyawan', [KaryawanController::class, 'store'])->name('karyawan.store');
    Route::get('/karyawan/{id_karyawan}', [KaryawanController::class, 'show'])->name('karyawan.show');
    Route::get('/karyawan/{id_karyawan}/edit', [KaryawanController::class, 'edit'])->name('karyawan.edit');
    Route::put('/karyawan/{id_karyawan}', [KaryawanController::class, 'update'])->name('karyawan.update');
    Route::delete('/karyawan/{id_karyawan}', [KaryawanController::class, 'destroy'])->name('karyawan.destroy');
    Route::get('/karyawan/{id_karyawan}/register-face', [KaryawanController::class, 'registerFace'])->name('karyawan.register-face');

    // Attendance Routes
    Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
    Route::get('/attendance/history', [AttendanceController::class, 'history'])->name('attendance.history');

    // Cuti Routes
    Route::get('/cuti', [CutiController::class, 'index'])->name('cuti.index');
    Route::get('/cuti/create', [CutiController::class, 'create'])->name('cuti.create');
    Route::post('/cuti', [CutiController::class, 'store'])->name('cuti.store');
    Route::get('/cuti/{id_cuti}', [CutiController::class, 'show'])->name('cuti.show');
    Route::delete('/cuti/{id_cuti}', [CutiController::class, 'cancel'])->name('cuti.cancel');
    Route::get('/cuti/approval/list', [CutiController::class, 'approval'])->name('cuti.approval');
    Route::patch('/cuti/{id_cuti}/status', [CutiController::class, 'updateStatus'])->name('cuti.update-status');
    Route::get('/cuti/history/all', [CutiController::class, 'history'])->name('cuti.history');

    // Jadwal Kerja Routes
    Route::get('/jadwal', [JadwalKerjaController::class, 'index'])->name('jadwal.index');
    Route::get('/jadwal/create', [JadwalKerjaController::class, 'create'])->name('jadwal.create');
    Route::post('/jadwal', [JadwalKerjaController::class, 'store'])->name('jadwal.store');
    Route::get('/jadwal/bulk-create', [JadwalKerjaController::class, 'bulkCreate'])->name('jadwal.bulk-create');
    Route::post('/jadwal/bulk-store', [JadwalKerjaController::class, 'bulkStore'])->name('jadwal.bulk-store');
    Route::get('/jadwal/{id_jadwal}/edit', [JadwalKerjaController::class, 'edit'])->name('jadwal.edit');
    Route::put('/jadwal/{id_jadwal}', [JadwalKerjaController::class, 'update'])->name('jadwal.update');
    Route::delete('/jadwal/{id_jadwal}', [JadwalKerjaController::class, 'destroy'])->name('jadwal.destroy');
    Route::get('/jadwal/karyawan/{id_karyawan}', [JadwalKerjaController::class, 'show'])->name('jadwal.show');
    Route::post('/jadwal/libur-massal', [JadwalKerjaController::class, 'setLiburMassal'])->name('jadwal.libur-massal');
});
