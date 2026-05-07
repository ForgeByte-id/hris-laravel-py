<?php

namespace App\Http\Controllers;

use App\Models\Devisi;
use App\Models\Jabatan;
use App\Models\Karyawan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\FaceRecognitionService;

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
        $divisiList  = Devisi::orderBy('nama_devisi')->get();
        return view('employees.karyawan_create', compact('jabatanList', 'divisiList'));
    }

    public function store(Request $request)
    {
        $request->validate([
            // Karyawan fields
            'nama'                  => 'required|string|max:255',
            'id_jabatan'            => 'nullable|exists:jabatans,id',
            'id_devisi'             => 'nullable|exists:devisis,id',
            'tanggal_masuk'         => 'nullable|date',
            'yearly_leave_quota'    => 'nullable|integer|min:0|max:365',
            'remaining_leave_quota' => 'nullable|integer|min:0|max:365',
            // New user account fields
            'username'              => 'required|string|max:255|unique:users,username',
            'email'                 => 'nullable|email|max:255|unique:users,email',
            'password'              => 'required|string|min:6|confirmed',
        ]);

        DB::beginTransaction();
        try {
            // 1. Create the login account first
            $user = User::create([
                'username' => $request->username,
                'email'    => $request->email ?: null,
                'password' => $request->password,   // hashed automatically by User model cast
                'role'     => 'karyawan',
            ]);

            // Assign Spatie role so permissions work correctly
            $user->assignRole('karyawan');

            // 2. Create karyawan linked to the new user
            $yearlyLeaveQuota    = $request->yearly_leave_quota ?? 12;
            $remainingLeaveQuota = $request->remaining_leave_quota ?? $yearlyLeaveQuota;

            Karyawan::create([
                'nama'                  => $request->nama,
                'id_jabatan'            => $request->id_jabatan,
                'id_devisi'             => $request->id_devisi,
                'tanggal_masuk'         => $request->tanggal_masuk,
                'id_user'               => $user->id_user,
                'yearly_leave_quota'    => $yearlyLeaveQuota,
                'remaining_leave_quota' => min($remainingLeaveQuota, $yearlyLeaveQuota),
            ]);

            DB::commit();

            return redirect()->route('karyawan.index')
                ->with('success', "Karyawan '{$request->nama}' dan akun login berhasil dibuat.");

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()
                ->with('error', 'Gagal membuat karyawan: ' . $e->getMessage());
        }
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
            'yearly_leave_quota' => 'nullable|integer|min:0|max:365',
            'remaining_leave_quota' => 'nullable|integer|min:0|max:365',
        ]);

        $yearlyLeaveQuota = $request->yearly_leave_quota ?? $karyawan->yearly_leave_quota ?? 12;
        $remainingLeaveQuota = $request->remaining_leave_quota ?? $karyawan->remaining_leave_quota ?? $yearlyLeaveQuota;

        $karyawan->update([
            'nama'          => $request->nama,
            'id_jabatan'    => $request->id_jabatan,
            'id_devisi'     => $request->id_devisi,
            'tanggal_masuk' => $request->tanggal_masuk,
            'id_user'       => $request->id_user,
            'yearly_leave_quota' => $yearlyLeaveQuota,
            'remaining_leave_quota' => min($remainingLeaveQuota, $yearlyLeaveQuota),
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

    public function storeFaceEncoding(Request $request, FaceRecognitionService $faceRecognitionService)
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

            // Simpan foto sementara di direktori yang bisa diakses Python service
            $tempPath = $faceRecognitionService->makeTempPath('temp_face_');
            file_put_contents($tempPath, $imageBinary);

            // Kirim ke Python service untuk generate encoding
            $result = $faceRecognitionService->encodeFace($tempPath);

            // Hapus file temporary
            if (file_exists($tempPath)) {
                unlink($tempPath);
            }

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['error'] ?? 'Gagal deteksi wajah. Pastikan wajah jelas.'
                ], 400);
            }

            if (!isset($result['encoding'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak ada wajah terdeteksi.'
                ], 400);
            }

            // Update face_embedding di tabel karyawan
            $karyawan = Karyawan::find($request->id_karyawan);
            $karyawan->face_embedding = json_encode($result['encoding']);
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
