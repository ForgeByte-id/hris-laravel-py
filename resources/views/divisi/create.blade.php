@extends('layouts.app')

@section('title', 'Tambah Divisi - HRIS')

@section('content')
<div class="hris-container">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="hris-card">
                <div class="hris-card-header">
                    <h2 class="mb-0"><i class="bi bi-diagram-3-fill me-2"></i>Tambah Divisi</h2>
                </div>
                <div class="hris-card-body">
                    <form action="{{ route('divisi.store') }}" method="POST">
                        @csrf

                        <div class="mb-3">
                            <label class="form-label fw-semibold">
                                Nama Divisi <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="nama_devisi" class="form-control @error('nama_devisi') is-invalid @enderror"
                                   value="{{ old('nama_devisi') }}" placeholder="Contoh: IT, Marketing, HRD" required>
                            @error('nama_devisi')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-flex gap-2 mt-4">
                            <button type="submit" class="btn btn-primary flex-fill">
                                <i class="bi bi-check-circle me-2"></i>Simpan
                            </button>
                            <a href="{{ route('divisi.index') }}" class="btn btn-secondary flex-fill">
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
