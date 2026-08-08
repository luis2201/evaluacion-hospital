<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CategoriaResultado extends Model
{
    protected $table = 'categorias_resultado';

    protected $fillable = ['modelo_evaluacion_id', 'nombre', 'porcentaje_desde', 'porcentaje_hasta', 'interpretacion', 'orden'];

    protected function casts(): array
    {
        return ['porcentaje_desde' => 'decimal:2', 'porcentaje_hasta' => 'decimal:2'];
    }

    public function modeloEvaluacion(): BelongsTo
    {
        return $this->belongsTo(ModeloEvaluacion::class);
    }
}
