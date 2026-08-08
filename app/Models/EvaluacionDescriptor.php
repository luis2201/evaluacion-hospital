<?php

namespace App\Models;

use App\Enums\CalificacionDescriptor;
use App\Enums\EstadoEvaluacionDescriptor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EvaluacionDescriptor extends Model
{
    protected $table = 'evaluacion_descriptores';

    protected $fillable = ['evaluacion_id', 'descriptor_id', 'estado', 'calificacion', 'observacion_evaluador', 'evaluado_por', 'evaluado_at'];

    protected function casts(): array
    {
        return ['estado' => EstadoEvaluacionDescriptor::class, 'calificacion' => CalificacionDescriptor::class, 'evaluado_at' => 'datetime'];
    }

    public function evaluacion(): BelongsTo
    {
        return $this->belongsTo(Evaluacion::class);
    }

    public function descriptor(): BelongsTo
    {
        return $this->belongsTo(Descriptor::class);
    }

    public function evaluador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'evaluado_por');
    }

    public function archivos(): HasMany
    {
        return $this->hasMany(DescriptorArchivo::class);
    }

    public function enlaces(): HasMany
    {
        return $this->hasMany(DescriptorEnlace::class);
    }

    public function observaciones(): HasMany
    {
        return $this->hasMany(Observacion::class);
    }

    public function historialCalificaciones(): HasMany
    {
        return $this->hasMany(EvaluacionDescriptorCalificacion::class)->orderByDesc('calificada_at');
    }
}
