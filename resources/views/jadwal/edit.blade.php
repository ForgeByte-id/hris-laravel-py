@extends('layouts.app')

@section('content')
<div class="hris-container">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="hris-card">
                <div class="hris-card-header">
                    <h2 class="mb-0">Edit Jadwal Kerja</h2>
                </div>

                <div class="hris-card-body">

            <!-- Info Karyawan -->
            <div class="p-3 bg-light border rounded-3 mb-4">
                <h5 class="mb-2"><i class="bi bi-person-fill" style="margin-right: 0.5rem;"></i>Karyawan</h5>
                <p class="mb-1"><strong>Nama:</strong> {{ $jadwal->karyawan->nama }}</p>
                <p class="mb-1"><strong>Jabatan:</strong> {{ $jadwal->karyawan->jabatan->nama_jabatan ?? '-' }}</p>
                <p class="mb-0"><strong>Divisi:</strong> {{ $jadwal->karyawan->divisi }}</p>
            </div>

            <form action="{{ route('jadwal.update', $jadwal->id_jadwal) }}" method="POST">
                @csrf
                @method('PUT')

                <!-- Tanggal -->
                <div class="mb-3">
                    <label class="form-label fw-semibold">
                        Tanggal <span class="text-danger">*</span>
                    </label>
                    <input type="date" name="tanggal" required
                           value="{{ old('tanggal', $jadwal->tanggal->format('Y-m-d')) }}" class="form-control">
                    @error('tanggal')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>

                <!-- Jam Kerja -->
                <div class="mb-3">
                    <label class="form-label fw-semibold">
                        Jam Kerja / Shift <span class="text-danger">*</span>
                    </label>
                    <select name="id_shift" required class="form-select">
                        <option value="">-- Pilih Jam Kerja --</option>
                        @foreach($jamKerjaOptions as $option)
                            <option value="{{ $option->id_shift }}"
                                {{ old('id_shift', $jadwal->id_shift) == $option->id_shift ? 'selected' : '' }}>
                                {{ $option->id_shift }} - {{ $option->label }}
                            </option>
                        @endforeach
                    </select>
                    @error('id_shift')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>

                <!-- Buttons -->
                <div class="d-flex gap-2">
                    <button type="submit" class="btn hris-btn hris-btn-primary flex-fill">
                        Update Jadwal
                    </button>
                    <a href="{{ route('jadwal.index') }}" class="btn hris-btn hris-btn-secondary flex-fill">
                        Batal
                    </a>
                </div>
            </form>

            <!-- Delete Button -->
            <form action="{{ route('jadwal.destroy', $jadwal->id_jadwal) }}" method="POST" class="mt-3">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger w-100"
                        onclick="return confirm('Yakin ingin menghapus jadwal ini?')">
                    Hapus Jadwal
                </button>
            </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
