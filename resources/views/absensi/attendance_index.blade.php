@extends('layouts.app')

@section('title', 'Catat Absensi - HRIS')

@section('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css">
<style>
    /* Select2 badge inside dropdown options */
    .s2-employee-option { display: flex; align-items: center; gap: 8px; padding: 2px 0; }
    .s2-employee-option .s2-no-face { font-size: 0.72rem; color: #f59e0b; font-weight: 600; }
    .s2-employee-option .s2-job     { font-size: 0.8rem;  color: #6b7280; }
    .status-option {
        cursor: pointer;
        transition: background 0.15s, border-color 0.15s;
        border-color: var(--hris-border) !important;
    }
    .status-option.selected {
        background: #f0f4ff;
        border-color: var(--hris-primary) !important;
    }
    .status-option:hover:not(.selected) {
        background: #f8f9fa;
    }
    #video {
        width: 100%;
        display: block;
        transform: scaleX(-1);
        border-radius: 10px;
    }
    #loadingOverlay {
        position: absolute;
        inset: 0;
        background: rgba(0,0,0,0.6);
        display: none;
        align-items: center;
        justify-content: center;
        flex-direction: column;
        gap: 12px;
        border-radius: 10px;
    }
    #loadingOverlay.active { display: flex; }
    .verification-success { background: #f0fff4 !important; }
    .verification-fail    { background: #fff5f5 !important; }
    .audit-chip {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 2px 10px;
        background: #f1f5f9;
        border-radius: 999px;
        font-size: 0.78rem;
        color: #64748b;
    }
    /* Keep Leaflet map tiles below Select2 dropdown (z-index 1051) and modals */
    #map { z-index: 0 !important; }
    .leaflet-pane,
    .leaflet-control { z-index: 0 !important; }
    .leaflet-top,
    .leaflet-bottom { z-index: 1 !important; }
</style>
@endsection

@section('content')
<div class="hris-container">

    {{-- ── Header ──────────────────────────────────────────────────────────── --}}
    <div class="row g-3 mb-3">
        <div class="col-12">
            <div class="hris-card">
                <div class="hris-card-body d-flex align-items-center justify-content-between flex-wrap gap-3">
                    <div>
                        <h4 class="mb-1 fw-bold">
                            <i class="bi bi-camera-video-fill me-2 text-primary"></i>Catat Absensi Karyawan
                        </h4>
                        <div class="text-muted small">
                            <i class="bi bi-calendar3 me-1"></i><span id="currentDate"></span>
                            &nbsp;&mdash;&nbsp;
                            <i class="bi bi-clock me-1"></i><span id="currentTime" class="fw-semibold"></span>
                        </div>
                    </div>
                    <div class="d-flex gap-2 align-items-center">
                        @if(!$serviceHealthy)
                            <span class="badge bg-danger fs-6 px-3 py-2">
                                <i class="bi bi-exclamation-triangle me-1"></i>Face Service Tidak Aktif
                            </span>
                        @else
                            <span class="badge bg-success fs-6 px-3 py-2">
                                <i class="bi bi-check-circle me-1"></i>Face Service Aktif
                            </span>
                        @endif
                        <a href="{{ route('attendance.history') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-clock-history me-1"></i>Riwayat
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">

        {{-- ── LEFT: Form panel ────────────────────────────────────────────── --}}
        <div class="col-lg-4 d-flex flex-column gap-3">

            {{-- Step 1: Employee --}}
            <div class="hris-card">
                <div class="hris-card-header">
                    <h6 class="mb-0 fw-semibold"><span class="badge bg-primary me-2">1</span>Pilih Karyawan</h6>
                </div>
                <div class="hris-card-body">
                    <select id="employeeSelect" class="form-select">
                        <option value="">-- Pilih Karyawan --</option>
                        @foreach($karyawanList as $k)
                            <option value="{{ $k->id_karyawan }}"
                                    data-face="{{ $k->hasFaceRegistered() ? '1' : '0' }}"
                                    data-nama="{{ $k->nama }}"
                                    data-jabatan="{{ $k->jabatan?->nama_jabatan ?? '' }}"
                                    data-register-url="{{ route('karyawan.register-face', $k->id_karyawan) }}">
                                {{ $k->nama }}
                            </option>
                        @endforeach
                    </select>

                    <div id="faceWarning" class="alert alert-warning py-2 small mt-2 mb-0" style="display:none;">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i>
                        Karyawan belum mendaftarkan wajah. Verifikasi wajah tidak tersedia.
                        <a id="registerFaceLink" href="javascript:void(0)" class="alert-link ms-1"
                           onclick="goToRegisterFace()" target="_blank" rel="noopener">
                            Daftar sekarang &rarr;
                        </a>
                    </div>
                </div>
            </div>

            {{-- Step 2: Status --}}
            <div class="hris-card">
                <div class="hris-card-header">
                    <h6 class="mb-0 fw-semibold"><span class="badge bg-primary me-2">2</span>Status Kehadiran</h6>
                </div>
                <div class="hris-card-body d-flex flex-column gap-2">
                    @foreach(['hadir' => ['success','Hadir','Kehadiran normal'], 'terlambat' => ['warning','Terlambat','Masuk setelah jam kerja'], 'remote' => ['info','Remote / WFH','Bekerja dari rumah'], 'tidak_hadir' => ['danger','Tidak Hadir','Alpha / tidak masuk']] as $val => [$color, $label, $hint])
                    <label class="d-flex align-items-center gap-2 p-2 rounded border status-option {{ $val === 'hadir' ? 'selected' : '' }}"
                           data-status="{{ $val }}" onclick="selectStatus('{{ $val }}')">
                        <input type="radio" name="status" value="{{ $val }}" {{ $val === 'hadir' ? 'checked' : '' }} class="d-none">
                        <span class="badge bg-{{ $color }} {{ $color === 'warning' ? 'text-dark' : '' }}" style="min-width:90px;text-align:center;">
                            {{ $label }}
                        </span>
                        <span class="small text-muted">{{ $hint }}</span>
                    </label>
                    @endforeach
                </div>
            </div>

            {{-- Step 3: Time + GPS --}}
            <div class="hris-card">
                <div class="hris-card-header">
                    <h6 class="mb-0 fw-semibold"><span class="badge bg-primary me-2">3</span>Waktu &amp; Lokasi</h6>
                </div>
                <div class="hris-card-body">
                    <div class="mb-3" id="timeInputWrap">
                        <label class="form-label small fw-semibold">Jam Masuk</label>
                        <input type="time" id="jamMasuk" class="form-control" value="{{ now()->setTimezone('Asia/Singapore')->format('H:i') }}">
                        <div class="form-text">Biarkan kosong untuk menggunakan waktu saat ini</div>
                    </div>
                    <div>
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <label class="form-label small fw-semibold mb-0">GPS Lokasi</label>
                            <button class="btn btn-sm btn-outline-secondary" onclick="captureGPS()" id="btnGPS">
                                <i class="bi bi-geo-alt me-1"></i>Ambil GPS
                            </button>
                        </div>
                        <div id="gpsStatus" class="small text-muted mb-2">
                            <i class="bi bi-geo-alt me-1"></i>Belum diambil
                        </div>
                        <input type="hidden" id="gpsLat">
                        <input type="hidden" id="gpsLng">

                        {{-- Leaflet map — hidden until GPS is captured --}}
                        <div id="mapWrap" style="display:none; margin-top:8px;">
                            <div id="map" style="height:180px; border-radius:8px; border:1px solid var(--hris-border); z-index:0;"></div>
                            <div class="text-muted small mt-1">
                                <i class="bi bi-info-circle me-1"></i>Lokasi admin saat mencatat absensi
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        {{-- ── RIGHT: Camera + Verification ───────────────────────────────── --}}
        <div class="col-lg-8">
            <div class="hris-card h-100">
                <div class="hris-card-header d-flex align-items-center justify-content-between">
                    <h6 class="mb-0 fw-semibold"><span class="badge bg-primary me-2">4</span>Verifikasi Wajah</h6>
                    <span id="verificationBadge" class="badge bg-secondary">Menunggu</span>
                </div>
                <div class="hris-card-body d-flex flex-column gap-3">

                    {{-- Camera feed --}}
                    <div id="cameraSection">
                        <div style="position:relative; background:#111; border-radius:10px; overflow:hidden; aspect-ratio:4/3; max-width:500px; margin:0 auto;">
                            <video id="video" autoplay playsinline></video>
                            <canvas id="canvas" style="display:none;"></canvas>
                            <div id="loadingOverlay">
                                <div class="spinner-border text-white" role="status"></div>
                                <span class="text-white small fw-semibold">Memverifikasi wajah…</span>
                            </div>
                        </div>
                        <p class="text-muted small text-center mt-2 mb-0">
                            <i class="bi bi-info-circle me-1"></i>Pastikan wajah karyawan terlihat jelas dan pencahayaan cukup
                        </p>
                    </div>

                    {{-- Tidak Hadir placeholder --}}
                    <div id="noPhotoSection" style="display:none;" class="text-center py-5">
                        <i class="bi bi-person-x text-danger" style="font-size:3.5rem;"></i>
                        <p class="fw-semibold mt-3 mb-1">Status: Tidak Hadir</p>
                        <p class="text-muted small">Verifikasi wajah tidak diperlukan untuk status ini.</p>
                    </div>

                    {{-- Verification result --}}
                    <div id="verificationResult" class="rounded p-3" style="display:none;"></div>

                    {{-- Action buttons --}}
                    <div class="d-flex gap-2 flex-wrap">
                        <button class="btn btn-primary flex-grow-1" id="btnVerify" onclick="verifyFace()" disabled>
                            <i class="bi bi-camera-fill me-2"></i>Verifikasi Wajah
                        </button>
                        <button class="btn btn-success flex-grow-1" id="btnSave" onclick="saveAttendance()" disabled>
                            <i class="bi bi-check-circle-fill me-2"></i>Konfirmasi &amp; Simpan
                        </button>
                    </div>

                    {{-- Audit chip row --}}
                    <div class="border-top pt-3 d-flex flex-wrap gap-2">
                        <span class="audit-chip">
                            <i class="bi bi-shield-lock-fill"></i>
                            Admin: <strong class="ms-1">{{ auth()->user()->username }}</strong>
                        </span>
                        <span class="audit-chip" id="auditDevice">
                            <i class="bi bi-laptop"></i> Perangkat terdeteksi
                        </span>
                        <span class="audit-chip" id="auditIP">
                            <i class="bi bi-hdd-network"></i> IP: {{ request()->ip() }}
                        </span>
                        <span class="audit-chip">
                            <i class="bi bi-lock-fill"></i> Rekaman terkunci setelah disimpan
                        </span>
                    </div>

                </div>
            </div>
        </div>

    </div>
</div>

{{-- Success toast --}}
<div id="successToast" style="position:fixed;bottom:2rem;right:2rem;z-index:9999;display:none;min-width:320px;">
    <div class="hris-card shadow" style="border-left:4px solid #22c55e;">
        <div class="hris-card-body d-flex align-items-start gap-3 py-3">
            <i class="bi bi-check-circle-fill text-success fs-4 mt-1"></i>
            <div class="flex-grow-1">
                <div class="fw-semibold" id="toastTitle">Absensi Berhasil</div>
                <div class="small text-muted" id="toastBody"></div>
            </div>
            <button class="btn-close" onclick="hideToast()"></button>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
const serviceOk = @json($serviceHealthy);

// ── Timezone ────────────────────────────────────────────────────────────────────
const TZ = 'Asia/Singapore'; // GMT+8 — covers WIB+1, WITA, WIT, SGT, MYT

/** Returns the current wall-clock time in GMT+8 as an HH:mm string for <input type="time">. */
function currentTimeGMT8() {
    const parts = new Intl.DateTimeFormat('en-GB', {
        timeZone: TZ, hour: '2-digit', minute: '2-digit', hour12: false,
    }).formatToParts(new Date());
    const h = parts.find(p => p.type === 'hour')?.value   ?? '00';
    const m = parts.find(p => p.type === 'minute')?.value ?? '00';
    return `${h}:${m}`;
}

// ── State ────────────────────────────────────────────────────────────────────
let selectedEmployee = null; // { id, nama, hasFace }
let currentStatus    = 'hadir';
let faceVerified     = false;
let capturedPhoto    = null;

// ── Boot ─────────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    updateTime();
    setInterval(updateTime, 1000);
    // Override the PHP-rendered initial value with the client-side GMT+8 time
    document.getElementById('jamMasuk').value = currentTimeGMT8();
    captureDeviceInfo();
    captureGPS();
    if (serviceOk) startCamera();
    initSelect2();
    updateButtons();
});

// ── Select2 init ─────────────────────────────────────────────────────────────
function initSelect2() {
    function formatOption(option) {
        if (!option.id) return option.text; // placeholder
        const $opt  = $(option.element);
        const face  = $opt.data('face')  === '1';
        const job   = $opt.data('jabatan') || '';
        const warn  = face ? '' : '<span class="s2-no-face"><i class="bi bi-exclamation-triangle-fill"></i> Belum daftar wajah</span>';
        return $(`<span class="s2-employee-option">
                    <span>${option.text}${job ? ' <span class="s2-job">('+job+')</span>' : ''}</span>
                    ${warn}
                  </span>`);
    }

    $('#employeeSelect').select2({
        theme:       'bootstrap-5',
        placeholder: '-- Pilih Karyawan --',
        allowClear:  true,
        width:       '100%',
        templateResult:    formatOption,
        templateSelection: formatOption,
    }).on('change', function () {
        onEmployeeChange();
    });
}

// ── Live clock ────────────────────────────────────────────────────────────────
function updateTime() {
    const now = new Date();
    document.getElementById('currentTime').textContent =
        now.toLocaleTimeString('id-ID', { timeZone: TZ, hour: '2-digit', minute: '2-digit', second: '2-digit' });
    document.getElementById('currentDate').textContent =
        now.toLocaleDateString('id-ID', { timeZone: TZ, weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
}

// ── Employee selection ────────────────────────────────────────────────────────────────────
function onEmployeeChange() {
    const $sel = $('#employeeSelect');
    const val  = $sel.val();

    faceVerified  = false;
    capturedPhoto = null;
    setVerificationUI('idle');

    if (!val) {
        selectedEmployee = null;
        document.getElementById('faceWarning').style.display = 'none';
        updateButtons();
        return;
    }

    // Use .attr() — reads the HTML attribute directly, bypasses jQuery's data() cache
    const $opt        = $sel.find('option[value="' + val + '"]');
    const hasFace     = $opt.attr('data-face') === '1';

    selectedEmployee = {
        id:          parseInt(val),
        nama:        $opt.attr('data-nama'),
        hasFace:     hasFace,
        registerUrl: $opt.attr('data-register-url') || '/karyawan',
    };

    document.getElementById('faceWarning').style.display = hasFace ? 'none' : 'block';
    updateButtons();
}

// Navigate to the selected employee's face-registration page (new tab)
function goToRegisterFace() {
    if (selectedEmployee?.registerUrl) {
        window.open(selectedEmployee.registerUrl, '_blank', 'noopener');
    }
}

// ── Status selection ──────────────────────────────────────────────────────────
function selectStatus(status) {
    currentStatus = status;
    faceVerified  = false;
    capturedPhoto = null;
    setVerificationUI('idle');

    document.querySelectorAll('.status-option').forEach(el => {
        const active = el.dataset.status === status;
        el.classList.toggle('selected', active);
    });

    const isAbsent = status === 'tidak_hadir';
    document.getElementById('cameraSection').style.display   = isAbsent ? 'none' : 'block';
    document.getElementById('noPhotoSection').style.display  = isAbsent ? 'block' : 'none';
    document.getElementById('timeInputWrap').style.display   = isAbsent ? 'none' : 'block';
    document.getElementById('verificationResult').style.display = 'none';

    if (isAbsent) faceVerified = true; // No face required

    updateButtons();
}

// ── Camera ────────────────────────────────────────────────────────────────────
async function startCamera() {
    try {
        const stream = await navigator.mediaDevices.getUserMedia({
            video: { width: { ideal: 640 }, height: { ideal: 480 }, facingMode: 'user' }
        });
        document.getElementById('video').srcObject = stream;
    } catch (err) {
        console.warn('Camera error:', err);
    }
}

function captureFrame() {
    const video  = document.getElementById('video');
    const canvas = document.getElementById('canvas');
    if (!video.videoWidth) return null;
    canvas.width  = video.videoWidth;
    canvas.height = video.videoHeight;
    const ctx = canvas.getContext('2d');
    ctx.save();
    ctx.translate(canvas.width, 0);
    ctx.scale(-1, 1);
    ctx.drawImage(video, 0, 0);
    ctx.restore();
    return canvas.toDataURL('image/jpeg', 0.9);
}

// ── Face verification ─────────────────────────────────────────────────────────
async function verifyFace() {
    if (!selectedEmployee)            return alert('Pilih karyawan terlebih dahulu.');
    if (!selectedEmployee.hasFace)    return alert('Karyawan belum mendaftarkan wajah.');
    if (!serviceOk)                   return alert('Face recognition service tidak aktif.');

    const photo = captureFrame();
    if (!photo) return alert('Kamera belum siap. Tunggu sebentar dan coba lagi.');

    capturedPhoto = photo;
    faceVerified  = false;
    setLoading(true);
    setVerificationUI('loading');
    document.getElementById('btnVerify').disabled = true;

    try {
        const res = await apiPost('/api/attendance/verify-face', {
            id_karyawan: selectedEmployee.id,
            photo: photo,
        });

        if (res.verified) {
            faceVerified = true;
            setVerificationUI('success', res.confidence);
        } else if (res.mismatch) {
            setVerificationUI('mismatch', 0, res.message);
        } else {
            setVerificationUI('fail', 0, res.message);
        }
    } catch (err) {
        setVerificationUI('error', 0, err.message);
    } finally {
        setLoading(false);
        document.getElementById('btnVerify').disabled = false;
        updateButtons();
    }
}

function setVerificationUI(state, confidence = 0, message = '') {
    const badge  = document.getElementById('verificationBadge');
    const result = document.getElementById('verificationResult');

    const map = {
        idle:     { cls: 'bg-secondary',            label: 'Menunggu',        show: false, bg: '' },
        loading:  { cls: 'bg-warning text-dark',    label: 'Memverifikasi…',  show: false, bg: '' },
        success:  { cls: 'bg-success',              label: 'Terverifikasi',   show: true,  bg: 'verification-success' },
        fail:     { cls: 'bg-danger',               label: 'Gagal',           show: true,  bg: 'verification-fail' },
        mismatch: { cls: 'bg-danger',               label: 'Tidak Cocok',     show: true,  bg: 'verification-fail' },
        error:    { cls: 'bg-warning text-dark',    label: 'Error',           show: true,  bg: '' },
    };
    const cfg = map[state] || map.idle;
    badge.className   = 'badge ' + cfg.cls;
    badge.textContent = cfg.label;
    result.style.display = cfg.show ? 'block' : 'none';
    result.className = 'rounded p-3 ' + cfg.bg;

    const icons = { success: 'bi-check-circle-fill text-success', fail: 'bi-x-circle-fill text-danger', mismatch: 'bi-shield-x text-danger', error: 'bi-exclamation-triangle text-warning' };
    const msgs  = {
        success:  `<div class="d-flex align-items-center gap-3">
                     <i class="bi ${icons.success} fs-3"></i>
                     <div><div class="fw-semibold text-success">Wajah Terverifikasi</div>
                     <div class="small text-muted">Akurasi: ${confidence.toFixed(1)}% &mdash; Siap untuk disimpan</div></div>
                   </div>`,
        fail:     `<div class="d-flex align-items-center gap-3">
                     <i class="bi ${icons.fail} fs-3"></i>
                     <div><div class="fw-semibold text-danger">Verifikasi Gagal</div>
                     <div class="small text-muted">${message || 'Wajah tidak dikenali. Coba lagi.'}</div></div>
                   </div>`,
        mismatch: `<div class="d-flex align-items-center gap-3">
                     <i class="bi ${icons.mismatch} fs-3"></i>
                     <div><div class="fw-semibold text-danger">Wajah Tidak Cocok</div>
                     <div class="small text-muted">${message}</div></div>
                   </div>`,
        error:    `<div class="d-flex align-items-center gap-3">
                     <i class="bi ${icons.error} fs-3"></i>
                     <div><div class="fw-semibold text-warning">Terjadi Kesalahan</div>
                     <div class="small text-muted">${message}</div></div>
                   </div>`,
    };
    if (cfg.show && msgs[state]) result.innerHTML = msgs[state];
}

// ── Save attendance ───────────────────────────────────────────────────────────
async function saveAttendance() {
    if (!selectedEmployee) return alert('Pilih karyawan terlebih dahulu.');
    if (!faceVerified && currentStatus !== 'tidak_hadir') return alert('Selesaikan verifikasi wajah terlebih dahulu.');

    const btnSave = document.getElementById('btnSave');
    btnSave.disabled = true;
    btnSave.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Menyimpan…';

    try {
        const payload = {
            id_karyawan: selectedEmployee.id,
            status:      currentStatus,
            jam_masuk:   document.getElementById('jamMasuk').value || null,
            gps_lat:     document.getElementById('gpsLat').value   || null,
            gps_lng:     document.getElementById('gpsLng').value   || null,
            device_info: navigator.userAgent.substring(0, 500),
        };
        if (capturedPhoto && currentStatus !== 'tidak_hadir') {
            payload.photo = capturedPhoto;
        }

        const res = await apiPost('/api/attendance/admin-record', payload);

        if (res.success) {
            showToast(
                'Absensi Berhasil Dicatat',
                `${res.employee_name} — ${statusLabel(currentStatus)} — ${document.getElementById('jamMasuk').value || 'waktu sekarang'}`
                + (res.face_verified ? ` — Wajah ✓ (${res.face_confidence?.toFixed(1)}%)` : '')
            );
            resetForm();
        } else {
            alert(res.message || 'Gagal menyimpan absensi.');
        }
    } catch (err) {
        alert(err.message);
    } finally {
        btnSave.innerHTML = '<i class="bi bi-check-circle-fill me-2"></i>Konfirmasi &amp; Simpan';
        updateButtons();
    }
}

// ── Helpers ───────────────────────────────────────────────────────────────────
function updateButtons() {
    const hasEmp   = !!selectedEmployee;
    const needFace = currentStatus !== 'tidak_hadir';

    document.getElementById('btnVerify').disabled =
        !hasEmp || !needFace || !selectedEmployee?.hasFace || !serviceOk;

    document.getElementById('btnSave').disabled =
        !hasEmp || (needFace && !faceVerified);
}

function setLoading(on) {
    document.getElementById('loadingOverlay').classList.toggle('active', on);
}

// Leaflet map instance — kept in module scope so we can update it on re-capture
let _map    = null;
let _marker = null;

function captureGPS() {
    if (!navigator.geolocation) {
        document.getElementById('gpsStatus').innerHTML =
            '<i class="bi bi-geo-alt me-1 text-muted"></i>Tidak didukung browser';
        return;
    }

    document.getElementById('gpsStatus').innerHTML =
        '<i class="bi bi-hourglass-split me-1"></i>Mengambil lokasi…';

    navigator.geolocation.getCurrentPosition(
        pos => {
            const lat = parseFloat(pos.coords.latitude.toFixed(7));
            const lng = parseFloat(pos.coords.longitude.toFixed(7));

            document.getElementById('gpsLat').value = lat;
            document.getElementById('gpsLng').value = lng;
            document.getElementById('gpsStatus').innerHTML =
                `<i class="bi bi-geo-alt-fill text-success me-1"></i>${lat}, ${lng}
                 <span class="text-success fw-semibold">✓</span>`;

            // Show map container
            document.getElementById('mapWrap').style.display = 'block';

            if (_map === null) {
                // First time — initialise Leaflet
                _map = L.map('map', { zoomControl: true, attributionControl: true })
                         .setView([lat, lng], 16);

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
                    maxZoom: 19,
                }).addTo(_map);

                _marker = L.marker([lat, lng])
                            .addTo(_map)
                            .bindPopup(`<b>Lokasi Admin</b><br>${lat}, ${lng}`)
                            .openPopup();
            } else {
                // Re-capture — update existing map
                _map.setView([lat, lng], 16);
                _marker.setLatLng([lat, lng])
                       .setPopupContent(`<b>Lokasi Admin</b><br>${lat}, ${lng}`)
                       .openPopup();
            }

            // Leaflet needs a size invalidation when the container was hidden
            setTimeout(() => _map.invalidateSize(), 50);
        },
        () => {
            document.getElementById('gpsStatus').innerHTML =
                '<i class="bi bi-geo-alt text-muted me-1"></i>Tidak tersedia';
        },
        { enableHighAccuracy: true, timeout: 10000 }
    );
}

function captureDeviceInfo() {
    const ua = navigator.userAgent;
    const short = ua.length > 60 ? ua.substring(0, 60) + '…' : ua;
    document.getElementById('auditDevice').innerHTML = `<i class="bi bi-laptop"></i> ${short}`;
}

function statusLabel(s) {
    return { hadir: 'Hadir', terlambat: 'Terlambat', remote: 'Remote / WFH', tidak_hadir: 'Tidak Hadir' }[s] || s;
}

async function apiPost(url, data) {
    const res  = await fetch(url, {
        method:  'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
        body:    JSON.stringify(data),
    });
    const text = await res.text();
    let json;
    try { json = JSON.parse(text); } catch { throw new Error(`Server error (${res.status})`); }
    if (!res.ok) throw new Error(json.message || `HTTP ${res.status}`);
    return json;
}

function showToast(title, body) {
    document.getElementById('toastTitle').textContent = title;
    document.getElementById('toastBody').textContent  = body;
    const t = document.getElementById('successToast');
    t.style.display = 'block';
    setTimeout(() => { t.style.display = 'none'; }, 6000);
}

function hideToast() {
    document.getElementById('successToast').style.display = 'none';
}

function resetForm() {
    $('#employeeSelect').val(null).trigger('change'); // clears Select2
    selectedEmployee = null;
    faceVerified     = false;
    capturedPhoto    = null;
    currentStatus    = 'hadir';
    document.getElementById('faceWarning').style.display = 'none';
    document.getElementById('jamMasuk').value = currentTimeGMT8(); // reset to current GMT+8 time
    selectStatus('hadir');
    setVerificationUI('idle');
    updateButtons();
}
</script>
@endsection
