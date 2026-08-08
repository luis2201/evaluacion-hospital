<?php

namespace App\Models;

use App\Enums\CalificacionDescriptor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EvaluacionDescriptorCalificacion extends Model
{
    public $timestamps = false;

    protected $table = 'evaluacion_descriptor_calificaciones';

    protected $fillable = ['evaluacion_descriptor_id', 'calificacion_anterior', 'calificacion_nueva', 'observacion_anterior', 'observacion_nueva', 'calificada_por', 'calificada_at'];

    protected function casts(): array
    {
        return ['calificacion_anterior' => CalificacionDescriptor::class, 'calificacion_nueva' => CalificacionDescriptor::class, 'calificada_at' => 'datetime'];
    }

    public function evaluacionDescriptor(): BelongsTo
    {
        return $this->belongsTo(EvaluacionDescriptor::class);
    }

    public function evaluador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'calificada_por');
    }
}
