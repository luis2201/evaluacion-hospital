<?php

namespace App\Enums;

enum CalificacionDescriptor: int
{
    case NoCumple = 0;
    case CumpleParcialmente = 1;
    case Cumple = 2;
}
