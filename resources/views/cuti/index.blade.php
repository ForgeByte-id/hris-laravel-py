@extends('layouts.app')

@section('content')
<div class="hris-container">

    @if(session('success'))
    <div class="alert alert-success">
        <i class="bi bi-check-circle me-1"></i>
        {{ session('success') }}
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger">
        <i class="bi bi-x-circle me-1"></i>
        {{ session('error') }}
    </div>
    @endif

    <div class="hris-card">

        <div class="hris-card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h2 class="mb-0">Pengajuan Cuti Saya</h2>

            <a href="{{ route('cuti.create') }}" class="btn hris-btn hris-btn-primary">
                <i class="bi bi-plus-circle me-1"></i>
                Ajukan Cuti Baru
            </a>
        </div>

        <div class="hris-card-body">

            <!-- SUMMARY -->
            <div class="row g-3 mb-4">

                <div class="col-12 col-md-6 col-xl-3">
                    <div class="p-3 rounded-3 bg-primary text-white shadow-sm">
                        <h6 class="mb-1 opacity-75">Total Pengajuan</h6>
                        <h3 class="mb-0 fw-bold">{{ $cutiList->count() }}</h3>
                    </div>
                </div>

                <div class="col-12 col-md-6 col-xl-3">
                    <div class="p-3 rounded-3 bg-warning text-dark shadow-sm">
                        <h6 class="mb-1 opacity-75">Menunggu</h6>
                        <h3 class="mb-0 fw-bold">
                            {{ $cutiList->where('status_persetujuan', 'pending')->count() }}
                        </h3>
                    </div>
                </div>

                <div class="col-12 col-md-6 col-xl-3">
                    <div class="p-3 rounded-3 bg-success text-white shadow-sm">
                        <h6 class="mb-1 opacity-75">Disetujui</h6>
                        <h3 class="mb-0 fw-bold">
                            {{ $cutiList->where('status_persetujuan', 'approved')->count() }}
                        </h3>
                    </div>
                </div>

                <div class="col-12 col-md-6 col-xl-3">
                    <div class="p-3 rounded-3 bg-danger text-white shadow-sm">
                        <h6 class="mb-1 opacity-75">Ditolak</h6>
                        <h3 class="mb-0 fw-bold">
                            {{ $cutiList->where('status_persetujuan', 'rejected')->count() }}
                        </h3>
                    </div>
                </div>

            </div>

            <!-- TABLE -->
            @if($cutiList->count() > 0)

            <div class="table-responsive">
                <table class="table table-hover hris-table align-middle">

                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Jenis Cuti</th>
                            <th>Tanggal</th>
                            <th>Durasi</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach($cutiList as $index => $cuti)
                        <tr>
                            <td>{{ $index + 1 }}</td>

                            <td>
                                <strong>{{ $cuti->jenis_cuti }}</strong>
                            </td>

                            <td>
                                {{ $cuti->tanggal_mulai->format('d/m/Y') }} -
                                {{ $cuti->tanggal_selesai->format('d/m/Y') }}
                            </td>

                            <td>
                                {{ $cuti->jumlah_hari }} hari
                            </td>

                            <td>
                                @if($cuti->status_persetujuan === 'pending')
                                    <span class="badge text-bg-warning">
                                        <i class="bi bi-hourglass-split me-1"></i>
                                        Menunggu
                                    </span>
                                @elseif($cuti->status_persetujuan === 'approved')
                                    <span class="badge text-bg-success">
                                        <i class="bi bi-check-lg me-1"></i>
                                        Disetujui
                                    </span>
                                @else
                                    <span class="badge text-bg-danger">
                                        <i class="bi bi-x-lg me-1"></i>
                                        Ditolak
                                    </span>
                                @endif
                            </td>

                            <td>
                                <div class="d-flex gap-2">

                                    <a href="{{ route('cuti.show', $cuti->id_cuti) }}"
                                       class="btn btn-sm btn-primary">
                                        <i class="bi bi-eye"></i>
                                    </a>

                                    @if($cuti->status_persetujuan === 'pending')
                                    <form action="{{ route('cuti.cancel', $cuti->id_cuti) }}" method="POST">
                                        @csrf
                                        @method('DELETE')

                                        <button type="submit"
                                                class="btn btn-sm btn-danger"
                                                onclick="return confirm('Yakin ingin membatalkan pengajuan ini?')">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                    @endif

                                </div>
                            </td>

                        </tr>
                        @endforeach
                    </tbody>

                </table>
            </div>

            @else

            <div class="text-center py-5 text-muted">
                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                <h3>Belum Ada Pengajuan Cuti</h3>
                <p class="mb-0">Klik tombol "Ajukan Cuti Baru" untuk membuat pengajuan</p>
            </div>

            @endif

        </div>
    </div>
</div>
@endsection
