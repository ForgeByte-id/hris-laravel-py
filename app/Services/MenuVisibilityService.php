<?php

namespace App\Services;

use App\Models\MenuItem;
use Illuminate\Support\Facades\Cache;

class MenuVisibilityService
{
    private const CACHE_KEY = 'hris.hidden_menus';

    /**
     * @return array<int, string>
     */
    public function hiddenMenus(): array
    {
        return Cache::get(self::CACHE_KEY, config('hris.hidden_menus', []));
    }

    /**
     * @return array<int, string>
     */
    public function flaggableMenus(): array
    {
        return config('hris.flaggable_hidden_menus', []);
    }

    public function isHidden(MenuItem $menuItem): bool
    {
        return in_array($menuItem->name, $this->hiddenMenus(), true);
    }

    /**
     * @param array<int, string> $hiddenMenus
     */
    public function replaceHiddenMenus(array $hiddenMenus): void
    {
        $allowed = $this->flaggableMenus();
        $hiddenMenus = array_values(array_intersect($allowed, $hiddenMenus));

        Cache::forever(self::CACHE_KEY, $hiddenMenus);
    }

    public function resetToConfig(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
