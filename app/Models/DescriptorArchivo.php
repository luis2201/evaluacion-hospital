<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class DescriptorArchivo extends Model
{
    use SoftDeletes;

    protected $table = 'descriptor_archivos';

    protected $fillable = ['evaluacion_descriptor_id', 'descripcion', 'disco', 'ruta', 'nombre_original', 'nombre_almacenado', 'mime_type', 'extension', 'tamano_bytes', 'hash_sha256', 'cargado_por', 'eliminado_por'];

    protected function casts(): array
    {
        return ['tamano_bytes' => 'integer'];
    }

    public function evaluacionDescriptor(): BelongsTo
    {
        return $this->belongsTo(EvaluacionDescriptor::class);
    }

    public function cargador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cargado_por');
    }

    public function eliminador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'eliminado_por');
    }

    public function descargas(): HasMany
    {
        return $this->hasMany(DescriptorArchivoDescarga::class);
    }
}
