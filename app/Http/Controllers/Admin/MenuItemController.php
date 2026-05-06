<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MenuItem;
use Illuminate\Http\Request;

class MenuItemController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:menu_items',
            'route' => 'required|string|max:255|unique:menu_items',
            'icon' => 'required|string|max:255',
            'is_admin_only' => 'boolean',
            'order' => 'nullable|integer',
        ]);

        $validated['is_admin_only'] = $validated['is_admin_only'] ?? false;
        $validated['order'] = $validated['order'] ?? (MenuItem::max('order') + 1);

        MenuItem::create($validated);

        return redirect()->route('admin.roles.index')->with('success', 'Menu berhasil ditambahkan');
    }

    public function update(Request $request, MenuItem $menuItem)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:menu_items,name,' . $menuItem->id,
            'route' => 'required|string|max:255|unique:menu_items,route,' . $menuItem->id,
            'icon' => 'required|string|max:255',
            'is_admin_only' => 'boolean',
            'order' => 'nullable|integer',
        ]);

        $validated['is_admin_only'] = $validated['is_admin_only'] ?? false;

        $menuItem->update($validated);

        return redirect()->route('admin.roles.index')->with('success', 'Menu berhasil diperbarui');
    }

    public function destroy(MenuItem $menuItem)
    {
        $menuItem->roles()->detach();
        $menuItem->delete();

        return redirect()->route('admin.roles.index')->with('success', 'Menu berhasil dihapus');
    }
}
