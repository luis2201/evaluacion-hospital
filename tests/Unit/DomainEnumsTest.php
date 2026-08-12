<?php

namespace Tests\Unit;

use App\Enums\CalificacionDescriptor;
use App\Enums\EstadoAutoevaluacion;
use App\Enums\EstadoEvaluacion;
use App\Enums\TipoEscenario;
use PHPUnit\Framework\TestCase;

class DomainEnumsTest extends TestCase
{
    public function test_evaluation_enums_expose_the_official_database_values(): void
    {
        $this->assertSame('CERRADA', EstadoEvaluacion::Cerrada->value);
        $this->assertSame('MIXTA', TipoEscenario::Mixta->value);
        $this->assertSame(0, CalificacionDescriptor::NoCumple->value);
        $this->assertSame(2, CalificacionDescriptor::Cumple->value);
        $this->assertSame('INCUMPLIDA', EstadoAutoevaluacion::Incumplida->value);
    }
}
