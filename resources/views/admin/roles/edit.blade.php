@extends('layouts.app')

@section('title', 'Edit Role - HRIS')

@section('content')
<div class="hris-container">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="hris-card">
                <div class="hris-card-header">
                    <h2 class="mb-0">
                        <i class="bi bi-shield-lock-fill me-2"></i>Edit Role: <span class="text-primary">{{ $role->name }}</span>
                    </h2>
                </div>
                <div class="hris-card-body">
                    <form action="{{ route('admin.roles.update', $role) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="mb-4">
                            <label class="form-label fw-semibold mb-3">
                                <i class="bi bi-grid me-2"></i>Akses Menu
                            </label>
                            <div class="row g-2">
                                @foreach($menuItems as $menu)
                                <div class="col-md-4">
                                    <div class="form-check p-3 border rounded {{ in_array($menu->id, $assignedMenus) ? 'border-primary bg-light' : '' }}">
                                        <input class="form-check-input" type="checkbox"
                                               name="menus[]" value="{{ $menu->id }}"
                                               id="menu_{{ $menu->id }}"
                                               {{ in_array($menu->id, $assignedMenus) ? 'checked' : '' }}>
                                        <label class="form-check-label fw-semibold" for="menu_{{ $menu->id }}">
                                            {{ $menu->name }}
                                        </label>
                                        @if($menu->route)
                                            <div class="text-muted" style="font-size: 11px;">{{ $menu->route }}</div>
                                        @endif
                                        @if($menu->is_admin_only)
                                            <span class="badge bg-danger" style="font-size: 10px;">Admin Only</span>
                                        @endif
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="d-flex gap-2 mt-4">
                            <button type="submit" class="btn btn-primary flex-fill">
                                <i class="bi bi-check-circle me-2"></i>Simpan Perubahan
                            </button>
                            <a href="{{ route('admin.roles.index') }}" class="btn btn-secondary flex-fill">
                                <i class="bi bi-x-circle me-2"></i>Batal
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
