<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Dominio extends Model
{
    protected $table = 'dominios';

    protected $fillable = ['modelo_evaluacion_id', 'codigo', 'nombre', 'peso', 'orden', 'activo'];

    protected function casts(): array
    {
        return ['peso' => 'decimal:2', 'activo' => 'boolean'];
    }

    public function modeloEvaluacion(): BelongsTo
    {
        return $this->belongsTo(ModeloEvaluacion::class);
    }

    public function criterios(): HasMany
    {
        return $this->hasMany(Criterio::class)->orderBy('orden');
    }

    public function evaluacionesDominio(): HasMany
    {
        return $this->hasMany(EvaluacionDominio::class);
    }
}
