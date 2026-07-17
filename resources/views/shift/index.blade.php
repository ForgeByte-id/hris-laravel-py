@extends('layouts.app')

@section('title', 'Shift - HRIS')

@section('content')
<div class="hris-container">
    <div class="row g-3 mb-4">
        <div class="col-12">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <h2 class="mb-1">
                        <i class="bi bi-clock-fill me-2" style="font-size: 1.5rem;"></i>Manajemen Shift
                    </h2>
                    <p class="text-muted small mb-0">Kelola data shift untuk acuan absensi masuk dan pulang</p>
                </div>
                <a href="{{ route('shift.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-circle me-2"></i>Tambah Shift
                </a>
            </div>
        </div>
    </div>

    <div class="hris-card">
        <div class="hris-card-header">
            <h5 class="mb-0"><i class="bi bi-list-ul me-2"></i>Daftar Shift</h5>
        </div>
        <div class="hris-card-body p-0">
            @if($shifts->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover mb-0 datatable">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 50px;">No</th>
                                <th>Kode</th>
                                <th>Nama Shift</th>
                                <th>Jam Masuk</th>
                                <th>Jam Pulang</th>
                                <th style="width: 140px;">Dipakai</th>
                                <th style="width: 120px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($shifts as $shift)
                            <tr>
                                <td class="align-middle">{{ $loop->iteration }}</td>
                                <td class="align-middle fw-semibold">{{ $shift->kode_shift }}</td>
                                <td class="align-middle">{{ $shift->nama_shift }}</td>
                                <td class="align-middle">{{ $shift->jam_masuk ? substr($shift->jam_masuk, 0, 5) : '-' }}</td>
                                <td class="align-middle">{{ $shift->jam_pulang ? substr($shift->jam_pulang, 0, 5) : '-' }}</td>
                                <td class="align-middle">
                                    <span class="badge bg-primary rounded-pill">{{ $shift->jadwal_kerja_count + $shift->karyawan_count }}</span>
                                </td>
                                <td class="align-middle">
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="{{ route('shift.edit', $shift->id_shift) }}"
                                           class="btn btn-outline-secondary" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form action="{{ route('shift.destroy', $shift->id_shift) }}" method="POST"
                                              onsubmit="return confirm('Yakin ingin menghapus shift ini?')">
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
                    <div class="text-muted">Belum ada data shift</div>
                    <a href="{{ route('shift.create') }}" class="btn btn-primary mt-3">
                        <i class="bi bi-plus-circle me-2"></i>Tambah Shift Pertama
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
