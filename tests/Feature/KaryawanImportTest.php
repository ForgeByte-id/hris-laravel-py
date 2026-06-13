<?php

namespace Tests\Feature;

use App\Models\Karyawan;
use App\Models\LeaveType;
use App\Models\Shift;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use ZipArchive;

class KaryawanImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_import_saves_work_status_and_leave_balances(): void
    {
        $admin = $this->prepareImportMasterData('admin_import');

        Storage::fake('local');
        $csv = implode("\n", [
            'nama,username,email,password,nama_devisi,nama_jabatan,kode_shift,tanggal_masuk,tanggal_mulai_kerja,status_aktif,status_karyawan,yearly_leave_quota,remaining_leave_quota,face_image_path',
            'Import Test,import.test,import.test@hris.local,,Import Divisi,Import Jabatan,P,2026-06-01,2026-06-02,Aktif,Kontrak,0,0,',
        ]);
        $file = UploadedFile::fake()->createWithContent('karyawan.csv', $csv);

        $response = $this->actingAs($admin)->post(route('karyawan.import.store'), [
            'import_file' => $file,
        ]);

        $response->assertRedirect(route('karyawan.import'));
        $this->assertDatabaseHas('karyawan', [
            'nama' => 'Import Test',
            'status_aktif' => 'Aktif',
            'status_karyawan' => 'Kontrak',
            'yearly_leave_quota' => 0,
            'remaining_leave_quota' => 0,
        ]);

        $karyawan = Karyawan::where('nama', 'Import Test')->firstOrFail();
        $this->assertSame('2026-06-02', $karyawan->tanggal_mulai_kerja->toDateString());
        $this->assertDatabaseHas('karyawan_leave_quotas', [
            'id_karyawan' => $karyawan->id_karyawan,
            'quota' => 4,
            'remaining_quota' => 4,
        ]);
        $this->assertDatabaseHas('karyawan_leave_quotas', [
            'id_karyawan' => $karyawan->id_karyawan,
            'quota' => 6,
            'remaining_quota' => 6,
        ]);
    }

    public function test_employee_import_supports_real_dataset_headers_and_dates(): void
    {
        $admin = $this->prepareImportMasterData('admin_dataset');
        $csv = implode("\n", [
            'Nama Lengkap,Divisi,Posisi,Mulai Kerja,Aktif,Status',
            'I Gede Juli Suparwata,NBCS,Manager Divisi,16-Agu-2016,Aktif,Tetap',
            'Citra BR Sinuraya,NBCS,Kasir,23-Mar-26,Aktif,Training',
        ]);
        $file = UploadedFile::fake()->createWithContent('data-karyawan.csv', $csv);

        $response = $this->actingAs($admin)->post(route('karyawan.import.store'), [
            'import_file' => $file,
        ]);

        $response->assertRedirect(route('karyawan.import'));
        $this->assertDatabaseHas('users', [
            'username' => 'i.gede.juli.suparwata',
            'role' => 'karyawan',
        ]);
        $this->assertDatabaseHas('karyawan', [
            'nama' => 'I Gede Juli Suparwata',
            'status_aktif' => 'Aktif',
            'status_karyawan' => 'Tetap',
            'kode_shift' => 'P',
            'yearly_leave_quota' => 12,
            'remaining_leave_quota' => 12,
        ]);
        $this->assertDatabaseHas('karyawan', [
            'nama' => 'Citra BR Sinuraya',
            'status_karyawan' => 'Training',
            'yearly_leave_quota' => 0,
            'remaining_leave_quota' => 0,
        ]);

        $tetap = Karyawan::where('nama', 'I Gede Juli Suparwata')->firstOrFail();
        $training = Karyawan::where('nama', 'Citra BR Sinuraya')->firstOrFail();
        $this->assertSame('2016-08-16', $tetap->tanggal_masuk->toDateString());
        $this->assertSame('2026-03-23', $training->tanggal_masuk->toDateString());
    }

    public function test_employee_import_supports_json_and_skips_existing_duplicates(): void
    {
        $admin = $this->prepareImportMasterData('admin_json');
        $csv = implode("\n", [
            'Nama Lengkap,Divisi,Posisi,Mulai Kerja,Aktif,Status',
            'Existing Employee,NBCS,Kasir,16-Agu-2016,Aktif,Tetap',
        ]);

        $this->actingAs($admin)->post(route('karyawan.import.store'), [
            'import_file' => UploadedFile::fake()->createWithContent('existing.csv', $csv),
        ]);

        $json = json_encode([
            [
                'Nama Lengkap' => 'Existing Employee',
                'Divisi' => 'NBCS',
                'Posisi' => 'Kasir',
                'Mulai Kerja' => '16-Agu-2016',
                'Aktif' => 'Aktif',
                'Status' => 'Tetap',
            ],
            [
                'Nama Lengkap' => 'New JSON Employee',
                'Divisi' => 'NSC1',
                'Posisi' => 'Teknisi',
                'Mulai Kerja' => '23-Mar-26',
                'Aktif' => 'Aktif',
                'Status' => 'Training',
            ],
        ]);

        $response = $this->actingAs($admin)->post(route('karyawan.import.store'), [
            'import_file' => UploadedFile::fake()->createWithContent('karyawan.json', $json),
        ]);

        $response->assertRedirect(route('karyawan.import'));
        $summary = session('import_summary');

        $this->assertSame(1, $summary['success']);
        $this->assertSame(1, $summary['skipped']);
        $this->assertSame(1, Karyawan::where('nama', 'Existing Employee')->count());
        $this->assertDatabaseHas('karyawan', [
            'nama' => 'New JSON Employee',
            'status_karyawan' => 'Training',
            'kode_shift' => 'P',
        ]);
    }

    public function test_employee_import_supports_xlsx_with_matching_headers(): void
    {
        $admin = $this->prepareImportMasterData('admin_xlsx');

        $file = $this->makeXlsxUpload([
            ['Nama Lengkap', 'Divisi', 'Posisi', 'Mulai Kerja', 'Aktif', 'Status'],
            ['XLSX Employee', 'Office', 'SDM', '8-Jun-26', 'Aktif', 'Training'],
        ]);

        $response = $this->actingAs($admin)->post(route('karyawan.import.store'), [
            'import_file' => $file,
        ]);

        $response->assertRedirect(route('karyawan.import'));
        $this->assertDatabaseHas('karyawan', [
            'nama' => 'XLSX Employee',
            'status_karyawan' => 'Training',
            'kode_shift' => 'P',
            'yearly_leave_quota' => 0,
            'remaining_leave_quota' => 0,
        ]);

        $employee = Karyawan::where('nama', 'XLSX Employee')->firstOrFail();
        $this->assertSame('2026-06-08', $employee->tanggal_masuk->toDateString());
    }

    private function makeXlsxUpload(array $rows): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'karyawan-import-') . '.xlsx';
        $zip = new ZipArchive();
        $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/></Types>');
        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
        $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheets><sheet name="Sheet1" sheetId="1" r:id="rId1" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"/></sheets></workbook>');
        $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/></Relationships>');
        $zip->addFromString('xl/worksheets/sheet1.xml', $this->xlsxSheetXml($rows));
        $zip->close();

        return new UploadedFile($path, 'karyawan.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
    }

    private function xlsxSheetXml(array $rows): string
    {
        $xmlRows = [];

        foreach ($rows as $rowIndex => $row) {
            $cells = [];

            foreach ($row as $columnIndex => $value) {
                $column = chr(65 + $columnIndex);
                $escaped = htmlspecialchars((string) $value, ENT_XML1);
                $cells[] = "<c r=\"{$column}" . ($rowIndex + 1) . "\" t=\"inlineStr\"><is><t>{$escaped}</t></is></c>";
            }

            $xmlRows[] = '<row r="' . ($rowIndex + 1) . '">' . implode('', $cells) . '</row>';
        }

        return '<?xml version="1.0" encoding="UTF-8"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>' . implode('', $xmlRows) . '</sheetData></worksheet>';
    }

    private function prepareImportMasterData(string $adminUsername): \App\Models\User
    {
        config(['hris.default_import_password' => 'password']);
        Role::findOrCreate('admin', 'web');
        Role::findOrCreate('karyawan', 'web');
        Shift::firstOrCreate(['kode_shift' => 'P'], [
            'nama_shift' => 'Pagi',
            'jam_masuk' => '08:00:00',
            'jam_pulang' => '17:00:00',
        ]);
        $this->seedLeaveTypes();

        $admin = \App\Models\User::create([
            'username' => $adminUsername,
            'password' => 'password',
            'role' => 'admin',
        ]);
        $admin->assignRole('admin');

        return $admin;
    }

    private function seedLeaveTypes(): void
    {
        foreach ([
            ['nama_cuti' => 'Cuti Tahunan', 'default_quota' => 12, 'applies_to_status' => 'Tetap'],
            ['nama_cuti' => 'Cuti Hari Raya', 'default_quota' => 4, 'applies_to_status' => null],
            ['nama_cuti' => 'Cuti Sakit', 'default_quota' => 6, 'applies_to_status' => null],
        ] as $leaveType) {
            LeaveType::updateOrCreate(
                ['nama_cuti' => $leaveType['nama_cuti']],
                $leaveType + ['is_active' => true]
            );
        }
    }
}
