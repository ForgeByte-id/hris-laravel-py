<?php

namespace App\Http\Controllers;

use App\Models\JadwalKerja;
use App\Models\Karyawan;
use App\Models\Divisi;
use App\Models\Absensi;
use App\Models\Cuti;
use App\Models\Shift;
use App\Models\Jabatan;
use App\Services\JadwalBulkService;
use App\Services\AuthorizationService;
use Illuminate\Http\Request;
use Carbon\Carbon;

class JadwalKerjaController extends Controller
{
    public function __construct(private AuthorizationService $authService) {}
    public function index(Request $request)
    {
        $bulan = $request->get('bulan', date('Y-m'));
        $nama = $request->get('nama');
        $idDivisi = $request->get('id_divisi');
        $idKaryawan = $request->get('id_karyawan');

        $viewScope = $this->authService->getJadwalViewScope($request->user());

        if (!$viewScope['allowed']) {
            $karyawan = Karyawan::where('id_user', $request->user()->id_user)->first();

            if ($karyawan) {
                return redirect()->route('jadwal.show', [
                    'id_karyawan' => $karyawan->id_karyawan,
                    'bulan' => $bulan,
                ]);
            }
        }

        $tanggalAwal = Carbon::parse($bulan . '-01')->startOfMonth();
        $tanggalAkhir = Carbon::parse($bulan . '-01')->endOfMonth();

        $karyawanOptions = Karyawan::orderBy('nama')
            ->when($viewScope['id_divisi'], fn ($q) => $q->where('id_divisi', $viewScope['id_divisi']))
            ->get();

        $karyawanList = Karyawan::orderBy('nama')
            ->when($viewScope['id_divisi'], fn ($q) => $q->where('id_divisi', $viewScope['id_divisi']))
            ->when($idKaryawan, fn ($q) => $q->where('id_karyawan', $idKaryawan))
            ->when($idDivisi, fn ($q) => $q->where('id_divisi', $idDivisi))
            ->get();

        $divisiList = Divisi::orderBy('nama_divisi')
            ->when($viewScope['id_divisi'], fn ($q) => $q->where('id', $viewScope['id_divisi']))
            ->get();

        $jadwalList = JadwalKerja::with('karyawan', 'shift')
                                ->whereDate('tanggal', '>=', $tanggalAwal->format('Y-m-d'))
                                ->whereDate('tanggal', '<=', $tanggalAkhir->format('Y-m-d'))
                                ->get()
                                ->groupBy('id_karyawan');

        $absensiList = Absensi::whereDate('tanggal', '>=', $tanggalAwal->format('Y-m-d'))
            ->whereDate('tanggal', '<=', $tanggalAkhir->format('Y-m-d'))
            ->get()
            ->groupBy('id_karyawan');

        $cutiList = Cuti::with('karyawan')
            ->where('status_persetujuan', 'approved')
            ->whereDate('tanggal_mulai', '<=', $tanggalAkhir->format('Y-m-d'))
            ->whereDate('tanggal_selesai', '>=', $tanggalAwal->format('Y-m-d'))
            ->get()
            ->groupBy('id_karyawan');

        $shiftLegend = $this->getScheduleShiftOptions();
        $shiftLegend = $this->getShiftLegendOptions();

        return view('jadwal.index', compact(
            'karyawanList',
            'karyawanOptions',
            'jadwalList',
            'absensiList',
            'cutiList',
            'shiftLegend',
            'bulan',
            'idKaryawan',
            'idDivisi',
            'divisiList',
            'tanggalAwal',
            'tanggalAkhir'
        ));
    }

    public function create(Request $request)
    {
        $scope = $this->authService->getJadwalManageScope($request->user());
        abort_unless($scope['allowed'], 403);   

        $karyawanList = Karyawan::orderBy('nama')
        ->when($scope['id_divisi'], fn ($q) => $q->where('id_divisi', $scope['id_divisi']))
        ->get();

        $jamKerjaOptions = $this->getScheduleShiftOptions();

        return view('jadwal.create', compact('karyawanList', 'jamKerjaOptions'));
    }

    public function store(Request $request)
    {
        $scope = $this->authService->getJadwalManageScope($request->user());
        abort_unless($scope['allowed'], 403);

        $request->validate([
            'id_karyawan' => 'required|exists:karyawan,id_karyawan',
            'tanggal' => 'required|date',
            'id_shift' => 'required|exists:shifts,id_shift',
        ]);

        if ($scope['id_divisi'] !== null) {
            $karyawan = Karyawan::find($request->id_karyawan);
            abort_if(!$karyawan || $karyawan->id_divisi !== $scope['id_divisi'], 403);
        }

        $existing = JadwalKerja::where('id_karyawan', $request->id_karyawan)
                              ->whereDate('tanggal', $request->tanggal)
                              ->first();

        if ($existing) {
            return redirect()->back()
                           ->withInput()
                           ->with('error', 'Jadwal untuk karyawan ini di tanggal tersebut sudah ada!');
        }

        $jadwal = JadwalKerja::create([
            'id_karyawan' => $request->id_karyawan,
            'tanggal' => $request->tanggal,
            'id_shift' => $request->id_shift,
        ]);

        $bulan = Carbon::parse($request->tanggal)->format('Y-m');

        return redirect()->route('jadwal.index', ['bulan' => $bulan])
                        ->with('success', 'Jadwal berhasil ditambahkan!');
    }

