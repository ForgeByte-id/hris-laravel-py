<!doctype html>
<html lang="@yield('html_lang', 'en')">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate" />
    <meta http-equiv="Pragma" content="no-cache" />
    <meta http-equiv="Expires" content="0" />
    <meta name="apple-mobile-web-app-capable" content="yes" />
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="theme-color" content="#000000">
    <title>@yield('title', 'HRIS')</title>
    <meta name="description" content="@yield('meta_description', 'Mobilekit HTML Mobile UI Kit')">
    <meta name="keywords" content="@yield('meta_keywords', 'bootstrap 4, mobile template, cordova, phonegap, mobile, html, hris')" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.13.1/font/bootstrap-icons.min.css" integrity="sha512-t7Few9xlddEmgd3oKZQahkNI4dS6l80+eGEzFQiqtyVYdvcSG2D3Iub77R20BdotfRPA9caaRkg1tyaJiPmO0g==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    {{-- datatable --}}
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css">
    @php($includeDefaultStyles = trim($__env->yieldContent('include_default_styles', '1')) === '1')
    @if ($includeDefaultStyles)
        <link rel="stylesheet" href="{{ asset('assets/css/cuti.css') }}">
        <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">
        {{-- <link rel="manifest" href="__manifest.json"> --}}
    @endif
    <style>
        :root {
            --hris-bg: #f5f7fb;
            --hris-surface: #ffffff;
            --hris-primary: #4f6ef7;
            --hris-accent: #2ec4b6;
            --hris-border: #e5e7eb;
            --hris-muted: #6b7280;
            --hris-radius: 14px;
            --hris-shadow: 0 8px 24px rgba(15, 23, 42, 0.08);
        }

        body.hris-body {
            background: var(--hris-bg);
            color: #111827;
        }

        .hris-shell {
            display: flex;
            min-height: 100vh;
        }

        .hris-sidenav {
            width: 260px;
            background: var(--hris-surface);
            border-right: 1px solid var(--hris-border);
            padding: 24px 18px;
            position: sticky;
            top: 0;
            height: 100vh;
        }

        .hris-topbar {
            background: var(--hris-surface);
            border-bottom: 1px solid var(--hris-border);
            padding: 14px 24px;
            position: sticky;
            top: 0;
            z-index: 1020;
        }

        .hris-content {
            flex: 1;
            padding: 24px;
        }

        .hris-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .hris-card {
            background: var(--hris-surface);
            border: 1px solid var(--hris-border);
            border-radius: var(--hris-radius);
            box-shadow: var(--hris-shadow);
        }

        .hris-card-header {
            padding: 16px 20px;
            border-bottom: 1px solid var(--hris-border);
            background: linear-gradient(135deg, #f5f7ff, #ffffff);
            border-top-left-radius: var(--hris-radius);
            border-top-right-radius: var(--hris-radius);
        }

        .hris-card-body {
            padding: 20px;
        }

        .hris-btn {
            border-radius: 10px;
            padding: 10px 16px;
            font-weight: 600;
        }

        .hris-btn-primary {
            background: var(--hris-primary);
            color: #ffffff;
            border: none;
        }

        .hris-btn-secondary {
            background: #64748b;
            color: #ffffff;
            border: none;
        }

        .hris-badge {
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 600;
        }

        .hris-table th {
            background: #f8fafc;
        }

        .hris-muted {
            color: var(--hris-muted);
        }

        @media (max-width: 992px) {
            .hris-shell {
                display: block;
            }

            .hris-sidenav {
                display: none;
            }

            .hris-content {
                padding: 16px;
            }
        }

        /* wrapper spacing */
        .dataTables_wrapper {
            padding: 16px;
        }

        /* search box */
        .dataTables_filter input {
            border-radius: 8px;
            border: 1px solid #ddd;
            padding: 6px 10px;
            margin-left: 8px;
        }

        /* length dropdown */
        .dataTables_length select {
            border-radius: 8px;
            padding: 4px 8px;
        }

        /* table row */
        table.dataTable tbody tr {
            transition: all 0.2s ease;
        }

        table.dataTable tbody tr:hover {
            background-color: #f8f9fa !important;
        }

        /* pagination */
        .dataTables_paginate .paginate_button {
            border-radius: 6px !important;
            padding: 4px 10px !important;
            margin: 0 2px;
        }

        .dataTables_paginate .paginate_button.current {
            background: var(--hris-primary) !important;
            color: #fff !important;
            border: none;
        }

        /* info text */
        .dataTables_info {
            font-size: 12px;
            color: #888;
        }

        .nav-link.active {
            background: rgba(79, 110, 247, 0.1);
            color: var(--hris-primary) !important;
        }

        .nav-link {
            color: var(--hris-muted);
        }
    </style>
    @yield('head')
    @yield('styles')
</head>

<body class="hris-body @yield('body_class')" style="@yield('body_style')">

    @php($showChrome = trim($__env->yieldContent('show_chrome', '1')) === '1')
    @if ($showChrome)
        <div class="hris-shell">
            @include('layouts.partials.sidenav')
            <div class="flex-grow-1">
                @include('layouts.partials.topnav')
                @php($showLoader = trim($__env->yieldContent('show_loader', '1')) === '1')
                @if ($showLoader)
                    <div id="loader">
                        <div class="spinner-border text-primary" role="status"></div>
                    </div>
                @endif
                <main id="appCapsule" class="hris-content @yield('app_capsule_class')">
                    @yield('content')
                </main>
            </div>
        </div>
    @else
        @php($showLoader = trim($__env->yieldContent('show_loader', '1')) === '1')
        @if ($showLoader)
            <div id="loader">
                <div class="spinner-border text-primary" role="status"></div>
            </div>
        @endif
        @php($useAppCapsule = trim($__env->yieldContent('use_app_capsule', '1')) === '1')
        @if ($useAppCapsule)
            <main id="appCapsule" class="@yield('app_capsule_class')">
                @yield('content')
            </main>
        @else
            @yield('content')
        @endif
    @endif
    @include('layouts.partials.footer')

    @php($includeDefaultScripts = trim($__env->yieldContent('include_default_scripts', '1')) === '1')
    @if ($includeDefaultScripts)
        @include('layouts.script')
    @endif
    @include('layouts.partials.cdn-scripts')
    @yield('scripts')

</body>

</html>
