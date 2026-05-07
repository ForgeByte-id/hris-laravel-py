@extends('layouts.app')

@section('title', 'Edit Karyawan - HRIS')
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
                    <h2 class="mb-0"><i class="bi bi-pencil-fill me-2"></i>Edit Karyawan</h2>
                    <a href="{{ route('karyawan.index') }}" class="btn btn-secondary btn-sm">
                        <i class="bi bi-arrow-left me-1"></i>Kembali
                    </a>
                </div>
                <div class="hris-card-body">
                    <form action="{{ route('karyawan.update', $karyawan->id_karyawan) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label class="form-label fw-semibold">
                                Nama Lengkap <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="nama" class="form-control @error('nama') is-invalid @enderror"
                                   value="{{ old('nama', $karyawan->nama) }}" required>
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
                                            <option value="{{ $jabatan->id }}"
                                                {{ old('id_jabatan', $karyawan->id_jabatan) == $jabatan->id ? 'selected' : '' }}>
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
                                            <option value="{{ $divisi->id }}"
                                                {{ old('id_devisi', $karyawan->id_devisi) == $divisi->id ? 'selected' : '' }}>
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
                                           value="{{ old('tanggal_masuk', $karyawan->tanggal_masuk ? \Carbon\Carbon::parse($karyawan->tanggal_masuk)->format('Y-m-d') : '') }}">
                                    @error('tanggal_masuk')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Akun User</label>
                                    <select name="id_user" class="form-select @error('id_user') is-invalid @enderror">
                                        <option value="">-- Pilih User (Opsional) --</option>
                                        @foreach($userList as $user)
                                            <option value="{{ $user->id_user }}"
                                                {{ old('id_user', $karyawan->id_user) == $user->id_user ? 'selected' : '' }}>
                                                {{ $user->username }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('id_user')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Kuota Cuti Tahunan</label>
                                    <input type="number" name="yearly_leave_quota" min="0" max="365"
                                           class="form-control @error('yearly_leave_quota') is-invalid @enderror"
                                           value="{{ old('yearly_leave_quota', $karyawan->yearly_leave_quota ?? 12) }}">
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
                                           value="{{ old('remaining_leave_quota', $karyawan->remaining_leave_quota ?? ($karyawan->yearly_leave_quota ?? 12)) }}">
                                    @error('remaining_leave_quota')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="d-flex gap-2 mt-4">
                            <button type="submit" class="btn btn-primary flex-fill">
                                <i class="bi bi-check-circle me-2"></i>Update
                            </button>
                            <a href="{{ route('karyawan.show', $karyawan->id_karyawan) }}" class="btn btn-secondary flex-fill">
                                <i class="bi bi-x-circle me-2"></i>Batal
                            </a>
                        </div>
                    </form>

                    <form action="{{ route('karyawan.destroy', $karyawan->id_karyawan) }}" method="POST" class="mt-3"
                          onsubmit="return confirm('Yakin ingin menghapus karyawan ini? Data tidak dapat dikembalikan.')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger w-100">
                            <i class="bi bi-trash me-2"></i>Hapus Karyawan
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
