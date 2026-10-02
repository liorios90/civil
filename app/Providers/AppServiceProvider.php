<?php

namespace App\Providers;

use App\Models\Mensaje;
use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('layout', function ($view) {
            $cantidad = 0;
            $usuario = auth()->user();
            if ($usuario instanceof User && ($usuario->esAdministrador() || $usuario->esUsuario()) && Schema::hasTable('mensajes')) {
                $cantidad = Mensaje::query()->where('para_id', $usuario->id)->whereNull('leido_at')->count();
            }
            $view->with('sinLeerMensajes', $cantidad);
        });
    }
}
