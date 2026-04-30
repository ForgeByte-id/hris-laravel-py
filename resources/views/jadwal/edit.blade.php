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
            <h2 style="margin: 0;">Edit Jadwal Kerja</h2>
        </div>

        <div class="card-body" style="padding: 30px;">
            
            <!-- Info Karyawan -->
            <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 25px;">
                <h5 style="margin-bottom: 10px;">👤 Karyawan</h5>
                <p style="margin: 5px 0;"><strong>Nama:</strong> {{ $jadwal->karyawan->nama }}</p>
                <p style="margin: 5px 0;"><strong>Jabatan:</strong> {{ $jadwal->karyawan->jabatan }}</p>
                <p style="margin: 5px 0;"><strong>Divisi:</strong> {{ $jadwal->karyawan->divisi }}</p>
            </div>

            <form action="{{ route('jadwal.update', $jadwal->id_jadwal) }}" method="POST">
                @csrf
                @method('PUT')

                <!-- Tanggal -->
                <div style="margin-bottom: 20px;">
                    <label style="font-weight: 600; margin-bottom: 8px; display: block;">
                        Tanggal <span style="color: red;">*</span>
                    </label>
                    <input type="date" name="tanggal" required 
                           value="{{ old('tanggal', $jadwal->tanggal->format('Y-m-d')) }}" 
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
                            <option value="{{ $option }}" 
                                {{ old('jam_kerja', $jadwal->jam_kerja) == $option ? 'selected' : '' }}>
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
                              style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; resize: vertical;">{{ old('keterangan', $jadwal->keterangan) }}</textarea>
                    @error('keterangan')
                        <small style="color: red;">{{ $message }}</small>
                    @enderror
                </div>

                <!-- Buttons -->
                <div style="display: flex; gap: 10px;">
                    <button type="submit" 
                            style="flex: 1; padding: 14px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer;">
                        Update Jadwal
                    </button>
                    <a href="{{ route('jadwal.index') }}" 
                       style="flex: 1; padding: 14px; background: #6c757d; color: white; border: none; border-radius: 8px; font-weight: 600; text-align: center; text-decoration: none; display: block;">
                        Batal
                    </a>
                </div>
            </form>

            <!-- Delete Button -->
            <form action="{{ route('jadwal.destroy', $jadwal->id_jadwal) }}" method="POST" style="margin-top: 20px;">
                @csrf
                @method('DELETE')
                <button type="submit" 
                        onclick="return confirm('Yakin ingin menghapus jadwal ini?')"
                        style="width: 100%; padding: 14px; background: #dc3545; color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer;">
                    Hapus Jadwal
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
