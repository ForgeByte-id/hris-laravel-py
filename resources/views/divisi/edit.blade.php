@extends('layouts.app')

@section('title', 'Edit Divisi - HRIS')

@section('content')
<div class="hris-container">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="hris-card">
                <div class="hris-card-header">
                    <h2 class="mb-0"><i class="bi bi-pencil-square me-2"></i>Edit Divisi</h2>
                </div>
                <div class="hris-card-body">
                    <form action="{{ route('divisi.update', $divisi->id) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label class="form-label fw-semibold">
                                Nama Divisi <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="nama_divisi" class="form-control @error('nama_divisi') is-invalid @enderror"
                                   value="{{ old('nama_divisi', $divisi->nama_divisi) }}" required>
                            @error('nama_divisi')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-flex gap-2 mt-4">
                            <button type="submit" class="btn btn-primary flex-fill">
                                <i class="bi bi-check-circle me-2"></i>Update
                            </button>
                            <a href="{{ route('divisi.index') }}" class="btn btn-secondary flex-fill">
                                <i class="bi bi-x-circle me-2"></i>Batal
                            </a>
                        </div>
                    </form>

                    <form action="{{ route('divisi.destroy', $divisi->id) }}" method="POST" class="mt-3"
                          onsubmit="return confirm('Yakin ingin menghapus divisi ini?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger w-100">
                            <i class="bi bi-trash me-2"></i>Hapus Divisi
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
