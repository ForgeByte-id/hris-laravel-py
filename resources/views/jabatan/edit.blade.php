@extends('layouts.app')

@section('title', 'Edit Jabatan - HRIS')

@section('content')
<div class="hris-container">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="hris-card">
                <div class="hris-card-header">
                    <h2 class="mb-0"><i class="bi bi-pencil-square me-2"></i>Edit Jabatan</h2>
                </div>
                <div class="hris-card-body">
                    <form action="{{ route('jabatan.update', $jabatan->id) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label class="form-label fw-semibold">
                                Nama Jabatan <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="nama_jabatan" class="form-control @error('nama_jabatan') is-invalid @enderror"
                                   value="{{ old('nama_jabatan', $jabatan->nama_jabatan) }}" required>
                            @error('nama_jabatan')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-flex gap-2 mt-4">
                            <button type="submit" class="btn btn-primary flex-fill">
                                <i class="bi bi-check-circle me-2"></i>Update
                            </button>
                            <a href="{{ route('jabatan.index') }}" class="btn btn-secondary flex-fill">
                                <i class="bi bi-x-circle me-2"></i>Batal
                            </a>
                        </div>
                    </form>

                    <form action="{{ route('jabatan.destroy', $jabatan->id) }}" method="POST" class="mt-3"
                          onsubmit="return confirm('Yakin ingin menghapus jabatan ini?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger w-100">
                            <i class="bi bi-trash me-2"></i>Hapus Jabatan
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
