<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'rol', 'empresa_id', 'activo'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    public const SISTEMAS = 'sistemas';

    public const ADMINISTRADOR = 'administrador';

    public const USUARIO = 'usuario';

    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function contratos(): BelongsToMany
    {
        return $this->belongsToMany(Contrato::class);
    }

    public function esSistemas(): bool
    {
        return $this->rol === self::SISTEMAS;
    }

    public function esAdministrador(): bool
    {
        return $this->rol === self::ADMINISTRADOR;
    }

    public function esUsuario(): bool
    {
        return $this->rol === self::USUARIO;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'activo' => 'boolean',
        ];
    }
}
