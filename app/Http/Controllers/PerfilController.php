<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class PerfilController extends Controller
{
    public function edit()
    {
        return view('perfil.edit', [
            'usuario' => auth()->user(),
        ]);
    }

    public function update(Request $request)
    {
        $datos = $request->validate([
            'password_actual' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'password_actual.required' => 'Escriba su clave actual.',
            'password.required' => 'Escriba la clave nueva.',
            'password.min' => 'La clave nueva debe tener al menos 8 caracteres.',
            'password.confirmed' => 'La confirmación de la clave nueva no coincide.',
        ]);

        $usuario = $request->user();
        if (! Hash::check($datos['password_actual'], $usuario->password)) {
            throw ValidationException::withMessages([
                'password_actual' => 'La clave actual no es correcta.',
            ]);
        }

        $usuario->update(['password' => $datos['password']]);

        return redirect()->route('perfil.edit')->with('estado', 'Clave actualizada.');
    }
}
