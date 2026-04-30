@extends('layouts.app')

@section('content')
<div class="container" style="max-width: 1200px; margin: 20px auto; padding: 20px;">
    
    @if(session('success'))
    <div style="background: #d4edda; color: #155724; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #c3e6cb;">
        ✅ {{ session('success') }}
    </div>
    @endif

    @if(session('error'))
    <div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #f5c6cb;">
        ❌ {{ session('error') }}
    </div>
    @endif

    <div class="card">
        <div class="card-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; display: flex; justify-content: space-between; align-items: center;">
            <h2 style="margin: 0;">Pengajuan Cuti Saya</h2>
            <a href="{{ route('cuti.create') }}" 
               style="background: white; color: #667eea; padding: 10px 20px; border-radius: 8px; text-decoration: none; font-weight: 600;">
                ➕ Ajukan Cuti Baru
            </a>
        </div>

        <div class="card-body" style="padding: 30px;">
            <!-- Info Summary -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 30px;">
                <div style="background: #e7f3ff; padding: 20px; border-radius: 10px; border-left: 4px solid #667eea;">
                    <h4 style="margin: 0 0 10px 0; color: #667eea;">Total Pengajuan</h4>
                    <h2 style="margin: 0; color: #333;">{{ $cutiList->count() }}</h2>
                </div>
                <div style="background: #fff3cd; padding: 20px; border-radius: 10px; border-left: 4px solid #ffc107;">
                    <h4 style="margin: 0 0 10px 0; color: #856404;">Menunggu</h4>
                    <h2 style="margin: 0; color: #333;">{{ $cutiList->where('status_persetujuan', 'pending')->count() }}</h2>
                </div>
                <div style="background: #d4edda; padding: 20px; border-radius: 10px; border-left: 4px solid #28a745;">
                    <h4 style="margin: 0 0 10px 0; color: #155724;">Disetujui</h4>
                    <h2 style="margin: 0; color: #333;">{{ $cutiList->where('status_persetujuan', 'approved')->count() }}</h2>
                </div>
                <div style="background: #f8d7da; padding: 20px; border-radius: 10px; border-left: 4px solid #dc3545;">
                    <h4 style="margin: 0 0 10px 0; color: #721c24;">Ditolak</h4>
                    <h2 style="margin: 0; color: #333;">{{ $cutiList->where('status_persetujuan', 'rejected')->count() }}</h2>
                </div>
            </div>

            <!-- Tabel Cuti -->
            @if($cutiList->count() > 0)
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: #f8f9fa;">
                            <th style="padding: 15px; text-align: left; border-bottom: 2px solid #dee2e6;">No</th>
                            <th style="padding: 15px; text-align: left; border-bottom: 2px solid #dee2e6;">Jenis Cuti</th>
                            <th style="padding: 15px; text-align: left; border-bottom: 2px solid #dee2e6;">Tanggal</th>
                            <th style="padding: 15px; text-align: left; border-bottom: 2px solid #dee2e6;">Durasi</th>
                            <th style="padding: 15px; text-align: left; border-bottom: 2px solid #dee2e6;">Status</th>
                            <th style="padding: 15px; text-align: left; border-bottom: 2px solid #dee2e6;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($cutiList as $index => $cuti)
                        <tr style="border-bottom: 1px solid #dee2e6;">
                            <td style="padding: 15px;">{{ $index + 1 }}</td>
                            <td style="padding: 15px;">
                                <strong>{{ $cuti->jenis_cuti }}</strong>
                            </td>
                            <td style="padding: 15px;">
                                {{ $cuti->tanggal_mulai->format('d/m/Y') }} - 
                                {{ $cuti->tanggal_selesai->format('d/m/Y') }}
                            </td>
                            <td style="padding: 15px;">
                                {{ $cuti->jumlah_hari }} hari
                            </td>
                            <td style="padding: 15px;">
                                @if($cuti->status_persetujuan === 'pending')
                                    <span style="background: #fff3cd; color: #856404; padding: 5px 12px; border-radius: 15px; font-size: 12px; font-weight: 600;">
                                        ⏳ Menunggu
                                    </span>
                                @elseif($cuti->status_persetujuan === 'approved')
                                    <span style="background: #d4edda; color: #155724; padding: 5px 12px; border-radius: 15px; font-size: 12px; font-weight: 600;">
                                        ✅ Disetujui
                                    </span>
                                @else
                                    <span style="background: #f8d7da; color: #721c24; padding: 5px 12px; border-radius: 15px; font-size: 12px; font-weight: 600;">
                                        ❌ Ditolak
                                    </span>
                                @endif
                            </td>
                            <td style="padding: 15px;">
                                <div style="display: flex; gap: 5px;">
                                <a href="{{ route('cuti.show', $cuti->id_cuti) }}" 
                                   style="background: #667eea; color: white; padding: 8px 15px; border-radius: 5px; text-decoration: none; font-size: 14px; margin-right: 5px;">
                                    👁️
                                </a>
                                @if($cuti->status_persetujuan === 'pending')
                                <form action="{{ route('cuti.cancel', $cuti->id_cuti) }}" method="POST" style="display: inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" 
                                            onclick="return confirm('Yakin ingin membatalkan pengajuan ini?')"
                                            style="background: #dc3545; color: white; padding: 8px 15px; border-radius: 5px; border: none; cursor: pointer; font-size: 14px;">
                                        🗑️
                                    </button>
                                </form>
                                @endif
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div style="text-align: center; padding: 60px 20px; color: #999;">
                <h3>Belum Ada Pengajuan Cuti</h3>
                <p>Klik tombol "Ajukan Cuti Baru" untuk membuat pengajuan</p>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
