<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function proseslogin(Request $request)
    {
            if(Auth::attempt([
                'username'=> $request->username,
                'password'=> $request->password
                ]))  
            {
                $request->session()->regenerate();    
                return redirect('/dashboard');
            
            } else {
                return redirect('/')->with('error', 'Username atau password salah.');
            }
    }

    public function proseslogout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}