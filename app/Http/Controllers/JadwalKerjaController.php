<?php

namespace App\Http\Controllers;

use App\Models\JadwalKerja;
use App\Models\Karyawan;
use App\Models\Divisi;
use App\Models\Absensi;
use App\Models\Cuti;
use App\Models\Shift;
use App\Services\JadwalBulkService;
use Illuminate\Http\Request;
use Carbon\Carbon;

class JadwalKerjaController extends Controller
{
    public function index(Request $request)
    {
        $bulan = $request->get('bulan', date('Y-m'));
        $tanggalAwal = Carbon::parse($bulan . '-01')->startOfMonth();
        $tanggalAkhir = Carbon::parse($bulan . '-01')->endOfMonth();

        $karyawanList = Karyawan::orderBy('nama')->get();

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

        return view('jadwal.index', compact(
            'karyawanList',
            'jadwalList',
            'absensiList',
            'cutiList',
            'shiftLegend',
            'bulan',
            'tanggalAwal',
            'tanggalAkhir'
        ));
    }

    public function create()
    {
        $karyawanList = Karyawan::orderBy('nama')->get();

        $jamKerjaOptions = $this->getScheduleShiftOptions();

        return view('jadwal.create', compact('karyawanList', 'jamKerjaOptions'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'id_karyawan' => 'required|exists:karyawan,id_karyawan',
            'tanggal' => 'required|date',
            'id_shift' => 'required|exists:shifts,kode_shift',
        ]);

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

    public function bulkCreate()
    {
        $karyawanList = Karyawan::with(['jabatan', 'divisi'])->orderBy('nama')->get();
        $divisiList = Divisi::orderBy('nama_divisi')->get();

        $jamKerjaOptions = $this->getScheduleShiftOptions();

        return view('jadwal.bulk_create', compact('karyawanList', 'divisiList', 'jamKerjaOptions'));
    }

    public function bulkStore(Request $request)
    {
        $request->validate([
            'tanggal' => 'required|date',
            'jadwal' => 'required|array',
            'jadwal.*.id_karyawan' => 'required|exists:karyawan,id_karyawan',
            'jadwal.*.id_shift' => 'required|exists:shifts,kode_shift',
        ]);

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
        $summary = $jadwalBulkService->storeRange($validated);

        $bulan = Carbon::parse($validated['tanggal_mulai'])->format('Y-m');
        $message = "Bulk range selesai: {$summary['created']} created, {$summary['updated']} updated, {$summary['skipped']} skipped, {$summary['failed']} failed.";

        return redirect()->route('jadwal.bulk-create')
            ->with('success', $message)
            ->with('bulk_range_summary', $summary)
            ->with('bulk_range_month', $bulan);
    }

    public function edit($id_jadwal)
    {
        $jadwal = JadwalKerja::with('karyawan', 'shift')->findOrFail($id_jadwal);

        $jamKerjaOptions = $this->getScheduleShiftOptions();

        return view('jadwal.edit', compact('jadwal', 'jamKerjaOptions'));
    }

    public function update(Request $request, $id_jadwal)
    {
        $request->validate([
            'tanggal' => 'required|date',
            'id_shift' => 'required|exists:shifts,kode_shift',
        ]);

        $jadwal = JadwalKerja::findOrFail($id_jadwal);

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

    public function destroy($id_jadwal)
    {
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
                    'id_shift' => 'L',
                ]);
                $updated++;
            } else {
                JadwalKerja::create([
                    'id_karyawan' => $karyawan->id_karyawan,
                    'tanggal' => $request->tanggal,
                    'id_shift' => 'L',
                ]);
                $sukses++;
            }
        }

        $message = "Berhasil set libur: {$sukses} baru, {$updated} diupdate.";

        $bulan = Carbon::parse($request->tanggal)->format('Y-m');

        return redirect()->route('jadwal.index', ['bulan' => $bulan])
                        ->with('success', $message);
    }

    private function getScheduleShiftOptions()
    {
        return Shift::whereIn('kode_shift', ['Pa', 'Si', 'L'])
            ->orderByRaw("CASE kode_shift WHEN 'P' THEN 1 WHEN 'M' THEN 2 WHEN 'S' THEN 3 WHEN 'L' THEN 4 ELSE 5 END")
            ->get();
    }
}
