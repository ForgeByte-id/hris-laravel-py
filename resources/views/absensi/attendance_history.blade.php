@extends('layouts.app')

@section('title', 'Riwayat Absensi - HRIS')
@section('html_lang', 'id')
@section('show_loader', '0')
@section('show_bottom_nav', '0')
@section('use_app_capsule', '0')
@section('include_default_styles', '0')
@section('include_default_scripts', '0')
@section('show_chrome', '0')

@section('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/absensi.css') }}">
@endsection

@section('content')
    <div class="container">
        <button class="btn-back" onclick="window.location.href='/attendance'">← Kembali ke Absensi</button>

        <h1>Riwayat Absensi</h1>

        <form method="GET" action="{{ route('attendance.history') }}" class="filter-section">
            <div class="filter-group">
                <label>Tanggal</label>
                <input type="date" name="tanggal" value="{{ request('tanggal') }}">
            </div>

            <div class="filter-group">
                <label>Karyawan</label>
                <select name="id_karyawan">
                    <option value="">-- Semua Karyawan --</option>
                    @foreach($karyawanList as $k)
                        <option value="{{ $k->id_karyawan }}"
                            {{ request('id_karyawan') == $k->id_karyawan ? 'selected' : '' }}>
                            {{ $k->nama ?? 'Karyawan ' . $k->id_karyawan }}
                        </option>
                    @endforeach
                </select>
            </div>

            <button type="submit" class="btn-filter" style="display: flex; align-items: center; gap: 6px;"><i class="bi bi-search" style="font-size: 0.95rem;"></i> Cari</button>
        </form>

        @if($absensi->count() > 0)
        <table>
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Nama</th>
                    <th>Jam Masuk</th>
                    <th>Jam Pulang</th>
                    <th>Status</th>
                    <th>Dicatat Oleh</th>
                    <th>Verifikasi</th>
                    <th>GPS</th>
                </tr>
            </thead>
            <tbody>
                @foreach($absensi as $abs)
                <tr>
                    <td>{{ $abs->tanggal->format('d/m/Y') }}</td>
                    <td>{{ $abs->karyawan->nama ?? 'Karyawan ' . $abs->id_karyawan }}</td>
                    <td>{{ $abs->jam_masuk ?? '-' }}</td>
                    <td>{{ $abs->jam_pulang ?? '-' }}</td>
                    <td>
                        <span class="status-badge status-{{ $abs->status }}">
                            {{ $abs->status_label ?? ucfirst($abs->status ?? 'hadir') }}
                        </span>
                        @if($abs->is_locked)
                            <i class="bi bi-lock-fill text-muted ms-1" title="Terkunci"></i>
                        @endif
                    </td>
                    <td class="small text-muted">{{ $abs->recorder?->username ?? '-' }}</td>
                    <td>
                        @if($abs->face_verified)
                            <span class="badge bg-success" title="Akurasi: {{ $abs->face_confidence }}%">
                                <i class="bi bi-check-circle-fill me-1"></i>{{ number_format($abs->face_confidence, 1) }}%
                            </span>
                        @elseif($abs->status === 'tidak_hadir')
                            <span class="badge bg-secondary">N/A</span>
                        @else
                            <span class="badge bg-warning text-dark">Tidak Diverifikasi</span>
                        @endif
                    </td>
                    <td class="small">
                        @if($abs->gps_lat && $abs->gps_lng)
                            <a href="https://maps.google.com/?q={{ $abs->gps_lat }},{{ $abs->gps_lng }}"
                               target="_blank" class="text-decoration-none" title="{{ $abs->gps_lat }}, {{ $abs->gps_lng }}">
                                <i class="bi bi-geo-alt-fill text-success"></i>
                            </a>
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="pagination">
            {{ $absensi->links() }}
        </div>
        @else
        <div class="no-data">
            <h3>Tidak ada data absensi</h3>
            <p>Coba ubah filter atau lakukan absensi terlebih dahulu</p>
        </div>
        @endif
    </div>

    <div id="photoModal" class="modal" onclick="closeModal()">
        <div class="modal-content">
            <span class="close-modal">&times;</span>
            <img id="modalImage" src="" alt="Preview">
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        function showModal(imageUrl) {
            const modal = document.getElementById('photoModal');
            const modalImage = document.getElementById('modalImage');
            modalImage.src = imageUrl;
            modal.style.display = 'block';
        }

        function closeModal() {
            document.getElementById('photoModal').style.display = 'none';
        }

        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeModal();
            }
        });
    </script>
@endsection
