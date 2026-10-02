<?php

namespace App\Http\Controllers;

use App\Models\Contrato;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

class UsuarioController extends Controller
{
    public function index()
    {
        return view('usuarios.index', [
            'usuarios' => User::query()
                ->where('empresa_id', auth()->user()->empresa_id)
                ->where('rol', User::USUARIO)
                ->with('contratos')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function create()
    {
        return view('usuarios.form', [
            'usuario' => new User,
            'contratos' => $this->contratosEmpresa(),
            'seleccionados' => array_map('intval', old('contratos', [])),
        ]);
    }

    public function store(Request $request)
    {
        $datos = $this->datos($request, null, true);
        $usuario = User::create([
            'name' => $datos['name'],
            'email' => $datos['email'],
            'password' => $datos['password'],
            'rol' => User::USUARIO,
            'empresa_id' => $request->user()->empresa_id,
            'activo' => $datos['activo'],
        ]);
        $usuario->contratos()->sync($datos['contratos']);

        return redirect()->route('usuarios.index')->with('estado', 'Usuario creado.');
    }

    public function edit(User $usuario)
    {
        $usuario = $this->propio($usuario);

        return view('usuarios.form', [
            'usuario' => $usuario,
            'contratos' => $this->contratosEmpresa(),
            'seleccionados' => array_map('intval', old('contratos', $usuario->contratos()->pluck('contratos.id')->all())),
        ]);
    }

    public function update(Request $request, User $usuario)
    {
        $usuario = $this->propio($usuario);
        $datos = $this->datos($request, $usuario, false);
        $usuario->update([
            'name' => $datos['name'],
            'email' => $datos['email'],
            'activo' => $datos['activo'],
        ] + (isset($datos['password']) ? ['password' => $datos['password']] : []));
        $usuario->contratos()->sync($datos['contratos']);

        return redirect()->route('usuarios.index')->with('estado', 'Usuario actualizado.');
    }

    public function destroy(User $usuario)
    {
        $usuario = $this->propio($usuario);
        $nombre = $usuario->name;
        $usuario->delete();

        return redirect()->route('usuarios.index')->with('estado', 'Usuario '.$nombre.' eliminado.');
    }

    private function propio(User $usuario): User
    {
        abort_unless(
            $usuario->esUsuario() && (int) $usuario->empresa_id === (int) auth()->user()->empresa_id,
            404
        );

        return $usuario;
    }

    /**
     * @return Collection<int, Contrato>
     */
    private function contratosEmpresa(): Collection
    {
        return Contrato::query()
            ->where('empresa_id', auth()->user()->empresa_id)
            ->orderBy('codigo_proceso')
            ->get();
    }

    /**
     * @return array{name: string, email: string, password?: string, activo: bool, contratos: array<int, int>}
     */
    private function datos(Request $request, ?User $usuario, bool $passwordObligatoria): array
    {
        $datos = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($usuario?->id)],
            'password' => [$passwordObligatoria ? 'required' : 'nullable', 'string', 'min:8', 'confirmed'],
            'activo' => ['required', 'boolean'],
            'contratos' => ['required', 'array', 'min:1'],
            'contratos.*' => ['integer', Rule::exists('contratos', 'id')->where('empresa_id', $request->user()->empresa_id)],
        ], [
            'name.required' => 'Escriba el nombre.',
            'email.required' => 'Escriba el correo.',
            'email.unique' => 'Ese correo ya está registrado.',
            'password.required' => 'Escriba la contraseña.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'La confirmación de la contraseña no coincide.',
            'contratos.required' => 'Seleccione al menos un contrato.',
            'contratos.min' => 'Seleccione al menos un contrato.',
        ]);

        $resultado = [
            'name' => $datos['name'],
            'email' => $datos['email'],
            'activo' => $request->boolean('activo'),
            'contratos' => array_map('intval', $datos['contratos']),
        ];
        if (! empty($datos['password'])) {
            $resultado['password'] = $datos['password'];
        }

        return $resultado;
    }
}
