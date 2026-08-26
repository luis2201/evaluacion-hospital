<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReporteDescarga extends Model
{
    public $timestamps = false;

    protected $table = 'reporte_descargas';

    protected $fillable = ['evaluacion_id', 'user_id', 'tipo', 'nombre_archivo', 'ip_address', 'user_agent', 'descargado_at'];

    protected function casts(): array
    {
        return ['descargado_at' => 'datetime'];
    }

    public function evaluacion(): BelongsTo
    {
        return $this->belongsTo(Evaluacion::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
