@extends('layouts.app')

@section('title', 'Registrasi Wajah - ' . $karyawan->nama)

@section('styles')
<style>
    #video  { width: 100%; display: block; transform: scaleX(-1); border-radius: 10px; }
    #canvas { display: none; }
    .camera-wrap {
        position: relative;
        background: #111;
        border-radius: 10px;
        overflow: hidden;
        aspect-ratio: 4/3;
        max-width: 480px;
        margin: 0 auto;
    }
    #cameraOverlay {
        position: absolute;
        inset: 0;
        background: rgba(0,0,0,0.55);
        display: none;
        align-items: center;
        justify-content: center;
        flex-direction: column;
        gap: 12px;
        border-radius: 10px;
    }
    #cameraOverlay.active { display: flex; }
</style>
@endsection

@section('content')
<div class="hris-container">

    <div class="mb-3">
        <a href="{{ route('karyawan.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i>Kembali ke Daftar Karyawan
        </a>
    </div>

    <div class="row g-3">

        {{-- LEFT: employee info + instructions --}}
        <div class="col-lg-4 d-flex flex-column gap-3">

            <div class="hris-card">
                <div class="hris-card-header">
                    <h6 class="mb-0 fw-semibold">
                        <i class="bi bi-person-badge-fill me-2 text-primary"></i>Data Karyawan
                    </h6>
                </div>
                <div class="hris-card-body">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center flex-shrink-0"
                             style="width:48px;height:48px;font-size:1.25rem;">
                            <i class="bi bi-person-fill"></i>
                        </div>
                        <div>
                            <div class="fw-semibold">{{ $karyawan->nama }}</div>
                            <div class="small text-muted">
                                {{ $karyawan->jabatan->nama_jabatan ?? '-' }}
                                @if($karyawan->devisi)
                                    &mdash; {{ $karyawan->devisi->nama_devisi ?? '-'}}
                                @endif
                            </div>
                        </div>
                    </div>

                    @if($karyawan->face_embedding)
                        <div class="alert alert-success py-2 mb-0 small">
                            <i class="bi bi-check-circle-fill me-1"></i>
                            <strong>Wajah sudah terdaftar.</strong>
                            Mendaftarkan ulang akan mengganti data sebelumnya.
                        </div>
                    @else
                        <div class="alert alert-warning py-2 mb-0 small">
                            <i class="bi bi-exclamation-circle-fill me-1"></i>
                            Wajah belum terdaftar.
                        </div>
                    @endif
                </div>
            </div>

            <div class="hris-card">
                <div class="hris-card-header">
                    <h6 class="mb-0 fw-semibold">
                        <i class="bi bi-info-circle-fill me-2 text-warning"></i>Petunjuk
                    </h6>
                </div>
                <div class="hris-card-body">
                    <ol class="small text-muted ps-3 mb-0" style="line-height:1.9;">
                        <li>Pastikan pencahayaan <strong>cukup terang</strong></li>
                        <li>Posisikan wajah di <strong>tengah</strong> frame</li>
                        <li>Hadap kamera secara <strong>langsung</strong></li>
                        <li>Hindari kacamata hitam atau masker</li>
                        <li>Klik <strong>"Daftarkan Wajah"</strong> saat siap</li>
                    </ol>
                </div>
            </div>

        </div>

        {{-- RIGHT: camera + action --}}
        <div class="col-lg-8">
            <div class="hris-card h-100">
                <div class="hris-card-header d-flex align-items-center justify-content-between">
                    <h6 class="mb-0 fw-semibold">
                        <i class="bi bi-camera-video-fill me-2 text-primary"></i>Kamera
                    </h6>
                    <span id="cameraStatus" class="badge bg-secondary">Memuat kamera&hellip;</span>
                </div>
                <div class="hris-card-body d-flex flex-column gap-3">

                    <div class="camera-wrap">
                        <video id="video" autoplay playsinline></video>
                        <canvas id="canvas"></canvas>
                        <div id="cameraOverlay">
                            <div class="spinner-border text-white" role="status"></div>
                            <span class="text-white small fw-semibold">Memproses wajah&hellip;</span>
                        </div>
                    </div>

                    <div id="statusMessage" style="display:none;"></div>

                    <button class="btn btn-primary btn-lg w-100" id="btnRegister">
                        <i class="bi bi-camera-fill me-2"></i>Daftarkan Wajah
                    </button>
                    <a href="{{ route('karyawan.import-face', ['id_karyawan' => $karyawan->id_karyawan]) }}"
                       class="btn btn-outline-primary w-100">
                        <i class="bi bi-image me-2"></i>Import dari File Image
                    </a>

                </div>
            </div>
        </div>

    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {

const video         = document.getElementById('video');
const canvas        = document.getElementById('canvas');
const btnRegister   = document.getElementById('btnRegister');
const statusMsg     = document.getElementById('statusMessage');
const cameraOverlay = document.getElementById('cameraOverlay');
const cameraStatus  = document.getElementById('cameraStatus');
const csrfToken     = document.querySelector('meta[name="csrf-token"]')?.content;
const idKaryawan    = @json($karyawan->id_karyawan);

async function startCamera() {
    try {
        const stream = await navigator.mediaDevices.getUserMedia({
            video: { width: { ideal: 640 }, height: { ideal: 480 }, facingMode: 'user' }
        });
        video.srcObject = stream;
        video.onloadedmetadata = () => {
            cameraStatus.className   = 'badge bg-success';
            cameraStatus.textContent = 'Kamera Aktif';
        };
    } catch (err) {
        cameraStatus.className   = 'badge bg-danger';
        cameraStatus.textContent = 'Kamera Tidak Tersedia';
        btnRegister.disabled     = true;
        showMessage('error', 'Tidak dapat mengakses kamera: ' + err.message);
    }
}

function capturePhoto() {
    const ctx = canvas.getContext('2d');
    canvas.width  = video.videoWidth;
    canvas.height = video.videoHeight;
    ctx.save();
    ctx.translate(canvas.width, 0);
    ctx.scale(-1, 1);
    ctx.drawImage(video, 0, 0);
    ctx.restore();
    return canvas.toDataURL('image/jpeg', 0.9);
}

async function registerFace() {
    const photo = capturePhoto();
    btnRegister.disabled  = true;
    btnRegister.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Memproses&hellip;';
    cameraOverlay.classList.add('active');
    statusMsg.style.display = 'none';

    try {
        const res  = await fetch('/api/karyawan/register-face', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body:    JSON.stringify({ id_karyawan: idKaryawan, photo }),
        });
        const data = await res.json();

        if (data.success) {
            showMessage('success',
                '<div class="d-flex align-items-start gap-2">' +
                '  <i class="bi bi-check-circle-fill text-success fs-5 mt-1"></i>' +
                '  <div>' +
                '    <div class="fw-semibold">' + data.message + '</div>' +
                '    <a href="/karyawan" class="small text-success fw-semibold mt-1 d-inline-block">' +
                '      <i class="bi bi-arrow-left me-1"></i>Kembali ke Daftar Karyawan' +
                '    </a>' +
                '  </div>' +
                '</div>'
            );
        } else {
            showMessage('error',
                '<div class="d-flex align-items-start gap-2">' +
                '  <i class="bi bi-x-circle-fill text-danger fs-5 mt-1"></i>' +
                '  <div class="fw-semibold">' + data.message + '</div>' +
                '</div>'
            );
            btnRegister.disabled  = false;
            btnRegister.innerHTML = '<i class="bi bi-camera-fill me-2"></i>Coba Lagi';
        }
    } catch (err) {
        showMessage('error', 'Terjadi kesalahan: ' + err.message);
        btnRegister.disabled  = false;
        btnRegister.innerHTML = '<i class="bi bi-camera-fill me-2"></i>Coba Lagi';
    } finally {
        cameraOverlay.classList.remove('active');
    }
}

function showMessage(type, html) {
    statusMsg.style.display = 'block';
    statusMsg.className     = type === 'success' ? 'alert alert-success' : 'alert alert-danger';
    statusMsg.innerHTML     = html;
}

btnRegister.addEventListener('click', registerFace);
startCamera();

}); // DOMContentLoaded
</script>
@endsection
