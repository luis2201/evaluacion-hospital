<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Descriptor extends Model
{
    protected $table = 'descriptores';

    protected $fillable = ['criterio_id', 'codigo', 'descripcion', 'orden', 'puntaje_maximo', 'activo'];

    protected function casts(): array
    {
        return ['activo' => 'boolean', 'puntaje_maximo' => 'integer'];
    }

    public function criterio(): BelongsTo
    {
        return $this->belongsTo(Criterio::class);
    }

    public function evaluacionesDescriptor(): HasMany
    {
        return $this->hasMany(EvaluacionDescriptor::class);
    }
}
