<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="{{ asset('assets/css/absensi.css') }}">
    <title>Riwayat Absensi - HRIS</title>
</head>
<body>
    <div class="container">
        <button class="btn-back" onclick="window.location.href='/attendance'">← Kembali ke Absensi</button>
        
        <h1>Riwayat Absensi</h1>

        <!-- Filter Section -->
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
            
            <button type="submit" class="btn-filter">🔍 Cari</button>
        </form>

        <!-- Table -->
        @if($absensi->count() > 0)
        <table>
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Nama</th>
                    <th>Jam Masuk</th>
                    <th>Jam Pulang</th>
                    <th>Status</th>
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
                            {{ ucfirst($abs->status ?? 'hadir') }}
                        </span>
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

    <!-- Modal untuk preview foto -->
    <div id="photoModal" class="modal" onclick="closeModal()">
        <div class="modal-content">
            <span class="close-modal">&times;</span>
            <img id="modalImage" src="" alt="Preview">
        </div>
    </div>

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

        // Close on ESC key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeModal();
            }
        });
    </script>
</body>
</html>
