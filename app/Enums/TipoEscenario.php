<?php

namespace App\Enums;

enum TipoEscenario: string
{
    case Clinica = 'CLINICA';
    case Quirurgica = 'QUIRURGICA';
    case Mixta = 'MIXTA';
    case Otra = 'OTRA';
}
