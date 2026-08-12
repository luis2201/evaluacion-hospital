<?php

namespace Tests\Feature\Instrument;

use App\Enums\CodigoRol;
use App\Enums\EstadoModeloEvaluacion;
use App\Models\ModeloEvaluacion;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class InstrumentManagementTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    public function test_responsible_user_can_view_published_instrument(): void
    {
        $model = ModeloEvaluacion::query()->firstOrFail();
        $user = User::factory()->create();
        $user->roles()->attach(Role::query()->where('codigo', CodigoRol::ResponsableDominio->value)->firstOrFail(), ['created_at' => now()]);
        $this->actingAs($user)->get(route('instruments.show', $model))
            ->assertOk()->assertSee('Aspectos académicos')->assertSee('Escala oficial');
    }

    public function test_non_administrator_cannot_view_draft_instrument(): void
    {
        $draft = ModeloEvaluacion::query()->create(['nombre' => 'Borrador privado', 'version' => '1.0', 'estado' => EstadoModeloEvaluacion::Borrador]);
        $this->actingAs(User::factory()->create())->get(route('instruments.show', $draft))->assertForbidden();
    }

    public function test_administrator_can_clone_official_instrument_with_all_structure(): void
    {
        $source = ModeloEvaluacion::query()->firstOrFail();
        $this->actingAs($this->administrator())->post(route('admin.instruments.duplicate', $source), ['version' => '1.1'])
            ->assertRedirect();

        $copy = ModeloEvaluacion::query()->where('version', '1.1')->firstOrFail();
        $this->assertSame(EstadoModeloEvaluacion::Borrador, $copy->estado);
        $this->assertSame($source->id, $copy->modelo_origen_id);
        $this->assertSame(5, $copy->dominios()->count());
        $descriptorCount = DB::table('descriptores')->join('criterios', 'criterios.id', '=', 'descriptores.criterio_id')->join('dominios', 'dominios.id', '=', 'criterios.dominio_id')->where('dominios.modelo_evaluacion_id', $copy->id)->count();
        $this->assertSame(44, $descriptorCount);
        $this->assertSame(4, $copy->categoriasResultado()->count());
    }

    public function test_published_instrument_cannot_be_edited(): void
    {
        $model = ModeloEvaluacion::query()->firstOrFail();
        $this->actingAs($this->administrator())->put(route('admin.instruments.update', $model), [
            'nombre' => 'Cambio no permitido', 'version' => $model->version,
        ])->assertForbidden();
    }

    public function test_valid_draft_can_be_published_and_becomes_immutable(): void
    {
        $administrator = $this->administrator();
        $source = ModeloEvaluacion::query()->firstOrFail();
        $this->actingAs($administrator)->post(route('admin.instruments.duplicate', $source), ['version' => '1.2']);
        $copy = ModeloEvaluacion::query()->where('version', '1.2')->firstOrFail();

        $this->post(route('admin.instruments.publish', $copy))->assertSessionHas('status');
        $this->assertSame(EstadoModeloEvaluacion::Publicado, $copy->fresh()->estado);
        $this->put(route('admin.instruments.update', $copy), ['nombre' => $copy->nombre, 'version' => '1.2'])->assertForbidden();
    }

    public function test_invalid_empty_draft_cannot_be_published(): void
    {
        $draft = ModeloEvaluacion::query()->create(['nombre' => 'Vacío', 'version' => '0.1', 'estado' => EstadoModeloEvaluacion::Borrador]);
        $this->actingAs($this->administrator())->post(route('admin.instruments.publish', $draft))->assertSessionHasErrors('instrumento');
        $this->assertSame(EstadoModeloEvaluacion::Borrador, $draft->fresh()->estado);
    }

    private function administrator(): User
    {
        $user = User::factory()->create();
        $role = Role::query()->where('codigo', CodigoRol::Administrador->value)->firstOrFail();
        $user->roles()->attach($role, ['created_at' => now()]);

        return $user;
    }
}
