@extends('layouts.app')

@section('content')
<div class="hris-container" style="max-width: 1000px;">
    <div class="hris-card">
        <div class="hris-card-header">
            <h2 class="mb-0">Jadwal Kerja - {{ $karyawan->nama }}</h2>
        </div>

        <div class="hris-card-body">

            <!-- Info Karyawan -->
            <div class="p-3 bg-light border rounded-3 mb-4">
                <div class="row g-2">
                    <div class="col-md-4">
                        <strong>Nama:</strong> {{ $karyawan->nama }}
                    </div>
                    <div class="col-md-4">
                        <strong>Jabatan:</strong> {{ $karyawan->jabatan->nama_jabatan ?? '-' }}
                    </div>
                    <div class="col-md-4">
                        <strong>Divisi:</strong> {{ $karyawan->divisi }}
                    </div>
                </div>
            </div>

            <!-- Filter Bulan -->
            <form method="GET" class="d-flex flex-wrap gap-2 align-items-center mb-3">
                <label class="fw-semibold">Pilih Bulan:</label>
                <input type="month" name="bulan" value="{{ $bulan }}" class="form-control" style="max-width: 220px;">
                <button type="submit" class="btn btn-primary">Tampilkan</button>
            </form>

            <!-- Summary -->
            <div class="row g-3 mb-4">
                @php
                    $totalPagi = $jadwalList->where('jam_kerja', 'Pagi (07:00-15:00)')->count();
                    $totalSiang = $jadwalList->where('jam_kerja', 'Siang (15:00-23:00)')->count();
                    $totalMalam = $jadwalList->where('jam_kerja', 'Malam (23:00-07:00)')->count();
                    $totalLibur = $jadwalList->where('jam_kerja', 'Libur')->count();
                @endphp
                <div class="col-6 col-lg-3">
                    <div class="p-3 rounded-3 text-center text-white" style="background: #4CAF50;">
                        <h3 class="mb-0">{{ $totalPagi }}</h3>
                        <small>Shift Pagi</small>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="p-3 rounded-3 text-center text-white" style="background: #FF9800;">
                        <h3 class="mb-0">{{ $totalSiang }}</h3>
                        <small>Shift Siang</small>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="p-3 rounded-3 text-center text-white" style="background: #2196F3;">
                        <h3 class="mb-0">{{ $totalMalam }}</h3>
                        <small>Shift Malam</small>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="p-3 rounded-3 text-center text-white" style="background: #f44336;">
                        <h3 class="mb-0">{{ $totalLibur }}</h3>
                        <small>Hari Libur</small>
                    </div>
                </div>
            </div>

            <!-- Tabel Jadwal -->
            <div class="table-responsive">
                <table class="table table-hover hris-table align-middle">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Hari</th>
                            <th>Jam Kerja</th>
                            <th>Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($jadwalList as $jadwal)
                        <tr>
                            <td>
                                {{ $jadwal->tanggal->format('d/m/Y') }}
                            </td>
                            <td>
                                {{ $jadwal->tanggal->isoFormat('dddd') }}
                            </td>
                            <td>
                                <span class="text-white px-2 py-1 rounded-2 fw-semibold" style="background: {{ $jadwal->shift_color }};">
                                    {{ $jadwal->jam_kerja }}
                                </span>
                            </td>
                            <td>
                                {{ $jadwal->keterangan ?? '-' }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">
                                Belum ada jadwal untuk bulan ini
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Back Button -->
            <a href="{{ route('jadwal.index') }}" class="btn hris-btn hris-btn-secondary w-100 mt-3">
                ← Kembali ke Jadwal Semua Karyawan
            </a>
        </div>
    </div>
</div>
@endsection
