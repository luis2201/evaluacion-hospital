<?php

namespace App\Models;

use App\Enums\EstadoObservacion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Observacion extends Model
{
    protected $table = 'observaciones';

    protected $fillable = ['evaluacion_descriptor_id', 'creada_por', 'asunto', 'detalle', 'estado', 'fecha_limite', 'cerrada_por', 'cerrada_at'];

    protected function casts(): array
    {
        return ['estado' => EstadoObservacion::class, 'fecha_limite' => 'date', 'cerrada_at' => 'datetime'];
    }

    public function evaluacionDescriptor(): BelongsTo
    {
        return $this->belongsTo(EvaluacionDescriptor::class);
    }

    public function creador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creada_por');
    }

    public function cerrador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cerrada_por');
    }

    public function respuestas(): HasMany
    {
        return $this->hasMany(ObservacionRespuesta::class);
    }
}
