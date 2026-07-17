@extends('layouts.app')

@section('content')
<div class="hris-container" style="max-width: 1400px;">

    @if(session('success'))
    <div class="alert alert-success d-flex gap-2 align-items-start">
        <i class="bi bi-check-circle-fill" style="font-size: 1.1rem; flex-shrink: 0;"></i>
        <div>{{ session('success') }}</div>
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger d-flex gap-2 align-items-start">
        <i class="bi bi-x-circle-fill" style="font-size: 1.1rem; flex-shrink: 0;"></i>
        <div>{{ session('error') }}</div>
    </div>
    @endif

    <div class="hris-card">
        <div class="hris-card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h2 class="mb-0">Jadwal Kerja Karyawan</h2>
            <div class="d-flex flex-wrap gap-2">
                @can('create-jadwal')
                <a href="{{ route('jadwal.create') }}" class="btn btn-primary">
                    Tambah Jadwal
                </a>
                @endcan
                @can('bulk-create-jadwal')
                <a href="{{ route('jadwal.bulk-create') }}" class="btn btn-success">
                    Input Massal
                </a>
                @endcan
            </div>
        </div>

        <div class="hris-card-body">

            <!-- Debug Info (uncomment jika perlu debug) -->
            {{--
            <div style="background: #fff3cd; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                <strong>Debug Info:</strong><br>
                Total Karyawan: {{ $karyawanList->count() }}<br>
                Total Jadwal: {{ $jadwalList->flatten()->count() }}<br>
                Bulan: {{ $bulan }}<br>
                Tanggal Awal: {{ $tanggalAwal->format('Y-m-d') }}<br>
                Tanggal Akhir: {{ $tanggalAkhir->format('Y-m-d') }}
            </div>
            --}}

            <!-- Filter Bulan -->
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
                <form method="GET" class="d-flex flex-wrap gap-2 align-items-center">
                    <label class="fw-semibold">Pilih Bulan:</label>
                    <input type="month" name="bulan" value="{{ $bulan }}" class="form-control" style="max-width: 220px;">
                    <button type="submit" class="btn btn-primary">Tampilkan</button>
                </form>

                @can('set-libur-massal')
                <button onclick="showLiburModal()" class="btn btn-danger">
                    Set Libur Massal
                </button>
                @endcan
            </div>

            <!-- Legend -->
            <div class="d-flex flex-wrap gap-3 mb-3 p-3 bg-light rounded-3">
                @forelse($shiftLegend as $shift)
                    <div>
                        <span class="badge" style="background: {{ $shift->color_hex }};">{{ $shift->id_shift }}</span>
                        = {{ $shift->label }}
                    </div>
                @empty
                    <div class="text-muted small">Belum ada data shift</div>
                @endforelse
            </div>

            @php
                $cutiAktif = $cutiList->flatten();
            @endphp
            @if($cutiAktif->count() > 0)
                <div class="alert alert-info">
                    <strong>Karyawan Cuti Bulan Ini:</strong>
                    {{ $cutiAktif->map(fn($cuti) => ($cuti->karyawan->nama ?? '-') . ' (' . $cuti->tanggal_mulai->format('d/m') . ' - ' . $cuti->tanggal_selesai->format('d/m') . ')')->implode(', ') }}
                </div>
            @endif

            <!-- Tabel Jadwal -->
            @if($karyawanList->count() > 0)
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                    <thead>
                        <tr style="background: #f8f9fa;">
                            <th style="padding: 12px; border: 1px solid #dee2e6; text-align: left; position: sticky; left: 0; background: #f8f9fa; z-index: 10; min-width: 150px;">
                                Nama Karyawan
                            </th>
                            @php
                                $currentDate = $tanggalAwal->copy();
                            @endphp
                            @while($currentDate->lte($tanggalAkhir))
                            <th style="padding: 8px; border: 1px solid #dee2e6; text-align: center; min-width: 60px; {{ $currentDate->isWeekend() ? 'background: #ffe0e0;' : '' }}">
                                <div>{{ $currentDate->format('d') }}</div>
                                <small style="color: #666;">{{ $currentDate->isoFormat('dd') }}</small>
                            </th>
                            @php
                                $currentDate->addDay();
                            @endphp
                            @endwhile
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($karyawanList as $karyawan)
                        <tr>
                            <td style="padding: 12px; border: 1px solid #dee2e6; position: sticky; left: 0; background: white; z-index: 9;">
                                <strong>{{ $karyawan->nama }}</strong><br>
                                <small style="color: #666;">{{ $karyawan->jabatan->nama_jabatan ?? '-'}}</small>
                            </td>
                            @php
                                $jadwalKaryawan = $jadwalList->get($karyawan->id_karyawan, collect());
                                $absensiKaryawan = $absensiList->get($karyawan->id_karyawan, collect());
                                $cutiKaryawan = $cutiList->get($karyawan->id_karyawan, collect());
                                $currentDate = $tanggalAwal->copy();
                            @endphp
                            @while($currentDate->lte($tanggalAkhir))
                                @php
                                    $tanggalString = $currentDate->format('Y-m-d');
                                    $jadwal = $jadwalKaryawan->first(function($item) use ($tanggalString) {
                                        return $item->tanggal->format('Y-m-d') === $tanggalString;
                                    });
                                    $absensi = $absensiKaryawan->first(function($item) use ($tanggalString) {
                                        return $item->tanggal->format('Y-m-d') === $tanggalString;
                                    });
                                    $cuti = $cutiKaryawan->first(function($item) use ($tanggalString) {
                                        return $item->tanggal_mulai->format('Y-m-d') <= $tanggalString
                                            && $item->tanggal_selesai->format('Y-m-d') >= $tanggalString;
                                    });
                                @endphp
                                <td style="padding: 5px; border: 1px solid #dee2e6; text-align: center; {{ $currentDate->isWeekend() ? 'background: #ffe0e0;' : '' }}">
                                    @if($cuti)
                                        <span title="Cuti {{ $cuti->jenis_cuti }}" style="display: block; color: white; background: #6f42c1; padding: 8px; border-radius: 5px; font-weight: 600;">
                                            C
                                        </span>
                                    @elseif($jadwal)
                                        @can('edit-jadwal')
                                        <a href="{{ route('jadwal.edit', $jadwal->id_jadwal) }}"
                                           title="{{ $jadwal->id_shift }}"
                                           style="display: block; text-decoration: none; color: white; background: {{ $jadwal->shift_color }}; padding: 8px; border-radius: 5px; font-weight: 600;">
                                            {{ $jadwal->shift_short }}
                                        </a>
                                        @else
                                        <span title="{{ $jadwal->id_shift }}"
                                              style="display: block; color: white; background: {{ $jadwal->shift_color }}; padding: 8px; border-radius: 5px; font-weight: 600;">
                                            {{ $jadwal->shift_short }}
                                        </span>
                                        @endcan
                                    @elseif($absensi)
                                        <span title="Absensi {{ $absensi->status }}" style="display: block; color: white; background: {{ $absensi->status === 'terlambat' ? '#ffc107' : '#212529' }}; padding: 8px; border-radius: 5px; font-weight: 600;">
                                            H
                                        </span>
                                    @else
                                        <span style="color: #ccc;">-</span>
                                    @endif
                                </td>
                                @php
                                    $currentDate->addDay();
                                @endphp
                            @endwhile
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div class="text-center py-5 text-muted">
                <h3>Tidak Ada Data Karyawan</h3>
                <p class="mb-0">Silakan tambahkan data karyawan terlebih dahulu</p>
            </div>
            @endif
        </div>
    </div>
