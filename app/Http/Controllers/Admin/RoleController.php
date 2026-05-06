<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MenuItem;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::where('guard_name', 'web')->get();
        $menuItems = MenuItem::orderBy('order')->get();
        
        return view('admin.roles.index', compact('roles', 'menuItems'));
    }

    public function edit(Role $role)
    {
        $menuItems = MenuItem::orderBy('order')->get();
        $assignedMenus = $role->menus()->pluck('menu_items.id')->toArray();
        
        return view('admin.roles.edit', compact('role', 'menuItems', 'assignedMenus'));
    }

    public function update(Request $request, Role $role)
    {
        $validated = $request->validate([
            'menus' => 'array',
            'menus.*' => 'exists:menu_items,id',
        ]);

        $role->menus()->sync($validated['menus'] ?? []);

        return redirect()->route('admin.roles.index')->with('success', 'Role menu permissions updated');
    }
}

