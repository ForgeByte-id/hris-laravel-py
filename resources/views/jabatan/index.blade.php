@extends('layouts.app')

@section('title', 'Jabatan - HRIS')

@section('content')
<div class="hris-container">
    <div class="row g-3 mb-4">
        <div class="col-12">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <h2 class="mb-1">
                        <i class="bi bi-briefcase-fill me-2" style="font-size: 1.5rem;"></i>Manajemen Jabatan
                    </h2>
                    <p class="text-muted small mb-0">Kelola data jabatan karyawan</p>
                </div>
                <a href="{{ route('jabatan.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-circle me-2"></i>Tambah Jabatan
                </a>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-circle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="hris-card">
        <div class="hris-card-header">
            <h5 class="mb-0"><i class="bi bi-list-ul me-2"></i>Daftar Jabatan</h5>
        </div>
        <div class="hris-card-body p-0">
            @if($jabatan->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover mb-0 datatable">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 50px;">No</th>
                                <th>Nama Jabatan</th>
                                <th style="width: 150px;">Jumlah Karyawan</th>
                                <th style="width: 120px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($jabatan as $j)
                            <tr>
                                <td class="align-middle">{{ $loop->iteration }}</td>
                                <td class="align-middle fw-semibold">{{ $j->nama_jabatan }}</td>
                                <td class="align-middle">
                                    <span class="badge bg-primary rounded-pill">{{ $j->karyawan_count }} Karyawan</span>
                                </td>
                                <td class="align-middle">
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="{{ route('jabatan.edit', $j->id) }}"
                                           class="btn btn-outline-secondary" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form action="{{ route('jabatan.destroy', $j->id) }}" method="POST"
                                              onsubmit="return confirm('Yakin ingin menghapus jabatan ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger" title="Hapus">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-5">
                    <i class="bi bi-inbox" style="font-size: 3rem; color: var(--hris-border); margin-bottom: 16px;"></i>
                    <div class="text-muted">Belum ada data jabatan</div>
                    <a href="{{ route('jabatan.create') }}" class="btn btn-primary mt-3">
                        <i class="bi bi-plus-circle me-2"></i>Tambah Jabatan Pertama
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
