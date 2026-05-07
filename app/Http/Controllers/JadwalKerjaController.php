<?php

namespace App\Http\Controllers;

use App\Models\JadwalKerja;
use App\Models\Karyawan;
use App\Models\Absensi;
use App\Models\Cuti;
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
        
        $jamKerjaOptions = [
            'Pagi (07:00-15:00)',
            'Siang (15:00-23:00)',
            'Malam (23:00-07:00)',
            'Libur',
        ];

        return view('jadwal.create', compact('karyawanList', 'jamKerjaOptions'));
    }

    // Store jadwal baru
    public function store(Request $request)
    {
        $request->validate([
            'id_karyawan' => 'required|exists:karyawan,id_karyawan',
            'tanggal' => 'required|date',
            'jam_kerja' => 'required|string',
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

        // Create jadwal
        $jadwal = JadwalKerja::create([
            'id_karyawan' => $request->id_karyawan,
            'tanggal' => $request->tanggal,
            'jam_kerja' => $request->jam_kerja,
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
        
        $jamKerjaOptions = [
            'Pagi (08:00-17:00)',
            'Middle (11:00-20:00)',
            'Siang (13:00-22:00)',
            'Libur',
        ];

        return view('jadwal.bulk_create', compact('karyawanList', 'jamKerjaOptions'));
    }

    // Store bulk jadwal
    public function bulkStore(Request $request)
    {
        $request->validate([
            'tanggal' => 'required|date',
            'jadwal' => 'required|array',
            'jadwal.*.id_karyawan' => 'required|exists:karyawan,id_karyawan',
            'jadwal.*.jam_kerja' => 'required|string',
        ]);

        $sukses = 0;
        $duplikat = 0;

        foreach ($request->jadwal as $item) {
            // Skip jika jam_kerja kosong
            if (empty($item['jam_kerja'])) {
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
                'jam_kerja' => $item['jam_kerja'],
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
        
        $jamKerjaOptions = [
            'Pagi (08:00-17:00)',
            'Middle (11:00-20:00)',
            'Siang (13:00-22:00)',
            'Libur',
        ];

        return view('jadwal.edit', compact('jadwal', 'jamKerjaOptions'));
    }

    // Update jadwal
    public function update(Request $request, $id_jadwal)
    {
        $request->validate([
            'tanggal' => 'required|date',
            'jam_kerja' => 'required|string',
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

        $jadwal->update([
            'tanggal' => $request->tanggal,
            'jam_kerja' => $request->jam_kerja,
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
                    'keterangan' => $request->keterangan,
                ]);
                $updated++;
            } else {
                // Create baru
                JadwalKerja::create([
                    'id_karyawan' => $karyawan->id_karyawan,
                    'tanggal' => $request->tanggal,
                    'jam_kerja' => 'Libur',
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
}
