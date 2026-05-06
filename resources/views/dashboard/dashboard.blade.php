@extends('layouts.app')

@php
    $user = auth()->user();
    $karyawan = $user->hasRole('admin') ? null : \App\Models\Karyawan::where('id_user', $user->id_user)->first();
    $isAdmin = $user->hasRole('admin');
    $isHr = $user->hasRole('hr');
@endphp

@section('content')
<div class="hris-container">
    <div class="row g-3 align-items-center mb-4">
        <div class="col-12">
            <div class="hris-card">
                <div class="hris-card-body d-flex align-items-center gap-3 flex-wrap">
                    <div class="avatar">
                        <img src="{{ asset('assets/img/sample/avatar/avatar1.jpg') }}" alt="avatar" class="imaged w64 rounded">
                    </div>

                    <div class="flex-grow-1">
                        @if ($karyawan)
                        <h2 class="h4 mb-1">{{ $karyawan->nama ?? $user->username }}</h2>
                            <span class="text-muted small">
                                {{ $karyawan->jabatan?->nama_jabatan ?? 'Karyawan' }} - {{ $karyawan->devisi?->nama_devisi ?? 'No Division' }}
                            </span>
                        @endif
                    </div>

                    <div class="text-end">
                        <div class="text-muted small">{{ now()->format('l, d M Y') }}</div>
                        <div class="h5 mb-0 mt-2" id="current-time" style="font-weight: 600;">
                            {{ now()->format('H:i:s') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if ($karyawan)
    <div class="row g-3 mb-3">
        <div class="col-12">
            <div class="hris-card">
                <div class="hris-card-header">
                    <h5 class="mb-0">
                        <i class="bi bi-clock-history me-2"></i>Absensi Hari Ini
                    </h5>
                </div>
                <div class="hris-card-body">
                    <div id="attendance-status" class="text-center py-3">
                        <p class="text-muted mb-3">Loading...</p>
                    </div>
                    <div class="row g-2 mt-3">
                        <div class="col-6">
                            <button type="button" class="btn btn-primary w-100" onclick="redirectToAttendance()" id="btn-clock-in">
                                <i class="bi bi-clock me-2"></i>Clock In
                            </button>
                        </div>
                        <div class="col-6">
                            <button type="button" class="btn btn-outline-primary w-100 disabled" onclick="redirectToAttendance()" id="btn-clock-out" disabled>
                                <i class="bi bi-arrow-right me-2"></i>Clock Out
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    @if ($isAdmin)
    <div class="row g-3 mb-3" id="attendance-summary">
    </div>
    @endif

    @if ($isAdmin)
    <div class="row g-3 mt-1">
        <div class="col-12">
            <div class="hris-card">
                <div class="hris-card-header">
                    <h5 class="mb-0">
                        <i class="bi bi-bar-chart me-2"></i>Rekap Kehadiran
                    </h5>
                </div>
                <div class="hris-card-body">
                    <div id="chartdiv"></div>
                </div>
            </div>
        </div>
    </div>
    @endif

    @if ($karyawan || $isAdmin || $isHr)
    <div class="row g-3 mt-3">
        <div class="col-12">
            <div class="hris-card">
                <div class="hris-card-header">
                    <h5 class="mb-0">
                        <i class="bi bi-list-check me-2"></i>Riwayat Kehadiran (7 Hari Terakhir)
                    </h5>
                </div>
                <div class="hris-card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Masuk</th>
                                    <th>Pulang</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody id="attendance-history-tbody">
                                <tr><td colspan="4" class="text-center text-muted py-3">Loading...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection

@section('scripts')
<script>
    const karyawanId = @json($karyawan?->id_karyawan);
    const isAdmin = @json($isAdmin);
    const isHr = @json($isHr);
    const canViewSummary = isAdmin;

    setInterval(() => {
        const timeEl = document.getElementById('current-time');
        if (timeEl) {
            timeEl.textContent = new Date().toLocaleTimeString('id-ID', {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            });
        }
    }, 1000);

    if (karyawanId) {
        loadAttendanceStatus();
        loadAttendanceHistory();
    }

    if (canViewSummary) {
        loadTodaysSummary();
    }

    function setAttendanceButtons(clockInEnabled, clockOutEnabled) {
        const btnClockIn = document.getElementById('btn-clock-in');
        const btnClockOut = document.getElementById('btn-clock-out');

        if (btnClockIn) {
            btnClockIn.disabled = !clockInEnabled;
            btnClockIn.classList.toggle('disabled', !clockInEnabled);
        }

        if (btnClockOut) {
            btnClockOut.disabled = !clockOutEnabled;
            btnClockOut.classList.toggle('disabled', !clockOutEnabled);
        }
    }

    async function fetchJson(url, options = {}) {
        const res = await fetch(url, options);
        const contentType = res.headers.get('content-type') || '';
        const raw = await res.text();

        let data = null;
        if (contentType.includes('application/json') && raw) {
            try {
                data = JSON.parse(raw);
            } catch (e) {
                data = null;
            }
        }

        if (!res.ok) {
            const message = data?.error || data?.message || `Request gagal (${res.status})`;
            throw new Error(message);
        }

        return data ?? {};
    }

    async function loadAttendanceStatus() {
        try {
            const data = await fetchJson(`/api/attendance/current-status/${karyawanId}`);

            const statusDiv = document.getElementById('attendance-status');
            if (!statusDiv) return;

            if (data.clock_in && data.clock_out) {
                statusDiv.innerHTML = `
                    <div>
                        <p class="mb-2">
                            <i class="bi bi-check-circle text-success" style="font-size: 2rem;"></i>
                        </p>
                        <p class="mb-1"><strong>Absensi Selesai</strong></p>
                        <p class="text-muted small">Masuk: ${data.clock_in} | Pulang: ${data.clock_out}</p>
                    </div>
                `;
                setAttendanceButtons(false, false);
            } else if (data.clock_in) {
                statusDiv.innerHTML = `
                    <div>
                        <p class="mb-2">
                            <i class="bi bi-hourglass-split text-warning" style="font-size: 2rem;"></i>
                        </p>
                        <p class="mb-1"><strong>Menunggu Clock Out</strong></p>
                        <p class="text-muted small">Masuk: ${data.clock_in}</p>
                        <p class="badge bg-${data.status === 'terlambat' ? 'warning' : 'success'}">
                            ${data.status === 'terlambat' ? 'Terlambat' : 'Tepat Waktu'}
                        </p>
                    </div>
                `;
                setAttendanceButtons(false, true);
            } else {
                statusDiv.innerHTML = `
                    <div>
                        <p class="mb-0"><strong>Siap Absensi</strong></p>
                    </div>
                `;
                setAttendanceButtons(true, false);
            }
        } catch (err) {
            console.error('Error loading attendance status:', err);
            const statusDiv = document.getElementById('attendance-status');
            if (statusDiv) {
                statusDiv.innerHTML = '<p class="text-danger">Gagal memuat status</p>';
            }
            setAttendanceButtons(false, false);
        }
    }

    async function loadTodaysSummary() {
        try {
            const data = await fetchJson('/api/attendance/todays-summary');

            const summaryDiv = document.getElementById('attendance-summary');
            if (!summaryDiv) return;

            summaryDiv.innerHTML = `
                <div class="col-md-4">
                    <div class="hris-card">
                        <div class="hris-card-body text-center">
                            <div style="font-size: 2rem; color: var(--hris-primary); margin-bottom: 8px;">
                                <i class="bi bi-person-check"></i>
                            </div>
                            <div class="h3 mb-1">${data.total_present}</div>
                            <div class="text-muted small">Hadir</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="hris-card">
                        <div class="hris-card-body text-center">
                            <div style="font-size: 2rem; color: #ff9800; margin-bottom: 8px;">
                                <i class="bi bi-exclamation-circle"></i>
                            </div>
                            <div class="h3 mb-1">${data.total_late}</div>
                            <div class="text-muted small">Terlambat</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="hris-card">
                        <div class="hris-card-body text-center">
                            <div style="font-size: 2rem; color: #f44336; margin-bottom: 8px;">
                                <i class="bi bi-person-x"></i>
                            </div>
                            <div class="h3 mb-1">${data.total_absent}</div>
                            <div class="text-muted small">Tidak Hadir</div>
                        </div>
                    </div>
                </div>
            `;
        } catch (err) {
            console.error('Error loading summary:', err);
        }
    }

    async function loadAttendanceHistory() {
        try {
            const data = await fetchJson(`/api/attendance/history/${karyawanId}?days=7`);

            const tbody = document.getElementById('attendance-history-tbody');
            if (!tbody) return;

            if (!Array.isArray(data) || data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted py-3">Tidak ada data</td></tr>';
                return;
            }

            tbody.innerHTML = data.map(record => {
                const statusBadge = record.status === 'terlambat'
                    ? '<span class="badge bg-warning">Terlambat</span>'
                    : '<span class="badge bg-success">Tepat Waktu</span>';

                return `
                    <tr>
                        <td class="small">${new Date(record.tanggal).toLocaleDateString('id-ID')}</td>
                        <td class="small">${record.jam_masuk || '-'}</td>
                        <td class="small">${record.jam_pulang || '-'}</td>
                        <td>${statusBadge}</td>
                    </tr>
                `;
            }).join('');
        } catch (err) {
            console.error('Error loading attendance history:', err);
            const tbody = document.getElementById('attendance-history-tbody');
            if (tbody) {
                tbody.innerHTML = '<tr><td colspan="4" class="text-center text-danger py-3">Gagal memuat riwayat</td></tr>';
            }
        }
    }

    function redirectToAttendance() {
        window.location.href = '/attendance';
    }
</script>
@endsection
