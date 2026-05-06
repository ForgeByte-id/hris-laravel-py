@php($showChrome = trim($__env->yieldContent('show_chrome', '1')) === '1')
@php($showBottomNav = trim($__env->yieldContent('show_bottom_nav', $showChrome ? '0' : '1')) === '1')
@php($footerContent = trim($__env->yieldContent('footer')))
@if ($showBottomNav || $footerContent !== '')
<footer class="appFooter">
    @if ($showBottomNav)
        @include('layouts.bottomNav')
    @endif
    @if ($footerContent !== '')
        @yield('footer')
    @endif
</footer>
@endif
