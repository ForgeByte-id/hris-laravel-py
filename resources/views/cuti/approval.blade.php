@extends('layouts.app')

@section('content')
<div class="container" style="max-width: 1200px; margin: 20px auto; padding: 20px;">
    
    @if(session('success'))
    <div style="background: #d4edda; color: #155724; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #c3e6cb;">
        ✅ {{ session('success') }}
    </div>
    @endif

    <div class="card">
        <div class="card-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px;">
            <h2 style="margin: 0;">Approval Pengajuan Cuti</h2>
        </div>

        <div class="card-body" style="padding: 30px;">
            
            @if($cutiList->count() > 0)
            <div style="background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; border-radius: 5px; margin-bottom: 25px;">
                <p style="margin: 0; color: #856404;">
                    📋 Terdapat <strong>{{ $cutiList->count() }}</strong> pengajuan cuti yang menunggu persetujuan
                </p>
            </div>

            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: #f8f9fa;">
                            <th style="padding: 15px; text-align: left; border-bottom: 2px solid #dee2e6;">No</th>
                            <th style="padding: 15px; text-align: left; border-bottom: 2px solid #dee2e6;">Nama Karyawan</th>
                            <th style="padding: 15px; text-align: left; border-bottom: 2px solid #dee2e6;">Jabatan</th>
                            <th style="padding: 15px; text-align: left; border-bottom: 2px solid #dee2e6;">Jenis Cuti</th>
                            <th style="padding: 15px; text-align: left; border-bottom: 2px solid #dee2e6;">Tanggal</th>
                            <th style="padding: 15px; text-align: left; border-bottom: 2px solid #dee2e6;">Durasi</th>
                            <th style="padding: 15px; text-align: left; border-bottom: 2px solid #dee2e6;">Keterangan</th>
                            <th style="padding: 15px; text-align: center; border-bottom: 2px solid #dee2e6;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($cutiList as $index => $cuti)
                        <tr style="border-bottom: 1px solid #dee2e6;">
                            <td style="padding: 15px;">{{ $index + 1 }}</td>
                            <td style="padding: 15px;">
                                <strong>{{ $cuti->karyawan->nama }}</strong>
                            </td>
                            <td style="padding: 15px;">
                                {{ $cuti->karyawan->jabatan }}
                            </td>
                            <td style="padding: 15px;">
                                <span style="background: #e7f3ff; padding: 5px 10px; border-radius: 5px; font-size: 13px;">
                                    {{ $cuti->jenis_cuti }}
                                </span>
                            </td>
                            <td style="padding: 15px;">
                                {{ $cuti->tanggal_mulai->format('d/m/Y') }}<br>
                                <small style="color: #666;">s/d {{ $cuti->tanggal_selesai->format('d/m/Y') }}</small>
                            </td>
                            <td style="padding: 15px;">
                                <strong>{{ $cuti->jumlah_hari }}</strong> hari
                            </td>
                            <td style="padding: 15px; max-width: 200px;">
                                <small>{{ $cuti->keterangan ?? '-' }}</small>
                            </td>
                            <td style="padding: 15px; text-align: center;">
                                <form action="{{ route('cuti.update-status', $cuti->id_cuti) }}" method="POST" style="display: inline-block;">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="status" value="approved">
                                    <button type="submit" 
                                            onclick="return confirm('Setujui pengajuan cuti ini?')"
                                            style="background: #28a745; color: white; padding: 8px 15px; border-radius: 5px; border: none; cursor: pointer; font-size: 14px; margin: 2px;">
                                        ✅ Setujui
                                    </button>
                                </form>
                                <form action="{{ route('cuti.update-status', $cuti->id_cuti) }}" method="POST" style="display: inline-block;">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="status" value="rejected">
                                    <button type="submit" 
                                            onclick="return confirm('Tolak pengajuan cuti ini?')"
                                            style="background: #dc3545; color: white; padding: 8px 15px; border-radius: 5px; border: none; cursor: pointer; font-size: 14px; margin: 2px;">
                                        ❌ Tolak
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div style="text-align: center; padding: 60px 20px; color: #999;">
                <h3>✅ Tidak Ada Pengajuan Pending</h3>
                <p>Semua pengajuan cuti sudah diproses</p>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
