@extends('layouts.app')

@section('content')
<div class="container" style="max-width: 600px; margin: 20px auto; padding: 20px;">

    <!-- Profile Header -->
    <div class="card" style="border-radius: 15px; overflow: hidden; margin-bottom: 20px;">
        <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px 20px; text-align: center;">
            <div style="width: 80px; height: 80px; background: rgba(255,255,255,0.3); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px auto; font-size: 36px;">
                <i class="bi bi-person-fill" style="color: white; font-size: 2.5rem;"></i>
            </div>
            <h2 style="color: white; margin: 0 0 6px 0; font-size: 22px;">
                {{ $karyawan?->nama ?? $user->username }}
            </h2>
        </div>
    </div>

    <!-- Informasi Akun -->
    <div class="card" style="border-radius: 15px; margin-bottom: 20px;">
        <div style="padding: 20px 20px 10px 20px; border-bottom: 1px solid #eee;">
            <h5 style="margin: 0; color: #667eea; font-size: 14px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px;">
                Informasi Akun
            </h5>
        </div>
        <div style="padding: 10px 20px 20px 20px;">
            <div style="display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid #f0f0f0;">
                <span style="color: #666; font-size: 14px;">Username</span>
                <span style="font-weight: 600; color: #333;">{{ $user->username }}</span>
            </div>
            {{-- <div style="display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid #f0f0f0;">
                <span style="color: #666; font-size: 14px;">Role</span>
                <span style="font-weight: 600; color: #333; background: #e7f3ff; padding: 3px 12px; border-radius: 15px; font-size: 13px;">
                    {{ ucfirst($user->role ?? 'user') }}
                </span>
            </div> --}}
            <div style="padding: 12px 0 0 0;">
                <button type="button" onclick="showChangePasswordModal()"
                        style="width: 100%; padding: 10px; background: #667eea; color: white; border: none; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer;">
                    🔒 Ganti Password
                </button>
            </div>
        </div>
    </div>

    <!-- Informasi Karyawan -->
    @if($karyawan)
    <div class="card" style="border-radius: 15px; margin-bottom: 20px;">
        <div style="padding: 20px 20px 10px 20px; border-bottom: 1px solid #eee;">
            <h5 style="margin: 0; color: #667eea; font-size: 14px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px;">
                Informasi Karyawan
            </h5>
        </div>
        <div style="padding: 10px 20px 20px 20px;">
            <div style="display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid #f0f0f0;">
                <span style="color: #666; font-size: 14px;">Nama Lengkap</span>
                <span style="font-weight: 600; color: #333;">{{ $karyawan->nama }}</span>
            </div>
            <div style="display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid #f0f0f0;">
                <span style="color: #666; font-size: 14px;">Jabatan</span>
                <span style="font-weight: 600; color: #333;">{{ $karyawan->jabatan->nama_jabatan ?? '-' }}</span>
            </div>
            <div style="display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid #f0f0f0;">
                <span style="color: #666; font-size: 14px;">Divisi</span>
                <span style="font-weight: 600; color: #333;">{{ $karyawan->divisi->nama_divisi ?? '-' }}</span>
            </div>
            <div style="display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid #f0f0f0;">
                <span style="color: #666; font-size: 14px;">Tanggal Masuk</span>
                <span style="font-weight: 600; color: #333;">
                    {{ $karyawan->tanggal_masuk ? \Carbon\Carbon::parse($karyawan->tanggal_masuk)->format('d/m/Y') : '-' }}
                </span>
            </div>
            <div style="display: flex; justify-content: space-between; padding: 12px 0;">
                <span style="color: #666; font-size: 14px;">Status Wajah</span>
                <span style="font-weight: 600;">
                    @if($karyawan->face_embedding)
                        <span style="background: #d4edda; color: #155724; padding: 3px 12px; border-radius: 15px; font-size: 12px;">
                            ✓ Terdaftar
                        </span>
                    @else
                        <span style="background: #f8d7da; color: #721c24; padding: 3px 12px; border-radius: 15px; font-size: 12px;">
                            ✗ Belum Terdaftar
                        </span>
                    @endif
                </span>
            </div>
        </div>
    </div>
    @endif
    <!-- Modal Ganti Password -->
    <div id="changePasswordModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
        <div style="background: white; max-width: 400px; margin: 100px auto; padding: 30px; border-radius: 15px;">
            <h3 style="margin-bottom: 20px;">🔒 Ganti Password</h3>

            @if($errors->any())
                <div style="background: #f8d7da; color: #721c24; padding: 10px; border-radius: 8px; margin-bottom: 15px; font-size: 13px;">
                    @foreach($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            <form action="{{ route('profile.change-password') }}" method="POST">
                @csrf
                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 6px; font-weight: 600; font-size: 13px;">Password Saat Ini:</label>
                    <input type="password" name="current_password" required
                        style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 8px; box-sizing: border-box;">
                </div>
                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 6px; font-weight: 600; font-size: 13px;">Password Baru:</label>
                    <input type="password" name="password" required minlength="6"
                        style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 8px; box-sizing: border-box;">
                </div>
                <div style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 6px; font-weight: 600; font-size: 13px;">Konfirmasi Password Baru:</label>
                    <input type="password" name="password_confirmation" required minlength="6"
                        style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 8px; box-sizing: border-box;">
                </div>

                <div style="display: flex; gap: 10px;">
                    <button type="submit"
                            style="flex: 1; padding: 12px; background: #667eea; color: white; border: none; border-radius: 8px; cursor: pointer;">
                        Simpan
                    </button>
                    <button type="button" onclick="hideChangePasswordModal()"
                            style="flex: 1; padding: 12px; background: #6c757d; color: white; border: none; border-radius: 8px; cursor: pointer;">
                        Batal
                    </button>
                </div>
            </form>
        </div>
    </div>
    <!-- Logout Button -->
    <a href="/proseslogout"
       style="display: block; width: 100%; padding: 14px; background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; border: none; border-radius: 10px; font-size: 16px; font-weight: 600; text-align: center; text-decoration: none; box-sizing: border-box;">
        🚪 Keluar
    </a>

</div>
@endsection
@section('scripts')
<script>
function showChangePasswordModal() {
    document.getElementById('changePasswordModal').style.display = 'block';
}
function hideChangePasswordModal() {
    document.getElementById('changePasswordModal').style.display = 'none';
}
document.getElementById('changePasswordModal').addEventListener('click', function (e) {
    if (e.target === this) {
        hideChangePasswordModal();
    }
});
@if($errors->any())
showChangePasswordModal();
@endif
</script>
@endsection

