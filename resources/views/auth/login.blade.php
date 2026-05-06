@extends('layouts.app')

@section('title', 'HRIS - Login')
@section('body_class', 'bg-light') {{-- Ubah ke light agar card lebih pop-out --}}
@section('app_capsule_class', 'pt-0')
@section('show_bottom_nav', '0')
@section('include_default_scripts', '0')
@section('show_chrome', '0')
@section('show_loader', '0')

@section('content')
<div class="container-fluid px-3">
    <div class="row justify-content-center align-items-center min-vh-100">
        <div class="col-12 col-md-5 col-lg-4">

            <!-- Logo Section -->
            <div class="text-center mb-4">
                <img src="{{ asset('assets/img/login/0903b5a53bd9344fc0737fcd41f56ff5.png') }}"
                     alt="Logo" class="img-fluid" style="max-height: 120px;">
            </div>

            <!-- Login Card -->
            <div class="card shadow-lg border-0" style="background: #f1f3f8;">
                <div class="card-body p-4">
                    <div class="text-center mb-4">
                        <h2 class="fw-bold text-primary">HRIS</h2>
                        <p class="text-muted">Silakan masuk ke akun Anda</p>
                    </div>

                    <form action="{{ route('proseslogin') }}" method="POST" class="mt-3">
                        @csrf

                        <!-- Username -->
                        <div class="input-group mb-3 shadow rounded-pill overflow-hidden">
                            <span class="input-group-text bg-transparent border-0 px-3 d-flex align-items-center justify-content-center" id="username-addon">
                                <i class="bi bi-person fs-5"></i>
                            </span>
                            <input type="text" name="username" class="form-control py-2 border-0"
                                   id="username" placeholder="Masukkan username"
                                   aria-label="Username" aria-describedby="username-addon" required>
                        </div>

                        <!-- Password -->
                        <div class="input-group mb-3 shadow rounded-pill overflow-hidden">
                            <span class="input-group-text bg-transparent border-0 px-3 d-flex align-items-center justify-content-center" id="password-addon">
                                <i class="bi bi-lock fs-5"></i>
                            </span>
                            <input type="password" name="password" class="form-control py-2 border-0"
                                   id="password" placeholder="Masukkan password"
                                   aria-label="Password" aria-describedby="password-addon" required>
                            <button type="button" class="btn btn-link text-muted px-3" id="toggle-password" aria-label="Toggle password visibility">
                                <i class="bi bi-eye fs-5"></i>
                            </button>
                        </div>

                        <div class="d-flex justify-content-end mb-4">
                            <a href="page-forgot-password.html" class="small text-decoration-none text-muted">Lupa Password?</a>
                        </div>

                        <!-- Submit Button -->
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg shadow-sm">
                                Masuk Sekarang
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Footer Note -->
            <div class="text-center mt-4">
                <p class="small text-muted">&copy; {{ date('Y') }} HRIS System. All rights reserved.</p>
            </div>

        </div>
    </div>
</div>
@endsection

@section('scripts')
    <script>
        const passwordInput = document.getElementById('password');
        const togglePassword = document.getElementById('toggle-password');
        const eyeIcon = togglePassword?.querySelector('i');

        if (togglePassword && passwordInput && eyeIcon) {
            togglePassword.addEventListener('click', () => {
                const isHidden = passwordInput.type === 'password';
                passwordInput.type = isHidden ? 'text' : 'password';
                eyeIcon.classList.toggle('bi-eye');
                eyeIcon.classList.toggle('bi-eye-slash');
            });
        }
    </script>
    @if(session('error'))
        <script>
            Swal.fire({
                icon: 'error',
                title: 'Login Gagal',
                text: '{{ session('error') }}',
                confirmButtonColor: '#3085d6',
                confirmButtonText: 'Coba Lagi'
            });
        </script>
    @endif
@endsection
