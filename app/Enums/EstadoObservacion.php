<?php

namespace App\Enums;

enum EstadoObservacion: string
{
    case Abierta = 'ABIERTA';
    case Respondida = 'RESPONDIDA';
    case Cerrada = 'CERRADA';
}
