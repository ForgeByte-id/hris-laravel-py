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

    public function isAccessibleByRole(Role $role): bool
    {
        if ($this->is_admin_only) {
            return $role->name === 'admin';
        }
        return $this->roles()->where('role_id', $role->id)->exists();
    }
}
