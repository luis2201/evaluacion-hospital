<?php

namespace App\Enums;

enum EstadoModeloEvaluacion: string
{
    case Borrador = 'BORRADOR';
    case Publicado = 'PUBLICADO';
    case Archivado = 'ARCHIVADO';
}
