<?php

namespace App\Services;

use App\Models\Cuti;
use App\Models\Karyawan;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class CutiApprovalService
{
    /**
     * Determine whether a user may open the leave approval page.
     */
    public function canViewApproval(User $user): bool
    {
        return $user->hasRole('admin')
            || $user->hasRole('atasan_divisi')
            || $user->hasAnyRole(['hr', 'hrd']);
    }

    /**
     * Build the pending leave query scoped to the user's approval line.
     *
     * Admin and HR/HRD may read all pending requests. atasan_divisi may only
     * read pending requests from employees in the same division and never their
     * own leave request.
     *
     * @return Builder<Cuti>
     */
    public function pendingQueryFor(User $user): Builder
    {
        $query = Cuti::with(['karyawan.devisi', 'karyawan.jabatan', 'atasan'])
            ->where('status_persetujuan', 'pending')
            ->orderBy('created_at', 'desc');

        if ($user->hasRole('admin') || $user->hasAnyRole(['hr', 'hrd'])) {
            return $query;
        }

        $approver = $this->employeeFor($user);

        if (!$user->hasRole('atasan_divisi') || !$approver?->id_devisi) {
            return $query->whereRaw('1 = 0');
        }

        return $query
            ->where('id_karyawan', '!=', $approver->id_karyawan)
            ->whereHas('karyawan', fn ($employeeQuery) => $employeeQuery->where('id_devisi', $approver->id_devisi));
    }

    /**
     * Backend approval guard for approve/reject actions.
     */
    public function canUpdateStatus(User $user, Cuti $cuti): bool
    {
        if ($cuti->status_persetujuan !== 'pending') {
            return false;
        }

        if ($user->hasRole('admin')) {
            return true;
        }

        if (!$user->hasRole('atasan_divisi') || $user->hasAnyRole(['hr', 'hrd'])) {
            return false;
        }

        $approver = $this->employeeFor($user);
        $employee = $cuti->karyawan;

        return $approver
            && $employee
            && $approver->id_devisi
            && (int) $approver->id_devisi === (int) $employee->id_devisi
            && (int) $approver->id_karyawan !== (int) $employee->id_karyawan;
    }

    /**
     * Find the division head responsible for a leave request submitter.
     */
    public function findDivisionHeadFor(Karyawan $employee): ?Karyawan
    {
        if (!$employee->id_devisi) {
            return null;
        }

        return Karyawan::with('user')
            ->where('id_devisi', $employee->id_devisi)
            ->where('id_karyawan', '!=', $employee->id_karyawan)
            ->whereHas('user.roles', fn ($query) => $query->where('name', 'atasan_divisi'))
            ->orderBy('nama')
            ->first();
    }

    public function employeeFor(User $user): ?Karyawan
    {
        return Karyawan::with(['devisi', 'jabatan'])->where('id_user', $user->id_user)->first();
    }
}
