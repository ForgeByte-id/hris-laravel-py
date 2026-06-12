<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\KaryawanController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\CutiController;
use App\Http\Controllers\JadwalKerjaController;
use App\Http\Controllers\ShiftController;
use App\Http\Controllers\DivisiController;
use App\Http\Controllers\JabatanController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\MenuItemController;
use App\Http\Controllers\ProfileController;



Route::middleware('auth')->prefix('api')->group(function () {
    Route::post('/karyawan/register-face', [KaryawanController::class, 'storeFaceEncoding']);
    Route::delete('/karyawan/{id_karyawan}/face', [KaryawanController::class, 'deleteFaceEncoding']);

    // Attendance API routes — admin only
    Route::middleware('is_admin')->group(function () {
        Route::post('/attendance/check-in', [AttendanceController::class, 'checkIn']);
        Route::post('/attendance/verify-face', [AttendanceController::class, 'verifyFace']);
        Route::post('/attendance/admin-record', [AttendanceController::class, 'adminRecord']);
        Route::get('/attendance/todays-summary', [AttendanceController::class, 'todaysSummary']);
        // Static routes must come before wildcard {idKaryawan}
        Route::get('/attendance/recent-all', [AttendanceController::class, 'recentAll']);
        Route::get('/attendance/current-status/{idKaryawan}', [AttendanceController::class, 'getCurrentStatus']);
        Route::get('/attendance/history/{idKaryawan}', [AttendanceController::class, 'getHistory']);
    });
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
    Route::get('/karyawan/import-face', [KaryawanController::class, 'importFaceForm'])->name('karyawan.import-face');
    Route::post('/karyawan/import-face', [KaryawanController::class, 'importFace'])->name('karyawan.import-face.store');
    Route::post('/karyawan', [KaryawanController::class, 'store'])->name('karyawan.store');
    Route::get('/karyawan/{id_karyawan}', [KaryawanController::class, 'show'])->name('karyawan.show');
    Route::get('/karyawan/{id_karyawan}/edit', [KaryawanController::class, 'edit'])->name('karyawan.edit');
    Route::put('/karyawan/{id_karyawan}', [KaryawanController::class, 'update'])->name('karyawan.update');
    Route::delete('/karyawan/{id_karyawan}', [KaryawanController::class, 'destroy'])->name('karyawan.destroy');
    Route::get('/karyawan/{id_karyawan}/register-face', [KaryawanController::class, 'registerFace'])->name('karyawan.register-face');

    // Attendance Routes — admin only
    Route::middleware('is_admin')->group(function () {
        Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
        Route::get('/attendance/history', [AttendanceController::class, 'history'])->name('attendance.history');
    });

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

    // Shift Routes
    Route::resource('shift', ShiftController::class)->except('show');

    // Divisi Routes
    Route::get('/divisi', [DivisiController::class, 'index'])->name('divisi.index');
    Route::get('/divisi/create', [DivisiController::class, 'create'])->name('divisi.create');
    Route::post('/divisi', [DivisiController::class, 'store'])->name('divisi.store');
    Route::get('/divisi/{id}/edit', [DivisiController::class, 'edit'])->name('divisi.edit');
    Route::put('/divisi/{id}', [DivisiController::class, 'update'])->name('divisi.update');
    Route::delete('/divisi/{id}', [DivisiController::class, 'destroy'])->name('divisi.destroy');

    // Jabatan Routes
    Route::get('/jabatan', [JabatanController::class, 'index'])->name('jabatan.index');
    Route::get('/jabatan/create', [JabatanController::class, 'create'])->name('jabatan.create');
    Route::post('/jabatan', [JabatanController::class, 'store'])->name('jabatan.store');
    Route::get('/jabatan/{id}/edit', [JabatanController::class, 'edit'])->name('jabatan.edit');
    Route::put('/jabatan/{id}', [JabatanController::class, 'update'])->name('jabatan.update');
    Route::delete('/jabatan/{id}', [JabatanController::class, 'destroy'])->name('jabatan.destroy');

    // Laporan Routes
    Route::get('/laporan', [LaporanController::class, 'index'])->name('laporan.index');

    // Admin Routes
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/roles', [RoleController::class, 'index'])->name('roles.index');
        Route::get('/roles/{role}/edit', [RoleController::class, 'edit'])->name('roles.edit');
        Route::put('/roles/{role}', [RoleController::class, 'update'])->name('roles.update');

        Route::post('/menu-items', [MenuItemController::class, 'store'])->name('menu-items.store');
        Route::put('/menu-items/{menuItem}', [MenuItemController::class, 'update'])->name('menu-items.update');
        Route::delete('/menu-items/{menuItem}', [MenuItemController::class, 'destroy'])->name('menu-items.destroy');

        Route::get('/permissions', [PermissionController::class, 'index'])->name('permissions.index');
        Route::get('/permissions/create', [PermissionController::class, 'create'])->name('permissions.create');
        Route::post('/permissions', [PermissionController::class, 'store'])->name('permissions.store');
        Route::get('/permissions/{permission}/edit', [PermissionController::class, 'edit'])->name('permissions.edit');
        Route::put('/permissions/{permission}', [PermissionController::class, 'update'])->name('permissions.update');
        Route::delete('/permissions/{permission}', [PermissionController::class, 'destroy'])->name('permissions.destroy');
    });
    // Profile Routes
    Route::get('/profile', [ProfileController::class, 'index'])->name('profile.index');
});
