<?php

namespace App\Http\Controllers;

use App\Models\Absensi;
use App\Models\Cuti;
use App\Models\Divisi;
use App\Models\Jabatan;
use App\Models\Karyawan;
use Illuminate\Http\Request;
use App\Services\AuthorizationService;

class LaporanController extends Controller
{
        public function __construct(private AuthorizationService $authService) {}

    public function index(Request $request)
    {
        $scope = $this->authService->getLaporanViewScope($request->user());
        abort_unless($scope['allowed'], 403);

        $data = $this->resolveReportData($request, $scope);

        $karyawanOptions = Karyawan::orderBy('nama')
            ->when($scope['id_divisi'], fn ($q) => $q->where('id_divisi', $scope['id_divisi']))
            ->get();

        $divisiList = Divisi::orderBy('nama_divisi')
            ->when($scope['id_divisi'], fn ($q) => $q->where('id', $scope['id_divisi']))
            ->get();

        $jabatanList = Jabatan::orderBy('nama_jabatan')->get();

        return view('laporan.index', array_merge($data, compact(
            'karyawanOptions',
            'divisiList',
            'jabatanList'
        )));
    }

    public function export(Request $request)
    {
        $scope = $this->authService->getLaporanViewScope($request->user());
        abort_unless($scope['allowed'], 403);

        $data = $this->resolveReportData($request, $scope);
        $karyawanList = $data['karyawanList'];
        $absensiRekap = $data['absensiRekap'];
        $cutiRekap = $data['cutiRekap'];

        $filename = 'laporan-kehadiran-' . $data['bulan'] . '.csv';

        return response()->streamDownload(function () use ($karyawanList, $absensiRekap, $cutiRekap) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, ['No', 'Nama Karyawan', 'Jabatan', 'Divisi', 'Hadir', 'Terlambat', 'Absen', 'Cuti']);

            foreach ($karyawanList as $index => $k) {
                $absensi = $absensiRekap->get($k->id_karyawan);
                $cuti = $cutiRekap->get($k->id_karyawan);

                fputcsv($handle, [
                    $index + 1,
                    $k->nama,
                    $k->jabatan?->nama_jabatan ?? '-',
                    $k->divisi?->nama_divisi ?? '-',
                    $absensi?->hadir ?? 0,
                    $absensi?->terlambat ?? 0,
                    $absensi?->absen ?? 0,
                    $cuti?->total_cuti ?? 0,
                ]);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    private function resolveReportData(Request $request, array $scope): array
    {
        $bulan = $request->get('bulan', now()->format('Y-m'));
        $idKaryawan = $request->get('id_karyawan');
        $idDivisi = $request->get('id_divisi');
        $idJabatan = $request->get('id_jabatan');

        [$tahun, $bulanNum] = explode('-', $bulan);

        $karyawanList = Karyawan::with(['jabatan', 'divisi'])->orderBy('nama')
            ->when($scope['id_divisi'], fn ($q) => $q->where('id_divisi', $scope['id_divisi']))
            ->when($idKaryawan, fn ($q) => $q->where('id_karyawan', $idKaryawan))
            ->when($idDivisi, fn ($q) => $q->where('id_divisi', $idDivisi))
            ->when($idJabatan, fn ($q) => $q->where('id_jabatan', $idJabatan))
            ->get();

        $absensiRekap = Absensi::whereYear('tanggal', $tahun)
            ->whereMonth('tanggal', $bulanNum)
            ->selectRaw('id_karyawan, COUNT(*) as total_hadir, SUM(CASE WHEN status = "hadir" THEN 1 ELSE 0 END) as hadir, SUM(CASE WHEN status = "terlambat" THEN 1 ELSE 0 END) as terlambat, SUM(CASE WHEN status = "absen" THEN 1 ELSE 0 END) as absen')
            ->groupBy('id_karyawan')
            ->get()
            ->keyBy('id_karyawan');

        $cutiRekap = Cuti::whereYear('tanggal_mulai', $tahun)
            ->whereMonth('tanggal_mulai', $bulanNum)
            ->where('status_persetujuan', 'approved')
            ->selectRaw('id_karyawan, COUNT(*) as total_cuti')
            ->groupBy('id_karyawan')
            ->get()
            ->keyBy('id_karyawan');

        return compact('bulan', 'karyawanList', 'absensiRekap', 'cutiRekap', 'idKaryawan', 'idDivisi', 'idJabatan');
    }


}
