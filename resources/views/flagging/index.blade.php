@extends('layouts.app')

@section('title', 'Flagging Menu - HRIS')

@section('content')
<div class="hris-container" style="max-width: 760px;">
    <div class="hris-card">
        <div class="hris-card-header">
            <h5 class="mb-0"><i class="bi bi-sliders me-2"></i>Flagging Menu</h5>
        </div>
        <div class="hris-card-body">
            @unless($secretConfigured)
                <div class="alert alert-warning small">
                    <strong>HRIS_FLAGGING_SECRET belum diset.</strong>
                    URL ini tetap admin-only, tetapi disarankan set token secret di env untuk proteksi tambahan.
                </div>
            @endunless

            <form action="{{ route('flagging.update', ['token' => $token]) }}" method="POST">
                @csrf
                <div class="mb-3">
                    <div class="fw-semibold mb-2">Menu yang disembunyikan</div>
                    @foreach($flaggableMenus as $menuName)
                        <div class="form-check mb-2">
                            <input class="form-check-input"
                                   type="checkbox"
                                   name="hidden_menus[]"
                                   value="{{ $menuName }}"
                                   id="menu_{{ $loop->index }}"
                                   @checked(in_array($menuName, $hiddenMenus, true))>
                            <label class="form-check-label" for="menu_{{ $loop->index }}">
                                {{ $menuName }}
                            </label>
                        </div>
                    @endforeach
                </div>

                <div class="d-flex gap-2 flex-wrap">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-1"></i>Simpan Flagging
                    </button>
                    <button type="submit" name="action" value="reset" class="btn btn-outline-secondary">
                        Reset ke Config
                    </button>
                    <a href="{{ route('karyawan.index') }}" class="btn btn-outline-secondary">Kembali</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
