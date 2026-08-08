<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Criterio extends Model
{
    protected $table = 'criterios';

    protected $fillable = ['dominio_id', 'codigo', 'nombre', 'orden', 'activo'];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }

    public function dominio(): BelongsTo
    {
        return $this->belongsTo(Dominio::class);
    }

    public function descriptores(): HasMany
    {
        return $this->hasMany(Descriptor::class)->orderBy('orden');
    }
}
