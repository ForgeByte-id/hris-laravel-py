<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\KaryawanController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\CutiController;
use App\Http\Controllers\JadwalKerjaController;
use App\Http\Controllers\ProfileController;


Route::middleware(['guest'])->group(function () {
    Route::get('/', function () {
        return view('auth.login');
    })->name('login');

    Route::post('/proseslogin', [AuthController::class, 'proseslogin']);
});

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::get('/proseslogout', [AuthController::class, 'proseslogout']);

    // Karyawan Routes
    Route::get('/karyawan', [KaryawanController::class, 'index'])->name('karyawan.index');
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

    // Profile Routes
    Route::get('/profile', [ProfileController::class, 'index'])->name('profile.index');
});