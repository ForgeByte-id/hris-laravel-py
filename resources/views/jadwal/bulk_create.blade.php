@extends('layouts.app')

@section('content')
<div class="container" style="max-width: 1000px; margin: 20px auto; padding: 20px;">
    <div class="card">
        <div class="card-header" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; padding: 20px;">
            <h2 style="margin: 0;">Input Jadwal Massal</h2>
        </div>

        <div class="card-body" style="padding: 30px;">
            <form action="{{ route('jadwal.bulk-store') }}" method="POST">
                @csrf

                <!-- Pilih Tanggal -->
                <div style="background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; border-radius: 5px; margin-bottom: 25px;">
                    <h5 style="margin-bottom: 10px;">Pilih Tanggal</h5>
                    <input type="date" name="tanggal" required 
                           style="padding: 12px; border: 1px solid #ddd; border-radius: 8px; width: 250px;">
                </div>

                <!-- Tabel Input -->
                <h5 style="margin-bottom: 15px;">Atur Jadwal untuk Semua Karyawan:</h5>
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="background: #f8f9fa;">
                                <th style="padding: 12px; border: 1px solid #dee2e6; text-align: left;">No</th>
                                <th style="padding: 12px; border: 1px solid #dee2e6; text-align: left;">Nama Karyawan</th>
                                <th style="padding: 12px; border: 1px solid #dee2e6; text-align: left;">Jabatan</th>
                                <th style="padding: 12px; border: 1px solid #dee2e6; text-align: left; width: 250px;">Jam Kerja</th>
                                <th style="padding: 12px; border: 1px solid #dee2e6; text-align: left; width: 200px;">Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($karyawanList as $index => $k)
                            <tr>
                                <td style="padding: 12px; border: 1px solid #dee2e6;">{{ $index + 1 }}</td>
                                <td style="padding: 12px; border: 1px solid #dee2e6;">
                                    <strong>{{ $k->nama }}</strong>
                                    <input type="hidden" name="jadwal[{{ $index }}][id_karyawan]" value="{{ $k->id_karyawan }}">
                                </td>
                                <td style="padding: 12px; border: 1px solid #dee2e6;">{{ $k->jabatan }}</td>
                                <td style="padding: 12px; border: 1px solid #dee2e6;">
                                    <select name="jadwal[{{ $index }}][jam_kerja]" required 
                                            style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 5px;">
                                        <option value="">-- Pilih --</option>
                                        @foreach($jamKerjaOptions as $option)
                                            <option value="{{ $option }}">{{ $option }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td style="padding: 12px; border: 1px solid #dee2e6;">
                                    <input type="text" name="jadwal[{{ $index }}][keterangan]" 
                                           placeholder="Optional" 
                                           style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 5px;">
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Quick Set Buttons -->
                <div style="margin-top: 20px; padding: 20px; background: #f8f9fa; border-radius: 8px;">
                    <h6 style="margin-bottom: 10px;">Quick Set (Set Semua Sekaligus):</h6>
                    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                        <button type="button" onclick="setAllShift('Pagi (08:00-17:00)')" 
                                style="padding: 10px 20px; background: #4CAF50; color: white; border: none; border-radius: 5px; cursor: pointer;">
                            Set Semua Pagi
                        </button>
                        <button type="button" onclick="setAllShift('Middle (11:00-20:00)')" 
                                style="padding: 10px 20px; background: #FF9800; color: white; border: none; border-radius: 5px; cursor: pointer;">
                            Set Semua Siang
                        </button>
                        <button type="button" onclick="setAllShift('Siang (13:00-22:00)')" 
                                style="padding: 10px 20px; background: #2196F3; color: white; border: none; border-radius: 5px; cursor: pointer;">
                            Set Semua Malam
                        </button>
                        <button type="button" onclick="setAllShift('Libur')" 
                                style="padding: 10px 20px; background: #f44336; color: white; border: none; border-radius: 5px; cursor: pointer;">
                            Set Semua Libur
                        </button>
                    </div>
                </div>

                <!-- Submit Buttons -->
                <div style="display: flex; gap: 10px; margin-top: 30px;">
                    <button type="submit" 
                            style="flex: 1; padding: 14px; background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer;">
                        Simpan Semua Jadwal
                    </button>
                    <a href="{{ route('jadwal.index') }}" 
                       style="flex: 1; padding: 14px; background: #6c757d; color: white; border: none; border-radius: 8px; font-weight: 600; text-align: center; text-decoration: none; display: block;">
                        Batal
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function setAllShift(shift) {
    const selects = document.querySelectorAll('select[name*="[jam_kerja]"]');
    selects.forEach(select => {
        select.value = shift;
    });
}
</script>
@endsection
