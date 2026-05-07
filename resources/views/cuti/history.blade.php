@extends('layouts.app')

@section('content')
<div class="container" style="max-width: 1200px; margin: 20px auto; padding: 20px;">

    <div class="card">
        <div class="card-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; display: flex; justify-content: space-between; align-items: center;">
            <h2 style="margin: 0;">Riwayat Semua Cuti</h2>
            <a href="{{ route('cuti.approval') }}"
               style="background: white; color: #667eea; padding: 10px 20px; border-radius: 8px; text-decoration: none; font-weight: 600; display: flex; align-items: center; gap: 6px;">
                <i class="bi bi-clipboard-check" style="font-size: 1rem;"></i> Approval Pending
            </a>
        </div>

        <div class="card-body" style="padding: 30px;">

            <!-- Filter Section -->
            <form method="GET" action="{{ route('cuti.history') }}"
                  style="background: #f8f9fa; padding: 20px; border-radius: 10px; margin-bottom: 25px; display: flex; flex-wrap: wrap; gap: 15px; align-items: flex-end;">

                <div style="flex: 1; min-width: 180px;">
                    <label style="font-weight: 600; display: block; margin-bottom: 6px;">Karyawan</label>
                    <select name="id_karyawan"
                            style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px;">
                        <option value="">-- Semua Karyawan --</option>
                        @foreach($karyawanList as $k)
                            <option value="{{ $k->id_karyawan }}"
                                {{ request('id_karyawan') == $k->id_karyawan ? 'selected' : '' }}>
                                {{ $k->nama }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div style="flex: 1; min-width: 150px;">
                    <label style="font-weight: 600; display: block; margin-bottom: 6px;">Status</label>
                    <select name="status"
                            style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px;">
                        <option value="">-- Semua Status --</option>
                        <option value="pending"   {{ request('status') === 'pending'   ? 'selected' : '' }}>Menunggu</option>
                        <option value="approved"  {{ request('status') === 'approved'  ? 'selected' : '' }}>Disetujui</option>
                        <option value="rejected"  {{ request('status') === 'rejected'  ? 'selected' : '' }}>Ditolak</option>
                    </select>
                </div>

                <div style="flex: 1; min-width: 150px;">
                    <label style="font-weight: 600; display: block; margin-bottom: 6px;">Bulan</label>
                    <select name="bulan"
                            style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px;">
                        <option value="">-- Semua Bulan --</option>
                        @foreach(range(1, 12) as $bulan)
                            <option value="{{ $bulan }}"
                                {{ request('bulan') == $bulan ? 'selected' : '' }}>
                                {{ \Carbon\Carbon::create()->month($bulan)->translatedFormat('F') }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div style="display: flex; gap: 8px;">
                    <button type="submit"
                            style="padding: 10px 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; display: flex; align-items: center; gap: 6px;">
                        <i class="bi bi-search" style="font-size: 0.95rem;"></i> Cari
                    </button>
                    <a href="{{ route('cuti.history') }}"
                       style="padding: 10px 20px; background: #6c757d; color: white; border-radius: 6px; text-decoration: none; font-weight: 600; display: flex; align-items: center; gap: 6px;">
                        <i class="bi bi-arrow-clockwise" style="font-size: 0.95rem;"></i> Reset
                    </a>
                </div>
            </form>

            <!-- Summary Cards -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin-bottom: 25px;">
                <div style="background: #e7f3ff; padding: 15px; border-radius: 10px; border-left: 4px solid #667eea; text-align: center;">
                    <h4 style="margin: 0 0 6px 0; color: #667eea; font-size: 13px;">Total</h4>
                    <h2 style="margin: 0; color: #333;">{{ $cutiList->total() }}</h2>
                </div>
                <div style="background: #fff3cd; padding: 15px; border-radius: 10px; border-left: 4px solid #ffc107; text-align: center;">
                    <h4 style="margin: 0 0 6px 0; color: #856404; font-size: 13px;">Menunggu</h4>
                    <h2 style="margin: 0; color: #333;">{{ $cutiList->getCollection()->where('status_persetujuan', 'pending')->count() }}</h2>
                </div>
                <div style="background: #d4edda; padding: 15px; border-radius: 10px; border-left: 4px solid #28a745; text-align: center;">
                    <h4 style="margin: 0 0 6px 0; color: #155724; font-size: 13px;">Disetujui</h4>
                    <h2 style="margin: 0; color: #333;">{{ $cutiList->getCollection()->where('status_persetujuan', 'approved')->count() }}</h2>
                </div>
                <div style="background: #f8d7da; padding: 15px; border-radius: 10px; border-left: 4px solid #dc3545; text-align: center;">
                    <h4 style="margin: 0 0 6px 0; color: #721c24; font-size: 13px;">Ditolak</h4>
                    <h2 style="margin: 0; color: #333;">{{ $cutiList->getCollection()->where('status_persetujuan', 'rejected')->count() }}</h2>
                </div>
            </div>

            <!-- Table -->
            @if($cutiList->count() > 0)
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: #f8f9fa;">
                            <th style="padding: 14px; text-align: left; border-bottom: 2px solid #dee2e6;">No</th>
                            <th style="padding: 14px; text-align: left; border-bottom: 2px solid #dee2e6;">Nama Karyawan</th>
                            <th style="padding: 14px; text-align: left; border-bottom: 2px solid #dee2e6;">Jenis Cuti</th>
                            <th style="padding: 14px; text-align: left; border-bottom: 2px solid #dee2e6;">Tanggal</th>
                            <th style="padding: 14px; text-align: left; border-bottom: 2px solid #dee2e6;">Durasi</th>
                            <th style="padding: 14px; text-align: left; border-bottom: 2px solid #dee2e6;">Status</th>
                            <th style="padding: 14px; text-align: left; border-bottom: 2px solid #dee2e6;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($cutiList as $index => $cuti)
                        <tr style="border-bottom: 1px solid #dee2e6;">
                            <td style="padding: 14px;">{{ $cutiList->firstItem() + $index }}</td>
                            <td style="padding: 14px;">
                                <strong>{{ $cuti->karyawan->nama ?? '-' }}</strong><br>
                                <small style="color: #666;">{{ $cuti->karyawan->jabatan ?? '' }}</small>
                            </td>
                            <td style="padding: 14px;">
                                <span style="background: #e7f3ff; padding: 4px 10px; border-radius: 5px; font-size: 13px;">
                                    {{ $cuti->jenis_cuti }}
                                </span>
                            </td>
                            <td style="padding: 14px;">
                                {{ $cuti->tanggal_mulai->format('d/m/Y') }}<br>
                                <small style="color: #666;">s/d {{ $cuti->tanggal_selesai->format('d/m/Y') }}</small>
                            </td>
                            <td style="padding: 14px;"><strong>{{ $cuti->jumlah_hari }}</strong> hari</td>
                            <td style="padding: 14px;">
                                @if($cuti->status_persetujuan === 'pending')
                                    <span style="background: #fff3cd; color: #856404; padding: 5px 12px; border-radius: 15px; font-size: 12px; font-weight: 600; display: inline-flex; align-items: center; gap: 4px;"><i class="bi bi-hourglass-split" style="font-size: 0.9rem;"></i> Menunggu</span>
                                @elseif($cuti->status_persetujuan === 'approved')
                                    <span style="background: #d4edda; color: #155724; padding: 5px 12px; border-radius: 15px; font-size: 12px; font-weight: 600; display: inline-flex; align-items: center; gap: 4px;"><i class="bi bi-check-circle-fill" style="font-size: 0.9rem;"></i> Disetujui</span>
                                @else
                                    <span style="background: #f8d7da; color: #721c24; padding: 5px 12px; border-radius: 15px; font-size: 12px; font-weight: 600; display: inline-flex; align-items: center; gap: 4px;"><i class="bi bi-x-circle-fill" style="font-size: 0.9rem;"></i> Ditolak</span>
                                @endif
                            </td>
                            <td style="padding: 14px;">
                                <a href="{{ route('cuti.show', $cuti->id_cuti) }}"
                                   style="background: #667eea; color: white; padding: 7px 14px; border-radius: 5px; text-decoration: none; font-size: 13px; display: inline-flex; align-items: center; gap: 4px;">
                                    <i class="bi bi-eye-fill" style="font-size: 0.9rem;"></i> Detail
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div style="margin-top: 20px;">
                {{ $cutiList->appends(request()->query())->links() }}
            </div>
            @else
            <div style="text-align: center; padding: 60px 20px; color: #999;">
                <h3>Tidak Ada Data Cuti</h3>
                <p>Belum ada riwayat cuti yang sesuai dengan filter</p>
            </div>
            @endif

        </div>
    </div>
</div>
@endsection
