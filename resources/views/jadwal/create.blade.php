@extends('layouts.app')

@section('content')
<div class="hris-container">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="hris-card">
                <div class="hris-card-header">
                    <h2 class="mb-0">Tambah Jadwal Kerja</h2>
                </div>

                <div class="hris-card-body">
            <form action="{{ route('jadwal.store') }}" method="POST">
                @csrf

                <!-- Pilih Karyawan -->
                <div class="mb-3">
                    <label class="form-label fw-semibold">
                        Pilih Karyawan <span class="text-danger">*</span>
                    </label>
                    <select name="id_karyawan" required class="form-select">
                        <option value="">-- Pilih Karyawan --</option>
                        @foreach($karyawanList as $k)
                            <option value="{{ $k->id_karyawan }}" {{ old('id_karyawan') == $k->id_karyawan ? 'selected' : '' }}>
                                {{ $k->nama }} - {{ $k->jabatan->nama_jabatan }}
                            </option>
                        @endforeach
                    </select>
                    @error('id_karyawan')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>

                <!-- Tanggal -->
                <div class="mb-3">
                    <label class="form-label fw-semibold">
                        Tanggal <span class="text-danger">*</span>
                    </label>
                    <input type="date" name="tanggal" required value="{{ old('tanggal') }}" class="form-control">
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
                            <option value="{{ $option->id_shift }}" {{ old('id_shift') == $option->id_shift ? 'selected' : '' }}>
                                {{ $option->id_shift }} - {{ $option->label }}
                            </option>
                        @endforeach
                    </select>
                    @error('id_shift')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>

                <!-- Info Box -->
                <div class="alert alert-info d-flex gap-2 align-items-start my-3" role="alert">
                    <span>💡</span>
                    <div>
                        <strong>Tips:</strong> Pastikan tidak ada duplikasi jadwal untuk karyawan yang sama di tanggal yang sama.
                    </div>
                </div>

                <!-- Buttons -->
                <div class="d-flex gap-2">
                    <button type="submit" class="btn hris-btn hris-btn-primary flex-fill">
                        Simpan Jadwal
                    </button>
                    <a href="{{ route('jadwal.index') }}" class="btn hris-btn hris-btn-secondary flex-fill">
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
