<?php

namespace App\Http\Controllers;

use App\Models\Absensi;
use App\Models\Karyawan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    public function index()
    {
        return view('absensi.attendance_index');
    }

    public function history(Request $request)
    {
        $query = Absensi::with('karyawan');

        if ($request->has('tanggal')) {
            $query->where('tanggal', $request->tanggal);
        }

        if ($request->has('id_karyawan')) {
            $query->where('id_karyawan', $request->id_karyawan);
        }

        $absensi = $query->orderBy('tanggal', 'desc')
                         ->orderBy('jam_masuk', 'desc')
                         ->paginate(20);

        $karyawanList = Karyawan::all();

        return view(
            'absensi.attendance_history', 
            compact('absensi', 'karyawanList')
        );
    }

    public function checkIn(Request $request)
    {
        $request->validate([
            'photo' => 'required|string',
            'type' => 'required|in:masuk,pulang',
        ]);

        try {
            $imageData = $request->photo;
            $imageData = str_replace('data:image/jpeg;base64,', '', $imageData);
            $imageData = str_replace(' ', '+', $imageData);
            $imageBinary = base64_decode($imageData);

            // Simpan foto sementara
            $tempPath = storage_path('app/temp_attendance.jpg');
            file_put_contents($tempPath, $imageBinary);

            // Kirim ke Python service untuk face recognition
            $response = Http::timeout(30)->post(
                'http://localhost:5000/api/recognize-face',
                ['image_path' => $tempPath]
            );

            // Hapus file temporary
            if (file_exists($tempPath)) {
                unlink($tempPath);
            }

            if (!$response->successful()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal deteksi wajah. Pastikan wajah jelas.'
                ], 400);
            }

            $responseData = $response->json();

            if (!isset($responseData['matched']) || !$responseData['matched']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Wajah tidak dikenali. Daftar dulu.'
                ], 404);
            }

            $idKaryawan = $responseData['id_karyawan'];
            $karyawan = Karyawan::find($idKaryawan);

            if (!$karyawan) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data karyawan tidak ditemukan.'
                ], 404);
            }

            $today = Carbon::today();
            $currentTime = Carbon::now();

            // Ambil atau buat record absensi hari ini
            $absensi = Absensi::firstOrNew([
                'id_karyawan' => $idKaryawan,
                'tanggal' => $today,
            ]);

            if ($request->type === 'masuk') {
                if ($absensi->jam_masuk) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Sudah absen masuk: ' . $absensi->jam_masuk
                    ], 400);
                }

                $absensi->jam_masuk = $currentTime->format('H:i:s');

                // Tentukan status (terlambat jika masuk setelah jam 08:00)
                $jamMasukStandar = Carbon::parse('08:00:00');
                if ($currentTime->greaterThan($jamMasukStandar)) {
                    $absensi->status = 'terlambat';
                } else {
                    $absensi->status = 'hadir';
                }

            } else { // pulang
                if (!$absensi->jam_masuk) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Belum absen masuk hari ini.'
                    ], 400);
                }

                if ($absensi->jam_pulang) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Sudah absen pulang: ' . $absensi->jam_pulang
                    ], 400);
                }

                $absensi->jam_pulang = $currentTime->format('H:i:s');
            }

            $absensi->save();

            return response()->json([
                'success' => true,
                'message' => 'Absen ' . $request->type . ' berhasil!',
                'data' => [
                    'nama' => $karyawan->nama,
                    'waktu' => $currentTime->format('H:i:s'),
                    'status' => $absensi->status,
                    'confidence' => $responseData['confidence'] ?? 0,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }
}
