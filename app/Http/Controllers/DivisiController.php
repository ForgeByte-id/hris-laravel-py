<?php

namespace App\Http\Controllers;

use App\Models\Devisi;
use Illuminate\Http\Request;

class DivisiController extends Controller
{
    public function index()
    {
        $divisi = Devisi::withCount('karyawan')->orderBy('nama_devisi')->get();
        return view('divisi.index', compact('divisi'));
    }

    public function create()
    {
        return view('divisi.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama_devisi' => 'required|string|max:255|unique:devisis,nama_devisi',
        ]);

        Devisi::create([
            'nama_devisi' => $request->nama_devisi,
        ]);

        return redirect()->route('divisi.index')
            ->with('success', 'Divisi berhasil ditambahkan.');
    }

    public function edit($id)
    {
        $divisi = Devisi::findOrFail($id);
        return view('divisi.edit', compact('divisi'));
    }

    public function update(Request $request, $id)
    {
        $divisi = Devisi::findOrFail($id);

        $request->validate([
            'nama_devisi' => 'required|string|max:255|unique:devisis,nama_devisi,' . $id,
        ]);

        $divisi->update([
            'nama_devisi' => $request->nama_devisi,
        ]);

        return redirect()->route('divisi.index')
            ->with('success', 'Divisi berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $divisi = Devisi::findOrFail($id);

        if ($divisi->karyawan()->count() > 0) {
            return redirect()->route('divisi.index')
                ->with('error', 'Divisi tidak dapat dihapus karena masih memiliki karyawan.');
        }

        $divisi->delete();

        return redirect()->route('divisi.index')
            ->with('success', 'Divisi berhasil dihapus.');
    }
}
