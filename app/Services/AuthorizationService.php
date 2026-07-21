<?php

namespace App\Services;

use App\Models\Karyawan;
use App\Models\User;

/**
 * Authorization Service
 *
 * Centralized authorization logic for role-based access control
 * Enforces least privilege principle across the application
 */
class AuthorizationService
{
    /**
     * Mapping jabatan (position) name → Spatie role name.
     */
    public const JABATAN_ROLE_MAP = [
        'SDM' => 'admin',
        'Manager Umum' => 'atasan_divisi',
        'Manager Divisi' => 'atasan_divisi',
    ];

    /**
     * Get the Spatie role that should be assigned for a given jabatan name.
     */
    public function roleForJabatan(string $namaJabatan): string
    {
        return self::JABATAN_ROLE_MAP[$namaJabatan] ?? 'karyawan';
    }

    /**
     * Check if user can perform ANY attendance action (create, update, view, check-in).
     * Centralised rule: attendance management is exclusively for admins.
     *
     * @param User $user
     * @return bool
     */
    public function canManageAttendance(User $user): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Check if user can view attendance summary
     * Only admin can view global summary
     *
     * @param User $user
     * @return bool
     */
    public function canViewAttendanceSummary(User $user): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Check if user can view attendance record of another employee
     *
     * @param User $user
     * @param int $targetIdKaryawan
     * @return bool
     */
    public function canViewAttendanceRecord(User $user, int $targetIdKaryawan): bool
    {
        // Admin can view all
        if ($user->hasRole('admin')) {
            return true;
        }

        // HR can view all
        if ($user->hasRole('hr')) {
            return true;
        }

        // Regular employee can only view their own
        $ownKaryawan = Karyawan::where('id_user', $user->id_user)->first();
        if ($ownKaryawan && $ownKaryawan->id_karyawan === $targetIdKaryawan) {
            return true;
        }

        return false;
    }

    /**
     * Check if user can view full attendance history table
     * Admin & HR can view all, employees see only their own
     *
     * @param User $user
     * @return bool
     */
    public function canViewFullAttendanceHistory(User $user): bool
    {
        return $user->hasRole('admin') || $user->hasRole('hr');
    }

    /**
     * Check if user is atasan (division head / manager) for approval purposes.
     */
    public function isAtasan(User $user): bool
    {
        return $user->hasRole('atasan_divisi');
    }

    /**
     * Check if user can view attendance chart
     * Only admin can view
     *
     * @param User $user
     * @return bool
     */
    public function canViewAttendanceChart(User $user): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Get authorized attendance scope based on user role
     * Returns array of id_karyawan that user is allowed to view
     *
     * @param User $user
     * @return array
     */
    public function getAuthorizedAttendanceScope(User $user): array
    {
        // Admin can view all
        if ($user->hasRole('admin')) {
            return Karyawan::pluck('id_karyawan')->toArray();
        }

        // HR can view all
        if ($user->hasRole('hr')) {
            return Karyawan::pluck('id_karyawan')->toArray();
        }

        // Regular employee can only view their own
        $ownKaryawan = Karyawan::where('id_user', $user->id_user)->first();
        if ($ownKaryawan) {
            return [$ownKaryawan->id_karyawan];
        }

        return [];
    }

    /**
     * Get user's own karyawan ID
     *
     * @param User $user
     * @return int|null
     */
    public function getOwnKaryawanId(User $user): ?int
    {
        return Karyawan::where('id_user', $user->id_user)->value('id_karyawan');
    }

    /**
     * Check if user is admin
     *
     * @param User $user
     * @return bool
     */
    public function isAdmin(User $user): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Check if user is HR
     *
     * @param User $user
     * @return bool
     */
    public function isHr(User $user): bool
    {
        return $user->hasRole('hr');
    }

    /**
     * Check if user is regular employee (not admin/hr)
     *
     * @param User $user
     * @return bool
     */
    public function isRegularEmployee(User $user): bool
    {
        return !$this->isAdmin($user) && !$this->isHr($user);
    }

    public const JADWAL_MANAGE_FULL_ACCESS_JABATAN = ['Manager Umum', 'SDM'];
    public const JADWAL_MANAGE_DIVISI_SCOPED_JABATAN = ['Manager Divisi'];

    /**
     * Resolve whether the user may create/edit jadwal, and for which divisi.
     *
     * @return array{allowed: bool, id_divisi: int|null} id_divisi is null for full access.
     */
    public function getJadwalManageScope(User $user): array
    {
        if ($user->hasRole('admin')) {
            return ['allowed' => true, 'id_divisi' => null];
        }

        $karyawan = Karyawan::with('jabatan')->where('id_user', $user->id_user)->first();
        $nama = $karyawan?->jabatan?->nama_jabatan;

        if (in_array($nama, self::JADWAL_MANAGE_FULL_ACCESS_JABATAN, true)) {
            return ['allowed' => true, 'id_divisi' => null];
        }

        if (in_array($nama, self::JADWAL_MANAGE_DIVISI_SCOPED_JABATAN, true)) {
            return ['allowed' => true, 'id_divisi' => $karyawan->id_divisi];
        }

        return ['allowed' => false, 'id_divisi' => null];
    }

    /**
     * Resolve whether the user may view the company-wide laporan (report), and for which divisi.
     * Same tiering as jadwal management: Manager Umum/SDM get full access, Manager Divisi is
     * scoped to their own divisi, everyone else is denied entirely.
     *
     * @return array{allowed: bool, id_divisi: int|null}
     */
    public function getLaporanViewScope(User $user): array
    {
        return $this->getJadwalManageScope($user);
    }

    public const JADWAL_VIEW_FULL_ACCESS_JABATAN = ['Manager Umum', 'Wakil Manager Umum', 'SDM'];
    public const JADWAL_VIEW_DIVISI_SCOPED_JABATAN = ['Manager Divisi', 'Wakil Manager Divisi'];

    /**
     * Resolve whether the user may view the team jadwal calendar, and for which divisi.
     * allowed=false means the caller should fall back to viewing only their own schedule.
     *
     * @return array{allowed: bool, id_divisi: int|null} id_divisi is null for full access.
     */
    public function getJadwalViewScope(User $user): array
    {
        if ($user->hasRole('admin')) {
            return ['allowed' => true, 'id_divisi' => null];
        }

        $karyawan = Karyawan::with('jabatan')->where('id_user', $user->id_user)->first();
        $nama = $karyawan?->jabatan?->nama_jabatan;

        if (in_array($nama, self::JADWAL_VIEW_FULL_ACCESS_JABATAN, true)) {
            return ['allowed' => true, 'id_divisi' => null];
        }

        if (in_array($nama, self::JADWAL_VIEW_DIVISI_SCOPED_JABATAN, true)) {
            return ['allowed' => true, 'id_divisi' => $karyawan->id_divisi];
        }

        return ['allowed' => false, 'id_divisi' => null];
    }

}
