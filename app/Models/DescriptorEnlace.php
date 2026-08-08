<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class DescriptorEnlace extends Model
{
    use SoftDeletes;

    protected $table = 'descriptor_enlaces';

    protected $fillable = ['evaluacion_descriptor_id', 'url', 'descripcion', 'registrado_por'];

    public function evaluacionDescriptor(): BelongsTo
    {
        return $this->belongsTo(EvaluacionDescriptor::class);
    }

    public function registrador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }
}
