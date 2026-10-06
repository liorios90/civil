<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Empresa extends Model
{
    protected $fillable = [
        'nombre', 'responsable', 'fecha_inicio', 'activo', 'telefonos',
    ];

    protected function casts(): array
    {
        return [
            'fecha_inicio' => 'date',
            'activo' => 'boolean',
        ];
    }

    public function usuarios(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function administrador(): HasOne
    {
        return $this->hasOne(User::class)->where('rol', User::ADMINISTRADOR);
    }

    public function contratos(): HasMany
    {
        return $this->hasMany(Contrato::class);
    }

    public function catalogoRubros(): HasMany
    {
        return $this->hasMany(CatalogoRubro::class)->orderBy('numero');
    }
}
