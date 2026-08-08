<?php

namespace App\Models;

use App\Enums\EstadoAutoevaluacion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AutoevaluacionDominio extends Model
{
    protected $table = 'autoevaluaciones_dominios';

    protected $fillable = ['evaluacion_dominio_id', 'contenido', 'cantidad_palabras', 'estado', 'registrada_por', 'enviada_at'];

    protected function casts(): array
    {
        return ['estado' => EstadoAutoevaluacion::class, 'enviada_at' => 'datetime'];
    }

    public function evaluacionDominio(): BelongsTo
    {
        return $this->belongsTo(EvaluacionDominio::class);
    }

    public function registradaPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrada_por');
    }
}
