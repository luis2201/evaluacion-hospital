<?php

namespace App\Http\Requests\Evaluation;

use App\Enums\CodigoRol;
use App\Enums\TipoEscenario;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateEvaluationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('evaluacion')) ?? false;
    }

    public function rules(): array
    {
        return [
            'codigo' => ['required', 'string', 'max:50', Rule::unique('evaluaciones')->ignore($this->route('evaluacion'))], 'nombre' => ['required', 'string', 'max:200'],
            'descripcion' => ['nullable', 'string', 'max:3000'], 'tipo_escenario' => ['required', Rule::enum(TipoEscenario::class)],
            'fecha_inicio' => ['required', 'date'], 'fecha_limite_carga' => ['required', 'date', 'after_or_equal:fecha_inicio'],
            'fecha_inicio_evaluacion' => ['nullable', 'date', 'after:fecha_limite_carga'], 'fecha_cierre_prevista' => ['nullable', 'date', 'after_or_equal:fecha_inicio_evaluacion'],
            'responsables' => ['required', 'array'], 'responsables.*' => ['required', 'integer', 'exists:users,id'],
            'evaluadores' => ['required', 'array', 'min:1'], 'evaluadores.*' => ['required', 'integer', 'distinct', 'exists:users,id'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $evaluation = $this->route('evaluacion')->loadMissing('dominios');
            $domainIds = $evaluation->dominios->pluck('dominio_id')->sort()->values()->all();
            $givenIds = collect(array_keys($this->input('responsables', [])))->map(fn ($id) => (int) $id)->sort()->values()->all();
            if ($givenIds !== $domainIds) {
                $validator->errors()->add('responsables', 'Debe asignarse un responsable para cada dominio.');
            }
            $this->validateUsers($validator, array_values($this->input('responsables', [])), CodigoRol::ResponsableDominio, 'responsables');
            $this->validateUsers($validator, $this->input('evaluadores', []), CodigoRol::EvaluadorExterno, 'evaluadores');
        }];
    }

    private function validateUsers(Validator $validator, array $ids, CodigoRol $role, string $field): void
    {
        $unique = array_unique($ids);
        $valid = User::query()->whereIn('id', $unique)->where('activo', true)->whereHas('roles', fn ($query) => $query->where('codigo', $role->value))->count();
        if ($valid !== count($unique)) {
            $validator->errors()->add($field, 'Los usuarios seleccionados no tienen el rol requerido o están inactivos.');
        }
    }
}
