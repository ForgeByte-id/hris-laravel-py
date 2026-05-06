<?php

namespace App\Models;

use App\Models\MenuItem;
use Spatie\Permission\Models\Role as SpatieRole;


class Role extends SpatieRole
{
    public function menus()
    {
        return $this->belongsToMany(MenuItem::class, 'menu_item_role_permissions', 'role_id', 'menu_id');
    }
}
