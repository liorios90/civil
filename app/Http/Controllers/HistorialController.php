<?php

namespace App\Http\Controllers;

use App\Models\Contrato;
use App\Models\Frente;
use App\Models\HistorialCambio;
use App\Models\PlanillaRubro;
use App\Models\User;
use App\Services\Historial;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class HistorialController extends Controller
{
    public function index(Request $request, Contrato $contrato)
    {
        $frente = null;
        $ejecucion = null;
        $consulta = HistorialCambio::query()
            ->with('usuario')
            ->where('contrato_id', $contrato->id);

        if ($request->filled('ejecucion')) {
            $ejecucion = PlanillaRubro::with('rubro.frente')
                ->whereHas('planilla', fn ($q) => $q->where('contrato_id', $contrato->id))
                ->findOrFail((int) $request->query('ejecucion'));
            $consulta->where('planilla_rubro_id', $ejecucion->id);
        } elseif ($request->filled('frente')) {
            $frente = Frente::where('contrato_id', $contrato->id)->findOrFail((int) $request->query('frente'));
            $consulta->where('frente_id', $frente->id);
        }

        return view('historial.index', [
            'contrato' => $contrato,
            'frente' => $frente,
            'ejecucion' => $ejecucion,
            'cambios' => $consulta->latest('created_at')->latest('id')->paginate(50)->withQueryString(),
        ]);
    }

    public function general(Request $request)
    {
        $empresaId = $request->user()->empresa_id;
        $filtros = $request->validate([
            'usuario' => ['nullable', 'integer'],
            'accion' => ['nullable', 'in:creado,modificado,eliminado'],
            'contrato' => ['nullable', 'integer'],
            'desde' => ['nullable', 'date'],
            'hasta' => ['nullable', 'date'],
        ]);

        $contratos = Contrato::where('empresa_id', $empresaId)->orderBy('codigo_proceso')->get();
        $consulta = HistorialCambio::query()
            ->with(['usuario', 'contrato'])
            ->where('empresa_id', $empresaId);

        if (! empty($filtros['usuario'])) {
            $consulta->where('user_id', $filtros['usuario']);
        }
        if (! empty($filtros['accion'])) {
            $consulta->where('accion', $filtros['accion']);
        }
        if (! empty($filtros['contrato'])) {
            $consulta->where('contrato_id', $filtros['contrato']);
        }
        if (! empty($filtros['desde'])) {
            $consulta->where('created_at', '>=', Carbon::parse($filtros['desde'], Historial::ZONA)->startOfDay()->utc());
        }
        if (! empty($filtros['hasta'])) {
            $consulta->where('created_at', '<=', Carbon::parse($filtros['hasta'], Historial::ZONA)->endOfDay()->utc());
        }

        return view('historial.general', [
            'cambios' => $consulta->latest('created_at')->latest('id')->paginate(50)->withQueryString(),
            'contratos' => $contratos,
            'usuarios' => User::where('empresa_id', $empresaId)->orderBy('name')->get(),
        ]);
    }
}
