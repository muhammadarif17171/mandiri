<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function form() { return view('auth.login'); }

    public function login(LoginRequest $request)
    {
        if (! Auth::attempt($request->validated())) {
            throw ValidationException::withMessages(['username' => 'Nama pengguna atau kata sandi salah.']);
        }
        $request->session()->regenerate();

        return redirect()->route(auth()->user()->role === 'owner' ? 'owner.dashboard' : 'kasir.pos');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
