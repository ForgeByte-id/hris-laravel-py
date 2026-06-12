@extends('layouts.app')

@section('title', 'Import Karyawan - HRIS')

@section('content')
<div class="hris-container">
    <div class="mb-3">
        <a href="{{ route('karyawan.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i>Kembali ke Daftar Karyawan
        </a>
    </div>

    <div class="row g-3">
        <div class="col-lg-5">
            <div class="hris-card h-100">
                <div class="hris-card-header">
                    <h5 class="mb-0"><i class="bi bi-filetype-csv me-2"></i>Import Karyawan CSV</h5>
                </div>
                <div class="hris-card-body">
                    @if(session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif
                    @if(session('error'))
                        <div class="alert alert-danger">{{ session('error') }}</div>
                    @endif

                    <form action="{{ route('karyawan.import.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label fw-semibold">File CSV <span class="text-danger">*</span></label>
                            <input type="file"
                                   name="import_file"
                                   class="form-control @error('import_file') is-invalid @enderror"
                                   accept=".csv,text/csv"
                                   required>
                            @error('import_file')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="alert alert-info small">
                            <div class="fw-semibold mb-1">Catatan face image</div>
                            Kolom <code>face_image_path</code> menunjuk file di <code>storage/app/imports/faces</code>.
                            Contoh: <code>budi.jpg</code> atau <code>subfolder/budi.png</code>.
                        </div>

                        <div class="d-flex gap-2 flex-wrap">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-upload me-1"></i>Import
                            </button>
                            <a href="{{ route('karyawan.import.template') }}" class="btn btn-outline-primary">
                                <i class="bi bi-download me-1"></i>Download Template CSV
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="hris-card h-100">
                <div class="hris-card-header">
                    <h5 class="mb-0"><i class="bi bi-list-check me-2"></i>Summary Import</h5>
                </div>
                <div class="hris-card-body">
                    @if($summary)
                        <div class="row g-2 mb-3">
                            <div class="col-6 col-md-3">
                                <div class="p-3 rounded bg-success-subtle text-center">
                                    <div class="h4 mb-0">{{ $summary['success'] }}</div>
                                    <div class="small">Sukses</div>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="p-3 rounded bg-primary-subtle text-center">
                                    <div class="h4 mb-0">{{ $summary['updated'] }}</div>
                                    <div class="small">Updated</div>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="p-3 rounded bg-warning-subtle text-center">
                                    <div class="h4 mb-0">{{ $summary['skipped'] }}</div>
                                    <div class="small">Skipped</div>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="p-3 rounded bg-danger-subtle text-center">
                                    <div class="h4 mb-0">{{ $summary['failed'] }}</div>
                                    <div class="small">Failed</div>
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-sm table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Baris</th>
                                        <th>Status</th>
                                        <th>Pesan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($summary['details'] as $detail)
                                        @php
                                            $badge = [
                                                'success' => 'success',
                                                'updated' => 'primary',
                                                'skipped' => 'warning text-dark',
                                                'failed' => 'danger',
                                            ][$detail['status']] ?? 'secondary';
                                        @endphp
                                        <tr>
                                            <td>{{ $detail['row'] }}</td>
                                            <td><span class="badge bg-{{ $badge }}">{{ ucfirst($detail['status']) }}</span></td>
                                            <td class="small">{{ $detail['message'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center text-muted py-5">
                            <i class="bi bi-file-earmark-arrow-up" style="font-size: 2.5rem;"></i>
                            <p class="mt-2 mb-0">Belum ada proses import pada sesi ini.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
