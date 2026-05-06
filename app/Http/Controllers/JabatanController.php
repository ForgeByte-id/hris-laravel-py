<?php

namespace App\Http\Controllers;

use App\Models\Jabatan;
use Illuminate\Http\Request;

class JabatanController extends Controller
{
    public function index()
    {
        $jabatan = Jabatan::withCount('karyawan')->orderBy('nama_jabatan')->get();
        return view('jabatan.index', compact('jabatan'));
    }

    public function create()
    {
        return view('jabatan.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama_jabatan' => 'required|string|max:255|unique:jabatans,nama_jabatan',
        ]);

        Jabatan::create([
            'nama_jabatan' => $request->nama_jabatan,
        ]);

        return redirect()->route('jabatan.index')
            ->with('success', 'Jabatan berhasil ditambahkan.');
    }

    public function edit($id)
    {
        $jabatan = Jabatan::findOrFail($id);
        return view('jabatan.edit', compact('jabatan'));
    }

    public function update(Request $request, $id)
    {
        $jabatan = Jabatan::findOrFail($id);

        $request->validate([
            'nama_jabatan' => 'required|string|max:255|unique:jabatans,nama_jabatan,' . $id,
        ]);

        $jabatan->update([
            'nama_jabatan' => $request->nama_jabatan,
        ]);

        return redirect()->route('jabatan.index')
            ->with('success', 'Jabatan berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $jabatan = Jabatan::findOrFail($id);

        if ($jabatan->karyawan()->count() > 0) {
            return redirect()->route('jabatan.index')
                ->with('error', 'Jabatan tidak dapat dihapus karena masih memiliki karyawan.');
        }

        $jabatan->delete();

        return redirect()->route('jabatan.index')
            ->with('success', 'Jabatan berhasil dihapus.');
    }
}
