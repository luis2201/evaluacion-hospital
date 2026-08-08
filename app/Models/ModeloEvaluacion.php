<?php

namespace App\Models;

use App\Enums\EstadoModeloEvaluacion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ModeloEvaluacion extends Model
{
    protected $table = 'modelos_evaluacion';

    protected $fillable = ['modelo_origen_id', 'nombre', 'version', 'descripcion', 'estado', 'publicado_at'];

    protected function casts(): array
    {
        return ['estado' => EstadoModeloEvaluacion::class, 'publicado_at' => 'datetime'];
    }

    public function dominios(): HasMany
    {
        return $this->hasMany(Dominio::class)->orderBy('orden');
    }

    public function categoriasResultado(): HasMany
    {
        return $this->hasMany(CategoriaResultado::class)->orderBy('orden');
    }

    public function evaluaciones(): HasMany
    {
        return $this->hasMany(Evaluacion::class);
    }

    public function modeloOrigen(): BelongsTo
    {
        return $this->belongsTo(self::class, 'modelo_origen_id');
    }

    public function versionesDerivadas(): HasMany
    {
        return $this->hasMany(self::class, 'modelo_origen_id');
    }

    public function isEditable(): bool
    {
        return $this->estado === EstadoModeloEvaluacion::Borrador && ! $this->evaluaciones()->exists();
    }

    public function isUsed(): bool
    {
        return $this->evaluaciones()->exists();
    }
}
