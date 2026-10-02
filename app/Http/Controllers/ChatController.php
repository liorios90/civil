<?php

namespace App\Http\Controllers;

use App\Models\Mensaje;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class ChatController extends Controller
{
    private const ZONA = 'America/Guayaquil';

    public function index(Request $request)
    {
        return view('mensajes.index', $this->pantalla($request->user(), null));
    }

    public function show(Request $request, User $usuario)
    {
        $yo = $request->user();
        $otro = $this->interlocutor($yo, $usuario);
        $this->marcarLeidos($yo, $otro);

        return view('mensajes.index', $this->pantalla($yo, $otro));
    }

    public function enviar(Request $request, User $usuario)
    {
        $yo = $request->user();
        $otro = $this->interlocutor($yo, $usuario);
        $texto = trim((string) $request->input('texto'));

        if ($texto === '' || mb_strlen($texto) > 2000) {
            return response()->json(['mensaje' => 'Escriba un mensaje de hasta 2000 caracteres.'], 422);
        }

        $mensaje = Mensaje::create([
            'empresa_id' => $yo->empresa_id,
            'de_id' => $yo->id,
            'para_id' => $otro->id,
            'texto' => $texto,
        ]);

        return response()->json($this->presentar($mensaje, $yo));
    }

    public function novedades(Request $request)
    {
        $yo = $request->user();
        $otro = null;

        if ($request->filled('conversacion')) {
            $candidato = User::query()->find($request->integer('conversacion'));
            abort_unless($candidato, 404);
            $otro = $this->interlocutor($yo, $candidato);
            $this->marcarLeidos($yo, $otro);
        }

        $nuevos = collect();
        if ($otro) {
            $nuevos = $this->entre($yo, $otro)
                ->where('id', '>', $request->integer('despues'))
                ->orderBy('id')
                ->get();
        }

        return response()->json([
            'mensajes' => $nuevos->map(fn (Mensaje $mensaje) => $this->presentar($mensaje, $yo))->values(),
            'sin_leer' => $this->sinLeerTotal($yo),
            'contactos' => $this->resumenContactos($yo),
        ]);
    }

    private function pantalla(User $yo, ?User $otro): array
    {
        $filas = $this->filas($yo);
        if ($otro?->esUsuario()) {
            $otro->load('contratos');
        }

        return [
            'filas' => $filas,
            'abierto' => $otro,
            'mensajes' => $otro ? $this->entre($yo, $otro)->orderByDesc('id')->limit(300)->get()->reverse()->values() : collect(),
            'esAdministrador' => $yo->esAdministrador(),
        ];
    }

    private function filas(User $yo): Collection
    {
        $ultimos = $this->ultimos($yo);
        $sinLeer = $this->sinLeerPorPersona($yo);

        return $this->contactos($yo)
            ->map(fn (User $contacto) => [
                'contacto' => $contacto,
                'ultimo' => $ultimos->get($contacto->id),
                'sin_leer' => (int) ($sinLeer[$contacto->id] ?? 0),
            ])
            ->sortBy([
                fn (array $fila) => $fila['ultimo'] ? 0 : 1,
                fn (array $fila) => $fila['ultimo'] ? -$fila['ultimo']->id : 0,
                fn (array $fila) => mb_strtolower($fila['contacto']->name),
            ])
            ->values();
    }

    private function resumenContactos(User $yo): array
    {
        return $this->filas($yo)->map(function (array $fila) use ($yo) {
            $ultimo = $fila['ultimo'];

            return [
                'id' => $fila['contacto']->id,
                'preview' => $ultimo ? (($ultimo->de_id === $yo->id ? 'Tú: ' : '').$this->recorte($ultimo->texto)) : '',
                'hora' => $ultimo ? $this->hora($ultimo->created_at) : '',
                'sin_leer' => $fila['sin_leer'],
            ];
        })->all();
    }

    private function contactos(User $yo): Collection
    {
        $rol = $yo->esAdministrador() ? User::USUARIO : User::ADMINISTRADOR;

        return User::query()
            ->where('empresa_id', $yo->empresa_id)
            ->where('rol', $rol)
            ->when($rol === User::USUARIO, fn ($consulta) => $consulta->with('contratos'))
            ->orderBy('name')
            ->get();
    }

    private function interlocutor(User $yo, User $otro): User
    {
        abort_unless($otro->empresa_id && (int) $otro->empresa_id === (int) $yo->empresa_id, 404);
        abort_unless($otro->id !== $yo->id, 404);

        $adminYUsuario = ($yo->esAdministrador() && $otro->esUsuario())
            || ($yo->esUsuario() && $otro->esAdministrador());
        abort_unless($adminYUsuario, 404);

        return $otro;
    }

    private function entre(User $yo, User $otro)
    {
        return Mensaje::query()
            ->where('empresa_id', $yo->empresa_id)
            ->where(function ($consulta) use ($yo, $otro) {
                $consulta->where(fn ($q) => $q->where('de_id', $yo->id)->where('para_id', $otro->id))
                    ->orWhere(fn ($q) => $q->where('de_id', $otro->id)->where('para_id', $yo->id));
            });
    }

    private function ultimos(User $yo): Collection
    {
        return Mensaje::query()
            ->where('empresa_id', $yo->empresa_id)
            ->where(function ($consulta) use ($yo) {
                $consulta->where('de_id', $yo->id)->orWhere('para_id', $yo->id);
            })
            ->whereIn('id', function ($consulta) use ($yo) {
                $consulta->from('mensajes')
                    ->selectRaw('max(id)')
                    ->where('empresa_id', $yo->empresa_id)
                    ->where(function ($q) use ($yo) {
                        $q->where('de_id', $yo->id)->orWhere('para_id', $yo->id);
                    })
                    ->groupByRaw('case when de_id = ? then para_id else de_id end', [$yo->id]);
            })
            ->get()
            ->keyBy(fn (Mensaje $mensaje) => $mensaje->de_id === $yo->id ? $mensaje->para_id : $mensaje->de_id);
    }

    private function sinLeerPorPersona(User $yo): Collection
    {
        return Mensaje::query()
            ->where('para_id', $yo->id)
            ->whereNull('leido_at')
            ->selectRaw('de_id, count(*) as total')
            ->groupBy('de_id')
            ->pluck('total', 'de_id');
    }

    private function sinLeerTotal(User $yo): int
    {
        return Mensaje::query()
            ->where('para_id', $yo->id)
            ->whereNull('leido_at')
            ->count();
    }

    private function marcarLeidos(User $yo, User $otro): void
    {
        Mensaje::query()
            ->where('de_id', $otro->id)
            ->where('para_id', $yo->id)
            ->whereNull('leido_at')
            ->update(['leido_at' => now()]);
    }

    private function presentar(Mensaje $mensaje, User $yo): array
    {
        return [
            'id' => $mensaje->id,
            'mio' => $mensaje->de_id === $yo->id,
            'texto' => $mensaje->texto,
            'hora' => $mensaje->created_at->timezone(self::ZONA)->format('H:i'),
        ];
    }

    private function hora(Carbon $fecha): string
    {
        $fecha = $fecha->copy()->timezone(self::ZONA);

        return $fecha->isSameDay(now(self::ZONA))
            ? $fecha->format('H:i')
            : $fecha->format('d/m H:i');
    }

    private function recorte(string $texto): string
    {
        $texto = preg_replace('/\s+/', ' ', trim($texto)) ?? '';

        return mb_strlen($texto) > 70 ? mb_substr($texto, 0, 67).'…' : $texto;
    }
}
