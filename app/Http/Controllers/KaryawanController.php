<?php

namespace App\Http\Controllers;

use App\Models\Devisi;
use App\Models\Jabatan;
use App\Models\Karyawan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class KaryawanController extends Controller
{
    public function index()
    {
        $karyawan = Karyawan::with(['jabatan', 'devisi'])->get();
        return view('employees.karyawan_index', compact('karyawan'));
    }

    public function create()
    {
        $jabatanList = Jabatan::orderBy('nama_jabatan')->get();
        $divisiList = Devisi::orderBy('nama_devisi')->get();
        $userList = User::whereDoesntHave('karyawan')->get();
        return view('employees.karyawan_create', compact('jabatanList', 'divisiList', 'userList'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama'          => 'required|string|max:255',
            'id_jabatan'    => 'nullable|exists:jabatans,id',
            'id_devisi'     => 'nullable|exists:devisis,id',
            'tanggal_masuk' => 'nullable|date',
            'id_user'       => 'nullable|exists:users,id_user',
        ]);

        Karyawan::create([
            'nama'          => $request->nama,
            'id_jabatan'    => $request->id_jabatan,
            'id_devisi'     => $request->id_devisi,
            'tanggal_masuk' => $request->tanggal_masuk,
            'id_user'       => $request->id_user,
        ]);

        return redirect()->route('karyawan.index')
            ->with('success', 'Karyawan berhasil ditambahkan.');
    }

    public function show($id_karyawan)
    {
        $karyawan = Karyawan::with(['jabatan', 'devisi', 'absensi', 'cuti'])->findOrFail($id_karyawan);
        return view('employees.karyawan_show', compact('karyawan'));
    }

    public function edit($id_karyawan)
    {
        $karyawan = Karyawan::findOrFail($id_karyawan);
        $jabatanList = Jabatan::orderBy('nama_jabatan')->get();
        $divisiList = Devisi::orderBy('nama_devisi')->get();
        $userList = User::whereDoesntHave('karyawan')
            ->orWhere('id_user', $karyawan->id_user)
            ->get();
        return view('employees.karyawan_edit', compact('karyawan', 'jabatanList', 'divisiList', 'userList'));
    }

    public function update(Request $request, $id_karyawan)
    {
        $karyawan = Karyawan::findOrFail($id_karyawan);

        $request->validate([
            'nama'          => 'required|string|max:255',
            'id_jabatan'    => 'nullable|exists:jabatans,id',
            'id_devisi'     => 'nullable|exists:devisis,id',
            'tanggal_masuk' => 'nullable|date',
            'id_user'       => 'nullable|exists:users,id_user',
        ]);

        $karyawan->update([
            'nama'          => $request->nama,
            'id_jabatan'    => $request->id_jabatan,
            'id_devisi'     => $request->id_devisi,
            'tanggal_masuk' => $request->tanggal_masuk,
            'id_user'       => $request->id_user,
        ]);

        return redirect()->route('karyawan.index')
            ->with('success', 'Data karyawan berhasil diperbarui.');
    }

    public function destroy($id_karyawan)
    {
        $karyawan = Karyawan::findOrFail($id_karyawan);
        $karyawan->delete();

        return redirect()->route('karyawan.index')
            ->with('success', 'Karyawan berhasil dihapus.');
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
