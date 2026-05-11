@extends('layouts.app')

@section('content')
<div class="hris-container" style="max-width: 1000px;">
    <div class="hris-card">
        <div class="hris-card-header">
            <h2 class="mb-0">Input Jadwal Massal</h2>
        </div>

        <div class="hris-card-body">
            <form action="{{ route('jadwal.bulk-store') }}" method="POST">
                @csrf

                <!-- Pilih Tanggal -->
                <div class="alert alert-warning d-flex align-items-center gap-3" role="alert">
                    <div>
                        <h5 class="mb-1">Pilih Tanggal</h5>
                        <input type="date" name="tanggal" required class="form-control" style="max-width: 250px;">
                    </div>
                </div>

                <!-- Tabel Input -->
                <h5 class="mb-3">Atur Jadwal untuk Semua Karyawan:</h5>
                <div class="table-responsive">
                    <table class="table table-hover hris-table align-middle">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama Karyawan</th>
                                <th>Jabatan</th>
                                <th>Jam Kerja</th>
                                <th>Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($karyawanList as $index => $k)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>
                                    <strong>{{ $k->nama }}</strong>
                                    <input type="hidden" name="jadwal[{{ $index }}][id_karyawan]" value="{{ $k->id_karyawan }}">
                                </td>
                                <td>{{ $k->jabatan->nama_jabatan ?? '-' }}</td>
                                <td>
                                     <select name="jadwal[{{ $index }}][kode_shift]" required class="form-select">
                                         <option value="">-- Pilih --</option>
                                         @foreach($jamKerjaOptions as $option)
                                             <option value="{{ $option->kode_shift }}">{{ $option->kode_shift }} - {{ $option->label }}</option>
                                         @endforeach
                                     </select>
                                </td>
                                <td>
                                    <input type="text" name="jadwal[{{ $index }}][keterangan]"
                                           placeholder="Optional" class="form-control">
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Quick Set Buttons -->
                <div class="mt-3 p-3 bg-light rounded-3">
                    <h6 class="mb-2">Quick Set (Set Semua Sekaligus):</h6>
                    <div class="d-flex flex-wrap gap-2">
                        <button type="button" onclick="setAllShift('P')" class="btn btn-success">
                            Set Semua Pagi
                        </button>
                        <button type="button" onclick="setAllShift('M')" class="btn btn-warning">
                            Set Semua Siang
                        </button>
                        <button type="button" onclick="setAllShift('S')" class="btn btn-primary">
                            Set Semua Malam
                        </button>
                        <button type="button" onclick="setAllShift('L')" class="btn btn-danger">
                            Set Semua Libur
                        </button>
                    </div>
                </div>

                <!-- Submit Buttons -->
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
</div>
@endsection

@section('scripts')
<script>
function setAllShift(shift) {
    const selects = document.querySelectorAll('select[name*="[kode_shift]"]');
    selects.forEach(select => {
        select.value = shift;
    });
}
</script>
@endsection
