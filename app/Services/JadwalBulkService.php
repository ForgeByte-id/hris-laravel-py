<?php

namespace App\Services;

use App\Models\JadwalKerja;
use App\Models\Karyawan;
use App\Models\Shift;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class JadwalBulkService
{
    /**
     * Create or update schedules for a date range and employee target.
     *
     * @param array{
     *     tanggal_mulai:string,
     *     tanggal_selesai:string,
     *     target_type:string,
     *     id_divisi?:int|string|null,
     *     karyawan_ids?:array<int, int|string>,
     *     id_shift:string,
     *     overwrite?:bool,
     * } $payload
     *
     * @return array{created:int,updated:int,skipped:int,failed:int,details:array<int, array{status:string,message:string}>}
     */
    public function storeRange(array $payload): array
    {
        $summary = [
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'failed' => 0,
            'details' => [],
        ];

        $shift = Shift::where('kode_shift', $payload['id_shift'])->firstOrFail();
        $employees = $this->resolveEmployees($payload);

        if ($employees->isEmpty()) {
            $summary['failed']++;
            $summary['details'][] = [
                'status' => 'failed',
                'message' => 'Tidak ada karyawan sesuai target yang dipilih.',
            ];

            return $summary;
        }

        $start = Carbon::parse($payload['tanggal_mulai'])->startOfDay();
        $end = Carbon::parse($payload['tanggal_selesai'])->startOfDay();
        $overwrite = (bool) ($payload['overwrite'] ?? false);

        DB::transaction(function () use ($employees, $start, $end, $payload, &$summary) {
            foreach ($employees as $employee) {
                for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
                    $existing = JadwalKerja::where('id_karyawan', $employee->id_karyawan)
                        ->whereDate('tanggal', $date->toDateString())
                        ->first();

                    if ($existing && !$payload['overwrite']) {
                        $summary['skipped']++;
                        continue;
                    }

                    $data = [
                        'id_karyawan' => $employee->id_karyawan,
                        'tanggal' => $date->toDateString(),
                        'id_shift' => $payload['id_shift'],
                    ];

                    if ($existing) {
                        $existing->update($data);
                        $summary['updated']++;
                    } else {
                        JadwalKerja::create($data);
                        $summary['created']++;
                    }
                }
            }
        });

        return $summary;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function resolveEmployees(array $payload)
    {
        $query = Karyawan::query()->orderBy('nama');

        if ($payload['target_type'] === 'divisi') {
            $query->where('id_divisi', $payload['id_divisi']);
        }

        if ($payload['target_type'] === 'karyawan') {
            $query->whereIn('id_karyawan', $payload['karyawan_ids'] ?? []);
        }

        return $query->get();
    }
}
