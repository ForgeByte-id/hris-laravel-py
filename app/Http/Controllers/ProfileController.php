<?php

namespace App\Http\Controllers;

use App\Models\Karyawan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    public function index()
    {
        $user     = Auth::user();
        $karyawan = Karyawan::where('id_user', $user->id_user)->first();

        return view('profile.index', compact('user', 'karyawan'));
    }
}
