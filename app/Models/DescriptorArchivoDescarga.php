<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DescriptorArchivoDescarga extends Model
{
    public $timestamps = false;

    protected $table = 'descriptor_archivo_descargas';

    protected $fillable = ['descriptor_archivo_id', 'user_id', 'ip_address', 'user_agent', 'descargado_at'];

    protected function casts(): array
    {
        return ['descargado_at' => 'datetime'];
    }

    public function archivo(): BelongsTo
    {
        return $this->belongsTo(DescriptorArchivo::class, 'descriptor_archivo_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
