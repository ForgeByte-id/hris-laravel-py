<?php

namespace App\Http\Controllers;

use App\Models\Absensi;
use App\Models\Cuti;
use App\Models\Divisi;
use App\Models\Jabatan;
use App\Models\Karyawan;
use Illuminate\Http\Request;

class LaporanController extends Controller
{
    public function index(Request $request)
    {
        $bulan = $request->get('bulan', now()->format('Y-m'));

        [$tahun, $bulanNum] = explode('-', $bulan);

        $karyawanList = Karyawan::with(['jabatan', 'divisi'])->get();

        // Rekap absensi per karyawan
        $absensiRekap = Absensi::whereYear('tanggal', $tahun)
            ->whereMonth('tanggal', $bulanNum)
            ->selectRaw('id_karyawan, COUNT(*) as total_hadir, SUM(CASE WHEN status = "hadir" THEN 1 ELSE 0 END) as hadir, SUM(CASE WHEN status = "terlambat" THEN 1 ELSE 0 END) as terlambat, SUM(CASE WHEN status = "absen" THEN 1 ELSE 0 END) as absen')
            ->groupBy('id_karyawan')
            ->get()
            ->keyBy('id_karyawan');

        // Rekap cuti per karyawan
        $cutiRekap = Cuti::whereYear('tanggal_mulai', $tahun)
            ->whereMonth('tanggal_mulai', $bulanNum)
            ->where('status_persetujuan', 'approved')
            ->selectRaw('id_karyawan, COUNT(*) as total_cuti')
            ->groupBy('id_karyawan')
            ->get()
            ->keyBy('id_karyawan');

        $divisiList = Divisi::orderBy('nama_divisi')->get();
        $jabatanList = Jabatan::orderBy('nama_jabatan')->get();

        return view('laporan.index', compact(
            'bulan',
            'karyawanList',
            'absensiRekap',
            'cutiRekap',
            'divisiList',
            'jabatanList'
        ));
    }
}
