@extends('layouts.app')

@section('content')
<div class="container" style="max-width: 1000px; margin: 20px auto; padding: 20px;">
    <div class="card">
        <div class="card-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px;">
            <h2 style="margin: 0;">Jadwal Kerja - {{ $karyawan->nama }}</h2>
        </div>

        <div class="card-body" style="padding: 30px;">
            
            <!-- Info Karyawan -->
            <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 25px;">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px;">
                    <div>
                        <strong>Nama:</strong> {{ $karyawan->nama }}
                    </div>
                    <div>
                        <strong>Jabatan:</strong> {{ $karyawan->jabatan }}
                    </div>
                    <div>
                        <strong>Divisi:</strong> {{ $karyawan->divisi }}
                    </div>
                </div>
            </div>

            <!-- Filter Bulan -->
            <form method="GET" style="margin-bottom: 20px; display: flex; gap: 10px; align-items: center;">
                <label style="font-weight: 600;">Pilih Bulan:</label>
                <input type="month" name="bulan" value="{{ $bulan }}" 
                       style="padding: 10px; border: 1px solid #ddd; border-radius: 8px;">
                <button type="submit" 
                        style="padding: 10px 20px; background: #667eea; color: white; border: none; border-radius: 8px; cursor: pointer;">
                    Tampilkan
                </button>
            </form>

            <!-- Summary -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin-bottom: 25px;">
                @php
                    $totalPagi = $jadwalList->where('jam_kerja', 'Pagi (07:00-15:00)')->count();
                    $totalSiang = $jadwalList->where('jam_kerja', 'Siang (15:00-23:00)')->count();
                    $totalMalam = $jadwalList->where('jam_kerja', 'Malam (23:00-07:00)')->count();
                    $totalLibur = $jadwalList->where('jam_kerja', 'Libur')->count();
                @endphp
                <div style="background: #4CAF50; color: white; padding: 15px; border-radius: 8px; text-align: center;">
                    <h3 style="margin: 0;">{{ $totalPagi }}</h3>
                    <small>Shift Pagi</small>
                </div>
                <div style="background: #FF9800; color: white; padding: 15px; border-radius: 8px; text-align: center;">
                    <h3 style="margin: 0;">{{ $totalSiang }}</h3>
                    <small>Shift Siang</small>
                </div>
                <div style="background: #2196F3; color: white; padding: 15px; border-radius: 8px; text-align: center;">
                    <h3 style="margin: 0;">{{ $totalMalam }}</h3>
                    <small>Shift Malam</small>
                </div>
                <div style="background: #f44336; color: white; padding: 15px; border-radius: 8px; text-align: center;">
                    <h3 style="margin: 0;">{{ $totalLibur }}</h3>
                    <small>Hari Libur</small>
                </div>
            </div>

            <!-- Tabel Jadwal -->
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: #f8f9fa;">
                            <th style="padding: 12px; border: 1px solid #dee2e6; text-align: left;">Tanggal</th>
                            <th style="padding: 12px; border: 1px solid #dee2e6; text-align: left;">Hari</th>
                            <th style="padding: 12px; border: 1px solid #dee2e6; text-align: left;">Jam Kerja</th>
                            <th style="padding: 12px; border: 1px solid #dee2e6; text-align: left;">Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($jadwalList as $jadwal)
                        <tr>
                            <td style="padding: 12px; border: 1px solid #dee2e6;">
                                {{ $jadwal->tanggal->format('d/m/Y') }}
                            </td>
                            <td style="padding: 12px; border: 1px solid #dee2e6;">
                                {{ $jadwal->tanggal->isoFormat('dddd') }}
                            </td>
                            <td style="padding: 12px; border: 1px solid #dee2e6;">
                                <span style="background: {{ $jadwal->shift_color }}; color: white; padding: 5px 12px; border-radius: 5px; font-weight: 600;">
                                    {{ $jadwal->jam_kerja }}
                                </span>
                            </td>
                            <td style="padding: 12px; border: 1px solid #dee2e6;">
                                {{ $jadwal->keterangan ?? '-' }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" style="padding: 40px; text-align: center; color: #999;">
                                Belum ada jadwal untuk bulan ini
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Back Button -->
            <a href="{{ route('jadwal.index') }}" 
               style="display: block; margin-top: 20px; padding: 14px; background: #6c757d; color: white; border: none; border-radius: 8px; font-weight: 600; text-align: center; text-decoration: none;">
                ← Kembali ke Jadwal Semua Karyawan
            </a>
        </div>
    </div>
</div>
@endsection
