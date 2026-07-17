@extends('layouts.app')

@section('content')
<div class="hris-container cuti-container">

    <div class="card hris-card">
        <div class="card-header card-header-custom hris-card-header d-flex justify-content-between align-items-center">
            <h2 class="card-title mb-0">Detail Pengajuan Cuti</h2>
            <a href="{{ route('cuti.index') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left me-1"></i>Kembali
            </a>
        </div>

        <div class="card-body card-body-custom hris-card-body">
            <div class="row mb-4">
                <div class="col-md-6">
                    <table class="table table-borderless">
                        <tr>
                            <th style="width: 140px">Jenis Cuti</th>
                            <td><strong>{{ $cuti->jenis_cuti }}</strong></td>
                        </tr>
                        <tr>
                            <th>Tanggal Mulai</th>
                            <td>{{ $cuti->tanggal_mulai->format('d/m/Y') }}</td>
                        </tr>
                        <tr>
                            <th>Tanggal Selesai</th>
                            <td>{{ $cuti->tanggal_selesai->format('d/m/Y') }}</td>
                        </tr>
                        <tr>
                            <th>Durasi</th>
                            <td>{{ $cuti->jumlah_hari }} hari</td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <table class="table table-borderless">
                        <tr>
                            <th style="width: 140px">Status</th>
                            <td>
                                @if($cuti->status_persetujuan === 'pending')
                                    <span class="badge text-bg-warning">
                                        <i class="bi bi-hourglass-split me-1"></i>Menunggu
                                    </span>
                                @elseif($cuti->status_persetujuan === 'approved')
                                    <span class="badge text-bg-success">
                                        <i class="bi bi-check-lg me-1"></i>Disetujui
                                    </span>
                                @else
                                    <span class="badge text-bg-danger">
                                        <i class="bi bi-x-lg me-1"></i>Ditolak
                                    </span>
                                @endif
                            </td>
                        </tr>
                        @if($cuti->keterangan)
                        <tr>
                            <th>Keterangan</th>
                            <td>{{ $cuti->keterangan }}</td>
                        </tr>
                        @endif
                        @if($cuti->atasan)
                        <tr>
                            <th>Disetujui/Ditolak oleh</th>
                            <td>{{ $cuti->atasan->nama }}</td>
                        </tr>
                        @endif
                        @if($cuti->tanggal_persetujuan)
                        <tr>
                            <th>Tanggal Proses</th>
                            <td>{{ $cuti->tanggal_persetujuan->format('d/m/Y H:i') }}</td>
                        </tr>
                        @endif
                    </table>
                </div>
            </div>

            @if($cuti->status_persetujuan === 'pending')
            <div class="d-flex gap-2">
                <form action="{{ route('cuti.cancel', $cuti->id_cuti) }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger"
                            onclick="return confirm('Yakin ingin membatalkan pengajuan ini?')">
                        <i class="bi bi-trash me-1"></i>Batalkan Pengajuan
                    </button>
                </form>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
