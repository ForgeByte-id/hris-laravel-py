@extends('layouts.app')

@section('content')
<div class="container" style="max-width: 800px; margin: 20px auto; padding: 20px;">
    <div class="card">
        <div class="card-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px;">
            <h2 style="margin: 0;">Form Pengajuan Cuti</h2>
        </div>
        
        <div class="card-body" style="padding: 30px;">
            <form action="{{ route('cuti.store') }}" method="POST">
                @csrf

                <!-- Informasi Karyawan -->
                <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 25px;">
                    <h5 style="margin-bottom: 10px; color: #333;">Informasi Karyawan</h5>
                    <p style="margin: 5px 0;"><strong>Nama:</strong> {{ $karyawan->nama }}</p>
                    <p style="margin: 5px 0;"><strong>Jabatan:</strong> {{ $karyawan->jabatan }}</p>
                    <p style="margin: 5px 0;"><strong>Divisi:</strong> {{ $karyawan->divisi }}</p>
                </div>

                <!-- Jenis Cuti -->
                <div class="form-group" style="margin-bottom: 20px;">
                    <label style="font-weight: 600; margin-bottom: 8px; display: block;">
                        Jenis Cuti <span style="color: red;">*</span>
                    </label>
                    <select name="jenis_cuti" class="form-control" required 
                            style="border: 1px solid #ddd; border-radius: 8px; width: 100%;">
                        <option value="">-- Pilih Jenis Cuti --</option>
                        @foreach($jenisCuti as $jenis)
                            <option value="{{ $jenis }}" {{ old('jenis_cuti') == $jenis ? 'selected' : '' }}>
                                {{ $jenis }}
                            </option>
                        @endforeach
                    </select>
                    @error('jenis_cuti')
                        <small style="color: red;">{{ $message }}</small>
                    @enderror
                </div>

                <!-- Tanggal Mulai -->
                <div class="form-group" style="margin-bottom: 20px;">
                    <label style="font-weight: 600; margin-bottom: 8px; display: block;">
                        Tanggal Mulai <span style="color: red;">*</span>
                    </label>
                    <input type="date" name="tanggal_mulai" class="form-control" required 
                           value="{{ old('tanggal_mulai') }}"
                           min="{{ date('Y-m-d') }}"
                           style="padding: 12px; border: 1px solid #ddd; border-radius: 8px; width: 100%;">
                    @error('tanggal_mulai')
                        <small style="color: red;">{{ $message }}</small>
                    @enderror
                </div>

                <!-- Tanggal Selesai -->
                <div class="form-group" style="margin-bottom: 20px;">
                    <label style="font-weight: 600; margin-bottom: 8px; display: block;">
                        Tanggal Selesai <span style="color: red;">*</span>
                    </label>
                    <input type="date" name="tanggal_selesai" class="form-control" required 
                           value="{{ old('tanggal_selesai') }}"
                           min="{{ date('Y-m-d') }}"
                           style="padding: 12px; border: 1px solid #ddd; border-radius: 8px; width: 100%;">
                    @error('tanggal_selesai')
                        <small style="color: red;">{{ $message }}</small>
                    @enderror
                </div>

                <!-- Keterangan -->
                <div class="form-group" style="margin-bottom: 20px;">
                    <label style="font-weight: 600; margin-bottom: 8px; display: block;">
                        Keterangan/Alasan
                    </label>
                    <textarea name="keterangan" class="form-control" rows="4" 
                              placeholder="Tuliskan alasan pengajuan cuti..."
                              style="padding: 12px; border: 1px solid #ddd; border-radius: 8px; width: 100%; resize: vertical;">{{ old('keterangan') }}</textarea>
                    @error('keterangan')
                        <small style="color: red;">{{ $message }}</small>
                    @enderror
                </div>

                <!-- Info Box -->
                <div style="background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; border-radius: 5px; margin-bottom: 25px;">
                    <p style="margin: 0; color: #856404;">
                        ⚠️ <strong>Catatan:</strong> Pengajuan cuti akan diproses oleh atasan Anda. 
                        Pastikan semua data sudah benar sebelum submit.
                    </p>
                </div>

                <!-- Buttons -->
                <div style="display: flex; gap: 10px;">
                    <button type="submit" 
                            style="flex: 1; padding: 14px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; font-size: 16px;">
                        Ajukan Cuti
                    </button>
                    <a href="{{ route('cuti.index') }}" 
                       style="flex: 1; padding: 14px; background: #6c757d; color: white; border: none; border-radius: 8px; font-weight: 600; text-align: center; text-decoration: none; display: block; font-size: 16px;">
                        Batal
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Auto-update tanggal selesai minimal sama dengan tanggal mulai
document.querySelector('input[name="tanggal_mulai"]').addEventListener('change', function() {
    document.querySelector('input[name="tanggal_selesai"]').min = this.value;
});
</script>
@endsection
