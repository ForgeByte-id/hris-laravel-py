<?php

namespace App\Http\Controllers;

use App\Models\JadwalKerja;
use App\Models\Karyawan;
use App\Models\Absensi;
use App\Models\Cuti;
use App\Models\Shift;
use Illuminate\Http\Request;
use Carbon\Carbon;

class JadwalKerjaController extends Controller
{
    // Halaman utama jadwal (kalender view)
    public function index(Request $request)
    {
        $bulan = $request->get('bulan', date('Y-m'));
        $tanggalAwal = Carbon::parse($bulan . '-01')->startOfMonth();
        $tanggalAkhir = Carbon::parse($bulan . '-01')->endOfMonth();

        // Get semua karyawan
        $karyawanList = Karyawan::orderBy('nama')->get();

        // Get jadwal bulan ini - PENTING: Format tanggal harus match
        $jadwalList = JadwalKerja::with('karyawan')
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

        // Debug - uncomment jika perlu cek data
        // dd($jadwalList->toArray());

        return view('jadwal.index', compact(
            'karyawanList', 
            'jadwalList', 
            'absensiList',
            'cutiList',
            'bulan',
            'tanggalAwal',
            'tanggalAkhir'
        ));
    }

    // Form create jadwal (single/multiple)
    public function create()
    {
        $karyawanList = Karyawan::orderBy('nama')->get();

        $jamKerjaOptions = $this->getScheduleShiftOptions();

        return view('jadwal.create', compact('karyawanList', 'jamKerjaOptions'));
    }

    // Store jadwal baru
    public function store(Request $request)
    {
        $request->validate([
            'id_karyawan' => 'required|exists:karyawan,id_karyawan',
            'tanggal' => 'required|date',
            'kode_shift' => 'required|exists:shifts,kode_shift',
            'keterangan' => 'nullable|string|max:500',
        ]);

        // Cek duplikasi
        $existing = JadwalKerja::where('id_karyawan', $request->id_karyawan)
                              ->whereDate('tanggal', $request->tanggal)
                              ->first();

        if ($existing) {
            return redirect()->back()
                           ->withInput()
                           ->with('error', 'Jadwal untuk karyawan ini di tanggal tersebut sudah ada!');
        }

        $shift = Shift::where('kode_shift', $request->kode_shift)->firstOrFail();

        // Create jadwal
        $jadwal = JadwalKerja::create([
            'id_karyawan' => $request->id_karyawan,
            'tanggal' => $request->tanggal,
            'jam_kerja' => $shift->label,
            'kode_shift' => $shift->kode_shift,
            'keterangan' => $request->keterangan,
        ]);

        // Redirect ke bulan yang sesuai dengan tanggal jadwal
        $bulan = Carbon::parse($request->tanggal)->format('Y-m');

        return redirect()->route('jadwal.index', ['bulan' => $bulan])
                        ->with('success', 'Jadwal berhasil ditambahkan!');
    }

    // Bulk create jadwal (untuk semua karyawan sekaligus)
    public function bulkCreate()
    {
        $karyawanList = Karyawan::orderBy('nama')->get();

        $jamKerjaOptions = $this->getScheduleShiftOptions();

        return view('jadwal.bulk_create', compact('karyawanList', 'jamKerjaOptions'));
    }

    // Store bulk jadwal
    public function bulkStore(Request $request)
    {
        $request->validate([
            'tanggal' => 'required|date',
            'jadwal' => 'required|array',
            'jadwal.*.id_karyawan' => 'required|exists:karyawan,id_karyawan',
            'jadwal.*.kode_shift' => 'required|exists:shifts,kode_shift',
        ]);

        $sukses = 0;
        $duplikat = 0;
        $shiftMap = Shift::get()->keyBy('kode_shift');

        foreach ($request->jadwal as $item) {
            // Skip jika kode_shift kosong
            if (empty($item['kode_shift'])) {
                continue;
            }

            $shift = $shiftMap->get($item['kode_shift']);
            if (!$shift) {
                continue;
            }

            // Cek duplikasi
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
                'jam_kerja' => $shift->label,
                'kode_shift' => $shift->kode_shift,
                'keterangan' => $item['keterangan'] ?? null,
            ]);

