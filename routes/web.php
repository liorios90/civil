<?php

use App\Http\Controllers\AnexoController;
use App\Http\Controllers\ContratoController;
use App\Http\Controllers\FrenteController;
use App\Http\Controllers\ImpresionController;
use App\Http\Controllers\RubroController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ContratoController::class, 'index'])->name('inicio');
Route::get('/contratos/nuevo', [ContratoController::class, 'create'])->name('contratos.create');
Route::post('/contratos', [ContratoController::class, 'store'])->name('contratos.store');
Route::get('/contratos/{contrato}/editar', [ContratoController::class, 'edit'])->name('contratos.edit');
Route::get('/contratos/{contrato}', [ContratoController::class, 'show'])->name('contratos.show');
Route::put('/contratos/{contrato}', [ContratoController::class, 'update'])->name('contratos.update');
Route::post('/contratos/{contrato}/rubros-excel', [ContratoController::class, 'importarRubros'])->name('contratos.rubros.excel');
Route::delete('/contratos/{contrato}/rubros/{rubro}', [ContratoController::class, 'eliminarRubro'])->name('contratos.rubros.eliminar');
Route::delete('/contratos/{contrato}', [ContratoController::class, 'destroy'])->name('contratos.destroy');

Route::post('/contratos/{contrato}/frentes', [FrenteController::class, 'store'])->name('frentes.store');
Route::get('/frentes/{frente}', [FrenteController::class, 'show'])->name('frentes.show');
Route::put('/frentes/{frente}', [FrenteController::class, 'update'])->name('frentes.update');
Route::delete('/frentes/{frente}', [FrenteController::class, 'destroy'])->name('frentes.destroy');

Route::post('/frentes/{frente}/hoja', [RubroController::class, 'guardar'])->name('rubros.guardar');
Route::post('/frentes/{frente}/rubros', [RubroController::class, 'store'])->name('rubros.store');
Route::put('/rubros/{rubro}', [RubroController::class, 'update'])->name('rubros.update');
Route::delete('/rubros/{rubro}', [RubroController::class, 'destroy'])->name('rubros.destroy');

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
