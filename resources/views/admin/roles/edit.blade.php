@extends('layouts.app')

@section('title', 'Edit Role - HRIS')

@section('content')
<div class="hris-container">
    <div class="row justify-content-center">
        <div class="col-lg-9">

            <div class="hris-card">
                {{-- Header --}}
                <div class="hris-card-header d-flex align-items-center justify-content-between">
                    <h5 class="mb-0">
                        <i class="bi bi-shield-lock-fill me-2"></i>
                        Edit Role
                    </h5>
                    <span class="badge bg-primary-subtle text-primary px-3 py-2">
                        {{ $role->name }}
                    </span>
                </div>

                {{-- Body --}}
                <div class="hris-card-body">

                    <form action="{{ route('admin.roles.update', $role) }}" method="POST">
                        @csrf
                        @method('PUT')

                        {{-- Section Title --}}
                        <div class="mb-3">
                            <label class="form-label fw-semibold d-flex align-items-center gap-2">
                                <i class="bi bi-grid"></i>
                                Akses Menu
                            </label>
                            <div class="small text-muted">
                                Pilih menu yang dapat diakses oleh role ini.
                            </div>
                        </div>

                        {{-- Menu Grid --}}
                        <div class="row g-3">
                            @foreach($menuItems as $menu)
                            @php
                                $checked = in_array($menu->id, $assignedMenus);
                                $isDisabled = $menu->is_admin_only;
                            @endphp

                            <div class="col-md-4">
                                <label for="menu_{{ $menu->id }}" class="w-100">

                                    <div class="p-3 border rounded h-100 transition
                                        {{ $checked ? 'border-primary bg-light' : '' }}
                                        {{ $isDisabled ? 'bg-light opacity-75' : '' }}"
                                        style="cursor: {{ $isDisabled ? 'not-allowed' : 'pointer' }};"

                                        @if($isDisabled)
                                            data-bs-toggle="tooltip"
                                            data-bs-placement="top"
                                            title="Menu ini hanya bisa diakses oleh admin"
                                        @endif
                                        >

                                        <div class="form-check d-flex align-items-start gap-2 mb-1">
                                            <input class="form-check-input mt-1"
                                                type="checkbox"
                                                name="menus[]"
                                                value="{{ $menu->id }}"
                                                id="menu_{{ $menu->id }}"
                                                {{ $checked ? 'checked' : '' }}
                                                {{ $isDisabled ? 'disabled' : '' }}>

                                            <div class="flex-grow-1">
                                                <div class="fw-semibold">
                                                    {{ $menu->name }}
                                                </div>

                                                @if($menu->route)
                                                    <div class="text-muted small">
                                                        {{ $menu->route }}
                                                    </div>
                                                @endif
                                            </div>
                                        </div>

                                        <div class="d-flex justify-content-between align-items-center mt-2">
                                            @if($menu->is_admin_only)
                                                <span class="badge bg-danger-subtle text-danger small">
                                                    Admin Only
                                                </span>
                                            @endif
                                        </div>

                                    </div>
                                </label>
                            </div>
                            @endforeach
                        </div>

                        {{-- Action --}}
                        <div class="d-flex gap-2 mt-4">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-check-circle me-2"></i>
                                Simpan Perubahan
                            </button>

                            <a href="{{ route('admin.roles.index') }}" class="btn btn-outline-secondary w-100">
                                <i class="bi bi-arrow-left me-2"></i>
                                Kembali
                            </a>
                        </div>

                    </form>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection

@section('scripts')
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (el) {
            return new bootstrap.Tooltip(el);
        });
    });
</script>
@endsection
