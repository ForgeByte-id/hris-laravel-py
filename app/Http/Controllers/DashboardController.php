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
        ));
    }
}
