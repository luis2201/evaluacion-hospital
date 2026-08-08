<?php

namespace App\Models;

use App\Enums\EstadoEvaluacionDominio;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class EvaluacionDominio extends Model
{
    protected $table = 'evaluacion_dominios';

    protected $fillable = ['evaluacion_id', 'dominio_id', 'responsable_id', 'estado', 'enviado_at', 'completado_at'];

    protected function casts(): array
    {
        return ['estado' => EstadoEvaluacionDominio::class, 'enviado_at' => 'datetime', 'completado_at' => 'datetime'];
    }

    public function evaluacion(): BelongsTo
    {
        return $this->belongsTo(Evaluacion::class);
    }

    public function dominio(): BelongsTo
    {
        return $this->belongsTo(Dominio::class);
    }

    public function responsable(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }

    public function autoevaluacion(): HasOne
    {
        return $this->hasOne(AutoevaluacionDominio::class);
    }
}
