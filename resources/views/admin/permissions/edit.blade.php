@extends('layouts.app')

@section('title', 'Edit Permission - HRIS')

@section('content')
<div class="hris-container">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="hris-card">
                <div class="hris-card-header">
                    <h2 class="mb-0"><i class="bi bi-pencil-square me-2"></i>Edit Permission</h2>
                </div>
                <div class="hris-card-body">
                    <form action="{{ route('admin.permissions.update', $permission) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label class="form-label fw-semibold">
                                Nama Permission <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                   value="{{ old('name', $permission->name) }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-flex gap-2 mt-4">
                            <button type="submit" class="btn btn-primary flex-fill">
                                <i class="bi bi-check-circle me-2"></i>Update
                            </button>
                            <a href="{{ route('admin.permissions.index') }}" class="btn btn-secondary flex-fill">
                                <i class="bi bi-x-circle me-2"></i>Batal
                            </a>
                        </div>
                    </form>

                    <form action="{{ route('admin.permissions.destroy', $permission) }}" method="POST" class="mt-3"
                          onsubmit="return confirm('Yakin ingin menghapus permission ini?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger w-100">
                            <i class="bi bi-trash me-2"></i>Hapus Permission
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
