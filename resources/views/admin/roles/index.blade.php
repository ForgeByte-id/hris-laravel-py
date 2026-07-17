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
                                    @if($role->guard_name === 'web')
                                        <span class="badge bg-primary-subtle text-primary"
                                            data-bs-toggle="tooltip"
                                            title="Digunakan untuk akses melalui browser (login user biasa)">
                                            <i class="bi bi-globe me-1"></i> Web
                                        </span>
                                    @elseif($role->guard_name === 'api')
                                        <span class="badge bg-warning-subtle text-warning"
                                            data-bs-toggle="tooltip"
                                            title="Digunakan untuk akses melalui API / mobile / service">
                                            <i class="bi bi-cpu me-1"></i> API
                                        </span>
                                    @else
                                        <span class="badge bg-secondary">
                                            {{ $role->guard_name }}
                                        </span>
                                    @endif
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
        <div class="card-text">
            <div class="mb-2 small text-muted p-2">
                <i class="bi bi-globe text-primary"></i> Web = untuk user login via browser &nbsp;
                <i class="bi bi-cpu text-warning"></i> API = untuk sistem / mobile / backend
            </div>
        </div>
    </div>

    {{-- Menu Items Management --}}
    <div class="hris-card mt-4">
        <div class="hris-card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="bi bi-grid me-2"></i>Daftar Menu Tersedia</h5>
            <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addMenuModal">
                <i class="bi bi-plus-circle me-1"></i>Tambah Menu
            </button>
        </div>
        <div class="hris-card-body">
            @if($menuItems->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Menu</th>
                                <th>Route</th>
                                <th>Icon</th>
                                <th>Order</th>
                                <th>Status</th>
                                <th style="width: 120px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($menuItems as $menu)
                            <tr>
                                <td>{{ $menu->name }}</td>
                                <td><code>{{ $menu->route }}</code></td>
                                <td><i class="bi {{ $menu->icon }}"></i></td>
                                <td>{{ $menu->order }}</td>
                                <td>
                                    @if($menu->is_admin_only)
                                        <span class="badge bg-danger">Admin Only</span>
                                    @else
                                        <span class="badge bg-success">All Users</span>
                                    @endif
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-warning" data-bs-toggle="modal"
                                            data-bs-target="#editMenuModal" data-menu-id="{{ $menu->id }}"
                                            data-menu-name="{{ $menu->name }}" data-menu-route="{{ $menu->route }}"
                                            data-menu-icon="{{ $menu->icon }}" data-menu-order="{{ $menu->order }}"
                                            data-menu-admin="{{ $menu->is_admin_only ? '1' : '0' }}">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <form action="{{ route('admin.menu-items.destroy', $menu) }}" method="POST" style="display:inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger"
                                                onclick="return confirm('Yakin ingin menghapus menu ini?')">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-4">
                    <i class="bi bi-grid-3x3-gap text-muted" style="font-size: 2rem;"></i>
                    <div class="text-muted mt-2">Belum ada menu tersedia</div>
                </div>
            @endif
        </div>
    </div>

    {{-- Add Menu Modal --}}
    <div class="modal fade" id="addMenuModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Menu Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="{{ route('admin.menu-items.store') }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="name" class="form-label">Nama Menu</label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror"
                                   id="name" name="name" placeholder="e.g., Karyawan" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label for="route" class="form-label">Route</label>
                            <input type="text" class="form-control @error('route') is-invalid @enderror"
                                   id="route" name="route" placeholder="e.g., /karyawan" required>
                            @error('route')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label for="icon" class="form-label">Icon (Bootstrap Icon Class)</label>

                            <input type="text"
                                class="form-control @error('icon') is-invalid @enderror"
                                id="icon"
                                name="icon"
                                placeholder="e.g., bi-people-fill"
                                required>

                            <!-- NOTE -->
                            <div class="form-text">
                                Gunakan class dari Bootstrap Icons, contoh: <code>bi bi-people-fill</code>.
                                Lihat daftar icon di
                                <a href="https://icons.getbootstrap.com/" target="_blank">
                                    Bootstrap Icons
                                </a>.
                            </div>

                            @error('icon')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="order" class="form-label">Urutan (Order)</label>
                            <input type="number" class="form-control @error('order') is-invalid @enderror"
                                   id="order" name="order" placeholder="e.g., 1">
                            @error('order')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="is_admin_only" name="is_admin_only" value="1">
                            <label class="form-check-label" for="is_admin_only">
                                Hanya untuk Admin
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Tambah Menu</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Edit Menu Modal --}}
    <div class="modal fade" id="editMenuModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Menu</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="editMenuForm" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="editName" class="form-label">Nama Menu</label>
                            <input type="text" class="form-control" id="editName" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="editRoute" class="form-label">Route</label>
                            <input type="text" class="form-control" id="editRoute" name="route" required>
                        </div>
                        <div class="mb-3">
                            <label for="editIcon" class="form-label">Icon (Bootstrap Icon Class)</label>
                            <input type="text" class="form-control" id="editIcon" name="icon" required>
                        </div>
                        <div class="mb-3">
                            <label for="editOrder" class="form-label">Urutan (Order)</label>
                            <input type="number" class="form-control" id="editOrder" name="order">
                        </div>
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="editIsAdminOnly" name="is_admin_only" value="1">
                            <label class="form-check-label" for="editIsAdminOnly">
                                Hanya untuk Admin
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('editMenuModal').addEventListener('show.bs.modal', function(e) {
            const button = e.relatedTarget;
            const menuId = button.getAttribute('data-menu-id');
            const menuName = button.getAttribute('data-menu-name');
            const menuRoute = button.getAttribute('data-menu-route');
            const menuIcon = button.getAttribute('data-menu-icon');
            const menuOrder = button.getAttribute('data-menu-order');
            const menuAdmin = button.getAttribute('data-menu-admin');

            document.getElementById('editName').value = menuName;
            document.getElementById('editRoute').value = menuRoute;
            document.getElementById('editIcon').value = menuIcon;
            document.getElementById('editOrder').value = menuOrder;
            document.getElementById('editIsAdminOnly').checked = menuAdmin === '1';

            document.getElementById('editMenuForm').action = `/admin/menu-items/${menuId}`;
        });
    </script>
</div>
@endsection

@section('scripts')
<script>
    const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]')
    tooltipTriggerList.forEach(el => new bootstrap.Tooltip(el))
</script>
@endsection
