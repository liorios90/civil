<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LandingController extends Controller
{
    public function show()
    {
        if (auth()->check()) {
            return redirect()->route('inicio');
        }

        return view('landing');
    }

    public function contacto(Request $request)
    {
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:180'],
            'empresa' => ['required', 'string', 'max:180'],
            'rol' => ['required', 'in:residente,contratista,administrador,otro'],
            'mensaje' => ['nullable', 'string', 'max:500'],
        ]);

        Log::info('Solicitud landing Civil', $data);

        return redirect()
            ->route('landing')
            ->with('estado', 'Recibimos su solicitud. Le contactaremos pronto.')
            ->withFragment('acceso');
    }
}
