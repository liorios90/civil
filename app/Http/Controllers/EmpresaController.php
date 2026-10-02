<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class EmpresaController extends Controller
{
    public function index()
    {
        return view('empresas.index', [
            'empresas' => Empresa::with('administrador')->latest()->get(),
        ]);
    }

    public function create()
    {
        return view('empresas.form', [
            'empresa' => new Empresa(['activo' => true]),
            'admin' => null,
        ]);
    }

    public function store(Request $request)
    {
        $datos = $this->datos($request, null, true);
        DB::transaction(function () use ($datos) {
            $empresa = Empresa::create($datos['empresa']);
            $empresa->usuarios()->create($datos['admin'] + ['rol' => User::ADMINISTRADOR]);
        });

        return redirect()->route('empresas.index')->with('estado', 'Empresa creada con su usuario administrador.');
    }

    public function edit(Empresa $empresa)
    {
        return view('empresas.form', [
            'empresa' => $empresa,
            'admin' => $empresa->administrador,
        ]);
    }

    public function update(Request $request, Empresa $empresa)
    {
        $admin = $empresa->administrador;
        $datos = $this->datos($request, $admin, $admin === null);
        DB::transaction(function () use ($empresa, $admin, $datos) {
            $empresa->update($datos['empresa']);
            if ($admin) {
                $admin->update($datos['admin']);
            } else {
                $empresa->usuarios()->create($datos['admin'] + ['rol' => User::ADMINISTRADOR]);
            }
        });

        return redirect()->route('empresas.index')->with('estado', 'Empresa actualizada.');
    }

    /**
     * @return array{empresa: array<string, mixed>, admin: array<string, mixed>}
     */
    private function datos(Request $request, ?User $admin, bool $passwordObligatoria): array
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'responsable' => ['required', 'string', 'max:255'],
            'fecha_inicio' => ['required', 'date'],
            'activo' => ['required', 'boolean'],
            'telefonos' => ['nullable', 'string', 'max:1000'],
            'admin_nombre' => ['required', 'string', 'max:255'],
            'admin_email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($admin?->id)],
            'admin_password' => [$passwordObligatoria ? 'required' : 'nullable', 'string', 'min:8', 'confirmed'],
        ], [
            'nombre.required' => 'Escriba el nombre de la empresa.',
            'responsable.required' => 'Escriba el responsable.',
            'fecha_inicio.required' => 'Indique la fecha de inicio.',
            'admin_nombre.required' => 'Escriba el nombre del administrador.',
            'admin_email.required' => 'Escriba el correo del administrador.',
            'admin_email.unique' => 'Ese correo ya está registrado.',
            'admin_password.required' => 'Escriba la contraseña del administrador.',
            'admin_password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'admin_password.confirmed' => 'La confirmación de la contraseña no coincide.',
        ]);

        $usuario = [
            'name' => $datos['admin_nombre'],
            'email' => $datos['admin_email'],
        ];
        if (! empty($datos['admin_password'])) {
            $usuario['password'] = $datos['admin_password'];
        }

        return [
            'empresa' => [
                'nombre' => $datos['nombre'],
                'responsable' => $datos['responsable'],
                'fecha_inicio' => $datos['fecha_inicio'],
                'activo' => $request->boolean('activo'),
                'telefonos' => $datos['telefonos'] ?? null,
            ],
            'admin' => $usuario,
        ];
    }
}
