@extends('layouts.app')

@section('content')
<div class="hris-container">

    {{-- ── Profile / greeting card ─────────────────────────────────────── --}}
    <div class="row g-3 align-items-center mb-4">
        <div class="col-12">
            <div class="hris-card">
                <div class="hris-card-body d-flex align-items-center gap-3 flex-wrap">
                    <div>
                        <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center"
                             style="width:56px;height:56px;font-size:1.5rem;">
                            <i class="bi bi-person-fill"></i>
                        </div>
                    </div>

                    <div class="flex-grow-1">
                        @if ($karyawan)
                            <h2 class="h4 mb-1">{{ $karyawan->nama ?? $user->username }}</h2>
                            <span class="text-muted small">
                                {{ $karyawan->jabatan?->nama_jabatan ?? 'Karyawan' }}
                                &mdash; {{ $karyawan->divisi?->nama_divisi ?? 'Tanpa Divisi' }}
                            </span>
                        @else
                            <h2 class="h4 mb-1">{{ $user->username }}</h2>
                            <span class="text-muted small">Administrator</span>
                        @endif
                    </div>

                    <div class="text-end">
                        <div class="text-muted small"><i class="bi bi-calendar3 me-1"></i>{{ now()->format('l, d M Y') }}</div>
                        <div class="h5 mb-0 mt-2 fw-semibold" id="current-time">{{ now()->format('H:i:s') }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════════ --}}
    {{-- EMPLOYEE SECTIONS                                                  --}}
    {{-- ══════════════════════════════════════════════════════════════════ --}}
    @if ($karyawan)

        {{-- ── Quick-stats row ─────────────────────────────────────────── --}}
        <div class="row g-3 mb-3">

            {{-- Today's shift --}}
            <div class="col-md-4">
                <div class="hris-card h-100">
                    <div class="hris-card-body text-center">
                        <div class="mb-2" style="font-size:2rem;">
                            @if ($todayJadwal)
                                @if ($todayJadwal->isLibur())
                                    <i class="bi bi-moon-stars-fill text-danger"></i>
                                @else
                                    <i class="bi bi-clock-fill text-primary"></i>
                                @endif
                            @else
                                <i class="bi bi-calendar-x text-muted"></i>
                            @endif
                        </div>
                        <div class="h3 mb-1 fw-bold">Jadwal Hari Ini</div>
                        @if ($todayJadwal)
                            <span class="badge"
                                  style="background-color:{{ $todayJadwal->shift_color }};font-size:0.8rem;">
                                {{ $todayJadwal->shift->nama_shift ?? '-' }}
                            </span>
                        @else
                            <span class="text-muted small">Tidak ada jadwal</span>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Remaining leave quota --}}
            <div class="col-md-4">
                <div class="hris-card h-100">
                    <div class="hris-card-body text-center">
                        <div class="mb-2" style="font-size:2rem;">
                            <i class="bi bi-calendar-check-fill text-success"></i>
                        </div>
                        <div class="h3 mb-1 fw-bold">{{ $karyawan->status_karyawan ?? 0 }}</div>
                        <div class="text-muted small">Sisa Cuti (hari)</div>
                        @if ($pendingCutiCount > 0)
                            <span class="badge bg-warning text-dark mt-1">{{ $pendingCutiCount }} menunggu persetujuan</span>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Hadir this month --}}
            <div class="col-md-4">
                <div class="hris-card h-100">
                    <div class="hris-card-body text-center">
                        <div class="mb-2" style="font-size:2rem;">
                            <i class="bi bi-person-check-fill text-info"></i>
                        </div>
                        <div class="h3 mb-1 fw-bold">{{ $hadirThisMonth }}</div>
                        <div class="text-muted small">Hadir Bulan Ini</div>
                    </div>
                </div>
            </div>

        </div>

        {{-- ── Today's attendance status ────────────────────────────────── --}}
        <div class="row g-3 mb-3">
            <div class="col-12">
                <div class="hris-card">
                    <div class="hris-card-header">
                        <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i>Absensi Hari Ini</h5>
                    </div>
                    <div class="hris-card-body">
                        @if ($todayAbsensi)
                            <div class="d-flex align-items-center gap-3 flex-wrap">
                                <div class="text-center px-3">
                                    <div class="text-muted small mb-1">Jam Masuk</div>
                                    <div class="fw-semibold fs-5">{{ $todayAbsensi->jam_masuk ?? '-' }}</div>
                                </div>
                                <div class="vr d-none d-sm-block"></div>
                                <div class="text-center px-3">
                                    <div class="text-muted small mb-1">Jam Pulang</div>
                                    <div class="fw-semibold fs-5">{{ $todayAbsensi->jam_pulang ?? '-' }}</div>
                                </div>
                                <div class="vr d-none d-sm-block"></div>
                                <div class="text-center px-3">
                                    <div class="text-muted small mb-1">Status</div>
                                    <span class="badge bg-{{ $todayAbsensi->status === 'terlambat' ? 'warning text-dark' : 'success' }}">
                                        {{ $todayAbsensi->status === 'terlambat' ? 'Terlambat' : 'Tepat Waktu' }}
                                    </span>
                                </div>
                                @if ($todayAbsensi->jam_masuk && $todayAbsensi->jam_pulang)
                                    <div class="ms-auto">
                                        <span class="badge bg-success fs-6 px-3 py-2">
                                            <i class="bi bi-check-circle me-1"></i>Selesai
                                        </span>
                                    </div>
                                @else
                                    <div class="ms-auto">
                                        <span class="badge bg-warning text-dark fs-6 px-3 py-2">
                                            <i class="bi bi-hourglass-split me-1"></i>Belum Pulang
                                        </span>
                                    </div>
                                @endif
                            </div>
                        @else
                            <div class="text-center text-muted py-3">
                                <i class="bi bi-calendar-x" style="font-size:2rem;"></i>
                                <p class="mt-2 mb-0">Belum ada absensi hari ini</p>
                                <p class="small">Absensi diproses oleh admin</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Attendance history (7 days, server-rendered) ────────────── --}}
        <div class="row g-3 mb-3">
            <div class="col-lg-7">
                <div class="hris-card h-100">
                    <div class="hris-card-header d-flex align-items-center justify-content-between">
                        <h5 class="mb-0"><i class="bi bi-list-check me-2"></i>Riwayat Absensi (7 Hari)</h5>
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
                                <tbody>
                                    @forelse ($attendanceHistory as $abs)
                                        <tr>
                                            <td class="small">{{ $abs->tanggal->format('d/m/Y') }}</td>
                                            <td class="small">{{ $abs->jam_masuk ?? '-' }}</td>
                                            <td class="small">{{ $abs->jam_pulang ?? '-' }}</td>
                                            <td>
                                                <span class="badge bg-{{ $abs->status === 'terlambat' ? 'warning text-dark' : 'success' }}">
                                                    {{ $abs->status === 'terlambat' ? 'Terlambat' : 'Tepat Waktu' }}
                                                </span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center text-muted py-3">Tidak ada data absensi</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ── Recent Cuti ──────────────────────────────────────────── --}}
            <div class="col-lg-5">
                <div class="hris-card h-100">
                    <div class="hris-card-header d-flex align-items-center justify-content-between">
                        <h5 class="mb-0"><i class="bi bi-calendar-event me-2"></i>Pengajuan Cuti</h5>
                        <a href="{{ route('cuti.index') }}" class="btn btn-sm btn-outline-primary">Lihat Semua</a>
                    </div>
                    <div class="hris-card-body">
                        @forelse ($recentCuti as $cuti)
                            <div class="d-flex align-items-start gap-2 mb-3 pb-3 {{ !$loop->last ? 'border-bottom' : '' }}">
                                <span class="badge bg-{{ $cuti->status_color }} mt-1" style="min-width:72px;text-align:center;">
                                    {{ $cuti->status_text }}
                                </span>
                                <div class="flex-grow-1">
                                    <div class="small fw-semibold">{{ $cuti->jenis_cuti }}</div>
                                    <div class="small text-muted">
                                        {{ $cuti->tanggal_mulai->format('d M') }}
                                        @if (!$cuti->tanggal_mulai->eq($cuti->tanggal_selesai))
                                            &ndash; {{ $cuti->tanggal_selesai->format('d M Y') }}
                                        @else
                                            {{ $cuti->tanggal_mulai->format('Y') }}
                                        @endif
                                        &middot; {{ $cuti->jumlah_hari }} hari
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="text-center text-muted py-3">
                                <i class="bi bi-calendar-x" style="font-size:1.75rem;"></i>
                                <p class="small mt-2 mb-2">Belum ada pengajuan cuti</p>
                                <a href="{{ route('cuti.create') }}" class="btn btn-sm btn-primary">
                                    <i class="bi bi-plus me-1"></i>Ajukan Cuti
                                </a>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

    @endif {{-- end $karyawan --}}

    {{-- ══════════════════════════════════════════════════════════════════ --}}
    {{-- ADMIN / HR SECTIONS                                                --}}
    {{-- ══════════════════════════════════════════════════════════════════ --}}
    @if ($isAdmin || $isHr)

        {{-- Daily attendance summary --}}
        <div class="row g-3 mb-3">
            @foreach([
                ['icon' => 'bi-people-fill', 'label' => 'Total Karyawan', 'value' => $dailyAttendanceSummary['total_karyawan'] ?? 0, 'color' => 'var(--hris-primary)'],
                ['icon' => 'bi-person-check-fill', 'label' => 'Sudah Absen Masuk', 'value' => $dailyAttendanceSummary['sudah_absen_masuk'] ?? 0, 'color' => '#198754'],
                ['icon' => 'bi-person-x-fill', 'label' => 'Belum Absen', 'value' => $dailyAttendanceSummary['belum_absen'] ?? 0, 'color' => '#dc3545'],
                ['icon' => 'bi-exclamation-circle-fill', 'label' => 'Terlambat', 'value' => $dailyAttendanceSummary['terlambat'] ?? 0, 'color' => '#ff9800'],
                ['icon' => 'bi-check-circle-fill', 'label' => 'Tepat Waktu/Hadir', 'value' => $dailyAttendanceSummary['tepat_waktu'] ?? 0, 'color' => '#20c997'],
                ['icon' => 'bi-slash-circle-fill', 'label' => 'Tidak Hadir', 'value' => $dailyAttendanceSummary['tidak_hadir'] ?? 0, 'color' => '#6c757d'],
                ['icon' => 'bi-calendar-check-fill', 'label' => 'Cuti Approved', 'value' => $dailyAttendanceSummary['cuti_approved'] ?? 0, 'color' => '#6f42c1'],
            ] as $card)
                <div class="col-6 col-lg-3">
                    <div class="hris-card h-100">
                        <div class="hris-card-body text-center">
                            <div style="font-size:1.8rem;color:{{ $card['color'] }};margin-bottom:8px;">
                                <i class="bi {{ $card['icon'] }}"></i>
                            </div>
                            <div class="h3 mb-1">{{ $card['value'] }}</div>
                            <div class="text-muted small">{{ $card['label'] }}</div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Today's attendance table --}}
        <div class="row g-3 mb-3">
            <div class="col-12">
                <div class="hris-card">
                    <div class="hris-card-header">
                        <h5 class="mb-0"><i class="bi bi-calendar-day me-2"></i>Absensi Hari Ini</h5>
                    </div>
                    <div class="hris-card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Nama</th>
                                        <th>Divisi</th>
                                        <th>Jabatan</th>
                                        <th>Jadwal/Shift</th>
                                        <th>Masuk</th>
                                        <th>Pulang</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($todayAttendanceRows as $row)
                                        @php
                                            $employee = $row['karyawan'];
                                            $absensi = $row['absensi'];
                                            $jadwal = $row['jadwal'];
                                        @endphp
                                        <tr>
                                            <td class="small fw-semibold">{{ $employee->nama }}</td>
                                            <td class="small">{{ $employee->divisi->nama_divisi ?? '-' }}</td>
                                            <td class="small">{{ $employee->jabatan->nama_jabatan ?? '-' }}</td>
                                            <td class="small">{{ $jadwal?->id_shift ?? $employee->shift?->label ?? '-' }}</td>
                                            <td class="small">{{ $absensi?->jam_masuk ?? '-' }}</td>
                                            <td class="small">{{ $absensi?->jam_pulang ?? '-' }}</td>
                                            <td>
                                                @if($absensi)
                                                    <span class="badge bg-{{ $absensi->status_color }}">{{ $absensi->status_label }}</span>
                                                @else
                                                    <span class="badge bg-secondary">Belum Absen</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center text-muted py-3">Belum ada data karyawan</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    @endif {{-- end admin / HR daily recap --}}

    @if ($isAdmin)

        {{-- Attendance chart --}}
        <div class="row g-3 mt-1">
            <div class="col-12">
                <div class="hris-card">
                    <div class="hris-card-header">
                        <h5 class="mb-0"><i class="bi bi-bar-chart me-2"></i>Rekap Kehadiran</h5>
                    </div>
                    <div class="hris-card-body">
                        <div id="chartdiv"></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Attendance history (all employees, JS-loaded) --}}
        <div class="row g-3 mt-3">
            <div class="col-12">
                <div class="hris-card">
                    <div class="hris-card-header">
                        <h5 class="mb-0"><i class="bi bi-list-check me-2"></i>Riwayat Kehadiran (7 Hari Terakhir)</h5>
                    </div>
                    <div class="hris-card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-sm mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Tanggal</th>
                                        <th>Nama</th>
                                        <th>Masuk</th>
                                        <th>Pulang</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody id="attendance-history-tbody">
                                    <tr><td colspan="5" class="text-center text-muted py-3">Loading...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    @endif {{-- end $isAdmin --}}

