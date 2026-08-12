<?php

namespace App\Enums;

enum EstadoAutoevaluacion: string
{
    case Borrador = 'BORRADOR';
    case Enviada = 'ENVIADA';
    case Incumplida = 'INCUMPLIDA';
}
