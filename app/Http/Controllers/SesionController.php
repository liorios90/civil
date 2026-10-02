<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SesionController extends Controller
{
    public function crear()
    {
        return view('auth.ingresar');
    }

    public function ingresar(Request $request)
    {
        $credenciales = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credenciales, $request->boolean('recordar'))) {
            return back()->withErrors([
                'email' => 'Correo o contraseña incorrectos.',
            ])->onlyInput('email');
        }

        $request->session()->regenerate();
        $user = $request->user();
        if (! $user->esSistemas() && (! $user->empresa || ! $user->empresa->activo)) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()->withErrors([
                'email' => 'La empresa está inactiva.',
            ])->onlyInput('email');
        }

        return redirect()->intended(route('inicio'));
    }

    public function salir(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('ingresar');
    }
}
