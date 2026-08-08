<?php

namespace Tests\Feature;

use App\Enums\EstadoModeloEvaluacion;
use App\Models\Descriptor;
use App\Models\Dominio;
use App\Models\ModeloEvaluacion;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DatabaseSchemaTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    public function test_official_instrument_is_seeded_completely(): void
    {
        $this->assertDatabaseCount('roles', 4);
        $this->assertDatabaseCount('modelos_evaluacion', 1);
        $this->assertDatabaseCount('dominios', 5);
        $this->assertDatabaseCount('criterios', 17);
        $this->assertDatabaseCount('descriptores', 44);
        $this->assertDatabaseCount('categorias_resultado', 4);
        $this->assertSame(100.0, (float) Dominio::query()->sum('peso'));
    }

    public function test_instrument_relationships_and_enum_casts_are_available(): void
    {
        $modelo = ModeloEvaluacion::query()->with('dominios.criterios.descriptores')->firstOrFail();

        $this->assertSame(EstadoModeloEvaluacion::Publicado, $modelo->estado);
        $this->assertCount(5, $modelo->dominios);
        $this->assertSame(44, $modelo->dominios->sum(
            fn (Dominio $dominio) => $dominio->criterios->sum(
                fn ($criterio) => $criterio->descriptores->count(),
            ),
        ));
        $this->assertSame('ADMINISTRADOR', Role::query()->firstOrFail()->codigo);
        $this->assertSame(2, Descriptor::query()->firstOrFail()->puntaje_maximo);
    }

    public function test_mysql_views_and_integrity_triggers_are_installed(): void
    {
        $schema = DB::getDatabaseName();

        $views = DB::table('information_schema.views')->where('table_schema', $schema)->count();
        $triggers = DB::table('information_schema.triggers')->where('trigger_schema', $schema)->count();

        $this->assertSame(3, $views);
        $triggerNames = collect(DB::select(
            'SELECT TRIGGER_NAME AS name FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA = ?',
            [$schema],
        ))->pluck('name');

        $this->assertSame(6, $triggers);
        $this->assertTrue($triggerNames->contains('trg_no_insertar_enlace_evaluacion_finalizada'));
        $this->assertTrue($triggerNames->contains('trg_no_cambiar_enlace_evaluacion_finalizada'));
    }

    public function test_database_rejects_invalid_descriptor_score(): void
    {
        $user = User::factory()->create();
        $evaluacionId = DB::table('evaluaciones')->insertGetId([
            'modelo_evaluacion_id' => 1,
            'codigo' => 'TEST-001',
            'nombre' => 'Evaluación de prueba',
            'creada_por' => $user->id,
        ]);

        $this->expectException(QueryException::class);

        DB::table('evaluacion_descriptores')->insert([
            'evaluacion_id' => $evaluacionId,
            'descriptor_id' => 1,
            'calificacion' => 3,
        ]);
    }
}
