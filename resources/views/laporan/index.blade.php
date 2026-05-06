@extends('layouts.app')

@section('title', 'Laporan - HRIS')

@section('content')
<div class="hris-container" style="max-width: 1400px;">

    <div class="row g-3 mb-4">
        <div class="col-12">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div>
                    <h2 class="mb-1">
                        <i class="bi bi-bar-chart-fill me-2" style="font-size: 1.5rem;"></i>Laporan Kehadiran
                    </h2>
                    <p class="text-muted small mb-0">Rekap data kehadiran dan cuti karyawan</p>
                </div>
                <form method="GET" class="d-flex align-items-center gap-2">
                    <label class="fw-semibold small">Bulan:</label>
                    <input type="month" name="bulan" value="{{ $bulan }}" class="form-control" style="max-width: 200px;">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-search me-1"></i>Tampilkan
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- Summary Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="hris-card">
                <div class="hris-card-body text-center">
                    <div style="font-size: 2rem; color: var(--hris-primary); margin-bottom: 8px;">
                        <i class="bi bi-people-fill"></i>
                    </div>
                    <div class="h3 mb-1">{{ $karyawanList->count() }}</div>
                    <div class="text-muted small">Total Karyawan</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="hris-card">
                <div class="hris-card-body text-center">
                    <div style="font-size: 2rem; color: #2ec4b6; margin-bottom: 8px;">
                        <i class="bi bi-check2-circle"></i>
                    </div>
                    <div class="h3 mb-1">{{ $absensiRekap->sum('hadir') }}</div>
                    <div class="text-muted small">Total Hadir</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="hris-card">
                <div class="hris-card-body text-center">
                    <div style="font-size: 2rem; color: #ff9800; margin-bottom: 8px;">
                        <i class="bi bi-clock-history"></i>
                    </div>
                    <div class="h3 mb-1">{{ $absensiRekap->sum('terlambat') }}</div>
                    <div class="text-muted small">Total Terlambat</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="hris-card">
                <div class="hris-card-body text-center">
                    <div style="font-size: 2rem; color: #ef4444; margin-bottom: 8px;">
                        <i class="bi bi-calendar-x"></i>
                    </div>
                    <div class="h3 mb-1">{{ $cutiRekap->sum('total_cuti') }}</div>
                    <div class="text-muted small">Total Cuti Disetujui</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Table --}}
    <div class="hris-card">
        <div class="hris-card-header">
            <h5 class="mb-0">
                <i class="bi bi-table me-2"></i>Detail Laporan Bulan {{ \Carbon\Carbon::parse($bulan)->isoFormat('MMMM YYYY') }}
            </h5>
        </div>
        <div class="hris-card-body p-0">
            @if($karyawanList->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover mb-0 datatable">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 50px;">No</th>
                                <th>Nama Karyawan</th>
                                <th>Jabatan</th>
                                <th>Divisi</th>
                                <th class="text-center">Hadir</th>
                                <th class="text-center">Terlambat</th>
                                <th class="text-center">Absen</th>
                                <th class="text-center">Cuti</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($karyawanList as $k)
                            @php
                                $absensi = $absensiRekap->get($k->id_karyawan);
                                $cuti = $cutiRekap->get($k->id_karyawan);
                            @endphp
                            <tr>
                                <td class="align-middle">{{ $loop->iteration }}</td>
                                <td class="align-middle">
                                    <div class="d-flex align-items-center gap-2">
                                        <div style="width: 34px; height: 34px; background: var(--hris-primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 13px;">
                                            {{ substr($k->nama, 0, 1) }}
                                        </div>
                                        <span class="fw-semibold small">{{ $k->nama }}</span>
                                    </div>
                                </td>
                                <td class="align-middle small">{{ $k->jabatan?->nama_jabatan ?? '-' }}</td>
                                <td class="align-middle small text-muted">{{ $k->devisi?->nama_devisi ?? '-' }}</td>
                                <td class="align-middle text-center">
                                    <span class="badge bg-success">{{ $absensi?->hadir ?? 0 }}</span>
                                </td>
                                <td class="align-middle text-center">
                                    <span class="badge bg-warning text-dark">{{ $absensi?->terlambat ?? 0 }}</span>
                                </td>
                                <td class="align-middle text-center">
                                    <span class="badge bg-danger">{{ $absensi?->absen ?? 0 }}</span>
                                </td>
                                <td class="align-middle text-center">
                                    <span class="badge bg-info text-dark">{{ $cuti?->total_cuti ?? 0 }}</span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-5">
                    <i class="bi bi-inbox" style="font-size: 3rem; color: var(--hris-border);"></i>
                    <div class="text-muted mt-3">Belum ada data karyawan</div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
