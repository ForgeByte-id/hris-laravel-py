@extends('layouts.app')

@section('content')
<div class="hris-container">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="hris-card">
                <div class="hris-card-header">
                    <h2 class="mb-0">Form Pengajuan Cuti</h2>
                </div>
                <div class="hris-card-body">
            <form action="{{ route('cuti.store') }}" method="POST">
                @csrf

                <!-- Informasi Karyawan -->
                    <div class="p-3 bg-light border rounded-3 mb-4">
                        <h5 class="mb-2">Informasi Karyawan</h5>
                        <p class="mb-1"><strong>Nama:</strong> {{ $karyawan->nama }}</p>
                        <p class="mb-1"><strong>Jabatan:</strong> {{ $karyawan->jabatan->nama_jabatan ?? '-' }}</p>
                        <p class="mb-1"><strong>Divisi:</strong> {{ $karyawan->divisi->nama_divisi ?? '-' }}</p>
                        @if(($leaveBalances ?? collect())->isNotEmpty())
                            <div class="d-flex flex-wrap gap-2">
                                @foreach($leaveBalances as $balance)
                                    <span class="badge text-bg-light border">
                                        {{ $balance->leaveType->nama_cuti }}:
                                        {{ $balance->remaining_quota }}/{{ $balance->quota }} hari
                                    </span>
                                @endforeach
                            </div>
                        @endif
                    </div>

                <!-- Jenis Cuti -->
                <div class="mb-3">
                    <label class="form-label fw-semibold">
                        Jenis Cuti <span class="text-danger">*</span>
                    </label>
                    <select name="jenis_cuti" class="form-select" required>
                        <option value="">-- Pilih Jenis Cuti --</option>
                        @foreach($jenisCuti as $jenis)
                            <option value="{{ $jenis }}" {{ old('jenis_cuti') == $jenis ? 'selected' : '' }}>
                                {{ $jenis }}
                            </option>
                        @endforeach
                    </select>
                    @error('jenis_cuti')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>

                <!-- Tanggal Mulai -->
                <div class="mb-3">
                    <label class="form-label fw-semibold">
                        Tanggal Mulai <span class="text-danger">*</span>
                    </label>
                    <input type="date" name="tanggal_mulai" class="form-control" required
                           value="{{ old('tanggal_mulai') }}"
                           min="{{ date('Y-m-d') }}">
                    @error('tanggal_mulai')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>

                <!-- Tanggal Selesai -->
                <div class="mb-3">
                    <label class="form-label fw-semibold">
                        Tanggal Selesai <span class="text-danger">*</span>
                    </label>
                    <input type="date" name="tanggal_selesai" class="form-control" required
                           value="{{ old('tanggal_selesai') }}"
                           min="{{ date('Y-m-d') }}">
                    @error('tanggal_selesai')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>

                <!-- Keterangan -->
                <div class="mb-3">
                    <label class="form-label fw-semibold">
                        Keterangan/Alasan
                    </label>
                    <textarea name="keterangan" class="form-control" rows="4"
                              placeholder="Tuliskan alasan pengajuan cuti...">{{ old('keterangan') }}</textarea>
                    @error('keterangan')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>

                <!-- Info Box -->
                <div class="alert alert-warning d-flex gap-2 align-items-start my-3" role="alert">
                    <i class="bi bi-exclamation-triangle-fill" style="font-size: 1.2rem; color: #856404; flex-shrink: 0;"></i>
                    <div>
                        <strong>Catatan:</strong>
                        Pengajuan cuti akan diproses melalui alur persetujuan.
                        Pastikan semua data sudah benar sebelum submit.
                    </div>
                </div>

                <!-- Buttons -->
                <div class="d-flex gap-2">
                    <button type="submit" class="btn hris-btn hris-btn-primary flex-fill">
                        Ajukan Cuti
                    </button>
                    <a href="{{ route('cuti.index') }}" class="btn hris-btn hris-btn-secondary flex-fill">
                        Batal
                    </a>
                </div>
            </form>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
// Auto-update tanggal selesai minimal sama dengan tanggal mulai
document.querySelector('input[name="tanggal_mulai"]').addEventListener('change', function() {
    document.querySelector('input[name="tanggal_selesai"]').min = this.value;
});
</script>
@endsection