    public function bulkCreate(Request $request)
    {
        $scope = $this->authService->getJadwalManageScope($request->user());
        abort_unless($scope['allowed'], 403);

        $karyawanList = Karyawan::with(['jabatan', 'divisi'])->orderBy('nama')
            ->when($scope['id_divisi'], fn ($q) => $q->where('id_divisi', $scope['id_divisi']))
            ->get();
        $divisiList = Divisi::orderBy('nama_divisi')
            ->when($scope['id_divisi'], fn ($q) => $q->where('id', $scope['id_divisi']))
            ->get();
        $jabatanList = Jabatan::orderBy('nama_jabatan')->get();

        $jamKerjaOptions = $this->getScheduleShiftOptions();

        return view('jadwal.bulk_create', compact('karyawanList', 'divisiList', 'jabatanList', 'jamKerjaOptions'));
    }

    public function bulkStore(Request $request)
    {
        $scope = $this->authService->getJadwalManageScope($request->user());
        abort_unless($scope['allowed'], 403);

        $request->validate([
            'tanggal' => 'required|date',
            'jadwal' => 'required|array',
            'jadwal.*.id_karyawan' => 'required|exists:karyawan,id_karyawan',
            'jadwal.*.id_shift' => 'required|exists:shifts,id_shift',
        ]);

        if ($scope['id_divisi'] !== null) {
            $allowedIds = Karyawan::where('id_divisi', $scope['id_divisi'])->pluck('id_karyawan')->all();
            $request->merge([
                'jadwal' => array_values(array_filter(
                    $request->jadwal,
                    fn ($item) => in_array((int) $item['id_karyawan'], $allowedIds, true)
                )),
            ]);
        }

        $sukses = 0;
        $duplikat = 0;

        foreach ($request->jadwal as $item) {
            if (empty($item['id_shift'])) {
                continue;
            }

            $existing = JadwalKerja::where('id_karyawan', $item['id_karyawan'])
                                  ->whereDate('tanggal', $request->tanggal)
                                  ->first();

            if ($existing) {
                $duplikat++;
                continue;
            }

            JadwalKerja::create([
                'id_karyawan' => $item['id_karyawan'],
                'tanggal' => $request->tanggal,
                'id_shift' => $item['id_shift'],
            ]);

            $sukses++;
        }

        $message = "Berhasil menambahkan {$sukses} jadwal.";
        if ($duplikat > 0) {
            $message .= " {$duplikat} jadwal sudah ada (duplikat).";
        }

        $bulan = Carbon::parse($request->tanggal)->format('Y-m');

        return redirect()->route('jadwal.index', ['bulan' => $bulan])
                        ->with('success', $message);
    }

    public function bulkRangeStore(Request $request, JadwalBulkService $jadwalBulkService)
    {
        $scope = $this->authService->getJadwalManageScope($request->user());
        abort_unless($scope['allowed'], 403);

        $validated = $request->validate([
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            'target_type' => 'required|in:all,divisi,karyawan',
            'id_divisi' => 'required_if:target_type,divisi|nullable|exists:divisis,id',
            'karyawan_ids' => 'required_if:target_type,karyawan|array',
            'karyawan_ids.*' => 'exists:karyawan,id_karyawan',
            'id_shift' => 'required|exists:shifts,kode_shift',
            'overwrite' => 'nullable|boolean',
        ], [
            'tanggal_selesai.after_or_equal' => 'Tanggal selesai tidak boleh sebelum tanggal mulai.',
            'id_divisi.required_if' => 'Pilih divisi untuk target by divisi.',
            'karyawan_ids.required_if' => 'Pilih minimal satu karyawan.',
        ]);

        $validated['overwrite'] = $request->boolean('overwrite');
        $summary = $jadwalBulkService->storeRange($validated, $scope['id_divisi']);

        $bulan = Carbon::parse($validated['tanggal_mulai'])->format('Y-m');
        $message = "Bulk range selesai: {$summary['created']} created, {$summary['updated']} updated, {$summary['skipped']} skipped, {$summary['failed']} failed.";

        return redirect()->route('jadwal.bulk-create')
            ->with('success', $message)
            ->with('bulk_range_summary', $summary)
            ->with('bulk_range_month', $bulan);
    }

