<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="{{ asset('assets/css/absensi.css') }}">
    <title>Registrasi Wajah - {{ $karyawan->nama }}</title>
    <style>
        .karyawan-info {
            background: #e7f3ff;
            border-left: 4px solid #667eea;
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .karyawan-info h3 {
            margin: 0 0 6px 0;
            color: #333;
        }
        .karyawan-info p {
            margin: 3px 0;
            color: #555;
            font-size: 14px;
        }
        .status-registered-badge {
            background: #d4edda;
            color: #155724;
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: bold;
            display: inline-block;
            margin-top: 6px;
        }
        .status-not-registered-badge {
            background: #f8d7da;
            color: #721c24;
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: bold;
            display: inline-block;
            margin-top: 6px;
        }
        .btn-register-face {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 15px;
        }
        .btn-register-face:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        .instructions {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 14px;
            color: #856404;
        }
    </style>
</head>
<body>
    <div class="container">
        <button class="btn-back" onclick="window.location.href='/karyawan'">← Kembali ke Daftar Karyawan</button>

        <h1><i class="bi bi-camera-fill" style="font-size: 1.8rem; margin-right: 0.5rem; color: #667eea;"></i>Registrasi Wajah</h1>

        <div class="karyawan-info">
            <h3>{{ $karyawan->nama }}</h3>
            <p><strong>Jabatan:</strong> {{ $karyawan->jabatan ?? '-' }}</p>
            <p><strong>Divisi:</strong> {{ $karyawan->divisi ?? '-' }}</p>
            @if($karyawan->face_embedding)
                <span class="status-registered-badge"><i class="bi bi-check-circle-fill" style="margin-right: 4px;"></i>Wajah Sudah Terdaftar</span>
            @else
                <span class="status-not-registered-badge"><i class="bi bi-x-circle-fill" style="margin-right: 4px;"></i>Belum Ada Data Wajah</span>
            @endif
        </div>

        <div class="instructions">
            <i class="bi bi-exclamation-triangle-fill" style="font-size: 1.1rem; color: #856404; margin-right: 0.5rem;"></i><strong>Petunjuk:</strong> Pastikan wajah menghadap kamera dengan pencahayaan yang cukup.
            Posisikan wajah di tengah frame kamera, lalu klik tombol "Daftarkan Wajah".
        </div>

        <div class="camera-container">
            <video id="video" autoplay playsinline></video>
        </div>
        <canvas id="canvas"></canvas>

        <button class="btn-register-face" id="btnRegister" onclick="registerFace()">
            <i class="bi bi-camera-fill" style="margin-right: 0.5rem;"></i>Daftarkan Wajah
        </button>

        <div id="statusMessage" class="status-message" style="display:none;"></div>
    </div>

    <script>
        const video   = document.getElementById('video');
        const canvas  = document.getElementById('canvas');
        const context = canvas.getContext('2d');
        const btnRegister   = document.getElementById('btnRegister');
        const statusMessage = document.getElementById('statusMessage');
        const csrfToken     = document.querySelector('meta[name="csrf-token"]').content;
        const idKaryawan    = {{ $karyawan->id_karyawan }};

        async function startCamera() {
            try {
                const stream = await navigator.mediaDevices.getUserMedia({
                    video: { width: 640, height: 480, facingMode: 'user' }
                });
                video.srcObject = stream;
            } catch (err) {
                showMessage('error', 'Tidak dapat mengakses kamera: ' + err.message);
            }
        }

        function capturePhoto() {
            canvas.width  = video.videoWidth;
            canvas.height = video.videoHeight;
            context.setTransform(1, 0, 0, 1, 0, 0);
            context.translate(canvas.width, 0);
            context.scale(-1, 1);
            context.drawImage(video, 0, 0);
            return canvas.toDataURL('image/jpeg', 0.9);
        }

        async function registerFace() {
            const photo = capturePhoto();

            btnRegister.disabled = true;
            showMessage('info', '<div class="spinner"></div><p>Memproses wajah Anda...</p>');

            try {
                const response = await fetch('/api/karyawan/register-face', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({
                        id_karyawan: idKaryawan,
                        photo: photo
                    })
                });

                const data = await response.json();

                if (data.success) {
                    showMessage('success', '<i class="bi bi-check-circle-fill" style="margin-right: 6px; font-size: 1.1rem;"></i>' + data.message + '<br><br><a href="/karyawan" style="color:#155724;font-weight:600;"><i class="bi bi-arrow-left" style="margin-right: 4px;"></i>Kembali ke Daftar Karyawan</a>');
                } else {
                    showMessage('error', '<i class="bi bi-x-circle-fill" style="margin-right: 6px; font-size: 1.1rem;"></i>' + data.message);
                    btnRegister.disabled = false;
                }
            } catch (error) {
                showMessage('error', 'Terjadi kesalahan: ' + error.message);
                btnRegister.disabled = false;
            }
        }

        function showMessage(type, message) {
            statusMessage.style.display = 'block';
            statusMessage.className     = 'status-message';

            if (type === 'success') {
                statusMessage.classList.add('status-success');
            } else if (type === 'error') {
                statusMessage.classList.add('status-error');
            } else {
                statusMessage.style.background = '#fff3cd';
                statusMessage.style.color      = '#856404';
                statusMessage.style.border     = '1px solid #ffeeba';
            }

            statusMessage.innerHTML = message;
        }

        startCamera();
    </script>
</body>
</html>
