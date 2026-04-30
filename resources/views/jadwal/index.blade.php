@extends('layouts.app')

@section('content')
<div class="container" style="max-width: 1400px; margin: 20px auto; padding: 20px;">
    
    @if(session('success'))
    <div style="background: #d4edda; color: #155724; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
        ✅ {{ session('success') }}
    </div>
    @endif

    @if(session('error'))
    <div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
        ❌ {{ session('error') }}
    </div>
    @endif

    <div class="card">
        <div class="card-header" style="background: white; color: white; padding: 20px;">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                <h2 style="margin: 0;">Jadwal Kerja Karyawan</h2>
                <div style="display: flex; gap: 10px;">
                    <a href="{{ route('jadwal.create') }}" 
                       style="background: #1E74FD; color: white; padding: 10px 20px; border-radius: 8px; text-decoration: none; font-weight: 600;">
                        Tambah Jadwal
                    </a>
                    <a href="{{ route('jadwal.bulk-create') }}" 
                       style="background: #28a745; color: white; padding: 10px 20px; border-radius: 8px; text-decoration: none; font-weight: 600;">
                        Input Massal
                    </a>
                </div>
            </div>
        </div>

        <div class="card-body" style="padding: 30px;">
            
            <!-- Debug Info (uncomment jika perlu debug) -->
            {{-- 
            <div style="background: #fff3cd; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                <strong>Debug Info:</strong><br>
                Total Karyawan: {{ $karyawanList->count() }}<br>
                Total Jadwal: {{ $jadwalList->flatten()->count() }}<br>
                Bulan: {{ $bulan }}<br>
                Tanggal Awal: {{ $tanggalAwal->format('Y-m-d') }}<br>
                Tanggal Akhir: {{ $tanggalAkhir->format('Y-m-d') }}
            </div>
            --}}

            <!-- Filter Bulan -->
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; flex-wrap: wrap; gap: 15px;">
                <form method="GET" style="display: flex; gap: 10px; align-items: center;">
                    <label style="font-weight: 600;">Pilih Bulan:</label>
                    <input type="month" name="bulan" value="{{ $bulan }}" 
                           style="padding: 10px; border: 1px solid #ddd; border-radius: 8px;">
                    <button type="submit" 
                            style="padding: 10px 20px; background: #667eea; color: white; border: none; border-radius: 8px; cursor: pointer;">
                        Tampilkan
                    </button>
                </form>

                <!-- Set Libur Massal -->
                <button onclick="showLiburModal()" 
                        style="padding: 10px 20px; background: #dc3545; color: white; border: none; border-radius: 8px; cursor: pointer;">
                    Set Libur Massal
                </button>
            </div>

            <!-- Legend -->
            <div style="display: flex; gap: 20px; margin-bottom: 20px; padding: 15px; background: #f8f9fa; border-radius: 8px; flex-wrap: wrap;">
                <div><span style="background: #4CAF50; padding: 5px 10px; border-radius: 5px; color: white; font-weight: 600;">P</span> = Pagi (08:00-17:00)</div>
                <div><span style="background: #FF9800; padding: 5px 10px; border-radius: 5px; color: white; font-weight: 600;">M</span> = Middle (11:00-20:00)</div>
                <div><span style="background: #2196F3; padding: 5px 10px; border-radius: 5px; color: white; font-weight: 600;">S</span> = Siang (13:00-22:00)</div>
                <div><span style="background: #f44336; padding: 5px 10px; border-radius: 5px; color: white; font-weight: 600;">L</span> = Libur</div>
            </div>

            <!-- Tabel Jadwal -->
            @if($karyawanList->count() > 0)
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                    <thead>
                        <tr style="background: #f8f9fa;">
                            <th style="padding: 12px; border: 1px solid #dee2e6; text-align: left; position: sticky; left: 0; background: #f8f9fa; z-index: 10; min-width: 150px;">
                                Nama Karyawan
                            </th>
                            @php
                                $currentDate = $tanggalAwal->copy();
                            @endphp
                            @while($currentDate->lte($tanggalAkhir))
                            <th style="padding: 8px; border: 1px solid #dee2e6; text-align: center; min-width: 60px; {{ $currentDate->isWeekend() ? 'background: #ffe0e0;' : '' }}">
                                <div>{{ $currentDate->format('d') }}</div>
                                <small style="color: #666;">{{ $currentDate->isoFormat('dd') }}</small>
                            </th>
                            @php
                                $currentDate->addDay();
                            @endphp
                            @endwhile
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($karyawanList as $karyawan)
                        <tr>
                            <td style="padding: 12px; border: 1px solid #dee2e6; position: sticky; left: 0; background: white; z-index: 9;">
                                <strong>{{ $karyawan->nama }}</strong><br>
                                <small style="color: #666;">{{ $karyawan->jabatan }}</small>
                            </td>
                            @php
                                $jadwalKaryawan = $jadwalList->get($karyawan->id_karyawan, collect());
                                $currentDate = $tanggalAwal->copy();
                            @endphp
                            @while($currentDate->lte($tanggalAkhir))
                                @php
                                    $tanggalString = $currentDate->format('Y-m-d');
                                    $jadwal = $jadwalKaryawan->first(function($item) use ($tanggalString) {
                                        return $item->tanggal->format('Y-m-d') === $tanggalString;
                                    });
                                @endphp
                                <td style="padding: 5px; border: 1px solid #dee2e6; text-align: center; {{ $currentDate->isWeekend() ? 'background: #ffe0e0;' : '' }}">
                                    @if($jadwal)
                                        <a href="{{ route('jadwal.edit', $jadwal->id_jadwal) }}" 
                                           title="{{ $jadwal->jam_kerja }}"
                                           style="display: block; text-decoration: none; color: white; background: {{ $jadwal->shift_color }}; padding: 8px; border-radius: 5px; font-weight: 600;">
                                            {{ $jadwal->shift_short }}
                                        </a>
                                    @else
                                        <span style="color: #ccc;">-</span>
                                    @endif
                                </td>
                                @php
                                    $currentDate->addDay();
                                @endphp
                            @endwhile
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div style="text-align: center; padding: 60px 20px; color: #999;">
                <h3>Tidak Ada Data Karyawan</h3>
                <p>Silakan tambahkan data karyawan terlebih dahulu</p>
            </div>
            @endif
        </div>
    </div>
