<?php

namespace App\Http\Requests\Evaluation;

use App\Enums\CodigoRol;
use App\Enums\EstadoModeloEvaluacion;
use App\Enums\TipoEscenario;
use App\Models\Evaluacion;
use App\Models\ModeloEvaluacion;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreEvaluationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Evaluacion::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'modelo_evaluacion_id' => ['required', 'integer', Rule::exists('modelos_evaluacion', 'id')->where('estado', EstadoModeloEvaluacion::Publicado->value)],
            'codigo' => ['required', 'string', 'max:50', 'unique:evaluaciones,codigo'], 'nombre' => ['required', 'string', 'max:200'],
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
            $model = ModeloEvaluacion::query()->with('dominios')->find($this->integer('modelo_evaluacion_id'));
            if ($model && collect(array_keys($this->input('responsables', [])))->map(fn ($id) => (int) $id)->sort()->values()->all() !== $model->dominios->pluck('id')->sort()->values()->all()) {
                $validator->errors()->add('responsables', 'Debe asignarse un responsable para cada dominio del instrumento.');
            }
            $this->validateUserRoles($validator, 'responsables', CodigoRol::ResponsableDominio);
            $this->validateUserRoles($validator, 'evaluadores', CodigoRol::EvaluadorExterno);
        }];
    }

    private function validateUserRoles(Validator $validator, string $field, CodigoRol $role): void
    {
        $ids = $field === 'responsables' ? array_values($this->input($field, [])) : $this->input($field, []);
        if ($ids === []) {
            return;
        }
        $valid = User::query()->whereIn('id', $ids)->where('activo', true)->whereHas('roles', fn ($query) => $query->where('codigo', $role->value))->count();
        if ($valid !== count(array_unique($ids))) {
            $validator->errors()->add($field, 'Todos los usuarios seleccionados deben estar activos y tener el rol correspondiente.');
        }
    }
}
