<?php

if (!function_exists('navActive')) {
    function navActive($pattern)
    {
        return request()->is($pattern) ? 'active' : '';
    }
}

if (!function_exists('navStyle')) {
    function navStyle($pattern)
    {
        return request()->is($pattern)
            ? 'background: rgba(79, 110, 247, 0.1); color: var(--hris-primary);'
            : '';
    }
}
