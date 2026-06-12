@extends('layouts.app')

@section('title', 'Import Wajah Karyawan - HRIS')

@section('content')
<div class="hris-container" style="max-width: 900px;">
    <div class="mb-3">
        <a href="{{ route('karyawan.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i>Kembali ke Daftar Karyawan
        </a>
    </div>

    <div class="hris-card">
        <div class="hris-card-header">
            <h5 class="mb-0">
                <i class="bi bi-image-fill me-2"></i>Import Wajah dari Image
            </h5>
        </div>
        <div class="hris-card-body">
            @if(session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            <form action="{{ route('karyawan.import-face.store') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="mb-3">
                    <label class="form-label fw-semibold">Karyawan <span class="text-danger">*</span></label>
                    <select name="id_karyawan" class="form-select @error('id_karyawan') is-invalid @enderror" required>
                        <option value="">-- Pilih Karyawan --</option>
                        @foreach($karyawanList as $karyawan)
                            <option value="{{ $karyawan->id_karyawan }}"
                                @selected((string) old('id_karyawan', $selectedKaryawan?->id_karyawan) === (string) $karyawan->id_karyawan)>
                                {{ $karyawan->nama }}
                                @if($karyawan->devisi)
                                    - {{ $karyawan->devisi->nama_devisi }}
                                @endif
                                @if($karyawan->face_embedding)
                                    (update wajah)
                                @endif
                            </option>
                        @endforeach
                    </select>
                    @error('id_karyawan')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">File Wajah <span class="text-danger">*</span></label>
                    <input type="file"
                           name="face_image"
                           class="form-control @error('face_image') is-invalid @enderror"
                           accept=".jpg,.jpeg,.png,image/jpeg,image/png"
                           required>
                    <div class="form-text">Format JPG, JPEG, atau PNG. Maksimal 5 MB. Gunakan foto dengan satu wajah yang jelas.</div>
                    @error('face_image')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="alert alert-info small">
                    <i class="bi bi-info-circle-fill me-1"></i>
                    File image hanya dipakai untuk generate encoding. Database tetap menyimpan hasil encoding di
                    <code>karyawan.face_embedding</code>, bukan menyimpan image mentah.
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-upload me-1"></i>Import Wajah
                    </button>
                    <a href="{{ route('karyawan.index') }}" class="btn btn-outline-secondary">Batal</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
