<ul class="nav flex-column gap-2">

    {{-- Dashboard --}}
    @php $active = request()->is('dashboard'); @endphp
    <li class="nav-item">
        <a class="nav-link d-flex align-items-center gap-3 px-3 py-2 rounded {{ $active ? 'active' : '' }}"
           href="/dashboard">
            <i class="bi bi-house-door-fill"></i>
            <span class="flex-grow-1">Dashboard</span>
            @if($active)
                <i class="bi bi-check-circle-fill small"></i>
            @endif
        </a>
    </li>

    {{-- Default Admin Menu Items (Static) --}}
    @php $active = request()->is('cuti*'); @endphp
    <li class="nav-item">
        <a class="nav-link d-flex align-items-center gap-3 px-3 py-2 rounded {{ $active ? 'active' : '' }}"
           href="/cuti">
            <i class="bi bi-calendar-event-fill"></i>
            <span class="flex-grow-1">Cuti</span>
            @if($active)
                <i class="bi bi-check-circle-fill small"></i>
            @endif
        </a>
    </li>

    @if(auth()->user()?->hasRole('admin'))

        {{-- Divider --}}
        <li class="nav-item mt-3 mb-2">
            <div class="border-top pt-2">
                <span class="small text-muted px-3">ADMINISTRASI</span>
            </div>
        </li>

        {{-- Dynamic Menu Items from Database --}}
        @php
            $menuItems = \App\Models\MenuItem::orderBy('order')->get();
            $userRole = auth()->user()->roles->first();
        @endphp

        @forelse($menuItems as $menu)
            @php
                $isAccessible = $menu->isAccessibleByRole($userRole);
                $isActive = request()->is(ltrim($menu->route, '/') . '*');
            @endphp

            @if($isAccessible)
            <li class="nav-item">
                <a class="nav-link {{ $isActive ? 'active' : '' }}" href="{{ $menu->route }}">
                    <i class="bi {{ $menu->icon }}"></i>
                    <span class="flex-grow-1">{{ $menu->name }}</span>
                    @if($isActive)
                        <i class="bi bi-check-circle-fill small"></i>
                    @endif
                </a>
            </li>
            @endif
        @empty
            {{-- Default static items when no menu items exist --}}
            {{-- Role --}}
            @php $active = request()->is('admin/roles*'); @endphp
            <li class="nav-item">
                <a class="nav-link {{ $active ? 'active' : '' }}" href="/admin/roles">
                    <i class="bi bi-shield-lock-fill"></i>
                    <span class="flex-grow-1">Role Management</span>
                </a>
            </li>

            {{-- Permission --}}
            @php $active = request()->is('admin/permissions*'); @endphp
            <li class="nav-item">
                <a class="nav-link {{ $active ? 'active' : '' }}" href="/admin/permissions">
                    <i class="bi bi-lock-fill"></i>
                    <span class="flex-grow-1">Hak Akses</span>
                </a>
            </li>

            {{-- Jadwal --}}
            @php $active = request()->is('jadwal*'); @endphp
            <li class="nav-item">
                <a class="nav-link {{ $active ? 'active' : '' }}" href="/jadwal">
                    <i class="bi bi-clock-fill"></i>
                    <span class="flex-grow-1">Jadwal Kerja</span>
                </a>
            </li>

            {{-- Divisi --}}
            @php $active = request()->is('divisi*'); @endphp
            <li class="nav-item">
                <a class="nav-link {{ $active ? 'active' : '' }}" href="/divisi">
                    <i class="bi bi-diagram-3-fill"></i>
                    <span class="flex-grow-1">Divisi</span>
                </a>
            </li>

            {{-- Jabatan --}}
            @php $active = request()->is('jabatan*'); @endphp
            <li class="nav-item">
                <a class="nav-link {{ $active ? 'active' : '' }}" href="/jabatan">
                    <i class="bi bi-briefcase-fill"></i>
                    <span class="flex-grow-1">Jabatan</span>
                </a>
            </li>

            {{-- Karyawan --}}
            @php $active = request()->is('karyawan*'); @endphp
            <li class="nav-item">
                <a class="nav-link {{ $active ? 'active' : '' }}" href="/karyawan">
                    <i class="bi bi-people-fill"></i>
                    <span class="flex-grow-1">Karyawan</span>
                </a>
            </li>

            {{-- Laporan --}}
            @php $active = request()->is('laporan*'); @endphp
            <li class="nav-item">
                <a class="nav-link {{ $active ? 'active' : '' }}" href="/laporan">
                    <i class="bi bi-bar-chart-fill"></i>
                    <span class="flex-grow-1">Laporan</span>
                </a>
            </li>
        @endforelse

    @endif

</ul>
