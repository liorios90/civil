<?php

use App\Http\Controllers\AnexoController;
use App\Http\Controllers\CatalogoController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\ContratoController;
use App\Http\Controllers\EmpresaController;
use App\Http\Controllers\FiscalizacionController;
use App\Http\Controllers\FrenteController;
use App\Http\Controllers\HistorialController;
use App\Http\Controllers\ImpresionController;
use App\Http\Controllers\InicioController;
use App\Http\Controllers\RubroController;
use App\Http\Controllers\SesionController;
use App\Http\Controllers\UsuarioController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/ingresar', [SesionController::class, 'crear'])->name('ingresar');
    Route::post('/ingresar', [SesionController::class, 'ingresar'])->middleware('throttle:10,1')->name('ingresar.enviar');
});

Route::get('/fiscalizacion/{token}', [FiscalizacionController::class, 'show'])->where('token', '[A-Za-z0-9]+')->name('fiscalizacion.show');
Route::get('/fiscalizacion/{token}/rubros/{ejecucion}', [FiscalizacionController::class, 'hoja'])->where(['token' => '[A-Za-z0-9]+', 'ejecucion' => '[0-9]+'])->name('fiscalizacion.hoja');
Route::get('/fiscalizacion/{token}/archivos/{ruta}', [FiscalizacionController::class, 'archivo'])->where(['token' => '[A-Za-z0-9]+', 'ruta' => '.*'])->name('fiscalizacion.archivo');

