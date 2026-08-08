<?php

namespace App\Http\Controllers;

use App\Enums\EstadoEvaluacion;
use App\Models\Evaluacion;
use App\Services\EvaluationCalendarService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request, EvaluationCalendarService $calendar): View
    {
        $calendar->syncAll();
        $query = Evaluacion::query()->visibleTo($request->user());
        $stats = ['total' => (clone $query)->count(), 'activas' => (clone $query)->whereIn('estado', [EstadoEvaluacion::CargaEvidencias, EstadoEvaluacion::EnEvaluacion])->count(), 'borradores' => (clone $query)->where('estado', EstadoEvaluacion::Borrador)->count(), 'cerradas' => (clone $query)->where('estado', EstadoEvaluacion::Cerrada)->count()];
        $recent = $query->with('modeloEvaluacion')->latest()->limit(5)->get();

        return view('dashboard', compact('stats', 'recent'));
    }
}
