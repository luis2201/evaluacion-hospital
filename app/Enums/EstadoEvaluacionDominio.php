<?php

namespace App\Enums;

enum EstadoEvaluacionDominio: string
{
    case Pendiente = 'PENDIENTE';
    case EnCarga = 'EN_CARGA';
    case Enviado = 'ENVIADO';
    case Observado = 'OBSERVADO';
    case Completo = 'COMPLETO';
    case Cerrado = 'CERRADO';
}
