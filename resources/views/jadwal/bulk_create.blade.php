@extends('layouts.app')

@section('content')
<div class="hris-container" style="max-width: 1100px;">
    <div class="hris-card mb-4">
        <div class="hris-card-header">
            <h2 class="mb-0">Input Jadwal Massal</h2>
        </div>

        <div class="hris-card-body">
            <form action="{{ route('jadwal.bulk-store') }}" method="POST">
                @csrf

                 <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Pilih Tanggal</label>
                        <input type="date" name="tanggal" required class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Divisi</label>
                        <select id="filterDivisi" name="id_divisi" class="form-select">
                            <option value="">-- Pilih Divisi --</option>
                            @foreach($divisiList as $divisi)
                                <option value="{{ $divisi->id }}" @selected((string) old('id_divisi') === (string) $divisi->id)>
                                    {{ $divisi->nama_divisi }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Jabatan</label>
                        <select id="filterJabatan" class="form-select">
                            <option value="">-- Pilih Jabatan --</option>
                            @foreach($jabatanList as $jabatan)
                                <option value="{{ $jabatan->id }}">{{ $jabatan->nama_jabatan }}</option>
                            @endforeach
                        </select>
                    </div>
                <div>
                    <h5>Atur Jadwal untuk Semua Karyawan:</h5>
                </div>
                <div class="mt-3 bg-light rounded-3">
                    <label class="form-label fw-semibold">Quick Set (Set Semua Sekaligus):</label>
                    <div class="d-flex flex-wrap gap-2">
                        <button type="button" onclick="setAllShift('1')" class="btn btn-success">Set Semua Pagi</button>
                        <button type="button" onclick="setAllShift('2')" class="btn btn-warning">Set Semua Siang</button>
                        <button type="button" onclick="setAllShift('3')" class="btn btn-primary">Set Semua Libur</button>
                        <button type="button" onclick="clearAllShift()" class="btn btn-outline-secondary">Clear Semua</button>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover hris-table align-middle">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama Karyawan</th>
                                <th>Jabatan</th>
                                <th>Jam Kerja</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($karyawanList as $index => $k)
                            <tr data-id-divisi="{{ $k->id_divisi }}" data-id-jabatan="{{ $k->id_jabatan }}">
                                <td>{{ $index + 1 }}</td>
                                <td>
                                    <strong>{{ $k->nama }}</strong>
                                    <input type="hidden" name="jadwal[{{ $index }}][id_karyawan]" value="{{ $k->id_karyawan }}">
                                </td>
                                <td>{{ $k->jabatan->nama_jabatan ?? '-' }}</td>
                                <td>
                                     <select name="jadwal[{{ $index }}][id_shift]" required class="form-select">
                                         <option value="">-- Pilih --</option>
                                         @foreach($jamKerjaOptions as $option)
                                             <option value="{{ $option->id_shift }}">{{ $option->label }}</option>
                                         @endforeach
                                     </select>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="d-flex gap-2 mt-4">
                    <button type="submit" class="btn hris-btn hris-btn-primary flex-fill">
                        Simpan Semua Jadwal
                    </button>
                    <a href="{{ route('jadwal.index') }}" class="btn hris-btn hris-btn-secondary flex-fill">
                        Batal
                    </a>
                </div>
            </form>
        </div>
    </div>

    {{-- <div class="hris-card">
        <div class="hris-card-header">
            <h5 class="mb-0"><i class="bi bi-calendar-range me-2"></i>Bulk Range Jadwal</h5>
        </div>
        <div class="hris-card-body">
            @if(session('bulk_range_summary'))
                @php $summary = session('bulk_range_summary'); @endphp
                <div class="row g-2 mb-3">
                    <div class="col-6 col-md-3"><div class="p-3 rounded bg-success-subtle text-center"><div class="h4 mb-0">{{ $summary['created'] }}</div><div class="small">Created</div></div></div>
                    <div class="col-6 col-md-3"><div class="p-3 rounded bg-primary-subtle text-center"><div class="h4 mb-0">{{ $summary['updated'] }}</div><div class="small">Updated</div></div></div>
                    <div class="col-6 col-md-3"><div class="p-3 rounded bg-warning-subtle text-center"><div class="h4 mb-0">{{ $summary['skipped'] }}</div><div class="small">Skipped</div></div></div>
                    <div class="col-6 col-md-3"><div class="p-3 rounded bg-danger-subtle text-center"><div class="h4 mb-0">{{ $summary['failed'] }}</div><div class="small">Failed</div></div></div>
                </div>
            @endif

            <form action="{{ route('jadwal.bulk-range-store') }}" method="POST">
                @csrf
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Tanggal Mulai</label>
                        <input type="date" name="tanggal_mulai" class="form-control" required value="{{ old('tanggal_mulai') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Tanggal Selesai</label>
                        <input type="date" name="tanggal_selesai" class="form-control" required value="{{ old('tanggal_selesai') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Shift</label>
                        <select name="id_shift" class="form-select" required>
                            <option value="">-- Pilih --</option>
                            @foreach($jamKerjaOptions as $option)
                                <option value="{{ $option->id_shift }}" @selected(old('id_shift') === $option->id_shift)>
                                    {{ $option->id_shift }} - {{ $option->label }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Overwrite Existing</label>
                        <select name="overwrite" class="form-select">
                            <option value="0" @selected(old('overwrite', '0') === '0')>Tidak, skip jadwal existing</option>
                            <option value="1" @selected(old('overwrite') === '1')>Ya, update jadwal existing</option>
                        </select>
                    </div>
                </div>

                <div class="row g-3 mt-1">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Target</label>
                        <select name="target_type" id="targetType" class="form-select" required>
                            <option value="all" @selected(old('target_type', 'all') === 'all')>Semua Karyawan</option>
                            <option value="divisi" @selected(old('target_type') === 'divisi')>By Divisi</option>
                            <option value="karyawan" @selected(old('target_type') === 'karyawan')>Karyawan Tertentu</option>
                        </select>
                    </div>
                    <div class="col-md-4 target-panel" id="targetDivisi">
                        <label class="form-label fw-semibold">Divisi</label>
                        <select name="id_divisi" class="form-select">
                            <option value="">-- Pilih Divisi --</option>
                            @foreach($divisiList as $divisi)
                                <option value="{{ $divisi->id }}" @selected((string) old('id_divisi') === (string) $divisi->id)>
                                    {{ $divisi->nama_divisi }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                </div>

                <div class="target-panel mt-3" id="targetKaryawan">
                    <label class="form-label fw-semibold">Pilih Karyawan</label>
                    <div class="row g-2" style="max-height: 260px; overflow: auto;">
                        @foreach($karyawanList as $karyawan)
                            <div class="col-md-4">
                                <label class="form-check border rounded p-2 h-100">
                                    <input class="form-check-input me-1" type="checkbox" name="karyawan_ids[]" value="{{ $karyawan->id_karyawan }}"
                                           @checked(in_array($karyawan->id_karyawan, old('karyawan_ids', [])))>
                                    <span class="small fw-semibold">{{ $karyawan->nama }}</span>
                                    <span class="small text-muted d-block">{{ $karyawan->divisi->nama_divisi ?? 'Tanpa Divisi' }}</span>
                                </label>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="d-flex gap-2 mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-1"></i>Simpan Bulk Range
                    </button>
                    <a href="{{ route('jadwal.index') }}" class="btn btn-outline-secondary">Batal</a>
                </div>
            </form>
        </div>
    </div> --}}
</div>
@endsection

@section('scripts')
<script>
function setAllShift(shift) {
    const selects = document.querySelectorAll('select[name*="[id_shift]"]');
    selects.forEach(select => {
        select.value = shift;
    });
}
function clearAllShift() {
    const selects = document.querySelectorAll('select[name*="[id_shift]"]');
    selects.forEach(select => {
        select.value = '';
    });
}

const allJabatanOptions = @json($jabatanList->map(fn($j) => ['id' => (string) $j->id, 'nama' => $j->nama_jabatan]));

function updateJabatanOptions(divisiVal) {
    const jabatanSelect = document.getElementById('filterJabatan');
    let allowedIds = null;

    if (divisiVal) {
        allowedIds = new Set();
        document.querySelectorAll('tbody tr[data-id-divisi="' + divisiVal + '"]').forEach(row => {
            if (row.dataset.idJabatan) {
                allowedIds.add(row.dataset.idJabatan);
            }
        });
    }

    jabatanSelect.innerHTML = '<option value="">-- Pilih Jabatan --</option>';
    allJabatanOptions.forEach(jabatan => {
        if (!allowedIds || allowedIds.has(jabatan.id)) {
            const opt = document.createElement('option');
            opt.value = jabatan.id;
            opt.textContent = jabatan.nama;
            jabatanSelect.appendChild(opt);
        }
    });
}


function applyFilters() {
    const divisiVal = document.getElementById('filterDivisi').value;
    const jabatanVal = document.getElementById('filterJabatan').value;

    document.querySelectorAll('tbody tr[data-id-divisi]').forEach(row => {
        const matches = (!divisiVal || row.dataset.idDivisi === divisiVal)
                      && (!jabatanVal || row.dataset.idJabatan === jabatanVal);

        row.style.display = matches ? '' : 'none';
        row.querySelectorAll('select, input').forEach(field => field.disabled = !matches);
    });
}

document.getElementById('filterDivisi').addEventListener('change', function () {
    updateJabatanOptions(this.value);
    applyFilters();
});
document.getElementById('filterJabatan').addEventListener('change', applyFilters);

// function syncTargetPanels() {
//     const type = document.getElementById('targetType')?.value;
//     document.getElementById('targetDivisi').style.display = type === 'divisi' ? 'block' : 'none';
//     document.getElementById('targetKaryawan').style.display = type === 'karyawan' ? 'block' : 'none';
// }

// document.getElementById('targetType')?.addEventListener('change', syncTargetPanels);
// syncTargetPanels();

</script>
@endsection
