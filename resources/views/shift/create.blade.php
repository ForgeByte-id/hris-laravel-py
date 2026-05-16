@extends('layouts.app')

@section('title', 'Tambah Shift - HRIS')

@section('content')
<div class="hris-container">
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="hris-card">
                <div class="hris-card-header d-flex align-items-center justify-content-between">
                    <h2 class="mb-0"><i class="bi bi-plus-circle me-2"></i>Tambah Shift</h2>
                    <a href="{{ route('shift.index') }}" class="btn btn-secondary btn-sm">
                        <i class="bi bi-arrow-left me-1"></i>Kembali
                    </a>
                </div>
                <div class="hris-card-body">
                    <form action="{{ route('shift.store') }}" method="POST">
                        @csrf

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Kode Shift <span class="text-danger">*</span></label>
                            <input type="text" name="kode_shift"
                                   class="form-control @error('kode_shift') is-invalid @enderror"
                                   value="{{ old('kode_shift') }}" maxlength="2" required>
                            @error('kode_shift')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Nama Shift <span class="text-danger">*</span></label>
                            <input type="text" name="nama_shift"
                                   class="form-control @error('nama_shift') is-invalid @enderror"
                                   value="{{ old('nama_shift') }}" required>
                            @error('nama_shift')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Jam Masuk</label>
                                <input type="time" name="jam_masuk"
                                       class="form-control @error('jam_masuk') is-invalid @enderror"
                                       value="{{ old('jam_masuk') }}">
                                @error('jam_masuk')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Jam Pulang</label>
                                <input type="time" name="jam_pulang"
                                       class="form-control @error('jam_pulang') is-invalid @enderror"
                                       value="{{ old('jam_pulang') }}">
                                @error('jam_pulang')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="d-flex gap-2 mt-4">
                            <button type="submit" class="btn btn-primary flex-fill">
                                <i class="bi bi-check-circle me-2"></i>Simpan
                            </button>
                            <a href="{{ route('shift.index') }}" class="btn btn-secondary flex-fill">
                                <i class="bi bi-x-circle me-2"></i>Batal
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
