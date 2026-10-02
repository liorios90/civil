@php
    $acciones = ['creado' => 'Creó', 'modificado' => 'Modificó', 'eliminado' => 'Eliminó'];
    $conContrato ??= false;
@endphp
@if ($cambios->isEmpty())
    <div class="card"><p>No hay cambios registrados.</p></div>
@else
    <div class="card historial">
        <table>
            <thead>
                <tr>
                    <th>Fecha y hora</th>
                    <th>Usuario</th>
                    <th>Acción</th>
                    @if ($conContrato)
                        <th>Contrato</th>
                    @endif
                    <th>Registro</th>
                    <th>Detalle</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($cambios as $cambio)
                    <tr>
                        <td class="fecha">{{ \App\Services\Historial::fecha($cambio->created_at) }}</td>
                        <td>{{ $cambio->nombreUsuario() }}</td>
                        <td><span class="accion accion-{{ $cambio->accion }}">{{ $acciones[$cambio->accion] ?? $cambio->accion }}</span></td>
                        @if ($conContrato)
                            <td>
                                @if ($cambio->contrato)
                                    <a href="{{ route('historial.index', $cambio->contrato) }}">{{ $cambio->contrato->codigo_proceso }}</a>
                                @endif
                            </td>
                        @endif
                        <td>{{ $cambio->descripcion }}</td>
                        <td>
                            @foreach ($cambio->cambios ?? [] as $campo => $valores)
                                <div>
                                    <b>{{ \App\Services\Historial::campo($campo) }}:</b>
                                    @if (array_key_exists('antes', $valores) && array_key_exists('despues', $valores))
                                        {{ \App\Services\Historial::valor($valores['antes'], $campo) }} → {{ \App\Services\Historial::valor($valores['despues'], $campo) }}
                                    @elseif (array_key_exists('despues', $valores))
                                        {{ \App\Services\Historial::valor($valores['despues'], $campo) }}
                                    @else
                                        {{ \App\Services\Historial::valor($valores['antes'] ?? null, $campo) }}
                                    @endif
                                </div>
                            @endforeach
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @if ($cambios->hasPages())
        <p class="acciones">
            @if ($cambios->previousPageUrl())
                <a class="btn secundario" href="{{ $cambios->previousPageUrl() }}">Más recientes</a>
            @endif
            <span>Página {{ $cambios->currentPage() }} de {{ $cambios->lastPage() }}</span>
            @if ($cambios->nextPageUrl())
                <a class="btn secundario" href="{{ $cambios->nextPageUrl() }}">Más antiguos</a>
            @endif
        </p>
    @endif
@endif
