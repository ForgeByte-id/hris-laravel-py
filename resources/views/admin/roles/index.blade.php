@extends('layouts.app')

@section('title', 'Role Management - HRIS')

@section('content')
<div class="hris-container">
    <div class="row g-3 mb-4">
        <div class="col-12">
            <div>
                <h2 class="mb-1">
                    <i class="bi bi-shield-lock-fill me-2" style="font-size: 1.5rem;"></i>Role Management
                </h2>
                <p class="text-muted small mb-0">Kelola peran dan akses menu untuk setiap role</p>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="hris-card">
        <div class="hris-card-header">
            <h5 class="mb-0"><i class="bi bi-list-ul me-2"></i>Daftar Role</h5>
        </div>
        <div class="hris-card-body p-0">
            @if($roles->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 50px;">No</th>
                                <th>Nama Role</th>
                                <th>Guard</th>
                                <th>Menu yang Diakses</th>
                                <th style="width: 100px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($roles as $role)
                            <tr>
                                <td class="align-middle">{{ $loop->iteration }}</td>
                                <td class="align-middle">
                                    <span class="badge bg-primary fs-6">{{ $role->name }}</span>
                                </td>
                                <td class="align-middle">
                                    <span class="badge bg-secondary">{{ $role->guard_name }}</span>
                                </td>
                                <td class="align-middle">
                                    @php $assignedMenus = $role->menus()->pluck('name') @endphp
                                    @if($assignedMenus->count() > 0)
                                        @foreach($assignedMenus as $menu)
                                            <span class="badge bg-light text-dark border me-1">{{ $menu }}</span>
                                        @endforeach
                                    @else
                                        <span class="text-muted small">Semua menu (default)</span>
                                    @endif
                                </td>
                                <td class="align-middle">
                                    <a href="{{ route('admin.roles.edit', $role) }}"
                                       class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-5">
                    <i class="bi bi-shield-x" style="font-size: 3rem; color: var(--hris-border);"></i>
                    <div class="text-muted mt-3">Belum ada data role</div>
                </div>
            @endif
        </div>
    </div>

    {{-- Menu Items Info --}}
    <div class="hris-card mt-4">
        <div class="hris-card-header">
            <h5 class="mb-0"><i class="bi bi-grid me-2"></i>Daftar Menu Tersedia</h5>
        </div>
        <div class="hris-card-body">
            <div class="row g-2">
                @foreach($menuItems as $menu)
                <div class="col-md-3">
                    <div class="p-2 border rounded d-flex align-items-center gap-2">
                        <i class="bi bi-list-check text-primary"></i>
                        <span class="small">{{ $menu->name }}</span>
                        @if($menu->is_admin_only)
                            <span class="badge bg-danger ms-auto" style="font-size: 10px;">Admin</span>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection
