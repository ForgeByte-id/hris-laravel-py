<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Daftar Karyawan - HRIS</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f6fa;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            padding: 30px;
        }

        h1 {
            color: #333;
            margin-bottom: 30px;
            font-size: 28px;
        }

        .btn-back {
            background: #6c757d;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            margin-bottom: 20px;
            text-decoration: none;
            display: inline-block;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px;
            text-align: left;
            font-weight: 600;
        }

        td {
            padding: 15px;
            border-bottom: 1px solid #eee;
        }

        tr:hover {
            background: #f8f9fa;
        }

        .status-registered {
            background: #d4edda;
            color: #155724;
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: bold;
            display: inline-block;
        }

        .status-not-registered {
            background: #f8d7da;
            color: #721c24;
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: bold;
            display: inline-block;
        }

        .face-photo {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 50%;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
        }

        .btn-register {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
        }

        .btn-delete {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
        }

        .btn-register:hover,
        .btn-delete:hover {
            opacity: 0.9;
        }

        .no-data {
            text-align: center;
            padding: 40px;
            color: #999;
        }

        .info-box {
            background: #e7f3ff;
            border-left: 4px solid #667eea;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .loading {
            text-align: center;
            padding: 20px;
        }

        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            animation: spin 1s linear infinite;
            margin: 0 auto;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="/dashboard" class="btn-back">← Kembali ke Dashboard</a>
        
        <h1>👥 Daftar Karyawan</h1>

        <div class="info-box">
            <p><strong>Total Karyawan:</strong> {{ $karyawan->count() }}</p>
            <p><strong>Sudah Registrasi Wajah:</strong> {{ $karyawan->filter(fn($k) => $k->face_embedding)->count() }}</p>
            <p><strong>Belum Registrasi:</strong> {{ $karyawan->filter(fn($k) => !$k->face_embedding)->count() }}</p>
        </div>

        @if($karyawan->count() > 0)
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nama</th>
                    <th>Foto Wajah</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach($karyawan as $k)
                <tr>
                    <td>{{ $k->id_karyawan }}</td>
                    <td>{{ $k->nama ?? 'Karyawan ' . $k->id_karyawan }}</td>
                    <td>
                        @if($k->face_embedding)
                            <div style="width: 50px; height: 50px; background: #d4edda; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #155724; font-size: 24px;">
                                ✓
                            </div>
                        @else
                            <div style="width: 50px; height: 50px; background: #e9ecef; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                👤
                            </div>
                        @endif
                    </td>
                    <td>
                        @if($k->face_embedding)
                            <span class="status-registered">✓ Terdaftar</span>
                        @else
                            <span class="status-not-registered">✗ Belum Terdaftar</span>
                        @endif
                    </td>
                    <td>
                        <div class="action-buttons">
                            @if($k->face_embedding)
                                <button class="btn-delete" onclick="deleteFace({{ $k->id_karyawan }})">
                                    🗑 Hapus
                                </button>
                                <a href="{{ route('karyawan.register-face', $k->id_karyawan) }}" class="btn-register">
                                    🔄 Update
                                </a>
                            @else
                                <a href="{{ route('karyawan.register-face', $k->id_karyawan) }}" class="btn-register">
                                    📸 Daftar Wajah
                                </a>
                            @endif
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <div class="no-data">
            <h3>Belum ada data karyawan</h3>
            <p>Silakan tambahkan data karyawan terlebih dahulu</p>
        </div>
        @endif
    </div>

    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

        async function deleteFace(idKaryawan) {
            if (!confirm('Yakin ingin menghapus data wajah karyawan ini?')) {
                return;
            }

            try {
                const response = await fetch(`/api/karyawan/${idKaryawan}/face`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Content-Type': 'application/json'
                    }
                });

                const data = await response.json();

                if (data.success) {
                    alert(data.message);
                    location.reload();
                } else {
                    alert('Gagal menghapus: ' + data.message);
                }
            } catch (error) {
                alert('Terjadi kesalahan: ' + error.message);
            }
        }
    </script>
</body>
</html>
