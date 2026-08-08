<?php

namespace App\Policies;

use App\Enums\EstadoModeloEvaluacion;
use App\Models\ModeloEvaluacion;
use App\Models\User;

class ModeloEvaluacionPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, ModeloEvaluacion $modelo): bool
    {
        return $modelo->estado !== EstadoModeloEvaluacion::Borrador || $user->isAdministrator();
    }

    public function create(User $user): bool
    {
        return $user->isAdministrator();
    }

    public function update(User $user, ModeloEvaluacion $modelo): bool
    {
        return $user->isAdministrator() && $modelo->isEditable();
    }

    public function publish(User $user, ModeloEvaluacion $modelo): bool
    {
        return $this->update($user, $modelo);
    }

    public function archive(User $user, ModeloEvaluacion $modelo): bool
    {
        return $user->isAdministrator() && $modelo->estado === EstadoModeloEvaluacion::Publicado;
    }

    public function replicate(User $user, ModeloEvaluacion $modelo): bool
    {
        return $user->isAdministrator() && $modelo->estado !== EstadoModeloEvaluacion::Borrador;
    }
}
