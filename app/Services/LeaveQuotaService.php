<?php

namespace App\Services;

use App\Models\Cuti;
use App\Models\Karyawan;
use App\Models\KuotaCutiKaryawan;
use App\Models\TipeCuti;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use RuntimeException;

class LeaveQuotaService
{
    /**
     * Return active leave types applicable to an employee status.
     *
     * @return Collection<int, TipeCuti>
     */
    public function applicableTypesFor(Karyawan $karyawan): Collection
    {
        return TipeCuti::query()
            ->where('is_active', true)
            ->where(function ($query) use ($karyawan) {
                $query->whereNull('berlaku_untuk_status')
                    ->orWhere('berlaku_untuk_status', $karyawan->status_karyawan);
            })
            ->orderBy('nama_cuti')
            ->get();
    }

    /**
     * Ensure all configured yearly leave balances exist for an employee.
     *
     * Existing balances are adjusted by quota delta instead of reset, so used
     * leave days are not lost when seeders are rerun.
     *
     * @return Collection<int, KuotaCutiKaryawan>
     */
    public function ensureBalancesFor(Karyawan $karyawan, ?int $year = null): Collection
    {
        $year ??= (int) now()->year;

        foreach ($this->applicableTypesFor($karyawan) as $leaveType) {
            $this->ensureBalanceFor($karyawan, $leaveType, $year);
        }

        return KuotaCutiKaryawan::with('leaveType')
            ->where('id_karyawan', $karyawan->id_karyawan)
            ->where('year', $year)
            ->get();
    }

    public function balanceFor(Karyawan $karyawan, string $jenisCuti, ?int $year = null): KuotaCutiKaryawan
    {
        $year ??= (int) now()->year;
        $leaveType = $this->resolveTipeCuti($jenisCuti);

        if (!$this->isApplicable($leaveType, $karyawan)) {
            throw new RuntimeException("Jenis cuti {$leaveType->nama_cuti} tidak berlaku untuk status karyawan {$karyawan->status_karyawan}.");
        }

        return $this->ensureBalanceFor($karyawan, $leaveType, $year);
    }

    /**
     * Validate quota before a leave request is stored or approved.
     */
    public function assertAvailable(Karyawan $karyawan, string $jenisCuti, int $days, ?int $year = null): void
    {
        $balance = $this->balanceFor($karyawan, $jenisCuti, $year);

        if ($balance->remaining_quota < $days) {
            throw new RuntimeException("Sisa kuota {$balance->leaveType->nama_cuti} tidak mencukupi. Tersisa {$balance->remaining_quota} hari.");
        }
    }

    /**
     * Decrease leave quota for an approved leave request.
     */
    public function decrementForApprovedLeave(Cuti $cuti): void
    {
        $karyawan = $cuti->karyawan;

        if (!$karyawan) {
            throw new RuntimeException('Data karyawan pengajuan cuti tidak ditemukan.');
        }

        $days = $cuti->tanggal_mulai->diffInDays($cuti->tanggal_selesai) + 1;
        $year = (int) Carbon::parse($cuti->tanggal_mulai)->year;
        $balance = $this->balanceFor($karyawan, $cuti->jenis_cuti, $year);

        if ($balance->remaining_quota < $days) {
            throw new RuntimeException("Sisa kuota {$balance->leaveType->nama_cuti} tidak mencukupi. Tersisa {$balance->remaining_quota} hari.");
        }

        $balance->decrement('remaining_quota', $days);
    }

    public function resolveTipeCuti(string $jenisCuti): TipeCuti
    {
        $needle = $this->normalizeLeaveName($jenisCuti);

        $leaveType = TipeCuti::where('is_active', true)->get()
            ->first(fn (TipeCuti $type) => $this->normalizeLeaveName($type->nama_cuti) === $needle);

        if (!$leaveType) {
            throw new RuntimeException('Jenis cuti tidak valid atau sedang tidak aktif.');
        }

        return $leaveType;
    }

    private function ensureBalanceFor(Karyawan $karyawan, TipeCuti $leaveType, int $year): KuotaCutiKaryawan
    {
        $quota = $this->defaultQuotaFor($karyawan, $leaveType);
        $balance = KuotaCutiKaryawan::where('id_karyawan', $karyawan->id_karyawan)
            ->where('leave_type_id', $leaveType->id)
            ->where('year', $year)
            ->lockForUpdate()
            ->first();

        if (!$balance) {
            return KuotaCutiKaryawan::create([
                'id_karyawan' => $karyawan->id_karyawan,
                'leave_type_id' => $leaveType->id,
                'year' => $year,
                'quota' => $quota,
                'remaining_quota' => $quota,
            ]);
        }

        if ($balance->quota !== $quota) {
            $delta = $quota - $balance->quota;
            $balance->quota = $quota;
            $balance->remaining_quota = max(0, min($quota, $balance->remaining_quota + $delta));
            $balance->save();
        }

        return $balance->load('leaveType');
    }

    private function defaultQuotaFor(Karyawan $karyawan, TipeCuti $leaveType): int
    {
        if ($this->isAnnualLeave($leaveType)) {
            return $karyawan->status_karyawan === 'Tetap'
                ? (int) $leaveType->kuota_cuti
                : 0;
        }

        return (int) $leaveType->kuota_cuti;
    }

    private function isApplicable(TipeCuti $leaveType, Karyawan $karyawan): bool
    {
        return $leaveType->berlaku_untuk_status === null
            || $leaveType->berlaku_untuk_status === $karyawan->status_karyawan;
    }

    private function isAnnualLeave(TipeCuti $leaveType): bool
    {
        return $this->normalizeLeaveName($leaveType->nama_cuti) === 'tahunan';
    }

    private function normalizeLeaveName(string $name): string
    {
        $name = strtolower(trim($name));
        $name = preg_replace('/^cuti\s+/i', '', $name) ?? $name;

        return preg_replace('/\s+/', ' ', $name) ?? $name;
    }
}
