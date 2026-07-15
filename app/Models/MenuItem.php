<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Role;

class MenuItem extends Model
{
    protected $fillable = ['name', 'route', 'icon', 'order', 'is_admin_only'];

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_menu_permissions', 'menu_id', 'role_id');
    }

    public function isAccessibleByRole(?Role $role): bool
    {
        // Admin-only items: admin, plus HR-family (hr, hrd) dan role "sdm"
        if ($this->is_admin_only) {
            return $role && in_array(strtolower($role->name), ['admin', 'hr', 'hrd', 'sdm'], true);
        }

        // If no roles are explicitly assigned in the pivot, treat the item
        // as visible to every authenticated user (open/public menu item).
        if ($this->roles()->count() === 0) {
            return true;
        }

        // Otherwise restrict to the explicitly assigned roles
        if (!$role) {
            return false;
        }

        return $this->roles()->where('role_id', $role->id)->exists();
    }
}
