@extends('layouts.app')

@section('title', 'Detail Karyawan - HRIS')
@section('html_lang', 'id')

@section('head')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endsection

@section('content')
<div class="hris-container">
    <div class="row g-3 mb-3">
        <div class="col-12">
            <div class="d-flex align-items-center justify-content-between">
                <h2 class="mb-0">
                    <i class="bi bi-person-badge-fill me-2"></i>Detail Karyawan
                </h2>
                <div class="d-flex gap-2">
                    <a href="{{ route('karyawan.edit', $karyawan->id_karyawan) }}" class="btn btn-primary btn-sm">
                        <i class="bi bi-pencil me-1"></i>Edit
                    </a>
                    <a href="{{ route('karyawan.index') }}" class="btn btn-secondary btn-sm">
                        <i class="bi bi-arrow-left me-1"></i>Kembali
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        {{-- Info Karyawan --}}
        <div class="col-lg-4">
            <div class="hris-card h-100">
                <div class="hris-card-header">
                    <h5 class="mb-0"><i class="bi bi-person me-2"></i>Informasi Karyawan</h5>
                </div>
                <div class="hris-card-body text-center">
                    <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-3"
                         style="width: 80px; height: 80px; background: var(--hris-primary); color: white; font-size: 2rem; font-weight: bold;">
                        {{ substr($karyawan->nama, 0, 1) }}
                    </div>
                    <h4 class="mb-1">{{ $karyawan->nama }}</h4>
                    <p class="text-muted mb-3">{{ $karyawan->jabatan?->nama_jabatan ?? 'Jabatan belum ditentukan' }}</p>

                    <div class="text-start">
                        <div class="mb-2 d-flex justify-content-between">
                            <span class="text-muted small">Divisi</span>
                            <span class="fw-semibold small">{{ $karyawan->devisi?->nama_devisi ?? '-' }}</span>
                        </div>
                        <div class="mb-2 d-flex justify-content-between">
                            <span class="text-muted small">Tanggal Masuk</span>
                            <span class="fw-semibold small">
                                {{ $karyawan->tanggal_masuk ? \Carbon\Carbon::parse($karyawan->tanggal_masuk)->format('d M Y') : '-' }}
                            </span>
                        </div>
                        <div class="mb-2 d-flex justify-content-between">
                            <span class="text-muted small">Akun User</span>
                            <span class="fw-semibold small">{{ $karyawan->user?->username ?? '-' }}</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted small">Status Wajah</span>
                            @if($karyawan->face_embedding)
                                <span class="badge bg-success">Terdaftar</span>
                            @else
                                <span class="badge bg-warning">Belum Daftar</span>
                            @endif
                        </div>
                        <div class="mt-2 d-flex justify-content-between">
                            <span class="text-muted small">Kuota Cuti</span>
                            <span class="fw-semibold small">{{ $karyawan->remaining_leave_quota ?? 0 }}/{{ $karyawan->yearly_leave_quota ?? 0 }} hari</span>
                        </div>
                    </div>

                    <div class="mt-4">
                        <a href="{{ route('karyawan.register-face', $karyawan->id_karyawan) }}"
                           class="btn btn-outline-info btn-sm w-100">
                            <i class="bi bi-camera me-1"></i>
                            {{ $karyawan->face_embedding ? 'Update Wajah' : 'Daftar Wajah' }}
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- Riwayat Absensi Terbaru --}}
        <div class="col-lg-8">
            <div class="hris-card mb-3">
                <div class="hris-card-header">
                    <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i>Riwayat Absensi Terbaru</h5>
                </div>
                <div class="hris-card-body p-0">
                    @php $absensiTerbaru = $karyawan->absensi->sortByDesc('tanggal')->take(10); @endphp
                    @if($absensiTerbaru->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Tanggal</th>
                                        <th>Jam Masuk</th>
                                        <th>Jam Pulang</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($absensiTerbaru as $absensi)
                                    <tr>
                                        <td class="small">{{ \Carbon\Carbon::parse($absensi->tanggal)->format('d M Y') }}</td>
                                        <td class="small">{{ $absensi->jam_masuk ?? '-' }}</td>
                                        <td class="small">{{ $absensi->jam_pulang ?? '-' }}</td>
                                        <td>
                                            @php
                                                $statusColors = [
                                                    'hadir' => 'success',
                                                    'terlambat' => 'warning',
                                                    'absen' => 'danger',
                                                ];
                                                $color = $statusColors[$absensi->status] ?? 'secondary';
                                            @endphp
                                            <span class="badge bg-{{ $color }}">{{ ucfirst($absensi->status) }}</span>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="bi bi-calendar-x text-muted" style="font-size: 2rem;"></i>
                            <p class="text-muted mt-2 mb-0 small">Belum ada riwayat absensi</p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Riwayat Cuti --}}
            <div class="hris-card">
                <div class="hris-card-header">
                    <h5 class="mb-0"><i class="bi bi-calendar-event me-2"></i>Riwayat Cuti</h5>
                </div>
                <div class="hris-card-body p-0">
                    @php $cutiList = $karyawan->cuti->sortByDesc('created_at')->take(5); @endphp
                    @if($cutiList->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Jenis</th>
                                        <th>Mulai</th>
                                        <th>Selesai</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($cutiList as $cuti)
                                    <tr>
                                        <td class="small">{{ $cuti->jenis_cuti }}</td>
                                        <td class="small">{{ \Carbon\Carbon::parse($cuti->tanggal_mulai)->format('d M Y') }}</td>
                                        <td class="small">{{ \Carbon\Carbon::parse($cuti->tanggal_selesai)->format('d M Y') }}</td>
                                        <td>
                                            @php
                                                $cutiColors = [
                                                    'approved' => 'success',
                                                    'pending' => 'warning',
                                                    'rejected' => 'danger',
                                                ];
                                                $color = $cutiColors[$cuti->status_persetujuan] ?? 'secondary';
                                            @endphp
                                            <span class="badge bg-{{ $color }}">{{ ucfirst($cuti->status_persetujuan) }}</span>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="bi bi-calendar-x text-muted" style="font-size: 2rem;"></i>
                            <p class="text-muted mt-2 mb-0 small">Belum ada riwayat cuti</p>
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
        if (!confirm('Yakin ingin menghapus data wajah karyawan ini?')) return;

        try {
            const response = await fetch(`/api/karyawan/${idKaryawan}/face`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Content-Type': 'application/json',
                },
            });
            const data = await response.json();
            if (data.success) {
                alert(data.message);
                location.reload();
            } else {
                alert('Gagal: ' + data.message);
            }
        } catch (error) {
            alert('Terjadi kesalahan: ' + error.message);
        }
    }
</script>
@endsection
