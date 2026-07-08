<?php

namespace App\Http\Controllers;

use App\Models\Divisi;
use Illuminate\Http\Request;

class DivisiController extends Controller
{
    public function index()
    {
        $divisi = Divisi::withCount('karyawan')->orderBy('nama_divisi')->get();
        return view('divisi.index', compact('divisi'));
    }

    public function create()
    {
        return view('divisi.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama_divisi' => 'required|string|max:255|unique:divisis,nama_divisi',
        ]);

        Divisi::create([
            'nama_divisi' => $request->nama_divisi,
        ]);

        return redirect()->route('divisi.index')
            ->with('success', 'Divisi berhasil ditambahkan.');
    }

    public function edit($id)
    {
        $divisi = Divisi::findOrFail($id);
        return view('divisi.edit', compact('divisi'));
    }

    public function update(Request $request, $id)
    {
        $divisi = Divisi::findOrFail($id);

        $request->validate([
            'nama_divisi' => 'required|string|max:255|unique:divisis,nama_divisi,' . $id,
        ]);

        $divisi->update([
            'nama_divisi' => $request->nama_divisi,
        ]);

        return redirect()->route('divisi.index')
            ->with('success', 'Divisi berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $divisi = Divisi::findOrFail($id);

        if ($divisi->karyawan()->count() > 0) {
            return redirect()->route('divisi.index')
                ->with('error', 'Divisi tidak dapat dihapus karena masih memiliki karyawan.');
        }

        $divisi->delete();

        return redirect()->route('divisi.index')
            ->with('success', 'Divisi berhasil dihapus.');
    }
}
