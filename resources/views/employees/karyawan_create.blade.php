@extends('layouts.app')

@section('title', 'Tambah Karyawan - HRIS')
@section('html_lang', 'id')

@section('head')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endsection

@section('content')
<div class="hris-container">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="hris-card">
                <div class="hris-card-header d-flex align-items-center justify-content-between">
                    <h2 class="mb-0"><i class="bi bi-person-plus-fill me-2"></i>Tambah Karyawan</h2>
                    <a href="{{ route('karyawan.index') }}" class="btn btn-secondary btn-sm">
                        <i class="bi bi-arrow-left me-1"></i>Kembali
                    </a>
                </div>
                <div class="hris-card-body">

                    @if(session('error'))
                        <div class="alert alert-danger mb-3">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ session('error') }}
                        </div>
                    @endif

                    <form action="{{ route('karyawan.store') }}" method="POST">
                        @csrf

                        <div class="mb-3">
                            <label class="form-label fw-semibold">
                                Nama Lengkap <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="nama" class="form-control @error('nama') is-invalid @enderror"
                                   value="{{ old('nama') }}" placeholder="Masukkan nama lengkap" required>
                            @error('nama')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Jabatan</label>
                                    <select name="id_jabatan" class="form-select @error('id_jabatan') is-invalid @enderror">
                                        <option value="">-- Pilih Jabatan --</option>
                                        @foreach($jabatanList as $jabatan)
                                            <option value="{{ $jabatan->id }}" {{ old('id_jabatan') == $jabatan->id ? 'selected' : '' }}>
                                                {{ $jabatan->nama_jabatan }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('id_jabatan')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Divisi</label>
                                    <select name="id_devisi" class="form-select @error('id_devisi') is-invalid @enderror">
                                        <option value="">-- Pilih Divisi --</option>
                                        @foreach($divisiList as $divisi)
                                            <option value="{{ $divisi->id }}" {{ old('id_devisi') == $divisi->id ? 'selected' : '' }}>
                                                {{ $divisi->nama_devisi }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('id_devisi')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Tanggal Masuk</label>
                                    <input type="date" name="tanggal_masuk"
                                           class="form-control @error('tanggal_masuk') is-invalid @enderror"
                                           value="{{ old('tanggal_masuk') }}">
                                    @error('tanggal_masuk')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Tanggal Mulai Kerja</label>
                                    <input type="date" name="tanggal_mulai_kerja"
                                           class="form-control @error('tanggal_mulai_kerja') is-invalid @enderror"
                                           value="{{ old('tanggal_mulai_kerja') }}">
                                    @error('tanggal_mulai_kerja')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Status Aktif</label>
                                    <select name="status_aktif" class="form-select @error('status_aktif') is-invalid @enderror">
                                        @foreach(['Aktif', 'Nonaktif'] as $status)
                                            <option value="{{ $status }}" {{ old('status_aktif', 'Aktif') === $status ? 'selected' : '' }}>{{ $status }}</option>
                                        @endforeach
                                    </select>
                                    @error('status_aktif')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Status Karyawan</label>
                                    <select name="status_karyawan" class="form-select @error('status_karyawan') is-invalid @enderror">
                                        @foreach(['Tetap', 'Kontrak', 'Training'] as $status)
                                            <option value="{{ $status }}" {{ old('status_karyawan', 'Tetap') === $status ? 'selected' : '' }}>{{ $status }}</option>
                                        @endforeach
                                    </select>
                                    @error('status_karyawan')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">
                                Shift Default <span class="text-danger">*</span>
                            </label>
                            <select name="kode_shift" class="form-select @error('kode_shift') is-invalid @enderror" required>
                                <option value="">-- Pilih Shift --</option>
                                @foreach($shiftList as $shift)
                                    <option value="{{ $shift->kode_shift }}" {{ old('kode_shift', 'P') === $shift->kode_shift ? 'selected' : '' }}>
                                        {{ $shift->label }} [{{ $shift->kode_shift }}]
                                    </option>
                                @endforeach
                            </select>
                            @error('kode_shift')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Akun Login --}}
                        <hr class="my-4">
                        <h6 class="fw-bold mb-3 text-primary">
                            <i class="bi bi-person-lock me-2"></i>Akun Login Karyawan
                        </h6>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">
                                Username <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="username"
                                   class="form-control @error('username') is-invalid @enderror"
                                   value="{{ old('username') }}"
                                   placeholder="Contoh: budi.santoso" autocomplete="off" required>
                            @error('username')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Email</label>
                            <input type="email" name="email"
                                   class="form-control @error('email') is-invalid @enderror"
                                   value="{{ old('email') }}"
                                   placeholder="Opsional">
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">
                                    Password <span class="text-danger">*</span>
                                </label>
                                <input type="password" name="password"
                                       class="form-control @error('password') is-invalid @enderror"
                                       placeholder="Minimal 6 karakter" autocomplete="new-password" required>
                                @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">
                                    Konfirmasi Password <span class="text-danger">*</span>
                                </label>
                                <input type="password" name="password_confirmation"
                                       class="form-control"
                                       placeholder="Ulangi password" autocomplete="new-password" required>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Kuota Cuti Tahunan</label>
                                    <input type="number" name="yearly_leave_quota" min="0" max="365"
                                           class="form-control @error('yearly_leave_quota') is-invalid @enderror"
                                           value="{{ old('yearly_leave_quota', 12) }}">
                                    @error('yearly_leave_quota')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Sisa Kuota Cuti</label>
                                    <input type="number" name="remaining_leave_quota" min="0" max="365"
                                           class="form-control @error('remaining_leave_quota') is-invalid @enderror"
                                           value="{{ old('remaining_leave_quota', 12) }}">
                                    @error('remaining_leave_quota')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="d-flex gap-2 mt-4">
                            <button type="submit" class="btn btn-primary flex-fill">
                                <i class="bi bi-check-circle me-2"></i>Simpan
                            </button>
                            <a href="{{ route('karyawan.index') }}" class="btn btn-secondary flex-fill">
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