Route::middleware(['auth', 'empresa.activa'])->group(function () {
    Route::post('/salir', [SesionController::class, 'salir'])->name('salir');
    Route::get('/', [InicioController::class, 'index'])->name('inicio');

    Route::middleware('rol:sistemas')->group(function () {
        Route::get('/empresas', [EmpresaController::class, 'index'])->name('empresas.index');
        Route::get('/empresas/nueva', [EmpresaController::class, 'create'])->name('empresas.create');
        Route::post('/empresas', [EmpresaController::class, 'store'])->name('empresas.store');
        Route::get('/empresas/{empresa}/editar', [EmpresaController::class, 'edit'])->name('empresas.edit');
        Route::put('/empresas/{empresa}', [EmpresaController::class, 'update'])->name('empresas.update');
    });

    Route::middleware('rol:administrador')->group(function () {
        Route::get('/usuarios', [UsuarioController::class, 'index'])->name('usuarios.index');
        Route::get('/usuarios/nuevo', [UsuarioController::class, 'create'])->name('usuarios.create');
        Route::post('/usuarios', [UsuarioController::class, 'store'])->name('usuarios.store');
        Route::get('/usuarios/{usuario}/editar', [UsuarioController::class, 'edit'])->name('usuarios.edit');
        Route::put('/usuarios/{usuario}', [UsuarioController::class, 'update'])->name('usuarios.update');
        Route::delete('/usuarios/{usuario}', [UsuarioController::class, 'destroy'])->name('usuarios.destroy');
        Route::get('/historial', [HistorialController::class, 'general'])->name('historial.general');
        Route::get('/catalogo', [CatalogoController::class, 'edit'])->name('catalogo.edit');
        Route::put('/catalogo', [CatalogoController::class, 'update'])->name('catalogo.update');
        Route::post('/catalogo/excel', [CatalogoController::class, 'excel'])->name('catalogo.excel');

        Route::get('/contratos/nuevo', [ContratoController::class, 'create'])->name('contratos.create');
        Route::post('/contratos', [ContratoController::class, 'store'])->name('contratos.store');
        Route::get('/contratos/{contrato}/editar', [ContratoController::class, 'edit'])->name('contratos.edit');
        Route::put('/contratos/{contrato}', [ContratoController::class, 'update'])->name('contratos.update');
        Route::post('/contratos/{contrato}/rubros-excel', [ContratoController::class, 'importarRubros'])->name('contratos.rubros.excel');
        Route::delete('/contratos/{contrato}/rubros/{rubro}', [ContratoController::class, 'eliminarRubro'])->name('contratos.rubros.eliminar');
        Route::delete('/contratos/{contrato}', [ContratoController::class, 'destroy'])->name('contratos.destroy');
        Route::post('/contratos/{contrato}/siguiente', [ContratoController::class, 'abrirSiguiente'])->name('contratos.siguiente');
        Route::delete('/contratos/{contrato}/planillas/{planilla}', [ContratoController::class, 'eliminarPlanilla'])->name('contratos.planillas.eliminar');

        Route::put('/frentes/{frente}', [FrenteController::class, 'update'])->name('frentes.update');
        Route::delete('/frentes/{frente}', [FrenteController::class, 'destroy'])->name('frentes.destroy');

        Route::post('/frentes/{frente}/rubros', [RubroController::class, 'store'])->name('rubros.store');
        Route::put('/rubros/{rubro}', [RubroController::class, 'update'])->name('rubros.update');
        Route::delete('/rubros/{rubro}', [RubroController::class, 'destroy'])->name('rubros.destroy');
    });

    Route::middleware('rol:administrador,usuario')->group(function () {
        Route::get('/mensajes', [ChatController::class, 'index'])->name('mensajes.index');
        Route::get('/mensajes/sin-leer', [ChatController::class, 'sinLeer'])->name('mensajes.sin-leer');
        Route::get('/mensajes/novedades', [ChatController::class, 'novedades'])->name('mensajes.novedades');
        Route::get('/mensajes/{usuario}', [ChatController::class, 'show'])->whereNumber('usuario')->name('mensajes.show');
        Route::post('/mensajes/{usuario}', [ChatController::class, 'enviar'])->whereNumber('usuario')->middleware('throttle:60,1')->name('mensajes.enviar');

        Route::get('/contratos/{contrato}', [ContratoController::class, 'show'])->name('contratos.show');
        Route::post('/contratos/{contrato}/enlace', [ContratoController::class, 'enlace'])->name('contratos.enlace');
        Route::get('/contratos/{contrato}/historial', [HistorialController::class, 'index'])->name('historial.index');
        Route::post('/contratos/{contrato}/frentes', [FrenteController::class, 'store'])->name('frentes.store');
        Route::get('/frentes/{frente}', [FrenteController::class, 'show'])->name('frentes.show');
        Route::post('/frentes/{frente}/hoja', [RubroController::class, 'guardar'])->name('rubros.guardar');

        Route::get('/ejecuciones/{ejecucion}/anexo', [AnexoController::class, 'show'])->name('anexos.show');
        Route::get('/planillas/{planilla}/imprimir', [ImpresionController::class, 'planilla'])->name('impresion.planilla');
        Route::get('/planillas/{planilla}/excel', [ImpresionController::class, 'excel'])->name('impresion.excel');
        Route::get('/planillas/{planilla}/avance', [ImpresionController::class, 'comparacion'])->name('avance.comparacion');
        Route::get('/ejecuciones/{ejecucion}/imprimir', [ImpresionController::class, 'anexo'])->name('impresion.anexo');
        Route::post('/ejecuciones/{ejecucion}/anexo/guardar', [AnexoController::class, 'guardar'])->name('anexos.guardar');
        Route::post('/ejecuciones/{ejecucion}/imagenes', [AnexoController::class, 'imagenes'])->name('anexos.imagenes');
        Route::get('/archivos/{ruta}', [AnexoController::class, 'archivo'])->where('ruta', '.*')->name('archivos.publicos');
        Route::delete('/ejecuciones/{ejecucion}/imagenes/{imagen}', [AnexoController::class, 'destroyImagen'])->name('anexos.imagenes.destroy');
        Route::post('/ejecuciones/{ejecucion}/anexo', [AnexoController::class, 'store'])->name('anexos.store');
        Route::delete('/ejecuciones/{ejecucion}/lineas/{linea}', [AnexoController::class, 'destroy'])->name('anexos.destroy');
    });
});
