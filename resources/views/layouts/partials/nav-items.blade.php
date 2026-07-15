@php
    $userRole    = auth()->user()?->roles->first();
    $menuItems   = \App\Models\MenuItem::orderBy('order')->get();
    $menuVisibility = app(\App\Services\MenuVisibilityService::class);
    $menuItems = $menuItems->reject(fn($m) => $menuVisibility->isHidden($m));
    $currentPath = request()->path();

    // Split into general items (not admin-only) and admin items for the divider
    $generalItems = $menuItems->filter(fn($m) => !$m->is_admin_only && $m->isAccessibleByRole($userRole));
    $adminItems   = $menuItems->filter(fn($m) => $m->is_admin_only  && $m->isAccessibleByRole($userRole));
@endphp

<ul class="nav flex-column gap-2">

    {{-- General / shared menu items (Dashboard, Cuti, etc.) --}}
    @foreach($generalItems as $menu)
        @php
            $routePath = ltrim($menu->route, '/');
            $isActive = $routePath === 'cuti'
                ? request()->is('cuti') || request()->is('cuti/create') || request()->is('cuti/*/edit') || preg_match('#^cuti/\d+$#', $currentPath)
                : request()->is($routePath . '*');
        @endphp
        <li class="nav-item">
            <a class="nav-link d-flex align-items-center gap-3 px-3 py-2 rounded {{ $isActive ? 'active' : '' }}"
               href="{{ $menu->route }}">
                <i class="bi {{ $menu->icon }}"></i>
                <span class="flex-grow-1">{{ $menu->name }}</span>
                @if($isActive)
                    <i class="bi bi-check-circle-fill small"></i>
                @endif
            </a>
        </li>
    @endforeach

    {{-- Admin-only section (only rendered when the user can see at least one item) --}}
    @if($adminItems->isNotEmpty())
        <li class="nav-item mt-3 mb-2">
            <div class="border-top pt-2">
                <span class="small text-muted px-3">ADMINISTRASI</span>
            </div>
        </li>

        @foreach($adminItems as $menu)
            @php $isActive = request()->is(ltrim($menu->route, '/') . '*'); @endphp
            <li class="nav-item">
                <a class="nav-link d-flex align-items-center gap-3 px-3 py-2 rounded {{ $isActive ? 'active' : '' }}"
                   href="{{ $menu->route }}">
                    <i class="bi {{ $menu->icon }}"></i>
                    <span class="flex-grow-1">{{ $menu->name }}</span>
                    @if($isActive)
                        <i class="bi bi-check-circle-fill small"></i>
                    @endif
                </a>
            </li>
        @endforeach
    @endif

</ul>
