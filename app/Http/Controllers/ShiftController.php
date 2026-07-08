<?php

namespace App\Http\Controllers;

use App\Models\Shift;
use Illuminate\Http\Request;

class ShiftController extends Controller
{
    public function index()
    {
        $shifts = Shift::withCount('jadwalKerja')->orderBy('kode_shift')->get();

        return view('shift.index', compact('shifts'));
    }

    public function create()
    {
        return view('shift.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'kode_shift' => 'required|string|max:2|unique:shifts,kode_shift',
            'nama_shift' => 'required|string|max:255',
            'jam_masuk' => 'nullable|date_format:H:i',
            'jam_pulang' => 'nullable|date_format:H:i|after:jam_masuk',
        ]);

        Shift::create([
            'kode_shift' => strtoupper($validated['kode_shift']),
            'nama_shift' => $validated['nama_shift'],
            'jam_masuk' => $validated['jam_masuk'] ?? null,
            'jam_pulang' => $validated['jam_pulang'] ?? null,
        ]);

        return redirect()->route('shift.index')->with('success', 'Shift berhasil ditambahkan.');
    }

    public function edit(Shift $shift)
    {
        return view('shift.edit', compact('shift'));
    }

    public function update(Request $request, Shift $shift)
    {
        $validated = $request->validate([
            'kode_shift' => 'required|string|max:2|unique:shifts,kode_shift,' . $shift->id_shift . ',id_shift',
            'nama_shift' => 'required|string|max:255',
            'jam_masuk' => 'nullable|date_format:H:i',
            'jam_pulang' => 'nullable|date_format:H:i|after:jam_masuk',
        ]);

        $shift->update([
            'kode_shift' => strtoupper($validated['kode_shift']),
            'nama_shift' => $validated['nama_shift'],
            'jam_masuk' => $validated['jam_masuk'] ?? null,
            'jam_pulang' => $validated['jam_pulang'] ?? null,
        ]);

        return redirect()->route('shift.index')->with('success', 'Shift berhasil diperbarui.');
    }

    public function destroy(Shift $shift)
    {
        if ($shift->jadwalKerja()->count() > 0) {
            return redirect()->route('shift.index')
                ->with('error', 'Shift tidak dapat dihapus karena masih digunakan.');
        }

        $shift->delete();

        return redirect()->route('shift.index')->with('success', 'Shift berhasil dihapus.');
    }
}