            $sukses++;
        }

        $message = "Berhasil menambahkan {$sukses} jadwal.";
        if ($duplikat > 0) {
            $message .= " {$duplikat} jadwal sudah ada (duplikat).";
        }

        // Redirect ke bulan yang sesuai
        $bulan = Carbon::parse($request->tanggal)->format('Y-m');

        return redirect()->route('jadwal.index', ['bulan' => $bulan])
                        ->with('success', $message);
    }

    // Form edit jadwal
    public function edit($id_jadwal)
    {
        $jadwal = JadwalKerja::with('karyawan')->findOrFail($id_jadwal);

        $jamKerjaOptions = $this->getScheduleShiftOptions();

        return view('jadwal.edit', compact('jadwal', 'jamKerjaOptions'));
    }

    // Update jadwal
    public function update(Request $request, $id_jadwal)
    {
        $request->validate([
            'tanggal' => 'required|date',
            'kode_shift' => 'required|exists:shifts,kode_shift',
            'keterangan' => 'nullable|string|max:500',
        ]);

        $jadwal = JadwalKerja::findOrFail($id_jadwal);

        // Cek duplikasi (kecuali diri sendiri)
        $existing = JadwalKerja::where('id_karyawan', $jadwal->id_karyawan)
                              ->whereDate('tanggal', $request->tanggal)
                              ->where('id_jadwal', '!=', $id_jadwal)
                              ->first();

        if ($existing) {
            return redirect()->back()
                           ->withInput()
                           ->with('error', 'Jadwal sudah ada di tanggal tersebut!');
        }

        $shift = Shift::where('kode_shift', $request->kode_shift)->firstOrFail();

        $jadwal->update([
            'tanggal' => $request->tanggal,
            'jam_kerja' => $shift->label,
            'kode_shift' => $shift->kode_shift,
            'keterangan' => $request->keterangan,
        ]);

        // Redirect ke bulan yang sesuai
        $bulan = Carbon::parse($request->tanggal)->format('Y-m');

        return redirect()->route('jadwal.index', ['bulan' => $bulan])
                        ->with('success', 'Jadwal berhasil diupdate!');
    }

    // Delete jadwal
    public function destroy($id_jadwal)
    {
        $jadwal = JadwalKerja::findOrFail($id_jadwal);
        $bulan = $jadwal->tanggal->format('Y-m');
        $jadwal->delete();

        return redirect()->route('jadwal.index', ['bulan' => $bulan])
                        ->with('success', 'Jadwal berhasil dihapus!');
    }

    // Lihat jadwal karyawan tertentu
    public function show($id_karyawan, Request $request)
    {
        $karyawan = Karyawan::findOrFail($id_karyawan);
        
        $bulan = $request->get('bulan', date('Y-m'));
        $tanggalAwal = Carbon::parse($bulan . '-01')->startOfMonth();
        $tanggalAkhir = Carbon::parse($bulan . '-01')->endOfMonth();

        $jadwalList = JadwalKerja::where('id_karyawan', $id_karyawan)
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

    // Set hari libur untuk semua karyawan
    public function setLiburMassal(Request $request)
    {
        $request->validate([
            'tanggal' => 'required|date',
            'keterangan' => 'nullable|string',
        ]);

        $karyawanList = Karyawan::all();
        $sukses = 0;
        $updated = 0;

        foreach ($karyawanList as $karyawan) {
            // Cek apakah sudah ada jadwal
            $existing = JadwalKerja::where('id_karyawan', $karyawan->id_karyawan)
                                  ->whereDate('tanggal', $request->tanggal)
                                  ->first();

            if ($existing) {
                // Update jadi libur
                $existing->update([
                    'jam_kerja' => 'Libur',
                    'kode_shift' => 'L',
                    'keterangan' => $request->keterangan,
                ]);
                $updated++;
            } else {
                // Create baru
                JadwalKerja::create([
                    'id_karyawan' => $karyawan->id_karyawan,
                    'tanggal' => $request->tanggal,
                    'jam_kerja' => 'Libur',
                    'kode_shift' => 'L',
                    'keterangan' => $request->keterangan,
                ]);
                $sukses++;
            }
        }

        $message = "Berhasil set libur: {$sukses} baru, {$updated} diupdate.";
        
        // Redirect ke bulan yang sesuai
        $bulan = Carbon::parse($request->tanggal)->format('Y-m');

        return redirect()->route('jadwal.index', ['bulan' => $bulan])
                        ->with('success', $message);
    }

    private function getScheduleShiftOptions()
    {
        return Shift::whereIn('kode_shift', ['P', 'M', 'S', 'L'])
            ->orderByRaw("CASE kode_shift WHEN 'P' THEN 1 WHEN 'M' THEN 2 WHEN 'S' THEN 3 WHEN 'L' THEN 4 ELSE 5 END")
            ->get();
    }
}
