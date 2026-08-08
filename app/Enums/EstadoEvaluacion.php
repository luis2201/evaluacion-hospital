<?php

namespace App\Enums;

enum EstadoEvaluacion: string
{
    case Borrador = 'BORRADOR';
    case CargaEvidencias = 'CARGA_EVIDENCIAS';
    case EnEvaluacion = 'EN_EVALUACION';
    case Cerrada = 'CERRADA';
    case Cancelada = 'CANCELADA';
}
