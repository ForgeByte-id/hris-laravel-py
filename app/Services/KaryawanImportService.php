<?php

namespace App\Services;

use App\Models\Divisi;
use App\Models\Jabatan;
use App\Models\Karyawan;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use SimpleXMLElement;
use ZipArchive;

class KaryawanImportService
{
    public function __construct(
        private readonly KaryawanFaceImportService $faceImportService,
        private readonly LeaveQuotaService $leaveQuotaService
    ) {
    }

    /**
     * Import employees from CSV, JSON, or XLSX.
     *
     * Each row runs in its own transaction. Existing employees are skipped so
     * only employees that are truly missing from the database are appended.
     *
     * @return array{
     *     success:int,
     *     updated:int,
     *     skipped:int,
     *     failed:int,
     *     details:array<int, array{row:int,status:string,message:string}>
     * }
     */
    public function importFile(UploadedFile $file): array
    {
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension());

        return match ($extension) {
            'csv', 'txt' => $this->importRows($this->readCsvRows($file)),
            'json' => $this->importRows($this->readJsonRows($file)),
            'xlsx' => $this->importRows($this->readXlsxRows($file)),
            default => throw new RuntimeException('Format file import harus CSV, JSON, atau XLSX.'),
        };
    }

    public function importCsv(UploadedFile $file): array
    {
        return $this->importRows($this->readCsvRows($file));
    }

    /**
     * @param array<int, array<string, string|null>> $rows
     *
     * @return array{success:int,updated:int,skipped:int,failed:int,details:array}
     */
    private function importRows(array $rows): array
    {
        $summary = $this->emptySummary();

        foreach ($rows as $rowNumber => $row) {
            if ($this->isEmptyRow(array_values($row))) {
                $this->addDetail($summary, 'skipped', $rowNumber, 'Baris kosong dilewati.');
                continue;
            }

            try {
                $status = DB::transaction(fn () => $this->importRow($row));
                $this->addDetail($summary, $status, $rowNumber, $this->messageForStatus($status));
            } catch (\Throwable $e) {
                $this->addDetail($summary, 'failed', $rowNumber, $e instanceof RuntimeException ? $e->getMessage() : 'Baris gagal diproses. Periksa format data.');
            }
        }

        return $summary;
    }

    /**
     * @return array<int, array<string, string|null>>
     */
    private function readCsvRows(UploadedFile $file): array
    {
        $handle = fopen($file->getRealPath(), 'r');

        if (!$handle) {
            throw new RuntimeException('File CSV tidak dapat dibaca.');
        }

        try {
            $headers = fgetcsv($handle);

            if (!$headers) {
                throw new RuntimeException('File CSV kosong atau header tidak valid.');
            }

            $headers = array_map(fn ($header) => $this->normalizeHeader((string) $header), $headers);
            $rowNumber = 1;
            $rows = [];

            while (($values = fgetcsv($handle)) !== false) {
                $rowNumber++;
                $rows[$rowNumber] = $this->combineRow($headers, $values);
            }

            return $rows;
        } finally {
            fclose($handle);
        }
    }

    /**
     * @return array<int, array<string, string|null>>
     */
    private function readJsonRows(UploadedFile $file): array
    {
        $decoded = json_decode((string) file_get_contents($file->getRealPath()), true);

        if (!is_array($decoded)) {
            throw new RuntimeException('File JSON tidak valid.');
        }

        $items = $this->isAssoc($decoded) && isset($decoded['data']) && is_array($decoded['data'])
            ? $decoded['data']
            : $decoded;
        $rows = [];
        $rowNumber = 1;

        foreach ($items as $item) {
            $rowNumber++;

            if (!is_array($item)) {
                throw new RuntimeException("Baris JSON {$rowNumber} harus berupa object.");
            }

            $row = [];
            foreach ($item as $key => $value) {
                $row[$this->normalizeHeader((string) $key)] = $value === null ? null : trim((string) $value);
            }

            $rows[$rowNumber] = $row;
        }

        return $rows;
    }

    /**
     * @return array<int, array<string, string|null>>
     */
    private function readXlsxRows(UploadedFile $file): array
    {
        if (!class_exists(ZipArchive::class)) {
            throw new RuntimeException('Import XLSX membutuhkan ekstensi PHP zip. Rebuild container app terlebih dahulu.');
        }

        $zip = new ZipArchive();

        if ($zip->open($file->getRealPath()) !== true) {
            throw new RuntimeException('File XLSX tidak dapat dibaca.');
        }

        try {
            $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');

            if ($sheetXml === false) {
                throw new RuntimeException('Sheet pertama XLSX tidak ditemukan.');
            }

            $sheet = simplexml_load_string($sheetXml);

            if (!$sheet) {
                throw new RuntimeException('Sheet XLSX tidak valid.');
            }

            $sharedStrings = $this->xlsxSharedStrings($zip);
            $rawRows = [];

            foreach ($sheet->sheetData->row as $row) {
                $rowIndex = (int) $row['r'];
                $values = [];

                foreach ($row->c as $cell) {
                    $cellRef = (string) $cell['r'];
                    $column = preg_replace('/\d+/', '', $cellRef) ?: 'A';
                    $values[$this->xlsxColumnIndex($column)] = $this->xlsxCellValue($cell, $sharedStrings);
                }

                if ($values !== []) {
                    ksort($values);
                    $rawRows[$rowIndex] = $values;
                }
            }

            if ($rawRows === []) {
                throw new RuntimeException('File XLSX kosong.');
            }

            $headers = array_map(fn ($header) => $this->normalizeHeader((string) $header), array_shift($rawRows));
            $rows = [];

            foreach ($rawRows as $rowIndex => $values) {
                $rows[$rowIndex] = $this->combineRow($headers, $values);
            }

            return $rows;
        } finally {
            $zip->close();
        }
    }

    /**
     * @param array<string, string|null> $row
     */
    private function importRow(array $row): string
    {
        $nama = trim((string) ($row['nama'] ?? ''));
        $username = trim((string) ($row['username'] ?? ''));
        $email = trim((string) ($row['email'] ?? ''));

        if ($nama === '') {
            throw new RuntimeException('Nama wajib diisi.');
        }

        if ($username === '') {
            $username = $this->usernameFromName($nama);
        }

        if ($this->hasExistingEmployee($username, $email, $nama)) {
            return 'skipped';
        }

        $user = $this->resolveUser($username, $email);

        if (!$user) {
            $password = trim((string) ($row['password'] ?? '')) ?: (string) config('hris.default_import_password');

            if ($password === '') {
                throw new RuntimeException('Password kosong. Isi kolom password atau set HRIS_DEFAULT_IMPORT_PASSWORD di env.');
            }

            $user = User::create([
                'username' => $username,
                'email' => $email ?: null,
                'password' => $password,
                'role' => 'karyawan',
            ]);
        }

        if (!$user->hasRole('karyawan')) {
            $user->assignRole('karyawan');
        }

        $divisi = $this->resolveDivisi($row);
        $jabatan = $this->resolveJabatan($row);
        $statusAktif = $this->statusOrDefault($row['status_aktif'] ?? null, ['Aktif', 'Nonaktif'], 'Aktif', 'status aktif');
        $statusKaryawan = $this->statusOrDefault($row['status_karyawan'] ?? null, ['Tetap', 'Kontrak', 'Training'], 'Tetap', 'status karyawan');
        $tanggalMasuk = $this->normalizeDateValue($row['tanggal_masuk'] ?? null)
            ?: $this->normalizeDateValue($row['tanggal_mulai_kerja'] ?? null);

        $karyawan = new Karyawan(['id_user' => $user->id_user]);
        $karyawan->fill([
            'nama' => $nama,
            'id_divisi' => $divisi?->id,
            'id_jabatan' => $jabatan?->id,
            'tanggal_masuk' => $tanggalMasuk,
            'status_aktif' => $statusAktif,
            'status_karyawan' => $statusKaryawan,
        ]);

        $faceImagePath = $this->nullableValue($row['face_image_path'] ?? null);

        if ($faceImagePath) {
            $karyawan->face_embedding = json_encode($this->faceImportService->encodeImportPath($faceImagePath));
        }

        $karyawan->save();

        if ($faceImagePath) {
            $karyawan->face_image_path = $this->faceImportService->storeImportPreview($faceImagePath, $karyawan->id_karyawan);
            $karyawan->save();
        }

        $this->leaveQuotaService->ensureBalancesFor($karyawan);

        return 'success';
    }

    /**
     * @param array<string, string|null> $row
     */
    private function resolveDivisi(array $row): ?Divisi
    {
        if ($this->nullableValue($row['id_divisi'] ?? null)) {
            $divisi = Divisi::find((int) $row['id_divisi']);

            if (!$divisi) {
                throw new RuntimeException("Divisi dengan ID {$row['id_divisi']} tidak ditemukan.");
            }

            return $divisi;
        }

        $namaDevisi = $this->nullableValue($row['nama_divisi'] ?? null);

        return $namaDevisi ? Divisi::firstOrCreate(['nama_divisi' => $namaDevisi]) : null;
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

    private function hasExistingEmployee(string $username, string $email, string $nama): bool
    {
        return Karyawan::query()
            ->where('nama', $nama)
            ->orWhereHas('user', function ($query) use ($username, $email) {
                $query->where('username', $username);

                if ($email !== '') {
                    $query->orWhere('email', $email);
                }
            })
            ->exists();
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

    /**
     * @param array<string, mixed> $value
     */
    private function isAssoc(array $value): bool
    {
        return array_keys($value) !== range(0, count($value) - 1);
    }

    /**
     * @return array<int, string>
     */
    private function xlsxSharedStrings(ZipArchive $zip): array
    {
        $xml = $zip->getFromName('xl/sharedStrings.xml');

        if ($xml === false) {
            return [];
        }

        $strings = simplexml_load_string($xml);

        if (!$strings) {
            return [];
        }

        $result = [];
        foreach ($strings->si as $item) {
            if (isset($item->t)) {
                $result[] = (string) $item->t;
                continue;
            }

            $text = '';
            foreach ($item->r as $run) {
                $text .= (string) $run->t;
            }
            $result[] = $text;
        }

        return $result;
    }

    /**
     * @param array<int, string> $sharedStrings
     */
    private function xlsxCellValue(SimpleXMLElement $cell, array $sharedStrings): ?string
    {
        $type = (string) $cell['t'];

        if ($type === 's') {
            $index = (int) $cell->v;
            return $sharedStrings[$index] ?? '';
        }

        if ($type === 'inlineStr') {
            return isset($cell->is->t) ? (string) $cell->is->t : null;
        }

        return isset($cell->v) ? (string) $cell->v : null;
    }

    private function xlsxColumnIndex(string $letters): int
    {
        $letters = strtoupper($letters);
        $index = 0;

        for ($i = 0, $length = strlen($letters); $i < $length; $i++) {
            $index = ($index * 26) + (ord($letters[$i]) - 64);
        }

        return $index - 1;
    }

    private function normalizeHeader(string $header): string
    {
        $header = strtolower(trim(str_replace("\xEF\xBB\xBF", '', $header)));

        return match ($header) {
            'nama lengkap' => 'nama',
            'divisi' => 'nama_divisi',
            'posisi' => 'nama_jabatan',
            'mulai kerja' => 'tanggal_masuk',
            'aktif' => 'status_aktif',
            'status' => 'status_karyawan',
            'id_divisi/nama_divisi' => 'nama_divisi',
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

    private function normalizeDateValue(?string $value): ?string
    {
        $value = $this->nullableValue($value);

        if ($value === null) {
            return null;
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return $value;
        }

        if (preg_match('/^(\d{1,2})-([A-Za-z]+)-(\d{2,4})$/', $value, $matches)) {
            $month = $this->monthNumber($matches[2]);
            $year = (int) $matches[3];

            if ($year < 100) {
                $year += $year >= 70 ? 1900 : 2000;
            }

            return sprintf('%04d-%02d-%02d', $year, $month, (int) $matches[1]);
        }

        if (is_numeric($value) && (int) $value > 25000) {
            return \Carbon\Carbon::create(1899, 12, 30)->addDays((int) $value)->toDateString();
        }

        try {
            return \Carbon\Carbon::parse($value)->toDateString();
        } catch (\Throwable) {
            throw new RuntimeException("Format tanggal {$value} tidak valid. Gunakan YYYY-MM-DD atau format dataset seperti 7-Jul-14.");
        }
    }

    private function monthNumber(string $month): int
    {
        $key = strtolower($month);
        $months = [
            'jan' => 1,
            'feb' => 2,
            'mar' => 3,
            'apr' => 4,
            'mei' => 5,
            'may' => 5,
            'jun' => 6,
            'jul' => 7,
            'agu' => 8,
            'aug' => 8,
            'sep' => 9,
            'okt' => 10,
            'oct' => 10,
            'nov' => 11,
            'des' => 12,
            'dec' => 12,
        ];

        if (!isset($months[$key])) {
            throw new RuntimeException("Bulan {$month} pada tanggal import tidak dikenali.");
        }

        return $months[$key];
    }

    private function usernameFromName(string $name): string
    {
        return Str::of($name)
            ->lower()
            ->ascii()
            ->replaceMatches('/[^a-z0-9]+/', '.')
            ->trim('.')
            ->value();
    }

    /**
     * @param array<int, string> $allowed
     */
    private function statusOrDefault(?string $value, array $allowed, string $default, string $label): string
    {
        $value = $this->nullableValue($value) ?? $default;

        foreach ($allowed as $allowedValue) {
            if (strcasecmp($value, $allowedValue) === 0) {
                return $allowedValue;
            }
        }

        throw new RuntimeException(ucfirst($label) . ' tidak valid. Gunakan: ' . implode(', ', $allowed) . '.');
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

    private function messageForStatus(string $status): string
    {
        return match ($status) {
            'success' => 'Karyawan baru berhasil dibuat.',
            'updated' => 'Data karyawan berhasil diperbarui.',
            'skipped' => 'Data karyawan sudah ada di database, dilewati.',
            default => 'Baris selesai diproses.',
        };
    }
}
