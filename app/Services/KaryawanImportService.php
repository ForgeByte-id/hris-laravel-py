<?php

namespace App\Services;

use App\Models\Devisi;
use App\Models\Jabatan;
use App\Models\Karyawan;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class KaryawanImportService
{
    public function __construct(private readonly KaryawanFaceImportService $faceImportService)
    {
    }

    /**
     * Import employees from one CSV file.
     *
     * Each row runs in its own transaction so one invalid employee does not
     * rollback successful rows. The returned summary is safe to display to users.
     *
     * @return array{
     *     success:int,
     *     updated:int,
     *     skipped:int,
     *     failed:int,
     *     details:array<int, array{row:int,status:string,message:string}>
     * }
     */
    public function importCsv(UploadedFile $file): array
    {
        $handle = fopen($file->getRealPath(), 'r');

        if (!$handle) {
            throw new RuntimeException('File CSV tidak dapat dibaca.');
        }

        $summary = $this->emptySummary();

        try {
            $headers = fgetcsv($handle);

            if (!$headers) {
                throw new RuntimeException('File CSV kosong atau header tidak valid.');
            }

            $headers = array_map(fn ($header) => $this->normalizeHeader((string) $header), $headers);
            $rowNumber = 1;

            while (($values = fgetcsv($handle)) !== false) {
                $rowNumber++;

                if ($this->isEmptyRow($values)) {
                    $this->addDetail($summary, 'skipped', $rowNumber, 'Baris kosong dilewati.');
                    continue;
                }

                $row = $this->combineRow($headers, $values);

                try {
                    $status = DB::transaction(fn () => $this->importRow($row));
                    $this->addDetail($summary, $status, $rowNumber, $status === 'success' ? 'Karyawan baru berhasil dibuat.' : 'Data karyawan berhasil diperbarui.');
                } catch (\Throwable $e) {
                    $this->addDetail($summary, 'failed', $rowNumber, $e instanceof RuntimeException ? $e->getMessage() : 'Baris gagal diproses. Periksa format data.');
                }
            }
        } finally {
            fclose($handle);
        }

        return $summary;
    }

    /**
     * @param array<string, string|null> $row
     */
    private function importRow(array $row): string
    {
        $nama = trim((string) ($row['nama'] ?? ''));
        $username = trim((string) ($row['username'] ?? ''));
        $email = trim((string) ($row['email'] ?? ''));
        $kodeShift = strtoupper(trim((string) ($row['kode_shift'] ?? '')));

        if ($nama === '') {
            throw new RuntimeException('Nama wajib diisi.');
        }

        if ($username === '' && $email === '') {
            throw new RuntimeException('Username atau email wajib diisi.');
        }

        if ($kodeShift === '') {
            throw new RuntimeException('Kode shift wajib diisi.');
        }

        $shift = Shift::where('kode_shift', $kodeShift)->first();
        if (!$shift) {
            throw new RuntimeException("Kode shift {$kodeShift} tidak ditemukan.");
        }

        $user = $this->resolveUser($username, $email);
        $isNewEmployee = false;

        if (!$user) {
            $password = trim((string) ($row['password'] ?? '')) ?: (string) config('hris.default_import_password');

            if ($password === '') {
                throw new RuntimeException('Password kosong. Isi kolom password atau set HRIS_DEFAULT_IMPORT_PASSWORD di env.');
            }

            $user = User::create([
                'username' => $username ?: $email,
                'email' => $email ?: null,
                'password' => $password,
                'role' => 'karyawan',
            ]);
            $isNewEmployee = true;
        } else {
            $updates = [];

            if ($username !== '' && $user->username !== $username) {
                $updates['username'] = $username;
            }

            if ($email !== '' && $user->email !== $email) {
                $updates['email'] = $email;
            }

            if (trim((string) ($row['password'] ?? '')) !== '') {
                $updates['password'] = trim((string) $row['password']);
            }

            if (!empty($updates)) {
                $user->update($updates);
            }
        }

        if (!$user->hasRole('karyawan')) {
            $user->assignRole('karyawan');
        }

        $divisi = $this->resolveDivisi($row);
        $jabatan = $this->resolveJabatan($row);
        $yearlyLeaveQuota = $this->integerOrDefault($row['yearly_leave_quota'] ?? null, 12);
        $remainingLeaveQuota = $this->integerOrDefault($row['remaining_leave_quota'] ?? null, $yearlyLeaveQuota);

        $karyawan = Karyawan::where('id_user', $user->id_user)->first();

        if (!$karyawan) {
            $karyawan = new Karyawan(['id_user' => $user->id_user]);
            $isNewEmployee = true;
        }

        $karyawan->fill([
            'nama' => $nama,
            'id_devisi' => $divisi?->id,
            'id_jabatan' => $jabatan?->id,
            'kode_shift' => $shift->kode_shift,
            'tanggal_masuk' => $this->nullableValue($row['tanggal_masuk'] ?? null),
            'yearly_leave_quota' => $yearlyLeaveQuota,
            'remaining_leave_quota' => min($remainingLeaveQuota, $yearlyLeaveQuota),
        ]);

        if ($this->nullableValue($row['face_image_path'] ?? null)) {
            $karyawan->face_embedding = json_encode($this->faceImportService->encodeImportPath((string) $row['face_image_path']));
        }

        $karyawan->save();

        return $isNewEmployee ? 'success' : 'updated';
    }

    /**
     * @param array<string, string|null> $row
     */
    private function resolveDivisi(array $row): ?Devisi
    {
        if ($this->nullableValue($row['id_devisi'] ?? null)) {
            $divisi = Devisi::find((int) $row['id_devisi']);

            if (!$divisi) {
                throw new RuntimeException("Divisi dengan ID {$row['id_devisi']} tidak ditemukan.");
            }

            return $divisi;
        }

        $namaDevisi = $this->nullableValue($row['nama_devisi'] ?? null);

        return $namaDevisi ? Devisi::firstOrCreate(['nama_devisi' => $namaDevisi]) : null;
    }

    /**
     * @param array<string, string|null> $row
     */
    private function resolveJabatan(array $row): ?Jabatan
    {
        if ($this->nullableValue($row['id_jabatan'] ?? null)) {
            $jabatan = Jabatan::find((int) $row['id_jabatan']);

            if (!$jabatan) {
                throw new RuntimeException("Jabatan dengan ID {$row['id_jabatan']} tidak ditemukan.");
            }

            return $jabatan;
        }

        $namaJabatan = $this->nullableValue($row['nama_jabatan'] ?? null);

        return $namaJabatan ? Jabatan::firstOrCreate(['nama_jabatan' => $namaJabatan]) : null;
    }

    private function resolveUser(string $username, string $email): ?User
    {
        $byUsername = $username !== '' ? User::where('username', $username)->first() : null;
        $byEmail = $email !== '' ? User::where('email', $email)->first() : null;

        if ($byUsername && $byEmail && $byUsername->id_user !== $byEmail->id_user) {
            throw new RuntimeException('Username dan email sudah dipakai oleh user berbeda.');
        }

        return $byUsername ?: $byEmail;
    }

    /**
     * @param array<int, string> $headers
     * @param array<int, string|null> $values
     *
     * @return array<string, string|null>
     */
    private function combineRow(array $headers, array $values): array
    {
        $row = [];

        foreach ($headers as $index => $header) {
            if ($header === '') {
                continue;
            }

            $row[$header] = isset($values[$index]) ? trim((string) $values[$index]) : null;
        }

        return $row;
    }

    private function normalizeHeader(string $header): string
    {
        $header = strtolower(trim($header));

        return match ($header) {
            'id_devisi/nama_devisi' => 'nama_devisi',
            'id_jabatan/nama_jabatan' => 'nama_jabatan',
            default => $header,
        };
    }

    /**
     * @param array<int, string|null> $values
     */
    private function isEmptyRow(array $values): bool
    {
        return collect($values)->filter(fn ($value) => trim((string) $value) !== '')->isEmpty();
    }

    private function nullableValue(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function integerOrDefault(?string $value, int $default): int
    {
        if ($this->nullableValue($value) === null) {
            return $default;
        }

        return max(0, (int) $value);
    }

    /**
     * @return array{success:int,updated:int,skipped:int,failed:int,details:array}
     */
    private function emptySummary(): array
    {
        return [
            'success' => 0,
            'updated' => 0,
            'skipped' => 0,
            'failed' => 0,
            'details' => [],
        ];
    }

    /**
     * @param array{success:int,updated:int,skipped:int,failed:int,details:array} $summary
     */
    private function addDetail(array &$summary, string $status, int $row, string $message): void
    {
        $summary[$status]++;
        $summary['details'][] = [
            'row' => $row,
            'status' => $status,
            'message' => $message,
        ];
    }
}
