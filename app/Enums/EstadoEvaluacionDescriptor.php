<?php

namespace App\Enums;

enum EstadoEvaluacionDescriptor: string
{
    case Pendiente = 'PENDIENTE';
    case EnEvaluacion = 'EN_EVALUACION';
    case Observado = 'OBSERVADO';
    case Evaluado = 'EVALUADO';
}
