@php($topNavContent = trim($__env->yieldContent('topnav')))
<nav class="hris-topbar">
    @if ($topNavContent !== '')
        @yield('topnav')
    @else
        <div class="d-flex align-items-center justify-content-between gap-3">
            <div class="d-flex align-items-center gap-2">
                <button class="btn btn-outline-secondary d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#hrisSidebar" aria-controls="hrisSidebar">
                    <i class="bi bi-list"></i>
                </button>
                <div>
                    <div class="fw-semibold">Selamat datang</div>
                    <div class="small hris-muted">
                        {{ auth()->user()->username ?? 'Pengguna' }}
                    </div>
                </div>
            </div>
            <div class="d-flex align-items-center gap-3">
                <span class="small hris-muted"><i class="bi bi-calendar3"></i> {{ now()->format('d M Y') }}</span>
                
                <!-- User Profile Dropdown Menu -->
                <div class="dropdown">
                    <button class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-2 dropdown-toggle" type="button" id="userProfileDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-person-circle"></i>
                        <span class="d-none d-sm-inline">{{ auth()->user()->username ?? 'Pengguna' }}</span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm" aria-labelledby="userProfileDropdown">
                        <li>
                            <h6 class="dropdown-header">
                                {{ auth()->user()->username ?? 'Pengguna' }}
                            </h6>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center gap-2" href="{{ route('profile.index') }}">
                                <i class="bi bi-person-fill"></i>
                                <span>Profil</span>
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center gap-2 text-danger" href="/proseslogout">
                                <i class="bi bi-box-arrow-right"></i>
                                <span>Keluar</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    @endif
</nav>

<div class="offcanvas offcanvas-start" tabindex="-1" id="hrisSidebar" aria-labelledby="hrisSidebarLabel">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title" id="hrisSidebarLabel">HRIS Menu</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
        @include('layouts.partials.nav-items')
    </div>
</div>
