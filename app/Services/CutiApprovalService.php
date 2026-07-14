<?php

namespace App\Services;

use App\Models\Cuti;
use App\Models\Karyawan;
use App\Models\PersetujuanCuti;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class CutiApprovalService
{
    private const LEVEL_LABELS = [
        1 => 'SDM',
        2 => 'Manager Divisi',
        3 => 'Manager Umum',
    ];

    public function canViewApproval(User $user): bool
    {
        return $user->hasRole('admin')
            || $user->hasRole('atasan_divisi')
            || $user->hasAnyRole(['hr', 'hrd']);
    }

    public function pendingQueryFor(User $user): Builder
    {
        $query = Cuti::with([
            'karyawan.divisi',
            'karyawan.jabatan',
            'atasan.jabatan',
            'persetujuanCuti.penyetuju.jabatan',
        ])
            ->where('status_persetujuan', 'pending')
            ->orderBy('created_at', 'desc');

        if ($user->hasAnyRole(['hr', 'hrd'])) {
            return $query;
        }

        $approver = $this->employeeFor($user);

        if ($user->hasRole('admin')) {
            $level = $approver ? $this->approverLevel($approver) : null;
            if ($level === null) {
                return $query;
            }
            return $query->has('persetujuanCuti', '=', $level - 1);
        }

        if (!$user->hasRole('atasan_divisi') || !$approver) {
            return $query->whereRaw('1 = 0');
        }

        $level = $this->approverLevel($approver);
        if ($level === null) {
            return $query->whereRaw('1 = 0');
        }

        $query->where('id_karyawan', '!=', $approver->id_karyawan)
              ->has('persetujuanCuti', '=', $level - 1);

        if ($level === 2) {
            $query->whereHas('karyawan', fn ($q) => $q->where('id_divisi', $approver->id_divisi));
        }

        return $query;
    }

    public function canUpdateStatus(User $user, Cuti $cuti): bool
    {
        if ($cuti->status_persetujuan !== 'pending') {
            return false;
        }

        if ($user->hasAnyRole(['hr', 'hrd'])) {
            return false;
        }

        $approver = $this->employeeFor($user);
        $level = $approver ? $this->approverLevel($approver) : null;

        if ($user->hasRole('admin')) {
            return $level === null || $this->currentLevel($cuti) === $level;
        }

        if (!$user->hasRole('atasan_divisi') || !$approver || $level === null) {
            return false;
        }

        if ((int) $approver->id_karyawan === (int) $cuti->id_karyawan) {
            return false;
        }

        if ($level === 2 && (int) $approver->id_divisi !== (int) $cuti->karyawan?->id_divisi) {
            return false;
        }

        return $this->currentLevel($cuti) === $level;
    }

    public function recordApproval(Cuti $cuti, User $user, string $status, ?string $catatan = null): void
    {
        $approver = $this->employeeFor($user);
        if (!$approver) {
            return;
        }

        PersetujuanCuti::create([
            'id_cuti' => $cuti->id_cuti,
            'id_penyetuju' => $approver->id_karyawan,
            'status_persetujuan' => $status,
            'tanggal_persetujuan' => Carbon::today(),
            'catatan' => $catatan ?? ($status === 'approved' ? 'Disetujui' : 'Ditolak'),
        ]);
    }

    public function employeeFor(User $user): ?Karyawan
    {
        return Karyawan::with(['divisi', 'jabatan'])->where('id_user', $user->id_user)->first();
    }

    public function currentLevel(Cuti $cuti): int
    {
        $count = $cuti->relationLoaded('persetujuanCuti')
            ? $cuti->persetujuanCuti->where('status_persetujuan', 'approved')->count()
            : $cuti->persetujuanCuti()->where('status_persetujuan', 'approved')->count();

        return min($count + 1, 3);
    }

    public function levelLabel(Cuti $cuti): string
    {
        return self::LEVEL_LABELS[$this->currentLevel($cuti)] ?? 'Unknown';
    }

    public function finalApprovalLevel(): int
    {
        return 3;
    }

    private function approverLevel(Karyawan $approver): ?int
    {
        return match ($approver->jabatan?->nama_jabatan) {
            'SDM' => 1,
            'Manager Divisi' => 2,
            'Manager Umum' => 3,
            default => null,
        };
    }
}
