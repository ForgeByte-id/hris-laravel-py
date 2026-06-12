<?php

namespace App\Http\Controllers;

use App\Models\Cuti;
use App\Models\Karyawan;
use App\Services\CutiApprovalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CutiController extends Controller
{
    // Halaman daftar cuti (untuk karyawan)
    public function index()
    {
        $user = Auth::user();
        $isAdmin = $user->hasRole('admin');
        $isHrReadonly = $user->hasAnyRole(['hr', 'hrd']);

        if ($isAdmin || $isHrReadonly) {
            $cutiList = Cuti::with('karyawan')
                ->orderBy('created_at', 'desc')
                ->get();

            return view('cuti.index', [
                'cutiList' => $cutiList,
                'karyawan' => null,
                'isAdmin' => $isAdmin,
                'isHrReadonly' => $isHrReadonly,
            ]);
        }

        $karyawan = Karyawan::where('id_user', $user->id_user)->first();
        if (!$karyawan) {
            return redirect()->back()->with('error', 'Data karyawan tidak ditemukan');
        }

        $cutiList = Cuti::where('id_karyawan', $karyawan->id_karyawan)
                        ->orderBy('created_at', 'desc')
                        ->get();

        return view('cuti.index', [
            'cutiList' => $cutiList,
            'karyawan' => $karyawan,
            'isAdmin' => false,
            'isHrReadonly' => false,
        ]);
    }

    // Form pengajuan cuti
    public function create()
    {
        $user = Auth::user();
        $isAdmin = $user->hasRole('admin');
        $karyawan = $isAdmin ? null : Karyawan::where('id_user', $user->id_user)->first();
        $karyawanList = $isAdmin ? Karyawan::orderBy('nama')->get() : collect();

        if (!$isAdmin && !$karyawan) {
            return redirect()->route('cuti.index')->with('error', 'Data karyawan tidak ditemukan');
        }
        
        // Daftar jenis cuti
        $jenisCuti = [
            'Tahunan',
            'Sakit',
            'Melahirkan',
            'Menikah',
            'Keluarga Meninggal',
            'Lainnya'
        ];

        return view('cuti.create', compact('karyawan', 'karyawanList', 'jenisCuti', 'isAdmin'));
    }

    // Proses submit pengajuan cuti
    public function store(Request $request, CutiApprovalService $approvalService)
    {
        $user = Auth::user();
        $isAdmin = $user->hasRole('admin');

        $request->validate([
            'id_karyawan' => $isAdmin ? 'required|exists:karyawan,id_karyawan' : 'nullable',
            'jenis_cuti' => 'required|string',
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date',
            'keterangan' => 'nullable|string|max:500',
        ]);

        if (Carbon::parse($request->tanggal_selesai)->lt(Carbon::parse($request->tanggal_mulai))) {
            return redirect()->back()->withInput()->withErrors([
                'tanggal_selesai' => 'Tanggal selesai harus setelah atau sama dengan tanggal mulai.',
            ]);
        }

        $karyawan = $isAdmin
            ? Karyawan::findOrFail($request->id_karyawan)
            : Karyawan::where('id_user', $user->id_user)->first();

        if (!$karyawan) {
            return redirect()->back()->withInput()->with('error', 'Data karyawan tidak ditemukan');
        }

        $durasiCuti = Carbon::parse($request->tanggal_mulai)->diffInDays(Carbon::parse($request->tanggal_selesai)) + 1;

        if (($karyawan->remaining_leave_quota ?? 0) < $durasiCuti) {
            return redirect()->back()->withInput()->with('error', 'Sisa kuota cuti tidak mencukupi.');
        }

        $atasan = $isAdmin ? null : $approvalService->findDivisionHeadFor($karyawan);

        DB::transaction(function () use ($request, $karyawan, $isAdmin, $atasan, $durasiCuti) {
            Cuti::create([
                'id_karyawan' => $karyawan->id_karyawan,
                'jenis_cuti' => $request->jenis_cuti,
                'tanggal_mulai' => $request->tanggal_mulai,
                'tanggal_selesai' => $request->tanggal_selesai,
                'keterangan' => $request->keterangan,
                'status_persetujuan' => $isAdmin ? 'approved' : 'pending',
                'tanggal_persetujuan' => $isAdmin ? Carbon::now() : null,
                'id_atasan' => $isAdmin ? null : ($atasan ? $atasan->id_karyawan : null),
            ]);

            if ($isAdmin) {
                $karyawan->decrement('remaining_leave_quota', $durasiCuti);
            }
        });

        if (!$isAdmin && !$atasan) {
            return redirect()->route('cuti.index')
                ->with('warning', 'Pengajuan cuti berhasil dikirim, tetapi belum ada atasan divisi yang terdaftar untuk divisi ini.');
        }

        return redirect()->route('cuti.index')
                        ->with('success', $isAdmin ? 'Cuti berhasil dibuat dan langsung disetujui.' : 'Pengajuan cuti berhasil dikirim!');
    }

    // Halaman approval untuk atasan/HRD
    public function approval(CutiApprovalService $approvalService)
    {
        $user = Auth::user();

        if (!$approvalService->canViewApproval($user)) {
            return redirect()->back()->with('error', 'Anda tidak memiliki akses approval');
        }

        $cutiList = $approvalService->pendingQueryFor($user)->get();
        $approvalPermissions = $cutiList->mapWithKeys(fn ($cuti) => [
            $cuti->id_cuti => $approvalService->canUpdateStatus($user, $cuti),
        ]);
        $isReadonlyApproval = $user->hasAnyRole(['hr', 'hrd']) && !$user->hasRole('admin');

        return view('cuti.approval', compact('cutiList', 'approvalPermissions', 'isReadonlyApproval'));
    }

    // Proses approval/reject
    public function updateStatus(Request $request, $id_cuti, CutiApprovalService $approvalService)
    {
        $request->validate([
            'status' => 'required|in:approved,rejected',
        ]);

        $cuti = Cuti::with('karyawan')->findOrFail($id_cuti);

        abort_unless(
            $approvalService->canUpdateStatus(Auth::user(), $cuti),
            403,
            'Anda tidak berwenang memproses pengajuan cuti ini.'
        );

        if ($request->status === 'approved') {
            $durasiCuti = $cuti->tanggal_mulai->diffInDays($cuti->tanggal_selesai) + 1;
            $karyawan = $cuti->karyawan;

            if (($karyawan->remaining_leave_quota ?? 0) < $durasiCuti) {
                return redirect()->back()->with('error', 'Sisa kuota cuti karyawan tidak mencukupi.');
            }
        }

        DB::transaction(function () use ($cuti, $request) {
            $cuti->update([
                'status_persetujuan' => $request->status,
                'tanggal_persetujuan' => Carbon::now(),
            ]);

            if ($request->status === 'approved') {
                $durasiCuti = $cuti->tanggal_mulai->diffInDays($cuti->tanggal_selesai) + 1;
                $cuti->karyawan()->decrement('remaining_leave_quota', $durasiCuti);
            }
        });

        $statusText = $request->status === 'approved' ? 'disetujui' : 'ditolak';
        
        return redirect()->back()
                        ->with('success', "Pengajuan cuti berhasil {$statusText}!");
    }

    // Riwayat semua cuti (untuk HRD/Admin)
    public function history(Request $request)
    {
        $query = Cuti::with(['karyawan', 'atasan']);

        // Filter by status
        if ($request->has('status') && $request->status != '') {
            $query->where('status_persetujuan', $request->status);
        }

        // Filter by karyawan
        if ($request->has('id_karyawan') && $request->id_karyawan != '') {
            $query->where('id_karyawan', $request->id_karyawan);
        }

        // Filter by bulan
        if ($request->has('bulan') && $request->bulan != '') {
            $query->whereMonth('tanggal_mulai', $request->bulan);
        }

        $cutiList = $query->orderBy('created_at', 'desc')->paginate(20);
        $karyawanList = Karyawan::all();

        return view('cuti.history', compact('cutiList', 'karyawanList'));
    }

    // Detail cuti
    public function show($id_cuti)
    {
        $cuti = Cuti::with(['karyawan', 'atasan'])->findOrFail($id_cuti);
        return view('cuti.show', compact('cuti'));
    }

    // Cancel pengajuan (hanya bisa kalau masih pending)
    public function cancel($id_cuti)
    {
        $cuti = Cuti::findOrFail($id_cuti);
        
        if ($cuti->status_persetujuan !== 'pending') {
            return redirect()->back()
                           ->with('error', 'Hanya bisa membatalkan pengajuan yang masih pending');
        }

        $cuti->delete();
        
        return redirect()->route('cuti.index')
                        ->with('success', 'Pengajuan cuti berhasil dibatalkan');
    }
}
