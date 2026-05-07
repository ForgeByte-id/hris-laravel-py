@extends('layouts.app')

@section('content')
<div class="hris-container">
    @if(session('success'))
    <div class="alert alert-success">
        ✅ {{ session('success') }}
    </div>
    @endif

    <div class="hris-card">
        <div class="hris-card-header">
            <h2 class="mb-0">Approval Pengajuan Cuti</h2>
        </div>

        <div class="hris-card-body">

            @if($cutiList->count() > 0)
            <div class="alert alert-warning d-flex gap-2 align-items-start" role="alert">
                <span>📋</span>
                <div>
                    Terdapat <strong>{{ $cutiList->count() }}</strong> pengajuan cuti yang menunggu persetujuan
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover hris-table align-middle">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Karyawan</th>
                            <th>Jabatan</th>
                            <th>Jenis Cuti</th>
                            <th>Tanggal</th>
                            <th>Durasi</th>
                            <th>Kuota</th>
                            <th>Keterangan</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($cutiList as $index => $cuti)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>
                                <strong>{{ $cuti->karyawan->nama }}</strong>
                            </td>
                            <td>
                                {{ $cuti->karyawan->jabatan->nama_jabatan ?? '-' }}
                            </td>
                            <td>
                                <span class="badge text-bg-info">
                                    {{ $cuti->jenis_cuti }}
                                </span>
                            </td>
                            <td>
                                {{ $cuti->tanggal_mulai->format('d/m/Y') }}<br>
                                <small class="text-muted">s/d {{ $cuti->tanggal_selesai->format('d/m/Y') }}</small>
                            </td>
                            <td>
                                <strong>{{ $cuti->jumlah_hari }}</strong> hari
                            </td>
                            <td>
                                <small class="text-muted">
                                    Sisa: {{ $cuti->karyawan->remaining_leave_quota ?? 0 }} hari
                                </small>
                            </td>
                            <td class="text-truncate" style="max-width: 200px;">
                                <small class="text-muted">{{ $cuti->keterangan ?? '-' }}</small>
                            </td>
                            <td class="text-center">
                                <form action="{{ route('cuti.update-status', $cuti->id_cuti) }}" method="POST" class="d-inline-block">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="status" value="approved">
                                    <button type="submit" class="btn btn-sm btn-success"
                                            onclick="return confirm('Setujui pengajuan cuti ini?')">
                                        ✅ Setujui
                                    </button>
                                </form>
                                <form action="{{ route('cuti.update-status', $cuti->id_cuti) }}" method="POST" class="d-inline-block">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="status" value="rejected">
                                    <button type="submit" class="btn btn-sm btn-danger"
                                            onclick="return confirm('Tolak pengajuan cuti ini?')">
                                        ❌ Tolak
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div class="text-center py-5 text-muted">
                <h3>✅ Tidak Ada Pengajuan Pending</h3>
                <p class="mb-0">Semua pengajuan cuti sudah diproses</p>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
