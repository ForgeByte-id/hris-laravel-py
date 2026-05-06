@extends('layouts.app')

@section('title', 'Daftar Karyawan - HRIS')
@section('html_lang', 'id')

@section('head')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endsection

@section('content')
<div class="hris-container">
    {{-- Header Section --}}
    <div class="row g-3 mb-4">
        <div class="col-12">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <h2 class="mb-1">
                        <i class="bi bi-people-fill me-2" style="font-size: 1.5rem;"></i>Daftar Karyawan
                    </h2>
                    <p class="text-muted small mb-0">Kelola data karyawan dan registrasi wajah untuk face recognition</p>
                </div>
                <a href="{{ route('karyawan.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-circle me-2"></i>Tambah Karyawan
                </a>
            </div>
        </div>
    </div>

    {{-- Summary Stats --}}
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="hris-card">
                <div class="hris-card-body text-center">
                    <div style="font-size: 2rem; color: var(--hris-primary); margin-bottom: 8px;">
                        <i class="bi bi-people"></i>
                    </div>
                    <div class="h3 mb-1">{{ $karyawan->count() }}</div>
                    <div class="text-muted small">Total Karyawan</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="hris-card">
                <div class="hris-card-body text-center">
                    <div style="font-size: 2rem; color: #2ec4b6; margin-bottom: 8px;">
                        <i class="bi bi-check-circle"></i>
                    </div>
                    <div class="h3 mb-1">{{ $karyawan->filter(fn($k) => $k->face_embedding)->count() }}</div>
                    <div class="text-muted small">Sudah Registrasi Wajah</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="hris-card">
                <div class="hris-card-body text-center">
                    <div style="font-size: 2rem; color: #ff9800; margin-bottom: 8px;">
                        <i class="bi bi-exclamation-circle"></i>
                    </div>
                    <div class="h3 mb-1">{{ $karyawan->filter(fn($k) => !$k->face_embedding)->count() }}</div>
                    <div class="text-muted small">Belum Registrasi</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Employee List --}}
    <div class="row g-3">
        <div class="col-12">
            <div class="hris-card">
                <div class="hris-card-header">
                    <h5 class="mb-0">
                        <i class="bi bi-list-ul me-2"></i>Daftar Karyawan
                    </h5>
                </div>
                <div class="hris-card-body p-0">
                    @if($karyawan->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover mb-0 datatable">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 50px;">No</th>
                                        <th>Nama Karyawan</th>
                                        <th>Jabatan</th>
                                        <th>Divisi</th>
                                        <th style="width: 120px;">Status Wajah</th>
                                        <th style="width: 120px;">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($karyawan as $k)
                                    <tr>
                                        <td class="align-middle">
                                            {{-- <span class="badge bg-light text-dark">{{ $loop->iteration }}</span> --}}
                                        </td>
                                        <td class="align-middle">
                                            <div class="d-flex align-items-center gap-2">
                                                <div style="width: 36px; height: 36px; background: var(--hris-primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold;">
                                                    {{ substr($k->nama, 0, 1) }}
                                                </div>
                                                <div>
                                                    <div class="small font-weight-bold">{{ $k->nama }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="align-middle">
                                            <span class="small">{{ $k->jabatan?->nama_jabatan ?? 'N/A' }}</span>
                                        </td>
                                        <td class="align-middle">
                                            <span class="small text-muted">{{ $k->devisi->nama_devisi ?? 'N/A'}}</span>
                                        </td>
                                        <td class="align-middle">
                                            @if($k->face_embedding)
                                                <span class="badge bg-success">
                                                    <i class="bi bi-check-circle me-1"></i>Terdaftar
                                                </span>
                                            @else
                                                <span class="badge bg-warning">
                                                    <i class="bi bi-exclamation-circle me-1"></i>Pending
                                                </span>
                                            @endif
                                        </td>
                                        <td class="align-middle">
                                            <div class="btn-group btn-group-sm" role="group">
                                                <a href="{{ route('karyawan.show', $k->id_karyawan) }}"
                                                   class="btn btn-outline-primary" title="Lihat Detail">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <a href="{{ route('karyawan.edit', $k->id_karyawan) }}"
                                                   class="btn btn-outline-secondary" title="Edit">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <a href="{{ route('karyawan.register-face', $k->id_karyawan) }}"
                                                   class="btn btn-outline-info" title="Daftar/Update Wajah">
                                                    <i class="bi bi-camera"></i>
                                                </a>
                                                @if($k->face_embedding)
                                                    <button class="btn btn-outline-danger"
                                                            onclick="deleteFace({{ $k->id_karyawan }})"
                                                            title="Hapus Data Wajah">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                @endif
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
                            <div class="text-muted">Belum ada data karyawan</div>
                            <div class="text-muted small mt-2">Silakan tambahkan data karyawan terlebih dahulu</div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

        async function deleteFace(idKaryawan) {
            if (!confirm('Yakin ingin menghapus data wajah karyawan ini?')) {
                return;
            }

            try {
                const response = await fetch(`/api/karyawan/${idKaryawan}/face`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Content-Type': 'application/json'
                    }
                });

                const data = await response.json();

                if (data.success) {
                    alert(data.message);
                    location.reload();
                } else {
                    alert('Gagal menghapus: ' + data.message);
                }
            } catch (error) {
                alert('Terjadi kesalahan: ' + error.message);
            }
        }
    </script>
@endsection