</div>

<!-- Modal Set Libur Massal -->
<div id="liburModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
    <div style="background: white; max-width: 500px; margin: 100px auto; padding: 30px; border-radius: 15px;">
        <h3 style="margin-bottom: 20px;">🏖️ Set Libur Massal</h3>
        <form action="{{ route('jadwal.libur-massal') }}" method="POST">
            @csrf
            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 600;">Tanggal Libur:</label>
                <input type="date" name="tanggal" required
                       style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px;">
            </div>

            <div style="display: flex; gap: 10px;">
                <button type="submit"
                        style="flex: 1; padding: 12px; background: #dc3545; color: white; border: none; border-radius: 8px; cursor: pointer;">
                    Set Libur
                </button>
                <button type="button" onclick="hideLiburModal()"
                        style="flex: 1; padding: 12px; background: #6c757d; color: white; border: none; border-radius: 8px; cursor: pointer;">
                    Batal
                </button>
            </div>
        </form>
    </div>
</div>

@endsection

@section('scripts')
<script>
function showLiburModal() {
    document.getElementById('liburModal').style.display = 'block';
}

function hideLiburModal() {
    document.getElementById('liburModal').style.display = 'none';
}

document.getElementById('liburModal').addEventListener('click', function(e) {
    if (e.target === this) {
        hideLiburModal();
    }
});
</script>
@endsection
