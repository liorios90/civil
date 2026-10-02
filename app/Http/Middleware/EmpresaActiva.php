<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EmpresaActiva
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user && ! $user->esSistemas()) {
            $empresa = $user->empresa;
            if (! $empresa || ! $empresa->activo) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('ingresar')->withErrors([
                    'email' => 'La empresa está inactiva.',
                ]);
            }
        }

        return $next($request);
    }
}
