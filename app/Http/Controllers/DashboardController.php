<?php

namespace App\Http\Controllers;

use App\Models\Karyawan;
use App\Models\Absensi;
use App\Models\Cuti;
use App\Models\JadwalKerja;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $user     = Auth::user();
        $isAdmin  = $user->hasRole('admin');
        $isHr     = $user->hasRole('hr');
        $karyawan = $isAdmin ? null : Karyawan::with(['jabatan', 'devisi'])
                                              ->where('id_user', $user->id_user)
                                              ->first();

        // Employee-only data — loaded server-side so no admin-only API call is needed
        $todayJadwal      = null;
        $attendanceHistory = collect();
        $todayAbsensi     = null;
        $recentCuti       = collect();
        $pendingCutiCount = 0;
        $hadirThisMonth   = 0;
        $dailyAttendanceSummary = null;
        $todayAttendanceRows = collect();

        if ($karyawan) {
            $todayJadwal = JadwalKerja::where('id_karyawan', $karyawan->id_karyawan)
                ->whereDate('tanggal', today())
                ->first();

            $todayAbsensi = Absensi::where('id_karyawan', $karyawan->id_karyawan)
                ->whereDate('tanggal', today())
                ->first();

            $attendanceHistory = Absensi::where('id_karyawan', $karyawan->id_karyawan)
                ->whereBetween('tanggal', [now()->subDays(6)->toDateString(), today()->toDateString()])
                ->orderBy('tanggal', 'desc')
                ->get();

            $recentCuti = Cuti::where('id_karyawan', $karyawan->id_karyawan)
                ->orderBy('created_at', 'desc')
                ->limit(3)
                ->get();

            $pendingCutiCount = Cuti::where('id_karyawan', $karyawan->id_karyawan)
                ->where('status_persetujuan', 'pending')
                ->count();

            $hadirThisMonth = Absensi::where('id_karyawan', $karyawan->id_karyawan)
                ->whereYear('tanggal', now()->year)
                ->whereMonth('tanggal', now()->month)
                ->count();
        }

        if ($isAdmin || $isHr) {
            $today = Carbon::today();
            $employees = Karyawan::with(['devisi', 'jabatan'])->orderBy('nama')->get();
            $todayAbsensi = Absensi::whereDate('tanggal', $today)->get()->keyBy('id_karyawan');
            $todayJadwal = JadwalKerja::with('shift')->whereDate('tanggal', $today)->get()->keyBy('id_karyawan');
            $cutiApprovedToday = Cuti::where('status_persetujuan', 'approved')
                ->whereDate('tanggal_mulai', '<=', $today)
                ->whereDate('tanggal_selesai', '>=', $today)
                ->count();

            $presentRows = $todayAbsensi->filter(fn ($absensi) => !empty($absensi->jam_masuk));

            $dailyAttendanceSummary = [
                'total_karyawan' => $employees->count(),
                'sudah_absen_masuk' => $presentRows->count(),
                'belum_absen' => max(0, $employees->count() - $presentRows->count()),
                'terlambat' => $todayAbsensi->filter(fn ($absensi) => $absensi->status === 'terlambat' || ($absensi->menit_terlambat ?? 0) > 0)->count(),
                'tepat_waktu' => $todayAbsensi->filter(fn ($absensi) => in_array($absensi->status, ['hadir', 'tepat_waktu'], true))->count(),
                'remote' => $todayAbsensi->where('status', 'remote')->count(),
                'tidak_hadir' => $todayAbsensi->where('status', 'tidak_hadir')->count(),
                'cuti_approved' => $cutiApprovedToday,
            ];

            $todayAttendanceRows = $employees->map(function ($employee) use ($todayAbsensi, $todayJadwal) {
                return [
                    'karyawan' => $employee,
                    'absensi' => $todayAbsensi->get($employee->id_karyawan),
                    'jadwal' => $todayJadwal->get($employee->id_karyawan),
                ];
            });
        }

        return view('dashboard.dashboard', compact(
            'user',
            'karyawan',
            'isAdmin',
            'isHr',
            'todayJadwal',
            'todayAbsensi',
            'attendanceHistory',
            'recentCuti',
            'pendingCutiCount',
            'hadirThisMonth',
            'dailyAttendanceSummary',
            'todayAttendanceRows',
        ));
    }
}
