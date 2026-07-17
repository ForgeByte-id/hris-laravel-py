@extends('layouts.app')

@section('content')
<div class="hris-container">
    @if($isReadonlyApproval ?? false)
    <div class="alert alert-info d-flex gap-2 align-items-start">
        <i class="bi bi-info-circle-fill" style="font-size: 1.1rem; flex-shrink: 0;"></i>
        <div>Mode HR/HRD readonly. Anda dapat melihat pengajuan, tetapi tidak dapat approve/reject.</div>
    </div>
    @endif

    <div class="hris-card">
        <div class="hris-card-header">
            <h2 class="mb-0">Approval Pengajuan Cuti</h2>
        </div>

        <div class="hris-card-body">

            @if($cutiList->count() > 0)
            <div class="alert alert-warning d-flex gap-2 align-items-start" role="alert">
                <i class="bi bi-info-circle-fill" style="font-size: 1rem; flex-shrink: 0;"></i>
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
                            <th>Level</th>
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
                                {{ optional($cuti->karyawan->jabatan)->nama_jabatan ?? '-' }}
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
                                    @if(($quotaBalances[$cuti->id_cuti] ?? null))
                                        Sisa {{ $quotaBalances[$cuti->id_cuti]->leaveType->nama_cuti }}:
                                        {{ $quotaBalances[$cuti->id_cuti]->remaining_quota }} hari
                                    @else
                                        Kuota tidak ditemukan
                                    @endif
                                </small>
                            </td>
                            <td class="text-truncate" style="max-width: 200px;">
                                <small class="text-muted">{{ $cuti->keterangan ?? '-' }}</small>
                            </td>
                            <td>
                                <span class="badge bg-primary">{{ $levelLabels[$cuti->id_cuti] ?? 'Level' }}</span>
                            </td>
                            <td class="text-center">
                                @if(($approvalPermissions[$cuti->id_cuti] ?? false) === true)
                                    <form action="{{ route('cuti.update-status', $cuti->id_cuti) }}" method="POST" class="d-inline-block">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="status" value="approved">
                                        <button type="submit" class="btn btn-sm btn-success d-flex align-items-center gap-2"
                                                onclick="return confirm('Setujui pengajuan cuti ini?')">
                                            <i class="bi bi-check-circle-fill"></i> Setujui
                                        </button>
                                    </form>
                                    <form action="{{ route('cuti.update-status', $cuti->id_cuti) }}" method="POST" class="d-inline-block">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="status" value="rejected">
                                        <button type="submit" class="btn btn-sm btn-danger d-flex align-items-center gap-2"
                                                onclick="return confirm('Tolak pengajuan cuti ini?')">
                                            <i class="bi bi-x-circle-fill"></i> Tolak
                                        </button>
                                    </form>
                                @else
                                    <span class="badge bg-secondary">Readonly</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div class="text-center py-5 text-muted">
                <h3><i class="bi bi-check-circle-fill" style="color: #28a745; font-size: 2rem; display: block; margin-bottom: 0.5rem;"></i> Tidak Ada Pengajuan Pending</h3>
                <p class="mb-0">Semua pengajuan cuti sudah diproses</p>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