    public function edit(Request $request, $id_jadwal)
    {
        $scope = $this->authService->getJadwalManageScope($request->user());
        abort_unless($scope['allowed'], 403);

        $jadwal = JadwalKerja::with('karyawan', 'shift')->findOrFail($id_jadwal);

        if ($scope['id_divisi'] !== null) {
            abort_if($jadwal->karyawan->id_divisi !== $scope['id_divisi'], 403);
        }

        $jamKerjaOptions = $this->getScheduleShiftOptions();

        return view('jadwal.edit', compact('jadwal', 'jamKerjaOptions'));
    }

    public function update(Request $request, $id_jadwal)
    {
        $scope = $this->authService->getJadwalManageScope($request->user());
        abort_unless($scope['allowed'], 403);

        $request->validate([
            'tanggal' => 'required|date',
            'id_shift' => 'required|exists:shifts,kode_shift',
        ]);

        $jadwal = JadwalKerja::with('karyawan')->findOrFail($id_jadwal);
        if ($scope['id_divisi'] !== null) {
            abort_if($jadwal->karyawan->id_divisi !== $scope['id_divisi'], 403);
        }

        $existing = JadwalKerja::where('id_karyawan', $jadwal->id_karyawan)
                              ->whereDate('tanggal', $request->tanggal)
                              ->where('id_jadwal', '!=', $id_jadwal)
                              ->first();

        if ($existing) {
            return redirect()->back()
                           ->withInput()
                           ->with('error', 'Jadwal sudah ada di tanggal tersebut!');
        }

        $jadwal->update([
            'tanggal' => $request->tanggal,
            'id_shift' => $request->id_shift,
        ]);

        $bulan = Carbon::parse($request->tanggal)->format('Y-m');

        return redirect()->route('jadwal.index', ['bulan' => $bulan])
                        ->with('success', 'Jadwal berhasil diupdate!');
    }

    public function destroy(Request $request, $id_jadwal)
    {
        abort_unless($request->user()->can('delete-jadwal'), 403);

        $jadwal = JadwalKerja::findOrFail($id_jadwal);
        $bulan = $jadwal->tanggal->format('Y-m');
        $jadwal->delete();

        return redirect()->route('jadwal.index', ['bulan' => $bulan])
                        ->with('success', 'Jadwal berhasil dihapus!');
    }

    public function show($id_karyawan, Request $request)
    {
        $karyawan = Karyawan::findOrFail($id_karyawan);

        $bulan = $request->get('bulan', date('Y-m'));
        $tanggalAwal = Carbon::parse($bulan . '-01')->startOfMonth();
        $tanggalAkhir = Carbon::parse($bulan . '-01')->endOfMonth();

        $jadwalList = JadwalKerja::with('shift')
                                ->where('id_karyawan', $id_karyawan)
                                ->whereDate('tanggal', '>=', $tanggalAwal->format('Y-m-d'))
                                ->whereDate('tanggal', '<=', $tanggalAkhir->format('Y-m-d'))
                                ->orderBy('tanggal')
                                ->get();

        return view('jadwal.show', compact(
            'karyawan',
            'jadwalList',
            'bulan',
            'tanggalAwal',
            'tanggalAkhir'
        ));
    }

    public function setLiburMassal(Request $request)
    {
        abort_unless($request->user()->can('set-libur-massal'), 403);

        $request->validate([
            'tanggal' => 'required|date',
        ]);

        $karyawanList = Karyawan::all();
        $sukses = 0;
        $updated = 0;

        foreach ($karyawanList as $karyawan) {
            $existing = JadwalKerja::where('id_karyawan', $karyawan->id_karyawan)
                                  ->whereDate('tanggal', $request->tanggal)
                                  ->first();

            if ($existing) {
                $existing->update([
                    'id_shift' => '3',
                ]);
                $updated++;
            } else {
                JadwalKerja::create([
                    'id_karyawan' => $karyawan->id_karyawan,
                    'tanggal' => $request->tanggal,
                    'id_shift' => '3',
                ]);
                $sukses++;
            }
        }

        $message = "Berhasil set libur: {$sukses} baru, {$updated} diupdate.";

        $bulan = Carbon::parse($request->tanggal)->format('Y-m');

        return redirect()->route('jadwal.index', ['bulan' => $bulan])
                        ->with('success', $message);
    }

    private function getShiftLegendOptions()
    {
        return Shift::whereIn('kode_shift', ['Pa', 'Si', 'L', 'C'])
            ->orderByRaw("CASE kode_shift WHEN 'P' THEN 1 WHEN 'S' THEN 2 WHEN 'L' THEN 3 WHEN 'C' THEN 4 ELSE 5 END")
            ->get();
    }
    
    private function getScheduleShiftOptions()
    {
        return Shift::whereIn('kode_shift', ['Pa', 'Si', 'L'])
            ->orderByRaw("CASE kode_shift WHEN 'P' THEN 1 WHEN 'S' THEN 2 WHEN 'L' THEN 3 ELSE 4 END")
            ->get();
    }
}
