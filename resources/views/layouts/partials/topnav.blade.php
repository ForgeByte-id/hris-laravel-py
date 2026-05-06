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
            <div class="d-flex align-items-center gap-2">
                <span class="small hris-muted"><i class="bi bi-calendar3"></i> {{ now()->format('d M Y') }}</span>
                <a href="/proseslogout" class="btn btn-sm btn-outline-danger d-flex align-items-center gap-2">
                    <i class="bi bi-box-arrow-right"></i>
                    <span>Logout</span>
                </a>
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
