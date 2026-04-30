@extends('layouts.app')

@section('content')
<div class="container cuti-container">

    @if(session('success'))
    <div class="alert alert-success">
        ✅ {{ session('success') }}
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-error">
        ❌ {{ session('error') }}
    </div>
    @endif

    <div class="card">
        <div class="card-header card-header-custom">
            <h2 class="card-title">Pengajuan Cuti Saya</h2>
            <a href="{{ route('cuti.create') }}" class="btn-primary-custom">
                ➕ Ajukan Cuti Baru
            </a>
        </div>

        <div class="card-body card-body-custom">

            <!-- Summary -->
            <div class="summary-grid">
                <div class="summary-card summary-blue">
                    <h4>Total Pengajuan</h4>
                    <h2>{{ $cutiList->count() }}</h2>
                </div>
                <div class="summary-card summary-yellow">
                    <h4>Menunggu</h4>
                    <h2>{{ $cutiList->where('status_persetujuan', 'pending')->count() }}</h2>
                </div>
                <div class="summary-card summary-green">
                    <h4>Disetujui</h4>
                    <h2>{{ $cutiList->where('status_persetujuan', 'approved')->count() }}</h2>
                </div>
                <div class="summary-card summary-red">
                    <h4>Ditolak</h4>
                    <h2>{{ $cutiList->where('status_persetujuan', 'rejected')->count() }}</h2>
                </div>
            </div>

            <!-- Table -->
            @if($cutiList->count() > 0)
            <div class="table-wrapper">
                <table class="cuti-table">
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
                            <td><strong>{{ $cuti->jenis_cuti }}</strong></td>
                            <td>
                                {{ $cuti->tanggal_mulai->format('d/m/Y') }} - 
                                {{ $cuti->tanggal_selesai->format('d/m/Y') }}
                            </td>
                            <td>{{ $cuti->jumlah_hari }} hari</td>
                            <td>
                                @if($cuti->status_persetujuan === 'pending')
                                    <span class="badge badge-pending">⏳ Menunggu</span>
                                @elseif($cuti->status_persetujuan === 'approved')
                                    <span class="badge badge-approved">✅ Disetujui</span>
                                @else
                                    <span class="badge badge-rejected">❌ Ditolak</span>
                                @endif
                            </td>
                            <td>
                                <div class="action-group">
                                    <a href="{{ route('cuti.show', $cuti->id_cuti) }}" class="action-btn btn-view">
                                        👁️
                                    </a>

                                    @if($cuti->status_persetujuan === 'pending')
                                    <form action="{{ route('cuti.cancel', $cuti->id_cuti) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="action-btn btn-delete"
                                            onclick="return confirm('Yakin ingin membatalkan pengajuan ini?')">
                                            🗑️
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
            <div class="empty-state">
                <h3>Belum Ada Pengajuan Cuti</h3>
                <p>Klik tombol "Ajukan Cuti Baru" untuk membuat pengajuan</p>
            </div>
            @endif

        </div>
    </div>
</div>
@endsection
