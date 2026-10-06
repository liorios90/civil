@if (auth()->user()?->esAdministrador())
@php
    $ultimo = \App\Models\HistorialCambio::query()
        ->with('usuario')
        ->where($filtro)
        ->latest('created_at')
        ->latest('id')
        ->first();
    $creadoPor = $creado?->creador?->name;
@endphp
<p class="registro">
    @if ($creado && $creado->created_at)
        Creado{{ $creadoPor ? ' por '.$creadoPor : '' }} el {{ \App\Services\Historial::fecha($creado->created_at) }}.
    @endif
    @if ($ultimo)
        Último cambio por {{ $ultimo->nombreUsuario() }} el {{ \App\Services\Historial::fecha($ultimo->created_at) }}.
    @endif
    <a href="{{ $enlace }}">Historial de cambios</a>
</p>
@endif
