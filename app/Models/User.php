<?php

namespace App\Models;

use App\Enums\CodigoRol;
use Database\Factories\UserFactory;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'activo', 'ultimo_acceso_at', 'password_changed_at'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

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
            'ultimo_acceso_at' => 'datetime',
            'password_changed_at' => 'datetime',
        ];
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class)->withPivot('created_at');
    }

    public function evaluacionesCreadas(): HasMany
    {
        return $this->hasMany(Evaluacion::class, 'creada_por');
    }

    public function dominiosAsignados(): HasMany
    {
        return $this->hasMany(EvaluacionDominio::class, 'responsable_id');
    }

    public function archivosCargados(): HasMany
    {
        return $this->hasMany(DescriptorArchivo::class, 'cargado_por');
    }

    public function hasRole(string|CodigoRol $role): bool
    {
        $code = $role instanceof CodigoRol ? $role->value : $role;

        return $this->roles()->where('codigo', $code)->exists();
    }

    public function isAdministrator(): bool
    {
        return $this->hasRole(CodigoRol::Administrador);
    }
}
