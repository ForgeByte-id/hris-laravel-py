<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="{{ asset('assets/css/absensi.css') }}">
    <title>Absensi - HRIS</title>
    
</head>
<body>
    <div class="container">
        <button class="btn-back" onclick="window.location.href='/dashboard'">← Kembali ke Dashboard</button>
        <h1>Absensi Karyawan</h1>
        
        <div class="info-box">
            <p><strong>Waktu:</strong> <span id="currentTime"></span></p>
            <p><strong>Tanggal:</strong> <span id="currentDate"></span></p>
        </div>

        <div class="camera-container">
            <video id="video" autoplay playsinline></video>
        </div>
        <canvas id="canvas"></canvas>

        <div class="button-group">
            <button class="btn-masuk" id="btnMasuk" onclick="checkIn('masuk')">
                Absen Masuk
            </button>
            <button class="btn-keluar" id="btnKeluar" onclick="checkIn('keluar')">
                Absen Keluar
            </button>
        </div>

        <button class="btn-history" onclick="window.location.href='/attendance/history'">
            Lihat Riwayat Absensi
        </button>

        <div id="statusMessage" class="status-message"></div>
    </div>

    <script>
        const video = document.getElementById('video');
        const canvas = document.getElementById('canvas');
        const context = canvas.getContext('2d');
        const btnMasuk = document.getElementById('btnMasuk');
        const btnKeluar = document.getElementById('btnKeluar');
        const statusMessage = document.getElementById('statusMessage');

        // Setup CSRF token for AJAX requests
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

        // Start camera
        async function startCamera() {
            try {
                const stream = await navigator.mediaDevices.getUserMedia({ 
                    video: { 
                        width: 640, 
                        height: 480,
                        facingMode: 'user'
                    } 
                });
                video.srcObject = stream;
            } catch (err) {
                showMessage('error', 'Tidak dapat mengakses kamera: ' + err.message);
            }
        }

        // Capture photo
        function capturePhoto() {
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            
            // Flip the image back to normal (undo mirror effect)
            context.translate(canvas.width, 0);
            context.scale(-1, 1);
            context.drawImage(video, 0, 0);
            
            return canvas.toDataURL('image/jpeg', 0.9);
        }

        // Check in/out
        async function checkIn(type) {
            const photo = capturePhoto();
            
            // Disable buttons
            btnMasuk.disabled = true;
            btnKeluar.disabled = true;
            
            showMessage('info', '<div class="spinner"></div><p>Memproses wajah Anda...</p>');

            try {
                const response = await fetch('/api/attendance/check-in', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({
                        photo: photo,
                        type: type
                    })
                });

                const data = await response.json();

                if (data.success) {
                    let message = `<strong>Berhasil!</strong><br>`;
                    message += `Nama: ${data.data.nama}<br>`;
                    message += `Waktu: ${data.data.waktu}<br>`;
                    message += `Status: ${data.data.status}<br>`;
                    message += `Akurasi: ${data.data.confidence.toFixed(2)}%`;
                    showMessage('success', message);
                } else {
                    showMessage('error', data.message);
                }
            } catch (error) {
                showMessage('error', 'Terjadi kesalahan: ' + error.message);
            } finally {
                // Enable buttons
                btnMasuk.disabled = false;
                btnKeluar.disabled = false;
            }
        }

        // Show message
        function showMessage(type, message) {
            statusMessage.className = 'status-message';
            statusMessage.style.display = 'block';
            
            if (type === 'success') {
                statusMessage.classList.add('status-success');
            } else if (type === 'error') {
                statusMessage.classList.add('status-error');
            } else {
                statusMessage.style.background = '#fff3cd';
                statusMessage.style.color = '#856404';
                statusMessage.style.border = '1px solid #ffeeba';
            }
            
            statusMessage.innerHTML = message;
        }

        // Update time
        function updateTime() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('id-ID');
            const dateString = now.toLocaleDateString('id-ID', { 
                weekday: 'long', 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric' 
            });
            
            document.getElementById('currentTime').textContent = timeString;
            document.getElementById('currentDate').textContent = dateString;
        }

        // Initialize
        startCamera();
        updateTime();
        setInterval(updateTime, 1000);
    </script>
</body>
</html>
