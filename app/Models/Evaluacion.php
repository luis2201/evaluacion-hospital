<?php

namespace App\Models;

use App\Enums\CodigoRol;
use App\Enums\EstadoEvaluacion;
use App\Enums\TipoEscenario;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Evaluacion extends Model
{
    protected $table = 'evaluaciones';

    protected $fillable = ['modelo_evaluacion_id', 'codigo', 'nombre', 'descripcion', 'tipo_escenario', 'fecha_inicio', 'fecha_limite_carga', 'fecha_inicio_evaluacion', 'fecha_cierre_prevista', 'fecha_cierre', 'estado', 'creada_por', 'cerrada_por', 'cerrada_at'];

    protected function casts(): array
    {
        return [
            'tipo_escenario' => TipoEscenario::class,
            'estado' => EstadoEvaluacion::class,
            'fecha_inicio' => 'date',
            'fecha_limite_carga' => 'date',
            'fecha_inicio_evaluacion' => 'date',
            'fecha_cierre_prevista' => 'date',
            'fecha_cierre' => 'date',
            'cerrada_at' => 'datetime',
        ];
    }

    public function modeloEvaluacion(): BelongsTo
    {
        return $this->belongsTo(ModeloEvaluacion::class);
    }

    public function creador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creada_por');
    }

    public function cerrador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cerrada_por');
    }

    public function dominios(): HasMany
    {
        return $this->hasMany(EvaluacionDominio::class);
    }

    public function descriptores(): HasMany
    {
        return $this->hasMany(EvaluacionDescriptor::class);
    }

    public function evaluadores(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'evaluacion_evaluadores', 'evaluacion_id', 'evaluador_id')->withPivot(['es_principal', 'asignado_at'])->withTimestamps();
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->isAdministrator() || $user->hasRole(CodigoRol::AuditorLectura)) {
            return $query;
        }

        return $query->where(function (Builder $query) use ($user): void {
            $query->where('creada_por', $user->id)
                ->orWhereHas('dominios', fn (Builder $query) => $query->where('responsable_id', $user->id))
                ->orWhereHas('evaluadores', fn (Builder $query) => $query->whereKey($user->id));
        });
    }
}
