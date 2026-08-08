<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Auditoria extends Model
{
    public $timestamps = false;

    protected $table = 'auditorias';

    protected $fillable = ['user_id', 'accion', 'tabla', 'registro_id', 'valores_anteriores', 'valores_nuevos', 'ip_address', 'user_agent', 'created_at'];

    protected function casts(): array
    {
        return ['valores_anteriores' => 'array', 'valores_nuevos' => 'array', 'created_at' => 'datetime'];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
