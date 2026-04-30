<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\KaryawanController;
use App\Http\Controllers\AttendanceController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Face Recognition Routes
Route::post('/karyawan/register-face', [KaryawanController::class, 'storeFaceEncoding']);
Route::delete('/karyawan/{id_karyawan}/face', [KaryawanController::class, 'deleteFaceEncoding']);

// Attendance Routes
Route::post('/attendance/check-in', [AttendanceController::class, 'checkIn']);