</div>
@endsection

@section('scripts')
<script>
    {{-- Live clock --}}
    setInterval(() => {
        const el = document.getElementById('current-time');
        if (el) el.textContent = new Date().toLocaleTimeString('id-ID', {
            hour: '2-digit', minute: '2-digit', second: '2-digit'
        });
    }, 1000);

    {{-- Admin-only JS --}}
    @if ($isAdmin)
    renderAttendanceChart();
    loadAdminAttendanceHistory();

    function renderAttendanceChart() {
        const chartEl = document.getElementById('chartdiv');
        if (!chartEl || typeof am4core === 'undefined') return;

        const chartData = @json($dailyAttendanceChartData);

        const filteredData = chartData.filter(item => Number(item.total) > 0);

        if (filteredData.length === 0) {
            chartEl.innerHTML = '<div class="text-center text-muted py-5">Belum ada data rekap kehadiran hari ini</div>';
            return;
        }

        am4core.ready(function () {
            am4core.useTheme(am4themes_animated);

            const chart = am4core.create('chartdiv', am4charts.PieChart3D);
            chart.hiddenState.properties.opacity = 0;
            chart.legend = new am4charts.Legend();
            chart.data = filteredData;

            const series = chart.series.push(new am4charts.PieSeries3D());
            series.dataFields.value = 'total';
            series.dataFields.category = 'status';
            series.alignLabels = false;
            series.labels.template.text = "{value.percent.formatNumber('#.0')}%";
            series.labels.template.radius = am4core.percent(-40);
            series.labels.template.fill = am4core.color('white');
            series.colors.list = [
                am4core.color('#20c997'),
                am4core.color('#ff9800'),
                am4core.color('#0dcaf0'),
                am4core.color('#6c757d'),
                am4core.color('#dc3545'),
                am4core.color('#6f42c1'),
            ];
        });
    }

    async function fetchJson(url) {
        const res  = await fetch(url);
        const text = await res.text();
        if (!res.ok) throw new Error(`Request gagal (${res.status})`);
        return text ? JSON.parse(text) : {};
    }

    async function loadAdminAttendanceHistory() {
        const tbody = document.getElementById('attendance-history-tbody');
        if (!tbody) return;
        try {
            const data = await fetchJson('/api/attendance/recent-all?days=7');
            if (!Array.isArray(data) || data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-3">Tidak ada data absensi 7 hari terakhir</td></tr>';
                return;
            }
            tbody.innerHTML = data.map(r => `
                <tr>
                    <td class="small">${new Date(r.tanggal + 'T00:00:00').toLocaleDateString('id-ID')}</td>
                    <td class="small">${r.nama ?? '-'}</td>
                    <td class="small">${r.jam_masuk ?? '-'}</td>
                    <td class="small">${r.jam_pulang ?? '-'}</td>
                    <td><span class="badge bg-${statusBadgeClass(r.status)}">${r.status_label ?? statusLabelLocal(r.status)}</span></td>
                </tr>
            `).join('');
        } catch (err) {
            console.error('History error:', err);
            Swal.fire({ icon: 'error', title: 'Gagal', text: 'Riwayat absensi gagal dimuat. Periksa koneksi atau hubungi admin.', toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 });
            tbody.innerHTML = '<tr><td colspan="5" class="text-center text-danger py-3">Gagal memuat riwayat</td></tr>';
        }
    }

    function statusBadgeClass(s) {
        const map = { hadir: 'success', terlambat: 'warning text-dark', remote: 'info', tidak_hadir: 'danger', tepat_waktu: 'success' };
        return map[s] ?? 'secondary';
    }
    function statusLabelLocal(s) {
        const map = { hadir: 'Hadir', terlambat: 'Terlambat', remote: 'Remote', tidak_hadir: 'Tidak Hadir', tepat_waktu: 'Tepat Waktu' };
        return map[s] ?? (s ?? '-');
    }
    @endif
</script>
@endsection
