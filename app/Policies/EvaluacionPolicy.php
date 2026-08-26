<?php

namespace App\Policies;

use App\Enums\CodigoRol;
use App\Enums\EstadoEvaluacion;
use App\Models\Evaluacion;
use App\Models\EvaluacionDescriptor;
use App\Models\User;

class EvaluacionPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Evaluacion $evaluacion): bool
    {
        return $user->isAdministrator()
            || $user->hasRole(CodigoRol::AuditorLectura)
            || $evaluacion->creada_por === $user->id
            || $evaluacion->dominios()->where('responsable_id', $user->id)->exists()
            || $evaluacion->evaluadores()->whereKey($user->id)->exists();
    }

    public function create(User $user): bool
    {
        return $user->isAdministrator();
    }

    public function update(User $user, Evaluacion $evaluacion): bool
    {
        return $user->isAdministrator() && $evaluacion->estado === EstadoEvaluacion::Borrador;
    }

    public function start(User $user, Evaluacion $evaluacion): bool
    {
        return $this->update($user, $evaluacion);
    }

    public function startReview(User $user, Evaluacion $evaluacion): bool
    {
        return $user->isAdministrator() && $evaluacion->estado === EstadoEvaluacion::CargaEvidencias;
    }

    public function cancel(User $user, Evaluacion $evaluacion): bool
    {
        return $user->isAdministrator() && ! in_array($evaluacion->estado, [EstadoEvaluacion::Cerrada, EstadoEvaluacion::Cancelada], true);
    }

    public function manageEvidence(User $user, Evaluacion $evaluacion, EvaluacionDescriptor $evaluationDescriptor): bool
    {
        if ($evaluationDescriptor->evaluacion_id !== $evaluacion->id) {
            return false;
        }

        $domainId = $evaluationDescriptor->descriptor()->with('criterio')->first()?->criterio?->dominio_id;

        return $domainId !== null && $evaluacion->dominios()
            ->where('dominio_id', $domainId)
            ->where('responsable_id', $user->id)
            ->exists();
    }

    public function review(User $user, Evaluacion $evaluacion): bool
    {
        return $evaluacion->evaluadores()->whereKey($user->id)->exists();
    }

    public function viewResults(User $user, Evaluacion $evaluacion): bool
    {
        if ($user->isAdministrator() || $user->hasRole(CodigoRol::AuditorLectura)) {
            return true;
        }

        return $evaluacion->estado === EstadoEvaluacion::Cerrada
            && ! $evaluacion->evaluadores()->whereKey($user->id)->exists()
            && $evaluacion->dominios()->where('responsable_id', $user->id)->exists();
    }

    public function close(User $user, Evaluacion $evaluacion): bool
    {
        return $user->isAdministrator() && $evaluacion->estado === EstadoEvaluacion::EnEvaluacion;
    }

    public function manageSchedule(User $user, Evaluacion $evaluacion): bool
    {
        return $user->isAdministrator()
            && ! in_array($evaluacion->estado, [EstadoEvaluacion::Cerrada, EstadoEvaluacion::Cancelada], true);
    }

    public function respondObservation(User $user, Evaluacion $evaluacion, EvaluacionDescriptor $evaluationDescriptor): bool
    {
        if ($evaluationDescriptor->evaluacion_id !== $evaluacion->id) {
            return false;
        }

        $domainId = $evaluationDescriptor->descriptor()->with('criterio')->first()?->criterio?->dominio_id;

        return $domainId !== null && $evaluacion->dominios()
            ->where('dominio_id', $domainId)
            ->where('responsable_id', $user->id)
            ->exists();
    }
}
