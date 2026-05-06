@php($headerContent = trim($__env->yieldContent('header')))
@if ($headerContent !== '')
<header class="appHeader">
    @yield('header')
</header>
@endif
