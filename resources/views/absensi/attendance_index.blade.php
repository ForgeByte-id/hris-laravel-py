@extends('layouts.app')

@section('title', 'Absensi - HRIS')
@section('html_lang', 'id')
@section('show_loader', '0')
@section('show_bottom_nav', '0')
@section('use_app_capsule', '0')
@section('include_default_styles', '0')
@section('include_default_scripts', '0')
@section('show_chrome', '0')

@section('head')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
@endsection

@section('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/absensi.css') }}">
    <style>
        :root {
            --color-primary: #4a90e2;
            --color-success: #48bb78;
            --color-warning: #ff9800;
            --color-danger: #f44336;
            --color-info: #2196f3;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }

        .attendance-wrapper {
            width: 100%;
            max-width: 500px;
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
            overflow: hidden;
        }

        .attendance-header {
            background: linear-gradient(135deg, var(--color-primary), #667eea);
            color: white;
            padding: 2rem 1.5rem;
            text-align: center;
        }

        .attendance-header h1 {
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .attendance-datetime {
            opacity: 0.9;
            font-size: 0.95rem;
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .attendance-body {
            padding: 2rem 1.5rem;
        }

        .state-indicator {
            background: #f8f9fa;
            border-left: 4px solid var(--color-info);
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1.5rem;
            text-align: center;
        }

        .state-indicator.ready {
            border-left-color: var(--color-info);
            background: #e3f2fd;
        }

        .state-indicator.waiting {
            border-left-color: var(--color-warning);
            background: #fff3e0;
        }

        .state-indicator.completed {
            border-left-color: var(--color-success);
            background: #e8f5e9;
        }

        .state-icon {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
            display: inline-block;
        }

        .state-label {
            font-weight: 600;
            font-size: 1.1rem;
            margin: 0.5rem 0;
        }

        .state-label.ready { color: var(--color-info); }
        .state-label.waiting { color: var(--color-warning); }
        .state-label.completed { color: var(--color-success); }

        .state-detail {
            font-size: 0.9rem;
            color: #666;
            margin-top: 0.5rem;
        }

        .camera-container {
            position: relative;
            width: 100%;
            aspect-ratio: 4/3;
            background: #000;
            border-radius: 12px;
            overflow: hidden;
            margin-bottom: 1.5rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        #video {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transform: scaleX(-1);
        }

        canvas {
            display: none;
        }

        .camera-instruction {
            position: absolute;
            bottom: 1rem;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(0, 0, 0, 0.6);
            color: white;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            font-size: 0.9rem;
            text-align: center;
            z-index: 10;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            pointer-events: none;
        }

        .loading-overlay {
            position: absolute;
            inset: 0;
            background: rgba(0, 0, 0, 0.7);
            display: none;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            gap: 1rem;
            z-index: 20;
            border-radius: 12px;
        }

        .loading-overlay.active {
            display: flex;
        }

        .spinner {
            width: 50px;
            height: 50px;
            border: 4px solid rgba(255, 255, 255, 0.3);
            border-top: 4px solid white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .loading-text {
            color: white;
            font-weight: 500;
            font-size: 1rem;
        }

        .button-group {
            display: flex;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .btn-capture {
            flex: 1;
            padding: 1rem;
            font-size: 1rem;
            font-weight: 600;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            white-space: nowrap;
            text-transform: none;
            letter-spacing: 0;
        }

        .btn-capture {
            background: linear-gradient(135deg, var(--color-primary), #667eea);
            color: white;
            box-shadow: 0 4px 12px rgba(74, 144, 226, 0.3);
        }

        .btn-capture:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(74, 144, 226, 0.4);
        }

        .btn-capture:active:not(:disabled) {
            transform: translateY(0);
        }

        .btn-capture:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            background: #ccc;
            box-shadow: none;
        }

        .btn-secondary {
            flex: 1;
            padding: 1rem;
            font-size: 0.95rem;
            font-weight: 600;
            border: 2px solid var(--color-primary);
            background: white;
            color: var(--color-primary);
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn-secondary:hover {
            background: #f0f4ff;
            transform: translateY(-2px);
        }

        .status-message {
            display: none;
            padding: 1.5rem;
            border-radius: 10px;
            margin-top: 1rem;
            font-weight: 500;
            text-align: center;
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .status-success {
            background: #e8f5e9;
            color: #2e7d32;
            border: 1px solid #c8e6c9;
        }

        .status-error {
            background: #ffebee;
            color: #c62828;
            border: 1px solid #ffcdd2;
        }

        .status-info {
            background: #e3f2fd;
            color: #1565c0;
            border: 1px solid #bbdefb;
        }

        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            position: absolute;
            top: 1rem;
            left: 1rem;
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: none;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            z-index: 30;
        }

        .back-button:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateX(-3px);
        }

        @media (max-width: 480px) {
            .attendance-header {
                padding: 1.5rem 1rem;
            }

            .attendance-header h1 {
                font-size: 1.5rem;
            }

            .attendance-body {
                padding: 1.5rem 1rem;
            }

            .btn-capture {
                padding: 0.875rem;
                font-size: 0.95rem;
            }

            .state-icon {
                font-size: 2rem;
            }

            .state-label {
                font-size: 1rem;
            }
        }
    </style>
@endsection

@section('content')
    <div class="attendance-wrapper">
        <button class="back-button" onclick="window.location.href='/dashboard'">
            <i class="bi bi-chevron-left"></i> Kembali
        </button>

        <div class="attendance-header">
            <h1><i class="bi bi-camera-video"></i> Absensi Karyawan</h1>
            <div class="attendance-datetime">
                <span id="currentDate"></span>
                <span id="currentTime" style="font-size: 1.3rem; font-weight: 600;"></span>
            </div>
        </div>

        <div class="attendance-body">
            @if(!$serviceHealthy)
                <div class="status-message status-error" style="display:block; margin-bottom: 1rem;">
                    Service face recognition sedang tidak tersedia. Silakan coba lagi beberapa saat.
                </div>
            @endif

            {{-- State Indicator --}}
            <div id="stateIndicator" class="state-indicator ready">
                {{-- <div class="state-icon" id="stateIcon"><i class="bi bi-circle-fill" style="color: #4a90e2;"></i></div> --}}
                <div class="state-label ready" id="stateLabel">Siap Absen Masuk</div>
                <div class="state-detail" id="stateDetail">Ambil foto wajah Anda untuk konfirmasi kehadiran</div>
            </div>

            {{-- Camera Container --}}
            <div class="camera-container" id="cameraContainer">
                <video id="video" autoplay playsinline></video>
                <canvas id="canvas" style="display:none;"></canvas>
                <div class="loading-overlay" id="loadingOverlay">
                    <div class="spinner"></div>
                    <div class="loading-text">Memproses wajah Anda...</div>
                </div>
                <div class="camera-instruction">
                    <i class="bi bi-info-circle"></i>
                    <span>Pastikan wajah terlihat jelas di kamera</span>
                </div>
            </div>

            {{-- Capture Button --}}
            <div class="button-group">
                <button class="btn-capture" id="btnCapture" onclick="captureAndSubmit()">
                    <i class="bi bi-camera-fill"></i>
                    <span id="btnLabel">Ambil Gambar untuk Masuk</span>
                </button>
            </div>

            {{-- Secondary Action --}}
            <button class="btn-secondary" onclick="window.location.href='/attendance/history'">
                <i class="bi bi-clock-history"></i>
                Lihat Riwayat
            </button>

            {{-- Status Message --}}
            <div id="statusMessage" class="status-message"></div>
        </div>
    </div>
@endsection


@section('scripts')
<script>
const video = document.getElementById('video');
const canvas = document.getElementById('canvas');
const context = canvas.getContext('2d');
const btnCapture = document.getElementById('btnCapture');
const statusMessage = document.getElementById('statusMessage');
const loadingOverlay = document.getElementById('loadingOverlay');

const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
const serviceHealthy = @json($serviceHealthy);

async function startCamera() {
    if (!serviceHealthy) {
        btnCapture.disabled = true;
        showMessage('error', 'Service face recognition tidak tersedia.');
        return;
    }

    try {
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            throw new Error('Browser tidak support kamera');
        }

        const stream = await navigator.mediaDevices.getUserMedia({
            video: {
                width: { ideal: 640 },
                height: { ideal: 480 },
                facingMode: 'user'
            }
        });

        video.srcObject = stream;

        video.onloadedmetadata = () => {
            video.play();
        };

    } catch (err) {
        console.error('Camera error:', err);

        if (err.name === 'NotAllowedError') {
            showMessage('error', 'Akses kamera ditolak. Izinkan di browser.');
        } else if (err.name === 'NotFoundError') {
            showMessage('error', 'Kamera tidak ditemukan.');
        } else {
            showMessage('error', err.message);
        }

        btnCapture.disabled = true;
    }
}

function capturePhoto() {
    if (!video.videoWidth || !video.videoHeight) {
        showMessage('error', 'Kamera belum siap');
        return null;
    }

    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;

    context.save();
    context.translate(canvas.width, 0);
    context.scale(-1, 1);
    context.drawImage(video, 0, 0);
    context.restore();

    return canvas.toDataURL('image/jpeg', 0.9);
}

async function captureAndSubmit() {
    const photo = capturePhoto();
    if (!photo) return;

    btnCapture.disabled = true;
    if (loadingOverlay) loadingOverlay.classList.add('active');

    try {
        const response = await fetch('/api/attendance/check-in', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({ photo: photo })
        });

        // handle response non-json (500 error biasanya HTML)
        const text = await response.text();

        let data;
        try {
            data = JSON.parse(text);
        } catch (e) {
            console.error('Response bukan JSON:', text);
            throw new Error('Server error (bukan JSON)');
        }

        if (!response.ok) {
            throw new Error(data.message || 'Request gagal');
        }

        if (data.success) {
            const actionLabel = data.action === 'clock_in' ? 'Masuk' : 'Pulang';

            let message = `<strong>Berhasil Absen ${actionLabel}</strong><br>`;
            message += `Nama: ${data.data.nama}<br>`;
            message += `Waktu: ${data.data.waktu}<br>`;
            message += `Status: ${data.data.status}<br>`;
            message += `Akurasi: ${(data.data.confidence).toFixed(2)}%`;

            showMessage('success', message);
        } else {
            showMessage('error', data.message || 'Gagal absensi');
        }

    } catch (error) {
        console.error(error);
        showMessage('error', error.message);
    } finally {
        btnCapture.disabled = false;
        if (loadingOverlay) loadingOverlay.classList.remove('active');
    }
}

function showMessage(type, message) {
    if (!statusMessage) return;

    statusMessage.className = 'status-message';
    statusMessage.style.display = 'block';

    if (type === 'success') {
        statusMessage.classList.add('status-success');
    } else if (type === 'error') {
        statusMessage.classList.add('status-error');
    } else {
        statusMessage.classList.add('status-info');
    }

    statusMessage.innerHTML = message;
}

function updateTime() {
    const now = new Date();

    const timeString = now.toLocaleTimeString('id-ID', {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
    });

    const dateString = now.toLocaleDateString('id-ID', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });

    document.getElementById('currentTime').textContent = timeString;
    document.getElementById('currentDate').textContent = dateString;
}

// init
startCamera();
updateTime();
setInterval(updateTime, 1000);
</script>
@endsection
