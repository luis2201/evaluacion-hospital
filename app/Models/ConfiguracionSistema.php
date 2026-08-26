<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConfiguracionSistema extends Model
{
    protected $table = 'configuraciones_sistema';

    protected $fillable = ['clave', 'valor', 'grupo', 'actualizada_por'];

    public function actualizador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actualizada_por');
    }
}
