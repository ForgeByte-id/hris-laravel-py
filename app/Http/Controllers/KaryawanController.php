<?php

namespace App\Http\Controllers;

use App\Models\Karyawan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class KaryawanController extends Controller
{
    public function index()
    {
        $karyawan = Karyawan::all();
        return view('employees.karyawan_index', compact('karyawan'));
    }

    public function registerFace($id_karyawan)
    {
        $karyawan = Karyawan::findOrFail($id_karyawan);
        return view('karyawan.register-face', compact('karyawan'));
    }

    public function storeFaceEncoding(Request $request)
    {
        $request->validate([
            'id_karyawan' => 'required|exists:karyawan,id_karyawan',
            'photo' => 'required|string',
        ]);

        try {
            $imageData = $request->photo;
            $imageData = str_replace('data:image/jpeg;base64,', '', $imageData);
            $imageData = str_replace(' ', '+', $imageData);
            $imageBinary = base64_decode($imageData);

            // Simpan foto sementara
            $tempPath = storage_path('app/temp_face.jpg');
            file_put_contents($tempPath, $imageBinary);

            // Kirim ke Python service untuk generate encoding
            $response = Http::timeout(30)->post(
                'http://localhost:5000/api/encode-face',
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

            if (!isset($responseData['encoding'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak ada wajah terdeteksi.'
                ], 400);
            }

            // Update face_embedding di tabel karyawan
            $karyawan = Karyawan::find($request->id_karyawan);
            $karyawan->face_embedding = json_encode($responseData['encoding']);
            $karyawan->save();

            return response()->json([
                'success' => true,
                'message' => 'Wajah berhasil didaftarkan!',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    public function deleteFaceEncoding($id_karyawan)
    {
        $karyawan = Karyawan::find($id_karyawan);
        
        if ($karyawan) {
            $karyawan->face_embedding = null;
            $karyawan->save();
            
            return response()->json([
                'success' => true,
                'message' => 'Data wajah berhasil dihapus.'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Data tidak ditemukan.'
        ], 404);
    }
}
