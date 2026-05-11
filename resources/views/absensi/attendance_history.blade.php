@extends('layouts.app')

@section('title', 'Riwayat Absensi - HRIS')

@section('content')
<div class="hris-container">

    <div class="row g-3 mb-3">
        <div class="col-12">
            <div class="hris-card">
                <div class="hris-card-body d-flex align-items-center justify-content-between flex-wrap gap-3">
                    <div>
                        <h4 class="mb-1 fw-bold"><i class="bi bi-clock-history me-2 text-primary"></i>Riwayat Absensi</h4>
                        <div class="text-muted small">Daftar absensi karyawan — gunakan filter untuk mempersempit hasil</div>
                    </div>
                    <div class="d-flex gap-2 align-items-center">
                        <button class="btn btn-outline-secondary" onclick="window.location.href='/attendance'">
                            <i class="bi bi-arrow-left-circle me-1"></i>Kembali
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-12">
            <div class="hris-card">
                <div class="hris-card-body">
                    <form method="GET" action="{{ route('attendance.history') }}" class="row g-2 mb-3 align-items-end">
                        <div class="col-auto">
                            <label class="form-label small">Tanggal</label>
                            <input type="date" name="tanggal" class="form-control form-control-sm" value="{{ request('tanggal') }}">
                        </div>
                        <div class="col-auto">
                            <label class="form-label small">Karyawan</label>
                            <select name="id_karyawan" class="form-select form-select-sm">
                                <option value="">-- Semua Karyawan --</option>
                                @foreach($karyawanList as $k)
                                    <option value="{{ $k->id_karyawan }}" {{ request('id_karyawan') == $k->id_karyawan ? 'selected' : '' }}>
                                        {{ $k->nama ?? 'Karyawan ' . $k->id_karyawan }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-auto">
                            <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-search me-1"></i>Cari</button>
                        </div>
                    </form>

                    @if($absensi->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-striped table-hover align-middle">
                                <thead class="table-light">
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
                                                <span class="badge bg-light text-dark status-{{ $abs->status }}">{{ $abs->status_label ?? ucfirst($abs->status ?? 'hadir') }}</span>
                                                @if($abs->is_locked)
                                                    <i class="bi bi-lock-fill text-muted ms-1" title="Terkunci"></i>
                                                @endif
                                            </td>
                                            <td class="small text-muted">{{ $abs->recorder?->username ?? '-' }}</td>
                                            <td>
                                                @if($abs->face_verified)
                                                    <span class="badge bg-success"><i class="bi bi-check-circle-fill me-1"></i>{{ number_format($abs->face_confidence ?? 0, 1) }}%</span>
                                                @elseif($abs->status === 'tidak_hadir')
                                                    <span class="badge bg-secondary">N/A</span>
                                                @else
                                                    <span class="badge bg-warning text-dark">Tidak Diverifikasi</span>
                                                @endif
                                            </td>
                                            <td class="small">
                                                @if($abs->gps_lat && $abs->gps_lng)
                                                    <a href="https://maps.google.com/?q={{ $abs->gps_lat }},{{ $abs->gps_lng }}" target="_blank" class="text-decoration-none">
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
                        </div>

                        <div class="d-flex justify-content-end mt-3">
                            {{ $absensi->links() }}
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="bi bi-inbox fs-1 text-muted"></i>
                            <h5 class="mt-3">Tidak ada data absensi</h5>
                            <p class="text-muted">Coba ubah filter atau lakukan absensi terlebih dahulu.</p>
                        </div>
                    @endif

                </div>
            </div>
        </div>
    </div>

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
            if (event.key === 'Escape') closeModal();
        });
    </script>
@endsection
