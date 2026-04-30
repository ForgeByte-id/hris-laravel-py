@extends('layouts.app')

@section('content')
<div class="container" style="max-width: 800px; margin: 20px auto; padding: 20px;">
    
    @if(session('error'))
    <div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
        ❌ {{ session('error') }}
    </div>
    @endif

    <div class="card">
        <div class="card-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px;">
            <h2 style="margin: 0;">Tambah Jadwal Kerja</h2>
        </div>

        <div class="card-body" style="padding: 30px;">
            <form action="{{ route('jadwal.store') }}" method="POST">
                @csrf

                <!-- Pilih Karyawan -->
                <div style="margin-bottom: 20px;">
                    <label style="font-weight: 600; margin-bottom: 8px; display: block;">
                        Pilih Karyawan <span style="color: red;">*</span>
                    </label>
                    <select name="id_karyawan" required 
                            style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px;">
                        <option value="">-- Pilih Karyawan --</option>
                        @foreach($karyawanList as $k)
                            <option value="{{ $k->id_karyawan }}" {{ old('id_karyawan') == $k->id_karyawan ? 'selected' : '' }}>
                                {{ $k->nama }} - {{ $k->jabatan }}
                            </option>
                        @endforeach
                    </select>
                    @error('id_karyawan')
                        <small style="color: red;">{{ $message }}</small>
                    @enderror
                </div>

                <!-- Tanggal -->
                <div style="margin-bottom: 20px;">
                    <label style="font-weight: 600; margin-bottom: 8px; display: block;">
                        Tanggal <span style="color: red;">*</span>
                    </label>
                    <input type="date" name="tanggal" required value="{{ old('tanggal') }}" 
                           style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px;">
                    @error('tanggal')
                        <small style="color: red;">{{ $message }}</small>
                    @enderror
                </div>

                <!-- Jam Kerja -->
                <div style="margin-bottom: 20px;">
                    <label style="font-weight: 600; margin-bottom: 8px; display: block;">
                        Jam Kerja / Shift <span style="color: red;">*</span>
                    </label>
                    <select name="jam_kerja" required 
                            style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px;">
                        <option value="">-- Pilih Jam Kerja --</option>
                        @foreach($jamKerjaOptions as $option)
                            <option value="{{ $option }}" {{ old('jam_kerja') == $option ? 'selected' : '' }}>
                                {{ $option }}
                            </option>
                        @endforeach
                    </select>
                    @error('jam_kerja')
                        <small style="color: red;">{{ $message }}</small>
                    @enderror
                </div>

                <!-- Keterangan -->
                <div style="margin-bottom: 20px;">
                    <label style="font-weight: 600; margin-bottom: 8px; display: block;">
                        Keterangan (Optional)
                    </label>
                    <textarea name="keterangan" rows="3" placeholder="Catatan tambahan..." 
                              style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; resize: vertical;">{{ old('keterangan') }}</textarea>
                    @error('keterangan')
                        <small style="color: red;">{{ $message }}</small>
                    @enderror
                </div>

                <!-- Info Box -->
                <div style="background: #e7f3ff; border-left: 4px solid #667eea; padding: 15px; border-radius: 5px; margin-bottom: 25px;">
                    <p style="margin: 0; color: #333;">
                        💡 <strong>Tips:</strong> Pastikan tidak ada duplikasi jadwal untuk karyawan yang sama di tanggal yang sama.
                    </p>
                </div>

                <!-- Buttons -->
                <div style="display: flex; gap: 10px;">
                    <button type="submit" 
                            style="flex: 1; padding: 14px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer;">
                        Simpan Jadwal
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
@endsection