</div>

<!-- Modal Set Libur Massal -->
<div id="liburModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
    <div style="background: white; max-width: 500px; margin: 100px auto; padding: 30px; border-radius: 15px;">
        <h3 style="margin-bottom: 20px;">🏖️ Set Libur Massal</h3>
        <form action="{{ route('jadwal.libur-massal') }}" method="POST">
            @csrf
            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 600;">Tanggal Libur:</label>
                <input type="date" name="tanggal" required 
                       style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px;">
            </div>
            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 600;">Keterangan:</label>
                <input type="text" name="keterangan" placeholder="Contoh: Hari Libur Nasional" 
                       style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px;">
            </div>
            <div style="display: flex; gap: 10px;">
                <button type="submit" 
                        style="flex: 1; padding: 12px; background: #dc3545; color: white; border: none; border-radius: 8px; cursor: pointer;">
                    Set Libur
                </button>
                <button type="button" onclick="hideLiburModal()" 
                        style="flex: 1; padding: 12px; background: #6c757d; color: white; border: none; border-radius: 8px; cursor: pointer;">
                    Batal
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function showLiburModal() {
    document.getElementById('liburModal').style.display = 'block';
}

function hideLiburModal() {
    document.getElementById('liburModal').style.display = 'none';
}

// Close modal when clicking outside
document.getElementById('liburModal').addEventListener('click', function(e) {
    if (e.target === this) {
        hideLiburModal();
    }
});
</script>
@endsection
