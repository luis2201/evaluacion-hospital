<?php

namespace App\Actions;

use App\Enums\EstadoAutoevaluacion;
use App\Enums\EstadoEvaluacion;
use App\Enums\EstadoEvaluacionDominio;
use App\Models\AutoevaluacionDominio;
use App\Models\Evaluacion;
use App\Models\EvaluacionDominio;
use App\Models\User;
use App\Services\AuditService;
use App\Services\EvaluationCalendarService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaveDomainSelfAssessment
{
    public function __construct(private readonly AuditService $audit, private readonly EvaluationCalendarService $calendar) {}

    public function execute(Evaluacion $evaluation, EvaluacionDominio $evaluationDomain, User $user, string $content, EstadoAutoevaluacion $state): AutoevaluacionDominio
    {
        return DB::transaction(function () use ($evaluation, $evaluationDomain, $user, $content, $state): AutoevaluacionDominio {
            $lockedEvaluation = Evaluacion::query()->lockForUpdate()->findOrFail($evaluation->id);
            $lockedDomain = EvaluacionDominio::query()->lockForUpdate()->findOrFail($evaluationDomain->id);

            if ($lockedDomain->evaluacion_id !== $lockedEvaluation->id) {
                abort(404);
            }

            if ($lockedDomain->responsable_id !== $user->id) {
                abort(403);
            }

            if ($lockedEvaluation->estado !== EstadoEvaluacion::CargaEvidencias || ! $this->calendar->isLoadingOpen($lockedEvaluation)) {
                throw ValidationException::withMessages(['autoevaluacion' => 'La evaluación no permite registrar autoevaluaciones en este estado.']);
            }

            $selfAssessment = AutoevaluacionDominio::query()
                ->where('evaluacion_dominio_id', $lockedDomain->id)
                ->lockForUpdate()
                ->first();

            if ($selfAssessment?->estado === EstadoAutoevaluacion::Enviada) {
                throw ValidationException::withMessages(['autoevaluacion' => 'La autoevaluación ya fue enviada y no puede modificarse.']);
            }

            $before = $selfAssessment?->getAttributes();
            $selfAssessment ??= new AutoevaluacionDominio(['evaluacion_dominio_id' => $lockedDomain->id]);
            $selfAssessment->fill([
                'contenido' => $content,
                'cantidad_palabras' => self::wordCount($content),
                'estado' => $state,
                'registrada_por' => $user->id,
                'enviada_at' => $state === EstadoAutoevaluacion::Enviada ? now() : null,
            ])->save();

            if ($state === EstadoAutoevaluacion::Enviada) {
                $lockedDomain->update(['estado' => EstadoEvaluacionDominio::Enviado, 'enviado_at' => now()]);
            }

            $this->audit->recordModel(
                $state === EstadoAutoevaluacion::Enviada ? 'AUTOEVALUACION_ENVIADA' : 'AUTOEVALUACION_GUARDADA',
                $selfAssessment,
                $before,
            );

            return $selfAssessment;
        });
    }

    public static function wordCount(string $content): int
    {
        preg_match_all('/[\p{L}\p{N}]+(?:[’\'-][\p{L}\p{N}]+)*/u', $content, $matches);

        return count($matches[0]);
    }
}
