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

// // Face Recognition Routes
// Route::post('/karyawan/register-face', [KaryawanController::class, 'storeFaceEncoding']);
// Route::delete('/karyawan/{id_karyawan}/face', [KaryawanController::class, 'deleteFaceEncoding']);

// // Attendance Routes
// Route::post('/attendance/check-in', [AttendanceController::class, 'checkIn']);
// Route::get('/attendance/todays-summary', [AttendanceController::class, 'todaysSummary']);
// Route::get('/attendance/current-status/{idKaryawan}', [AttendanceController::class, 'getCurrentStatus']);
// Route::get('/attendance/history/{idKaryawan}', function ($idKaryawan) {
//     $user = \Illuminate\Support\Facades\Auth::user();
//     $authService = app(\App\Services\AuthorizationService::class);

//     // Authorization check: Can user view this employee's data?
//     if (!$authService->canViewAttendanceRecord($user, $idKaryawan)) {
//         return response()->json([
//             'error' => 'Unauthorized: You can only view your own attendance history',
//             'code' => 'UNAUTHORIZED_HISTORY_ACCESS'
//         ], 403);
//     }

//     $days = request('days', 30);
//     $service = app(\App\Services\AttendanceService::class);
//     return response()->json($service->getAttendanceHistory($idKaryawan, $days)->toArray());
// });
