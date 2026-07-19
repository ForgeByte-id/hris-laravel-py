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
        4 => 'Management',
    ];

    /**
     * Applicant starts at a level above their own position:
     * - Regular employee / Staff → Level 1 (SDM)
     * - SDM                      → Level 2 (Manager Divisi)
     * - Manager Divisi          → Level 3 (Manager Umum)
     * - Manager Umum            → Level 4 (Management)
     */
    private function startingLevel(Karyawan $applicant): int
    {
        return match ($applicant->jabatan?->nama_jabatan) {
            'SDM'            => 2,
            'Manager Divisi' => 3,
            'Manager Umum'   => 4,
            default          => 1,
        };
    }

    public function canViewApproval(User $user): bool
    {
        return $user->hasRole('admin')
            || $user->hasRole('atasan_divisi')
            || $user->hasRole('Management')
            || $user->hasAnyRole(['hr', 'hrd']);
    }

    /**
     * Filter query to only show leaves currently at the target level.
     */
    private function queryForLevel(Builder $query, int $targetLevel): Builder
    {
        return $query->where(function ($q) use ($targetLevel) {
            // Case 1: Staff (start = 1) -> count = targetLevel - 1
            $q->where(function ($sub) use ($targetLevel) {
                $sub->whereHas('karyawan.jabatan', function ($j) {
                    $j->whereNotIn('nama_jabatan', ['SDM', 'Manager Divisi', 'Manager Umum']);
                })->has('persetujuanCuti', '=', $targetLevel - 1);
            });

            // Case 2: SDM (start = 2) -> count = targetLevel - 2
            if ($targetLevel >= 2) {
                $q->orWhere(function ($sub) use ($targetLevel) {
                    $sub->whereHas('karyawan.jabatan', function ($j) {
                        $j->where('nama_jabatan', 'SDM');
                    })->has('persetujuanCuti', '=', $targetLevel - 2);
                });
            }

            // Case 3: Manager Divisi (start = 3) -> count = targetLevel - 3
            if ($targetLevel >= 3) {
                $q->orWhere(function ($sub) use ($targetLevel) {
                    $sub->whereHas('karyawan.jabatan', function ($j) {
                        $j->where('nama_jabatan', 'Manager Divisi');
                    })->has('persetujuanCuti', '=', $targetLevel - 3);
                });
            }

            // Case 4: Manager Umum (start = 4) -> count = targetLevel - 4
            if ($targetLevel >= 4) {
                $q->orWhere(function ($sub) use ($targetLevel) {
                    $sub->whereHas('karyawan.jabatan', function ($j) {
                        $j->where('nama_jabatan', 'Manager Umum');
                    })->has('persetujuanCuti', '=', $targetLevel - 4);
                });
            }
        });
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
            $level = $approver ? $this->approverLevel($approver) : 1;
            return $this->queryForLevel($query, $level);
        }

        if ($user->hasRole('Management')) {
            return $this->queryForLevel($query, 4);
        }

        if (!$user->hasRole('atasan_divisi') || !$approver) {
            return $query->whereRaw('1 = 0');
        }

        $level = $this->approverLevel($approver);
        if ($level === null) {
            return $query->whereRaw('1 = 0');
        }

        $query = $this->queryForLevel($query, $level);
        $query->where('id_karyawan', '!=', $approver->id_karyawan);

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
        
        if ($user->hasRole('admin')) {
            $userLevel = $approver ? $this->approverLevel($approver) : 1;
            return $this->currentLevel($cuti) === $userLevel;
        }

        if ($user->hasRole('Management')) {
            return $this->currentLevel($cuti) === 4;
        }

        if (!$user->hasRole('atasan_divisi') || !$approver) {
            return false;
        }

        $userLevel = $this->approverLevel($approver);
        if ($userLevel === null) {
            return false;
        }

        if ((int) $approver->id_karyawan === (int) $cuti->id_karyawan) {
            return false;
        }

        if ($userLevel === 2 && (int) $approver->id_divisi !== (int) $cuti->karyawan?->id_divisi) {
            return false;
        }

        return $this->currentLevel($cuti) === $userLevel;
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
        $count = $cuti->persetujuanCuti()->where('status_persetujuan', 'approved')->count();

        $applicant = $cuti->karyawan;
        if (!$applicant) {
            $applicant = Karyawan::with('jabatan')->find($cuti->id_karyawan);
        }

        $start = $applicant ? $this->startingLevel($applicant) : 1;
        return $start + $count;
    }

    public function levelLabel(Cuti $cuti): string
    {
        return self::LEVEL_LABELS[$this->currentLevel($cuti)] ?? 'Unknown';
    }

    public function finalApprovalLevel(Cuti $cuti): int
    {
        $applicant = $cuti->karyawan;
        if (!$applicant) {
            $applicant = Karyawan::with('jabatan')->find($cuti->id_karyawan);
        }

        $start = $applicant ? $this->startingLevel($applicant) : 1;
        return ($start === 4) ? 5 : 4;
    }

    private function approverLevel(Karyawan $approver): ?int
    {
        return match ($approver->jabatan?->nama_jabatan) {
            'SDM'            => 1,
            'Manager Divisi' => 2,
            'Manager Umum'   => 3,
            'Management'     => 4,
            default          => null,
        };
    }
}
