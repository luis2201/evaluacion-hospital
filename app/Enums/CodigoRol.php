<?php

namespace App\Enums;

enum CodigoRol: string
{
    case Administrador = 'ADMINISTRADOR';
    case ResponsableDominio = 'RESPONSABLE_DOMINIO';
    case EvaluadorExterno = 'EVALUADOR_EXTERNO';
    case AuditorLectura = 'AUDITOR_LECTURA';
}
