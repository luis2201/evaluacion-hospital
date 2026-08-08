<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ObservacionRespuesta extends Model
{
    protected $table = 'observacion_respuestas';

    protected $fillable = ['observacion_id', 'respondida_por', 'respuesta'];

    public function observacion(): BelongsTo
    {
        return $this->belongsTo(Observacion::class);
    }

    public function autor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'respondida_por');
    }
}
